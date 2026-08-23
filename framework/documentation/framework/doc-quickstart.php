<?php
$docPage = 'framework/doc-quickstart.php';
$seo = ['title' => 'Démarrage rapide — Documentation · GameNodePanel', 'desc' => "Installer Aegis Framework / GameNodePanel en 5 minutes : récupérer le code, lancer l'assistant d'installation, se connecter.", 'canonical' => 'https://gamenodepanel.com/documentation/doc-quickstart.php'];
require __DIR__ . '/../inc/head.php';
$code_get = <<<'BASH'
# Cloner le dépôt Aegis Framework
git clone https://github.com/Guillaume29200/aegis-framework.git
cd aegis-framework

# (ou télécharger le ZIP depuis GitHub et l'extraire dans votre webroot)
BASH;
$code_vhost = <<<'TXT'
http://localhost/aegis-framework/install/
TXT;
?>
    <h1>Démarrage rapide</h1>
    <p class="doc-lead">De zéro à un panel fonctionnel en 5 minutes. Cette page résume l'essentiel — les détails sont dans <a href="framework/doc-aegis.php#installation">Installation</a>.</p>
    <div class="doc-meta"><span class="doc-pill">⏱️ ~5 min</span><span class="doc-pill">PHP ≥ 8.5</span><span class="doc-pill">MySQL/MariaDB</span></div>

    <h2 id="qs-intro">En 5 minutes</h2>
    <ol class="steps">
      <li>Récupérer le code et le placer dans votre serveur web.</li>
      <li>Créer une base de données vide.</li>
      <li>Lancer l'assistant <code>/install/</code> et suivre les 5 étapes.</li>
      <li>Se connecter à l'administration.</li>
    </ol>
    <div class="callout"><span class="i">📦</span><div>Aegis Framework est gratuit : <a href="<?= $h($github) ?>" target="_blank" rel="noopener"><?= $h($github) ?></a>.</div></div>

    <h2 id="qs-get">1 · Récupérer le projet</h2>
    <pre><code><?= $h($code_get) ?></code></pre>
    <p>Placez le dossier dans votre webroot (ex. <code>C:\wamp64\www\</code> sous WAMP, <code>/var/www/</code> sous Linux). Vérifiez que <code>mod_rewrite</code> / <code>.htaccess</code> est actif.</p>

    <h2 id="qs-install">2 · Lancer l'installation</h2>
    <p>Créez une base MySQL vide, puis ouvrez l'assistant dans votre navigateur :</p>
    <pre><code><?= $h($code_vhost) ?></code></pre>
    <p>Suivez les <strong>5 étapes</strong> : Bienvenue → Prérequis → Base de données → Administrateur → Installation. L'assistant crée le <code>.env</code>, importe le schéma, crée votre compte admin (Argon2id) et écrit <code>installed.lock</code>.</p>
    <div class="callout warn"><span class="i">⚠️</span><div>Si vous êtes redirigé vers <code>/install/</code> alors que c'est déjà installé, vérifiez la présence de <code>installed.lock</code> et la config DB dans <code>.env</code> / <code>framework/config/database.php</code>.</div></div>

    <h2 id="qs-login">3 · Premier login</h2>
    <p>Rendez-vous sur <code>/auth/login</code>, connectez-vous avec le compte créé, puis accédez au tableau de bord <code>/admin/dashboard</code>.</p>

    <h2 id="qs-next">Et ensuite ?</h2>
    <ul>
      <li>Installer des modules (dont GameNodePanel) — voir <a href="framework/doc-module.php#mod-install">Installer un module</a>.</li>
      <li>Configurer l'environnement — voir <a href="framework/doc-config.php">Configuration .env</a>.</li>
      <li>Créer votre propre module — voir <a href="framework/doc-module.php">Créer un module</a>.</li>
    </ul>

    <?php require __DIR__ . '/../inc/foot.php'; ?>
