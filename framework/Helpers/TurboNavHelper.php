<?php
/**
 * Helper de la capacité « turbonav » — navigation AJAX sur les pages publiques.
 *
 * Chargé par CapabilityManager::boot() si un module actif déclare
 * `"capabilities": ["turbonav"]`.
 *
 * TurboNav intercepte les clics, précharge au survol et remplace le corps de
 * la page sans rechargement complet. L'administration s'en sert déjà ; ce
 * helper permet à un module de l'offrir aussi à ses visiteurs, sans recopier
 * la balise script.
 *
 * L'interrupteur reste unique et central : Administration → Configuration.
 * Si TurboNav y est éteint, le script est bien servi mais se met en veille de
 * lui-même — on ne décide pas ici à la place de l'administrateur.
 *
 *   turbonav_script()  → les balises à poser en fin de page
 *   turbonav_enabled() → l'interrupteur central est-il allumé ?
 */

if (!function_exists('turbonav_enabled')) {
    /** TurboNav est-il activé dans la configuration du site ? */
    function turbonav_enabled(): bool
    {
        return defined('TURBONAV_ENABLED') && TURBONAV_ENABLED;
    }
}

if (!function_exists('turbonav_script')) {
    /**
     * Balises TurboNav, prêtes à poser avant la fermeture du corps.
     *
     * Idempotent : rendu une seule fois par requête, même appelé plusieurs fois.
     */
    function turbonav_script(): string
    {
        static $rendu = false;
        if ($rendu) { return ''; }
        $rendu = true;

        if (!defined('ROOT_PATH') || !function_exists('u')) { return ''; }

        $fichier = ROOT_PATH . '/framework/assets/js/turbo-nav.js';
        if (!is_file($fichier)) { return ''; }

        // La version suit la date du fichier : un cache de navigateur ne sert
        // pas une version périmée après une mise à jour du framework.
        $version = @filemtime($fichier) ?: '1';
        $actif   = turbonav_enabled() ? 'true' : 'false';

        return "\n<script>window.TURBONAV = { enabled: {$actif} };</script>\n"
             . '<script src="' . htmlspecialchars(u('/framework/assets/js/turbo-nav.js'), ENT_QUOTES, 'UTF-8')
             . '?v=' . $version . '" defer></script>' . "\n";
    }
}
