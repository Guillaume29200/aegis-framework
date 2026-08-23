<?php
$docPage = 'modules/gnv/doc-gnv.php';
$seo = [
    'title' => 'GameNodeViewer — Vue d\'ensemble · Documentation Aegis Framework',
    'desc'  => "GameNodeViewer : le viewer temps réel de serveurs de jeu d'Aegis. Multi-protocoles auto-découverts, matrice d'affichage configurable, cache, historique, carte du monde, widgets embeddables et SEO.",
    'canonical' => 'https://gamenodepanel.com/documentation/modules/gnv/doc-gnv.php',
];
require __DIR__ . '/../../inc/head.php';
?>
    <h1>GameNodeViewer — Vue d'ensemble</h1>
    <p class="doc-lead"><strong>GameNodeViewer</strong> (GNV) est un <strong>viewer de serveurs de jeu en temps réel</strong> : il interroge vos serveurs (joueurs, map, ping, localisation…) pour des dizaines de jeux et les affiche sur un site public thémable. Full FR, sécurisé, et pensé pour être <strong>étendu sans toucher au cœur</strong>.</p>
    <div class="doc-meta"><span class="doc-pill">Multi-protocoles</span><span class="doc-pill">Auto-découverte</span><span class="doc-pill">Cache + historique</span><span class="doc-pill">Thémable (ZIP)</span><span class="doc-pill">RGPD + Analytics</span></div>

    <div class="callout"><span class="i">🧩</span><div>GNV est l'évolution moderne et POO de l'ancien <strong>GSHQ</strong> (code procédural). Mêmes bonnes idées, mais en architecture MVC Aegis : services injectés, protocoles auto-découverts, matrice d'affichage administrable, sécurité au cœur.</div></div>

    <h2 id="gnv-intro">Qu'est-ce que GameNodeViewer ?</h2>
    <p>Un module Aegis qui se compose de deux faces :</p>
    <ul>
      <li><strong>Une partie admin</strong> (<code>/admin/gamenodeviewer</code>) : dashboard, CRUD serveurs, registre de jeux, maps, carte du monde, cache, thèmes, réglages.</li>
      <li><strong>Un viewer public</strong> (<code>/gamenodeviewer</code>) : liste des serveurs avec recherche multi-critères, et une fiche détaillée par serveur (joueurs, map + image, ping, GeoIP, graphiques, widget embeddable).</li>
    </ul>

    <h2 id="gnv-vs">Pourquoi pas GameQ / LGSL ?</h2>
    <table class="doc-table">
      <tr><th></th><th>GameQ / LGSL</th><th>GameNodeViewer</th></tr>
      <tr><td>Architecture</td><td>Procédural figé</td><td>POO MVC, services injectés</td></tr>
      <tr><td>Ajouter un protocole</td><td>Éditer le cœur</td><td><strong>Déposer 1 fichier</strong> (auto-découvert)</td></tr>
      <tr><td>Champs par jeu</td><td>Codés en dur</td><td>Matrice cochable en admin</td></tr>
      <tr><td>Maps</td><td>Dossiers manuels</td><td>Upload sécurisé + détection auto</td></tr>
      <tr><td>Carte monde / historique</td><td>❌</td><td>✅ Leaflet + graphiques</td></tr>
      <tr><td>Widgets / SEO / RGPD</td><td>❌</td><td>✅ intégrés</td></tr>
    </table>

    <h2 id="gnv-archi">Architecture</h2>
    <div class="tree">modules/GameNodeViewer/
