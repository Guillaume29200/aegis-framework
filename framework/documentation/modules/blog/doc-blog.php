<?php
$docPage = 'modules/blog/doc-blog.php';
$seo = [
    'title' => 'Blog — Vue d\'ensemble · Documentation Aegis Framework',
    'desc'  => "Le module Blogging d'Aegis : blog complet avec articles en Markdown, catégories, tags, commentaires modérés, likes, thèmes importables (ZIP), flux RSS et SEO. Architecture, fonctionnalités et installation.",
    'canonical' => 'https://gamenodepanel.com/documentation/doc-blog.php',
];
require __DIR__ . '/../../inc/head.php';
?>
    <h1>Blog — Vue d'ensemble</h1>
    <p class="doc-lead">Le module <strong>Blogging</strong> ajoute un blog complet à votre site : articles en <strong>Markdown</strong> (image de couverture, extrait, tags), catégories, <strong>commentaires modérés</strong>, likes, <strong>publication programmée</strong>, thèmes importables par ZIP, flux <strong>RSS</strong> et <strong>SEO</strong> intégré.</p>
    <div class="doc-meta"><span class="doc-pill">Communautaire</span><span class="doc-pill">Markdown</span><span class="doc-pill">Commentaires modérés</span><span class="doc-pill">Thèmes ZIP</span></div>

    <h2 id="bl-intro">Qu'est-ce que le module Blogging ?</h2>
    <p>Un système de blog livré comme <strong>module officiel</strong> activable depuis <code>Administration → Modules</code> (dépend du module <code>Auth</code>). Il expose une partie <strong>publique</strong> (<code>/blog</code>) et une partie <strong>administration</strong> (<code>/admin/blogging</code>) greffée dans le menu Aegis.</p>
    <div class="callout ok"><span class="i">🎯</span><div>En bref : vous rédigez &amp; publiez sur <code>/admin/blogging</code> ; vos visiteurs lisent, commentent et aiment les articles sur <code>/blog</code>.</div></div>

    <h2 id="bl-features">Fonctionnalités</h2>
    <table class="doc-table">
      <tr><th>Domaine</th><th>Ce que ça couvre</th></tr>
      <tr><td><strong>Rédaction</strong></td><td>Articles en Markdown (aperçu live), image de couverture, extrait, upload d'images, temps de lecture calculé</td></tr>
      <tr><td><strong>Organisation</strong></td><td>Catégories, tags, article « à la une » (featured), articles liés</td></tr>
      <tr><td><strong>Publication</strong></td><td>Brouillon / publié / <strong>programmé</strong> (parution à une date future)</td></tr>
      <tr><td><strong>Interaction</strong></td><td>Commentaires <strong>modérés</strong> (invités autorisés ou non), likes sans compte</td></tr>
      <tr><td><strong>Apparence</strong></td><td>Thèmes importables par ZIP (deux fournis : <em>default</em> &amp; <em>magazine</em>)</td></tr>
      <tr><td><strong>Diffusion / SEO</strong></td><td>Flux <strong>RSS</strong>, <strong>sitemap.xml</strong> du blog, métadonnées par article</td></tr>
    </table>

    <h2 id="bl-archi">Architecture</h2>
    <table class="doc-table">
      <tr><th>Composant</th><th>Rôle</th></tr>
      <tr><td><code>BlogController</code></td><td>Pages publiques (accueil, catégorie, tag, article, commentaires, likes, RSS, sitemap)</td></tr>
      <tr><td><code>AdminController</code></td><td>Administration (articles, catégories, commentaires, thèmes, réglages)</td></tr>
      <tr><td><code>BloggingService</code></td><td>Logique métier &amp; accès données (requêtes préparées)</td></tr>
      <tr><td><code>MarkdownService</code></td><td>Rendu Markdown <strong>sécurisé</strong> des articles &amp; aperçus</td></tr>
      <tr><td><code>ImageUploadService</code></td><td>Upload d'images validé (couverture &amp; images d'article)</td></tr>
      <tr><td><code>ThemeService</code></td><td>Thèmes de la vitrine (activation, import ZIP, CSS dynamique)</td></tr>
    </table>
    <p>Tables : <code>blog_posts</code>, <code>blog_categories</code>, <code>blog_tags</code> (+ liaison <code>blog_post_tags</code>), <code>blog_comments</code>, <code>blog_post_likes</code>, <code>blog_settings</code>.</p>

    <h2 id="bl-install">Installation &amp; activation</h2>
    <ol>
      <li>Le module est présent dans <code>/modules/Blogging</code> et requiert <code>Auth</code>.</li>
      <li><code>Administration → Modules</code> → <strong>Activer</strong> : <code>database/install.sql</code> crée les tables <code>blog_*</code>.</li>
      <li>Le menu <strong>Blog</strong> apparaît dans l'administration ; la vitrine est servie sur <code>/blog</code>.</li>
    </ol>
    <div class="callout"><span class="i">ℹ️</span><div>À la désactivation, <code>database/uninstall.sql</code> retire les tables <code>blog_*</code>. Sauvegardez avant si vous tenez à conserver vos articles.</div></div>

    <h2 id="bl-secu">Sécurité en bref</h2>
    <ul>
      <li><strong>CSRF</strong> sur toutes les actions (admin + commentaires/likes publics).</li>
      <li><strong>Markdown</strong> rendu de façon sûre (échappement) ; <strong>SQL</strong> 100 % préparé ; sorties échappées.</li>
      <li><strong>Commentaires modérés</strong> par défaut ; likes dédoublonnés par hash d'IP (pas de compte requis).</li>
      <li><strong>Uploads d'images</strong> validés (type &amp; contenu) via <code>ImageUploadService</code>.</li>
    </ul>

    <?php require __DIR__ . '/../../inc/foot.php'; ?>
