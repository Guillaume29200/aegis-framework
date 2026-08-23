<?php
/**
 * documentation/doc-module.php — Créer un module Aegis (guide complet via MonModule).
 */
$docPage = 'framework/doc-module.php';
$seo = [
    'title'     => 'Créer un module — Documentation · GameNodePanel',
    'desc'      => "Guide complet pour créer un module Aegis Framework : structure, module.json, classe BaseModule, routes, contrôleur, service, vues admin et SQL — illustré par le module d'exemple MonModule.",
    'canonical' => 'https://gamenodepanel.com/documentation/doc-module.php',
];
require __DIR__ . '/../inc/head.php';

/* Extraits de code réels du module d'exemple MonModule (échappés à l'affichage). */
$code_manifest = <<<'JSON'
{
    "name": "MonModule",
    "version": "1.0.0",
    "description": "Module d'exemple pour développement",
    "author": "Lavela Studio",
    "class": "MonModule\\MonModule",
    "core": false,
    "category": "Autres",
    "menu": [
        {
            "label": "Mon premier module",
            "icon": "🧩",
            "position": 300,
            "match": "/admin/monmodule",
            "children": [
                { "label": "Tableau de bord", "icon": "📊", "url": "/admin/monmodule/dashboard" },
                { "label": "Accueil",        "icon": "•",  "url": "/admin/monmodule/accueil" },
                { "label": "menu2",          "icon": "•",  "url": "/admin/monmodule/menu2" },
                { "label": "menu3",          "icon": "•",  "url": "/admin/monmodule/menu3" }
            ]
        }
    ],
    "requires": {
        "cms_version": ">=4.0.0",
        "php_version": ">=8.1.0",
        "modules": ["Auth"]
    }
}
JSON;

$code_class = <<<'PHP'
<?php
declare(strict_types=1);

namespace MonModule;

use Framework\Interfaces\BaseModule;

class MonModule extends BaseModule
{
    public function getName(): string        { return 'MonModule'; }
    public function getDescription(): string { return 'Mon premier module'; }

    // Pas de getVersion() : BaseModule lit la version dans module.json,
    // seule source de vérité (une version en dur finit par diverger).

    public function registerRoutes($router): void
    {
        $register = require __DIR__ . '/routes.php';
        if (is_callable($register)) {
            $register($router);
        }
    }

    public function install(): bool   { return true; } // schéma exécuté via database/install.sql
    public function uninstall(): bool { return true; }
}
PHP;

$code_routes = <<<'PHP'
<?php
// routes.php — retourne une closure qui enregistre les routes du module.
return function ($router) {
    $router->group('/admin/monmodule', function ($router) {
        $router->get('/dashboard', 'MonModule\\Controllers\\AdminController@dashboard');
        $router->get('/accueil',   'MonModule\\Controllers\\AdminController@accueil');
        $router->get('/menu2',     'MonModule\\Controllers\\AdminController@menu2');
        $router->get('/menu3',     'MonModule\\Controllers\\AdminController@menu3');
    });
};
PHP;

$code_controller = <<<'PHP'
<?php
declare(strict_types=1);

namespace MonModule\Controllers;

use Framework\Services\Database;
use Framework\Security\CSRFProtection;
use MonModule\Services\MonModuleService;

class AdminController
{
    private Database $db;
    private CSRFProtection $csrf;
    private MonModuleService $service;

    // Les dépendances sont injectées automatiquement par le routeur.
    public function __construct(Database $db, CSRFProtection $csrf)
    {
        $this->db = $db;
        $this->csrf = $csrf;
        $this->service = new MonModuleService($db);
    }

    public function dashboard(): void
    {
        $this->requireAdmin();
        $pageTitle = 'Tableau de bord';
        $stats     = $this->service->getStats();
        $csrfToken = $this->csrf->generateToken();
        require __DIR__ . '/../Views/admin/dashboard.php';
    }

    private function requireAdmin(): void
    {
        if (empty($_SESSION['logged_in']) ||
            !in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'], true)) {
            redirect('/auth/login');
        }
    }
}
PHP;

$code_service = <<<'PHP'
<?php
declare(strict_types=1);

namespace MonModule\Services;

use Framework\Services\Database;

// Toute la logique d'accès aux données vit ici (requêtes préparées).
class MonModuleService
{
    public function __construct(private Database $db) {}

