<?php
/**
 * documentation/modules/gne/doc-gne.php — GameNodeEsport, vue d'ensemble.
 */
$docPage = 'modules/gne/doc-gne.php';
$seo = [
    'title'     => 'GameNodeEsport — Vue d\'ensemble · Documentation Aegis Framework',
    'desc'      => "GameNodeEsport : le CMS des équipes et communautés gaming. Équipes, joueurs, matchs, actualités, recrutement, boutique, dons, streamers Twitch. Gratuit, avec deux thèmes offerts.",
    'canonical' => 'https://gamenodepanel.com/documentation/modules/gne/doc-gne.php',
];
require __DIR__ . '/../../inc/head.php';

$code_tree = <<<'TXT'
GameNodeEsport/
├── module.json          manifeste : menu, capacités, préfixe public
├── routes.php           205 routes — administration + site public
├── Controllers/         45 contrôleurs, un par écran
├── Services/            41 services, un par domaine
├── themes/              5 thèmes, un dossier chacun
│   └── <clé>/
│       ├── meta.json    identité + options déclarées
│       ├── *.html       les gabarits, en HTML pur
│       └── assets/      css · js · images · uploads
└── database/
    ├── install.sql      26 tables gne_
    └── uninstall.sql
TXT;
?>

    <h1>GameNodeEsport</h1>
    <p class="doc-lead">Le CMS des <strong>équipes et communautés gaming</strong> — de l'organisation professionnelle à la team entre amis, en passant par la communauté multi-gaming. Roster, matchs, actualités, recrutement, boutique, dons, streamers : tout ce qu'une structure a besoin de montrer.</p>
    <div class="doc-meta">
      <span class="doc-pill">Gratuit</span>
      <span class="doc-pill">26 tables gne_</span>
      <span class="doc-pill">205 routes</span>
      <span class="doc-pill">PHP 8.1+</span>
    </div>

    <div class="callout ok"><span class="i">🎁</span><div>
      <strong>GameNodeEsport est gratuit.</strong> Son prédécesseur, <em>eSport-CMS</em>, était vendu 29,90 €. Le logiciel complet, ses 26 tables, ses vingt écrans d'administration et <strong>deux thèmes</strong> ne coûtent désormais rien. Seuls les <strong>thèmes premium</strong> sont payants, sur <a href="https://lavelia-studio.com" target="_blank" rel="noopener">lavelia-studio.com</a>.
    </div></div>

    <h2 id="gne-pour-qui">Pour qui</h2>
    <table class="doc-table">
      <tr><th>Profil</th><th>Ce que le module lui apporte</th></tr>
      <tr><td><strong>Équipe professionnelle</strong></td><td>Roster par jeu, calendrier de matchs et résultats, palmarès, partenaires et sponsors, boutique de goodies, page de recrutement.</td></tr>
      <tr><td><strong>Team amateur</strong></td><td>La même chose, sans avoir à coder : on remplit des formulaires, le site se construit.</td></tr>
      <tr><td><strong>Communauté multi-gaming</strong></td><td>Plusieurs jeux, plusieurs équipes, un annuaire de membres, des sondages, une galerie, des vidéos et les streamers de la commu.</td></tr>
    </table>

    <h2 id="gne-fonctions">Ce que le module couvre</h2>
    <table class="doc-table">
      <tr><th>Domaine</th><th>Fonctions</th><th>Détail</th></tr>
      <tr><td>🏆 Compétition</td><td>Jeux, équipes, joueurs, configurations PC, marques matériel, matchs et statistiques</td><td><a href="modules/gne/doc-gne-equipes.php">Équipes, joueurs &amp; matchs</a></td></tr>
      <tr><td>📰 Contenu</td><td>Actualités, pages, galerie photo, vidéos, sondages, streamers Twitch, recrutement, dons, menu, sliders, widgets</td><td><a href="modules/gne/doc-gne-contenu.php">Contenus &amp; communauté</a></td></tr>
      <tr><td>🛒 Boutique</td><td>Catalogue, panier, commande, paiement, livraison, espace membre et commandes</td><td><a href="modules/gne/doc-gne-boutique.php">Boutique &amp; espace membre</a></td></tr>
      <tr><td>🎨 Apparence</td><td>Cinq thèmes, options déclarées, repli par gabarit, téléversement de thèmes</td><td><a href="modules/gne/doc-gne-themes.php">Les thèmes</a></td></tr>
      <tr><td>⚖️ Positionnement</td><td>Face à NeoFrag et Contentify</td><td><a href="modules/gne/doc-gne-comparatif.php">Comparatif</a></td></tr>
    </table>

    <h2 id="gne-archi">Architecture</h2>
    <div class="tree"><?= $h($code_tree) ?></div>
    <p>GameNodeEsport est un <strong>module d'Aegis Framework</strong>, pas une application isolée. Il n'apporte ni son authentification, ni son cache, ni son SEO, ni son bandeau cookies : il déclare les <a href="framework/doc-capabilities.php">capacités</a> dont il a besoin — <strong>Markdown, Cache, SEO, RGPD, Analytics</strong> — et le framework fournit.</p>
    <div class="callout"><span class="i">🧩</span><div>Conséquence directe : un site GameNodeEsport peut recevoir un <strong>forum</strong>, un système de <strong>tournois</strong>, un <strong>blog</strong> ou une <strong>marketplace</strong> en activant les modules correspondants d'Aegis. Ce ne sont pas des greffons à écrire, ils existent déjà à côté.</div></div>

    <h2 id="gne-ecosysteme">L'écosystème autour</h2>
    <p>C'est ce qui distingue le plus GameNodeEsport d'un CMS esport classique : le même socle fait tourner d'autres métiers, sur la même base d'utilisateurs.</p>
    <table class="doc-table">
      <tr><th>Module voisin</th><th>Ce qu'il ajoute au site de l'équipe</th></tr>
      <tr><td><a href="modules/forum/doc-forum.php">Forum</a></td><td>Les discussions de la communauté</td></tr>
      <tr><td>Tournaments</td><td>L'organisation de tournois et de brackets</td></tr>
      <tr><td><a href="modules/gnp/doc-gnp.php">GameNodePanel</a></td><td>L'exploitation réelle des serveurs de jeu</td></tr>
      <tr><td><a href="modules/gnh/doc-gnh.php">GameNodeHosting</a></td><td>La <strong>location</strong> de serveurs — l'équipe devient hébergeur</td></tr>
      <tr><td><a href="modules/analytics/doc-analytics.php">Analytics</a></td><td>La mesure d'audience, sans cookie</td></tr>
    </table>

    <h2 id="gne-install">Installation</h2>
    <ol class="steps">
      <li>Déposez le dossier <code>GameNodeEsport/</code> dans <code>modules/</code>, ou importez le ZIP depuis <em>Administration → Modules</em>.</li>
      <li>Activez le module : les 26 tables <code>gne_</code> sont créées et le thème <strong>Gratuit</strong> devient actif.</li>
      <li>Renseignez <em>GameNode eSport → Réglages</em> : nom du site, description, contact.</li>
      <li>Créez vos <strong>jeux</strong>, puis vos <strong>équipes</strong> et vos <strong>joueurs</strong>. Le site public répond déjà.</li>
      <li>Choisissez votre thème et réglez ses options.</li>
    </ol>
    <div class="callout"><span class="i">🔗</span><div>Le site visiteur répond sous le préfixe <code>/gamenodeesport</code>, <strong>renommable</strong> depuis <em>Administration → Modules</em>. Sur ce site de démonstration, il a été raccourci en <code>/fr</code>.</div></div>

    <h2 id="gne-menu">Les vingt écrans d'administration</h2>
    <p>Le module s'installe en <strong>méga-menu</strong> sous « 🧩 GameNode eSport » :</p>
    <p>Tableau de bord · Jeux · Équipes · Sliders · Matchs · Partenaires · Pages · Actualités · Recrutement · Marques matériel · Menu · Thèmes · Widgets · Album photo · Dons · Sondages · Vidéos · Streamers Twitch · Boutique · Commandes · Réglages</p>

    <h2 id="gne-tables">Les 26 tables</h2>
    <table class="doc-table">
      <tr><th>Domaine</th><th>Tables</th></tr>
      <tr><td>Réglages</td><td><code>gne_settings</code></td></tr>
      <tr><td>Compétition</td><td><code>gne_games</code>, <code>gne_teams</code>, <code>gne_players</code>, <code>gne_matches</code>, <code>gne_pc_configs</code>, <code>gne_hardware_brands</code></td></tr>
      <tr><td>Contenu</td><td><code>gne_news</code>, <code>gne_pages</code>, <code>gne_sliders</code>, <code>gne_widgets</code>, <code>gne_menu_items</code>, <code>gne_partners</code></td></tr>
      <tr><td>Médias</td><td><code>gne_photo_categories</code>, <code>gne_photos</code>, <code>gne_videos</code>, <code>gne_twitch_streamers</code></td></tr>
      <tr><td>Communauté</td><td><code>gne_polls</code>, <code>gne_poll_votes</code>, <code>gne_presence</code>, <code>gne_visit_log</code></td></tr>
      <tr><td>Recrutement</td><td><code>gne_recruitment_offers</code>, <code>gne_recruitment_applications</code></td></tr>
      <tr><td>Dons</td><td><code>gne_donation_campaigns</code>, <code>gne_donations</code></td></tr>
      <tr><td>Boutique</td><td><code>gne_shop_categories</code> (+ produits et commandes)</td></tr>
    </table>
    <div class="callout"><span class="i">🏷️</span><div>Comme tout module Aegis, GameNodeEsport porte son <strong>propre préfixe de tables</strong>. Deux modules ne se mélangent jamais, et désinstaller l'un ne touche pas l'autre.</div></div>

    <div class="doc-foot">
      <span>GameNodeEsport v1.4.0 · gratuit</span>
      <span><a href="modules/gne/doc-gne-equipes.php">Équipes, joueurs &amp; matchs →</a></span>
    </div>

<?php require __DIR__ . '/../../inc/foot.php'; ?>
