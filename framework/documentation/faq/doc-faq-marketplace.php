<?php
/**
 * documentation/doc-faq-marketplace.php — FAQ du module Marketplace (produits dématérialisés).
 * Deux niveaux : 🟢 Débutant · 🔵 Avancé.
 */
$docPage = 'faq/doc-faq-marketplace.php';
$seo = [
    'title'     => 'FAQ — Marketplace (produits dématérialisés)',
    'desc'      => "Foire aux questions du module Marketplace d'Aegis : vente de produits numériques (modules, thèmes, jeux), paiement PayPal, commandes & téléchargement, factures PDF, promotions, avis et sécurité. Deux niveaux Débutant / Avancé.",
    'canonical' => 'https://gamenodepanel.com/documentation/doc-faq-marketplace.php',
];
require __DIR__ . '/../inc/head.php';

$faq = [
  ['id'=>'mk-intro','icon'=>'🛒','title'=>'Présentation','q'=>[
    ['d','Qu\'est-ce que la Marketplace ?','Une boutique de <strong>produits dématérialisés</strong> (modules, thèmes, jeux… téléchargeables). Il n\'y a <strong>ni stock, ni livraison physique</strong> : après paiement, le client télécharge son fichier.'],
    ['d','Où la gère-t-on ?','Côté admin : <strong>Marketplace</strong> (tableau de bord, produits, commandes, etc.). Côté public : la boutique accessible aux visiteurs.'],
    ['a','Quels types de produits peut-on vendre ?','Tout fichier numérique (généralement un <code>.zip</code>) — gratuit ou payant — avec <strong>versions</strong>, <strong>changelog</strong> par produit, images et description en <strong>Markdown</strong>.'],
  ]],
  ['id'=>'mk-produits','icon'=>'📦','title'=>'Produits','q'=>[
    ['d','Comment ajouter un produit ?','<strong>Marketplace → Produits → Nouveau</strong> : nom, description (Markdown), prix (ou gratuit), images et <strong>fichier téléchargeable</strong>.'],
    ['d','Comment publier une nouvelle version d\'un produit ?','Chaque produit gère ses <strong>versions</strong> et un <strong>changelog</strong> : ajoutez une version, les acheteurs récupèrent la dernière au téléchargement.'],
    ['d','Puis-je proposer un produit gratuit ?','Oui : prix à <strong>0</strong> → le produit reste « commandé » (commande à 0 €) puis téléchargeable, sans passer par le paiement.'],
    ['a','Où sont stockés les fichiers et comment sont-ils protégés ?','Les fichiers vendus sont hors de la zone publique et servis par <code>DownloadService</code> (vérification <code>realpath</code> + <code>str_starts_with</code> contre le <em>path-traversal</em>). Les uploads passent par <code>UploadService</code> (garde <strong>zip-slip</strong>, exécution PHP dans les assets bloquée).'],
  ]],
  ['id'=>'mk-catalog','icon'=>'🗂️','title'=>'Catégories & tags','q'=>[
    ['d','Comment organiser le catalogue ?','Avec les <strong>Catégories</strong> (rubriques) et les <strong>Tags</strong> (étiquettes transverses) — gérables depuis l\'admin.'],
    ['a','À quoi servent les tags vs catégories ?','La catégorie classe le produit dans une rubrique principale ; les tags permettent un filtrage transversal (liaison <code>marketplace_product_tags</code>).'],
  ]],
  ['id'=>'mk-paiement','icon'=>'💳','title'=>'Paiement','q'=>[
    ['d','Quels moyens de paiement sont disponibles ?','<strong>PayPal</strong> pour les produits payants, et un parcours <strong>gratuit</strong> (commande à 0 €) pour les produits à prix nul. Aucun autre prestataire (Stripe…) n\'est fourni par défaut.'],
    ['d','Comment configurer PayPal ?','<strong>Marketplace → Réglages</strong> : choisissez le mode <strong>sandbox</strong> (tests) ou <strong>live</strong> (production), renseignez vos identifiants (Client ID / secret) et la devise (EUR par défaut).'],
    ['a','Comment une commande est-elle confirmée après paiement ?','Via PayPal : création de l\'ordre → paiement → <strong>capture</strong>, doublée d\'un <strong>webhook</strong> (signature vérifiée). La route du webhook est <strong>exemptée de CSRF</strong> car authentifiée par signature, pas par jeton de session.'],
  ]],
  ['id'=>'mk-commandes','icon'=>'📥','title'=>'Commandes & téléchargement','q'=>[
    ['d','Comment le client récupère-t-il son produit ?','Une fois la commande payée, il accède au <strong>téléchargement</strong> depuis l\'historique de ses commandes.'],
    ['d','Où l\'admin voit-il les ventes ?','<strong>Marketplace → Commandes</strong> (et un récap dans Statistiques).'],
    ['a','Le téléchargement est-il sécurisé ?','Oui : le lien est <strong>lié à la commande</strong> (propriété vérifiée), le fichier n\'est jamais accessible en direct, et chaque téléchargement est journalisé (<code>marketplace_downloads</code> / <code>_log</code>).'],
  ]],
  ['id'=>'mk-factures','icon'=>'🧾','title'=>'Factures PDF','q'=>[
    ['d','Le client a-t-il une facture ?','Oui : une <strong>facture PDF</strong> est téléchargeable depuis la commande, une fois celle-ci payée.'],
    ['a','Comment est générée la facture ?','Par <code>PdfInvoiceService</code> — un générateur PDF <strong>en PHP pur</strong> (sans dépendance externe), reprenant le produit, l\'acheteur et les informations de la boutique.'],
  ]],
  ['id'=>'mk-promos','icon'=>'🏷️','title'=>'Promotions & coupons','q'=>[
    ['d','Comment faire une promotion ou un code promo ?','Trois outils : <strong>Promotions</strong>, <strong>codes promo / coupons</strong>, et <strong>Ventes flash</strong> (réductions limitées dans le temps).'],
    ['a','Comment c\'est suivi ?','Coupons et leurs usages (<code>marketplace_coupons</code> / <code>_coupon_usages</code>), liens promotionnels avec comptage de clics (<code>marketplace_promo_links</code> / <code>_promo_clicks</code>), et ventes flash (<code>marketplace_flash_sales</code>).'],
  ]],
  ['id'=>'mk-avis','icon'=>'⭐','title'=>'Avis & notes','q'=>[
    ['d','Les clients peuvent-ils noter un produit ?','Oui — mais <strong>seulement les acheteurs</strong> du produit peuvent laisser un avis et une note, ce qui garantit des retours authentiques.'],
    ['a','Comment l\'achat est-il vérifié ?','Un contrôle (type <code>canUserReview</code>) vérifie que l\'utilisateur a une commande payée du produit avant d\'autoriser l\'avis. Les avis sont modérables côté admin.'],
  ]],
  ['id'=>'mk-apparence','icon'=>'🎨','title'=>'Apparence de la boutique','q'=>[
    ['d','Comment personnaliser la vitrine ?','Via les <strong>Thèmes</strong> (apparence), les <strong>Sliders</strong> (bannières d\'accueil), les <strong>Pages</strong> statiques et la <strong>navigation</strong> de la boutique.'],
    ['a','Les descriptions supportent-elles la mise en forme ?','Oui, via <code>MarkdownService</code> (Markdown maison) : titres, listes, gras, code, liens — rendu de façon sûre (échappement).'],
  ]],
  ['id'=>'mk-secu','icon'=>'🔒','title'=>'Sécurité','q'=>[
    ['d','La boutique est-elle sécurisée ?','Oui : accès admin réservé au staff, paiements via PayPal, téléchargements réservés aux acheteurs, et avis réservés aux acheteurs.'],
    ['a','Quels sont les garde-fous techniques ?','Requêtes <strong>préparées</strong> (anti-injection SQL), <strong>XSS échappé</strong>, <strong>CSRF</strong> sur tous les POST admin (webhook exempté car signé), <strong>uploads validés</strong> (zip-slip + PHP-in-assets bloqué), <strong>téléchargements protégés</strong> (anti path-traversal + propriété de la commande), dossier des produits protégé par <code>.htaccess</code>.'],
  ]],
];

