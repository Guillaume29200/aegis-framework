<?php
/**
 * documentation/framework/doc-capabilities.php — Les capacités transverses d'un module.
 */
$docPage = 'framework/doc-capabilities.php';
$seo = [
    'title'     => 'Fonctionnalités & capacités — Documentation · Aegis Framework',
    'desc'      => "Les capacités d'Aegis : Markdown, Cache, IA, SEO, RGPD/Cookies, Analytics, TurboNav et reCAPTCHA. Déclaration dans module.json, helpers chargés au démarrage et rendu par CapabilityOutput.",
    'canonical' => 'https://gamenodepanel.com/documentation/framework/doc-capabilities.php',
];
require __DIR__ . '/../inc/head.php';

$code_manifest = <<<'JSON'
{
    "name": "MonModule",
    "capabilities": ["seo", "rgpd", "analytics", "cache", "turbonav"]
}
JSON;

$code_output = <<<'PHP'
<?php
use Framework\Capabilities\CapabilityOutput;

// Dans le contrôleur public, au moment de préparer le contexte :
$caps = CapabilityOutput::forPage([
    'title'       => $titre,
    'description' => $description,
]);

echo $themes->render('home', array_merge($caps, [
    'site'  => ['name' => 'Mon site'],
    'theme' => $options,
]));
PHP;

$code_html = <<<'TPL'
<!-- header.html, juste avant </head> -->
{{{ head_extra }}}      → balises SEO, tracker Analytics, script reCAPTCHA

<!-- footer.html, juste avant </body> -->
{{{ body_end }}}        → bandeau cookies, script TurboNav
TPL;

$code_cache = <<<'PHP'
// Le helper vient du framework, mais on ne le suppose PAS présent :
// un service doit répondre même si la brique de cache manque.
$calcul = function () use ($page, $perPage) {
    return $this->db->query('SELECT …', [$perPage, ($page - 1) * $perPage]);
};

return function_exists('cache_remember')
    ? cache_remember('monmodule_list_' . $page, $calcul, 60)
    : $calcul();
