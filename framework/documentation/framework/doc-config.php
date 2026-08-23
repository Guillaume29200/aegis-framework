<?php
$docPage = 'framework/doc-config.php';
$seo = ['title' => 'Configuration .env — Documentation · GameNodePanel', 'desc' => "Les variables d'environnement d'Aegis Framework : base de données, application, clé de chiffrement, sécurité. Bonnes pratiques de configuration.", 'canonical' => 'https://gamenodepanel.com/documentation/doc-config.php'];
require __DIR__ . '/../inc/head.php';
$code_env = <<<'ENV'
# .env — généré par l'installeur, à la racine du projet
APP_ENV=production           # development | production
APP_DEBUG=false             # true en dev seulement
APP_URL=https://exemple.com
APP_KEY=base64:xxxxxxxxxxxx  # clé de chiffrement (AES-256) — NE PAS partager

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aegis
DB_USERNAME=aegis_user
DB_PASSWORD=********
ENV;
?>
    <h1>Configuration <code>.env</code></h1>
    <p class="doc-lead">La configuration sensible vit dans un fichier <code>.env</code> à la racine, généré par l'installeur. Il ne doit jamais être versionné ni exposé publiquement.</p>

    <h2 id="env-intro">Le fichier <code>.env</code></h2>
    <p>Aegis lit le <code>.env</code> au démarrage (bootstrap) pour configurer la base de données, l'application et le chiffrement. Les valeurs sont chargées dans l'environnement PHP et consommées par les services.</p>
    <pre><code><?= $h($code_env) ?></code></pre>

    <h2 id="env-vars">Variables principales</h2>
    <table class="doc-table">
      <tr><th>Variable</th><th>Rôle</th></tr>
      <tr><td><code>APP_ENV</code></td><td>Environnement : <code>development</code> ou <code>production</code>.</td></tr>
      <tr><td><code>APP_DEBUG</code></td><td>Affichage détaillé des erreurs. <strong>Toujours <code>false</code> en production.</strong></td></tr>
      <tr><td><code>APP_URL</code></td><td>URL de base du site (liens absolus, redirections).</td></tr>
      <tr><td><code>APP_KEY</code></td><td>Clé de chiffrement (AES-256). Sert au chiffrement des secrets. À garder confidentielle.</td></tr>
      <tr><td><code>DB_HOST</code> / <code>DB_PORT</code></td><td>Hôte et port MySQL/MariaDB.</td></tr>
      <tr><td><code>DB_DATABASE</code></td><td>Nom de la base.</td></tr>
      <tr><td><code>DB_USERNAME</code> / <code>DB_PASSWORD</code></td><td>Identifiants de connexion.</td></tr>
    </table>
    <div class="callout"><span class="i">🔑</span><div>La <code>APP_KEY</code> chiffre les données sensibles. Si vous la changez après coup, les secrets déjà chiffrés deviennent illisibles : prévoyez une migration.</div></div>

    <h2 id="env-security">Sécuriser le <code>.env</code></h2>
    <ul>
      <li>Hors du dossier public si possible, sinon protégé par <code>.htaccess</code> (refus d'accès direct).</li>
      <li>Permissions restrictives (lecture par l'utilisateur du serveur web uniquement).</li>
      <li>Jamais commité dans Git (ajoutez-le à <code>.gitignore</code>).</li>
      <li>Une valeur (mot de passe, clé) ne doit jamais finir dans un log.</li>
    </ul>

    <h2 id="env-prod">En production</h2>
    <ul>
      <li><code>APP_ENV=production</code> et <code>APP_DEBUG=false</code>.</li>
      <li>HTTPS forcé (cookies <code>Secure</code>).</li>
      <li>Conserver <code>installed.lock</code> pour empêcher toute réinstallation.</li>
      <li>Sauvegardes régulières de la base et du <code>.env</code>.</li>
    </ul>

    <?php require __DIR__ . '/../inc/foot.php'; ?>
