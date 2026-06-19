<?php
declare(strict_types=1);

namespace System\Controllers;

use Framework\Security\CSRFProtection;
use Framework\Services\SecurityCenterService;
use Framework\Services\SecurityFirewallService;

/**
 * SecurityController — Centre de sécurité (Aegis Framework).
 *
 * Pilote le SecurityCenterService : tableau de bord, configuration globale et
 * par catégorie, seuils, règles/détecteurs, listes blanche/noire, historique.
 * L'enforcement (blocage IP) reste délégué à SecurityFirewallService.
 */
class SecurityController
{
    private SecurityCenterService $center;
    private SecurityFirewallService $firewall;
    private CSRFProtection $csrf;

    public function __construct(
        SecurityCenterService $center,
        SecurityFirewallService $firewall,
        CSRFProtection $csrf
    ) {
        $this->center = $center;
        $this->firewall = $firewall;
        $this->csrf = $csrf;
    }

    public function index(): void
    {
        $this->requireAdmin();

        $dashboard = $this->center->getDashboard();
        $stats = $dashboard['stats'];
        $byCategory = $dashboard['by_category'];
        $bySeverity = $dashboard['by_severity'];
        $topIps = $dashboard['top_ips'];
        $blocks = $dashboard['blocks'];
        $events = $dashboard['events'];

        $settings = $this->center->getSettings();
        $rulesByCategory = $this->center->getRulesByCategory();
        $whitelist = $this->center->getWhitelist();
        $categoriesMeta = SecurityCenterService::CATEGORIES;
        $severities = SecurityCenterService::SEVERITIES;

        $csrfToken = $this->csrf->generateToken();

        require __DIR__ . '/../Views/admin/security/index.php';
    }

    public function saveSettings(): void
    {
        $this->requireAdmin();
        $this->checkCsrf();

        $this->center->setSetting('enabled', isset($_POST['enabled']) ? '1' : '0');
        $this->center->setSetting('auto_block', isset($_POST['auto_block']) ? '1' : '0');
        $this->center->setSetting('block_threshold', (string)max(1, (int)($_POST['block_threshold'] ?? 100)));
        $this->center->setSetting('block_duration_hours', (string)max(1, (int)($_POST['block_duration_hours'] ?? 24)));
        $this->center->setSetting('ban_threshold', (string)max(1, (int)($_POST['ban_threshold'] ?? 300)));
        $this->center->setSetting('log_retention_days', (string)max(1, (int)($_POST['log_retention_days'] ?? 30)));

        // Activation par catégorie.
        foreach (array_keys(SecurityCenterService::CATEGORIES) as $cat) {
            $this->center->setCategoryEnabled($cat, isset($_POST['cat'][$cat]));
        }

        $_SESSION['success'] = 'Réglages de sécurité enregistrés.';
        redirect('/admin/security');
    }

    public function saveRules(): void
    {
        $this->requireAdmin();
        $this->checkCsrf();

        $rules = $this->center->getRules();
        $enabledKeys = $_POST['rule_enabled'] ?? [];
        foreach ($rules as $key => $rule) {
            $score = (int)($_POST['rule_score'][$key] ?? $rule['score']);
            $severity = (string)($_POST['rule_severity'][$key] ?? $rule['severity']);
            $enabled = isset($enabledKeys[$key]);
            $this->center->updateRule($key, $score, $severity, $enabled);
        }

        $_SESSION['success'] = 'Détecteurs mis à jour.';
        redirect('/admin/security');
    }

    public function whitelistAdd(): void
    {
        $this->requireAdmin();
        $this->checkCsrf();

        $ip = trim((string)($_POST['ip_address'] ?? ''));
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $_SESSION['error'] = 'Adresse IP invalide.';
            redirect('/admin/security');
        }
        $this->center->addToWhitelist($ip, trim((string)($_POST['note'] ?? '')), $_SESSION['user_id'] ?? null);
        $_SESSION['success'] = 'IP ajoutée à la liste blanche : ' . $ip;
        redirect('/admin/security');
    }

    public function whitelistRemove(): void
    {
        $this->requireAdmin();
        $this->checkCsrf();

        $ip = trim((string)($_POST['ip_address'] ?? ''));
        $this->center->removeFromWhitelist($ip);
        $_SESSION['success'] = 'IP retirée de la liste blanche : ' . $ip;
        redirect('/admin/security');
    }

    public function blacklistAdd(): void
    {
        $this->requireAdmin();
        $this->checkCsrf();

        $ip = trim((string)($_POST['ip_address'] ?? ''));
        $reason = trim((string)($_POST['reason'] ?? 'Liste noire (manuel)'));
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $_SESSION['error'] = 'Adresse IP invalide.';
            redirect('/admin/security');
        }
        $this->center->addToBlacklist($ip, $reason, $_SESSION['user_id'] ?? null);
        $_SESSION['success'] = 'IP bloquée définitivement : ' . $ip;
        redirect('/admin/security');
    }

    /** Blocage temporaire manuel. */
    public function block(): void
    {
        $this->requireAdmin();
        $this->checkCsrf();

        $ip = trim((string)($_POST['ip_address'] ?? ''));
        $reason = trim((string)($_POST['reason'] ?? 'Blocage manuel'));
        $minutes = max(5, min(43200, (int)($_POST['minutes'] ?? 60)));
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $_SESSION['error'] = 'Adresse IP invalide.';
            redirect('/admin/security');
        }
        $this->firewall->blockIp($ip, $reason, $minutes * 60, false, $_SESSION['user_id'] ?? null);
        $_SESSION['success'] = 'IP bloquée : ' . $ip;
        redirect('/admin/security');
    }

    public function unblock(): void
    {
        $this->requireAdmin();
        $this->checkCsrf();

        $ip = trim((string)($_POST['ip_address'] ?? ''));
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $_SESSION['error'] = 'Adresse IP invalide.';
            redirect('/admin/security');
        }
        $this->firewall->unblockIp($ip);
        $_SESSION['success'] = 'IP débloquée : ' . $ip;
        redirect('/admin/security');
    }

    public function purgeEvents(): void
    {
        $this->requireAdmin();
        $this->checkCsrf();

        $days = (int)($_POST['older_than_days'] ?? 0);
        $n = $this->center->purgeEvents($days > 0 ? $days : null);
        $_SESSION['success'] = "Historique purgé ({$n} événements supprimés).";
        redirect('/admin/security');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function checkCsrf(): void
    {
        try {
            $this->csrf->validateToken($_POST['csrf_token'] ?? '');
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Token CSRF invalide.';
            redirect('/admin/security');
        }
    }

    private function requireAdmin(): void
    {
        if (empty($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'], true)) {
            redirect('/auth/login');
        }
    }
}
