<?php
declare(strict_types=1);

namespace Framework\Templating;

use Framework\Storage\ImageUploader;

/**
 * Gestionnaire de thèmes partagé par tous les modules à partie publique.
 *
 * Un thème est UN dossier, et tout ce qui le compose y vit :
 *
 *   modules/<Module>/themes/<clé>/
 *       meta.json          le manifeste : nom, auteur, options déclarées
 *       header.html        les gabarits, à la racine du thème
 *       footer.html
 *       home.html
 *       preview.png        l'aperçu montré dans l'administration
 *       assets/css/        les feuilles de style
 *       assets/js/         les scripts éventuels
 *       assets/images/     les images livrées avec le thème
 *       assets/uploads/    celles que l'administrateur téléverse
 *
 * Un dossier, une clé, rien qui traîne ailleurs : le thème se zippe, se
 * partage et s'installe tel quel.
 *
 * Les valeurs des options sont rangées dans la table de réglages du module,
 * sous des clés `theme_option.<clé>.<option>` — jamais dans le dossier du
 * thème. On peut donc remplacer un thème par une version plus récente sans
 * perdre ce qui a été réglé.
 *
 * Deux garanties qui font tout l'intérêt de la mécanique :
 *
 *   — un thème ne contient aucun PHP, donc en téléverser un ne peut pas
 *     exécuter de code ;
 *   — un gabarit absent du thème actif est repris du thème de repli, si bien
 *     qu'un thème incomplet donne une page complète plutôt qu'une page blanche.
 */
final class ThemeManager
{
    /** Sous-dossiers que tout thème possède. */
    public const ASSET_DIRS = ['css', 'js', 'images', 'uploads'];

    /** Types d'options qu'un meta.json peut déclarer. */
    private const OPTION_TYPES = ['toggle', 'select', 'color', 'text', 'number', 'textarea', 'links', 'image'];

    /** Extensions acceptées dans une archive de thème. Aucune n'est exécutable. */
    private const ALLOWED_IN_ZIP = ['html', 'css', 'js', 'json', 'png', 'jpg', 'jpeg', 'webp', 'svg', 'gif', 'woff', 'woff2', 'ttf', 'eot', 'txt', 'md'];

    private string $themesDir;
    private string $themesUrl;

    public function __construct(
        private string $module,
        private SettingsStore $settings,
        private string $fallback = 'default'
    ) {
        $racine = defined('ROOT_PATH') ? rtrim(ROOT_PATH, "/\\") : dirname(__DIR__, 2);
        $this->themesDir = $racine . '/modules/' . $module . '/themes/';
        $this->themesUrl = (defined('BASE_URL') ? BASE_URL : '') . '/modules/' . $module . '/themes/';
    }

    // ── Emplacements ──────────────────────────────────────────────────────

    /** Dossier d'un thème sur le disque. */
    public function dir(string $key): string
    {
        return $this->themesDir . $this->clean($key) . '/';
    }

    /** URL publique du dossier assets d'un thème. */
    public function assetsUrl(?string $key = null): string
    {
        return $this->themesUrl . $this->clean($key ?? $this->activeKey()) . '/assets';
    }

    /** Racine des thèmes, utile aux écrans d'administration. */
    public function themesDir(): string
    {
        return $this->themesDir;
    }

    // ── Thème actif ───────────────────────────────────────────────────────

    public function activeKey(): string
    {
        $key = $this->clean($this->settings->get('active_theme', ''));
        if ($key !== '' && is_dir($this->dir($key))) {
            return $key;
        }

        // Réglage absent, ou thème effacé à la main : on retombe sur le thème
        // livré, sinon sur le premier trouvé — mieux vaut une page que rien.
        if (is_dir($this->dir($this->fallback))) {
            return $this->fallback;
        }

        $trouves = $this->themeKeys();
        return $trouves[0] ?? $this->fallback;
    }

    public function setActive(string $key): void
    {
        $key = $this->clean($key);
        if ($key === '' || !is_dir($this->dir($key))) {
            throw new \RuntimeException("Thème « {$key} » introuvable.");
        }
        $this->settings->set('active_theme', $key);
    }

    /** @return string[] */
    public function themeKeys(): array
    {
        $keys = [];
        foreach (glob($this->themesDir . '*', GLOB_ONLYDIR) ?: [] as $dossier) {
            $key = basename($dossier);
            if ($this->clean($key) === $key) {
                $keys[] = $key;
            }
        }
        sort($keys);
        return $keys;
    }

