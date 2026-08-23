<?php
$docPage = 'modules/gnp/doc-host.php';
$seo = ['title' => 'Prérequis serveur hôte — Documentation · GameNodePanel', 'desc' => "Ce qu'il faut côté VPS pour GameNodePanel : accès SSH, SteamCMD pour les jeux Steam, Docker pour les jeux conteneurisés, MySQL pour Database Manager.", 'canonical' => 'https://gamenodepanel.com/documentation/doc-host.php'];
require __DIR__ . '/../../inc/head.php';
?>
    <h1>Prérequis serveur hôte</h1>
    <p class="doc-lead">GameNodePanel pilote vos serveurs <strong>à distance, via SSH</strong>. Voici ce qui doit être disponible côté VPS / serveur dédié selon les fonctionnalités utilisées.</p>
    <div class="doc-meta"><span class="doc-pill">Debian / Ubuntu</span><span class="doc-pill">Accès SSH</span><span class="doc-pill">root ou sudoer</span></div>

    <h2 id="host-intro">Le serveur hôte</h2>
    <p>Un « serveur hôte » est une machine Linux (VPS, dédié) que GameNodePanel administre par SSH. Le panel s'y connecte pour installer les jeux, lire les logs, gérer MySQL, etc. <strong>Rien n'a besoin d'être exposé publiquement</strong> à part le port SSH.</p>
    <table class="doc-table">
      <tr><th>Besoin</th><th>Requis pour</th></tr>
      <tr><td>Accès SSH (root ou sudoer)</td><td>Tout (base)</td></tr>
      <tr><td>SteamCMD</td><td>Jeux Steam (CS2, Rust, Palworld…)</td></tr>
      <tr><td>Docker</td><td>Jeux conteneurisés (Conan, Enshrouded…)</td></tr>
      <tr><td>MySQL / MariaDB</td><td>Database Manager</td></tr>
    </table>

    <h2 id="host-ssh">SSH</h2>
    <p>C'est la fondation. GameNodePanel utilise <strong>phpseclib</strong> (pas de dépendance Composer) pour se connecter. Préparez :</p>
    <ul>
      <li>Un utilisateur <strong>root</strong> ou <strong>sudoer</strong> (l'installation de paquets l'exige).</li>
      <li>Authentification par mot de passe <em>ou</em> par clé privée SSH.</li>
      <li>Le port SSH (22 par défaut) accessible depuis le serveur du panel.</li>
    </ul>
    <div class="callout"><span class="i">🔐</span><div>Les identifiants SSH sont stockés <strong>chiffrés en AES-256</strong>. Les commandes sensibles transmettent leurs secrets via base64 sur stdin (jamais en clair sur la ligne de commande).</div></div>

    <h2 id="host-steamcmd">SteamCMD</h2>
    <p>Pour les jeux distribués via Steam, GameNodePanel installe et pilote <strong>SteamCMD</strong>. L'installation est streamée (SSE) : vous suivez la progression en direct dans le panel. Prévoyez l'espace disque et la bande passante nécessaires au jeu.</p>

    <h2 id="host-docker">Docker</h2>
    <p>Pour les jeux récents/exigeants, le panel Docker déploie chaque serveur dans un <strong>conteneur isolé</strong>. Le serveur hôte doit donc avoir <strong>Docker installé et le démon actif</strong>. Des règles prêtes existent (Conan Exiles, Enshrouded, Abiotic Factor, Windrose…).</p>
    <ul>
      <li>Docker Engine installé (<code>docker --version</code>).</li>
      <li>L'utilisateur SSH doit pouvoir exécuter Docker (groupe <code>docker</code> ou sudo).</li>
    </ul>

    <h2 id="host-mysql">MySQL / MariaDB</h2>
    <p>Pour <a href="modules/gnp/doc-database.php">Database Manager</a>, GameNodePanel peut <strong>installer MySQL/MariaDB automatiquement</strong> (apt/yum) sur un serveur vierge, ou se connecter à une instance existante. Le panel détecte le cas et configure le compte root en conséquence. Le port 3306 n'est jamais exposé : tout passe par SSH.</p>

    <h2 id="host-ips">IP failover &amp; adresses additionnelles</h2>
    <p>Un serveur hôte possède une <strong>IP principale</strong> (celle de sa connexion SSH). Beaucoup d'hébergeurs (OVH, Hetzner…) permettent d'ajouter des <strong>IP failover / additionnelles</strong> routées vers la même machine. GameNodePanel sait les <strong>répertorier</strong> pour que vous puissiez <strong>choisir, à la création d'un serveur de jeu, sur quelle IP l'exposer</strong>.</p>

    <h3 id="host-ips-gerer">Gérer les IP d'un hôte</h3>
    <p>Depuis la fiche d'un serveur hôte (<code>Game Node Panel → Serveurs hôtes → [votre hôte]</code>), la carte <strong>« 🌐 Adresses IP de l'hôte »</strong> liste :</p>
    <ul>
      <li>l'<strong>IP principale</strong> (lecture seule — c'est l'IP SSH de l'hôte) ;</li>
      <li>les <strong>IP additionnelles</strong> que vous ajoutez, avec un libellé facultatif (ex. <em>« Failover OVH #1 »</em>).</li>
    </ul>
    <p>Vous ajoutez ou supprimez une IP additionnelle directement depuis cette carte (ajout/suppression en AJAX, sans recharger la page).</p>
    <div class="callout"><span class="i">ℹ️</span><div>La gestion des IP se fait sur la <strong>fiche détail</strong> de l'hôte (<code>…/gestion-dedie/{id}</code>), pas sur le formulaire d'édition : c'est une liste dynamique, distincte de la configuration matérielle/SSH.</div></div>

    <h3 id="host-ips-choisir">Choisir l'IP à la création d'un serveur</h3>
    <p>Lorsque vous créez un serveur de jeu (<code>Serveurs de jeu → Créer</code>) et que vous sélectionnez un hôte, un menu déroulant <strong>« 🌐 Adresse IP »</strong> propose l'IP principale <em>et</em> toutes les IP failover de cet hôte. L'IP retenue est <strong>validée côté serveur</strong> (elle doit appartenir à l'hôte), <strong>mémorisée</strong> avec le serveur de jeu, puis <strong>affichée</strong> dans son panel.</p>

    <h3 id="host-ips-reseau">Côté réseau / OS</h3>
    <div class="callout"><span class="i">⚠️</span><div>GameNodePanel <strong>n'effectue aucune configuration réseau au niveau de l'OS</strong> et ne force pas l'IP dans la commande de lancement. Le rôle du panel est de <strong>répertorier, choisir et mémoriser</strong> l'IP. Le <strong>routage de l'IP failover vers l'hôte</strong> (et, si nécessaire, sa configuration dans <code>netplan</code> / <code>ip addr</code> ou le binding du serveur de jeu) reste à faire <strong>chez votre hébergeur / sur l'OS</strong>, selon le jeu.</div></div>
    <table class="doc-table">
      <tr><th>Fait par GameNodePanel</th><th>À votre charge (hébergeur / OS)</th></tr>
      <tr><td>Répertorier les IP d'un hôte</td><td>Commander / router l'IP failover</td></tr>
      <tr><td>Proposer le choix à la création</td><td>Configurer l'IP sur l'OS si besoin</td></tr>
      <tr><td>Mémoriser &amp; afficher l'IP choisie</td><td>Binder le jeu sur l'IP (selon le jeu)</td></tr>
    </table>

    <?php require __DIR__ . '/../../inc/foot.php'; ?>