PHP;
?>

    <h1>Fonctionnalités &amp; capacités</h1>
    <p class="doc-lead">Une <strong>capacité</strong> est une brique transverse qu'un module déclare vouloir utiliser — le cache, le SEO, le bandeau cookies… Elle se coche à la génération, se déclare en une ligne de JSON, et se pose toute seule sur les pages publiques.</p>
    <div class="doc-meta">
      <span class="doc-pill">Framework\Capabilities</span>
      <span class="doc-pill">8 capacités</span>
      <span class="doc-pill">Zéro câblage</span>
    </div>

    <h2 id="cap-intro">Le principe</h2>
    <p>Aegis cherche à <strong>écrire le moins de code possible</strong>. Une capacité en est l'illustration : au lieu que chaque module réimplémente son bandeau cookies, ses balises méta ou sa mise en cache, il <strong>déclare</strong> ce dont il a besoin et le framework fournit.</p>
    <ol class="steps">
      <li>Le module liste ses capacités dans <code>module.json</code>.</li>
      <li>Au démarrage, <code>CapabilityManager</code> charge les helpers correspondants — et <strong>uniquement ceux-là</strong>.</li>
      <li>Sur une page publique, <code>CapabilityOutput</code> rassemble ce que ces briques doivent poser dans le HTML.</li>
      <li>Le gabarit affiche le résultat via deux variables brutes.</li>
    </ol>
    <div class="callout"><span class="i">💡</span><div>Une capacité non déclarée <strong>ne coûte rien</strong> : son helper n'est même pas chargé. Une capacité déclarée mais désactivée en configuration se tait d'elle-même.</div></div>

    <h2 id="cap-list">Les huit capacités</h2>
    <table class="doc-table">
      <tr><th>Clé</th><th>Nom</th><th>Ce qu'elle apporte</th><th>Se règle dans</th></tr>
      <tr><td><code>markdown</code></td><td>✍️ Markdown</td><td>Éditeur Markdown sur les zones de texte et rendu HTML sécurisé partagé.</td><td>—</td></tr>
      <tr><td><code>cache</code></td><td>⚡ Cache</td><td><code>cache_remember()</code> pour mémoriser les requêtes coûteuses.</td><td>Configuration</td></tr>
      <tr><td><code>ai</code></td><td>🤖 Intelligence artificielle</td><td>Accès aux modèles configurés (modèle par défaut, provider, capacités).</td><td>Configuration → Modèles IA</td></tr>
      <tr><td><code>seo</code></td><td>🔎 SEO</td><td>Balises méta par page : titre, description, canonical, Open Graph, Twitter Card.</td><td>Configuration → SEO &amp; médias</td></tr>
      <tr><td><code>rgpd</code></td><td>🍪 RGPD / Cookies</td><td>Bandeau de consentement, composant central du framework.</td><td>Configuration → RGPD</td></tr>
      <tr><td><code>analytics</code></td><td>📊 Analytics</td><td>Injection du tracker d'audience sur les pages publiques.</td><td>Administration → Analytics</td></tr>
      <tr><td><code>turbonav</code></td><td>⚡ TurboNav</td><td>Navigation AJAX sur les pages publiques : préchargement au survol, pas de rechargement complet.</td><td>Configuration <strong>(obligatoire)</strong></td></tr>
      <tr><td><code>recaptcha</code></td><td>🛡️ reCAPTCHA</td><td>Protection anti-bot des formulaires (script + vérification).</td><td>Configuration → reCAPTCHA</td></tr>
    </table>

    <h2 id="cap-declare">Déclarer une capacité</h2>
    <p>Une seule ligne dans le manifeste. Le générateur l'écrit pour vous à partir des cases cochées.</p>
    <pre><code><?= $h($code_manifest) ?></code></pre>
    <div class="callout"><span class="i">🔌</span><div>Ajouter une capacité à un module <strong>déjà généré</strong> se fait en éditant cette ligne — rien d'autre à installer.</div></div>

    <h2 id="cap-output">Poser les capacités sur une page</h2>
    <p>C'est ici que se joue la différence entre une capacité <em>documentée</em> et une capacité <em>qui fonctionne</em>. Un gabarit n'exécute aucun PHP : il ne peut donc pas appeler <code>cookie_banner()</code>. Le contrôleur le fait pour lui et lui passe le résultat.</p>
    <pre><code><?= $h($code_output) ?></code></pre>
    <p>Côté gabarit, deux variables <strong>brutes</strong> (triple accolade) suffisent :</p>
    <pre><code><?= $h($code_html) ?></code></pre>
    <table class="doc-table">
      <tr><th>Variable</th><th>Où la poser</th><th>Ce qu'elle contient</th></tr>
      <tr><td><code>{{{ head_extra }}}</code></td><td>Fin du <code>&lt;head&gt;</code></td><td><code>seo_head()</code> + <code>analytics_head()</code> + <code>recaptcha_script()</code></td></tr>
      <tr><td><code>{{{ body_end }}}</code></td><td>Fin du <code>&lt;body&gt;</code></td><td><code>cookie_banner()</code> + <code>turbonav_script()</code></td></tr>
    </table>
    <div class="callout warn"><span class="i">⚠️</span><div>Utilisez bien <strong>trois</strong> accolades. Avec deux, le HTML serait échappé et vous verriez les balises s'afficher en clair sur la page.</div></div>
    <p>Les thèmes produits par le générateur portent déjà ces deux variables. Si vous écrivez un thème à la main, ne les oubliez pas — sans elles, cocher « RGPD » ne fera rien apparaître.</p>

    <h2 id="cap-turbonav">Le cas TurboNav</h2>
    <p>TurboNav est la seule capacité qui dépend d'un <strong>réglage global</strong>. Cocher la case dans le générateur pose le script sur vos pages publiques, mais celui-ci reste <strong>en veille</strong> tant que TurboNav n'est pas activé dans <strong>Administration → Configuration</strong>.</p>
    <div class="callout"><span class="i">⚡</span><div>Ce n'est pas un oubli : la navigation SPA change le comportement de <em>tout</em> le site. Le choix reste donc à l'administrateur, pas au module. Le fonctionnement détaillé est décrit dans <a href="framework/doc-turbonav.php">TurboNav (navigation SPA)</a>.</div></div>
    <p>Le script est posé une seule fois par page, et versionné par la date de modification du fichier — le cache du navigateur se rafraîchit donc tout seul à chaque mise à jour.</p>

    <h2 id="cap-cache">Le motif à retenir : ne jamais supposer</h2>
    <p>Un helper de capacité peut être absent : le module a été copié ailleurs, la capacité a été retirée du manifeste, la brique est désactivée. <strong>Un service doit répondre quand même.</strong></p>
    <pre><code><?= $h($code_cache) ?></code></pre>
    <p>Sans le garde <code>function_exists()</code>, retirer une capacité provoquerait une erreur fatale plutôt qu'une simple perte d'optimisation. Le code généré applique systématiquement ce motif.</p>
    <div class="callout ok"><span class="i">✅</span><div>La règle : une capacité <strong>améliore</strong> le fonctionnement, elle ne le conditionne jamais.</div></div>

    <h2 id="cap-example">Les voir toutes en marche</h2>
    <p>Le module <strong>Exemple</strong>, livré avec le framework, déclare les huit capacités et les utilise réellement. Il est fourni <strong>inactif</strong> : activez-le depuis Administration → Modules, ou lisez simplement son code.</p>
    <div class="tree">modules/Exemple/
├── module.json                    les 8 capacités déclarées
├── Controllers/PublicController.php   CapabilityOutput::forPage()
├── Services/ExempleService.php        le garde function_exists('cache_remember')
├── themes/default/header.html         {{{ head_extra }}}
├── themes/default/footer.html         {{{ body_end }}}
└── README.md                          les cinq règles, avec le fichier où chacune se voit</div>

    <div class="doc-foot">
      <span>Capacités · <code>framework/Capabilities/</code></span>
      <span><a href="<?= $h($github) ?>" target="_blank" rel="noopener">Dépôt GitHub ↗</a></span>
    </div>

<?php require __DIR__ . '/../inc/foot.php'; ?>
