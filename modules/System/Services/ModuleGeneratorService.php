<?php
declare(strict_types=1);

namespace System\Services;

/**
 * ModuleGeneratorService — générateur de squelette de module (scaffolding).
 *
 * Produit un module complet, immédiatement activable et conforme aux
 * conventions Aegis : manifeste + menu, classe BaseModule, routes, contrôleur
 * admin (dashboard + sections), service, schéma install/uninstall, changelog,
 * vues UI maison (.ui-*). Le module généré est posé dans /modules mais n'est
 * pas activé (l'admin l'active ensuite, avec vérification des tables).
 */
class ModuleGeneratorService
{
    /**
     * @param array $in name, display_name, description, author, category, icon, sections (string[]), mega (bool)
     * @return array{success:bool, message:string, module?:string}
     */
    public function generate(array $in): array
    {
        $name = preg_replace('/[^A-Za-z0-9]/', '', (string)($in['name'] ?? ''));
        if ($name === '' || !preg_match('/^[A-Z][A-Za-z0-9]+$/', $name)) {
            return ['success' => false, 'message' => "Nom de module invalide : utilisez du PascalCase (ex. « MonModule »)."];
        }
        $target = ROOT_PATH . '/modules/' . $name;
        if (is_dir($target)) {
            return ['success' => false, 'message' => "Un dossier modules/{$name} existe déjà."];
        }

        $display = trim((string)($in['display_name'] ?? '')) ?: $name;
        $desc    = trim((string)($in['description'] ?? '')) ?: "Module {$display}";
        $author  = trim((string)($in['author'] ?? '')) ?: 'Aegis';
        $category = trim((string)($in['category'] ?? '')) ?: 'Autres';
        $icon    = trim((string)($in['icon'] ?? '')) ?: '🧩';
        $prefix  = strtolower($name);
        $routeBase = '/admin/' . $prefix;

        // Sections (hors Dashboard, toujours présent).
        $rawSections = $in['sections'] ?? [];
        if (is_string($rawSections)) {
            $rawSections = array_filter(array_map('trim', explode(',', $rawSections)));
        }
        $sections = [];
        foreach ($rawSections as $label) {
            $label = trim((string)$label);
            if ($label === '') continue;
            $slug = $this->slug($label);
            if ($slug === '' || $slug === 'dashboard') continue;
            $sections[$slug] = ['label' => $label, 'method' => $this->methodName($slug)];
        }
        $mega = !empty($in['mega']) || count($sections) > 6;

        // Option licence (n'a d'effet que si le module Licenses est installé).
        $license = !empty($in['license']) && is_dir(ROOT_PATH . '/modules/Licenses');
        $licenseProduct = $this->slug((string)($in['license_product'] ?? '')) ?: $prefix;

        // Partie publique : moteur de gabarits + thème livré + écrans de thèmes.
        // C'est ce qui évitait jusqu'ici de recopier à la main le système de
        // thèmes d'un module à l'autre.
        $wantsPublic = !empty($in['public']);

        // Gabarits demandés. Pagination et recherche n'ont de sens qu'avec
        // une liste : le scaffold s'en assure de son côté.
        $templates = [
            'list'       => !empty($in['tpl_list']),
            'pagination' => !empty($in['tpl_pagination']),
            'search'     => !empty($in['tpl_search']),
        ];
        $themeKey    = $this->slug((string) ($in['theme'] ?? '')) ?: 'default';
        $themeName   = trim((string) ($in['theme_name'] ?? '')) ?: ucfirst($themeKey);

        // Capacités transverses cochées (markdown, …). Seules les disponibles sont retenues.
        $capabilities = \Framework\Capabilities\CapabilityRegistry::sanitize((array)($in['capabilities'] ?? []));
        $hasMarkdown  = in_array('markdown', $capabilities, true);
        $hasCache     = in_array('cache', $capabilities, true);
        $hasAi        = in_array('ai', $capabilities, true);
        $hasRgpd      = in_array('rgpd', $capabilities, true);
        $hasRecaptcha = in_array('recaptcha', $capabilities, true);
        $hasAnalytics = in_array('analytics', $capabilities, true);
        $hasSeo       = in_array('seo', $capabilities, true);

        // Une partie publique réclame une adresse : autant refuser tout de suite
        // celles qui ne pourront jamais répondre, plutôt que de livrer un module
        // dont la page visiteur mène à une erreur.
        if ($wantsPublic) {
            // La liste des segments réservés vient du framework ; s'il n'est pas
            // chargé, on ne bloque pas la génération pour autant.
            $reserved = class_exists('\Framework\Services\PublicPrefix')
                ? \Framework\Services\PublicPrefix::RESERVED
                : [];

            if (in_array($prefix, $reserved, true)) {
                return ['success' => false, 'message' => "L'adresse publique « /{$prefix} » est réservée au cœur du CMS. Choisissez un autre nom de module."];
            }
            foreach ($this->declaredPrefixes() as $autre => $pris) {
                if ($pris === $prefix) {
                    return ['success' => false, 'message' => "L'adresse publique « /{$prefix} » est déjà prise par le module {$autre}. Choisissez un autre nom de module."];
                }
            }
        }

        $scaffold = $wantsPublic
            ? new ModulePublicScaffold($name, $display, $prefix, $themeKey, $themeName, $capabilities, $templates)
            : null;

        try {
            $dirs = ['', '/Controllers', '/Services', '/Views/admin', '/database'];
            if ($scaffold !== null) {
                $dirs = array_merge($dirs, $scaffold->directories());
            }
            foreach ($dirs as $d) {
                $p = $target . $d;
                if (!is_dir($p) && !@mkdir($p, 0755, true)) {
                    throw new \RuntimeException("Impossible de créer {$p}.");
                }
            }

            file_put_contents($target . '/module.json',      $this->tplManifest($name, $display, $desc, $author, $category, $icon, $routeBase, $sections, $mega, $license, $licenseProduct, $capabilities, $scaffold));
            file_put_contents($target . '/' . $name . '.php', $this->tplModuleClass($name, $display));
            file_put_contents($target . '/routes.php',        $this->tplRoutes($name, $routeBase, $sections, $scaffold));
            file_put_contents($target . '/Controllers/AdminController.php', $this->tplController($name, $sections, $license));
            file_put_contents($target . '/Services/' . $name . 'Service.php', $this->tplService($name, $prefix, $hasCache, $scaffold));
            // Le scaffold peut remplacer la table d'exemple par une table
            // vraiment listable ; sinon on garde celle du squelette.
            $itemsSql = $scaffold?->itemsTableSql() ?? $this->tplInstallSql($prefix);
            file_put_contents($target . '/database/install.sql',   $itemsSql . ($scaffold?->installSql() ?? ''));
            file_put_contents($target . '/database/uninstall.sql', $this->tplUninstallSql($prefix, $scaffold));
            file_put_contents($target . '/changelog.json',    $this->tplChangelog());
            @mkdir($target . '/database/migrations', 0755, true);

            // Vues
            file_put_contents($target . '/Views/admin/dashboard.php', $this->tplDashboardView($display, $icon, $prefix, $sections, $routeBase, $license, $hasMarkdown, $hasAi, $hasRgpd, $hasRecaptcha, $hasAnalytics, $hasSeo, $wantsPublic, $themeName));
            foreach ($sections as $slug => $s) {
                file_put_contents($target . '/Views/admin/' . $slug . '.php', $this->tplSectionView($display, $s['label'], $routeBase));
            }

            if ($scaffold !== null) {
                foreach ($scaffold->files() as $relatif => $contenu) {
                    file_put_contents($target . $relatif, $contenu);
                }
            }

            return ['success' => true, 'message' => "Module « {$name} » généré dans modules/{$name}. Activez-le depuis la page Modules.", 'module' => $name];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Erreur de génération : ' . $e->getMessage()];
        }
    }

