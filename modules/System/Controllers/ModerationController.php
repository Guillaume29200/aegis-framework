<?php
declare(strict_types=1);

namespace System\Controllers;

use Framework\Services\Database;
use Framework\Services\ModerationService;
use Framework\Services\AuditService;
use Framework\Security\CSRFProtection;

/**
 * Signalements : endpoint membre (report) + file de modération admin.
 */
class ModerationController
{
    private ModerationService $mod;

    public function __construct(Database $db, CSRFProtection $csrf)
    {
        $this->mod = new ModerationService($db);
    }

    private function json(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    private function guardAdmin(): bool
    {
        if (empty($_SESSION['user_id'])) { header('Location: ' . u('/auth/login')); return false; }
        if (!in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'], true)) {
            http_response_code(403); echo 'Accès refusé'; return false;
        }
        return true;
    }

    /** Signalement par un membre connecté (AJAX). */
    public function report(): void
    {
        $uid = (int)($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) { $this->json(['success' => false, 'error' => 'Connectez-vous pour signaler']); return; }

        $type   = trim((string)($_POST['content_type'] ?? ''));
        $id     = (int)($_POST['content_id'] ?? 0);
        $reason = trim((string)($_POST['reason'] ?? 'other'));
        $note   = trim((string)($_POST['note'] ?? ''));
        $name   = (string)($_SESSION['username'] ?? '');

        $res = $this->mod->report($type, $id, $uid, $name, $reason, $note);
        $this->json(['success' => $res['ok'], 'error' => $res['error'] ?? '']);
    }

    /** File de modération (admin). */
    public function index(): void
    {
        if (!$this->guardAdmin()) return;

        $status  = (string)($_GET['status'] ?? 'pending');
        $items   = $this->mod->listGrouped($status, 200);
        $pending = $this->mod->pendingCount();
        $reasons = ModerationService::REASONS;

        $currentUser = ['username' => $_SESSION['username'] ?? 'Admin', 'role' => $_SESSION['role'] ?? 'admin'];
        $pageTitle   = 'Modération';
        require __DIR__ . '/../Views/admin/moderation.php';
    }

    /** Traite les signalements d'un contenu (résoudre / rejeter). */
    public function resolve(): void
    {
        if (!$this->guardAdmin()) return;

        $type   = trim((string)($_POST['content_type'] ?? ''));
        $id     = (int)($_POST['content_id'] ?? 0);
        $action = (string)($_POST['action'] ?? '');
        $status = $action === 'reject' ? 'rejected' : 'resolved';

        if ($type !== '' && $id > 0) {
            $this->mod->setStatus($type, $id, $status, (int)$_SESSION['user_id']);
            AuditService::record('moderation.' . $status, $type, (string)$id,
                $status === 'resolved' ? 'Signalement traité' : 'Signalement rejeté');
        }
        $this->json(['success' => true, 'pending' => $this->mod->pendingCount()]);
    }
}
