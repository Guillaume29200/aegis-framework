<?php
$docPage = 'framework/doc-router.php';
$seo = ['title' => 'Router & Helpers — Référence · Documentation GameNodePanel', 'desc' => "Référence du routeur Aegis (get/post/put/delete, paramètres, groupes, injection de dépendances) et des helpers globaux (u, url, redirect, admin_header).", 'canonical' => 'https://gamenodepanel.com/documentation/doc-router.php'];
require __DIR__ . '/../inc/head.php';
$code_routes = <<<'PHP'
// Verbes HTTP
$router->get('/chemin', $handler);
$router->post('/chemin', $handler);
$router->put('/chemin/{id}', $handler);
$router->delete('/chemin/{id}', $handler);

// Handler : closure …
$router->get('/api/status', function () {
    echo json_encode(['ok' => true]);
});

// … ou Contrôleur@méthode (recommandé)
$router->get('/admin/users', 'Auth\\Controllers\\AdminController@users');
PHP;
$code_params = <<<'PHP'
$router->get('/admin/users/{id}', 'Auth\\Controllers\\AdminController@show');

// Dans le contrôleur, le paramètre est passé à la méthode :
public function show(int $id): void { /* … */ }
PHP;
$code_groups = <<<'PHP'
$router->group('/admin/monmodule', function ($router) {
    $router->get('/dashboard', 'MonModule\\Controllers\\AdminController@dashboard');
    $router->post('/items/{id}/delete', 'MonModule\\Controllers\\AdminController@delete');
});
PHP;
$code_di = <<<'PHP'
class AdminController
{
    // Le routeur injecte automatiquement les dépendances typées.
    public function __construct(
        private \Framework\Services\Database $db,
        private \Framework\Security\CSRFProtection $csrf
    ) {}
}
PHP;
$code_helpers = <<<'PHP'
u('/admin/users');                 // → URL absolue (respecte APP_URL / base path)
redirect('/auth/login');           // redirection HTTP
admin_header('Titre de la page');  // en-tête du thème admin
admin_footer();                    // pied de page du thème admin
PHP;
?>
    <h1>Router &amp; Helpers</h1>
    <p class="doc-lead">Le routeur associe une URL à un handler, avec paramètres, groupes et injection de dépendances. Les helpers globaux simplifient URLs, redirections et rendu des pages admin.</p>

    <h2 id="rt-routes">Définir des routes</h2>
    <p>Quatre verbes principaux (<code>get</code>, <code>post</code>, <code>put</code>, <code>delete</code>). Le handler est une closure ou, de préférence, une référence <code>Contrôleur@méthode</code> (chargée à la demande).</p>
    <pre><code><?= $h($code_routes) ?></code></pre>

    <h2 id="rt-params">Paramètres d'URL</h2>
    <p>Les segments dynamiques utilisent les accolades <code>{...}</code> et sont transmis à la méthode du contrôleur.</p>
    <pre><code><?= $h($code_params) ?></code></pre>

    <h2 id="rt-groups">Groupes de routes</h2>
    <p>Regroupez les routes sous un préfixe commun avec <code>group()</code> — idéal pour un module.</p>
    <pre><code><?= $h($code_groups) ?></code></pre>

    <h2 id="rt-di">Injection de dépendances</h2>
    <p>Le routeur résout les dépendances du constructeur du contrôleur par typage : pas besoin de les instancier à la main.</p>
    <pre><code><?= $h($code_di) ?></code></pre>
    <div class="callout"><span class="i">🛡️</span><div>Le garde CSRF global valide automatiquement les requêtes mutantes (POST/PUT/DELETE) avant d'atteindre votre handler.</div></div>

    <h2 id="rt-helpers">Helpers globaux</h2>
    <pre><code><?= $h($code_helpers) ?></code></pre>
    <table class="doc-table">
      <tr><th>Helper</th><th>Rôle</th></tr>
      <tr><td><code>u($path)</code></td><td>Construit une URL absolue à partir d'un chemin.</td></tr>
      <tr><td><code>url()</code> / <code>redirect()</code></td><td>Navigation / redirection HTTP.</td></tr>
      <tr><td><code>admin_header($title, $context)</code></td><td>Ouvre une page d'administration (thème).</td></tr>
      <tr><td><code>admin_footer()</code></td><td>Ferme une page d'administration.</td></tr>
    </table>

    <?php require __DIR__ . '/../inc/foot.php'; ?>
