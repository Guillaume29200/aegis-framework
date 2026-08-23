<?php
declare(strict_types=1);

namespace System\Services;

/**
 * Génère la partie publique d'un module : moteur de gabarits, thème de départ
 * et écrans d'administration des thèmes.
 *
 * Ce code existait déjà — deux fois — dans GameNodeEsport et GameNodeHosting,
 * recopié à la main d'un module à l'autre. Le moteur et le gestionnaire de
 * thèmes vivent maintenant dans le framework ; il ne reste ici que le
 * squelette propre au module : ses routes, son contrôleur public, son thème
 * livré, et les deux pages d'administration qui vont avec.
 *
 * Rien de ce qui est produit ici ne contient de logique métier : c'est un
 * point de départ qui s'affiche correctement dès l'activation, et que le
 * développeur remplit ensuite.
 */
final class ModulePublicScaffold
{
    /**
     * @param string $name      Nom technique du module (PascalCase).
     * @param string $display   Nom affiché.
     * @param string $prefix    Préfixe des tables et des URL.
     * @param string $themeKey  Clé du thème livré (ex. « default »).
     * @param string $themeName Nom affiché du thème.
     * @param string[] $capabilities Capacités cochées à la génération.
     */
    public function __construct(
        private string $name,
        private string $display,
        private string $prefix,
        private string $themeKey,
        private string $themeName,
        private array $capabilities = [],
        private array $templates = []
    ) {
    }

    /** La liste et la fiche ont-elles été demandées ? */
    private function wantsList(): bool
    {
        return !empty($this->templates['list']);
    }

    /** La pagination n'a de sens que sur une liste. */
    private function wantsPagination(): bool
    {
        return $this->wantsList() && !empty($this->templates['pagination']);
    }

    /** La recherche non plus. */
    private function wantsSearch(): bool
    {
        return $this->wantsList() && !empty($this->templates['search']);
    }

    /**
     * Dossiers à créer en plus de ceux du squelette d'administration.
     *
     * Un thème est un dossier autonome : ses gabarits à la racine, et une
     * ossature assets/ toujours présente — css, js, images, uploads. Elle est
     * créée même vide, pour que l'intégrateur sache où poser ses fichiers sans
     * avoir à inventer l'arborescence.
     */
    public function directories(): array
    {
        $racine = '/themes/' . $this->themeKey;
        $dirs   = [$racine, $racine . '/assets'];

        foreach (\Framework\Templating\ThemeManager::ASSET_DIRS as $sous) {
            $dirs[] = $racine . '/assets/' . $sous;
        }
        return $dirs;
    }

    /**
     * Fichiers à écrire : chemin relatif au module => contenu.
     *
     * @return array<string,string>
     */
    public function files(): array
    {
        $t = $this->themeKey;

        $fichiers = [
            '/Services/' . $this->name . 'Themes.php'       => $this->tplThemeFactory(),
            '/Controllers/PublicController.php'             => $this->tplPublicController(),
            '/Controllers/ThemeAdminController.php'         => $this->tplThemeAdminController(),
            '/Views/admin/themes.php'                       => $this->tplThemesView(),
            '/Views/admin/theme-options.php'                => $this->tplThemeOptionsView(),
            '/themes/' . $t . '/meta.json'                  => $this->tplThemeMeta(),
            '/themes/' . $t . '/header.html'                => $this->tplHeaderHtml(),
            '/themes/' . $t . '/footer.html'                => $this->tplFooterHtml(),
            '/themes/' . $t . '/home.html'                  => $this->tplHomeHtml(),
            '/themes/' . $t . '/assets/css/theme.css'       => $this->tplThemeCss(),
            '/themes/' . $t . '/assets/js/theme.js'         => $this->tplThemeJs(),
            '/themes/' . $t . '/assets/images/.gitkeep'     => "",
            '/themes/' . $t . '/assets/uploads/.gitkeep'    => "",
            '/themes/' . $t . '/README.md'                  => $this->tplThemeReadme(),
        ];

        // Liste et fiche : deux gabarits de plus, et le contrôleur qui va avec
        // sait déjà paginer et chercher si on le lui a demandé.
        if ($this->wantsList()) {
            $fichiers['/themes/' . $t . '/list.html'] = $this->tplListHtml();
            $fichiers['/themes/' . $t . '/item.html'] = $this->tplItemHtml();
        }

        return $fichiers;
    }

    /** Lignes de routes à insérer dans le groupe d'administration. */
    public function adminRoutes(): string
    {
        $n = $this->name;
        return <<<PHP
        \$router->get('/themes', '{$n}\\\\Controllers\\\\ThemeAdminController@index');
        \$router->post('/themes/activate', '{$n}\\\\Controllers\\\\ThemeAdminController@activate');
        \$router->post('/themes/upload', '{$n}\\\\Controllers\\\\ThemeAdminController@upload');
        \$router->post('/themes/delete', '{$n}\\\\Controllers\\\\ThemeAdminController@delete');
        \$router->get('/themes/{key}/options', '{$n}\\\\Controllers\\\\ThemeAdminController@options');
        \$router->post('/themes/{key}/options', '{$n}\\\\Controllers\\\\ThemeAdminController@saveOptions');

PHP;
    }

    /** Lignes de routes publiques, hors groupe d'administration. */
    public function publicRoutes(): string
    {
        $n = $this->name;
        $p = $this->prefix;
        $listRoutes = $this->wantsList()
            ? "    \$router->get('/{$p}/liste', '{$n}\\\\Controllers\\\\PublicController@list');\n"
              . "    \$router->get('/{$p}/fiche/{slug}', '{$n}\\\\Controllers\\\\PublicController@item');\n"
            : '';
        return <<<PHP
    // ── Partie publique ──
    \$router->get('/{$p}', '{$n}\\\\Controllers\\\\PublicController@home');
{$listRoutes}
    // Fichiers d'un thème (assets/css, assets/js, assets/images…), servis par
    // le module : utile quand /modules n'est pas exposé par le serveur web.
    \$router->get('/{$p}/themes/{key}/assets/{sub}/{file}', '{$n}\\\\Controllers\\\\ThemeAdminController@asset');

PHP;
    }

    /**
     * Déclaration du préfixe public pour le manifeste.
     *
     * PublicPrefix lit `public.prefix` : c'est ce qui permet à l'administrateur
     * de renommer l'adresse du site visiteur — /monsite plutôt que /monmodule —
     * sans toucher une ligne du module.
     */
    public function publicManifest(): array
    {
        return [
            'prefix' => $this->prefix,
            'label'  => 'Site public',
            'hint'   => 'Adresse de la partie visiteur de ' . $this->display . '.',
        ];
    }

    /** Entrée de menu « Thèmes » à ajouter au manifeste. */
    public function menuChild(string $routeBase): array
    {
        return ['label' => 'Thèmes', 'icon' => '🎨', 'url' => $routeBase . '/themes'];
    }

