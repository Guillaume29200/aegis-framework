<?php
/**
 * Helpers globaux de la capacité « analytics ».
 *
 * Chargés par CapabilityManager::boot() si un module actif déclare
 * `"capabilities": ["analytics"]`. Adaptateur mince vers le module Analytics
 * (s'il est installé et actif) — piloté depuis Administration → Analytics,
 * aucun réglage local au module.
 *
 *   analytics_head()      → balise <script> du tracker (vide si module absent/désactivé)
 *   analytics_enabled()   → le suivi d'audience est-il actif ?
 */

if (!function_exists('analytics_service')) {
    /** Instance partagée d'AnalyticsService, ou null si le module n'est pas disponible. */
    function analytics_service(): ?object
    {
        static $svc = false; // false = pas encore résolu
        if ($svc !== false) { return $svc; }

        $svc = null;
        // class_exists() réussit dès que le fichier est autoloadable, QUE le module
        // Analytics soit actif ou non (l'autoload ne connaît pas l'état "activé" en
        // base). Sans la vérification module_active(), désactiver le module laisse
        // les autres modules continuer à injecter <script src="/analytics/a.js">
        // (capacité "analytics" déclarée ailleurs) vers une route qui n'existe plus
        // (routes.php d'Analytics non enregistré) → 404 loggé en CRITICAL à chaque page.
        if (!class_exists('Analytics\\Services\\AnalyticsService')) { return $svc; }
        if (!function_exists('module_active') || !module_active('Analytics')) { return $svc; }
        try {
            $db = $GLOBALS['db'] ?? null;
            if ($db instanceof \Framework\Services\Database) {
                $svc = new \Analytics\Services\AnalyticsService($db);
            }
        } catch (\Throwable $e) {
            $svc = null;
        }
        return $svc;
    }
}

if (!function_exists('analytics_head')) {
    /**
     * Balise <script> du tracker d'audience, à placer dans le <head> des pages
     * publiques. Renvoie une chaîne vide si le module Analytics est absent ou désactivé.
     */
    function analytics_head(): string
    {
        $svc = analytics_service();
        if ($svc === null) { return ''; }
        try {
            return $svc->trackerTag();
        } catch (\Throwable $e) {
            return '';
        }
    }
}

if (!function_exists('analytics_enabled')) {
    /** Le suivi d'audience est-il actif (module présent + activé en config) ? */
    function analytics_enabled(): bool
    {
        $svc = analytics_service();
        if ($svc === null) { return false; }
        try {
            return (bool) $svc->isEnabled();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