    /** @return array<string,int> */
    public function getStats(): array
    {
        $count = 0;
        try {
            $row = $this->db->queryOne("SELECT COUNT(*) AS n FROM monmodule_items");
            $count = (int) ($row['n'] ?? 0);
        } catch (\Throwable $e) {
            // table absente : stats à zéro
        }
        return ['items' => $count];
    }
}
PHP;

$code_view = <<<'PHP'
<?php
/** Vue admin — protégée + helpers de thème, design system .ui-* */
if (!defined('AEGIS_FRAMEWORK')) die('Access denied');
admin_header('Mon premier module — Tableau de bord');
$stats = $stats ?? ['items' => 0];
?>
<div class="adm-page-head">
    <div class="adm-breadcrumb">
        <a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><span>Mon premier module</span>
    </div>
    <h1>🧩 Mon premier module</h1>
    <p>Tableau de bord du module.</p>
</div>

<div class="ui-grid cols-4" style="margin-bottom:18px">
    <div class="ui-card tone-accent"><div class="ui-kpi">
        <div class="ui-kpi-icon">🧩</div>
        <div><p class="ui-kpi-label">Éléments</p>
             <div class="ui-kpi-value"><?= (int)($stats['items'] ?? 0) ?></div></div>
    </div></div>
</div>
<?php admin_footer(); ?>
PHP;

$code_sql = <<<'SQL'
-- database/install.sql — exécuté par ModuleManager à l'activation.
CREATE TABLE IF NOT EXISTS `monmodule_items` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`      VARCHAR(190) NOT NULL,
    `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_monmodule_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

$code_uninstall = <<<'SQL'
-- database/uninstall.sql — exécuté à la désinstallation (si présent).
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `monmodule_items`;
SET FOREIGN_KEY_CHECKS = 1;
SQL;

