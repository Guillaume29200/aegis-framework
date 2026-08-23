<?php
$docPage = 'modules/marketplace/doc-mp.php';
$seo = [
    'title' => 'Marketplace — Vue d\'ensemble · Documentation Aegis Framework',
    'desc'  => "Le module Marketplace d'Aegis : boutique de produits dématérialisés (modules, thèmes, jeux), gratuits ou payants via PayPal, avec versions, factures PDF, promotions, avis et thèmes de vitrine.",
    'canonical' => 'https://gamenodepanel.com/documentation/doc-mp.php',
];
require __DIR__ . '/../../inc/head.php';
?>
    <h1>Marketplace — Vue d'ensemble</h1>
    <p class="doc-lead">Le module <strong>Marketplace</strong> est une boutique de <strong>produits dématérialisés</strong> (modules, thèmes, jeux, ressources…) : gratuits ou payants via <strong>PayPal</strong>, avec versions &amp; changelog, factures PDF, promotions, avis vérifiés et une vitrine entièrement personnalisable.</p>
    <div class="doc-meta"><span class="doc-pill">e-commerce</span><span class="doc-pill">Produits numériques</span><span class="doc-pill">PayPal</span><span class="doc-pill">Vitrine à thèmes</span></div>

    <div class="callout"><span class="i">🧩</span><div><strong>À ne pas confondre :</strong> ce module Marketplace (boutique e-commerce) est différent du <a href="modules/gnp/doc-marketplace.php">Marketplace de plugins de GameNodePanel</a> (catalogue de plugins/mods pour serveurs de jeu).</div></div>

    <h2 id="mp-intro">Qu'est-ce que la Marketplace ?</h2>
    <p>Une boutique en ligne pour vendre ou distribuer des <strong>fichiers numériques</strong> (généralement des <code>.zip</code>) : <strong>ni stock, ni livraison physique</strong>. Après paiement (ou commande gratuite), le client <strong>télécharge</strong> son produit et reçoit une <strong>facture PDF</strong>. Module officiel activable depuis <code>Administration → Modules</code> (dépend du module <code>Auth</code>).</p>
    <div class="callout ok"><span class="i">🎯</span><div>En bref : vous publiez des produits sur <code>/admin/marketplace</code>, vos visiteurs achètent/téléchargent depuis la vitrine publique <code>/marketplace</code>.</div></div>

    <h2 id="mp-features">Fonctionnalités</h2>
    <table class="doc-table">
      <tr><th>Domaine</th><th>Ce que ça couvre</th></tr>
      <tr><td><strong>Catalogue</strong></td><td>Produits (Markdown, images, versions, changelog), catégories, tags, produits gratuits ou payants</td></tr>
      <tr><td><strong>Vente</strong></td><td>Paiement <strong>PayPal</strong> (sandbox/live) + commandes gratuites, factures PDF, historique d'achats</td></tr>
      <tr><td><strong>Marketing</strong></td><td>Codes promo / coupons, liens promotionnels avec suivi de clics, ventes flash</td></tr>
      <tr><td><strong>Confiance</strong></td><td>Avis &amp; notes <strong>réservés aux acheteurs</strong>, modérables côté admin</td></tr>
      <tr><td><strong>Vitrine</strong></td><td>Thèmes importables (ZIP), sliders d'accueil, pages statiques (CGV/CGU/mentions), navigation, SEO</td></tr>
      <tr><td><strong>Pilotage</strong></td><td>Tableau de bord, statistiques de ventes, export CSV des commandes</td></tr>
    </table>

    <h2 id="mp-archi">Architecture</h2>
    <table class="doc-table">
      <tr><th>Composant</th><th>Rôle</th></tr>
      <tr><td><code>MarketplaceController</code></td><td>Vitrine publique (catalogue, fiche produit, achats, téléchargement, factures, contact)</td></tr>
      <tr><td><code>AdminController</code></td><td>Administration (produits, commandes, promotions, pages, sliders, réglages…)</td></tr>
      <tr><td><code>PaymentController</code></td><td>Paiement : commandes gratuites + PayPal (création / capture / annulation / webhook)</td></tr>
      <tr><td><code>ThemeController</code></td><td>Thèmes de la vitrine (activation, import ZIP, service des assets)</td></tr>
      <tr><td><code>MarketplaceService</code></td><td>Logique métier &amp; accès données (requêtes préparées)</td></tr>
      <tr><td><code>PayPalService</code> / <code>PromotionService</code></td><td>API PayPal &amp; coupons/promotions</td></tr>
      <tr><td><code>DownloadService</code> / <code>UploadService</code></td><td>Tokens de téléchargement &amp; uploads validés (anti zip-slip)</td></tr>
      <tr><td><code>PdfInvoiceService</code> / <code>MarkdownService</code></td><td>Factures PDF en PHP pur &amp; rendu Markdown des descriptions</td></tr>
    </table>

    <h2 id="mp-install">Installation &amp; activation</h2>
    <ol>
      <li>Le module est présent dans <code>/modules/Marketplace</code> et requiert le module <strong>Auth</strong>.</li>
      <li><code>Administration → Modules</code> → <strong>Activer</strong> : <code>database/install.sql</code> crée les tables <code>marketplace_*</code>.</li>
      <li>Le menu <strong>Marketplace</strong> apparaît dans l'administration ; la vitrine est servie sur <code>/marketplace</code>.</li>
      <li>Configurez ensuite <strong>PayPal</strong>, la devise et les formats autorisés dans <code>Marketplace → Réglages</code> (voir <a href="modules/marketplace/doc-mp-customize.php#mpc-settings">Réglages</a>).</li>
    </ol>

    <h2 id="mp-secu">Sécurité en bref</h2>
    <ul>
      <li><strong>CSRF</strong> sur tous les POST admin (le <em>webhook</em> PayPal est exempté car authentifié par signature, pas par jeton de session).</li>
      <li><strong>Téléchargements</strong> par <strong>token</strong> lié à la commande, fichiers hors zone publique (anti path-traversal).</li>
      <li><strong>Uploads</strong> validés : garde <strong>zip-slip</strong>, exécution PHP bloquée dans les assets, dossier produits protégé par <code>.htaccess</code>.</li>
      <li><strong>Avis</strong> réservés aux acheteurs vérifiés ; <strong>SQL préparé</strong> &amp; sorties échappées partout.</li>
    </ul>

    <?php require __DIR__ . '/../../inc/foot.php'; ?>