$totalD = 0; $totalA = 0;
foreach ($faq as $t) { foreach ($t['q'] as $q) { $q[0] === 'd' ? $totalD++ : $totalA++; } }
$lvlMeta = ['d' => ['🟢 Débutant', 'green'], 'a' => ['🔵 Avancé', 'blue']];
?>

    <h1>FAQ — Marketplace</h1>
    <p class="doc-lead">Les questions courantes sur la marketplace de <strong>produits dématérialisés</strong> d'Aegis — produits, paiement, téléchargements, factures — à deux niveaux.</p>
    <div class="doc-meta">
      <span class="doc-pill">🟢 <?= $totalD ?> Débutant</span>
      <span class="doc-pill">🔵 <?= $totalA ?> Avancé</span>
      <span class="doc-pill"><?= count($faq) ?> thèmes</span>
      <span class="doc-pill">Produits numériques</span>
    </div>

    <div class="callout"><span class="i">💡</span><div><strong>Cette marketplace vend des produits dématérialisés</strong> (fichiers téléchargeables) — pas de produit physique, ni stock, ni livraison. <strong>Débutant</strong> = « comment faire » · <strong>Avancé</strong> = technique.</div></div>

    <div class="faq-tools">
      <div class="faq-filter" role="tablist">
        <button class="faq-flt active" data-lvl="all">Tout</button>
        <button class="faq-flt" data-lvl="d">🟢 Débutant</button>
        <button class="faq-flt" data-lvl="a">🔵 Avancé</button>
      </div>
      <input type="search" id="faqSearch" class="faq-search" placeholder="🔎 Rechercher une question…" autocomplete="off">
    </div>

    <?php foreach ($faq as $t): ?>
    <section class="faq-theme" id="<?= $t['id'] ?>">
      <h2 class="faq-theme-title"><?= $t['icon'] ?> <?= htmlspecialchars($t['title']) ?></h2>
      <?php foreach ($t['q'] as [$lvl, $q, $a]): $lm = $lvlMeta[$lvl]; ?>
      <details class="faq-item" data-lvl="<?= $lvl ?>" data-text="<?= htmlspecialchars(mb_strtolower($q . ' ' . strip_tags($a)), ENT_QUOTES) ?>">
        <summary>
          <span class="faq-q"><?= htmlspecialchars($q) ?></span>
          <span class="ui-badge <?= $lm[1] ?> faq-lvl"><?= $lm[0] ?></span>
        </summary>
        <div class="faq-a"><?= $a ?></div>
      </details>
      <?php endforeach; ?>
    </section>
    <?php endforeach; ?>
    <div class="faq-noresult" id="faqNoResult" style="display:none">Aucune question ne correspond.</div>