$code_changelog = <<<'JSON'
[
  {
    "version": "1.0.0",
    "date": "2026-06-04",
    "changes": [
      "Version initiale (générée par le générateur de modules Aegis)."
    ]
  }
]
JSON;
?>

    <h1>Créer un module</h1>
    <p class="doc-lead">Tout dans Aegis (et donc dans GameNodePanel) est un module. Voici comment en créer un de A à Z, illustré par le module d'exemple <strong>MonModule</strong> fourni avec la doc.</p>
    <div class="doc-meta">
      <span class="doc-pill">MonModule v1.0.0</span>
      <span class="doc-pill">namespace MonModule\\</span>
      <span class="doc-pill">requires: Auth</span>
    </div>

    <h2 id="mod-intro">Créer un module</h2>
    <p>Un module Aegis est un dossier autonome déposé dans <code>modules/</code>. Il déclare ses métadonnées, ses routes et son menu, puis fournit ses contrôleurs, services et vues. À l'activation, son schéma SQL est exécuté ; à la désactivation, son nettoyage optionnel l'est aussi.</p>
    <div class="callout"><span class="i">🎯</span><div>Règle d'or Aegis : <strong>1 contrôleur + 1 service par fonctionnalité</strong>, menus en JSON, sorties échappées, SQL préparé.</div></div>

    <h2 id="mod-generator">Le générateur de modules</h2>
    <p>Aegis fournit un <strong>générateur de modules</strong> (<em>Administration → Modules → Générateur</em>) : il crée l'ossature complète — <code>module.json</code>, classe principale, <code>routes.php</code>, contrôleurs, services, vues, thème public et fichiers SQL. <strong>MonModule</strong> a été généré ainsi. Vous pouvez aussi tout créer à la main en suivant la structure ci-dessous.</p>
    <p>Le formulaire tient en quatre blocs.</p>

    <h3>1 · Identité</h3>
    <table class="doc-table">
      <tr><th>Champ</th><th>Rôle</th></tr>
      <tr><td>Nom technique</td><td>Le nom du dossier et du namespace (<code>MonModule</code>).</td></tr>
      <tr><td>Nom affiché, icône</td><td>Ce que verra l'administrateur dans le menu.</td></tr>
      <tr><td>Description, auteur, licence</td><td>Métadonnées du manifeste.</td></tr>
      <tr><td>Catégorie</td><td>Regroupement dans la liste des modules.</td></tr>
      <tr><td>Sections</td><td>Une ligne par page d'administration à créer. Chaque section produit une route, une méthode de contrôleur et une vue.</td></tr>
      <tr><td>Méga-menu</td><td>Affiche les sections en menu large plutôt qu'en liste.</td></tr>
    </table>
    <div class="callout"><span class="i">🔤</span><div>Les accents sont gérés : une section nommée <strong>« Éléments »</strong> produit bien l'URL <code>/elements</code>, et non <code>/lements</code>.</div></div>

    <h3>2 · Fonctionnalités</h3>
    <p>Huit briques transverses à cocher — Markdown, Cache, IA, SEO, RGPD/Cookies, Analytics, TurboNav, reCAPTCHA. Elles sont écrites dans <code>module.json</code> et <strong>réellement câblées</strong> dans le code produit.</p>
    <div class="callout"><span class="i">🧱</span><div>Chacune est détaillée dans <a href="framework/doc-capabilities.php">Fonctionnalités &amp; capacités</a>. À retenir ici : <strong>TurboNav</strong> doit en plus être activé dans <em>Administration → Configuration</em> pour s'éveiller.</div></div>

    <h3 id="mod-gen-public">3 · Moteur de templates</h3>
    <p>C'est le bloc qui décide si votre module aura une <strong>partie visiteur</strong>. En cochant « Ce module disposera-t-il d'une partie publique ? », le générateur produit, en plus de l'administration :</p>
    <ul>
      <li>un <strong>thème complet</strong> dans <code>themes/&lt;clé&gt;/</code> — dont vous choisissez la clé et le nom affiché ;</li>
      <li>les quatre dossiers d'assets (<code>css</code>, <code>js</code>, <code>images</code>, <code>uploads</code>), <strong>toujours</strong>, même vides ;</li>
      <li>un <code>PublicController</code> qui prépare les données et rend les gabarits ;</li>
      <li>l'administration des thèmes : choix du thème actif, téléversement par ZIP, suppression, et l'écran d'options généré depuis <code>meta.json</code> ;</li>
      <li>un préfixe d'URL public déclaré dans le manifeste, que l'administrateur pourra renommer.</li>
    </ul>
    <div class="callout warn"><span class="i">🚧</span><div>Le préfixe public est <strong>vérifié à la génération</strong> : un préfixe réservé par le framework (comme <code>/themes</code>) ou déjà pris par un autre module est refusé, en nommant le module fautif. Vous ne pouvez pas générer deux sites sur la même adresse.</div></div>

    <h3>4 · Gabarits à générer</h3>
    <p>Sous-bloc du précédent : il décide de ce que le thème sait afficher au-delà de l'accueil.</p>
    <table class="doc-table">
      <tr><th>Option</th><th>Ce qui est produit</th></tr>
      <tr><td>Liste + fiche</td><td><code>list.html</code> et <code>item.html</code>, la table SQL des éléments, et les méthodes de service correspondantes.</td></tr>
      <tr><td>Avec pagination</td><td>Le comptage, le découpage en pages et la navigation dans le gabarit de liste.</td></tr>
      <tr><td>Avec recherche</td><td>Un champ de recherche, le filtrage SQL et le cas « aucun résultat ».</td></tr>
    </table>
    <div class="callout ok"><span class="i">🎓</span><div>Pour voir le résultat de <strong>toutes</strong> les cases cochées, ouvrez le module <strong>Exemple</strong> livré avec le framework : il a été produit par ce générateur, puis complété là où celui-ci laisse volontairement des pages vides.</div></div>

    <h3>Ce que le générateur ne fait pas</h3>
    <p>Il ne produit <strong>aucune logique métier</strong>, et aucun formulaire de création ou de suppression. Il produit un point de départ qui s'affiche correctement, se sécurise correctement et se désinstalle proprement. La chair, c'est votre métier.</p>

    <h2 id="mod-structure">Structure d'un module</h2>
    <div class="tree">MonModule/
├── module.json                  # manifeste (métadonnées, menu, requires)
├── MonModule.php                # classe principale (extends BaseModule)
├── routes.php                   # closure d'enregistrement des routes
├── changelog.json               # historique des versions
├── Controllers/
│   └── AdminController.php       # contrôleur d'administration
├── Services/
│   └── MonModuleService.php      # logique métier (accès données)
├── Views/
│   └── admin/
│       ├── dashboard.php
│       ├── accueil.php
│       ├── menu2.php
│       └── menu3.php
└── database/
    ├── install.sql              # schéma (activation)
    └── uninstall.sql            # nettoyage (désactivation)</div>

    <p>Si le module a une <strong>partie publique</strong>, le générateur ajoute à cette ossature :</p>
    <div class="tree">MonModule/
