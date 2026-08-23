<?php
/**
 * Helpers globaux de la capacité « rgpd » (bandeau de consentement cookies).
 *
 * Chargés par CapabilityManager::boot() si un module actif déclare
 * `"capabilities": ["rgpd"]`. Réutilise le composant central
 * framework/Views/theme/public/cookie-banner.php — piloté depuis
 * Admin → Configuration → RGPD / Cookies (aucun réglage local au module).
 *
 *   cookie_banner()   → rend le bandeau (une seule fois par requête)
 *   rgpd_enabled()    → le bandeau est-il actif côté configuration ?
 */

if (!function_exists('cookie_banner')) {
    /**
     * Rend le bandeau de consentement cookies partagé.
     * Idempotent : ne s'affiche qu'une fois par requête même si appelé plusieurs fois.
     * Le composant se masque de lui-même si le RGPD est désactivé en configuration.
     */
    function cookie_banner(): void
    {
        static $rendered = false;
        if ($rendered) { return; }
        $rendered = true;

        if (!defined('AEGIS_FRAMEWORK') || !defined('ROOT_PATH')) { return; }
        $banner = ROOT_PATH . '/framework/Views/theme/public/cookie-banner.php';
        if (is_file($banner)) { include $banner; }
    }
}

if (!function_exists('rgpd_enabled')) {
    /** Le bandeau RGPD est-il activé dans la configuration ? */
    function rgpd_enabled(): bool
    {
        try {
            $db = $GLOBALS['db'] ?? null;
            if ($db instanceof \Framework\Services\Database && class_exists('Configuration\\Services\\RgpdService')) {
                $cfg = (new \Configuration\Services\RgpdService($db))->getConfig();
                return !empty($cfg['enabled']);
            }
        } catch (\Throwable $e) {
            // config indisponible : considéré inactif
        }
        return false;
    }
}
