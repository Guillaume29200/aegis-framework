<?php
/**
 * documentation/doc-aegis.php — Présentation d'Aegis Framework (le socle).
 * (Extrait de l'ancien accueil ; l'accueil est devenu un hub multi-modules.)
 */
$docPage = 'framework/doc-aegis.php';
$seo = [
    'title'     => 'Aegis Framework — Présentation · Documentation',
    'desc'      => "Aegis Framework (PHP 8.5+, modulaire, sécurité by design) : le socle open-source et gratuit de GameNodePanel. Installation, architecture, modules, routing, sécurité.",
    'canonical' => 'https://gamenodepanel.com/documentation/doc-aegis.php',
];
require __DIR__ . '/../inc/head.php';
?>

    <h1>Aegis Framework</h1>
    <p class="doc-lead">GameNodePanel et tous les modules officiels sont bâtis sur <strong>Aegis Framework</strong>, un <strong>socle PHP modulaire</strong> open-source et gratuit — un framework léger doublé d'une plateforme à modules. Cette section documente le framework lui-même — son socle, son architecture et sa sécurité.</p>
    <div class="doc-meta">
      <span class="doc-pill">Aegis Framework v4.0.0</span>
      <span class="doc-pill">PHP ≥ 8.5</span>
      <span class="doc-pill">MySQL / MariaDB</span>
      <span class="doc-pill">Open-source</span>
    </div>

    <div class="callout">
      <span class="i">📦</span>
      <div><strong>Aegis Framework est gratuit et open-source.</strong> Récupérez-le, étudiez-le ou contribuez sur GitHub :
      <a href="<?= $h($github) ?>" target="_blank" rel="noopener"><?= $h($github) ?></a>.</div>
    </div>

    <h2 id="intro">Présentation</h2>
    <p>Aegis Framework V4 est un <strong>socle PHP modulaire (PHP 8.5+)</strong> : un <strong>framework MVC léger</strong> doublé d'une <strong>plateforme à modules</strong> prête à l'emploi (admin, authentification, configuration, sécurité). <strong>Sans aucune dépendance front externe</strong> (ni React, ni Vue, ni Bootstrap, ni jQuery), il met la sécurité au cœur de sa conception et sert de socle à des applications comme GameNodePanel.</p>
    <ul>
      <li>Architecture modulaire : chaque fonctionnalité est un module autonome, activable/désactivable.</li>
      <li>Interface d'administration maison (design system <code>.ui-*</code>), thèmes clair / sombre / auto.</li>
      <li>Sécurité intégrée : CSRF, XSS, rate limiting, firewall applicatif, Security Center (27 détecteurs).</li>
      <li>Navigation SPA <code>turbo-nav.js</code> sans framework.</li>
    </ul>
    <p style="color:var(--tx3);font-size:.85rem">Répartition du code : PHP 91,9 % · CSS 4,5 % · JavaScript 3,6 %.</p>

    <h2 id="aegis">GameNodePanel &amp; Aegis Framework</h2>
    <p>GameNodePanel <strong>n'est pas</strong> un produit monolithique : c'est un ensemble de modules (gestion de serveurs de jeu, VEGA, O.D.I.N, Database Manager, Marketplace…) qui s'installent sur Aegis Framework. Le framework fournit le socle commun : routeur, base de données, sécurité, thème admin, système de modules et de menus.</p>
    <div class="callout">
      <span class="i">🧩</span>
      <div>Comprendre Aegis, c'est comprendre comment GameNodePanel se structure : <strong>mêmes routes, mêmes services, mêmes conventions de sécurité</strong>. Voir aussi la rubrique <a href="modules/gnp/doc-gnp.php">GameNodePanel</a>.</div>
    </div>

    <h2 id="prerequis">Prérequis</h2>
    <table class="doc-table">
      <tr><th>Élément</th><th>Exigence</th></tr>
      <tr><td>PHP</td><td><strong>≥ 8.5</strong> (testé sur 8.5.6)</td></tr>
      <tr><td>Base de données</td><td>MySQL ou MariaDB</td></tr>
      <tr><td>Extensions PHP</td><td><code>mbstring</code>, <code>pdo_mysql</code>, <code>curl</code>, <code>gd</code>, <code>fileinfo</code>, <code>openssl</code>, <code>zip</code>, <code>json</code>, <code>intl</code></td></tr>
      <tr><td>Serveur web</td><td>Apache (avec <code>.htaccess</code> / mod_rewrite) ou équivalent</td></tr>
    </table>

    <h2 id="installation">Installation</h2>
    <p>Le dossier <code>/install/</code> fournit un assistant en <strong>5 étapes</strong> (avec barre latérale) :</p>
    <ol class="steps">
      <li><strong>Bienvenue</strong> — présentation et démarrage.</li>
      <li><strong>Prérequis</strong> — vérification de PHP 8.5+ et des extensions requises.</li>
      <li><strong>Base de données</strong> — saisie des informations de connexion.</li>
      <li><strong>Administrateur</strong> — création du compte admin (mot de passe haché en <strong>Argon2id</strong>).</li>
      <li><strong>Installation</strong> — exécution automatisée via AJAX, avec barre de progression.</li>
    </ol>
    <p>En interne, <code>Installer.php</code> crée le fichier <code>.env</code>, configure la base, importe <code>schema.sql</code>, crée le compte admin et amorce les modules. Un fichier <code>installed.lock</code> est écrit pour empêcher toute réinstallation.</p>
    <div class="callout warn"><span class="i">⚠️</span><div>Conservez <code>.env</code> et <code>installed.lock</code> hors de portée publique. Ne réexécutez pas l'installeur sur une instance déjà installée.</div></div>

    <h2 id="architecture">Architecture &amp; structure</h2>
    <p>Le projet suit une organisation claire : un front controller, un routeur, le cœur du framework, les modules, et l'installeur.</p>
    <div class="tree">v4_classic/