    /** Thèmes installés, décrits et prêts à afficher dans l'administration. */
    public function availableThemes(): array
    {
        $actif = $this->activeKey();
        $out   = [];

        foreach ($this->themeKeys() as $key) {
            $meta = $this->meta($key);
            $out[] = [
                'key'        => $key,
                'name'       => $meta['name'],
                'icon'       => $meta['icon'],
                'desc'       => $meta['desc'],
                'author'     => $meta['author'],
                'version'    => $meta['version'],
                'is_active'  => $key === $actif,
                'is_default' => $key === $this->fallback,
                'preview'    => $this->previewUrl($key),
                'options'    => count($this->declaredOptions($key)),
                'templates'  => count(glob($this->dir($key) . '*.html') ?: []),
            ];
        }
        return $out;
    }

    // ── Manifeste et options ──────────────────────────────────────────────

    public function meta(string $key): array
    {
        $fichier = $this->dir($key) . 'meta.json';
        $brut    = is_file($fichier) ? json_decode((string) file_get_contents($fichier), true) : null;
        if (!is_array($brut)) { $brut = []; }

        return [
            'name'    => trim((string) ($brut['name'] ?? '')) ?: ucfirst($this->clean($key)),
            'icon'    => trim((string) ($brut['icon'] ?? '')) ?: '🎨',
            'desc'    => trim((string) ($brut['desc'] ?? '')),
            'author'  => trim((string) ($brut['author'] ?? '')),
            'version' => trim((string) ($brut['version'] ?? '')) ?: '1.0.0',
            'options' => is_array($brut['options'] ?? null) ? $brut['options'] : [],
        ];
    }

    /** Options déclarées par un thème, nettoyées. */
    public function declaredOptions(string $key): array
    {
        $out = [];
        foreach ($this->meta($key)['options'] as $brut) {
            if (!is_array($brut)) { continue; }

            $cle  = preg_replace('/[^a-z0-9_]/', '', strtolower((string) ($brut['key'] ?? ''))) ?? '';
            $type = (string) ($brut['type'] ?? 'text');
            if ($cle === '' || !in_array($type, self::OPTION_TYPES, true)) { continue; }

            $choix = [];
            if ($type === 'select' && is_array($brut['choices'] ?? null)) {
                foreach ($brut['choices'] as $v => $libelle) {
                    $choix[(string) $v] = (string) $libelle;
                }
            }

            $out[$cle] = [
                'key'     => $cle,
                'type'    => $type,
                'label'   => trim((string) ($brut['label'] ?? $cle)),
                'help'    => trim((string) ($brut['help'] ?? '')),
                'group'   => trim((string) ($brut['group'] ?? '')) ?: 'Général',
                'choices' => $choix,
                'default' => $brut['default'] ?? ($type === 'toggle' ? false : ''),
            ];
        }
        return $out;
    }

    /** Valeurs effectives : le réglage enregistré, ou le défaut du thème. */
    public function optionValues(string $key): array
    {
        $key      = $this->clean($key);
        $enregistre = $this->settings->all();
        $valeurs  = [];

        foreach ($this->declaredOptions($key) as $cle => $opt) {
            $pleine = 'theme_option.' . $key . '.' . $cle;

            // Rien d'enregistré : on prend le défaut du thème. Il passe par le
            // même traitement que la suite — un thème livré avec des liens par
            // défaut doit les afficher, pas attendre qu'on les ressaisisse.
            $existe = array_key_exists($pleine, $enregistre);
            $brut   = $existe ? $enregistre[$pleine] : (string) $opt['default'];

            if (!$existe && $opt['type'] !== 'links') {
                $valeurs[$cle] = $opt['default'];
                continue;
            }

            $valeurs[$cle] = match ($opt['type']) {
                'toggle' => $brut === '1',
                'number' => is_numeric($brut) ? $brut + 0 : $opt['default'],
                'links'  => $this->parseLinks($brut),
                default  => $brut,
            };

            // Une liste de liens garde aussi sa forme brute, pour le champ de
            // saisie qui la réaffiche telle qu'elle a été écrite.
            //
            // `_items` est l'orthographe historique de la liste découpée. Les
            // thèmes se distribuent en ZIP : on ne peut pas les réécrire après
            // coup, donc les deux écritures restent valides.
            if ($opt['type'] === 'links') {
                $valeurs[$cle . '_raw']   = $brut;
                $valeurs[$cle . '_items'] = $valeurs[$cle];
            }
        }
        return $valeurs;
    }

