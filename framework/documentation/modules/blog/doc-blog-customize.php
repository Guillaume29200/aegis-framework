<?php
$docPage = 'modules/blog/doc-blog-customize.php';
$seo = [
    'title' => 'Blog — Apparence & diffusion · Documentation Aegis Framework',
    'desc'  => "Personnaliser le blog Aegis : thèmes importables par ZIP (default & magazine), réglages (titre, pagination, commentaires), SEO, flux RSS et sitemap.",
    'canonical' => 'https://gamenodepanel.com/documentation/doc-blog-customize.php',
];
require __DIR__ . '/../../inc/head.php';
?>
    <h1>Apparence &amp; diffusion</h1>
    <p class="doc-lead">Habillez le blog avec des thèmes importables et diffusez-le largement grâce au flux RSS, au sitemap et au SEO par article.</p>
    <div class="doc-meta"><span class="doc-pill">Thèmes ZIP</span><span class="doc-pill">RSS &amp; sitemap</span><span class="doc-pill">SEO</span><span class="doc-pill">Réglages</span></div>

    <h2 id="blz-themes">Thèmes &amp; import ZIP</h2>
    <p><code>Blog → Thèmes</code> : <strong>activez</strong> un thème, <strong>importez-en</strong> un nouveau via une <strong>archive ZIP</strong>, ou supprimez-en. Deux thèmes sont fournis :</p>
    <table class="doc-table">
      <tr><th>Thème</th><th>Style</th></tr>
      <tr><td><strong>default</strong></td><td>Présentation classique &amp; épurée du blog</td></tr>
      <tr><td><strong>magazine</strong></td><td>Mise en page type magazine (mise en avant des visuels)</td></tr>
    </table>
    <p>Un thème est un dossier sous <code>Themes/</code> avec un <code>theme.json</code> et ses gabarits (<code>layout</code>, <code>index</code>, <code>category</code>, <code>single</code>). La feuille de style du blog est servie via <code>/blog/theme.css</code>.</p>
    <div class="callout"><span class="i">🎨</span><div>Changer de thème ne touche <strong>ni vos articles ni vos commentaires</strong> : seule l'apparence côté lecteur change.</div></div>

    <h2 id="blz-settings">Réglages</h2>
    <p><code>Blog → Réglages</code> centralise la configuration :</p>
    <table class="doc-table">
      <tr><th>Réglage</th><th>Effet (défaut)</th></tr>
      <tr><td><strong>Titre &amp; description</strong></td><td>Identité du blog (utilisés aussi pour le SEO &amp; le RSS)</td></tr>
      <tr><td><strong>Articles par page</strong></td><td>Pagination de la liste publique (9 par défaut, 1–60)</td></tr>
      <tr><td><strong>Commentaires activés</strong></td><td>Autoriser ou non les commentaires (oui par défaut)</td></tr>
      <tr><td><strong>Modération</strong></td><td>Exiger une validation avant publication (oui par défaut)</td></tr>
      <tr><td><strong>Commentaires invités</strong></td><td>Autoriser les non-connectés à commenter (oui par défaut)</td></tr>
      <tr><td><strong>Thème actif</strong></td><td>Le thème de la vitrine</td></tr>
    </table>

    <h2 id="blz-seo">SEO</h2>
    <p>Chaque page du blog émet ses métadonnées (titre, description, Open Graph) à partir de l'article et des réglages du blog. La liste, les catégories et les tags ont des URL propres et lisibles (<code>/blog</code>, <code>/blog/c/&lt;slug&gt;</code>, <code>/blog/tag/&lt;slug&gt;</code>, <code>/blog/&lt;slug&gt;</code>).</p>

    <h2 id="blz-feed">Flux RSS &amp; sitemap</h2>
    <table class="doc-table">
      <tr><th>Ressource</th><th>URL</th></tr>
      <tr><td><strong>Flux RSS</strong></td><td><code>/blog/feed</code> — pour les agrégateurs &amp; abonnés</td></tr>
      <tr><td><strong>Sitemap</strong></td><td><code>/blog/sitemap.xml</code> — pour l'indexation par les moteurs</td></tr>
    </table>
    <div class="callout ok"><span class="i">✅</span><div>Une fois un thème actif, le titre/description renseignés et un premier article publié, votre blog est opérationnel, indexable (sitemap) et suivable (RSS).</div></div>

    <?php require __DIR__ . '/../../inc/foot.php'; ?>
