<?php
$docPage = 'modules/gnp/doc-panel.php';
$seo = ['title' => 'Gérer un serveur de jeu — Documentation · GameNodePanel', 'desc' => "Le panel de gestion d'un serveur de jeu GameNodePanel : console live, commandes, gestionnaire de fichiers, sauvegardes, éditeurs de configuration et panels spécialisés (Minecraft, TeamSpeak, FiveM…).", 'canonical' => 'https://gamenodepanel.com/documentation/doc-panel.php'];
require __DIR__ . '/../../inc/head.php';
?>
    <h1>Gérer un serveur de jeu</h1>
    <p class="doc-lead">Une fois un serveur installé, le <strong>panel</strong> est votre poste de pilotage : démarrer/arrêter, ouvrir la console, éditer les fichiers, sauvegarder, gérer joueurs et plugins — le tout à distance via SSH, sans toucher au terminal.</p>
    <div class="doc-meta"><span class="doc-pill">Console live</span><span class="doc-pill">Fichiers</span><span class="doc-pill">Sauvegardes</span><span class="doc-pill">Par type de jeu</span></div>

    <h2 id="panel-intro">Le panel</h2>
    <p>Chaque serveur de jeu dispose d'un panel accessible depuis sa fiche (<code>/admin/gamenodepanel/game-servers/{id}</code>). Le panel regroupe toutes les actions du quotidien dans une interface unique. Selon le type de jeu, certains onglets spécifiques apparaissent (mondes Minecraft, salons TeamSpeak, ressources FiveM…), mais le socle commun est toujours présent.</p>
    <div class="callout"><span class="i">💡</span><div>Le panel s'adapte automatiquement au jeu : vous n'avez rien à configurer. GameNodePanel choisit le bon « moteur de panel » à partir de la catégorie et de la version du jeu.</div></div>

    <h2 id="panel-dispatch">Un panel par type de jeu</h2>
    <p>Un routeur interne (<code>GamePanelRouter</code>) dirige chaque serveur vers le panel adapté. Si la version du jeu tourne en <strong>Docker</strong>, c'est toujours le panel Docker qui prend la main ; sinon le routage se fait selon le jeu :</p>
    <table class="doc-table">
      <tr><th>Type / catégorie</th><th>Panel utilisé</th><th>Spécificités</th></tr>
      <tr><td>FPS, survie, sandbox, racing, MMO, autre</td><td><strong>Standard</strong></td><td>Console, fichiers, sauvegardes, config</td></tr>
      <tr><td>Minecraft</td><td><strong>Minecraft</strong></td><td>Versions, mondes, properties, whitelist, ops, marketplace Modrinth</td></tr>
      <tr><td>Vocal (TeamSpeak)</td><td><strong>Vocal</strong></td><td>Salons, tokens, clients, kick/ban/move</td></tr>
      <tr><td>FiveM / RedM / CfX</td><td><strong>CfX</strong></td><td>Ressources, GSLT, joueurs, config</td></tr>
      <tr><td>Battlefield 3 (Venice)</td><td><strong>Battlefield</strong></td><td>Maps, settings, banlist, VIP, mods</td></tr>
      <tr><td>Don't Starve Together</td><td><strong>DST</strong></td><td>Cluster, token, mondes</td></tr>
      <tr><td>Hytale</td><td><strong>Hytale</strong></td><td>Installation &amp; config dédiées</td></tr>
      <tr><td><em>Version en conteneur</em></td><td><strong>Docker</strong></td><td>Diagnostic, health, history, live-FTP, recreate</td></tr>
    </table>

    <h2 id="panel-power">Démarrer / Arrêter</h2>
    <p>Les actions d'alimentation sont disponibles partout : <strong>Démarrer</strong>, <strong>Arrêter</strong>, <strong>Redémarrer</strong> et, pour Docker, <strong>recréer le conteneur</strong>. Le statut du serveur et ses stats temps réel (CPU/RAM, joueurs) sont rafraîchis en direct.</p>
    <ul>
      <li>Actions individuelles depuis la fiche du serveur.</li>
      <li><strong>Action groupée</strong> (« bulk ») pour démarrer/arrêter plusieurs serveurs d'un coup.</li>
      <li>Un <em>pre-start hook</em> spécifique au jeu peut s'exécuter avant le lancement (Ark, Palworld, 7DtD, Minecraft, CfX, TeamSpeak…) pour préparer fichiers et paramètres.</li>
    </ul>

    <h2 id="panel-console">Console &amp; commandes</h2>
    <p>La console affiche les <strong>logs en temps réel</strong> et permet d'<strong>envoyer des commandes</strong> au serveur (façon RCON), de vider l'affichage et de consulter le système Docker le cas échéant.</p>
    <table class="doc-table">
      <tr><th>Action</th><th>Rôle</th></tr>
      <tr><td>Logs live</td><td>Flux de sortie du serveur, auto-rafraîchi</td></tr>
      <tr><td>Envoyer une commande</td><td>Transmet une commande au jeu (kick, say, save…)</td></tr>
      <tr><td>Effacer</td><td>Nettoie la console affichée</td></tr>
      <tr><td>Stats</td><td>CPU, RAM, joueurs connectés</td></tr>
    </table>

    <h2 id="panel-files">Fichiers &amp; FTP</h2>
    <p>Deux façons de gérer les fichiers du serveur, sans client externe :</p>
    <ul>
      <li><strong>Navigateur de fichiers web</strong> (<code>ftp-browser</code>) : lister, <strong>éditer en ligne</strong>, créer dossier/fichier, <strong>upload</strong>, <strong>download</strong>, renommer et supprimer.</li>
      <li><strong>Comptes FTP</strong> dédiés : création automatique à l'installation, activation/désactivation, réinitialisation du mot de passe et nettoyage des comptes orphelins.</li>
    </ul>
    <p>Pour les serveurs Docker, un <strong>Live-FTP</strong> équivalent agit directement dans le conteneur (list, upload, download, read/save, create, delete, rename).</p>
    <div class="callout"><span class="i">🔐</span><div>Tous les accès passent par la connexion SSH chiffrée du serveur hôte. Les chemins sont contrôlés pour rester dans le répertoire du serveur de jeu.</div></div>

    <h2 id="panel-backups">Sauvegardes</h2>
    <p>Le panel permet de <strong>créer</strong>, <strong>supprimer</strong> et <strong>restaurer</strong> des sauvegardes par serveur. Selon le jeu, la sauvegarde cible les éléments utiles (configs, addons, maps, mondes). Pour les mondes, des actions dédiées existent : <strong>réinitialiser</strong> ou <strong>archiver</strong> un monde.</p>
    <p>Un cron (<code>cron_backups</code>) peut automatiser ces sauvegardes — voir <a href="modules/gnp/doc-cron.php">Cron &amp; tâches planifiées</a>.</p>

    <h2 id="panel-config">Configuration &amp; éditeurs</h2>
    <p>Plutôt qu'éditer des fichiers à la main, le panel propose des <strong>éditeurs structurés</strong> selon le jeu :</p>
    <table class="doc-table">
      <tr><th>Éditeur</th><th>Disponible pour</th></tr>
      <tr><td><code>server.properties</code>, whitelist, ops, server-icon, EULA</td><td>Minecraft</td></tr>
      <tr><td>Map rotation, maps, banlist, settings, VIP, mods</td><td>Battlefield / jeux à rounds</td></tr>
      <tr><td>Config CfX, ressources, GSLT</td><td>FiveM / RedM</td></tr>
      <tr><td>Cluster, token, mondes</td><td>Don't Starve Together</td></tr>
      <tr><td>Configuration générique &amp; game-config</td><td>Tous (panel standard)</td></tr>
    </table>

    <h2 id="panel-specialized">Panels spécialisés</h2>
    <p>Quelques jeux justifient des onglets dédiés. En voici les plus complets.</p>

    <h3 id="panel-mc">Minecraft</h3>
    <ul>
      <li>Gestion des <strong>versions</strong> et des <strong>mondes</strong>, édition de <code>server.properties</code>.</li>
      <li><strong>Whitelist</strong>, <strong>ops</strong>, <strong>IP bannies</strong>, <strong>icône de serveur</strong>, acceptation de l'<strong>EULA</strong>.</li>
      <li><strong>Marketplace Modrinth intégré</strong> : recherche, installation/désinstallation de mods &amp; plugins, installation d'un launcher, sauvegardes et restauration — directement dans le panel.</li>
    </ul>

    <h3 id="panel-voice">Vocal / TeamSpeak</h3>
    <ul>
      <li><strong>Salons</strong> (créer/supprimer), <strong>tokens</strong> (créer/supprimer), <strong>clients</strong> connectés.</li>
      <li>Modération : <strong>kick</strong>, <strong>ban</strong>, <strong>unban</strong>, <strong>déplacer</strong> un client, <strong>message</strong> serveur.</li>
      <li>Identifiants ServerQuery, statut, bande passante, bannière live et <strong>viewer</strong> intégrable.</li>
    </ul>

    <h3 id="panel-cfx">FiveM / CfX</h3>
    <ul>
      <li>Liste des <strong>ressources</strong>, édition de la <strong>config</strong>, enregistrement du <strong>GSLT</strong>.</li>
      <li>Suivi des <strong>joueurs</strong> et du statut serveur en direct.</li>
    </ul>

    <h2 id="panel-vega">Santé VEGA</h2>
    <p>Depuis le panel, chaque serveur expose un onglet <strong>VEGA</strong> : récupération des logs, <strong>analyse IA</strong> (et ré-analyse), score de <strong>santé</strong> et historique, gestion des <strong>incidents</strong> et des <strong>patterns</strong> détectés. C'est la porte d'entrée vers la supervision intelligente détaillée dans <a href="modules/gnp/doc-vega.php">VEGA (technique)</a>.</p>

    <?php require __DIR__ . '/../../inc/foot.php'; ?>