    /**
     * Préfixes publics déjà déclarés par les modules installés.
     *
     * @return array<string,string> module => préfixe
     */
    private function declaredPrefixes(): array
    {
        $out = [];
        foreach (glob(ROOT_PATH . '/modules/*/module.json') ?: [] as $file) {
            $cfg = json_decode((string) file_get_contents($file), true);
            if (!is_array($cfg)) { continue; }
            $p = strtolower(trim((string) ($cfg['public']['prefix'] ?? '')));
            if ($p !== '') { $out[basename(dirname($file))] = $p; }
        }
        return $out;
    }

    private function slug(string $s): string
    {
        // mb_strtolower et non strtolower : ce dernier travaille octet par
        // octet et laisse « É » intact, si bien qu'une section « Éléments »
        // devenait « lements » — la lettre accentuée était simplement perdue.
        $s = mb_strtolower(trim($s), 'UTF-8');

        // Translittération des accents courants (é→e, à→a, ç→c…).
        $from = ['à','á','â','ä','ã','å','è','é','ê','ë','ì','í','î','ï','ò','ó','ô','ö','õ','ù','ú','û','ü','ç','ñ','ý','ÿ','œ','æ'];
        $to   = ['a','a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c','n','y','y','oe','ae'];
        $s = str_replace($from, $to, $s);

        $s = preg_replace('/[^a-z0-9]+/', '-', $s);
        return trim((string) $s, '-');
    }

