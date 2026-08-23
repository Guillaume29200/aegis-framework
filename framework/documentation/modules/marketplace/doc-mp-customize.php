<?php
$docPage = 'modules/marketplace/doc-mp-customize.php';
$seo = [
    'title' => 'Marketplace — Vitrine & réglages · Documentation Aegis Framework',
    'desc'  => "Personnaliser la boutique Marketplace Aegis : thèmes importables (ZIP), sliders d'accueil, pages statiques (CGV/CGU/mentions), navigation, SEO et réglages (PayPal, devise, formats, contact).",
    'canonical' => 'https://gamenodepanel.com/documentation/doc-mp-customize.php',
];
require __DIR__ . '/../../inc/head.php';
?>
    <h1>Vitrine &amp; réglages</h1>
    <p class="doc-lead">La boutique publique (<code>/marketplace</code>) est entièrement personnalisable sans toucher au code : thèmes, sliders, pages, navigation, SEO — puis on règle PayPal &amp; les paramètres de vente.</p>
    <div class="doc-meta"><span class="doc-pill">Thèmes ZIP</span><span class="doc-pill">Sliders</span><span class="doc-pill">Pages &amp; SEO</span><span class="doc-pill">Réglages</span></div>

    <h2 id="mpc-themes">Thèmes &amp; import ZIP</h2>
    <p><code>Marketplace → Thèmes</code> : <strong>activez</strong> un thème de vitrine, <strong>importez-en</strong> un nouveau via une <strong>archive ZIP</strong>, ou supprimez-en. Un thème complet (<em>levelia</em>) est fourni : accueil, catalogue, nouveautés, gratuits, packs, fiche produit, pages, etc. Le CSS et les assets du thème sont servis via <code>/marketplace/assets/…</code>.</p>
    <div class="callout"><span class="i">🎨</span><div>La vitrine est <strong>découplée</strong> de l'administration : changer de thème ne modifie ni vos produits ni vos commandes, uniquement l'apparence côté client.</div></div>

    <h2 id="mpc-sliders">Sliders d'accueil</h2>
    <p><code>Marketplace → Sliders</code> : bannières « hero » de la page d'accueil — image, titre, lien, ordre (réordonnables), activation/désactivation. Idéal pour mettre en avant une nouveauté ou une vente flash.</p>

    <h2 id="mpc-pages">Pages statiques &amp; mentions légales</h2>
    <p><code>Marketplace → Pages</code> : créez des pages en Markdown (à propos, FAQ boutique…), publiables ou en brouillon. La boutique expose aussi des pages légales prêtes à l'emploi : <strong>CGV</strong> (<code>/marketplace/cgv</code>), <strong>CGU</strong> (<code>/marketplace/cgu</code>) et <strong>mentions légales</strong> (<code>/marketplace/mentions-legales</code>), ainsi qu'un formulaire de <strong>contact</strong>.</p>

    <h2 id="mpc-nav">Navigation</h2>
    <p><code>Marketplace → Navigation</code> : composez le menu de la boutique (libellés, liens, ordre, activation). Les rubriques natives — catalogue, nouveautés, gratuits, packs — sont disponibles d'office.</p>

    <h2 id="mpc-seo">SEO &amp; médias</h2>
    <p>La vitrine émet ses balises SEO (titre, description, Open Graph) par page (<code>partials/mp-seo.php</code>). Depuis les réglages, vous téléversez le <strong>logo</strong>, le <strong>favicon</strong> et l'<strong>image de partage (OG)</strong> de la boutique.</p>

    <h2 id="mpc-settings">Réglages</h2>
    <p><code>Marketplace → Réglages</code> centralise la configuration de la boutique :</p>
    <table class="doc-table">
      <tr><th>Réglage</th><th>Rôle</th></tr>
      <tr><td><strong>PayPal</strong></td><td>Mode <em>sandbox</em>/<em>live</em> + identifiants (Client ID / secret)</td></tr>
      <tr><td><strong>Devise</strong></td><td>Monnaie d'affichage &amp; de paiement (EUR par défaut)</td></tr>
      <tr><td><strong>Taille max / formats</strong></td><td>Limite d'upload (500 Mo) &amp; extensions autorisées</td></tr>
      <tr><td><strong>Identité &amp; contact</strong></td><td>Nom de la boutique, logo/favicon/OG, e-mail de contact</td></tr>
    </table>
    <div class="callout ok"><span class="i">✅</span><div>Une fois PayPal renseigné (en <em>live</em>) et au moins un produit publié, votre boutique est opérationnelle : les clients peuvent acheter, télécharger et récupérer leur facture.</div></div>

    <?php require __DIR__ . '/../../inc/foot.php'; ?>
