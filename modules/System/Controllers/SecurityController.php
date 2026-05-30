<?php
declare(strict_types=1);

namespace System\Controllers;

use Framework\Security\CSRFProtection;
use Framework\Services\SecurityFirewallService;

/**
 * SecurityController - Tableau de bord de la protection anti-abus.
 */
class SecurityController
{
    private SecurityFirewallService $firewall;
    private CSRFProtection $csrf;

    public function __construct(SecurityFirewallService $firewall, CSRFProtection $csrf)
    {
        $this->firewall = $firewall;
        $this->csrf = $csrf;
    }

    public function index(): void
    {
        $this->requireAdmin();

        $dashboard = $this->firewall->getDashboardData();
        $stats = $dashboard['stats'];
        $blocks = $dashboard['blocks'];
        $events = $dashboard['events'];
        $csrfToken = $this->csrf->generateToken();

        require __DIR__ . '/../Views/admin/security/index.php';
    }

    public function unblock(): void
    {
        $this->requireAdmin();

        try {
            $this->csrf->validateToken($_POST['csrf_token'] ?? '');
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Token CSRF invalide';
            redirect('/admin/security');
        }

        $ip = trim((string)($_POST['ip_address'] ?? ''));
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $_SESSION['error'] = 'Adresse IP invalide';
            redirect('/admin/security');
        }

        $this->firewall->unblockIp($ip);
        $_SESSION['success'] = 'IP debloquee : ' . $ip;
        redirect('/admin/security');
    }

    public function block(): void
    {
        $this->requireAdmin();

        try {
            $this->csrf->validateToken($_POST['csrf_token'] ?? '');
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Token CSRF invalide';
            redirect('/admin/security');
        }

        $ip = trim((string)($_POST['ip_address'] ?? ''));
        $reason = trim((string)($_POST['reason'] ?? 'Blocage manuel'));
        $minutes = max(5, min(10080, (int)($_POST['minutes'] ?? 60)));

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $_SESSION['error'] = 'Adresse IP invalide';
            redirect('/admin/security');
        }

        $this->firewall->blockIp($ip, $reason, $minutes * 60, false, $_SESSION['user_id'] ?? null);
        $_SESSION['success'] = 'IP bloquee : ' . $ip;
        redirect('/admin/security');
    }

    private function requireAdmin(): void
    {
        if (empty($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'], true)) {
            redirect('/auth/login');
        }
    }
}
