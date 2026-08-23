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

        // Filtrage géographique.
        $geo          = $this->geo();
        $geoMode      = $geo->mode();
        $geoPays      = $geo->paysChoisis();
        $geoActif     = $geo->actif();
        $geoSwitch    = $geo->interrupteur();
        $geoEmpechement = $geo->empechement();
        $geoBase      = $geo->base()->etat();
        $geoConnus    = $geoBase['existe'] ? $geo->paysConnus() : [];
        $geoIpAdmin   = $this->firewall->getClientIp();
        $geoPaysAdmin = $geo->base()->pays($geoIpAdmin);

        require __DIR__ . '/../Views/admin/security/index.php';
    }

    /**
     * Le filtre géographique.
     *
     * Récupéré depuis l'amorçage plutôt que reconstruit : c'est la même
     * instance que celle qui décide en début de requête, donc la page affiche
     * exactement ce qui s'applique.
     */
    private function geo(): \Framework\Services\CountryFirewall
    {
        $existant = $GLOBALS['countryFirewall'] ?? null;

        if ($existant instanceof \Framework\Services\CountryFirewall) {
            return $existant;
        }

        return new \Framework\Services\CountryFirewall($GLOBALS['db']);
    }

    /** Enregistrer le filtrage géographique. */
    public function saveGeo(): void
    {
        $this->requireAdmin();
        $this->checkCsrf();

        $geo   = $this->geo();
        $actif = isset($_POST['geo_enabled']);
        $mode  = (string) ($_POST['geo_mode'] ?? \Framework\Services\CountryFirewall::MODE_NOIRE);
        $pays  = \Framework\Services\CountryFirewall::analyser(
            is_array($_POST['geo_countries'] ?? null)
                ? implode(',', $_POST['geo_countries'])
                : (string) ($_POST['geo_countries'] ?? '')
        );

        if (!in_array($mode, [
            \Framework\Services\CountryFirewall::MODE_NOIRE,
            \Framework\Services\CountryFirewall::MODE_BLANCHE,
        ], true)) {
            $mode = \Framework\Services\CountryFirewall::MODE_NOIRE;
        }

        // Se bloquer soi-même est l'erreur la plus facile à commettre ici, et
        // la plus pénible à défaire. On refuse avant d'enregistrer.
        $sien = $geo->seTirerUneBalle($mode, $pays, $this->firewall->getClientIp(), $actif);
        if ($sien !== null) {
            $_SESSION['error'] = sprintf(
                'Enregistrement refusé : cette configuration bloquerait votre propre pays (%s). '
              . 'Ajoutez-le aux pays autorisés, ou retirez-le des pays bloqués.',
                \Framework\Services\CountryFirewall::nomPays($sien)
            );
            redirect('/admin/security');
            return;
        }

        if ($actif && $pays === []) {
            $_SESSION['error'] = 'Enregistrement refusé : aucun pays sélectionné. '
                               . 'Une liste vide ne protégerait de rien en liste noire, et fermerait le site en liste blanche.';
            redirect('/admin/security');
            return;
        }

        // La liste et le mode sont enregistrés même à l'arrêt : couper la
        // protection ne doit pas faire perdre le travail de sélection.
        $this->center->setSetting('geo_enabled', $actif ? '1' : '0');
        $this->center->setSetting('geo_mode', $mode);
        $this->center->setSetting('geo_countries', implode(',', $pays));

        // Sans cela, le réglage ne prendrait effet qu'à l'expiration du cache.
        $geo->oublier();

        \Framework\Services\AuditService::record(
            'security.geo_saved',
            'security_center',
            null,
            sprintf('actif=%s mode=%s pays=%s', $actif ? '1' : '0', $mode, $pays ? implode(',', $pays) : '(aucun)')
        );

        $_SESSION['success'] = !$actif
            ? sprintf('Filtrage géographique désactivé. La sélection de %d pays est conservée.', count($pays))
            : sprintf(
                'Filtrage géographique actif : %s, %d pays.',
                $mode === \Framework\Services\CountryFirewall::MODE_NOIRE ? 'liste noire' : 'liste blanche',
                count($pays)
            );

        redirect('/admin/security');
    }

    /**
     * (Re)construire la base IPv4 → pays.
     *
     * Une minute environ : les cinq registres pèsent 27 Mo. Le temps limite de
     * PHP est relevé pour la durée de l'opération, faute de quoi elle serait
     * interrompue au milieu — la construction écrit dans un fichier temporaire,
     * donc la base en service resterait intacte, mais l'administrateur n'aurait
     * qu'une page blanche pour l'expliquer.
     */
    public function rebuildGeoDatabase(): void
    {
        $this->requireAdmin();
        $this->checkCsrf();

        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        $base = $this->geo()->base();
        $r = $base->construire();

        if (!empty($r['success'])) {
            $base->oublierCodes();
            \Framework\Services\AuditService::record(
                'security.geo_database_built',
                'security_center',
                null,
                sprintf('%d plages', (int) ($r['plages'] ?? 0))
            );
            $_SESSION['success'] = $r['message'];
        } else {
            $_SESSION['error'] = $r['message'];
        }

        redirect('/admin/security');
    }

    /** Éprouver une adresse contre la configuration enregistrée. */
    public function testGeo(): void
    {
        $this->requireAdmin();
        $this->checkCsrf();

        $ip  = trim((string) ($_POST['ip'] ?? ''));
        $geo = $this->geo();

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $_SESSION['error'] = 'Adresse IP invalide.';
            redirect('/admin/security');
            return;
        }

        $pays = $geo->base()->pays($ip);
        $nom  = $pays !== null ? \Framework\Services\CountryFirewall::nomPays($pays) : null;

        if ($pays === null) {
            $_SESSION['success'] = sprintf(
                '%s : pays inconnu (adresse privée, IPv6, ou plage non attribuée). '
              . 'Un pays inconnu n\'est jamais bloqué.',
                $ip
            );
        } elseif (!$geo->actif()) {
            $_SESSION['success'] = sprintf('%s → %s (%s). Le filtre est inactif : l\'accès est autorisé.', $ip, $nom, $pays);
        } elseif ($geo->refus($ip) !== null) {
            $_SESSION['error'] = sprintf('⛔ %s → %s (%s) : cette adresse serait REFUSÉE.', $ip, $nom, $pays);
        } else {
            $_SESSION['success'] = sprintf('✅ %s → %s (%s) : cette adresse serait autorisée.', $ip, $nom, $pays);
        }

        redirect('/admin/security');
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

        \Framework\Services\AuditService::record(
            'security.settings_saved',
            'security_center',
            null,
            sprintf(
                'enabled=%s auto_block=%s block_threshold=%s ban_threshold=%s log_retention_days=%s',
                (string)($_POST['enabled'] ?? '0'),
                (string)($_POST['auto_block'] ?? '0'),
                (string)($_POST['block_threshold'] ?? ''),
                (string)($_POST['ban_threshold'] ?? ''),
                (string)($_POST['log_retention_days'] ?? '')
            )
        );

        $_SESSION['success'] = 'Réglages de sécurité enregistrés.';
        redirect('/admin/security');
    }

    public function saveRules(): void
    {
        $this->requireAdmin();
        $this->checkCsrf();

        $rules = $this->center->getRules();
        $enabledKeys = $_POST['rule_enabled'] ?? [];
        $changed = [];
        foreach ($rules as $key => $rule) {
            $score = (int)($_POST['rule_score'][$key] ?? $rule['score']);
            $severity = (string)($_POST['rule_severity'][$key] ?? $rule['severity']);
            $enabled = isset($enabledKeys[$key]);
            $this->center->updateRule($key, $score, $severity, $enabled);
            if ($enabled !== (bool)($rule['enabled'] ?? true)) {
                $changed[] = $key . '=' . ($enabled ? 'on' : 'off');
            }
        }

        \Framework\Services\AuditService::record(
            'security.rules_saved',
            'security_center',
            null,
            $changed ? ('Détecteurs modifiés : ' . implode(', ', $changed)) : 'Détecteurs mis à jour (scores/sévérités)'
        );

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
        \Framework\Services\AuditService::record('security.whitelist_add', 'ip', $ip, 'IP ajoutée à la liste blanche');
        $_SESSION['success'] = 'IP ajoutée à la liste blanche : ' . $ip;
        redirect('/admin/security');
    }

    public function whitelistRemove(): void
    {
        $this->requireAdmin();
        $this->checkCsrf();

        $ip = trim((string)($_POST['ip_address'] ?? ''));
        $this->center->removeFromWhitelist($ip);
        \Framework\Services\AuditService::record('security.whitelist_remove', 'ip', $ip, 'IP retirée de la liste blanche');
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
        \Framework\Services\AuditService::record('security.blacklist_add', 'ip', $ip, 'IP bloquée définitivement — ' . $reason);
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
        \Framework\Services\AuditService::record('security.block_ip', 'ip', $ip, 'IP bloquée ' . $minutes . ' min — ' . $reason);
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
        \Framework\Services\AuditService::record('security.unblock_ip', 'ip', $ip, 'IP débloquée');
        $_SESSION['success'] = 'IP débloquée : ' . $ip;
        redirect('/admin/security');
    }

    public function purgeEvents(): void
    {
        $this->requireAdmin();
        $this->checkCsrf();

        $days = (int)($_POST['older_than_days'] ?? 0);
        $n = $this->center->purgeEvents($days > 0 ? $days : null);
        // Action la plus sensible du centre : purger l'historique peut effacer les
        // traces d'une intrusion. Toujours tracée, même en cas de purge totale.
        \Framework\Services\AuditService::record(
            'security.events_purged',
            'security_center',
            null,
            $days > 0 ? "{$n} événement(s) de plus de {$days} jour(s) purgés" : "{$n} événement(s) purgés (historique complet)"
        );
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
