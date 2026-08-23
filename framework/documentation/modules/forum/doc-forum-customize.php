<?php
$docPage = 'modules/forum/doc-forum-customize.php';
$seo = [
    'title' => 'Forum — Personnalisation & SEO · Documentation Aegis Framework',
    'desc'  => "Personnaliser le forum Aegis : thèmes importables (ZIP), widgets sidebar/footer, sondages widgets, liens de navigation, pages statiques et SEO complet (Open Graph, JSON-LD, sitemap).",
    'canonical' => 'https://gamenodepanel.com/documentation/doc-forum-customize.php',
];
require __DIR__ . '/../../inc/head.php';
?>
    <h1>Personnalisation &amp; SEO</h1>
    <p class="doc-lead">Adaptez l'apparence du forum sans toucher au code (thèmes, widgets, navigation, pages), puis optimisez sa <strong>visibilité</strong> grâce à un SEO complet intégré à chaque page.</p>
    <div class="doc-meta"><span class="doc-pill">Thèmes ZIP</span><span class="doc-pill">Widgets</span><span class="doc-pill">Pages</span><span class="doc-pill">SEO</span></div>

    <h2 id="fcz-themes">Thèmes &amp; import ZIP</h2>
    <p>Le rendu visuel passe par un <strong>CSS dynamique</strong> généré depuis vos réglages (couleurs) et par des <strong>thèmes</strong>. Depuis <code>/admin/forum/themes</code> vous pouvez <strong>activer</strong> un thème, en <strong>importer</strong> un nouveau via une <strong>archive ZIP</strong>, ou en supprimer. Couleurs principales/secondaires/accent et <strong>CSS personnalisé</strong> se règlent dans les paramètres.</p>
    <div class="callout"><span class="i">⚡</span><div>La feuille de style est servie via <code>/forum/assets/theme.css</code> avec un cache-buster <strong>stable</strong> : le navigateur la met en cache et ne la re-télécharge que lorsqu'un réglage de thème change.</div></div>

    <h2 id="fcz-widgets">Widgets sidebar &amp; footer</h2>
    <p>La colonne latérale et le pied de page accueillent des <strong>widgets</strong> activables et réordonnables (<code>/admin/forum/sidebar-widgets</code>). Parmi les blocs disponibles :</p>
    <table class="doc-table">
      <tr><th>Catégorie</th><th>Exemples de widgets</th></tr>
      <tr><td>Activité</td><td>Sujets tendance, sans réponse, activité récente, activité par jour</td></tr>
      <tr><td>Communauté</td><td>Classement réputation, membre du mois, top de la semaine, croissance des membres, membre aléatoire</td></tr>
      <tr><td>Contenu</td><td>Sujets épinglés, catégories rapides, annonces, récompenses récentes, citation</td></tr>
      <tr><td>Statistiques</td><td>Statistiques globales, records du forum, meilleur posteur, en ligne / record de connectés</td></tr>
    </table>

    <h2 id="fcz-pollwidgets">Sondages widgets</h2>
    <p>Indépendants des sondages de sujet, les <strong>sondages widgets</strong> (<code>/admin/forum/poll-widgets</code>) s'affichent en sidebar/footer. On les crée, active, réinitialise (remise à zéro des votes) ; les invités peuvent voter (dédoublonnage par hash d'IP).</p>

    <h2 id="fcz-navlinks">Liens de navigation</h2>
    <p><code>/admin/forum/nav-links</code> ajoute des <strong>liens externes</strong> personnalisés dans la barre du forum (libellé, URL, ordre), en complément des catégories et des pages.</p>

    <h2 id="fcz-pages">Pages statiques</h2>
    <p>Créez des <strong>pages</strong> en Markdown (règles, à propos, mentions légales…) via <code>/admin/forum/pages</code> : titre, slug, contenu, état publié/brouillon, et affichage éventuel dans la navigation ou le pied de page. Elles sont servies sur <code>/forum/page/slug</code>.</p>

    <h2 id="fcz-seo">SEO</h2>
    <p>Chaque page publique du forum émet un jeu de balises complet, alimenté par vos réglages SEO (<code>/admin/forum/settings</code>) et enrichi par page :</p>
    <ul>
      <li><strong>Titre dynamique</strong> (<code>Titre de page — Nom du site</code>, séparateur configurable) &amp; <strong>meta description</strong>.</li>
      <li><strong>Canonical</strong> automatique et <strong>robots</strong> (avec <em>noindex</em> par page : recherche, notifications, profils paginés…).</li>
      <li><strong>Open Graph</strong> &amp; <strong>Twitter Cards</strong> (titre, description, image), activables.</li>
      <li><strong>JSON-LD</strong> structuré selon le type de page : <code>WebSite</code> + recherche (accueil), <code>BreadcrumbList</code> (catégorie/sujet), <code>Person</code> (profil), <code>Article</code> (pages).</li>
      <li><strong>sitemap.xml</strong> + <strong>robots.txt</strong> générables depuis la page SEO de l'administration.</li>
    </ul>
    <div class="callout ok"><span class="i">✅</span><div>Le SEO est <strong>natif et par page</strong> : aucune extension à installer. Il suffit de renseigner nom du site, description par défaut et image de partage dans les réglages pour des partages sociaux propres.</div></div>

    <?php require __DIR__ . '/../../inc/foot.php'; ?>
