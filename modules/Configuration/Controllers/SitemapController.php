<?php
declare(strict_types=1);

namespace Configuration\Controllers;

use Framework\Security\CSRFProtection;
use Framework\Services\Database;
use Configuration\Services\SitemapService;

/**
 * SitemapController — génération du sitemap.xml et du robots.txt.
 * Contrôleur dédié (1 contrôleur / fonctionnalité). L'UI vit sur la page SEO.
 */
class SitemapController
{
    private SitemapService $sitemap;
    private CSRFProtection $csrf;

    public function __construct(Database $db, CSRFProtection $csrf)
    {
        $this->sitemap = new SitemapService($db);
        $this->csrf = $csrf;
    }

    public function generate(): void
    {
        $this->requireAdmin();

        try {
            $this->csrf->validateToken($_POST['csrf_token'] ?? '');
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Token CSRF invalide.';
            redirect('/admin/configuration/seo');
        }

        $result = $this->sitemap->generate($this->baseUrl());

        if ($result['success']) {
            $_SESSION['success'] = '✅ Sitemap généré : ' . $result['message'];
        } else {
            $_SESSION['error'] = '❌ ' . $result['message'];
        }
        redirect('/admin/configuration/seo');
    }

    /** Construit l'URL absolue de base (schéma + hôte + sous-dossier BASE_URL). */
    private function baseUrl(): string
    {
        $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443);
        $scheme = $https ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base   = defined('BASE_URL') ? BASE_URL : '';
        return $scheme . '://' . $host . $base;
    }

    private function requireAdmin(): void
    {
        if (empty($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'], true)) {
            redirect('/auth/login');
        }
    }
}
