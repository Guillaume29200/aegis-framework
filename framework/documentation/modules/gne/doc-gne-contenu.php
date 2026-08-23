<?php
/**
 * documentation/modules/gne/doc-gne-contenu.php — Contenus et vie de la communauté.
 */
$docPage = 'modules/gne/doc-gne-contenu.php';
$seo = [
    'title'     => 'GameNodeEsport — Contenus & communauté · Documentation Aegis Framework',
    'desc'      => "Actualités, pages, galerie photo, vidéos, sondages, streamers Twitch, recrutement, cagnottes de dons, partenaires, menu, sliders et widgets de GameNodeEsport.",
    'canonical' => 'https://gamenodepanel.com/documentation/modules/gne/doc-gne-contenu.php',
];
require __DIR__ . '/../../inc/head.php';
?>

    <h1>Contenus &amp; communauté</h1>
    <p class="doc-lead">Une équipe qui ne publie rien est une équipe qu'on oublie. Voici tout ce que GameNodeEsport met à disposition pour faire vivre le site entre deux matchs.</p>
    <div class="doc-meta">
      <span class="doc-pill">11 domaines</span>
      <span class="doc-pill">Markdown</span>
      <span class="doc-pill">Twitch en direct</span>
    </div>

    <h2 id="gnec-news">Actualités</h2>
    <p>Un blog complet : brouillon et publication, image de couverture, résumé, contenu en <strong>Markdown</strong> (capacité du framework). Deux pages publiques — la liste et l'article, adressé par son identifiant d'URL.</p>

    <h2 id="gnec-pages">Pages</h2>
    <p>Des pages libres, adressées par <code>/page/{slug}</code>, éditées en Markdown. C'est là que vivent les mentions légales, la charte de la team, l'historique de la structure.</p>

    <h2 id="gnec-menu">Le menu</h2>
    <p>Entièrement administrable, sur <strong>deux niveaux</strong> (un parent et ses enfants). Trois natures d'entrées cohabitent : les <strong>pages système</strong> du module (équipes, matchs, actualités…), vos <strong>pages personnalisées</strong>, et des <strong>liens externes</strong>.</p>

    <h2 id="gnec-sliders">Sliders</h2>
    <p>Les slides du bandeau d'accueil : image, titre, texte, bouton. Activables une à une et réordonnables.</p>
    <div class="callout"><span class="i">🔤</span><div>Un détail d'accessibilité corrigé en cours de route : quand plusieurs slides portaient un titre, la page comptait <strong>autant de <code>&lt;h1&gt;</code> que de slides</strong>. Seul le premier reste un <code>h1</code>. Et si le bandeau est désactivé, un titre invisible prend le relais — une page sans <code>h1</code> est une page mal indexée.</div></div>

    <h2 id="gnec-widgets">Les widgets</h2>
    <p>Des blocs activables, placés dans une <strong>zone</strong> et limités à une portée de page. Quatre zones : <em>haut d'accueil</em>, <em>colonne latérale</em>, <em>bas d'accueil</em> et <em>pied de page</em>.</p>
    <table class="doc-table">
      <tr><th>Widget</th><th>Contenu</th></tr>
      <tr><td>👥 Stats communauté</td><td>Membres en ligne, visiteurs du jour, total des inscrits</td></tr>
      <tr><td>⚔️ Prochains matchs</td><td>Les rencontres à venir avec leur date</td></tr>
      <tr><td>📰 Dernières actualités</td><td>Les derniers articles publiés</td></tr>
      <tr><td>🤝 Bandeau partenaires</td><td>Les logos de vos partenaires</td></tr>
      <tr><td>📊 Sondage en cours</td><td>La question et ses résultats en barres</td></tr>
      <tr><td>💰 Cagnotte</td><td>La collecte en cours et sa jauge</td></tr>
      <tr><td>✏️ Contenu libre</td><td>Ce que vous voulez</td></tr>
    </table>
    <div class="callout"><span class="i">🧱</span><div>Dans les thèmes, la visibilité et l'ordre des blocs de colonne se pilotent par <strong>familles</strong> (<code>data-rb</code>) plutôt que page par page — un réglage vaut pour tout le site.</div></div>

    <h2 id="gnec-photos">Galerie photo</h2>
    <p>Des albums (catégories) contenant des photos. Utile pour les LAN, les rencontres, les coulisses. Chaque album a sa page publique.</p>

    <h2 id="gnec-videos">Vidéos</h2>
    <p>Quatre plateformes reconnues : <strong>YouTube, Vimeo, Dailymotion et TikTok</strong>. Vous collez l'adresse, le module en extrait l'identifiant, l'URL d'intégration et la miniature.</p>
    <div class="callout ok"><span class="i">🎬</span><div>La reconnaissance des plateformes est <strong>isolée</strong> du reste : ajouter une plateforme ne touche qu'un seul fichier. C'est le genre de séparation qui évite de casser l'existant pour ajouter Twitch Clips l'an prochain.</div></div>

    <h2 id="gnec-twitch">Streamers Twitch</h2>
    <p>Déclarez les streamers de la communauté ; le module interroge l'API Twitch et affiche <strong>qui est en direct</strong>, avec le nombre de spectateurs. Une page liste les streamers, une autre présente un streamer.</p>
    <table class="doc-table">
      <tr><th>Point</th><th>Détail</th></tr>
      <tr><td>Authentification</td><td>App Access Token (Client Credentials) — aucune connexion de vos visiteurs n'est requise</td></tr>
      <tr><td>Lecture seule</td><td>Le module ne publie rien sur Twitch</td></tr>
      <tr><td>Cache court</td><td>Via la capacité <strong>Cache</strong> du framework, pour ne pas saturer l'API</td></tr>
    </table>

    <h2 id="gnec-sondages">Sondages</h2>
    <p>Question et options, vote <strong>unique ou multiple</strong>, dédoublonné par utilisateur et par IP. Les résultats s'affichent en barres, sur la page dédiée comme dans le widget de colonne.</p>

    <h2 id="gnec-recrutement">Recrutement</h2>
    <p>Publiez des annonces — un poste, un jeu, un niveau attendu — et recevez les <strong>candidatures</strong> directement sur le site. L'administration liste les candidatures par annonce et permet de les modérer.</p>
    <div class="callout"><span class="i">📬</span><div>C'est la fonction qui transforme un site vitrine en outil : les candidatures arrivent structurées, au même endroit, au lieu de se perdre entre Discord et les messages privés.</div></div>

    <h2 id="gnec-dons">Dons &amp; cagnottes</h2>
    <p>Des <strong>campagnes</strong> avec un objectif et une échéance, et des <strong>dons</strong> individuels encaissés par PayPal. L'administration montre la liste des donateurs par campagne, et un widget affiche la jauge de progression.</p>
    <p>De quoi financer un déplacement en LAN, un jeu de maillots ou du matériel, sans passer par une plateforme tierce qui prend sa part.</p>

    <h2 id="gnec-partenaires">Partenaires</h2>
    <p>Logos, liens et descriptions de vos sponsors. Ils apparaissent en bandeau, en widget, et sur les pages où le thème les prévoit — notamment les carrousels de bas de page des thèmes premium.</p>

    <h2 id="gnec-contact">Contact</h2>
    <p>Un formulaire public, protégé par les mécanismes du framework. Les coordonnées se règlent dans <em>Réglages</em>.</p>

    <div class="doc-foot">
      <span>GameNodeEsport · contenus</span>
      <span><a href="modules/gne/doc-gne-boutique.php">Boutique &amp; espace membre →</a></span>
    </div>

<?php require __DIR__ . '/../../inc/foot.php'; ?>