├── Controllers/
│   ├── PublicController.php      # les pages visiteur
│   └── ThemeAdminController.php  # choix, ZIP et options des thèmes
├── Services/
│   └── MonModuleThemes.php       # branchement sur ThemeManager (≈ 10 lignes)
├── Views/admin/
│   ├── themes.php                # liste des thèmes + téléversement
│   └── theme-options.php         # options générées depuis meta.json
└── themes/
    └── default/                  # le thème livré — voir Moteur de templates
        ├── meta.json
        ├── header.html · footer.html · home.html
        └── assets/{css,js,images,uploads}/</div>
    <div class="callout"><span class="i">🖼️</span><div>La structure d'un thème et la syntaxe des gabarits sont détaillées dans <a href="framework/doc-templating.php">Moteur de templates &amp; thèmes publics</a>.</div></div>

    <h2 id="mod-manifest">Le manifeste — <code>module.json</code></h2>
    <p>C'est la carte d'identité du module : nom, version, classe, catégorie, <strong>menu (JSON)</strong> et dépendances (<code>requires</code>).</p>
    <pre><code><?= $h($code_manifest) ?></code></pre>
    <ul>
      <li><code>class</code> : le FQCN de la classe principale (avec namespace échappé).</li>
      <li><code>core</code> : <code>false</code> = module désactivable (les modules cœur sont à <code>true</code>).</li>
      <li><code>menu</code> : structure du menu admin, avec <code>match</code> (surlignage) et <code>children</code>.</li>
      <li><code>requires</code> : version min du CMS, de PHP, et modules requis (ici <code>Auth</code>).</li>
      <li><code>capabilities</code> <em>(optionnel)</em> : les <strong>briques transverses</strong> utilisées par le module. Voir <a href="framework/doc-capabilities.php">Fonctionnalités &amp; capacités</a>.</li>
      <li><code>public</code> <em>(optionnel)</em> : le <strong>préfixe d'URL</strong> de la partie visiteur. Voir ci-dessous.</li>
      <li><code>public_home</code> <em>(optionnel)</em> : déclare l'<strong>interface publique</strong> du module (page d'accueil côté visiteur). Voir ci-dessous.</li>
    </ul>

    <h3 id="mod-manifest-public">Le préfixe public</h3>
    <p>Un module qui a une partie visiteur déclare l'adresse à laquelle elle répond :</p>
    <pre><code><?= $h(<<<'JSON'
"capabilities": ["seo", "rgpd", "analytics", "turbonav"],
"public": {
    "prefix": "monmodule",
    "label": "Site public",
    "hint": "Adresse de la partie visiteur de MonModule."
}
JSON) ?></code></pre>
    <ul>
      <li><code>prefix</code> : le segment d'URL (<code>/monmodule</code>). Il est <strong>renommable</strong> par l'administrateur sans toucher au code.</li>
      <li><code>label</code> / <code>hint</code> : ce qui l'accompagne dans l'écran de configuration.</li>
    </ul>
    <div class="callout"><span class="i">🔀</span><div>Ne confondez pas <code>public</code> et <code>public_home</code> : le premier dit <strong>où</strong> vit le site du module, le second le propose comme <strong>page d'accueil du domaine</strong>. Un module peut déclarer l'un, l'autre, ou les deux.</div></div>

    <h2 id="mod-public-home">Interface publique &amp; page d'accueil par défaut</h2>
    <p>Un module qui propose une <strong>interface côté visiteur</strong> (un site public, pas seulement de l'admin) peut le déclarer dans son <code>module.json</code> via la clé <code>public_home</code> :</p>
    <pre><code><?= $h('"public_home": { "url": "/monmodule", "label": "Mon Module — accueil public" }') ?></code></pre>
    <p>Cela permet à l'administrateur de choisir, dans <strong>Administration → Configuration → Général → « Page d'accueil par défaut »</strong>, vers quelle interface publique rediriger les visiteurs arrivant sur la racine du site (<code>/</code>). La liste ne propose que les <strong>modules actifs déclarant <code>public_home</code></strong> (ex. FPSMeter → <code>/fpsmeter</code>, Forum → <code>/forum</code>, Marketplace → <code>/marketplace</code>).</p>
    <div class="callout"><span class="i">🏠</span><div>Un même socle Aegis peut ainsi alimenter <strong>plusieurs sites différents</strong> (un site forum, un site marketplace, un site FPSMeter…) : il suffit de choisir la page d'accueil publique. Si aucun module public n'est disponible (Aegis fraîchement installé) ou si l'on choisit <strong>« Page de connexion (Auth) »</strong>, la racine redirige automatiquement vers <code>/auth/login</code> — comportement par défaut.</div></div>

    <h2 id="mod-class">La classe du module</h2>
    <p>Elle étend <code>BaseModule</code> et enregistre les routes. Les hooks <code>install()</code> / <code>uninstall()</code> permettent d'exécuter du PHP au moment de l'activation/désactivation (le SQL, lui, est géré automatiquement).</p>
    <pre><code><?= $h($code_class) ?></code></pre>

    <h2 id="mod-routes">Les routes</h2>
    <p><code>routes.php</code> retourne une <strong>closure</strong> qui reçoit le routeur. On groupe sous un préfixe et on mappe chaque URL vers <code>Contrôleur@méthode</code>.</p>
    <pre><code><?= $h($code_routes) ?></code></pre>

    <h2 id="mod-controller">Le contrôleur</h2>
    <p>Le contrôleur reçoit ses dépendances par <strong>injection</strong> (ici <code>Database</code> et <code>CSRFProtection</code>), délègue la logique au service, protège l'accès, puis rend une vue.</p>
    <pre><code><?= $h($code_controller) ?></code></pre>
    <div class="callout"><span class="i">🔒</span><div><code>requireAdmin()</code> illustre le contrôle d'accès. En complément, le garde CSRF global protège toutes les requêtes mutantes.</div></div>

    <h2 id="mod-service">Le service</h2>
    <p>Toute la logique d'accès aux données vit dans le service, en <strong>requêtes préparées</strong>. Le contrôleur reste mince.</p>
    <pre><code><?= $h($code_service) ?></code></pre>

    <h2 id="mod-views">Les vues admin</h2>
    <p>Les vues utilisent les <strong>helpers de thème</strong> (<code>admin_header()</code> / <code>admin_footer()</code>) et le design system <code>.ui-*</code> — jamais de Bootstrap ni d'include de layout codé en dur. Elles sont protégées par le garde <code>AEGIS_FRAMEWORK</code>.</p>
    <pre><code><?= $h($code_view) ?></code></pre>

    <h2 id="mod-sql">SQL — install / uninstall</h2>
    <p><code>database/install.sql</code> crée les tables à l'activation. <code>database/uninstall.sql</code> (optionnel) nettoie à la désactivation — <strong>sans ce fichier, les données sont préservées</strong>.</p>
    <pre><code><?= $h($code_sql) ?></code></pre>
    <pre><code><?= $h($code_uninstall) ?></code></pre>

    <h2 id="mod-changelog">Changelog</h2>
    <p>Chaque module tient son <code>changelog.json</code> — pratique pour suivre les versions et les évolutions.</p>
    <pre><code><?= $h($code_changelog) ?></code></pre>

    <h2 id="mod-install">Installer le module</h2>
    <ol class="steps">
      <li>Déposez le dossier du module dans <code>modules/</code> (ou importez un <strong>ZIP</strong> depuis l'admin).</li>
      <li>Activez-le : le schéma <code>install.sql</code> s'exécute et le hook <code>install()</code> est appelé — de façon <strong>atomique</strong>, avec vérification des tables.</li>
      <li>Le menu déclaré dans <code>module.json</code> apparaît automatiquement dans l'administration.</li>
      <li>Pour désactiver : <code>uninstall()</code> est appelé, et <code>uninstall.sql</code> (s'il existe) nettoie.</li>
    </ol>
    <div class="callout ok"><span class="i">✅</span><div>C'est tout : pas de configuration centrale à éditer. Le routeur, le menu et la base se câblent tout seuls à partir du module.</div></div>

    <div class="doc-foot">
      <span>Guide module · basé sur l'exemple MonModule</span>
      <span><a href="<?= $h($github) ?>" target="_blank" rel="noopener">Dépôt GitHub ↗</a></span>
    </div>

<?php require __DIR__ . '/../inc/foot.php'; ?>
