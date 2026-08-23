<?php
$docPage = 'framework/doc-services.php';
$seo = ['title' => 'Services & helpers — Documentation · Aegis Framework', 'desc' => "Les services intégrés d'Aegis Framework : Database (PDO), Cache, Logger, Debug Bar, géolocalisation, ImageOptimizer, DeviceDetector, reCAPTCHA, et les helpers globaux u()/url()/redirect().", 'canonical' => 'https://gamenodepanel.com/documentation/doc-services.php'];
require __DIR__ . '/../inc/head.php';
?>
    <h1>Services &amp; helpers</h1>
    <p class="doc-lead">Aegis fournit un jeu de <strong>services prêts à l'emploi</strong> (base de données, cache, logs, géolocalisation, images…) injectés dans vos contrôleurs, plus quelques <strong>helpers globaux</strong> pour le quotidien.</p>
    <div class="doc-meta"><span class="doc-pill">Injection de dépendances</span><span class="doc-pill">PHP 8.5+</span><span class="doc-pill">Zéro dépendance externe</span></div>

    <h2 id="srv-intro">Le conteneur</h2>
    <p>Les services sont instanciés une fois au démarrage (<code>framework/bootstrap.php</code>) et fournis aux contrôleurs par <strong>injection</strong>. Dans une closure de route, ils sont disponibles via <code>use</code> ; dans un contrôleur, via le constructeur.</p>
    <pre><code>$router-&gt;get('/admin/stats', function () use ($db, $logger, $cache) {
    // $db, $logger, $cache… déjà prêts
});</code></pre>
    <div class="callout"><span class="i">🧩</span><div>Pour des dépendances spécifiques à votre module, déclarez-les dans <code>setDependencies()</code> (voir <a href="framework/doc-module.php">Créer un module</a>).</div></div>

    <h2 id="srv-database">Database</h2>
    <p>Wrapper PDO avec <strong>requêtes préparées</strong> et journalisation SQL (visible dans la Debug Bar). Les méthodes courantes :</p>
    <pre><code>$db-&gt;query("SELECT * FROM users WHERE role = ?", ['admin']);   // → array de lignes
$db-&gt;queryOne("SELECT * FROM users WHERE id = ?", [$id]);      // → 1 ligne ou null
$db-&gt;execute("UPDATE users SET active = 1 WHERE id = ?", [$id]); // INSERT/UPDATE/DELETE
$id = $db-&gt;getPDO()-&gt;lastInsertId();</code></pre>
    <div class="callout warn"><span class="i">⚠️</span><div>Toujours passer les valeurs en <strong>paramètres liés</strong> (<code>?</code>), jamais par concaténation — c'est la règle anti-injection du framework.</div></div>

    <h2 id="srv-cache">Cache</h2>
    <p><code>CacheService</code> (et le <code>CacheHelper</code>) fournissent un cache simple (fichier) pour éviter de recalculer des données coûteuses.</p>
    <pre><code>\Framework\Cache::set('stats_home', $data, 300);   // TTL 300 s
$data = \Framework\Cache::get('stats_home');        // null si absent / expiré
\Framework\Cache::delete('stats_home');
\Framework\Cache::clear('prefix_');                 // purge par préfixe</code></pre>

    <h2 id="srv-logger">Logger</h2>
    <p>Journalisation <strong>fichier + SQL</strong>, avec rotation par taille et rétention. Le dossier <code>framework/logs/</code> est interdit au web.</p>
    <pre><code>$logger-&gt;info('Plugin installé', ['server_id' =&gt; $id]);
