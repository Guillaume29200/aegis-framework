<?php
$docPage = 'modules/gnp/doc-install.php';
$seo = ['title' => 'Installer un serveur de jeu — Documentation · GameNodePanel', 'desc' => "Comment GameNodePanel installe un serveur de jeu : SteamCMD (avec Steam Guard), Docker, Java/Minecraft, Git, manuel et Venice Unleashed, avec une console d'installation en direct (SSE).", 'canonical' => 'https://gamenodepanel.com/documentation/doc-install.php'];
require __DIR__ . '/../../inc/head.php';
?>
    <h1>Installer un serveur de jeu</h1>
    <p class="doc-lead">Après avoir <a href="modules/gnp/doc-games.php">ajouté un jeu au catalogue</a>, vous déployez un serveur sur un hôte. GameNodePanel choisit le bon <strong>moteur d'installation</strong> (SteamCMD, Docker, Java, Git…) et vous montre la progression <strong>en direct</strong>.</p>
    <div class="doc-meta"><span class="doc-pill">SteamCMD</span><span class="doc-pill">Docker</span><span class="doc-pill">Java</span><span class="doc-pill">Console live</span></div>

    <h2 id="inst-intro">Vue d'ensemble</h2>
    <p>Une installation = un jeu (du catalogue) déployé sur un <a href="modules/gnp/doc-host.php">serveur hôte</a>, dans un répertoire dédié, avec un compte FTP et des ports attribués. Le moteur d'installation dépend de la <strong>version du jeu</strong> choisie : chaque version déclare son mode de distribution.</p>
    <table class="doc-table">
      <tr><th>Moteur</th><th>Pour quels jeux</th></tr>
      <tr><td><strong>SteamCMD</strong></td><td>Jeux distribués via Steam (CS2, Rust, Palworld, ARK…)</td></tr>
      <tr><td><strong>Docker</strong></td><td>Jeux conteneurisés (Conan, Enshrouded, Abiotic Factor…)</td></tr>
      <tr><td><strong>Java</strong></td><td>Minecraft &amp; dérivés (téléchargement du JAR + runtime Java)</td></tr>
      <tr><td><strong>Git</strong></td><td>Serveurs distribués par dépôt Git</td></tr>
      <tr><td><strong>Manuel</strong></td><td>Archive / exécutable fourni (jar, exe, sh, run)</td></tr>
      <tr><td><strong>Venice Unleashed</strong></td><td>Battlefield 3 (VU)</td></tr>
    </table>

    <h2 id="inst-flow">Le cycle d'installation</h2>
    <ol>
      <li>Vous créez le serveur : jeu, version, hôte, slots, ports.</li>
      <li>GameNodePanel se connecte en SSH et prépare le répertoire + le compte FTP.</li>
      <li>Le moteur adapté télécharge et installe les fichiers du jeu.</li>
      <li>Un éventuel <em>pre-start hook</em> finalise la configuration au premier démarrage.</li>
      <li>Le serveur apparaît prêt à être <a href="modules/gnp/doc-panel.php">piloté depuis son panel</a>.</li>
    </ol>
    <div class="callout"><span class="i">📡</span><div>Toute l'installation est <strong>streamée en direct</strong> (Server-Sent Events) : vous suivez chaque étape et chaque ligne de log sans recharger la page.</div></div>

    <h2 id="inst-steam">SteamCMD</h2>
    <p>Pour les jeux Steam, GameNodePanel s'assure d'abord que <strong>SteamCMD est installé</strong> sur l'hôte (sinon il l'installe), puis lance le téléchargement de l'app. Deux modes d'authentification :</p>
    <ul>
      <li><strong>Anonyme</strong> — pour les serveurs dédiés gratuits (la majorité).</li>
      <li><strong>Compte Steam</strong> — pour les jeux qui l'exigent, avec gestion du <strong>Steam Guard</strong>.</li>
    </ul>

    <h2 id="inst-steamguard">Steam Guard</h2>
    <p>Quand un compte Steam protégé est utilisé, SteamCMD réclame un <strong>code Steam Guard</strong>. L'installation se met en pause et le panel vous invite à saisir le code (route <code>trigger-steam-guard</code>) : une fois validé, le téléchargement reprend automatiquement.</p>
    <div class="callout"><span class="i">🔐</span><div>Les identifiants Steam ne servent qu'à l'installation et transitent de façon sécurisée. Privilégiez le mode anonyme quand le jeu le permet.</div></div>

    <h2 id="inst-docker">Docker</h2>
    <p>Si la version du jeu est de type <strong>conteneur</strong>, GameNodePanel utilise le moteur Docker : construction/préparation de l'image, création du conteneur, et un <strong>diagnostic</strong> intégré (santé, historique, logs). Le serveur hôte doit avoir Docker installé et actif (voir <a href="modules/gnp/doc-host.php#host-docker">Prérequis</a>). Des règles prêtes existent pour plusieurs jeux (Conan, Enshrouded, Abiotic Factor, Windrose…).</p>

    <h2 id="inst-java">Java / Minecraft</h2>
    <p>Pour Minecraft &amp; dérivés, GameNodePanel installe la <strong>bonne version de Java</strong> (depuis l'URL de téléchargement configurée pour la version) puis récupère le JAR du serveur. Le panel Minecraft prend ensuite le relais (mondes, properties, marketplace Modrinth…).</p>

    <h2 id="inst-git">Git</h2>
    <p>Le moteur Git clone le dépôt déclaré par la version du jeu directement sur l'hôte. Pratique pour des serveurs distribués sous forme de code source ou de scripts maintenus dans un repo.</p>

    <h2 id="inst-manual">Manuel</h2>
    <p>Le moteur manuel installe une <strong>archive ou un exécutable</strong> fourni (formats reconnus : <code>jar</code>, <code>exe</code>, <code>sh</code>, <code>run</code>). Idéal pour un jeu non distribué via Steam ni Docker, quand vous disposez du binaire ou de l'archive.</p>

    <h2 id="inst-vu">Venice Unleashed</h2>
    <p>Moteur dédié à <strong>Battlefield 3 (Venice Unleashed)</strong> : installation et activation spécifiques, suivies par le panel Battlefield (maps, settings, banlist, VIP, mods).</p>

    <h2 id="inst-live">Console live (SSE)</h2>
    <p>Pendant l'installation, une console affiche la progression en temps réel : pourcentage d'avancement et logs ligne par ligne. Les flux sont exposés par des routes <code>install-stream</code> / <code>...-install-stream</code> selon le moteur (Minecraft, Docker, Battlefield, Hytale, DST…).</p>

    <h2 id="inst-reinstall">Réinstaller</h2>
    <p>Un serveur peut être <strong>réinstallé</strong> (route <code>reinstall</code>) : utile après une corruption de fichiers ou un changement de version. Pensez à <a href="modules/gnp/doc-panel.php#panel-backups">sauvegarder</a> au préalable les configs et mondes que vous souhaitez conserver.</p>

    <?php require __DIR__ . '/../../inc/foot.php'; ?>