    private function methodName(string $slug): string
    {
        $parts = explode('-', $slug);
        $m = array_shift($parts);
        foreach ($parts as $p) { $m .= ucfirst($p); }
        return preg_replace('/[^A-Za-z0-9]/', '', $m) ?: 'page';
    }

    // ── Templates ─────────────────────────────────────────────────────────────

    private function tplManifest(string $name, string $display, string $desc, string $author, string $category, string $icon, string $routeBase, array $sections, bool $mega, bool $license = false, string $licenseProduct = '', array $capabilities = [], ?ModulePublicScaffold $scaffold = null): string
    {
        $children = [['label' => 'Tableau de bord', 'icon' => '📊', 'url' => $routeBase . '/dashboard']];
        foreach ($sections as $slug => $s) {
            $children[] = ['label' => $s['label'], 'icon' => '•', 'url' => $routeBase . '/' . $slug];
        }
        // La gestion des thèmes se range en fin de menu, après le métier.
        if ($scaffold !== null) {
            $children[] = $scaffold->menuChild($routeBase);
        }
        $menuItem = [
            'label' => $display, 'icon' => $icon, 'position' => 300, 'match' => $routeBase,
        ];
        if ($mega) { $menuItem['mega'] = true; }
        $menuItem['children'] = $children;

        $manifest = [
            'name' => $name, 'version' => '1.0.0', 'description' => $desc, 'author' => $author,
            'class' => $name . '\\' . $name, 'core' => false, 'category' => $category,
        ];
        if ($license) {
            // Livré en mode « open » (aucun blocage) ; bascule via Licences → Intégration.
            $manifest['license'] = ['product' => $licenseProduct, 'default_mode' => 'open'];
        }
        if ($capabilities) {
            // Capacités transverses câblées au runtime par CapabilityManager.
            $manifest['capabilities'] = array_values($capabilities);
        }
        if ($scaffold !== null) {
            // Déclaration du préfixe public, sous la forme attendue par
            // PublicPrefix : un simple « true » aurait privé le module du
            // renommage d'adresse depuis la page Modules.
            $manifest['public'] = $scaffold->publicManifest();
        }
        $manifest['menu'] = [$menuItem];
        $manifest['requires'] = ['cms_version' => '>=4.0.0', 'php_version' => '>=8.1.0', 'modules' => ['Auth']];
        return json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    }

    private function tplModuleClass(string $name, string $display): string
    {
        return <<<PHP
<?php
declare(strict_types=1);

namespace {$name};

use Framework\\Interfaces\\BaseModule;

/**
 * Module {$display} — généré par le générateur de modules Aegis.
 */
class {$name} extends BaseModule
{
    public function getName(): string { return '{$name}'; }
    public function getDescription(): string { return '{$display}'; }

    // getVersion() n'est pas surchargée : BaseModule lit la version dans
    // module.json, seule source de vérité (une version en dur y diverge).

    public function registerRoutes(\$router): void
    {
        \$register = require __DIR__ . '/routes.php';
        if (is_callable(\$register)) {
            \$register(\$router);
        }
    }

    public function install(): bool
    {
        // Le schéma est exécuté par ModuleManager via database/install.sql.
        return true;
    }

    public function uninstall(): bool
    {
        return true;
    }
}

PHP;
    }