    /**
     * Enregistre les options d'un thème.
     *
     * @param array $post  Le formulaire d'administration.
     * @param array $files Les fichiers joints, pour les options de type image.
     */
    public function saveOptions(string $key, array $post, array $files = []): void
    {
        $key = $this->clean($key);

        foreach ($this->declaredOptions($key) as $cle => $opt) {
            $pleine = 'theme_option.' . $key . '.' . $cle;

            if ($opt['type'] === 'image') {
                $this->settings->set($pleine, $this->storeImage($key, $cle, $post, $files, $this->settings->get($pleine, '')));
                continue;
            }

            if ($opt['type'] === 'toggle') {
                // Une case décochée n'est pas envoyée : son absence vaut « non ».
                $this->settings->set($pleine, isset($post[$cle]) ? '1' : '0');
                continue;
            }

            if (!array_key_exists($cle, $post)) { continue; }
            $this->settings->set($pleine, $this->castOption($opt, (string) $post[$cle]));
        }
    }

    private function castOption(array $opt, string $brut): string
    {
        return match ($opt['type']) {
            'color'    => preg_match('/^#[0-9a-fA-F]{3,8}$/', trim($brut)) ? strtolower(trim($brut)) : (string) $opt['default'],
            'number'   => (string) (is_numeric($brut) ? $brut + 0 : $opt['default']),
            'select'   => array_key_exists($brut, $opt['choices']) ? $brut : (string) $opt['default'],
            'links'    => mb_substr(trim($brut), 0, 4000),
            'textarea' => mb_substr(strip_tags($brut), 0, 4000),
            default    => mb_substr(strip_tags($brut), 0, 500),
        };
    }

    /** « Libellé | /chemin » par ligne ; une ligne incomplète est ignorée. */
    /**
     * Découpe une liste « Libellé | url », une par ligne.
     *
     * Chaque entrée porte `is_external`, pour que le gabarit puisse décider
     * d'ouvrir dans un nouvel onglet sans avoir à comparer quoi que ce soit —
     * il n'en a pas les moyens. Et un chemin interne repasse par u() : il suit
     * ainsi un renommage du préfixe public du module.
     */
    private function parseLinks(string $brut): array
    {
        $out = [];
        foreach (preg_split('/\R/', $brut) ?: [] as $ligne) {
            $parts = array_map('trim', explode('|', $ligne, 2));
            if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') { continue; }

            $url      = $parts[1];
            $externe  = (bool) preg_match('#^(?:[a-z][a-z0-9+.-]*:|//)#i', $url);

            $out[] = [
                'label'       => $parts[0],
                'url'         => $externe ? $url : self::internalUrl($url),
                'is_external' => $externe,
            ];
        }
        return $out;
    }

    /** Un chemin interne, préfixé par la base du site quand le helper existe. */
    private static function internalUrl(string $chemin): string
    {
        if ($chemin === '' || $chemin[0] === '#') { return $chemin; }
        if ($chemin[0] !== '/') { $chemin = '/' . $chemin; }

        return function_exists('u') ? u($chemin) : $chemin;
    }

    // ── Images d'option ───────────────────────────────────────────────────

