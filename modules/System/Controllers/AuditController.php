<?php
declare(strict_types=1);

namespace System\Controllers;

use Framework\Services\Database;
use Framework\Services\AuditService;
use Framework\Security\CSRFProtection;

/**
 * Consultation + gestion (suppression/purge) du journal d'audit.
 */
class AuditController
{
    private AuditService $audit;
    private CSRFProtection $csrf;

    public function __construct(Database $db, CSRFProtection $csrf)
    {
        $this->audit = new AuditService($db);
        $this->csrf = $csrf;
    }

    private function guard(): bool
    {
        if (empty($_SESSION['logged_in']) && empty($_SESSION['user_id'])) {
            header('Location: ' . u('/auth/login'));
            return false;
        }
        if (!in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'], true)) {
            http_response_code(403);
            echo 'Accès refusé';
            return false;
        }
        return true;
    }

    public function index(): void
    {
        if (!$this->guard()) return;

        $filters = [
            'action' => trim((string)($_GET['action'] ?? '')),
            'q'      => trim((string)($_GET['q'] ?? '')),
        ];
        $page    = max(1, (int)($_GET['page'] ?? 1));

        $result    = $this->audit->query($filters, $page, 40);
        $actions   = $this->audit->distinctActions();
        $csrfToken = $this->csrf->generateToken();

        $pageTitle = 'Journal d\'audit';
        require __DIR__ . '/../Views/admin/audit.php';
    }

    /** Supprime une entrée précise du journal. */
    public function delete(): void
    {
        if (!$this->guard()) return;
        try {
            $this->csrf->validateToken($_POST['csrf_token'] ?? '');
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Token CSRF invalide.';
            redirect('/admin/audit');
        }

        $id = (int)($_POST['id'] ?? 0);
        $ok = $this->audit->deleteOne($id);
        $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Entrée supprimée.' : 'Entrée introuvable.';
        redirect('/admin/audit');
    }

    /** Purge le journal (tout, ou plus vieux que N jours). Action sensible : elle-même tracée. */
    public function purge(): void
    {
        if (!$this->guard()) return;
        try {
            $this->csrf->validateToken($_POST['csrf_token'] ?? '');
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Token CSRF invalide.';
            redirect('/admin/audit');
        }

        $days = (int)($_POST['older_than_days'] ?? 0);
        $n = $this->audit->purge($days > 0 ? $days : null);
        AuditService::record(
            'audit.purged',
            'audit_log',
            null,
            $days > 0 ? "{$n} entrée(s) de plus de {$days} jour(s) purgées" : "{$n} entrée(s) purgées (historique complet)"
        );
        $_SESSION['success'] = "Journal purgé ({$n} entrée(s) supprimée(s)).";
        redirect('/admin/audit');
    }
}
