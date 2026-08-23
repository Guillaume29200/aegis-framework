<?php
$docPage = 'modules/gnv/doc-gnv-usage.php';
$seo = [
    'title' => 'GameNodeViewer — Admin & viewer public · Documentation Aegis Framework',
    'desc'  => "Utiliser GameNodeViewer au quotidien : serveurs, registre de jeux & matrice, maps multi-jeux, réglages, historique & cron, widgets embeddables, API JSON, SEO et thèmes.",
    'canonical' => 'https://gamenodepanel.com/documentation/modules/gnv/doc-gnv-usage.php',
];
require __DIR__ . '/../../inc/head.php';
?>
    <h1>Admin &amp; viewer public</h1>
    <p class="doc-lead">Tout ce qu'on pilote depuis <code>/admin/gamenodeviewer</code> et ce que voient vos visiteurs sur <code>/gamenodeviewer</code>.</p>

    <h2 id="u-servers">Serveurs</h2>
    <p><strong>Admin → Serveurs</strong> : ajout/édition d'un serveur (nom optionnel, jeu, hôte/IP, port, port query optionnel, ordre, actif). Le port se pré-remplit selon le jeu. Bouton <strong>« Tester maintenant »</strong> pour une interrogation live.</p>
    <div class="callout"><span class="i">💬</span><div>Pour <strong>Discord</strong>, le champ « hôte » est le <strong>code d'invitation</strong> (ex. <code>aBcDeF</code> ou l'URL <code>discord.gg/…</code>).</div></div>

    <h2 id="u-games">Jeux &amp; matrice d'affichage</h2>
    <p><strong>Admin → Jeux</strong> : le registre jeu → protocole. Pour chaque jeu : libellé, clé, protocole (liste auto-remplie depuis les protocoles découverts), port par défaut, <strong>upload d'icône</strong> (ré-encodée GD), et la section <strong>« Détails d'affichage »</strong> où vous cochez les informations visibles. Les scores des joueurs sont triés du plus haut au plus bas.</p>

    <h2 id="u-maps">Maps (multi-jeux)</h2>
    <p><strong>Admin → Maps</strong> : toutes les maps sont affichées avec leur jeu. Bouton <strong>« Ajouter une map »</strong> → nom exact (ex. <code>de_dust2</code>) + image (JPG/PNG, ré-encodée GD) + <strong>cases à cocher des jeux concernés</strong>. Une map partagée (CS 1.6 / CZ / Source / CS2…) n'est uploadée qu'<strong>une fois</strong> et associée à tous les jeux choisis.</p>
    <div class="callout ok"><span class="i">🖼️</span><div>Le viewer affiche l'image quand le serveur tourne sur cette map. Résolution : entrée en base, puis repli sur un fichier <code>Assets/maps/&lt;jeu&gt;/&lt;map&gt;.jpg</code>.</div></div>

    <h2 id="u-settings">Réglages</h2>
    <table class="doc-table">
      <tr><th>Section</th><th>Réglages</th></tr>
      <tr><td>Viewer public</td><td>Titre, accroche</td></tr>
      <tr><td>Performance &amp; cache</td><td>Cache on/off, TTL, timeout d'interrogation</td></tr>
      <tr><td>Historique</td><td>Activation, rétention (jours), URL du cron de relevé</td></tr>
      <tr><td>Alertes Discord</td><td>Activation + URL du webhook (notifie up/down)</td></tr>
      <tr><td>Géolocalisation</td><td>Active la carte du monde &amp; les drapeaux</td></tr>
      <tr><td>SEO</td><td>Description meta, image OG, sitemap</td></tr>
    </table>

    <h2 id="u-history">Historique &amp; cron</h2>
    <p>L'historique alimente les graphiques (activité &amp; uptime), le pic de joueurs et le sparkline. Il se remplit à chaque requête réelle, mais pour un uptime fiable <strong>même sans visiteurs</strong>, planifiez le cron de relevé (affiché dans Réglages) :</p>
    <pre><code>*/5 * * * * curl -s "https://votresite/gamenodeviewer/cron/poll?key=VOTRECLE" &gt;/dev/null</code></pre>
    <p>Ce cron interroge tous les serveurs actifs, enregistre les snapshots, envoie les <strong>alertes Discord</strong> sur transition d'état, puis purge selon la rétention.</p>

    <h2 id="u-widget">Widget embeddable</h2>
    <p>Sur chaque fiche serveur, un encart <strong>« Embarquer »</strong> fournit deux intégrations à coller ailleurs (forums, blogs) → backlinks &amp; viralité :</p>
    <table class="doc-table">
      <tr><th>Type</th><th>URL</th><th>Format</th></tr>
      <tr><td>Badge image (SVG)</td><td><code>/gamenodeviewer/server/{id}/badge.svg</code></td><td><code>?format=h</code> (horizontal) ou <code>?format=v</code> (vertical)</td></tr>
      <tr><td>Widget live (iframe)</td><td><code>/gamenodeviewer/server/{id}/widget</code></td><td>carte HTML autonome</td></tr>
    </table>

    <h2 id="u-api">API JSON publique</h2>
    <p><code>GET /gamenodeviewer/api/server/{id}</code> renvoie l'état temps réel (CORS ouvert) pour les développeurs :</p>
    <pre><code>{ "ok": true, "online": true, "hostname": "...", "map": "de_dust2",
  "players": 18, "maxPlayers": 32, "players_list": [...], "queryTimeMs": 21 }</code></pre>

    <h2 id="u-seo">SEO &amp; sitemap</h2>
    <ul>
      <li>Meta <strong>description</strong>, <strong>canonical</strong>, <strong>Open Graph</strong> &amp; <strong>Twitter card</strong> dynamiques (la fiche serveur génère « Hostname — X/Y joueurs sur map · jeu »).</li>
      <li><code>/gamenodeviewer/sitemap.xml</code> (accueil + chaque fiche) et <code>/gamenodeviewer/robots.txt</code>, activables dans les réglages.</li>
    </ul>

    <h2 id="u-themes">Thèmes</h2>
    <p><strong>Admin → Thèmes</strong> : le viewer public est thémable. Un thème = un dossier sous <code>Views/themes/&lt;clé&gt;/</code> (avec <code>theme.json</code>). Import par <strong>archive ZIP</strong> (anti zip-slip), activation, suppression. Le thème <code>default</code> est sombre. Le bandeau <strong>RGPD</strong> et le traceur <strong>Analytics</strong> (si le module est actif) sont injectés automatiquement.</p>

    <div class="callout"><span class="i">📚</span><div>Pour étendre le moteur, voir <a href="modules/gnv/doc-gnv-protocol.php">Créer un protocole</a>. Pour l'architecture, voir <a href="modules/gnv/doc-gnv.php">Vue d'ensemble</a>.</div></div>

    <?php require __DIR__ . '/../../inc/foot.php'; ?>