    private function tplRoutes(string $name, string $routeBase, array $sections, ?ModulePublicScaffold $scaffold = null): string
    {
        $lines = "        \$router->get('/dashboard', '{$name}\\\\Controllers\\\\AdminController@dashboard');\n";
        foreach ($sections as $slug => $s) {
            $lines .= "        \$router->get('/{$slug}', '{$name}\\\\Controllers\\\\AdminController@{$s['method']}');\n";
        }
        if ($scaffold !== null) {
            $lines .= "\n        // Thèmes de la partie publique.\n" . $scaffold->adminRoutes();
        }
        // Les routes publiques vivent hors du groupe d'administration.
        $publicLines = $scaffold !== null ? "\n" . $scaffold->publicRoutes() : '';

        return <<<PHP
<?php
/**
 * Routes du module {$name} (générées).
 */
return function (\$router) {
    \$router->group('{$routeBase}', function (\$router) {
{$lines}    });
{$publicLines}};

PHP;
    }

    private function tplController(string $name, array $sections, bool $license = false): string
    {
        // Garde de licence non bloquant : expose $this->licenseState aux vues.
        $licenseProp = $license ? "\n    /** @var array État de licence (non bloquant). */\n    protected array \$licenseState = ['allowed' => true, 'warning' => null];\n" : '';
        $licenseInit = $license ? "\n        // Vérification de licence — ne bloque jamais (cf. LicenseGuard).\n        if (class_exists('\\\\Framework\\\\Services\\\\LicenseGuard')) {\n            \$this->licenseState = \\Framework\\Services\\LicenseGuard::for('{$name}');\n        }\n" : '';
        $methods = '';
        foreach ($sections as $slug => $s) {
            $label = addslashes($s['label']);
            $methods .= <<<PHP

    public function {$s['method']}(): void
    {
        \$this->requireAdmin();
        \$pageTitle = '{$label}';
        \$sectionTitle = '{$label}';
        require __DIR__ . '/../Views/admin/{$slug}.php';
    }

PHP;
        }

        return <<<PHP
<?php
declare(strict_types=1);

namespace {$name}\\Controllers;

use Framework\\Services\\Database;
use Framework\\Security\\CSRFProtection;
use {$name}\\Services\\{$name}Service;

/**
 * Contrôleur d'administration du module {$name} (généré).
 */
class AdminController
{
    private Database \$db;
    private CSRFProtection \$csrf;
    private {$name}Service \$service;
{$licenseProp}
    public function __construct(Database \$db, CSRFProtection \$csrf)
    {
        \$this->db = \$db;
        \$this->csrf = \$csrf;
        \$this->service = new {$name}Service(\$db);
{$licenseInit}    }

    public function dashboard(): void
    {
        \$this->requireAdmin();
        \$pageTitle = 'Tableau de bord';
        \$stats = \$this->service->getStats();
        \$licenseState = \$this->licenseState ?? ['allowed' => true, 'warning' => null];
        \$csrfToken = \$this->csrf->generateToken();
        require __DIR__ . '/../Views/admin/dashboard.php';
    }
{$methods}
    private function requireAdmin(): void
    {
        if (empty(\$_SESSION['logged_in']) || !in_array(\$_SESSION['role'] ?? '', ['admin', 'superadmin'], true)) {
            redirect('/auth/login');
        }
    }
}

PHP;
    }

