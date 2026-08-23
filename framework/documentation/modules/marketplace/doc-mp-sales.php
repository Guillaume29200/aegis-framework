<?php
$docPage = 'modules/marketplace/doc-mp-sales.php';
$seo = [
    'title' => 'Marketplace — Ventes & clients · Documentation Aegis Framework',
    'desc'  => "Paiement PayPal et commandes gratuites, suivi des commandes & remboursements, téléchargement sécurisé par token, factures PDF, avis vérifiés et promotions (coupons, liens, ventes flash) de la Marketplace Aegis.",
    'canonical' => 'https://gamenodepanel.com/documentation/doc-mp-sales.php',
];
require __DIR__ . '/../../inc/head.php';
?>
    <h1>Ventes &amp; clients</h1>
    <p class="doc-lead">Du panier à la facture : paiement <strong>PayPal</strong> (ou commande gratuite), suivi des commandes, <strong>téléchargement sécurisé</strong>, factures PDF, avis vérifiés et leviers marketing.</p>
    <div class="doc-meta"><span class="doc-pill">PayPal</span><span class="doc-pill">Commandes</span><span class="doc-pill">Factures PDF</span><span class="doc-pill">Promotions</span></div>

    <h2 id="mps-payment">Paiement</h2>
    <p>Deux parcours selon le prix du produit :</p>
    <table class="doc-table">
      <tr><th>Cas</th><th>Déroulé</th></tr>
      <tr><td><strong>Produit gratuit (0 €)</strong></td><td>Commande créée directement à 0 €, sans paiement → téléchargement immédiat</td></tr>
      <tr><td><strong>Produit payant</strong></td><td><strong>PayPal</strong> : création de l'ordre → paiement → <em>capture</em> → commande confirmée</td></tr>
    </table>
    <p>PayPal fonctionne en mode <strong>sandbox</strong> (tests) ou <strong>live</strong> (production), avec la <strong>devise</strong> configurée (EUR par défaut). La confirmation passe aussi par un <strong>webhook</strong> PayPal dont la <strong>signature est vérifiée</strong>.</p>
    <div class="callout"><span class="i">🔐</span><div>La route du webhook (<code>/marketplace/payment/paypal/webhook</code>) est <strong>exemptée de CSRF</strong> : elle n'est pas appelée par un navigateur avec jeton de session, mais par PayPal — son authenticité repose sur la <strong>vérification de signature</strong>.</div></div>
    <div class="callout warn"><span class="i">ℹ️</span><div>Seul <strong>PayPal</strong> est intégré nativement (avec le parcours gratuit). Aucun autre prestataire (Stripe…) n'est fourni par défaut.</div></div>

    <h2 id="mps-orders">Commandes &amp; remboursements</h2>
    <p><code>Marketplace → Commandes</code> liste toutes les ventes (statut, acheteur, produit, montant). Vous pouvez consulter le <strong>détail</strong> d'une commande, la <strong>rembourser</strong>, la <strong>supprimer</strong>, et <strong>exporter en CSV</strong> pour votre comptabilité. Un récapitulatif figure aussi dans <strong>Statistiques</strong>.</p>

    <h2 id="mps-download">Téléchargement sécurisé</h2>
    <p>Après une commande payée (ou gratuite), le client télécharge depuis l'historique de ses achats (<code>/marketplace/purchases</code>). Le lien repose sur un <strong>token</strong> aléatoire (<code>DownloadService</code>, 32 octets) lié à la commande :</p>
    <ul>
      <li>le fichier n'est <strong>jamais</strong> accessible en direct (servi via <code>/marketplace/download/&lt;token&gt;</code>) ;</li>
      <li>la <strong>propriété</strong> de la commande est vérifiée, avec protection <strong>anti path-traversal</strong> ;</li>
      <li>chaque téléchargement est <strong>journalisé</strong> (table <code>marketplace_downloads</code>).</li>
    </ul>

    <h2 id="mps-invoice">Factures PDF</h2>
    <p>Une <strong>facture PDF</strong> est disponible pour chaque commande payée (<code>/marketplace/invoice/&lt;orderId&gt;</code>), générée par <code>PdfInvoiceService</code> — un générateur <strong>100 % PHP, sans dépendance externe</strong> — reprenant le produit, l'acheteur et les informations de la boutique.</p>

    <h2 id="mps-reviews">Avis &amp; notes</h2>
    <p>Les clients peuvent noter et commenter un produit, mais <strong>seuls les acheteurs vérifiés</strong> y sont autorisés (contrôle d'une commande payée du produit) — gage d'avis authentiques. Les avis sont <strong>modérables</strong> depuis <code>Marketplace → Avis</code>.</p>

    <h2 id="mps-promos">Promotions, coupons &amp; ventes flash</h2>
    <p>Trois leviers cumulables, dans <code>Marketplace → Promotions</code> et <code>→ Ventes flash</code> :</p>
    <table class="doc-table">
      <tr><th>Outil</th><th>Usage</th></tr>
      <tr><td><strong>Codes promo / coupons</strong></td><td>Réduction à la commande (validée via <code>/marketplace/validate-coupon</code>) ; usages suivis</td></tr>
      <tr><td><strong>Liens promotionnels</strong></td><td>Liens traçables (<code>/marketplace/promo/&lt;slug&gt;</code>) avec <strong>comptage de clics</strong> &amp; page de stats</td></tr>
      <tr><td><strong>Ventes flash</strong></td><td>Réductions <strong>limitées dans le temps</strong>, activables/désactivables</td></tr>
    </table>

    <?php require __DIR__ . '/../../inc/foot.php'; ?>