├── index.php            # front controller (point d'entrée unique)
├── routes.php           # routes globales
├── framework/
│   ├── bootstrap.php     # init constantes, autoloader, config, services
│   ├── config/           # environnement, base de données, sécurité
│   ├── Services/         # Router, Database, Logger, AdminMenuService…
│   ├── Security/         # CSRF, RateLimiter, SessionManager, XSSProtection
│   ├── Templating/       # TemplateEngine, ThemeManager, ThemeSettings
│   ├── Capabilities/     # CapabilityRegistry, CapabilityManager, CapabilityOutput
│   ├── Storage/          # ImageUploader (téléversements sécurisés)
│   ├── Helpers/          # helpers chargés à la demande par les capacités
│   ├── Middleware/
│   └── Views/            # thème admin
├── modules/              # Auth, Configuration, System (+ vos modules)
└── install/             # assistant d'installation</div>
    <table class="doc-table">
      <tr><th>Namespace</th><th>Rôle</th><th>Détail</th></tr>
      <tr><td><code>Framework\Templating</code></td><td>Les <strong>parties publiques</strong> : thèmes en HTML pur, sans PHP, donc téléversables sans risque.</td><td><a href="framework/doc-templating.php">Moteur de templates</a></td></tr>
      <tr><td><code>Framework\Capabilities</code></td><td>Les <strong>briques transverses</strong> qu'un module déclare au lieu de les réécrire.</td><td><a href="framework/doc-capabilities.php">Fonctionnalités</a></td></tr>
      <tr><td><code>Framework\Storage</code></td><td>La réception des <strong>fichiers envoyés</strong> par un utilisateur, en un seul endroit contrôlé.</td><td><a href="framework/doc-services.php#srv-uploader">ImageUploader</a></td></tr>
    </table>
    <div class="callout ok"><span class="i">🎯</span><div>Ces trois namespaces existent pour la même raison : <strong>ce qui est écrit une fois dans le framework n'est pas réécrit dans chaque module</strong>. C'est la règle qui gouverne Aegis — faire le moins de code possible.</div></div>

    <h2 id="lifecycle">Cycle d'une requête</h2>
    <ol>
      <li><code>.htaccess</code> redirige toutes les requêtes vers <code>index.php</code> (sauf fichiers statiques réels).</li>
      <li><code>framework/bootstrap.php</code> initialise les constantes, l'autoloader, la configuration, les gestionnaires d'erreurs et les services.</li>
      <li>Les modules sont chargés par ordre de priorité.</li>
      <li>Le routeur enregistre toutes les routes des modules.</li>
      <li>Le garde CSRF valide les requêtes mutantes (POST/PUT/DELETE).</li>
      <li>Le handler s'exécute avec résolution des dépendances (injection).</li>
    </ol>

    <h2 id="routing">Routing</h2>
    <p>Le routeur accepte deux styles de handlers, avec injection de dépendances. Les paramètres d'URL utilisent la syntaxe <code>{id}</code>, et les routes peuvent être groupées via <code>group()</code>.</p>
    <pre><code>// Closure