    private function tplService(string $name, string $prefix, bool $cache = false, ?ModulePublicScaffold $scaffold = null): string
    {
        // Les méthodes de liste vivent dans le service : c'est là que les
        // requêtes doivent être, et c'est ce que le module doit montrer.
        $listMethods = $scaffold?->serviceMethods() ?? '';
        // Capacité Cache : enveloppe la lecture coûteuse dans cache_remember() (60 s).
        // Le helper global cache_remember() est fourni par le framework (repli direct si cache absent).
        $statsBody = $cache
            ? <<<PHP
        // Capacité « cache » : résultat mémorisé 60 s (cache_remember fourni par le framework).
        return cache_remember('{$prefix}_stats', function () {
            \$count = 0;
            try {
                \$row = \$this->db->queryOne("SELECT COUNT(*) AS n FROM {$prefix}_items");
                \$count = (int) (\$row['n'] ?? 0);
            } catch (\\Throwable \$e) {
                // table absente : stats à zéro
            }
            return ['items' => \$count];
        }, 60);
PHP
            : <<<PHP
        \$count = 0;
        try {
            \$row = \$this->db->queryOne("SELECT COUNT(*) AS n FROM {$prefix}_items");
            \$count = (int) (\$row['n'] ?? 0);
        } catch (\\Throwable \$e) {
            // table absente : stats à zéro
        }
        return ['items' => \$count];
PHP;

        return <<<PHP
<?php
declare(strict_types=1);

namespace {$name}\\Services;

use Framework\\Services\\Database;

/**
 * Service métier du module {$name} (généré). Toute la logique d'accès aux
 * données vit ici (requêtes préparées).
 */
class {$name}Service
{
    private Database \$db;

    public function __construct(Database \$db)
    {
        \$this->db = \$db;
    }

    /** @return array<string,int> */
    public function getStats(): array
    {
{$statsBody}
    }
{$listMethods}}

PHP;
    }