    /**
     * Range l'image d'une option dans assets/uploads du thème et renvoie
     * l'URL à retenir.
     *
     * Trois cas, dans cet ordre : on demande le retrait, un fichier est joint,
     * ou rien n'est joint. Le dernier conserve la valeur en place — sans quoi
     * réenregistrer le formulaire effacerait l'image à chaque fois.
     */
    private function storeImage(string $themeKey, string $optKey, array $post, array $files, string $actuel): string
    {
        $dossier = $this->dir($themeKey) . 'assets/uploads/';
        $url     = $this->assetsUrl($themeKey) . '/uploads/';
        // Chaque option a son sous-dossier : deux bannières ne se marchent pas
        // dessus, et l'on retrouve d'un coup d'œil à quoi sert un fichier.

        if (!empty($post[$optKey . '_remove'])) {
            $this->deleteUpload($dossier, $url, $actuel);
            return '';
        }

        $fichier = $files[$optKey] ?? null;
        if (!is_array($fichier) || ($fichier['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $actuel;
        }

        // Le contrôle du type, le ré-encodage et le durcissement du dossier sont
        // faits par l'uploader du framework — le même pour tous les modules.
        // On le fait écrire directement chez le thème : un thème doit rester un
        // dossier autonome, images comprises.
        $depot = ImageUploader::into($this->module, rtrim($dossier, '/'), rtrim($url, '/'), 6 * 1024 * 1024);
        $range = $depot->store($fichier, $optKey);
        if ($range === null) { return $actuel; }

        $this->deleteUpload($dossier, $url, $actuel);
        return $range;
    }

    /** Efface un fichier téléversé, à condition qu'il soit bien dans le thème. */
    private function deleteUpload(string $dossier, string $url, string $valeur): void
    {
        if ($valeur === '' || !str_starts_with($valeur, $url)) { return; }

        $chemin = $dossier . ltrim(substr($valeur, strlen($url)), '/');
        $reel   = realpath($chemin);
        $base   = realpath($dossier);
        if ($reel !== false && $base !== false && str_starts_with($reel, $base) && is_file($reel)) {
            @unlink($reel);
        }
    }

    // ── Rendu ─────────────────────────────────────────────────────────────

    public function createEngine(): TemplateEngine
    {
        return new TemplateEngine(fn(string $nom): ?string => $this->loadTemplate($nom));
    }

    public function render(string $template, array $data = []): string
    {
        return $this->createEngine()->render($template, $data);
    }

    /**
     * Charge un gabarit dans le thème actif, à défaut dans le thème de repli.
     *
     * C'est ce repli qui permet à un thème de ne refaire que les pages qu'il
     * veut : les autres arrivent du thème livré avec le module.
     */
    public function loadTemplate(string $nom): ?string
    {
        $nom = preg_replace('/[^a-z0-9_-]/i', '', $nom) ?? '';
        if ($nom === '') { return null; }

        foreach (array_unique([$this->activeKey(), $this->fallback]) as $key) {
            $fichier = $this->dir($key) . $nom . '.html';
            if (is_file($fichier)) {
                return (string) file_get_contents($fichier);
            }
        }
        return null;
    }

    public function previewUrl(string $key): ?string
    {
        foreach (['preview.png', 'preview.jpg', 'preview.webp'] as $nom) {
            if (is_file($this->dir($key) . $nom)) {
                return $this->themesUrl . $this->clean($key) . '/' . $nom;
            }
        }
        return null;
    }

    // ── Installation d'un thème ───────────────────────────────────────────

    /**
     * Installe un thème depuis une archive ZIP.
     *
     * L'archive est inspectée avant qu'un seul octet ne soit écrit : chemins
     * remontants refusés, extensions limitées à ce qui n'est pas exécutable,
     * meta.json obligatoire, un unique dossier racine.
     *
     * @return string La clé du thème installé.
     */
    public function installZip(array $fichier): string
    {
        if (!class_exists('ZipArchive')) {
            throw new \RuntimeException("L'extension ZIP de PHP n'est pas disponible sur ce serveur.");
        }
        if (($fichier['error'] ?? 1) !== UPLOAD_ERR_OK || empty($fichier['tmp_name'])) {
            throw new \RuntimeException("Aucune archive reçue.");
        }
        if (PHP_SAPI !== 'cli' && !is_uploaded_file($fichier['tmp_name'])) {
            throw new \RuntimeException("Archive invalide.");
        }
        if (($fichier['size'] ?? 0) > 20 * 1024 * 1024) {
            throw new \RuntimeException("Archive trop lourde (20 Mo au maximum).");
        }

        $zip = new \ZipArchive();
        if ($zip->open($fichier['tmp_name']) !== true) {
            throw new \RuntimeException("Archive illisible.");
        }

        try {
            [$racine, $entrees] = $this->inspectZip($zip);

            $key = $this->clean($racine);
            if ($key === '') {
                throw new \RuntimeException("Nom de thème invalide dans l'archive.");
            }
            if (is_dir($this->dir($key))) {
                throw new \RuntimeException("Un thème « {$key} » est déjà installé. Supprimez-le d'abord.");
            }

            foreach ($entrees as $relatif => $index) {
                $cible   = $this->dir($key) . $relatif;
                $dossier = dirname($cible);
                if (!is_dir($dossier) && !@mkdir($dossier, 0755, true)) {
                    throw new \RuntimeException("Impossible de créer {$dossier}.");
                }

                $flux = $zip->getStream((string) $zip->getNameIndex($index));
                if ($flux === false) { continue; }
                file_put_contents($cible, stream_get_contents($flux));
                fclose($flux);
            }

            // Un thème installé doit avoir la même ossature qu'un thème généré.
            $this->ensureAssetDirs($key);

            return $key;
        } finally {
            $zip->close();
        }
    }

    /**
     * Vérifie l'archive et renvoie [clé de thème, entrées retenues].
     *
     * @return array{0:string, 1:array<string,int>}
     */
    private function inspectZip(\ZipArchive $zip): array
    {
        $racine  = null;
        $entrees = [];
        $vuMeta  = false;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $nom = (string) $zip->getNameIndex($i);
            if ($nom === '' || str_ends_with($nom, '/')) { continue; }

            $nom = str_replace("\\", '/', $nom);
            if (str_contains($nom, '../') || str_starts_with($nom, '/')) {
                throw new \RuntimeException("Archive refusée : elle contient un chemin remontant.");
            }
            // Métadonnées d'archiveurs : sans intérêt, et parfois piégeuses.
            if (str_starts_with($nom, '__MACOSX/') || basename($nom) === '.DS_Store') { continue; }

            $ext = strtolower(pathinfo($nom, PATHINFO_EXTENSION));
            if (!in_array($ext, self::ALLOWED_IN_ZIP, true)) {
                throw new \RuntimeException("Archive refusée : le fichier « {$nom} » n'est pas d'un type autorisé dans un thème.");
            }

            $parts = explode('/', $nom);
            if (count($parts) < 2) {
                throw new \RuntimeException("Archive refusée : elle doit contenir un dossier racine portant le nom du thème.");
            }

            $racine ??= $parts[0];
            if ($parts[0] !== $racine) {
                throw new \RuntimeException("Archive refusée : elle doit contenir un seul dossier racine.");
            }

            $relatif = implode('/', array_slice($parts, 1));
            if ($relatif === 'meta.json') { $vuMeta = true; }

            $entrees[$relatif] = $i;
        }

        if ($racine === null || $entrees === []) {
            throw new \RuntimeException("Archive vide.");
        }
        if (!$vuMeta) {
            throw new \RuntimeException("Archive refusée : il manque le fichier meta.json à la racine du thème.");
        }

        return [$racine, $entrees];
    }

    /** Crée l'ossature assets/ d'un thème : css, js, images, uploads. */
    public function ensureAssetDirs(string $key): void
    {
        foreach (self::ASSET_DIRS as $sous) {
            $chemin = $this->dir($key) . 'assets/' . $sous;
            if (!is_dir($chemin)) { @mkdir($chemin, 0755, true); }
        }
    }

    /** Désinstalle un thème : ses fichiers et ses réglages. */
    public function delete(string $key): void
    {
        $key = $this->clean($key);
        if ($key === '' || $key === $this->fallback) {
            throw new \RuntimeException("Le thème livré avec le module ne peut pas être supprimé.");
        }
        if ($key === $this->activeKey()) {
            throw new \RuntimeException("Ce thème est actif : activez-en un autre avant de le supprimer.");
        }

        $this->removeTree($this->dir($key));
        $this->settings->forget('theme_option.' . $key . '.');
    }

    private function removeTree(string $dossier): void
    {
        $reel = realpath($dossier);
        if ($reel === false || !is_dir($reel)) { return; }

        // Garde-fou : on ne supprime que sous le dossier des thèmes du module.
        $base = realpath($this->themesDir);
        if ($base === false || !str_starts_with($reel, $base)) { return; }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($reel, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($reel);
    }

    // ── Service des fichiers de thème ─────────────────────────────────────

    /**
     * Sert un fichier du dossier assets d'un thème.
     *
     * Utile quand /modules n'est pas exposé directement par le serveur web.
     * Le nom est vérifié avant tout accès disque, et le chemin résolu doit
     * rester sous le dossier assets du thème.
     */
    public function streamAsset(string $key, string $relative): void
    {
        $nom = basename($relative);
        if (!preg_match('/^[A-Za-z0-9_\-]+\.(css|js|png|jpe?g|webp|gif|svg|woff2?|ttf|eot)$/i', $nom)) {
            http_response_code(400); exit;
        }

        $base = realpath($this->dir($key) . 'assets');
        $reel = $base === false ? false : realpath($base . '/' . $relative);
        if ($base === false || $reel === false || !str_starts_with($reel, $base) || !is_file($reel)) {
            http_response_code(404); exit;
        }

        $types = [
            'css' => 'text/css', 'js' => 'application/javascript',
            'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp', 'gif' => 'image/gif', 'svg' => 'image/svg+xml',
            'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
        ];
        $ext = strtolower(pathinfo($reel, PATHINFO_EXTENSION));

        header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
        header('Cache-Control: public, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        readfile($reel);
        exit;
    }

    private function clean(string $key): string
    {
        return preg_replace('/[^a-z0-9_-]/', '', strtolower(trim($key))) ?? '';
    }
}