    /**
     * Table des éléments, quand la liste est demandée.
     *
     * Elle remplace la table d'exemple du squelette : une liste a besoin
     * d'un identifiant d'URL, d'un résumé et d'un corps. Trois lignes sont
     * semées à l'installation — une liste vide au premier lancement laisse
     * croire que rien ne marche.
     */
    public function itemsTableSql(): ?string
    {
        if (!$this->wantsList()) { return null; }

        $p = $this->prefix;
        return <<<SQL
-- Éléments affichés par la liste et les fiches publiques.
CREATE TABLE IF NOT EXISTS `{$p}_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(190) NOT NULL,
    `slug` VARCHAR(190) NOT NULL,
    `excerpt` VARCHAR(400) NULL,
    `body` LONGTEXT NULL,
    `image` VARCHAR(255) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_{$p}_slug` (`slug`),
    KEY `idx_{$p}_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trois exemples, pour que la liste montre quelque chose dès l'activation.
INSERT INTO `{$p}_items` (`title`, `slug`, `excerpt`, `body`) VALUES
  ('Premier élément', 'premier-element', 'Un exemple semé à l''installation, que vous pouvez supprimer.', 'Le corps de la fiche. Modifiez-le depuis l''administration.'),
  ('Deuxième élément', 'deuxieme-element', 'La liste sait chercher et paginer si vous l''avez demandé.', 'Un deuxième exemple.'),
  ('Troisième élément', 'troisieme-element', 'Supprimez ces trois lignes quand vos vraies données arrivent.', 'Un troisième exemple.')
ON DUPLICATE KEY UPDATE `slug` = `slug`;

SQL;
    }

    /** Table de réglages, indispensable au gestionnaire de thèmes. */
    public function installSql(): string
    {
        $p = $this->prefix;
        return <<<SQL

-- Réglages du module : thème actif et options de chaque thème.
-- Le gestionnaire de thèmes du framework s'y branche ; pas de table à lui.
CREATE TABLE IF NOT EXISTS `{$p}_settings` (
    `setting_key` VARCHAR(190) NOT NULL,
    `setting_value` LONGTEXT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `{$p}_settings` (`setting_key`, `setting_value`)
VALUES ('active_theme', '{$this->themeKey}')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;

SQL;
    }

    public function uninstallSql(): string
    {
        return "DROP TABLE IF EXISTS `{$this->prefix}_settings`;\n";
    }

    // ── Fabrique du gestionnaire ──────────────────────────────────────────

    private function tplThemeFactory(): string
    {
        $n = $this->name;
        $p = $this->prefix;
        $t = $this->themeKey;

        return <<<PHP
<?php
declare(strict_types=1);

namespace {$n}\\Services;

use Framework\\Services\\Database;
use Framework\\Templating\\ThemeManager;
use Framework\\Templating\\ThemeSettings;

/**
 * Point d'entrée unique vers les thèmes de {$n}.
 *
 * Le moteur et le gestionnaire vivent dans le framework : il n'y a ici que le
 * câblage — quel module, quelle table de réglages, quel thème de repli.
 */
final class {$n}Themes
{
    /** Nom du thème livré avec le module : il sert de repli et ne s'efface pas. */
    public const FALLBACK = '{$t}';

    public static function manager(Database \$db): ThemeManager
    {
        return new ThemeManager(
            '{$n}',
            new ThemeSettings(\$db, '{$p}_settings'),
            self::FALLBACK
        );
    }
}

PHP;
    }

    // ── Contrôleur public ─────────────────────────────────────────────────

    private function tplPublicController(): string
    {
        $n = $this->name;
        $d = addslashes($this->display);
        $p = $this->prefix;

        // Liste et fiche ne sont câblées que si elles ont été demandées :
        // un module sans liste n'a pas à traîner un service inutilisé.
        $listMethods = $this->tplListMethods();
        $serviceUse  = $this->wantsList() ? "use {$n}\\Services\\{$n}Service;\n" : '';
        $serviceProp = $this->wantsList() ? "\n    private {$n}Service \$service;\n" : '';
        $serviceInit = $this->wantsList() ? "\n        \$this->service = new {$n}Service(\$db);\n" : '';
        $listUrl     = $this->wantsList() ? "\n                'list'   => u('/{$p}/liste')," : '';

        return <<<PHP
<?php
declare(strict_types=1);

namespace {$n}\\Controllers;

use Framework\\Capabilities\\CapabilityOutput;
use Framework\\Services\\Database;
use {$n}\\Services\\{$n}Themes;
{$serviceUse}
/**
 * Pages publiques de {$n}.
 *
 * Un gabarit n'exécute aucun PHP : tout ce qu'il affiche doit donc lui
 * arriver tout prêt. C'est le rôle de ce contrôleur — préparer les données,
 * puis rendre.
 */
class PublicController
{{$serviceProp}
    public function __construct(private Database \$db)
    {{$serviceInit}    }

    public function home(): void
    {
        \$themes = {$n}Themes::manager(\$this->db);

        echo \$themes->render('home', \$this->context(\$themes, [
            'page_title'       => '{$d}',
            'page_description' => 'Bienvenue sur {$d}.',
        ]));
    }

    /**
     * Contexte commun à toutes les pages publiques.
     *
     * `head_extra` et `body_end` portent ce que les capacités cochées à la
     * génération doivent poser sur la page — bandeau cookies, balises SEO,
     * tracker d'audience, script anti-bot. Les gabarits les rendent en brut,
     * c'est ce qui permet à ces briques de fonctionner sans une ligne de PHP
     * dans le thème.
     */
    private function context(\$themes, array \$data = []): array
    {
        \$caps = CapabilityOutput::forPage([
            'title'       => \$data['page_title'] ?? '{$d}',
            'description' => \$data['page_description'] ?? '',
        ]);

        \$options = \$themes->optionValues(\$themes->activeKey());

        return array_merge([
            'site'  => ['name' => '{$d}'],
            'theme' => \$options,
            'urls'  => [
                'home'   => u('/{$p}'),{$listUrl}
                'assets' => \$themes->assetsUrl(),
            ],
            'head_extra' => \$caps['head_extra'],
            'body_end'   => \$caps['body_end'],
            'year'       => date('Y'),
        ], \$data);
    }
{$listMethods}}

PHP;
    }

    // ── Contrôleur d'administration des thèmes ────────────────────────────

    private function tplThemeAdminController(): string
    {
        $n = $this->name;
        $p = $this->prefix;
        $d = addslashes($this->display);

        return <<<PHP
<?php
declare(strict_types=1);

namespace {$n}\\Controllers;

use Framework\\Security\\CSRFProtection;
use Framework\\Services\\Database;
use {$n}\\Services\\{$n}Themes;

/**
 * Administration des thèmes de {$n} : choisir le thème actif, en installer un
 * depuis une archive, régler ses options.
 *
 * Toute la mécanique est dans Framework\\Templating\\ThemeManager ; ce
 * contrôleur ne fait que la relier à des écrans et vérifier les jetons.
 */
class ThemeAdminController
{
    public function __construct(private Database \$db, private CSRFProtection \$csrf)
    {
    }

    public function index(): void
    {
        \$this->requireAdmin();

        \$manager   = {$n}Themes::manager(\$this->db);
        \$themes    = \$manager->availableThemes();
        \$active    = \$manager->activeKey();
        \$csrfToken = \$this->csrf->generateToken();
        \$pageTitle = 'Thèmes';
        \$moduleLabel = '{$d}';
        \$routeBase = '/admin/{$p}';
        \$publicUrl = u('/{$p}');

        require __DIR__ . '/../Views/admin/themes.php';
    }

    public function activate(): void
    {
        \$this->requireAdmin();
        \$this->checkToken();

        try {
            {$n}Themes::manager(\$this->db)->setActive((string) (\$_POST['key'] ?? ''));
            \$_SESSION['success'] = '✅ Thème activé.';
        } catch (\\Throwable \$e) {
            \$_SESSION['error'] = '❌ ' . \$e->getMessage();
        }
        redirect('/admin/{$p}/themes');
    }

    public function upload(): void
    {
        \$this->requireAdmin();
        \$this->checkToken();

        try {
            \$key = {$n}Themes::manager(\$this->db)->installZip(\$_FILES['theme'] ?? []);
            \$_SESSION['success'] = "✅ Thème « {\$key} » installé.";
        } catch (\\Throwable \$e) {
            \$_SESSION['error'] = '❌ ' . \$e->getMessage();
        }
        redirect('/admin/{$p}/themes');
    }

    public function delete(): void
    {
        \$this->requireAdmin();
        \$this->checkToken();

        try {
            {$n}Themes::manager(\$this->db)->delete((string) (\$_POST['key'] ?? ''));
            \$_SESSION['success'] = '🗑️ Thème supprimé.';
        } catch (\\Throwable \$e) {
            \$_SESSION['error'] = '❌ ' . \$e->getMessage();
        }
        redirect('/admin/{$p}/themes');
    }

    public function options(string \$key): void
    {
        \$this->requireAdmin();

        \$manager = {$n}Themes::manager(\$this->db);
        \$meta    = \$manager->meta(\$key);
        \$options = \$manager->declaredOptions(\$key);
        \$values  = \$manager->optionValues(\$key);

        // Les options arrivent groupées : au-delà d'une dizaine, une page à plat
        // devient illisible.
        \$groups = [];
        foreach (\$options as \$opt) {
            \$groups[\$opt['group']][] = \$opt;
        }

        \$themeKey  = \$key;
        \$csrfToken = \$this->csrf->generateToken();
        \$pageTitle = 'Options — ' . \$meta['name'];
        \$routeBase = '/admin/{$p}';

        require __DIR__ . '/../Views/admin/theme-options.php';
    }

    public function saveOptions(string \$key): void
    {
        \$this->requireAdmin();
        \$this->checkToken();

        try {
            {$n}Themes::manager(\$this->db)->saveOptions(\$key, \$_POST, \$_FILES);
            \$_SESSION['success'] = '✅ Options enregistrées.';
        } catch (\\Throwable \$e) {
            \$_SESSION['error'] = '❌ ' . \$e->getMessage();
        }
        redirect('/admin/{$p}/themes/' . \$key . '/options');
    }

    /** Sert un fichier du thème (feuille de style, image…). */
    public function asset(string \$key, string \$sub, string \$file): void
    {
        {$n}Themes::manager(\$this->db)->streamAsset(\$key, \$sub . '/' . \$file);
    }

    private function checkToken(): void
    {
        try {
            \$this->csrf->validateToken(\$_POST['csrf_token'] ?? '');
        } catch (\\Throwable \$e) {
            \$_SESSION['error'] = '❌ Jeton de sécurité invalide.';
            redirect('/admin/{$p}/themes');
        }
    }

    private function requireAdmin(): void
    {
        if (empty(\$_SESSION['logged_in']) || !in_array(\$_SESSION['role'] ?? '', ['admin', 'superadmin'], true)) {
            redirect('/auth/login');
        }
    }
}

PHP;
    }

    // ── Écran : liste des thèmes ──────────────────────────────────────────

    private function tplThemesView(): string
    {
        return <<<'PHP'
<?php
/** Thèmes du module (généré). Variables : $themes, $active, $csrfToken, $routeBase, $publicUrl, $moduleLabel */
if (!defined('AEGIS_FRAMEWORK')) die('Access denied');
admin_header($pageTitle ?? 'Thèmes');

$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$ok  = $_SESSION['success'] ?? null; unset($_SESSION['success']);
$err = $_SESSION['error'] ?? null;   unset($_SESSION['error']);
?>
<div class="adm-page-head">
    <div class="adm-breadcrumb">
        <a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span>
        <a href="<?= u($routeBase . '/dashboard') ?>"><?= $h($moduleLabel) ?></a><span>/</span><span>Thèmes</span>
    </div>
    <h1>🎨 Thèmes</h1>
    <p>Le thème actif habille la partie publique du module. Un thème n'est fait que de fichiers HTML et CSS&nbsp;: aucun code n'y est exécuté.</p>
</div>

<?php if ($ok): ?><div class="ui-card" style="border-color:var(--green);margin-bottom:14px"><div class="ui-card-body" style="color:var(--green)"><?= $h($ok) ?></div></div><?php endif; ?>
<?php if ($err): ?><div class="ui-card" style="border-color:var(--red-soft);margin-bottom:14px"><div class="ui-card-body" style="color:var(--red)"><?= $h($err) ?></div></div><?php endif; ?>

<div class="ui-card" style="margin-bottom:18px">
    <div class="ui-card-body u-flex u-gap" style="align-items:center;flex-wrap:wrap">
        <span class="u-muted" style="font-size:13px">Page publique&nbsp;:</span>
        <a class="ui-btn" href="<?= $h($publicUrl) ?>" target="_blank" rel="noopener">↗ Voir le rendu</a>
    </div>
</div>

<div class="thm-grid">
    <?php foreach ($themes as $t): ?>
        <div class="thm-card<?= $t['is_active'] ? ' is-active' : '' ?>">
            <div class="thm-shot">
                <?php if ($t['preview']): ?>
                    <img src="<?= $h($t['preview']) ?>" alt="">
                <?php else: ?>
                    <span class="thm-shot-ph"><?= $h($t['icon']) ?></span>
                <?php endif; ?>
                <?php if ($t['is_active']): ?><span class="thm-badge">Actif</span><?php endif; ?>
            </div>

            <div class="thm-body">
                <div class="thm-title"><?= $h($t['icon']) ?> <?= $h($t['name']) ?> <span class="thm-ver">v<?= $h($t['version']) ?></span></div>
                <?php if ($t['desc']): ?><p class="thm-desc"><?= $h($t['desc']) ?></p><?php endif; ?>
                <p class="thm-meta">
                    <?= (int) $t['templates'] ?> gabarit<?= $t['templates'] > 1 ? 's' : '' ?>
                    · <?= (int) $t['options'] ?> option<?= $t['options'] > 1 ? 's' : '' ?>
                    <?php if ($t['author']): ?> · <?= $h($t['author']) ?><?php endif; ?>
                </p>

                <div class="thm-actions">
                    <?php if (!$t['is_active']): ?>
                        <form method="post" action="<?= u($routeBase . '/themes/activate') ?>" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>">
                            <input type="hidden" name="key" value="<?= $h($t['key']) ?>">
                            <button class="ui-btn primary" type="submit">Activer</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($t['options'] > 0): ?>
                        <a class="ui-btn" href="<?= u($routeBase . '/themes/' . $t['key'] . '/options') ?>">⚙️ Options</a>
                    <?php endif; ?>

                    <?php if (!$t['is_active'] && !$t['is_default']): ?>
                        <form method="post" action="<?= u($routeBase . '/themes/delete') ?>" style="display:inline"
                              onsubmit="return confirm('Supprimer définitivement ce thème et ses réglages ?')">
                            <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>">
                            <input type="hidden" name="key" value="<?= $h($t['key']) ?>">
                            <button class="ui-btn danger" type="submit">Supprimer</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="ui-card" style="margin-top:18px">
    <div class="ui-card-head">📦 Installer un thème</div>
    <div class="ui-card-body">
        <form method="post" action="<?= u($routeBase . '/themes/upload') ?>" enctype="multipart/form-data" class="u-flex u-gap" style="align-items:flex-end;flex-wrap:wrap">
            <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>">
            <div class="fld" style="flex:1;min-width:260px;margin:0">
                <label class="form-label">Archive ZIP du thème</label>
                <input class="form-control" type="file" name="theme" accept=".zip" required>
            </div>
            <button class="ui-btn primary" type="submit">Installer</button>
        </form>
        <p class="form-text" style="margin-top:10px">
            L'archive doit contenir <strong>un seul dossier racine</strong> portant le nom du thème, et un <code>meta.json</code>.
            Seuls les fichiers HTML, CSS, JS, images et polices sont acceptés&nbsp;: <strong>aucun PHP</strong>, donc aucun code exécuté.
        </p>
    </div>
</div>

<style>
.thm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px}
.thm-card{border:1.5px solid var(--border);border-radius:12px;background:var(--surface);overflow:hidden;display:flex;flex-direction:column}
.thm-card.is-active{border-color:var(--accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 14%,transparent)}
.thm-shot{position:relative;aspect-ratio:16/10;background:var(--surface-3);display:grid;place-items:center;overflow:hidden}
.thm-shot img{width:100%;height:100%;object-fit:cover;display:block}
.thm-shot-ph{font-size:38px;opacity:.5}
.thm-badge{position:absolute;top:10px;left:10px;padding:3px 10px;border-radius:999px;background:var(--accent);color:#fff;font-size:11px;font-weight:700}
.thm-body{padding:14px;display:flex;flex-direction:column;gap:8px;flex:1}
.thm-title{font-weight:700;font-size:15px}
.thm-ver{font-weight:400;font-size:11px;color:var(--text-muted,#64748b)}
.thm-desc{margin:0;font-size:12.5px;line-height:1.55;color:var(--text-muted,#64748b)}
.thm-meta{margin:0;font-size:11.5px;color:var(--text-muted,#64748b)}
.thm-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:auto;padding-top:6px}
</style>
<?php admin_footer(); ?>

PHP;
    }

    // ── Écran : options d'un thème ────────────────────────────────────────

    private function tplThemeOptionsView(): string
    {
        return <<<'PHP'
<?php
/** Options d'un thème (généré). Variables : $groups, $values, $meta, $themeKey, $csrfToken, $routeBase */
if (!defined('AEGIS_FRAMEWORK')) die('Access denied');
admin_header($pageTitle ?? 'Options du thème');

$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$ok  = $_SESSION['success'] ?? null; unset($_SESSION['success']);
$err = $_SESSION['error'] ?? null;   unset($_SESSION['error']);
$noms = array_keys($groups);
?>
<div class="adm-page-head">
    <div class="adm-breadcrumb">
        <a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span>
        <a href="<?= u($routeBase . '/themes') ?>">Thèmes</a><span>/</span><span><?= $h($meta['name']) ?></span>
    </div>
    <h1><?= $h($meta['icon']) ?> <?= $h($meta['name']) ?></h1>
    <p>Ces réglages sont propres à ce thème&nbsp;: en changer n'affecte pas les autres.</p>
</div>

<?php if ($ok): ?><div class="ui-card" style="border-color:var(--green);margin-bottom:14px"><div class="ui-card-body" style="color:var(--green)"><?= $h($ok) ?></div></div><?php endif; ?>
<?php if ($err): ?><div class="ui-card" style="border-color:var(--red-soft);margin-bottom:14px"><div class="ui-card-body" style="color:var(--red)"><?= $h($err) ?></div></div><?php endif; ?>

<?php if (!$groups): ?>
    <div class="ui-card"><div class="ui-card-body u-muted">Ce thème ne déclare aucune option.</div></div>
<?php else: ?>
<form method="post" action="<?= u($routeBase . '/themes/' . $themeKey . '/options') ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>">

    <?php if (count($noms) > 1): ?>
        <div class="thopt-tabs" role="tablist">
            <?php foreach ($noms as $i => $nom): ?>
                <button class="thopt-tab<?= $i === 0 ? ' is-on' : '' ?>" type="button" data-i="<?= $i ?>"><?= $h($nom) ?>
                    <span><?= count($groups[$nom]) ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php foreach ($noms as $i => $nom): ?>
        <div class="thopt-pane<?= $i === 0 ? ' is-on' : '' ?>" data-i="<?= $i ?>">
            <div class="ui-card">
                <div class="ui-card-head"><?= $h($nom) ?></div>
                <div class="ui-card-body">
                    <?php foreach ($groups[$nom] as $opt):
                        $k = $opt['key'];
                        $v = $values[$k] ?? $opt['default'];
                    ?>
                        <div class="fld" style="margin-bottom:16px">
                            <label class="form-label" for="opt-<?= $h($k) ?>"><?= $h($opt['label']) ?></label>

                            <?php if ($opt['type'] === 'toggle'): ?>
                                <label class="ui-switch" style="display:inline-flex;align-items:center;gap:10px">
                                    <input type="checkbox" id="opt-<?= $h($k) ?>" name="<?= $h($k) ?>" value="1" <?= !empty($v) ? 'checked' : '' ?>>
                                    <span class="u-muted" style="font-size:12.5px">Activé</span>
                                </label>

                            <?php elseif ($opt['type'] === 'select'): ?>
                                <select class="form-select" id="opt-<?= $h($k) ?>" name="<?= $h($k) ?>">
                                    <?php foreach ($opt['choices'] as $val => $label): ?>
                                        <option value="<?= $h($val) ?>" <?= (string) $v === (string) $val ? 'selected' : '' ?>><?= $h($label) ?></option>
                                    <?php endforeach; ?>
                                </select>

                            <?php elseif ($opt['type'] === 'color'): ?>
                                <input class="form-control" style="max-width:120px;padding:4px" type="color" id="opt-<?= $h($k) ?>" name="<?= $h($k) ?>" value="<?= $h($v ?: '#000000') ?>">

                            <?php elseif ($opt['type'] === 'number'): ?>
                                <input class="form-control" style="max-width:160px" type="number" id="opt-<?= $h($k) ?>" name="<?= $h($k) ?>" value="<?= $h((string) $v) ?>">

                            <?php elseif ($opt['type'] === 'textarea' || $opt['type'] === 'links'): ?>
                                <textarea class="form-control" rows="4" id="opt-<?= $h($k) ?>" name="<?= $h($k) ?>"><?= $h($opt['type'] === 'links' ? ($values[$k . '_raw'] ?? '') : (string) $v) ?></textarea>

                            <?php elseif ($opt['type'] === 'image'): ?>
                                <?php if (!empty($v)): ?>
                                    <div style="margin-bottom:8px">
                                        <img src="<?= $h($v) ?>" alt="" style="max-height:110px;border-radius:8px;border:1px solid var(--border)">
                                    </div>
                                    <label class="u-muted" style="display:inline-flex;align-items:center;gap:7px;font-size:12.5px;margin-bottom:8px">
                                        <input type="checkbox" name="<?= $h($k) ?>_remove" value="1"> Retirer cette image
                                    </label>
                                <?php endif; ?>
                                <input class="form-control" type="file" id="opt-<?= $h($k) ?>" name="<?= $h($k) ?>" accept="image/*">

                            <?php else: ?>
                                <input class="form-control" type="text" id="opt-<?= $h($k) ?>" name="<?= $h($k) ?>" value="<?= $h((string) $v) ?>">
                            <?php endif; ?>

                            <?php if ($opt['help']): ?><p class="form-text"><?= $h($opt['help']) ?></p><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <div style="display:flex;gap:10px;justify-content:flex-end;margin:18px 0">
        <a class="ui-btn" href="<?= u($routeBase . '/themes') ?>">Retour</a>
        <button class="ui-btn primary" type="submit">Enregistrer</button>
    </div>
</form>

<style>
.thopt-tabs{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px}
.thopt-tab{display:inline-flex;align-items:center;gap:7px;padding:8px 13px;border:1.5px solid var(--border);border-radius:9px;background:var(--surface);font:inherit;font-size:13px;cursor:pointer}
.thopt-tab span{padding:1px 7px;border-radius:999px;background:var(--surface-3);font-size:11px;font-weight:700}
.thopt-tab.is-on{border-color:var(--accent);background:color-mix(in srgb,var(--accent) 8%,var(--surface));font-weight:600}
.thopt-tab.is-on span{background:var(--accent);color:#fff}
.thopt-pane{display:block}
</style>
<script>
(function () {
    var tabs  = document.querySelectorAll('.thopt-tab');
    var panes = document.querySelectorAll('.thopt-pane');
    if (tabs.length < 2) { return; }

    // Sans script, tous les panneaux restent visibles : la page fonctionne,
    // elle est simplement plus longue. Et comme aucun panneau n'est retiré du
    // document, tous les champs sont bien envoyés à l'enregistrement.
    function montre(i) {
        tabs.forEach(function (t) { t.classList.toggle('is-on', t.dataset.i === String(i)); });
        panes.forEach(function (p) { p.style.display = p.dataset.i === String(i) ? '' : 'none'; });
    }

    tabs.forEach(function (t) {
        t.addEventListener('click', function () { montre(t.dataset.i); });
    });
    montre(0);
})();
</script>
<?php endif; ?>
<?php admin_footer(); ?>

PHP;
    }

    // ── Le thème livré ────────────────────────────────────────────────────

    private function tplHeaderHtml(): string
    {
        return <<<'HTML'
<!-- Gabarit d'en-tête du thème.

     Rappel utile : le filtre « default: » prend son argument pour une chaîne
     littérale, jamais pour un chemin. « {{ a | default:b.c }} » écrit donc
     « b.c » à l'écran. Pour retomber sur une autre valeur, il faut un
     {% if %} / {% else %}, comme pour le nom de la marque ci-dessous. -->
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ page_title }}</title>

<!-- Ce que les capacités du module posent dans l'en-tête : balises SEO,
     tracker d'audience, script anti-bot. Rendu en brut, car ce HTML est
     produit par le module, pas saisi par un visiteur. -->
{{{ head_extra }}}

<link rel="stylesheet" href="{{ urls.assets }}/css/theme.css">
<style>
  /* Les réglages du thème arrivent ici, et nulle part ailleurs : c'est le
     seul endroit où l'administration parle au rendu. */
  :root {
    --accent: {{ theme.accent | default:'#6d5efc' }};
    --bg:     {{ theme.background | default:'#0f1115' }};
    --ink:    {{ theme.text_color | default:'#f2f3f7' }};
    --radius: {{ theme.radius | default:'12' }}px;
  }
</style>
</head>
<body class="pg{% if not theme.wide %} pg--narrow{% endif %}">

<header class="pg-top">
  <div class="pg-wrap pg-top__in">
    <a class="pg-brand" href="{{ urls.home }}">
      <span class="pg-brand__mark" aria-hidden="true">{{ theme.brand_emoji | default:'🧩' }}</span>
      <b>{% if theme.brand_name %}{{ theme.brand_name }}{% else %}{{ site.name }}{% endif %}</b>
    </a>

    {% if theme.tagline %}<span class="pg-tagline">{{ theme.tagline }}</span>{% endif %}
  </div>
</header>

<main class="pg-wrap pg-main">
HTML;
    }

    private function tplFooterHtml(): string
    {
        return <<<'HTML'
</main>

<footer class="pg-foot">
  <div class="pg-wrap">
    <p>{{ theme.footer_text | default:'Propulsé par Aegis.' }}</p>
    <p class="pg-foot__legal">© {{ year }} {{ site.name }}</p>
  </div>
</footer>

<!-- Ce que les capacités posent en fin de page : aujourd'hui le bandeau de
     consentement aux cookies, quand la capacité RGPD est cochée et le bandeau
     activé dans Configuration. -->
{{{ body_end }}}

<script src="{{ urls.assets }}/js/theme.js" defer></script>

</body>
</html>
HTML;
    }

    private function tplHomeHtml(): string
    {
        $display = $this->display;
        $module  = $this->name;

        return str_replace(
            ['@@DISPLAY@@', '@@MODULE@@'],
            [$display, $module],
            <<<'HTML'
{% include "header" %}

<section class="pg-hero">
  <span class="pg-hero__kicker">{{ theme.hero_kicker | default:'Bienvenue' }}</span>
  <h1 class="pg-hero__title">{{ theme.hero_title | default:'@@DISPLAY@@' }}</h1>
  <p class="pg-hero__text">{{ theme.hero_text | default:'Cette page vient du thème du module. Modifiez-la dans Views/themes, sans écrire une ligne de PHP.' }}</p>

  {% if theme.hero_button_label %}
  <a class="pg-btn" href="{{ theme.hero_button_url | default:'#' }}">{{ theme.hero_button_label }}</a>
  {% endif %}
</section>

<section class="pg-cards">
  <article class="pg-card">
    <h2>🎨 Un thème, des fichiers HTML</h2>
    <p>Les gabarits vivent dans <code>Views/themes/</code>. Aucun PHP ne s'y exécute : un thème peut donc être partagé et installé sans risque.</p>
  </article>

  <article class="pg-card">
    <h2>⚙️ Des options déclarées</h2>
    <p>Le fichier <code>meta.json</code> décrit les réglages du thème. Ils apparaissent tout seuls dans l'administration, et arrivent ici sous <code>theme.*</code>.</p>
  </article>

  <article class="pg-card">
    <h2>🧩 Le moteur est partagé</h2>
    <p>Il vit dans le framework, pas dans ce module. Ce qu'on améliore là-bas profite à tous les modules d'un coup.</p>
  </article>
</section>

{% include "footer" %}
HTML
        );
    }

    private function tplThemeMeta(): string
    {
        $meta = [
            'name'    => $this->themeName,
            'icon'    => '🎨',
            'desc'    => 'Thème livré avec le module ' . $this->display . '. Sert aussi de repli : un gabarit absent d\'un autre thème est repris ici.',
            'author'  => 'Aegis',
            'version' => '1.0.0',
            'options' => [
                ['key' => 'brand_name', 'type' => 'text', 'label' => '🏷️ Nom affiché', 'group' => '🧭 En-tête', 'help' => "Laissez vide pour reprendre le nom du site.", 'default' => ''],
                ['key' => 'brand_emoji', 'type' => 'text', 'label' => '✨ Emoji du logo', 'group' => '🧭 En-tête', 'default' => '🧩'],
                ['key' => 'tagline', 'type' => 'text', 'label' => '💬 Baseline', 'group' => '🧭 En-tête', 'default' => ''],

                ['key' => 'accent', 'type' => 'color', 'label' => '🎨 Couleur d\'accent', 'group' => '🎨 Couleurs', 'default' => '#6d5efc'],
                ['key' => 'background', 'type' => 'color', 'label' => '🌑 Fond', 'group' => '🎨 Couleurs', 'default' => '#0f1115'],
                ['key' => 'text_color', 'type' => 'color', 'label' => '🔤 Texte', 'group' => '🎨 Couleurs', 'default' => '#f2f3f7'],

                ['key' => 'wide', 'type' => 'toggle', 'label' => '↔️ Pleine largeur', 'group' => '📐 Mise en page', 'help' => "Désactivé, le contenu reste centré sur une colonne lisible.", 'default' => false],
                ['key' => 'radius', 'type' => 'select', 'label' => '🔲 Arrondi', 'group' => '📐 Mise en page', 'choices' => ['0' => 'Aucun', '8' => 'Léger', '12' => 'Standard', '20' => 'Marqué'], 'default' => '12'],

                ['key' => 'hero_kicker', 'type' => 'text', 'label' => 'Sur-titre', 'group' => '🏠 Accueil', 'default' => 'Bienvenue'],
                ['key' => 'hero_title', 'type' => 'text', 'label' => 'Titre', 'group' => '🏠 Accueil', 'default' => $this->display],
                ['key' => 'hero_text', 'type' => 'textarea', 'label' => 'Texte', 'group' => '🏠 Accueil', 'default' => ''],
                ['key' => 'hero_button_label', 'type' => 'text', 'label' => 'Bouton — libellé', 'group' => '🏠 Accueil', 'help' => "Videz ce champ pour retirer le bouton.", 'default' => ''],
                ['key' => 'hero_button_url', 'type' => 'text', 'label' => 'Bouton — lien', 'group' => '🏠 Accueil', 'default' => ''],
                ['key' => 'hero_image', 'type' => 'image', 'label' => '🌄 Image de fond', 'group' => '🏠 Accueil', 'help' => "Format conseillé : 1400 × 500 px.", 'default' => ''],

                ['key' => 'footer_text', 'type' => 'text', 'label' => 'Texte du pied de page', 'group' => '⬇️ Pied de page', 'default' => 'Propulsé par Aegis.'],
            ],
        ];

        // Les réglages de la liste n'apparaissent que si la liste existe :
        // une option qui ne pilote rien est une option de trop.
        if ($this->wantsList()) {
            $meta['options'] = array_merge($meta['options'], [
                ['key' => 'list_title', 'type' => 'text', 'label' => 'Titre de la liste', 'group' => '📄 Liste', 'default' => 'Nos éléments'],
                ['key' => 'list_text', 'type' => 'textarea', 'label' => 'Texte d\'introduction', 'group' => '📄 Liste', 'default' => ''],
                ['key' => 'list_emoji', 'type' => 'text', 'label' => 'Emoji de remplacement', 'group' => '📄 Liste', 'help' => "Affiché à la place d'une image manquante.", 'default' => '📄'],
                ['key' => 'list_empty', 'type' => 'textarea', 'label' => 'Texte quand la liste est vide', 'group' => '📄 Liste', 'default' => 'Rien à afficher pour le moment.'],
                ['key' => 'item_more_title', 'type' => 'text', 'label' => 'Titre du bloc « à voir aussi »', 'group' => '📄 Liste', 'default' => 'À voir aussi'],
            ]);
        }

        if ($this->wantsSearch()) {
            $meta['options'] = array_merge($meta['options'], [
                ['key' => 'list_search', 'type' => 'toggle', 'label' => '🔍 Afficher la recherche', 'group' => '📄 Liste', 'help' => "La recherche interroge le serveur : le nombre trouvé reste donc exact, même paginé.", 'default' => true],
                ['key' => 'list_search_ph', 'type' => 'text', 'label' => 'Recherche — texte d\'invite', 'group' => '📄 Liste', 'default' => 'Rechercher…'],
                ['key' => 'list_empty_search', 'type' => 'textarea', 'label' => 'Texte quand la recherche ne donne rien', 'group' => '📄 Liste', 'default' => 'Aucun résultat pour cette recherche.'],
            ]);
        }

        return json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    }

    private function tplListHtml(): string
    {
        $recherche = $this->wantsSearch() ? <<<'HTML'

  {% if theme.list_search %}
  <!-- La recherche part au serveur : la liste est paginée, filtrer les seules
       cartes affichées mentirait au-delà de la première page. -->
  <form class="pg-find" method="get" action="{{ urls.list }}" role="search">
    <input class="pg-find__in" type="search" name="q" value="{{ search }}"
           placeholder="{{ theme.list_search_ph | default:'Rechercher…' }}" aria-label="Rechercher">
    <button class="pg-btn pg-btn--sm" type="submit">Chercher</button>
    {% if search %}<a class="pg-find__raz" href="{{ urls.list }}" title="Tout afficher">✕</a>{% endif %}
  </form>
  {% endif %}

HTML : '';

        $pagination = $this->wantsPagination() ? <<<'HTML'

  {% if pagination %}
  <nav class="pg-pages" aria-label="Pages">
    {% for lien in pagination %}
    <a class="{% if lien.is_current %}is-on{% endif %}" href="{{ lien.url }}"
       {% if lien.is_current %}aria-current="page"{% endif %}>{{ lien.number }}</a>
    {% endfor %}
  </nav>
  {% endif %}

HTML : '';

        return str_replace(
            ['@@RECHERCHE@@', '@@PAGINATION@@'],
            [$recherche, $pagination],
            <<<'HTML'
{% include "header" %}

<section class="pg-head">
  <h1 class="pg-head__title">{{ theme.list_title | default:'Nos éléments' }}</h1>
  {% if theme.list_text %}<p class="pg-head__text">{{ theme.list_text }}</p>{% endif %}
  <span class="pg-head__count">{{ total_label }}</span>
</section>
@@RECHERCHE@@
{% if is_filtered %}
<p class="pg-recall">
  <b>{{ total_label }}</b>{% if search %} pour « {{ search }} »{% endif %}
  <a href="{{ urls.list }}">Tout afficher</a>
</p>
{% endif %}

<div class="pg-list">
  {% for item in items %}
  <article class="pg-item">
    <a class="pg-item__media" href="{{ item.url }}">
      {% if item.image %}
        <img src="{{ item.image }}" alt="" loading="lazy">
      {% else %}
        <span class="pg-item__ph" aria-hidden="true">{{ theme.list_emoji | default:'📄' }}</span>
      {% endif %}
    </a>

    <div class="pg-item__say">
      <a class="pg-item__title" href="{{ item.url }}">{{ item.title }}</a>
      {% if item.excerpt %}<p class="pg-item__text">{{ item.excerpt | truncate:140 }}</p>{% endif %}
      {% if item.date_label %}<span class="pg-item__meta">{{ item.date_label }}</span>{% endif %}
    </div>
  </article>

  {% empty %}
  <div class="pg-void">
    <span class="pg-void__emo" aria-hidden="true">{{ theme.list_emoji | default:'📄' }}</span>
    {% if is_filtered %}
      {{ theme.list_empty_search | default:'Aucun résultat pour cette recherche.' }}
    {% else %}
      {{ theme.list_empty | default:'Rien à afficher pour le moment.' }}
    {% endif %}
  </div>
  {% endfor %}
</div>
@@PAGINATION@@
{% include "footer" %}
HTML
        );
    }

    private function tplItemHtml(): string
    {
        return <<<'HTML'
{% include "header" %}

<nav class="pg-crumb" aria-label="Fil d'Ariane">
  <a href="{{ urls.home }}">Accueil</a>
  <span aria-hidden="true">›</span>
  <a href="{{ urls.list }}">{{ theme.list_title | default:'Nos éléments' }}</a>
  <span aria-hidden="true">›</span>
  <b>{{ item.title }}</b>
</nav>

<article class="pg-single">
  {% if item.image %}
  <img class="pg-single__img" src="{{ item.image }}" alt="">
  {% endif %}

  <h1 class="pg-single__title">{{ item.title }}</h1>
  {% if item.date_label %}<p class="pg-single__meta">{{ item.date_label }}</p>{% endif %}

  {% if item.body %}
  <div class="pg-single__body">{{ item.body | nl2br }}</div>
  {% else %}
  <p class="pg-none">Cette fiche n'a pas encore de contenu.</p>
  {% endif %}

  <a class="pg-btn" href="{{ urls.list }}">← Retour à la liste</a>
</article>

{% if others %}
<section class="pg-more">
  <h2 class="pg-more__title">{{ theme.item_more_title | default:'À voir aussi' }}</h2>
  <div class="pg-list pg-list--tight">
    {% for autre in others %}
    <article class="pg-item">
      <a class="pg-item__media" href="{{ autre.url }}">
        {% if autre.image %}
          <img src="{{ autre.image }}" alt="" loading="lazy">
        {% else %}
          <span class="pg-item__ph" aria-hidden="true">{{ theme.list_emoji | default:'📄' }}</span>
        {% endif %}
      </a>
      <div class="pg-item__say">
        <a class="pg-item__title" href="{{ autre.url }}">{{ autre.title }}</a>
      </div>
    </article>
    {% endfor %}
  </div>
</section>
{% endif %}

{% include "footer" %}
HTML;
    }

    /**
     * Méthodes « liste » et « fiche » du contrôleur public.
     *
     * Recherche et pagination ne sont câblées que si elles ont été demandées :
     * un module qui n'en veut pas ne reçoit pas le code correspondant.
     */

    /**
     * Méthodes ajoutées au service du module quand la liste est demandée.
     *
     * Elles vivent dans le service et non dans le contrôleur : c'est là que
     * les requêtes doivent être, et c'est ce que le module généré doit montrer
     * à celui qui le reprendra.
     */
    public function serviceMethods(): string
    {
        if (!$this->wantsList()) { return ''; }

        $p = $this->prefix;

        // Capacité « cache » : la liste publique est la requête la plus
        // sollicitée du module, c'est elle qu'il vaut la peine de mémoriser.
        $cache = in_array('cache', $this->capabilities, true);
        // Le helper cache_remember() vient du framework, mais on ne le suppose
        // pas présent : un service doit répondre même si la brique de cache
        // manque. Sans elle, la requête est simplement refaite à chaque appel.
        $ouvre = $cache
            ? "        // Capacité « cache » : mémorisé 60 s quand la brique est là.\n"
              . "        \$calcul = function () use (\$page, \$perPage, \$search) {\n"
            : '';
        $ferme = $cache
            ? "        };\n\n"
              . "        return function_exists('cache_remember')\n"
              . "            ? cache_remember('{$p}_list_' . \$page . '_' . \$perPage . '_' . md5(\$search), \$calcul, 60)\n"
              . "            : \$calcul();\n"
            : '';
        $ind   = $cache ? '    ' : '';

        // Capacité « markdown » : le corps d'une fiche est écrit en Markdown,
        // il est rendu ici une bonne fois plutôt que dans chaque gabarit.
        $md = in_array('markdown', $this->capabilities, true)
            ? "\n            // Capacité « markdown » : le corps est rendu en HTML sûr.\n            'body_html' => function_exists('md_render') ? md_render((string) \$row['body']) : nl2br(htmlspecialchars((string) \$row['body'], ENT_QUOTES, 'UTF-8')),"
            : '';

        return <<<PHP

    /**
     * Une page de la liste publique.
     *
     * @return array{items:array, total:int, pages:int}
     */
    public function paginate(int \$page = 1, int \$perPage = 12, string \$search = ''): array
    {
{$ouvre}{$ind}        \$where  = ['is_active = 1'];
{$ind}        \$params = [];

{$ind}        if (\$search !== '') {
{$ind}            \$where[]  = '(title LIKE ? OR excerpt LIKE ?)';
{$ind}            \$params[] = '%' . \$search . '%';
{$ind}            \$params[] = '%' . \$search . '%';
{$ind}        }

{$ind}        \$condition = implode(' AND ', \$where);
{$ind}        \$total = 0;
{$ind}        \$rows  = [];

{$ind}        try {
{$ind}            \$compte = \$this->db->queryOne("SELECT COUNT(*) AS n FROM {$p}_items WHERE {\$condition}", \$params);
{$ind}            \$total  = (int) (\$compte['n'] ?? 0);

{$ind}            \$pages  = max(1, (int) ceil(\$total / max(1, \$perPage)));
{$ind}            \$page   = min(max(1, \$page), \$pages);
{$ind}            \$offset = (\$page - 1) * \$perPage;

{$ind}            \$rows = \$this->db->query(
{$ind}                "SELECT * FROM {$p}_items WHERE {\$condition} ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?",
{$ind}                array_merge(\$params, [\$perPage, \$offset])
{$ind}            ) ?: [];
{$ind}        } catch (\\Throwable \$e) {
{$ind}            // Table absente : le module n'est pas encore installé.
{$ind}        }

{$ind}        return [
{$ind}            'items' => array_map([\$this, 'present'], \$rows),
{$ind}            'total' => \$total,
{$ind}            'pages' => max(1, (int) ceil(\$total / max(1, \$perPage))),
{$ind}        ];
{$ferme}    }

    /** Une fiche, par son identifiant d'URL. */
    public function findBySlug(string \$slug): ?array
    {
        try {
            \$row = \$this->db->queryOne("SELECT * FROM {$p}_items WHERE slug = ? AND is_active = 1", [\$slug]);
        } catch (\\Throwable \$e) {
            return null;
        }
        return \$row ? \$this->present(\$row) : null;
    }

    /** Quelques autres fiches, pour ne pas laisser le lecteur en cul-de-sac. */
    public function others(int \$exceptId, int \$limit = 3): array
    {
        try {
            \$rows = \$this->db->query(
                "SELECT * FROM {$p}_items WHERE is_active = 1 AND id <> ? ORDER BY RAND() LIMIT ?",
                [\$exceptId, max(1, \$limit)]
            ) ?: [];
        } catch (\\Throwable \$e) {
            return [];
        }
        return array_map([\$this, 'present'], \$rows);
    }

    /**
     * Liens de pagination, prêts à poser.
     *
     * Le gabarit ne sait ni compter ni comparer : il reçoit les liens et le
     * drapeau « page courante » déjà décidés.
     */
    public function paginationLinks(int \$pages, int \$current, string \$search = ''): array
    {
        if (\$pages < 2) { return []; }

        \$base  = u('/{$p}/liste');
        \$liens = [];

        for (\$i = 1; \$i <= \$pages; \$i++) {
            \$query = array_filter(['q' => \$search, 'page' => \$i > 1 ? \$i : null]);
            \$liens[] = [
                'number'     => \$i,
                'url'        => \$query === [] ? \$base : \$base . '?' . http_build_query(\$query),
                'is_current' => \$i === \$current,
            ];
        }
        return \$liens;
    }

    /**
     * Met une ligne en forme pour l'affichage.
     *
     * Un gabarit n'exécute aucun PHP : dates formatées, URL construites et
     * libellés accordés doivent donc arriver tout prêts.
     */
    private function present(array \$row): array
    {
        \$mois = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
                 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
        \$t = strtotime((string) (\$row['created_at'] ?? ''));

        return [
            'id'         => (int) \$row['id'],
            'title'      => (string) \$row['title'],
            'slug'       => (string) (\$row['slug'] ?? ''),
            'excerpt'    => (string) (\$row['excerpt'] ?? ''),
            'body'       => (string) (\$row['body'] ?? ''),{$md}
            'image'      => (string) (\$row['image'] ?? ''),
            'date_label' => \$t ? ((int) date('j', \$t) . ' ' . \$mois[(int) date('n', \$t)] . ' ' . date('Y', \$t)) : '',
            'url'        => u('/{$p}/fiche/' . (string) (\$row['slug'] ?? '')),
        ];
    }

PHP;
    }

    private function tplListMethods(): string
    {
        if (!$this->wantsList()) { return ''; }

        $n = $this->name;

        $lectureRecherche = $this->wantsSearch()
            ? "\$recherche = trim((string) (\$_GET['q'] ?? ''));"
            : "\$recherche = '';";

        $lecturePage = $this->wantsPagination()
            ? "\$page = max(1, (int) (\$_GET['page'] ?? 1));"
            : "\$page = 1;";

        $parPage = $this->wantsPagination() ? '12' : '100';

        $liens = $this->wantsPagination()
            ? "\$this->service->paginationLinks(\$resultat['pages'], \$page, \$recherche)"
            : '[]';

        return <<<PHP

    /**
     * La liste.
     *
     * Recherche et pagination passent par le serveur : filtrer une liste
     * paginée dans le navigateur donnerait un compte faux dès la deuxième page.
     */
    public function list(): void
    {
        {$lectureRecherche}
        {$lecturePage}

        \$resultat = \$this->service->paginate(\$page, {$parPage}, \$recherche);
        \$themes   = {$n}Themes::manager(\$this->db);

        echo \$themes->render('list', \$this->context(\$themes, [
            'page_title'       => 'Liste',
            'page_description' => 'Tous les éléments publiés.',
            'items'            => \$resultat['items'],
            'total'            => \$resultat['total'],
            'total_label'      => \$resultat['total'] . ' élément' . (\$resultat['total'] > 1 ? 's' : ''),
            'search'           => \$recherche,
            'is_filtered'      => \$recherche !== '',
            'pagination'       => {$liens},
        ]));
    }

    /** La fiche d'un élément. */
    public function item(string \$slug): void
    {
        \$item   = \$this->service->findBySlug(\$slug);
        \$themes = {$n}Themes::manager(\$this->db);

        if (\$item === null) {
            // Fiche inconnue : on répond 404 et l'on renvoie vers l'accueil du
            // module plutôt que de laisser une page vide.
            http_response_code(404);
            echo \$themes->render('home', \$this->context(\$themes, [
                'page_title' => 'Introuvable',
            ]));
            return;
        }

        echo \$themes->render('item', \$this->context(\$themes, [
            'page_title'       => \$item['title'],
            'page_description' => \$item['excerpt'],
            'page_image'       => \$item['image'],
            'item'             => \$item,
            // Quelques autres fiches, pour ne pas laisser le lecteur en cul-de-sac.
            'others'           => \$this->service->others((int) \$item['id'], 3),
        ]));
    }

PHP;
    }

    private function tplThemeJs(): string
    {
        return <<<'JS'
/* Script du thème.

   Vide au départ, et c'est voulu : le thème livré n'a besoin de rien pour
   s'afficher. Ce fichier existe pour que l'intégrateur sache où poser le sien,
   plutôt que d'ajouter une balise script au milieu d'un gabarit.

   Il est chargé en fin de page avec « defer » : le rendu n'attend pas. */

(function () {
    'use strict';

    // Un visiteur qui a demandé moins d'animations ne doit rien subir : c'est
    // une préférence d'accessibilité, elle prime sur les choix du thème.
    var calme = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (calme) { document.documentElement.classList.add('pg-calme'); }
})();
JS;
    }

    private function tplThemeReadme(): string
    {
        $nom    = $this->themeName;
        $module = $this->display;
        $cle    = $this->themeKey;

        return <<<MD
# Thème « {$nom} »

Thème livré avec le module **{$module}**. Il sert aussi de **repli** : un
gabarit absent d'un autre thème est repris ici, si bien qu'un thème incomplet
donne quand même une page complète.

## Arborescence

```
{$cle}/
├── meta.json          Le manifeste : nom, auteur, et les options réglables
├── header.html        Les gabarits, à la racine
├── footer.html
├── home.html
├── preview.png        (facultatif) l'aperçu montré dans l'administration
└── assets/
    ├── css/theme.css  La feuille de style
    ├── js/theme.js    Les scripts éventuels
    ├── images/        Les images livrées avec le thème
    └── uploads/       Celles que l'administrateur téléverse
```

## Ce qu'un gabarit sait faire

Aucun PHP ne s'exécute dans un thème. La syntaxe est volontairement étroite :

```
{{ variable }}              valeur échappée
{{{ variable }}}            valeur brute (HTML produit par le module)
{{ a.b.c }}                 chemin dans les données
{{ x | filtre:argument }}   upper, lower, date, datetime, truncate, nl2br,
                            number, default, count, initials
{% if chemin %} … {% else %} … {% endif %}
{% if not chemin %} … {% endif %}
{% for x in liste %} … {% empty %} … {% endfor %}
{% include "partiel" %}
```

Ce qu'il ne sait **pas** faire, délibérément : comparer deux valeurs, calculer,
appeler une fonction. Tout ce qui relève d'une décision se prépare côté PHP et
arrive dans les données — un gabarit affiche, il ne raisonne pas.

⚠️ Le filtre `default:` prend son argument pour une **chaîne littérale**, jamais
pour un chemin : `{{ a | default:b.c }}` écrit « b.c » à l'écran. Pour retomber
sur une autre valeur, utilisez `{% if %}` / `{% else %}`.

## Les options

Elles se déclarent dans `meta.json`, apparaissent toutes seules dans
l'administration, et arrivent dans les gabarits sous `theme.<clé>` :

```json
{ "key": "accent", "type": "color", "label": "Couleur d'accent",
  "group": "🎨 Couleurs", "default": "#6d5efc" }
```

Types disponibles : `text`, `textarea`, `number`, `toggle`, `select`, `color`,
`links`, `image`. Les valeurs sont rangées en base, pas dans ce dossier : ce
thème peut donc être remplacé par une version plus récente sans que rien de
réglé ne soit perdu.

## Partager ce thème

Zippez le dossier `{$cle}/` **en gardant le dossier lui-même à la racine de
l'archive**, puis installez-le depuis Administration → {$module} → Thèmes.

MD;
    }

    private function tplThemeCss(): string
    {
        return <<<'CSS'
/* ═══════════════════════════════════════════════════════════════════════
   Thème livré avec le module.

   Volontairement court : c'est un point de départ lisible, pas une charte.
   Les couleurs et l'arrondi viennent des options du thème, injectées en
   variables par header.html — les toucher ici les figerait.
   ═══════════════════════════════════════════════════════════════════════ */

* { box-sizing: border-box; }

body.pg {
  margin: 0;
  background: var(--bg, #0f1115);
  color: var(--ink, #f2f3f7);
  font: 15px/1.6 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
}

.pg-wrap { width: 100%; max-width: 1180px; margin: 0 auto; padding: 0 20px; }
.pg--narrow .pg-wrap { max-width: 860px; }

/* ── En-tête ───────────────────────────────────────────────────────────── */

.pg-top {
  position: sticky; top: 0; z-index: 10;
  border-bottom: 1px solid rgba(255, 255, 255, .08);
  background: color-mix(in srgb, var(--bg, #0f1115) 92%, transparent);
  backdrop-filter: blur(8px);
}

.pg-top__in { display: flex; align-items: center; gap: 16px; height: 62px; }

.pg-brand { display: flex; align-items: center; gap: 10px; color: inherit; text-decoration: none; font-size: 16px; }
.pg-brand__mark { font-size: 22px; }
.pg-tagline { font-size: 12.5px; opacity: .6; }

.pg-main { padding: 40px 20px 60px; }

/* ── Accueil ───────────────────────────────────────────────────────────── */

.pg-hero {
  padding: 44px 32px;
  border: 1px solid rgba(255, 255, 255, .1);
  border-radius: var(--radius, 12px);
  background:
    radial-gradient(120% 140% at 20% 0%, color-mix(in srgb, var(--accent, #6d5efc) 22%, transparent), transparent 62%),
    rgba(255, 255, 255, .03);
}

.pg-hero__kicker {
  display: inline-block; margin-bottom: 14px;
  font-size: 11px; font-weight: 700; letter-spacing: 1.6px; text-transform: uppercase;
  color: var(--accent, #6d5efc);
}

.pg-hero__title { margin: 0; font-size: clamp(28px, 5vw, 46px); line-height: 1.1; letter-spacing: -1px; }
.pg-hero__text { margin: 16px 0 0; max-width: 60ch; opacity: .78; }

.pg-btn {
  display: inline-block; margin-top: 22px; padding: 11px 22px;
  border-radius: var(--radius, 12px);
  background: var(--accent, #6d5efc); color: #fff;
  font-weight: 600; text-decoration: none;
  transition: transform .18s ease, filter .18s ease;
}

.pg-btn:hover { transform: translateY(-1px); filter: brightness(1.08); }

/* ── Cartes ────────────────────────────────────────────────────────────── */

.pg-cards {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;
  margin-top: 26px;
}

.pg-card {
  padding: 22px;
  border: 1px solid rgba(255, 255, 255, .1);
  border-radius: var(--radius, 12px);
  background: rgba(255, 255, 255, .03);
}

.pg-card h2 { margin: 0 0 10px; font-size: 16px; }
.pg-card p  { margin: 0; font-size: 13.5px; opacity: .75; }

.pg-card code {
  padding: 2px 6px; border-radius: 5px;
  background: rgba(255, 255, 255, .08);
  font-size: 12.5px;
}

/* ── Pied de page ──────────────────────────────────────────────────────── */

.pg-foot {
  margin-top: 50px; padding: 30px 0;
  border-top: 1px solid rgba(255, 255, 255, .08);
  text-align: center; font-size: 13px; opacity: .65;
}

.pg-foot p { margin: 0 0 6px; }
.pg-foot__legal { font-size: 12px; opacity: .7; }

/* ── Liste et fiche ─────────────────────────────────────────────────────── */

.pg-head { margin-bottom: 22px; }
.pg-head__title { margin: 0; font-size: clamp(24px, 3.6vw, 34px); letter-spacing: -.6px; }
.pg-head__text { margin: 10px 0 0; max-width: 60ch; opacity: .75; font-size: 14px; }
.pg-head__count { display: inline-block; margin-top: 10px; font-size: 12px; opacity: .6; }

.pg-find { display: flex; gap: 8px; align-items: center; margin-bottom: 20px; max-width: 460px; }

.pg-find__in {
  flex: 1; min-width: 0; height: 40px; padding: 0 13px;
  border: 1px solid rgba(255, 255, 255, .14); border-radius: var(--radius, 12px);
  background: rgba(255, 255, 255, .04); color: inherit; font: inherit; font-size: 14px;
}

.pg-find__in:focus { outline: none; border-color: var(--accent, #6d5efc); }

.pg-find__raz {
  display: grid; place-items: center; flex: none;
  width: 34px; height: 34px; border-radius: var(--radius, 12px);
  border: 1px solid rgba(255, 255, 255, .14);
  color: inherit; text-decoration: none; opacity: .7;
}

.pg-btn--sm { margin: 0; padding: 9px 16px; font-size: 13px; }

.pg-recall { display: flex; flex-wrap: wrap; gap: 10px; align-items: baseline; margin: 0 0 18px; font-size: 13px; opacity: .8; }
.pg-recall a { margin-left: auto; color: var(--accent, #6d5efc); font-size: 12px; }

.pg-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 16px; }
.pg-list--tight { grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 12px; }

.pg-item {
  display: flex; flex-direction: column; min-width: 0; overflow: hidden;
  border: 1px solid rgba(255, 255, 255, .1); border-radius: var(--radius, 12px);
  background: rgba(255, 255, 255, .03);
  transition: transform .2s ease, border-color .2s ease;
}

.pg-item:hover { transform: translateY(-3px); border-color: var(--accent, #6d5efc); }

.pg-item__media {
  position: relative; display: block; aspect-ratio: 16 / 10; overflow: hidden;
  background: rgba(255, 255, 255, .04);
}

/* Absolu, et non « height: 100% » : dans un cadre centré, le pourcentage
   retombe sur le rapport d'aspect du fichier et l'image se déforme. */
.pg-item__media img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; display: block; }
.pg-item__ph { position: absolute; inset: 0; display: grid; place-items: center; font-size: 30px; opacity: .35; }

.pg-item__say { display: flex; flex-direction: column; gap: 7px; padding: 14px; min-width: 0; }
.pg-item__title { font-size: 15px; font-weight: 600; color: inherit; text-decoration: none; }
.pg-item:hover .pg-item__title { color: var(--accent, #6d5efc); }
.pg-item__text { margin: 0; font-size: 13px; line-height: 1.6; opacity: .72; }
.pg-item__meta { margin-top: auto; padding-top: 4px; font-size: 11.5px; opacity: .55; }

.pg-pages { display: flex; flex-wrap: wrap; justify-content: center; gap: 6px; margin-top: 26px; }

.pg-pages a {
  display: grid; place-items: center; min-width: 36px; height: 36px; padding: 0 11px;
  border: 1px solid rgba(255, 255, 255, .12); border-radius: var(--radius, 12px);
  color: inherit; text-decoration: none; font-size: 13px;
}

.pg-pages a.is-on { background: var(--accent, #6d5efc); border-color: transparent; color: #fff; }

.pg-crumb { display: flex; flex-wrap: wrap; gap: 7px; margin-bottom: 18px; font-size: 12px; opacity: .7; }
.pg-crumb a { color: var(--accent, #6d5efc); text-decoration: none; }

.pg-single { max-width: 760px; }
.pg-single__img { width: 100%; border-radius: var(--radius, 12px); margin-bottom: 22px; }
.pg-single__title { margin: 0; font-size: clamp(24px, 4vw, 38px); line-height: 1.15; letter-spacing: -.8px; }
.pg-single__meta { margin: 10px 0 0; font-size: 12.5px; opacity: .6; }
.pg-single__body { margin: 22px 0; font-size: 15px; line-height: 1.8; opacity: .85; }

.pg-more { margin-top: 44px; }
.pg-more__title { font-size: 16px; margin: 0 0 14px; }

.pg-void { grid-column: 1 / -1; padding: 40px 10px; text-align: center; font-size: 14px; opacity: .6; }
.pg-void__emo { display: block; margin-bottom: 12px; font-size: 32px; opacity: .5; }
.pg-none { opacity: .6; font-size: 14px; }

@media (max-width: 620px) {
  .pg-hero { padding: 30px 20px; }
  .pg-main { padding: 26px 16px 44px; }
  .pg-list { grid-template-columns: minmax(0, 1fr); }
}
CSS;
    }
}