├─ Net/Net.php                 ← UDP / TCP / HTTP (couche réseau)
├─ Protocol/
│  ├─ ProtocolInterface.php    ← le contrat
│  ├─ ProtocolRegistry.php     ← découverte automatique
│  ├─ ServerInfo.php           ← résultat typé
│  ├─ BinaryReader.php / Text.php
│  └─ *Protocol.php            ← 1 fichier = 1 protocole
├─ Services/   Query · Cache · Server · Game · Map · History · GeoIp · Settings · Theme · Notify
├─ Support/FieldCatalog.php    ← matrice d'affichage
├─ Controllers/  Admin/* + Public/*
└─ Views/  admin/* + themes/&lt;actif&gt;/*</div>
    <p>Le cœur est <code>QueryService</code> : il résout le protocole d'un serveur, sert le cache, mesure le temps, délègue au moteur de protocole et enregistre un snapshot d'historique.</p>

    <h2 id="gnv-flow">Cycle d'une requête</h2>
    <ol class="steps">
      <li>Le viewer demande l'état d'un serveur à <code>QueryService::query()</code>.</li>
      <li>Si une entrée <strong>cache</strong> fraîche existe → on la renvoie (aucune requête réseau).</li>
      <li>Sinon, <code>ProtocolRegistry</code> fournit la classe du protocole (ex. <code>source</code> → <code>SourceProtocol</code>).</li>
      <li>Le protocole interroge le serveur via <code>Net</code> et remplit un <code>ServerInfo</code> typé.</li>
      <li>Si en ligne → mise en cache ; un <strong>snapshot</strong> d'historique est enregistré.</li>
      <li>La vue affiche les champs <strong>activés dans la matrice</strong> et réellement renseignés.</li>
    </ol>

    <h2 id="gnv-protocols">Protocoles supportés</h2>
    <table class="doc-table">
      <tr><th>Clé</th><th>Couvre</th><th>Méthode</th></tr>
      <tr><td><code>source</code></td><td>~35 jeux Valve &amp; dérivés (CS, TF2, Rust, ARK, Valheim…)</td><td>A2S UDP (+ challenge, joueurs)</td></tr>
      <tr><td><code>quake3</code></td><td>MOHAA, JK2, ET, Urban Terror, CoD2…</td><td>getstatus UDP</td></tr>
      <tr><td><code>cod4</code></td><td>Call of Duty 4</td><td>getstatus (hérite de quake3)</td></tr>
      <tr><td><code>fivem</code></td><td>FiveM / RedM</td><td>HTTP (dynamic/info/players.json)</td></tr>
      <tr><td><code>minecraft</code></td><td>Minecraft Java</td><td>Server List Ping (TCP + VarInt)</td></tr>
      <tr><td><code>discord</code></td><td>Discord</td><td>API d'invitation (membres/online)</td></tr>
    </table>
    <div class="callout ok"><span class="i">➕</span><div>Ajouter un protocole = créer <strong>une classe</strong> dans <code>Protocol/</code>. Voir <a href="modules/gnv/doc-gnv-protocol.php">Créer un protocole</a>.</div></div>

    <h2 id="gnv-matrix">La matrice d'affichage</h2>
    <p>Chaque jeu n'expose pas les mêmes infos. Plutôt qu'une matrice codée en dur (comme GSHQ), GNV stocke par jeu la liste des <strong>champs activés</strong> (<code>gnv_games.fields</code>, JSON). En l'absence de configuration, les <strong>défauts du protocole</strong> s'appliquent. Un champ s'affiche si : <em>(coché pour le jeu)</em> <strong>ET</strong> <em>(réellement renvoyé par le serveur)</em>.</p>
    <p>Catalogue : hostname, type privé/public, joueurs/slots, map, <strong>image de map</strong>, OS, VAC, bots, type de partie, version, Steam, liste joueurs, <strong>score/kills</strong>, <strong>ping</strong>, localisation, latence.</p>

    <h2 id="gnv-cache">Cache &amp; historique</h2>
    <ul>
      <li><strong>Cache fichier</strong> (<code>CacheService</code>) : TTL réglable, sécurisé par <code>.htaccess</code>, vidable depuis l'admin.</li>
      <li><strong>Historique</strong> (<code>HistoryService</code>, table <code>gnv_history</code>) : snapshots joueurs/online → graphiques <em>activité</em> &amp; <em>uptime</em>, pic de joueurs, sparkline. Alimenté à chaque requête réelle et par le <strong>cron de relevé</strong>.</li>
    </ul>

    <h2 id="gnv-secu">Sécurité</h2>
    <ul>
      <li><strong>CSRF</strong> sur toutes les actions admin ; SQL <strong>préparé</strong> ; sorties échappées.</li>
      <li><strong>Uploads</strong> (icônes, maps) : validation MIME + <strong>ré-encodage GD</strong> (purge tout payload), thèmes ZIP <strong>anti zip-slip</strong>.</li>
      <li>Aucun mot de passe RCON stocké : seules les <strong>infos publiques</strong> sont lues.</li>
      <li>Endpoint cron protégé par <strong>clé secrète</strong> ; API JSON en lecture seule.</li>
    </ul>

    <?php require __DIR__ . '/../../inc/foot.php'; ?>
