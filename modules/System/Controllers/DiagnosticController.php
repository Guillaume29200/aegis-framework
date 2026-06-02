<?php
declare(strict_types=1);

namespace System\Controllers;

use Framework\Security\CSRFProtection;
use Framework\Services\Database;
use Framework\ModuleManager\ModuleManager;
use System\Services\DiagnosticService;

/**
 * DiagnosticController — page de santé / diagnostic de l'installation.
 */
class DiagnosticController
{
    private DiagnosticService $diag;
    private CSRFProtection $csrf;

    public function __construct(Database $db, CSRFProtection $csrf, ModuleManager $moduleManager)
    {
        $this->diag = new DiagnosticService($db, $moduleManager);
        $this->csrf = $csrf;
    }

    public function index(): void
    {
        $this->requireAdmin();

        $checks    = $this->diag->run();
        $summary   = $this->diag->summary($checks);
        $csrfToken = $this->csrf->generateToken();

        require __DIR__ . '/../Views/admin/diagnostic/index.php';
    }

    public function repair(): void
    {
        $this->requireAdmin();
        try {
            $this->csrf->validateToken($_POST['csrf_token'] ?? '');
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Token CSRF invalide.';
            redirect('/admin/diagnostic');
        }

        $fix    = preg_replace('/[^a-z_]/', '', (string)($_POST['fix'] ?? ''));
        $target = preg_replace('/[^A-Za-z0-9_-]/', '', (string)($_POST['target'] ?? ''));

        $result = $this->diag->repair($fix, $target);
        $_SESSION[$result['success'] ? 'success' : 'error'] = ($result['success'] ? '✅ ' : '❌ ') . $result['message'];
        redirect('/admin/diagnostic');
    }

    private function requireAdmin(): void
    {
        if (empty($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'], true)) {
            redirect('/auth/login');
        }
    }
}