<style>
.faq-tools{display:flex;gap:12px;flex-wrap:wrap;align-items:center;justify-content:space-between;margin:18px 0 22px}
.faq-filter{display:inline-flex;gap:4px;background:var(--bg2);border:1px solid var(--bd);border-radius:10px;padding:4px}
.faq-flt{border:none;background:none;padding:7px 14px;border-radius:7px;font-weight:600;font-size:.85rem;color:var(--tx2);cursor:pointer;font-family:inherit}
.faq-flt:hover{color:var(--tx)}
.faq-flt.active{background:var(--ac);color:#fff}
.faq-search{flex:1;min-width:220px;max-width:420px;padding:9px 13px;border:1px solid var(--bd2);border-radius:9px;background:var(--bg2);color:var(--tx);font-size:.88rem;font-family:inherit}
.faq-search:focus{outline:none;border-color:var(--ac)}
.faq-theme-title{font-size:1.25rem;margin:30px 0 12px;padding-top:14px;border-top:1px solid var(--bd)}
.faq-item{border:1px solid var(--bd);border-radius:10px;margin-bottom:8px;background:var(--bg2);overflow:hidden}
.faq-item[open]{border-color:var(--ac)}
.faq-item summary{display:flex;align-items:center;gap:12px;padding:13px 16px;cursor:pointer;list-style:none;font-weight:600;color:var(--tx)}
.faq-item summary::-webkit-details-marker{display:none}
.faq-item summary::before{content:'▸';color:var(--ac);font-size:.9rem;transition:transform .2s;flex-shrink:0}
.faq-item[open] summary::before{transform:rotate(90deg)}
.faq-q{flex:1}
.faq-lvl{flex-shrink:0;font-size:10px}
.faq-a{padding:0 16px 15px 40px;color:var(--tx2);font-size:.9rem;line-height:1.65}
.faq-a code{font-size:.85em;background:var(--code-bg);border:1px solid var(--bd);padding:.05em .35em;border-radius:4px;color:var(--tx)}
.faq-a strong{color:var(--tx)}
.faq-noresult{padding:20px;text-align:center;color:var(--tx3)}
</style>
<script>
(function () {
    var flts = document.querySelectorAll('.faq-flt'),
        items = Array.prototype.slice.call(document.querySelectorAll('.faq-item')),
        themes = Array.prototype.slice.call(document.querySelectorAll('.faq-theme')),
        search = document.getElementById('faqSearch'),
        noRes = document.getElementById('faqNoResult'),
        curLvl = 'all';
    function apply() {
        var q = (search.value || '').trim().toLowerCase(), any = false;
        items.forEach(function (it) {
            var okLvl = curLvl === 'all' || it.getAttribute('data-lvl') === curLvl;
            var okTxt = !q || (it.getAttribute('data-text') || '').indexOf(q) !== -1;
            var show = okLvl && okTxt;
            it.style.display = show ? '' : 'none';
            if (show && q) it.open = true; else if (!q) it.open = false;
            if (show) any = true;
        });
        themes.forEach(function (th) {
            var visible = th.querySelectorAll('.faq-item:not([style*="display: none"])').length;
            th.style.display = visible ? '' : 'none';
        });
        if (noRes) noRes.style.display = any ? 'none' : 'block';
    }
    flts.forEach(function (b) {
        b.addEventListener('click', function () {
            flts.forEach(function (x) { x.classList.remove('active'); });
            b.classList.add('active'); curLvl = b.getAttribute('data-lvl'); apply();
        });
    });
    if (search) search.addEventListener('input', apply);
}());
</script>

<?php require __DIR__ . '/../inc/foot.php'; ?>