$router-&gt;get('/api/status', function () { /* … */ });

// Contrôleur@méthode (avec injection de dépendances)
$router-&gt;get('/admin/users', 'Auth\\Controllers\\AdminController@users');

// Paramètre d'URL
$router-&gt;get('/admin/users/{id}', 'Auth\\Controllers\\AdminController@show');

// Groupe de routes
$router-&gt;group('/admin', function ($router) {
    $router-&gt;get('/dashboard', 'System\\Controllers\\DashboardController@index');
});</code></pre>

    <h2 id="modules">Système de modules</h2>
    <p>Un module est un paquet autonome. Fichiers <strong>obligatoires</strong> :</p>
    <ul>
      <li><code>module.json</code> — manifeste : métadonnées, routes, déclarations de menu.</li>
      <li><code>NomDuModule.php</code> — classe principale, étend <code>BaseModule</code>.</li>
    </ul>
    <p>Composants <strong>optionnels</strong> : <code>routes.php</code>, <code>Controllers/</code>, <code>Services/</code>, <code>Views/</code>, <code>database/install.sql</code> &amp; <code>uninstall.sql</code>, <code>database/migrations/</code>.</p>
    <div class="callout"><span class="i">👉</span><div>Un guide pas-à-pas complet (avec le module d'exemple <strong>MonModule</strong>) se trouve dans la rubrique <a href="framework/doc-module.php">Module → Créer un module</a>.</div></div>

    <h2 id="menus">Menus (100 % JSON)</h2>
    <p>Les menus d'administration sont déclarés exclusivement en JSON dans <code>module.json</code> — jamais en dur dans le code.</p>
    <pre><code>{
  "menu": [
    {
      "label": "Mon module",
      "icon": "🚀",
      "position": 300,
      "url": "/admin/module",
      "children": [
        { "label": "Sous-menu", "icon": "📋", "url": "/admin/module/sub" }
      ]
    }
  ]
}</code></pre>
    <p>Option <strong>méga-menu</strong> : un item avec <code>"mega": true</code> s'affiche en grille 3 colonnes (min 580 px / max 92 vw), réduite à 2 colonnes sous 700 px.</p>

    <h2 id="services">Services</h2>
    <table class="doc-table">
      <tr><th>Service</th><th>Rôle</th></tr>
      <tr><td><code>Router</code></td><td>Dispatch des requêtes, résolution des paramètres</td></tr>
      <tr><td><code>Database</code></td><td>Wrapper PDO, requêtes préparées, journalisation SQL</td></tr>
      <tr><td><code>Logger</code></td><td>Journalisation fichier &amp; SQL</td></tr>
      <tr><td><code>AdminMenuService</code></td><td>Construction dynamique du menu depuis les modules</td></tr>
      <tr><td><code>CacheService</code></td><td>Optimisation des performances</td></tr>
      <tr><td><code>AuthTracker</code></td><td>Suivi de l'activité utilisateur</td></tr>
      <tr><td><code>DeviceDetector</code></td><td>Identification du client</td></tr>
      <tr><td><code>GeolocService</code></td><td>Géolocalisation d'IP</td></tr>
      <tr><td><code>RecaptchaService</code></td><td>Intégration reCAPTCHA</td></tr>
      <tr><td><code>ImageOptimizer</code></td><td>Redimensionnement / compression GD (hors SVG/ICO/GIF)</td></tr>
    </table>

    <h2 id="securite">Sécurité by design</h2>
    <ul>
      <li><strong>CSRF</strong> : protection par jeton avec garde globale au niveau du routeur.</li>
      <li><strong>Rate limiting</strong> : par compte et par IP, avec suivi en base.</li>
      <li><strong>Firewall applicatif</strong> : réputation d'IP et scoring de menace.</li>
      <li><strong>XSS</strong> : filtrage des entrées et échappement des sorties (<code>htmlspecialchars</code> systématique).</li>
      <li><strong>Sessions</strong> : cookies <code>HttpOnly</code>, <code>Secure</code>, <code>SameSite</code>.</li>
      <li><strong>Mots de passe</strong> : hachage <strong>Argon2id</strong>.</li>
      <li><strong>SQL</strong> : requêtes préparées obligatoires partout.</li>
    </ul>

    <h2 id="security-center">Security Center</h2>
    <p>Le <code>SecurityCenterService</code> est un système avancé de détection de menaces.</p>
    <ul>
      <li><strong>27 détecteurs</strong> par catégories : web, scan, auth, upload, abuse, admin.</li>
      <li><strong>Scoring</strong> cumulatif par IP, 4 niveaux : faible / moyen / élevé / critique.</li>
      <li><strong>Blocage auto</strong> à seuils configurables : temporaire à 100 points, permanent à 300.</li>
      <li><strong>Surface analysée</strong> : URL (chemin, query, User-Agent), uploads POST, intégrité de session.</li>
      <li><strong>Listes d'IP</strong> : liste blanche (exemptée) et liste noire (bannissement permanent).</li>
    </ul>
    <p>Détecteurs actifs : XSS, injection SQL, path traversal, LFI/RFI, fingerprinting CMS, scanners, brute-force, flooding d'authentification, upload de webshells…</p>

    <h2 id="helpers">Helpers globaux</h2>
    <pre><code>u('/admin/users');                // URL absolue
url('/path'); redirect('/path');  // navigation
admin_header($title, $context);   // en-tête de page admin
admin_footer();                   // pied de page admin</code></pre>

    <h2 id="conventions">Conventions</h2>
    <ul>
      <li>Aucune dépendance front externe.</li>
      <li><strong>Un contrôleur + un service</strong> par fonctionnalité.</li>
      <li>Toute sortie utilisateur échappée (<code>htmlspecialchars</code>).</li>
      <li>Toutes les requêtes SQL en <strong>préparé</strong>.</li>
      <li>Menus <strong>exclusivement</strong> en JSON.</li>
      <li>Pages admin via helpers de thème, jamais d'include codé en dur.</li>
      <li>Modules cœur (<code>Auth</code>, <code>Configuration</code>, <code>System</code>) <strong>non désactivables</strong>.</li>
    </ul>

    <div class="doc-foot">
      <span>Aegis Framework v4.0.0 · socle de GameNodePanel</span>
      <span><a href="<?= $h($github) ?>" target="_blank" rel="noopener">Dépôt GitHub ↗</a></span>
    </div>

<?php require __DIR__ . '/../inc/foot.php'; ?>
