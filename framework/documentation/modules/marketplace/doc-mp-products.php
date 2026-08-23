<?php
$docPage = 'modules/marketplace/doc-mp-products.php';
$seo = [
    'title' => 'Marketplace — Catalogue & produits · Documentation Aegis Framework',
    'desc'  => "Gérer le catalogue de la Marketplace Aegis : créer des produits (Markdown, images, prix ou gratuit), versions & changelog, fichiers téléchargeables protégés, catégories et tags.",
    'canonical' => 'https://gamenodepanel.com/documentation/doc-mp-products.php',
];
require __DIR__ . '/../../inc/head.php';
?>
    <h1>Catalogue &amp; produits</h1>
    <p class="doc-lead">Le catalogue se gère depuis <code>/admin/marketplace</code> : produits avec description Markdown, galerie d'images, <strong>versions</strong> &amp; <strong>changelog</strong>, fichiers téléchargeables protégés, le tout classé par <strong>catégories</strong> et <strong>tags</strong>.</p>
    <div class="doc-meta"><span class="doc-pill">Produits</span><span class="doc-pill">Versions</span><span class="doc-pill">Catégories &amp; tags</span></div>

    <h2 id="mpp-products">Créer un produit</h2>
    <p><code>Marketplace → Produits → Nouveau</code>. Un produit comporte :</p>
    <ul>
      <li><strong>Nom</strong> &amp; <strong>slug</strong> (URL de la fiche : <code>/marketplace/product/&lt;slug&gt;</code>) ;</li>
      <li><strong>Description</strong> en <strong>Markdown</strong> (éditeur avec aperçu) ;</li>
      <li><strong>Prix</strong> — ou <strong>0</strong> pour un produit gratuit (commande à 0 €, téléchargement direct) ;</li>
      <li><strong>Catégorie</strong>, <strong>tags</strong>, <strong>images</strong> et le <strong>fichier téléchargeable</strong>.</li>
    </ul>
    <p>Un produit peut être <strong>activé/désactivé</strong> (visibilité en boutique) ; des actions <strong>groupées</strong> permettent de traiter plusieurs produits à la fois.</p>

    <h2 id="mpp-versions">Versions &amp; changelog</h2>
    <p>Chaque produit gère ses <strong>versions</strong> et un <strong>changelog</strong> daté. Lorsque vous publiez une nouvelle version, les acheteurs récupèrent automatiquement <strong>la dernière</strong> au téléchargement — idéal pour des modules/thèmes qui évoluent.</p>

    <h2 id="mpp-files">Fichiers &amp; stockage protégé</h2>
    <p>Le fichier vendu est stocké <strong>hors de la zone publique</strong> et n'est jamais accessible en direct : il est servi par <code>DownloadService</code> via un token lié à la commande (voir <a href="modules/marketplace/doc-mp-sales.php#mps-download">Téléchargement</a>).</p>
    <table class="doc-table">
      <tr><th>Réglage (par défaut)</th><th>Valeur</th></tr>
      <tr><td>Taille max d'un fichier</td><td>500 Mo (configurable)</td></tr>
      <tr><td>Formats autorisés</td><td><code>json</code>, <code>zip</code>, <code>rar</code>, <code>tar</code>, <code>tar.bz2</code></td></tr>
    </table>
    <div class="callout"><span class="i">🔐</span><div>Les uploads passent par <code>UploadService</code> : garde <strong>zip-slip</strong> (pas d'extraction hors dossier), exécution PHP bloquée dans les assets, et dossier des produits protégé par <code>.htaccess</code>.</div></div>

    <h2 id="mpp-categories">Catégories</h2>
    <p><code>Marketplace → Catégories</code> : créez les rubriques principales du catalogue (nom, slug). Chaque produit appartient à une catégorie, qui sert de filtre dans la vitrine.</p>

    <h2 id="mpp-tags">Tags</h2>
    <p><code>Marketplace → Tags</code> : étiquettes <strong>transverses</strong> (un produit peut en cumuler plusieurs, liaison <code>marketplace_product_tags</code>). Là où la catégorie classe, les tags permettent un filtrage croisé (ex. « gratuit », « premium », « FiveM »…).</p>

    <h2 id="mpp-images">Galerie d'images</h2>
    <p>Chaque produit a une galerie : ajout de plusieurs visuels, <strong>réordonnables</strong>, suppression individuelle. La première image sert de vignette dans le catalogue et d'image de partage de la fiche.</p>

    <?php require __DIR__ . '/../../inc/foot.php'; ?>
