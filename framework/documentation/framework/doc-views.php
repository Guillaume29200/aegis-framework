<?php
$docPage = 'framework/doc-views.php';
$seo = ['title' => "Anatomie d'une vue admin — Design system .ui-* · GameNodePanel", 'desc' => "Le design system .ui-* d'Aegis : ui-card, ui-kpi, ui-table, ui-btn, ui-badge, ui-grid, adm-page-head — avec code et exemples visuels.", 'canonical' => 'https://gamenodepanel.com/documentation/doc-views.php'];
require __DIR__ . '/../inc/head.php';
$c_head = <<<'PHP'
<?php if (!defined('AEGIS_FRAMEWORK')) die('Access denied');
admin_header('Mon module — Titre'); ?>
<div class="adm-page-head">
  <div class="adm-breadcrumb">
    <a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><span>Mon module</span>
  </div>
  <h1>🧩 Mon module</h1>
  <p>Sous-titre de la page.</p>
</div>
<!-- … contenu … -->
<?php admin_footer(); ?>
PHP;
$c_card = <<<'HTML'
<div class="ui-card">
  <div class="ui-card-head">⚡ Titre de la carte</div>
  <div class="ui-card-body">Contenu…</div>
</div>
HTML;
$c_kpi = <<<'HTML'
<div class="ui-grid cols-4">
  <div class="ui-card tone-accent"><div class="ui-kpi">
    <div class="ui-kpi-icon">🧩</div>
    <div><p class="ui-kpi-label">Éléments</p>
         <div class="ui-kpi-value">42</div></div>
  </div></div>
</div>
HTML;
$c_table = <<<'HTML'
<table class="ui-table">
  <thead><tr><th>Nom</th><th>Statut</th><th class="u-right">Actions</th></tr></thead>
  <tbody>
    <tr><td>Item A</td><td><span class="ui-badge green">Actif</span></td>
        <td class="u-right"><button class="ui-btn sm">✏️</button></td></tr>
  </tbody>
</table>
HTML;
$c_btn = <<<'HTML'
<button class="ui-btn">Neutre</button>
<button class="ui-btn primary">Principal</button>
<button class="ui-btn danger">Danger</button>
<button class="ui-btn sm">Petit</button>
HTML;
$c_badge = <<<'HTML'
<span class="ui-badge green">Actif</span>
<span class="ui-badge amber">En attente</span>
<span class="ui-badge red">Erreur</span>
<span class="ui-badge blue">Info</span>
HTML;
$c_grid = <<<'HTML'
<div class="ui-grid cols-3">
  <div class="ui-card"><div class="ui-card-body">1</div></div>
  <div class="ui-card"><div class="ui-card-body">2</div></div>
  <div class="ui-card"><div class="ui-card-body">3</div></div>
</div>
HTML;
$c_form = <<<'HTML'
<form method="POST" action="<?= u('/admin/monmodule/store') ?>">
  <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
  <div class="fld">
    <label class="form-label">Titre</label>
    <input type="text" class="form-control" name="title" required>
  </div>
  <button class="ui-btn primary" type="submit">💾 Enregistrer</button>
</form>
HTML;
?>
    <h1>Anatomie d'une vue admin</h1>
    <p class="doc-lead">Les vues d'administration s'appuient sur le design system maison <strong>.ui-*</strong> — pas de Bootstrap. Voici les composants essentiels, avec code et rendu.</p>

    <h2 id="v-intro">Principe</h2>
    <p>Chaque vue est protégée par le garde <code>AEGIS_FRAMEWORK</code>, s'ouvre avec <code>admin_header()</code> et se ferme avec <code>admin_footer()</code>. Entre les deux, on assemble des composants <code>.ui-*</code>. Toute donnée affichée est échappée avec <code>htmlspecialchars</code>.</p>

    <h2 id="v-layout">En-tête de page</h2>
    <p><code>adm-page-head</code> + <code>adm-breadcrumb</code> donnent le fil d'Ariane, le titre et le sous-titre.</p>
    <pre><code><?= $h($c_head) ?></code></pre>

    <h2 id="v-card">Cartes — <code>ui-card</code></h2>
    <pre><code><?= $h($c_card) ?></code></pre>
    <div class="uidemo"><div class="d-card"><strong>⚡ Titre de la carte</strong><div style="color:var(--tx2);margin-top:6px">Contenu…</div></div></div>

    <h2 id="v-kpi">KPIs — <code>ui-kpi</code></h2>
    <pre><code><?= $h($c_kpi) ?></code></pre>
    <div class="uidemo"><div class="d-card" style="display:flex;align-items:center;gap:12px;max-width:240px"><span style="font-size:1.6rem">🧩</span><div><div style="font-size:.72rem;color:var(--tx3)">Éléments</div><div style="font-size:1.6rem;font-weight:800">42</div></div></div></div>

    <h2 id="v-table">Tableaux — <code>ui-table</code></h2>
    <pre><code><?= $h($c_table) ?></code></pre>
    <div class="uidemo"><table class="doc-table" style="margin:0"><thead><tr><th>Nom</th><th>Statut</th><th style="text-align:right">Actions</th></tr></thead><tbody><tr><td>Item A</td><td><span class="d-badge b-green">Actif</span></td><td style="text-align:right"><span class="d-btn" style="padding:4px 8px">✏️</span></td></tr></tbody></table></div>

    <h2 id="v-btn">Boutons — <code>ui-btn</code></h2>
    <pre><code><?= $h($c_btn) ?></code></pre>
    <div class="uidemo" style="display:flex;gap:8px;flex-wrap:wrap"><span class="d-btn">Neutre</span><span class="d-btn p">Principal</span><span class="d-btn dg">Danger</span><span class="d-btn" style="padding:6px 10px;font-size:.78rem">Petit</span></div>

    <h2 id="v-badge">Badges — <code>ui-badge</code></h2>
    <pre><code><?= $h($c_badge) ?></code></pre>
    <div class="uidemo" style="display:flex;gap:8px;flex-wrap:wrap"><span class="d-badge b-green">Actif</span><span class="d-badge b-amber">En attente</span><span class="d-badge b-red">Erreur</span><span class="d-badge b-blue">Info</span></div>

    <h2 id="v-grid">Grilles — <code>ui-grid</code></h2>
    <p>Des classes <code>cols-2</code>, <code>cols-3</code>, <code>cols-4</code> mettent en colonnes, responsives automatiquement.</p>
    <pre><code><?= $h($c_grid) ?></code></pre>
    <div class="uidemo"><div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px"><div class="d-card" style="text-align:center">1</div><div class="d-card" style="text-align:center">2</div><div class="d-card" style="text-align:center">3</div></div></div>

    <h2 id="v-form">Formulaires</h2>
    <p>Champs avec <code>.fld</code> / <code>.form-label</code> / <code>.form-control</code>. <strong>Toujours</strong> un champ <code>csrf_token</code> caché dans les formulaires POST.</p>
    <pre><code><?= $h($c_form) ?></code></pre>
    <div class="callout"><span class="i">🔐</span><div>Le jeton CSRF est obligatoire sur toute action mutante — voir <a href="framework/doc-security.php#s-csrf">Sécurité dans un module</a>.</div></div>

    <?php require __DIR__ . '/../inc/foot.php'; ?>
