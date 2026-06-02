<?php
declare(strict_types=1);

namespace Configuration\Services;

use Framework\Services\Database;

/**
 * SitemapService — génération automatique de sitemap.xml et robots.txt.
 *
 * Service dédié (convention : 1 service par fonctionnalité). Écrit les deux
 * fichiers à la racine du site. Collecte les URLs publiques connues : accueil,
 * pages du forum, catégories et sujets visibles (si le module Forum est actif).
 */
class SitemapService
{
    private Database $db;
    private SettingsService $settings;

    private const SITEMAP_FILE = 'sitemap.xml';
    private const ROBOTS_FILE  = 'robots.txt';

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->settings = new SettingsService($db);
    }

    private function sitemapPath(): string { return ROOT_PATH . '/' . self::SITEMAP_FILE; }
    private function robotsPath(): string  { return ROOT_PATH . '/' . self::ROBOTS_FILE; }

    /** Statut courant des fichiers (pour l'affichage admin). */
    public function status(): array
    {
        $sm = $this->sitemapPath();
        $rb = $this->robotsPath();
        return [
            'sitemap_exists'    => is_file($sm),
            'sitemap_date'      => is_file($sm) ? date('d/m/Y H:i', (int) filemtime($sm)) : null,
            'sitemap_urls'      => is_file($sm) ? substr_count((string) @file_get_contents($sm), '<url>') : 0,
            'robots_exists'     => is_file($rb),
            'robots_date'       => is_file($rb) ? date('d/m/Y H:i', (int) filemtime($rb)) : null,
            'writable'          => is_writable(ROOT_PATH),
        ];
    }

    /**
     * Génère sitemap.xml + robots.txt.
     *
     * @param string $baseUrl Base absolue (ex. https://site.tld + sous-dossier), sans / final.
     * @return array{success:bool, message:string, urls:int}
     */
    public function generate(string $baseUrl): array
    {
        $baseUrl = rtrim($baseUrl, '/');

        if (!is_writable(ROOT_PATH)) {
            return ['success' => false, 'message' => "La racine du site n'est pas accessible en écriture.", 'urls' => 0];
        }

        $urls = $this->collectUrls($baseUrl);

        // --- sitemap.xml ---
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
            if (!empty($u['lastmod'])) {
                $xml .= '    <lastmod>' . htmlspecialchars($u['lastmod'], ENT_XML1) . "</lastmod>\n";
            }
            $xml .= '    <changefreq>' . ($u['changefreq'] ?? 'weekly') . "</changefreq>\n";
            $xml .= '    <priority>' . ($u['priority'] ?? '0.5') . "</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>' . "\n";

        if (@file_put_contents($this->sitemapPath(), $xml) === false) {
            return ['success' => false, 'message' => "Impossible d'écrire sitemap.xml.", 'urls' => 0];
        }

        // --- robots.txt ---
        $robots = $this->buildRobots($baseUrl);
        @file_put_contents($this->robotsPath(), $robots);

        return ['success' => true, 'message' => count($urls) . ' URL(s) écrites dans sitemap.xml + robots.txt généré.', 'urls' => count($urls)];
    }

    /** @return array<int,array{loc:string,lastmod?:string,changefreq?:string,priority?:string}> */
    private function collectUrls(string $baseUrl): array
    {
        $today = date('Y-m-d');
        $urls = [[
            'loc' => $baseUrl . '/', 'lastmod' => $today, 'changefreq' => 'daily', 'priority' => '1.0',
        ]];

        // Forum (si tables présentes)
        if ($this->tableExists('forum_categories')) {
            $urls[] = ['loc' => $baseUrl . '/forum', 'lastmod' => $today, 'changefreq' => 'daily', 'priority' => '0.9'];

            try {
                $cats = $this->db->query(
                    "SELECT slug FROM forum_categories WHERE is_visible = 1 AND view_permission = 'guest' AND slug IS NOT NULL AND slug != ''"
                );
                foreach ($cats as $c) {
                    $urls[] = ['loc' => $baseUrl . '/forum/c/' . $c['slug'], 'changefreq' => 'daily', 'priority' => '0.7'];
                }
            } catch (\Throwable $e) { /* ignore */ }

            try {
                $topics = $this->db->query(
                    "SELECT t.slug, t.updated_at
                     FROM forum_topics t
                     JOIN forum_categories c ON c.id = t.category_id
                     WHERE t.status != 'deleted' AND c.is_visible = 1 AND c.view_permission = 'guest'
                       AND t.slug IS NOT NULL AND t.slug != ''
                     ORDER BY t.last_post_at DESC
                     LIMIT 5000"
                );
                foreach ($topics as $t) {
                    $urls[] = [
                        'loc' => $baseUrl . '/forum/t/' . $t['slug'],
                        'lastmod' => !empty($t['updated_at']) ? date('Y-m-d', strtotime((string)$t['updated_at'])) : null,
                        'changefreq' => 'weekly', 'priority' => '0.6',
                    ];
                }
            } catch (\Throwable $e) { /* ignore */ }
        }

        // Pages statiques du forum
        if ($this->tableExists('forum_pages')) {
            try {
                $pages = $this->db->query("SELECT slug FROM forum_pages WHERE is_published = 1 AND slug IS NOT NULL AND slug != ''");
                foreach ($pages as $p) {
                    $urls[] = ['loc' => $baseUrl . '/forum/page/' . $p['slug'], 'changefreq' => 'monthly', 'priority' => '0.4'];
                }
            } catch (\Throwable $e) { /* ignore */ }
        }

        return $urls;
    }

    private function buildRobots(string $baseUrl): string
    {
        $robots  = "User-agent: *\n";

        // Si le site est en noindex global, on interdit tout.
        $seoRobots = (string) $this->settings->get('seo_robots', 'index,follow');
        if (str_starts_with($seoRobots, 'noindex')) {
            $robots .= "Disallow: /\n";
        } else {
            $robots .= "Disallow: /admin\n";
            $robots .= "Disallow: /install\n";
            $robots .= "Disallow: /framework\n";
            $robots .= "Disallow: /auth\n";
            $robots .= "Allow: /\n";
        }
        $robots .= "\nSitemap: " . $baseUrl . '/' . self::SITEMAP_FILE . "\n";

        return $robots;
    }

    private function tableExists(string $table): bool
    {
        try {
            $row = $this->db->queryOne(
                "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?",
                [$table]
            );
            return (bool) $row;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
