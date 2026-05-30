<?php
declare(strict_types=1);

namespace Configuration\Services;

use Framework\Services\Database;

/**
 * RgpdService — bandeau de consentement aux cookies (conforme CNIL).
 * Service dédié : activation, position, durée de validité, catégories,
 * textes, apparence et versionnement du consentement.
 */
class RgpdService
{
    private SettingsService $settings;

    /** Durées de validité proposées (jours). Max légal CNIL : 13 mois (~395 j). */
    public const VALIDITY = [30, 90, 180, 365, 395];

    public function __construct(Database $db)
    {
        $this->settings = new SettingsService($db);
    }

    /**
     * Configuration complète (valeurs par défaut incluses).
     */
    public function getConfig(): array
    {
        $accentDefault = '#6366f1';

        // Catégories : essentiels (toujours) + 3 optionnelles pilotées par interrupteur
        $catActive = [
            'analytics' => $this->settings->get('cookie_cat_analytics', '1') === '1' || $this->settings->get('cookie_cat_analytics', 1) == 1,
            'marketing' => (string)$this->settings->get('cookie_cat_marketing', '0') === '1',
            'social'    => (string)$this->settings->get('cookie_cat_social', '0') === '1',
        ];
        $defs = [
            ['code' => 'essential', 'icon' => '🔒', 'name' => 'Cookies essentiels',     'description' => "Session, authentification, sécurité, mémorisation du consentement.", 'required' => true,  'active' => true],
            ['code' => 'analytics', 'icon' => '📊', 'name' => 'Cookies analytiques',     'description' => "Statistiques de navigation anonymes (Google Analytics, Matomo…).", 'required' => false, 'active' => $catActive['analytics']],
            ['code' => 'marketing', 'icon' => '🎯', 'name' => 'Cookies marketing',       'description' => "Publicités personnalisées, retargeting.", 'required' => false, 'active' => $catActive['marketing']],
            ['code' => 'social',    'icon' => '💬', 'name' => 'Cookies réseaux sociaux', 'description' => "Widgets de partage, boutons et intégrations sociales.", 'required' => false, 'active' => $catActive['social']],
        ];

        return [
            'enabled'       => (bool) $this->settings->get('cookies_banner_enabled', false),
            'position'      => $this->settings->get('cookie_banner_position', 'bottom') === 'top' ? 'top' : 'bottom',
            'validity_days' => (int) ($this->settings->get('cookie_validity_days', 180) ?: 180),
            'policy_url'    => (string) $this->settings->get('rgpd_policy_url', ''),
            'title'         => (string) $this->settings->get('rgpd_title', 'Nous respectons votre vie privée 🍪'),
            'intro'         => (string) $this->settings->get('rgpd_text', "Nous utilisons des cookies pour améliorer votre expérience. Certains sont essentiels au fonctionnement du site, d'autres optionnels. Vous pouvez accepter, refuser ou personnaliser vos choix."),
            'version'       => (int) $this->settings->get('rgpd_version', 1),
            'colors'        => [
                'bg'     => (string) $this->settings->get('cookie_bg', '#16162a'),
                'text'   => (string) $this->settings->get('cookie_text_color', '#e8e8f0'),
                'accent' => (string) ($this->settings->get('cookie_accent', '') ?: $accentDefault),
                'refuse' => (string) $this->settings->get('cookie_btn_refuse_bg', '#2a2a3e'),
            ],
            'radius'        => (int) $this->settings->get('cookie_border_radius', 14),
            'categories'    => $defs,
        ];
    }

    /**
     * Enregistrer depuis $_POST. Incrémente la version → consentement redemandé.
     */
    public function save(array $p): bool
    {
        $hex = fn($v, $d) => preg_match('/^#[0-9a-fA-F]{6}$/', (string)$v) ? (string)$v : $d;

        return $this->settings->setMultiple([
            'cookies_banner_enabled' => ['value' => !empty($p['cookies_banner_enabled']) ? 1 : 0, 'type' => 'bool'],
            'cookie_banner_position' => ['value' => ($p['cookie_banner_position'] ?? 'bottom') === 'top' ? 'top' : 'bottom', 'type' => 'string'],
            'cookie_validity_days'   => ['value' => in_array((int)($p['cookie_validity_days'] ?? 180), self::VALIDITY, true) ? (int)$p['cookie_validity_days'] : 180, 'type' => 'int'],
            'rgpd_policy_url'        => ['value' => trim((string)($p['rgpd_policy_url'] ?? '')), 'type' => 'string'],
            'rgpd_title'             => ['value' => trim((string)($p['rgpd_title'] ?? '')) ?: 'Gestion des cookies', 'type' => 'string'],
            'rgpd_text'              => ['value' => trim((string)($p['rgpd_text'] ?? '')), 'type' => 'string'],
            'cookie_cat_analytics'   => ['value' => !empty($p['cookie_cat_analytics']) ? '1' : '0', 'type' => 'string'],
            'cookie_cat_marketing'   => ['value' => !empty($p['cookie_cat_marketing']) ? '1' : '0', 'type' => 'string'],
            'cookie_cat_social'      => ['value' => !empty($p['cookie_cat_social']) ? '1' : '0', 'type' => 'string'],
            'cookie_bg'              => ['value' => $hex($p['cookie_bg'] ?? '', '#16162a'), 'type' => 'string'],
            'cookie_text_color'      => ['value' => $hex($p['cookie_text_color'] ?? '', '#e8e8f0'), 'type' => 'string'],
            'cookie_accent'          => ['value' => $hex($p['cookie_accent'] ?? '', '#6366f1'), 'type' => 'string'],
            'cookie_btn_refuse_bg'   => ['value' => $hex($p['cookie_btn_refuse_bg'] ?? '', '#2a2a3e'), 'type' => 'string'],
            'cookie_border_radius'   => ['value' => max(0, min(24, (int)($p['cookie_border_radius'] ?? 14))), 'type' => 'int'],
            'rgpd_version'           => ['value' => ((int)$this->settings->get('rgpd_version', 1)) + 1, 'type' => 'int'],
        ]);
    }

    /** Force le réaffichage du bandeau pour tous les visiteurs (invalide les consentements). */
    public function resetConsents(): bool
    {
        return $this->settings->set('rgpd_version', ((int)$this->settings->get('rgpd_version', 1)) + 1, 'int');
    }
}
