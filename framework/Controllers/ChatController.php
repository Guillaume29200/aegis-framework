<?php
declare(strict_types=1);

namespace Framework\Controllers;

use Framework\Services\Database;
use Framework\Services\ChatService;
use Framework\Security\CSRFProtection;
use Framework\Services\AuditService;

/**
 * Assistant IA global (icône 🧠 du header, disponible sur toutes les pages
 * admin). Pré-intégré au core — ne dépend d'aucun module tiers. Conversations
 * scoppées par utilisateur — chacun a son propre historique.
 */
class ChatController
{
    private ChatService $chat;

    public function __construct(private Database $db, private CSRFProtection $csrf)
    {
        $this->chat = new ChatService($db);
    }

    public function index(): void
    {
        $this->render(null);
    }

    public function show(int $id): void
    {
        $this->render($id);
    }

    private function render(?int $id): void
    {
        $this->requireAdmin();
        $userId = (int)($_SESSION['user_id'] ?? 0);

        $conversations = $this->chat->getConversations($userId);
        $activeConversation = null;
        $messages = [];

        if ($id) {
            $activeConversation = $this->chat->getConversation($id, $userId);
            if (!$activeConversation) {
                redirect('/admin/chat?flash_type=err&flash_msg=' . urlencode('Conversation introuvable'));
            }
            $messages = $this->chat->getMessages($id);
        }

        $providers = $this->chat->availableProviders();
        $primerLastUpdated = ChatService::primerLastUpdated();

        $pageTitle = 'Assistant IA — Aegis Framework';
        $csrfToken = $this->csrf->generateToken();
        require ROOT_PATH . '/framework/Views/admin/chat.php';
    }

    public function send(): void
    {
        header('Content-Type: application/json');
        if (empty($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'], true)) {
            echo json_encode(['ok' => false, 'error' => 'Non autorisé']);
            return;
        }
        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['ok' => false, 'error' => 'Token invalide']);
            return;
        }

        $userId         = (int)($_SESSION['user_id'] ?? 0);
        $isNew          = empty($_POST['conversation_id']);
        $conversationId = !$isNew ? (int)$_POST['conversation_id'] : null;
        $message        = (string)($_POST['message'] ?? '');
        $provider       = (string)($_POST['provider'] ?? '');
        $model          = (string)($_POST['model'] ?? '');

        $result = $this->chat->sendMessage($userId, $conversationId, $message, $provider, $model);

        if ($result['ok'] && $isNew && !empty($result['conversation_id'])) {
            $conv = $this->chat->getConversation((int)$result['conversation_id'], $userId);
            $result['conversation_title'] = $conv['title'] ?? null;
        }

        echo json_encode($result);
    }

    public function rename(int $id): void
    {
        header('Content-Type: application/json');
        if (empty($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'], true)) {
            echo json_encode(['ok' => false]);
            return;
        }
        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['ok' => false]);
            return;
        }
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $ok = $this->chat->renameConversation($id, $userId, (string)($_POST['title'] ?? ''));
        echo json_encode(['ok' => $ok]);
    }

    public function delete(int $id): void
    {
        header('Content-Type: application/json');
        if (empty($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'], true)) {
            echo json_encode(['ok' => false]);
            return;
        }
        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['ok' => false]);
            return;
        }
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $ok = $this->chat->deleteConversation($id, $userId);
        if ($ok) {
            AuditService::record('core.chat_delete', 'conversation', (string)$id, 'Conversation IA supprimée');
        }
        echo json_encode(['ok' => $ok]);
    }

    private function requireAdmin(): void
    {
        if (empty($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'], true)) {
            redirect('/auth/login');
        }
    }
}
