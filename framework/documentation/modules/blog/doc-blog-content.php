<?php
$docPage = 'modules/blog/doc-blog-content.php';
$seo = [
    'title' => 'Blog — Articles & contenu · Documentation Aegis Framework',
    'desc'  => "Rédiger sur le blog Aegis : articles en Markdown, image de couverture & extrait, catégories & tags, article à la une, publication brouillon/publié/programmé, commentaires modérés et likes.",
    'canonical' => 'https://gamenodepanel.com/documentation/doc-blog-content.php',
];
require __DIR__ . '/../../inc/head.php';
?>
    <h1>Articles &amp; contenu</h1>
    <p class="doc-lead">Tout le contenu se gère depuis <code>/admin/blogging</code> : rédaction en Markdown, organisation par catégories &amp; tags, états de publication, puis modération des commentaires.</p>
    <div class="doc-meta"><span class="doc-pill">Markdown</span><span class="doc-pill">Publication programmée</span><span class="doc-pill">Commentaires</span></div>

    <h2 id="blc-post">Créer un article</h2>
    <p><code>Blog → Nouvel article</code>. Un article comporte :</p>
    <ul>
      <li><strong>Titre</strong> &amp; <strong>slug</strong> (URL : <code>/blog/&lt;slug&gt;</code>) ;</li>
      <li><strong>Contenu</strong> en <strong>Markdown</strong> (avec <strong>aperçu live</strong>) ;</li>
      <li><strong>Extrait</strong> (résumé, 500 car. max) — sinon généré depuis le contenu ;</li>
      <li><strong>Image de couverture</strong> &amp; images insérées (upload validé) ;</li>
      <li><strong>Catégorie</strong>, <strong>tags</strong>, et option <strong>« À la une »</strong> (mise en avant).</li>
    </ul>
    <p>Le <strong>temps de lecture</strong> est calculé automatiquement, et des <strong>articles liés</strong> sont suggérés sur la fiche publique.</p>

    <h2 id="blc-publish">Publication &amp; programmation</h2>
    <table class="doc-table">
      <tr><th>État</th><th>Comportement</th></tr>
      <tr><td><strong>Brouillon</strong> (<code>draft</code>)</td><td>Invisible du public, en cours de rédaction</td></tr>
      <tr><td><strong>Publié</strong> (<code>published</code>)</td><td>Visible immédiatement</td></tr>
      <tr><td><strong>Programmé</strong> (<code>scheduled</code>)</td><td>Visible <strong>automatiquement</strong> à la date de parution prévue</td></tr>
    </table>
    <div class="callout"><span class="i">⏰</span><div>La <strong>programmation ne nécessite aucun cron</strong> : un article programmé apparaît dès que sa date de parution est atteinte (filtré à l'affichage, <code>published_at &le; NOW()</code>). Une date passée saisie en « programmé » bascule l'article en « publié ».</div></div>

    <h2 id="blc-categories">Catégories &amp; tags</h2>
    <p><strong>Catégories</strong> (<code>Blog → Catégories</code>) : rubriques principales avec ordre d'affichage ; chaque article en a une, accessible via <code>/blog/c/&lt;slug&gt;</code>. <strong>Tags</strong> : étiquettes transverses (un article peut en cumuler), pages <code>/blog/tag/&lt;slug&gt;</code>, avec un classement des <strong>tags populaires</strong>.</p>

    <h2 id="blc-comments">Commentaires &amp; modération</h2>
    <p>Les lecteurs commentent les articles (selon les réglages). La modération se fait dans <code>Blog → Commentaires</code> :</p>
    <table class="doc-table">
      <tr><th>Statut</th><th>Sens</th></tr>
      <tr><td><code>pending</code></td><td>En attente de validation (par défaut si la modération est active)</td></tr>
      <tr><td><code>approved</code></td><td>Approuvé &amp; visible sous l'article</td></tr>
      <tr><td><code>spam</code></td><td>Marqué indésirable (masqué)</td></tr>
    </table>
    <p>Réglages associés (voir <a href="modules/blog/doc-blog-customize.php#blz-settings">Réglages</a>) : activer/désactiver les commentaires, exiger une <strong>modération</strong> avant publication, autoriser ou non les <strong>invités</strong>. Un compteur de commentaires en attente est remonté, et l'admin peut être <strong>notifié</strong> des nouveaux commentaires.</p>

    <h2 id="blc-likes">Likes</h2>
    <p>Chaque article peut être « aimé » <strong>sans compte</strong> : le like est dédoublonné par <strong>hash d'IP</strong> (un visiteur ne compte qu'une fois), avec bascule on/off.</p>

    <?php require __DIR__ . '/../../inc/foot.php'; ?>
