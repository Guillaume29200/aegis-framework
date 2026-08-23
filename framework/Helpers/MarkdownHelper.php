<?php
/**
 * Helpers globaux de la capacité « markdown ».
 *
 * Chargés par CapabilityManager::boot() UNIQUEMENT si au moins un module actif
 * déclare `"capabilities": ["markdown"]`. Fonctions guardées par function_exists
 * pour rester idempotentes.
 *
 *   md_render($texte)        → HTML sécurisé (à afficher côté public)
 *   md_editor_assets()       → <link> CSS + <script> éditeur (dans une vue admin/form)
 *   md_css()                 → <link> CSS seul (pages de rendu sans éditeur)
 */

use Framework\Capabilities\Markdown\MarkdownService;

if (!function_exists('md_render')) {
    /** Convertit du Markdown utilisateur en HTML sécurisé (échappé + URLs filtrées). */
    function md_render(?string $text, bool $allowMedia = true): string
    {
        if ($text === null || $text === '') { return ''; }
        static $svc = null;
        if ($svc === null) { $svc = new MarkdownService(); }
        return $svc->render($text, $allowMedia);
    }
}

if (!function_exists('md_asset_url')) {
    /** URL d'un asset de la capacité markdown. */
    function md_asset_url(string $file): string
    {
        $base = function_exists('u') ? u('/framework/Capabilities/Markdown/assets/') : '/framework/Capabilities/Markdown/assets/';
        return rtrim($base, '/') . '/' . ltrim($file, '/');
    }
}

if (!function_exists('md_css')) {
    /** Balise <link> vers la feuille de style de rendu markdown. */
    function md_css(): string
    {
        return '<link rel="stylesheet" href="' . htmlspecialchars(md_asset_url('md.css'), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('md_editor_assets')) {
    /**
     * CSS + JS de l'éditeur markdown automatique.
     * À inclure une fois dans une vue contenant des <textarea data-md>.
     * Idempotent côté HTML via un marqueur data-md-assets.
     */
    function md_editor_assets(): string
    {
        return md_css()
            . "\n" . '<script defer src="' . htmlspecialchars(md_asset_url('md-editor.js'), ENT_QUOTES, 'UTF-8') . '" data-md-assets></script>';
    }
}