    private function tplInstallSql(string $prefix): string
    {
        return <<<SQL
-- Module {$prefix} — Installation (schéma + données par défaut).
-- Exécuté par ModuleManager à l'activation.

CREATE TABLE IF NOT EXISTS `{$prefix}_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(190) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_{$prefix}_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SQL;
    }

    private function tplUninstallSql(string $prefix, ?ModulePublicScaffold $scaffold = null): string
    {
        $extra = $scaffold?->uninstallSql() ?? '';
        return <<<SQL
-- Module {$prefix} — Désinstallation.
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `{$prefix}_items`;
{$extra}SET FOREIGN_KEY_CHECKS = 1;

SQL;
    }

    private function tplChangelog(): string
    {
        $date = date('Y-m-d');
        return <<<JSON
[
  {
    "version": "1.0.0",
    "date": "{$date}",
    "changes": [
      "Version initiale (générée par le générateur de modules Aegis)."
    ]
  }
]

JSON;
    }

    private function tplDashboardView(string $display, string $icon, string $prefix, array $sections, string $routeBase, bool $license = false, bool $markdown = false, bool $ai = false, bool $rgpd = false, bool $recaptcha = false, bool $analytics = false, bool $seo = false, bool $public = false, string $themeName = ''): string
    {
        $links = '';
        foreach ($sections as $slug => $s) {
            $label = htmlspecialchars($s['label'], ENT_QUOTES);
            $links .= "            <a class=\"ui-btn\" href=\"<?= u('{$routeBase}/{$slug}') ?>\">{$label}</a>\n";
        }
        $displayEsc = htmlspecialchars($display, ENT_QUOTES);

        // Capacité Markdown : bloc de démonstration + chargement des assets de l'éditeur.
        // L'éditeur se greffe automatiquement sur tout <textarea data-md>.
        // Côté rendu public/serveur : echo md_render(\$texte); (helper global chargé par CapabilityManager).
        $mdBlock  = '';
        $mdAssets = '';
        if ($markdown) {
            $mdBlock = <<<'MD'

<div class="ui-card" style="margin-bottom:18px">
    <div class="ui-card-head">✍️ Markdown <span class="u-muted" style="font-weight:400;font-size:12px">— capacité activée</span></div>
    <div class="ui-card-body">
        <p class="u-muted" style="margin-top:0">L'éditeur s'active automatiquement sur les <code>&lt;textarea data-md&gt;</code>. Pour afficher du contenu formaté côté public&nbsp;: <code>&lt;?= md_render($texte) ?&gt;</code>.</p>
        <label class="ui-field-label">Exemple d'éditeur</label>
        <textarea data-md rows="6" class="form-control" placeholder="# Titre&#10;**gras**, *italique*, `code`, listes, [lien](https://…)"></textarea>
    </div>
</div>

MD;
            $mdAssets = "<?php if (function_exists('md_editor_assets')) { echo md_editor_assets(); } ?>\n";
        }

        // Capacité IA : carte listant le modèle par défaut + le nombre de modèles actifs.
        // Helpers globaux fournis par le framework (ai_get_default_model, ai_get_models).
        $aiBlock = '';
        if ($ai) {
            $aiBlock = <<<'AI'

<?php if (function_exists('ai_get_models')):
    $aiDefault = function_exists('ai_get_default_model') ? ai_get_default_model() : null;
    $aiModels  = ai_get_models(true);
?>
<div class="ui-card" style="margin-bottom:18px">
    <div class="ui-card-head">🤖 Modèles IA <span class="u-muted" style="font-weight:400;font-size:12px">— capacité activée</span></div>
    <div class="ui-card-body">
        <?php if ($aiModels): ?>
            <p style="margin-top:0">Modèle par défaut&nbsp;:
                <strong><?= htmlspecialchars($aiDefault ? ai_model_display_name($aiDefault) : '—', ENT_QUOTES) ?></strong>
                · <?= count($aiModels) ?> modèle(s) actif(s).</p>
            <p class="u-muted" style="margin-bottom:0;font-size:13px">Utilisez <code>ai_get_default_model()</code> / <code>ai_get_models()</code> dans votre service pour brancher une génération de contenu.</p>
        <?php else: ?>
            <p class="u-muted" style="margin:0">Aucun modèle IA configuré. Ajoutez-en dans <a href="<?= u('/admin/configuration/ai-models') ?>">Configuration → Modèles IA</a>.</p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

AI;
        }

        // Capacités « usage » (RGPD, reCAPTCHA) : pas de code auto sur ce dashboard admin
        // (elles servent les pages publiques). On rappelle le mode d'emploi + le pilotage central.
        $capNotesBlock = '';
        $notes = [];
        // Avec une partie publique, ces briques sont posées par le thème :
        // le contrôleur public les rend via CapabilityOutput, le gabarit les
        // affiche. Sans partie publique il n'y a aucune page où les poser —
        // et c'est très exactement pourquoi cocher « RGPD » n'affichait rien.
        $pose = $public
            ? "posé par le thème, en pied de page"
            : "à poser vous-même : ce module n'a pas de partie publique";
        $poseHead = $public
            ? "posé par le thème, dans l'en-tête"
            : "à poser vous-même : ce module n'a pas de partie publique";

        if ($rgpd) {
            $notes[] = "<li>🍪 <strong>RGPD / Cookies</strong> — bandeau de consentement, {$pose}. Réglages&nbsp;: <a href=\"<?= u('/admin/configuration/rgpd') ?>\">Configuration → RGPD</a>.</li>";
        }
        if ($recaptcha) {
            $notes[] = "<li>🛡️ <strong>reCAPTCHA</strong> — <code>recaptcha_script()</code> dans le &lt;head&gt;, <code>recaptcha_form_handler('formId','zone')</code> sous le formulaire, puis <code>recaptcha_verify(\$_POST['recaptcha_token'] ?? '', 'zone')</code> côté serveur. Réglages&nbsp;: <a href=\"<?= u('/admin/configuration') ?>\">Configuration → Général</a>.</li>";
        }
        if ($analytics) {
            $notes[] = "<li>📊 <strong>Analytics</strong> — <code>&lt;?= analytics_head() ?&gt;</code> dans le &lt;head&gt; de vos pages publiques (vide si le module Analytics est absent/désactivé). Statistiques&nbsp;: <a href=\"<?= u('/admin/analytics/dashboard') ?>\">Administration → Analytics</a>.</li>";
        }
        if ($seo) {
            $notes[] = "<li>🔎 <strong>SEO</strong> — <code>&lt;?= seo_head(['title' =&gt; \$titre, 'description' =&gt; \$desc, 'image' =&gt; \$img]) ?&gt;</code> dans le &lt;head&gt; (title/description/canonical/Open Graph/Twitter). Défauts site&nbsp;: <a href=\"<?= u('/admin/configuration/seo') ?>\">Configuration → SEO &amp; médias</a>.</li>";
        }
        if ($notes) {
            $items = implode("\n            ", $notes);
            $capNotesBlock = <<<CN

<div class="ui-card" style="margin-bottom:18px">
    <div class="ui-card-head">🧩 Capacités actives</div>
    <div class="ui-card-body">
        <p class="u-muted" style="margin-top:0">Ces briques sont mutualisées par le framework et <strong>pilotées depuis Configuration</strong> — aucun réglage à maintenir dans ce module.</p>
        <ul style="margin:0;padding-left:18px;line-height:1.9;font-size:13px">
            {$items}
        </ul>
    </div>
</div>

CN;
        }

        // Carte d'accueil vers la partie publique et ses thèmes.
        $publicBlock = '';
        if ($public) {
            $themeEsc = htmlspecialchars($themeName, ENT_QUOTES);
            $publicBlock = <<<PB

<div class="ui-card" style="margin-bottom:18px">
    <div class="ui-card-head">🎨 Partie publique</div>
    <div class="ui-card-body">
        <p style="margin-top:0">Ce module rend ses pages publiques à partir de gabarits HTML — <strong>aucun PHP dans les thèmes</strong>.
        Le thème livré s'appelle <strong>{$themeEsc}</strong> ; il sert aussi de repli aux thèmes incomplets.</p>
        <div class="u-flex u-gap" style="flex-wrap:wrap">
            <a class="ui-btn primary" href="<?= u('{$routeBase}/themes') ?>">🎨 Gérer les thèmes</a>
            <a class="ui-btn" href="<?= u('/{$prefix}') ?>" target="_blank" rel="noopener">↗ Voir la page publique</a>
        </div>
    </div>
</div>

PB;
        }

        $licenseBanner = $license
            ? "<?php \$ls = \$licenseState ?? ['warning' => null]; if (!empty(\$ls['warning'])): ?>\n<div class=\"ui-card\" style=\"border-color:var(--amber);margin-bottom:14px\"><div class=\"ui-card-body\" style=\"color:var(--amber)\">🔑 <?= htmlspecialchars(\$ls['warning'], ENT_QUOTES) ?></div></div>\n<?php endif; ?>\n"
            : '';
        return <<<PHP
<?php
/** Tableau de bord {$displayEsc} (généré). */
if (!defined('AEGIS_FRAMEWORK')) die('Access denied');
admin_header('{$displayEsc} — Tableau de bord');
\$stats = \$stats ?? ['items' => 0];
?>
<div class="adm-page-head">
    <div class="adm-breadcrumb"><a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><span>{$displayEsc}</span></div>
    <h1>{$icon} {$displayEsc}</h1>
    <p>Tableau de bord du module.</p>
</div>
{$licenseBanner}

<div class="ui-grid cols-4" style="margin-bottom:18px">
    <div class="ui-card tone-accent"><div class="ui-kpi"><div class="ui-kpi-icon">{$icon}</div><div><p class="ui-kpi-label">Éléments</p><div class="ui-kpi-value"><?= (int)(\$stats['items'] ?? 0) ?></div></div></div></div>
</div>
{$publicBlock}{$aiBlock}{$capNotesBlock}{$mdBlock}
<div class="ui-card">
    <div class="ui-card-head">⚡ Accès rapides</div>
    <div class="ui-card-body">
        <div class="u-flex u-gap" style="flex-wrap:wrap">
{$links}        </div>
    </div>
</div>
{$mdAssets}<?php admin_footer(); ?>

PHP;
    }

    private function tplSectionView(string $display, string $label, string $routeBase): string
    {
        $labelEsc = htmlspecialchars($label, ENT_QUOTES);
        $displayEsc = htmlspecialchars($display, ENT_QUOTES);
        return <<<PHP
<?php
/** Page « {$labelEsc} » de {$displayEsc} (générée — à compléter). */
if (!defined('AEGIS_FRAMEWORK')) die('Access denied');
admin_header('{$labelEsc}');
?>
<div class="adm-page-head">
    <div class="adm-breadcrumb"><a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><a href="<?= u('{$routeBase}/dashboard') ?>">{$displayEsc}</a><span>/</span><span>{$labelEsc}</span></div>
    <h1>{$labelEsc}</h1>
</div>
<div class="ui-card"><div class="ui-card-body">
    <p class="u-muted">Page « {$labelEsc} » générée automatiquement. À vous de la compléter 🙂</p>
</div></div>
<?php admin_footer(); ?>

PHP;
    }
}
