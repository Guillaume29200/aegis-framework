<?php
/**
 * Helpers globaux de la capacité « seo ».
 *
 * Chargés par CapabilityManager::boot() si un module actif déclare
 * `"capabilities": ["seo"]`. Fournit un rendu de balises méta PAR PAGE
 * (title, description, canonical, Open Graph, Twitter Card) réutilisant les
 * défauts site (nom du site, image OG) gérés dans Configuration → SEO & médias.
 *
 * Un module appelle simplement, dans le <head> de ses pages publiques :
 *   <?= seo_head(['title' => $titre, 'description' => $desc, 'image' => $img]) ?>
 *
 *   seo_head($ctx)   → chaîne de balises <meta>/<title>/<link>
 *   seo_meta($ctx)   → echo seo_head($ctx)
 *   seo_defaults()   → défauts site (site_name, og_image)
 */

if (!function_exists('seo_defaults')) {
    /** Défauts site-wide (nom du site, image OG par défaut), lus une fois. */
    function seo_defaults(): array
    {
        static $d = null;
        if ($d !== null) { return $d; }

        $d = ['site_name' => '', 'og_image' => ''];
        try {
            $db = $GLOBALS['db'] ?? null;
            if ($db instanceof \Framework\Services\Database) {
                if (class_exists('Configuration\\Services\\SettingsService')) {
                    $s = new \Configuration\Services\SettingsService($db);
                    $d['site_name'] = (string) ($s->get('site_name', '') ?: $s->get('site_title', ''));
                }
                if (class_exists('Configuration\\Services\\SeoService')) {
                    $cfg = (new \Configuration\Services\SeoService($db))->getConfig();
                    $d['og_image'] = (string) ($cfg['og_image'] ?? '');
                }
            }
        } catch (\Throwable $e) {
            // config indisponible : défauts vides
        }
        return $d;
    }
}

if (!function_exists('seo_abs_url')) {
    /** Rend une URL absolue (préfixe schéma+hôte si elle commence par « / »). */
    function seo_abs_url(string $url): string
    {
        $url = trim($url);
        if ($url === '' || preg_match('#^https?://#i', $url)) { return $url; }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = (string) ($_SERVER['HTTP_HOST'] ?? '');
        if ($host === '') { return $url; }
        return $scheme . '://' . $host . '/' . ltrim($url, '/');
    }
}

if (!function_exists('seo_current_url')) {
    /** URL courante absolue (sans query d'analytics superflue). */
    function seo_current_url(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = (string) ($_SERVER['HTTP_HOST'] ?? '');
        $uri    = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        if ($host === '') { return ''; }
        return $scheme . '://' . $host . $uri;
    }
}

if (!function_exists('seo_head')) {
    /**
     * Construit le bloc de balises méta d'une page.
     *
     * Clés de $ctx (toutes optionnelles) :
     *   title, description, keywords, image, url, canonical, type, robots, site_name
     */
    function seo_head(array $ctx = []): string
    {
        $def  = seo_defaults();
        $e    = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

        $siteName = (string) ($ctx['site_name'] ?? $def['site_name']);
        $title    = trim((string) ($ctx['title'] ?? ''));
        $desc     = trim((string) ($ctx['description'] ?? ''));
        $keywords = trim((string) ($ctx['keywords'] ?? ''));
        $type     = (string) ($ctx['type'] ?? 'website');
        $robots   = (string) ($ctx['robots'] ?? 'index,follow');
        $url      = (string) ($ctx['url'] ?? ($ctx['canonical'] ?? seo_current_url()));
        $url      = seo_abs_url($url);
        $image    = seo_abs_url((string) ($ctx['image'] ?? $def['og_image']));

        // Titre : « Page — Site » (sans doublon si identiques ou site vide).
        $fullTitle = $title;
        if ($siteName !== '' && $title !== '' && strcasecmp($title, $siteName) !== 0) {
            $fullTitle = $title . ' — ' . $siteName;
        } elseif ($title === '') {
            $fullTitle = $siteName;
        }

        $out = [];
        if ($fullTitle !== '') { $out[] = '<title>' . $e($fullTitle) . '</title>'; }
        if ($desc !== '')      { $out[] = '<meta name="description" content="' . $e($desc) . '">'; }
        if ($keywords !== '')  { $out[] = '<meta name="keywords" content="' . $e($keywords) . '">'; }
        if ($robots !== '')    { $out[] = '<meta name="robots" content="' . $e($robots) . '">'; }
        if ($url !== '')       { $out[] = '<link rel="canonical" href="' . $e($url) . '">'; }

        // Open Graph
        $out[] = '<meta property="og:type" content="' . $e($type) . '">';
        if ($fullTitle !== '') { $out[] = '<meta property="og:title" content="' . $e($fullTitle) . '">'; }
        if ($desc !== '')      { $out[] = '<meta property="og:description" content="' . $e($desc) . '">'; }
        if ($url !== '')       { $out[] = '<meta property="og:url" content="' . $e($url) . '">'; }
        if ($siteName !== '')  { $out[] = '<meta property="og:site_name" content="' . $e($siteName) . '">'; }
        if ($image !== '')     { $out[] = '<meta property="og:image" content="' . $e($image) . '">'; }

        // Twitter Card
        $out[] = '<meta name="twitter:card" content="' . ($image !== '' ? 'summary_large_image' : 'summary') . '">';
        if ($fullTitle !== '') { $out[] = '<meta name="twitter:title" content="' . $e($fullTitle) . '">'; }
        if ($desc !== '')      { $out[] = '<meta name="twitter:description" content="' . $e($desc) . '">'; }
        if ($image !== '')     { $out[] = '<meta name="twitter:image" content="' . $e($image) . '">'; }

        return implode("\n", $out) . "\n";
    }
}

if (!function_exists('seo_meta')) {
    /** Affiche directement le bloc SEO (raccourci de echo seo_head()). */
    function seo_meta(array $ctx = []): void
    {
        echo seo_head($ctx);
    }
}