$logger-&gt;warning('Quota bientôt atteint', ['usage' =&gt; '5.5/6 Mo']);
$logger-&gt;error('Échec SSH', ['host' =&gt; $ip]);</code></pre>
    <div class="callout"><span class="i">🔐</span><div>Ne journalisez jamais de secret en clair (mot de passe, clé API). Loggez l'événement, pas la donnée sensible.</div></div>

    <h2 id="srv-debugbar">Debug Bar</h2>
    <p>En mode debug (réglage <strong>Configuration → Système → <code>debug_mode</code></strong>, interrupteur maître au-dessus de <code>APP_ENV</code>), une barre affiche en bas de page : requêtes SQL exécutées, temps, mémoire et logs de la requête. Idéale pour traquer une requête lente ou un N+1.</p>

    <h2 id="srv-geoloc">Géolocalisation</h2>
    <p><code>GeolocService</code> résout un pays / une localisation à partir d'une IP (avec cache). Utilisé par O.D.I.N pour la carte des menaces et la géolocalisation des connexions.</p>
    <pre><code>$pays = $geoloc-&gt;country('8.8.8.8');   // ex. "US"</code></pre>

    <h2 id="srv-image">ImageOptimizer</h2>
    <p>Redimensionnement et compression via GD. <strong>Sûr par défaut</strong> : ignore les formats vectoriels/animés (SVG, ICO, GIF) et n'écrase le fichier que si le résultat est <em>plus léger</em>. Utilisé pour les logos, favicons, avatars et images Open Graph.</p>

    <h2 id="srv-uploader">ImageUploader</h2>
    <p><code>Framework\Storage\ImageUploader</code> est le point d'entrée <strong>unique</strong> pour recevoir une image envoyée par un utilisateur. Il remplace les copies de service d'upload que chaque module traînait auparavant.</p>
    <pre><code><?= $h(<<<'PHP'
use Framework\Storage\ImageUploader;

$uploader = new ImageUploader('MonModule');            // 5 Mo par défaut
$url = $uploader->store($_FILES['logo'], 'logos', $ancienneUrl);

// Variante qui explique pourquoi elle refuse, au lieu de rendre null :
$url = $uploader->storeOrFail($_FILES['logo'], 'logos');

// Écrire ailleurs que dans le dossier d'uploads du module :
$uploader = ImageUploader::into('MonModule', $dossierAbsolu, $urlPublique);
PHP) ?></code></pre>
    <table class="doc-table">
      <tr><th>Protection</th><th>Détail</th></tr>
      <tr><td>Taille plafonnée</td><td>5 Mo par défaut, réglable au constructeur.</td></tr>
      <tr><td>Type lu dans le contenu</td><td>Le MIME est déterminé à partir des octets du fichier, <strong>jamais</strong> à partir de son nom ni de ce qu'annonce le navigateur.</td></tr>
      <tr><td>Ré-encodage GD</td><td>L'image est reconstruite. Toute charge dissimulée dans les métadonnées <strong>disparaît</strong> — un « PNG » contenant du PHP ne survit pas.</td></tr>
      <tr><td>SVG refusé</td><td>Le format vectoriel peut porter du script. Il faut le demander explicitement (<code>['svg' =&gt; true]</code>).</td></tr>
      <tr><td>Dossier durci</td><td>Un <code>.htaccess</code> est déposé dans le dossier de destination pour empêcher toute exécution.</td></tr>
      <tr><td>Chemin nettoyé</td><td>Le sous-dossier est validé segment par segment (3 niveaux maximum) — l'imbrication est préservée, la remontée impossible.</td></tr>
    </table>
    <div class="callout"><span class="i">🖼️</span><div>Les options de thème de type <code>image</code> passent par ce service : un administrateur qui téléverse un logo depuis l'écran d'options bénéficie de toutes ces protections sans que le module ait une ligne à écrire.</div></div>

    <h2 id="srv-device">DeviceDetector</h2>
    <p>Identifie le client (navigateur, OS, type d'appareil) à partir du User-Agent. Alimente le suivi de connexions (<code>AuthTracker</code>) et l'historique de sécurité.</p>

    <h2 id="srv-recaptcha">reCAPTCHA</h2>
    <p><code>RecaptchaService</code> + <code>RecaptchaHelper</code> intègrent Google reCAPTCHA sur les formulaires sensibles (login, inscription). Activation et clés se configurent dans l'administration.</p>

    <h2 id="srv-helpers">Helpers globaux</h2>
    <p>Définis au bootstrap, disponibles partout :</p>
    <table class="doc-table">
      <tr><th>Helper</th><th>Rôle</th></tr>
      <tr><td><code>u('/admin/users')</code></td><td>URL absolue respectant <code>BASE_URL</code> (à utiliser dans les liens &amp; redirections)</td></tr>
      <tr><td><code>url('/x')</code></td><td>Construction d'URL</td></tr>
      <tr><td><code>redirect('/x')</code></td><td>Redirection HTTP</td></tr>
      <tr><td><code>admin_header($titre, $ctx)</code></td><td>En-tête de page admin (thème)</td></tr>
      <tr><td><code>admin_footer()</code></td><td>Pied de page admin</td></tr>
    </table>
    <div class="callout"><span class="i">💡</span><div>Utilisez <strong>toujours</strong> <code>u()</code> pour vos liens internes : l'application peut être installée dans un sous-dossier, et <code>u()</code> garantit des chemins corrects partout.</div></div>

    <?php require __DIR__ . '/../inc/foot.php'; ?>
