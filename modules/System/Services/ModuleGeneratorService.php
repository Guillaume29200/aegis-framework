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

        try {
            $dirs = ['', '/Controllers', '/Services', '/Views/admin', '/database'];
            foreach ($dirs as $d) {
                $p = $target . $d;
                if (!is_dir($p) && !@mkdir($p, 0755, true)) {
                    throw new \RuntimeException("Impossible de créer {$p}.");
                }
            }

            file_put_contents($target . '/module.json',      $this->tplManifest($name, $display, $desc, $author, $category, $icon, $routeBase, $sections, $mega, $license, $licenseProduct));
            file_put_contents($target . '/' . $name . '.php', $this->tplModuleClass($name, $display));
            file_put_contents($target . '/routes.php',        $this->tplRoutes($name, $routeBase, $sections));
            file_put_contents($target . '/Controllers/AdminController.php', $this->tplController($name, $sections, $license));
            file_put_contents($target . '/Services/' . $name . 'Service.php', $this->tplService($name, $prefix));
            file_put_contents($target . '/database/install.sql',   $this->tplInstallSql($prefix));
            file_put_contents($target . '/database/uninstall.sql', $this->tplUninstallSql($prefix));
            file_put_contents($target . '/changelog.json',    $this->tplChangelog());
            @mkdir($target . '/database/migrations', 0755, true);

            // Vues
            file_put_contents($target . '/Views/admin/dashboard.php', $this->tplDashboardView($display, $icon, $prefix, $sections, $routeBase, $license));
            foreach ($sections as $slug => $s) {
                file_put_contents($target . '/Views/admin/' . $slug . '.php', $this->tplSectionView($display, $s['label'], $routeBase));
            }

            return ['success' => true, 'message' => "Module « {$name} » généré dans modules/{$name}. Activez-le depuis la page Modules.", 'module' => $name];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Erreur de génération : ' . $e->getMessage()];
        }
    }

    private function slug(string $s): string
    {
        $s = strtolower(trim($s));
        // Translittération des accents courants (é→e, à→a, ç→c…).
        $from = ['à','á','â','ä','ã','å','è','é','ê','ë','ì','í','î','ï','ò','ó','ô','ö','õ','ù','ú','û','ü','ç','ñ'];
        $to   = ['a','a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c','n'];
        $s = str_replace($from, $to, $s);
        $s = preg_replace('/[^a-z0-9]+/', '-', $s);
        return trim((string)$s, '-');
    }

    private function methodName(string $slug): string
    {
        $parts = explode('-', $slug);
        $m = array_shift($parts);
        foreach ($parts as $p) { $m .= ucfirst($p); }
        return preg_replace('/[^A-Za-z0-9]/', '', $m) ?: 'page';
    }

    // ── Templates ─────────────────────────────────────────────────────────────

    private function tplManifest(string $name, string $display, string $desc, string $author, string $category, string $icon, string $routeBase, array $sections, bool $mega, bool $license = false, string $licenseProduct = ''): string
    {
        $children = [['label' => 'Tableau de bord', 'icon' => '📊', 'url' => $routeBase . '/dashboard']];
        foreach ($sections as $slug => $s) {
            $children[] = ['label' => $s['label'], 'icon' => '•', 'url' => $routeBase . '/' . $slug];
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
    public function getVersion(): string { return '1.0.0'; }
    public function getDescription(): string { return '{$display}'; }

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

    private function tplRoutes(string $name, string $routeBase, array $sections): string
    {
        $lines = "        \$router->get('/dashboard', '{$name}\\\\Controllers\\\\AdminController@dashboard');\n";
        foreach ($sections as $slug => $s) {
            $lines .= "        \$router->get('/{$slug}', '{$name}\\\\Controllers\\\\AdminController@{$s['method']}');\n";
        }
        return <<<PHP
<?php
/**
 * Routes du module {$name} (générées).
 */
return function (\$router) {
    \$router->group('{$routeBase}', function (\$router) {
{$lines}    });
};

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

    private function tplService(string $name, string $prefix): string
    {
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
        \$count = 0;
        try {
            \$row = \$this->db->queryOne("SELECT COUNT(*) AS n FROM {$prefix}_items");
            \$count = (int) (\$row['n'] ?? 0);
        } catch (\\Throwable \$e) {
            // table absente : stats à zéro
        }
        return ['items' => \$count];
    }
}

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

    private function tplUninstallSql(string $prefix): string
    {
        return <<<SQL
-- Module {$prefix} — Désinstallation.
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `{$prefix}_items`;
SET FOREIGN_KEY_CHECKS = 1;

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

    private function tplDashboardView(string $display, string $icon, string $prefix, array $sections, string $routeBase, bool $license = false): string
    {
        $links = '';
        foreach ($sections as $slug => $s) {
            $label = htmlspecialchars($s['label'], ENT_QUOTES);
            $links .= "            <a class=\"ui-btn\" href=\"<?= u('{$routeBase}/{$slug}') ?>\">{$label}</a>\n";
        }
        $displayEsc = htmlspecialchars($display, ENT_QUOTES);
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

<div class="ui-card">
    <div class="ui-card-head">⚡ Accès rapides</div>
    <div class="ui-card-body">
        <div class="u-flex u-gap" style="flex-wrap:wrap">
{$links}        </div>
    </div>
</div>
<?php admin_footer(); ?>

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
