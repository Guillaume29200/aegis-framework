<?php
/**
 * Gestion des modules — Aegis Framework V4 (UI maison)
 * Variables : $modules[], $stats[], $csrfToken
 */
if (!defined('AEGIS_FRAMEWORK')) die('Access denied');

$pageTitle = $pageTitle ?? 'Modules';
admin_header($pageTitle);

$modules = $modules ?? [];
$stats   = $stats ?? ['total' => 0, 'active' => 0, 'inactive' => 0, 'loaded' => 0];
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>

<div class="adm-page-head" style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
    <div>
        <div class="adm-breadcrumb"><a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><span>Modules</span></div>
        <h1>🧩 Modules</h1>
        <p>Activez ou désactivez les fonctionnalités de votre CMS.</p>
    </div>
    <div class="u-flex u-gap" style="flex-shrink:0">
        <a class="ui-btn" href="<?= u('/admin/modules/generate') ?>">🪄 Générer un module</a>
        <button type="button" class="ui-btn primary" onclick="document.getElementById('mod-upload').classList.toggle('open')">⬆️ Installer (.zip)</button>
    </div>
</div>

<?php
$flashOk  = $_SESSION['success'] ?? null; unset($_SESSION['success']);
$flashErr = $_SESSION['error'] ?? null;   unset($_SESSION['error']);
if ($flashOk): ?><div class="ui-card" style="border-color:var(--green-soft);margin-bottom:14px"><div class="ui-card-body" style="color:var(--green)"><?= $h($flashOk) ?></div></div><?php endif;
if ($flashErr): ?><div class="ui-card" style="border-color:var(--red-soft);margin-bottom:14px"><div class="ui-card-body" style="color:var(--red)"><?= $h($flashErr) ?></div></div><?php endif; ?>

<div class="ui-card" id="mod-upload" style="margin-bottom:18px">
    <div class="ui-card-head">⬆️ Installer / mettre à jour un module depuis une archive ZIP</div>
    <div class="ui-card-body">
        <form method="post" action="<?= u('/admin/modules/upload') ?>" enctype="multipart/form-data" class="u-flex u-gap" style="flex-wrap:wrap;align-items:center">
            <input type="hidden" name="csrf_token" value="<?= $h($csrfToken ?? '') ?>">
            <input class="form-control" type="file" name="module_zip" accept=".zip" required style="flex:1;min-width:240px">
            <button class="ui-btn primary" type="submit">Installer</button>
        </form>
        <p class="u-muted" style="font-size:12px;margin:10px 0 0">L'archive doit contenir un <code>module.json</code> valide. Le module est extrait dans <code>/modules</code> mais <strong>pas activé automatiquement</strong> — vous l'activez ensuite ci-dessous (avec vérification des tables).</p>
    </div>
</div>
<style>#mod-upload{display:none} #mod-upload.open{display:block}</style>

<div class="ui-grid cols-4" style="margin-bottom:18px">
    <div class="ui-card tone-accent"><div class="ui-kpi"><div class="ui-kpi-icon">📦</div><div><p class="ui-kpi-label">Total</p><div class="ui-kpi-value"><?= (int)$stats['total'] ?></div></div></div></div>
    <div class="ui-card tone-green"><div class="ui-kpi"><div class="ui-kpi-icon">✅</div><div><p class="ui-kpi-label">Actifs</p><div class="ui-kpi-value"><?= (int)$stats['active'] ?></div></div></div></div>
    <div class="ui-card tone-amber"><div class="ui-kpi"><div class="ui-kpi-icon">⏸️</div><div><p class="ui-kpi-label">Inactifs</p><div class="ui-kpi-value"><?= (int)$stats['inactive'] ?></div></div></div></div>
    <div class="ui-card tone-blue"><div class="ui-kpi"><div class="ui-kpi-icon">⚡</div><div><p class="ui-kpi-label">Chargés</p><div class="ui-kpi-value"><?= (int)$stats['loaded'] ?></div></div></div></div>
</div>

<div class="u-flex u-gap" style="margin-bottom:16px" id="mod-filters">
    <button class="ui-btn sm primary" data-filter="all">📋 Tous</button>
    <button class="ui-btn sm" data-filter="active">🟢 Actifs</button>
    <button class="ui-btn sm" data-filter="inactive">🟡 Inactifs</button>
</div>

<?php
/**
 * La liste des modules, en tableau.
 *
 * La grille de cartes tenait tant qu'il y avait dix modules. Passé ce cap,
 * on ne trouve plus rien : il faut balayer des vignettes de la même taille
 * pour repérer un nom. Un tableau se lit par colonnes, se trie, et surtout
 * se filtre.
 *
 * Deux tableaux plutôt qu'un : ce qui tourne et ce qui dort ne se cherchent
 * pas de la même façon. Les modules cœur ouvrent la marche — ce sont eux qui
 * portent le reste — puis vient le classement par catégorie.
 */
$activeModules   = $activeModules ?? [];
$inactiveModules = $inactiveModules ?? [];
$allCategories   = $allCategories ?? [];

$catIcons = [
    'Système' => '🛠️', 'Communautaire' => '💬', 'e-commerce' => '🛒',
    'Autres'  => '🧩', 'Hosting' => '🗄️', 'esport' => '🏆',
];

/** Une ligne de tableau, identique dans les deux tables. */
$ligne = function (array $m) use ($h, $catIcons): void {
    $active    = !empty($m['active']);
    $installed = !empty($m['installed']);
    $core      = !empty($m['is_core']);
    $cat       = (string) $m['category'];
    $icone     = $catIcons[$cat] ?? '📦';
    ?>
    <tr class="mod-row"
        data-name="<?= $h(mb_strtolower($m['display_name'] . ' ' . $m['name'] . ' ' . $m['description'])) ?>"
        data-cat="<?= $h($cat) ?>"
        data-state="<?= $active ? 'active' : ($installed ? 'disabled' : 'missing') ?>"
        data-core="<?= $core ? '1' : '0' ?>">

        <td>
            <div class="mod-cell">
                <span class="mod-ico"><?= $icone ?></span>
                <span class="mod-id">
                    <b><?= $h($m['display_name']) ?><?php if ($core): ?> <span class="ui-badge" title="Module cœur : ni désactivable, ni supprimable">🔒</span><?php endif; ?></b>
                    <em><?= $h($m['description']) ?></em>
                </span>
            </div>
        </td>

        <td class="u-muted" style="white-space:nowrap"><?= $icone ?> <?= $h($cat) ?></td>
        <td class="u-muted" style="white-space:nowrap;font-variant-numeric:tabular-nums">v<?= $h($m['version']) ?></td>
        <td class="u-muted" style="white-space:nowrap"><?= $h($m['author']) ?></td>

        <td style="white-space:nowrap">
            <?php if ($active): ?>
                <span class="ui-badge green">🟢 Actif</span>
            <?php elseif ($installed): ?>
                <span class="ui-badge amber">🟡 Désactivé</span>
            <?php else: ?>
                <span class="ui-badge">⬇️ Non installé</span>
            <?php endif; ?>
        </td>

        <td style="white-space:nowrap">
            <?php if ($active && $m['public_canonical'] !== ''): ?>
            <a class="mod-pub__url" href="<?= u('/' . $h($m['public_prefix'])) ?>" target="_blank" rel="noopener">/<?= $h($m['public_prefix']) ?></a>
            <button class="ui-btn sm" title="Changer l'adresse publique"
                    onclick="renamePrefix('<?= $h($m['name']) ?>','<?= $h($m['display_name']) ?>','<?= $h($m['public_prefix']) ?>','<?= $h($m['public_canonical']) ?>','<?= $h($m['public_hint']) ?>')">✏️</button>
            <?php else: ?>
            <span class="u-muted">—</span>
            <?php endif; ?>
        </td>

        <td style="text-align:right;white-space:nowrap">
            <div class="mod-acts">
                <?php if ($active && !empty($m['dashboard_url'])): ?>
                <a class="ui-btn sm primary" href="<?= u($h($m['dashboard_url'])) ?>" title="Tableau de bord">📊</a>
                <?php endif; ?>

                <button class="ui-btn sm" onclick="moduleInfo('<?= $h($m['name']) ?>')" title="Détails">ℹ️</button>

                <?php if ($core): ?>
                    <!-- Ni désactivation ni suppression : le bouton n'existe pas
                         plutôt que d'être présent et refusé au clic. -->
                <?php elseif ($active): ?>
                    <button class="ui-btn sm danger" onclick="toggleModule('<?= $h($m['name']) ?>','deactivate')" title="Désactiver">⏸️</button>
                <?php else: ?>
                    <button class="ui-btn sm primary" onclick="toggleModule('<?= $h($m['name']) ?>','activate')" title="Activer">✅</button>
                <?php endif; ?>

                <?php if (!$core): ?>
                <button class="ui-btn sm danger" title="Supprimer définitivement"
                        onclick="confirmDeleteModule('<?= $h($m['name']) ?>','<?= $h($m['display_name']) ?>')">🗑️</button>
                <?php endif; ?>
            </div>
        </td>
    </tr>
    <?php
};
?>

<!-- ── Barre de recherche et de filtres ─────────────────────────────────── -->
<div class="ui-card" style="margin-bottom:18px">
    <div class="ui-card-body mod-bar">
        <input class="form-control" type="search" id="modSearch" autocomplete="off"
               placeholder="Chercher un module par nom ou description…">

        <select class="form-select" id="modCat" aria-label="Filtrer par catégorie">
            <option value="">Toutes les catégories</option>
            <?php foreach ($allCategories as $c): ?>
            <option value="<?= $h($c) ?>"><?= $catIcons[$c] ?? '📦' ?> <?= $h($c) ?></option>
            <?php endforeach; ?>
        </select>

        <select class="form-select" id="modState" aria-label="Filtrer par état">
            <option value="">Tous les états</option>
            <option value="active">🟢 Actifs</option>
            <option value="disabled">🟡 Désactivés</option>
            <option value="missing">⬇️ Non installés</option>
        </select>

        <label class="mod-check">
            <input type="checkbox" id="modCore"> 🔒 Cœur seulement
        </label>

        <button class="ui-btn" type="button" id="modReset">Réinitialiser</button>
    </div>
</div>

<!-- ── Modules actifs ───────────────────────────────────────────────────── -->
<div class="ui-card" style="margin-bottom:18px" data-table="active">
    <div class="ui-card-head">
        🟢 Modules actifs <span class="ui-badge mod-count"><?= count($activeModules) ?></span>
    </div>
    <div class="ui-card-body" style="padding:0">
        <div style="overflow-x:auto">
            <table class="ui-table mod-table">
                <thead><tr>
                    <th>Module</th><th>Catégorie</th><th>Version</th><th>Auteur</th>
                    <th>État</th><th>Adresse publique</th><th style="text-align:right">Actions</th>
                </tr></thead>
                <tbody>
                    <?php foreach ($activeModules as $m) { $ligne($m); } ?>
                </tbody>
            </table>
        </div>
        <div class="mod-empty" hidden>Aucun module actif ne correspond à cette recherche.</div>
        <nav class="mod-pager" hidden></nav>
    </div>
</div>

<!-- ── Modules inactifs ─────────────────────────────────────────────────── -->
<div class="ui-card" data-table="inactive">
    <div class="ui-card-head">
        ⏸️ Désactivés ou non installés <span class="ui-badge mod-count"><?= count($inactiveModules) ?></span>
    </div>
    <div class="ui-card-body" style="padding:0">
        <?php if (!$inactiveModules): ?>
            <p class="u-muted" style="text-align:center;padding:26px;margin:0">
                Tous les modules présents sont actifs.
            </p>
        <?php else: ?>
        <div style="overflow-x:auto">
            <table class="ui-table mod-table">
                <thead><tr>
                    <th>Module</th><th>Catégorie</th><th>Version</th><th>Auteur</th>
                    <th>État</th><th>Adresse publique</th><th style="text-align:right">Actions</th>
                </tr></thead>
                <tbody>
                    <?php foreach ($inactiveModules as $m) { $ligne($m); } ?>
                </tbody>
            </table>
        </div>
        <div class="mod-empty" hidden>Aucun module inactif ne correspond à cette recherche.</div>
        <nav class="mod-pager" hidden></nav>
        <?php endif; ?>
    </div>
</div>

<style>
/* La barre de filtres.

   `.form-control` et `.form-select` portent `width: 100%` dans la feuille du
   thème — pensées pour un formulaire en colonnes. Dans une barre en flex,
   cette largeur fait réclamer à chaque liste déroulante la totalité de la
   place : elles se poussaient les unes les autres à la ligne et le bloc
   partait en morceaux. On leur rend donc une largeur propre.

   Les hauteurs sont fixées explicitement : champs et boutons ont le même
   rembourrage mais pas la même taille de police, ce qui suffit à décaler les
   uns par rapport aux autres de deux pixels — visible sur une seule ligne. */
.mod-bar { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }

/* Tout doit pouvoir rétrécir : la colonne d'administration ne laisse que
   ~910 px, et cinq contrôles à taille fixe en réclamaient 1030. Le bouton
   se retrouvait seul sur une deuxième ligne. */
.mod-bar #modSearch {
  flex: 1 1 190px;
  width: auto;
  min-width: 0;
}
.mod-bar .form-select {
  flex: 0 1 auto;
  width: auto;
  min-width: 0;
  /* Assez large pour lire « Intelligence artificielle » sans être figée. */
  max-width: 200px;
}
.mod-bar #modSearch,
.mod-bar .form-select,
.mod-bar .ui-btn {
  height: 38px;
  box-sizing: border-box;
}
.mod-bar .ui-btn { flex: 0 0 auto; }

.mod-check {
  display: flex; align-items: center; gap: 7px;
  height: 38px; padding: 0 4px;
  font-size: 13px; white-space: nowrap; cursor: pointer;
  flex: 0 0 auto;
}
.mod-check input { margin: 0; }

.mod-table { width: 100%; }
.mod-table th { white-space: nowrap; }
.mod-cell { display: flex; align-items: center; gap: 11px; min-width: 0; }
.mod-ico {
  flex: 0 0 auto; width: 34px; height: 34px;
  border-radius: 9px; background: var(--accent-soft, rgba(127,127,127,.12));
  display: grid; place-items: center; font-size: 17px;
}
.mod-id { display: flex; flex-direction: column; min-width: 0; gap: 2px; }
.mod-id b { font-size: 13.5px; font-weight: 600; }
/* La description est coupée à une ligne : c'est un repère, pas une lecture.
   Le détail complet est derrière le bouton ℹ️. */
.mod-id em {
  font-style: normal; font-size: 11.5px; opacity: .65;
  max-width: 460px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.mod-acts { display: inline-flex; gap: 5px; justify-content: flex-end; }
.mod-empty { padding: 26px; text-align: center; opacity: .65; font-size: 13.5px; }

.mod-pager {
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
  padding: 12px 16px; border-top: 1px solid var(--border, rgba(127,127,127,.18));
}
.mod-pager .mod-pages { display: flex; gap: 4px; flex-wrap: wrap; }
.mod-pager button {
  min-width: 30px; height: 30px; padding: 0 9px;
  border: 1px solid var(--border, rgba(127,127,127,.25));
  border-radius: 7px; background: transparent; color: inherit;
  font-size: 12.5px; cursor: pointer;
}
.mod-pager button:hover:not(:disabled) { background: rgba(127,127,127,.12); }
.mod-pager button.is-on { background: var(--accent, #6366f1); border-color: var(--accent, #6366f1); color: #fff; font-weight: 700; }
.mod-pager button:disabled { opacity: .38; cursor: default; }
.mod-pager .mod-info { font-size: 12px; opacity: .7; font-variant-numeric: tabular-nums; }

@media (max-width: 780px) {
  /* En étroit, on sacrifie ce qui se retrouve dans « Détails ». */
  .mod-table th:nth-child(3), .mod-table td:nth-child(3),
  .mod-table th:nth-child(4), .mod-table td:nth-child(4),
  .mod-table th:nth-child(6), .mod-table td:nth-child(6) { display: none; }
  .mod-id em { display: none; }
  .mod-bar .form-select, .mod-bar #modSearch { flex: 1 1 100%; }
}
</style>

<script>
/* Recherche, filtres et pagination — sur ce qui est déjà rendu.
   Les modules tiennent tous dans la page : aucun aller-retour serveur, et le
   compteur ne peut pas mentir sur ce qui est affiché. */
(function () {
  var PAR_PAGE = 15;

  var search = document.getElementById('modSearch');
  var selCat = document.getElementById('modCat');
  var selEta = document.getElementById('modState');
  var chkCor = document.getElementById('modCore');
  var reset  = document.getElementById('modReset');

  var tables = [].slice.call(document.querySelectorAll('[data-table]')).map(function (bloc) {
    return {
      bloc:  bloc,
      lignes: [].slice.call(bloc.querySelectorAll('.mod-row')),
      vide:  bloc.querySelector('.mod-empty'),
      pager: bloc.querySelector('.mod-pager'),
      compte: bloc.querySelector('.mod-count'),
      page:  1
    };
  });

  if (!tables.length) { return; }

  function retenu(tr, mot, cat, etat, coeur) {
    if (mot   && (tr.getAttribute('data-name') || '').indexOf(mot) === -1) { return false; }
    if (cat   && tr.getAttribute('data-cat')   !== cat)  { return false; }
    if (etat  && tr.getAttribute('data-state') !== etat) { return false; }
    if (coeur && tr.getAttribute('data-core')  !== '1')  { return false; }
    return true;
  }

  function pagine(t, gardees) {
    var pages = Math.max(1, Math.ceil(gardees.length / PAR_PAGE));
    if (t.page > pages) { t.page = pages; }

    var debut = (t.page - 1) * PAR_PAGE;
    gardees.forEach(function (tr, i) {
      tr.hidden = (i < debut || i >= debut + PAR_PAGE);
    });

    if (!t.pager) { return; }

    // Une pagination d'une seule page n'apporte rien : on la retire.
    if (pages <= 1) { t.pager.hidden = true; t.pager.innerHTML = ''; return; }

    t.pager.hidden = false;
    var html = '<span class="mod-info">' + gardees.length + ' module' + (gardees.length > 1 ? 's' : '')
             + ' · page ' + t.page + ' sur ' + pages + '</span><span class="mod-pages">';
    html += '<button type="button" data-p="' + (t.page - 1) + '"' + (t.page === 1 ? ' disabled' : '') + '>←</button>';
    for (var p = 1; p <= pages; p++) {
      html += '<button type="button" data-p="' + p + '"' + (p === t.page ? ' class="is-on"' : '') + '>' + p + '</button>';
    }
    html += '<button type="button" data-p="' + (t.page + 1) + '"' + (t.page === pages ? ' disabled' : '') + '>→</button>';
    t.pager.innerHTML = html + '</span>';
  }

  function applique(remetPage) {
    var mot   = (search && search.value || '').trim().toLowerCase();
    var cat   = selCat ? selCat.value : '';
    var etat  = selEta ? selEta.value : '';
    var coeur = chkCor ? chkCor.checked : false;

    tables.forEach(function (t) {
      if (remetPage) { t.page = 1; }

      var gardees = [];
      t.lignes.forEach(function (tr) {
        if (retenu(tr, mot, cat, etat, coeur)) { gardees.push(tr); }
        else { tr.hidden = true; }
      });

      if (t.compte) { t.compte.textContent = gardees.length; }
      if (t.vide)   { t.vide.hidden = gardees.length !== 0; }

      pagine(t, gardees);
    });
  }

  [search, selCat, selEta, chkCor].forEach(function (el) {
    if (!el) { return; }
    el.addEventListener(el.tagName === 'INPUT' && el.type === 'search' ? 'input' : 'change', function () {
      applique(true);
    });
  });

  if (reset) {
    reset.addEventListener('click', function () {
      if (search) { search.value = ''; }
      if (selCat) { selCat.value = ''; }
      if (selEta) { selEta.value = ''; }
      if (chkCor) { chkCor.checked = false; }
      applique(true);
    });
  }

  tables.forEach(function (t) {
    if (!t.pager) { return; }
    t.pager.addEventListener('click', function (e) {
      var b = e.target.closest('button[data-p]');
      if (!b || b.disabled) { return; }
      t.page = parseInt(b.getAttribute('data-p'), 10) || 1;
      applique(false);
      // On remonte en haut du tableau : changer de page en restant au milieu
      // donne l'impression que rien ne s'est passé.
      t.bloc.scrollIntoView({ block: 'start', behavior: 'smooth' });
    });
  });

  applique(true);
})();
</script>


<style>
.mod-cat { margin-bottom: 26px; }
.mod-cat-head { display: flex; align-items: center; gap: 10px; margin: 0 2px 12px; }
.mod-cat-title { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: var(--text-soft); }
.mod-cat-head::after { content: ""; flex: 1; height: 1px; background: var(--border); margin-left: 4px; }
</style>
</div>

<!-- Modal détails / changelog -->
<div class="mod-modal" id="mod-modal" hidden>
    <div class="mod-modal-backdrop" data-close></div>
    <div class="mod-modal-box" role="dialog" aria-modal="true">
        <div class="mod-modal-head">
            <h3 id="mod-modal-title">Module</h3>
            <button class="adm-icon-btn" data-close title="Fermer">✕</button>
        </div>
        <div class="mod-modal-body" id="mod-modal-body">
            <div class="u-muted" style="text-align:center;padding:24px">Chargement…</div>
        </div>
    </div>
</div>

<!-- Modal suppression (danger) -->
<div class="mod-modal" id="mod-del-modal" hidden>
    <div class="mod-modal-backdrop" data-del-close></div>
    <div class="mod-modal-box" role="dialog" aria-modal="true" style="width:min(520px,96vw)">
        <div class="mod-modal-head" style="border-bottom-color:var(--red-soft)">
            <h3 style="color:var(--red)">🗑️ Supprimer un module</h3>
            <button class="adm-icon-btn" data-del-close title="Fermer">✕</button>
        </div>
        <div class="mod-modal-body">
            <div class="ui-card" style="border-color:var(--red-soft);margin-bottom:14px"><div class="ui-card-body" style="color:var(--red)">
                ⚠️ <strong>Action irréversible.</strong> Vous êtes sur le point de supprimer le module
                <strong id="mod-del-name">—</strong>. Cela va :
                <ul style="margin:8px 0 0;padding-left:20px">
                    <li>supprimer définitivement <strong>le dossier du module</strong> sur le disque ;</li>
                    <li>exécuter sa désinstallation et <strong>supprimer ses tables</strong> (et donc ses données) ;</li>
                    <li>retirer ses entrées de menu et son enregistrement.</li>
                </ul>
            </div></div>
            <p class="u-muted" style="font-size:13px">Pour confirmer, saisissez le nom du module : <code id="mod-del-hint"></code></p>
            <input class="form-control" id="mod-del-confirm" placeholder="Nom du module" autocomplete="off" style="margin-bottom:14px">
            <div class="u-flex u-gap" style="justify-content:flex-end">
                <button class="ui-btn" data-del-close>Annuler</button>
                <button class="ui-btn danger" id="mod-del-go" disabled>🗑️ Supprimer définitivement</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal : adresse publique du module -->
<div class="mod-modal" id="mod-pfx-modal" hidden>
    <div class="mod-modal-backdrop" data-pfx-close></div>
    <div class="mod-modal-box" role="dialog" aria-modal="true" style="width:min(560px,96vw)">
        <div class="mod-modal-head">
            <h3>🔗 Adresse publique — <span id="mod-pfx-module">—</span></h3>
            <button class="adm-icon-btn" data-pfx-close title="Fermer">✕</button>
        </div>
        <div class="mod-modal-body">
            <p class="u-muted" style="font-size:13px;margin:0 0 14px" id="mod-pfx-hint"></p>

            <label class="ui-field-label" for="mod-pfx-input">Nouvelle adresse</label>
            <div class="mod-pfx-field">
                <span class="mod-pfx-base" id="mod-pfx-base">/</span>
                <input class="form-control" id="mod-pfx-input" placeholder="site" autocomplete="off" spellcheck="false">
            </div>
            <p class="u-muted" style="font-size:12px;margin:8px 0 0">
                Minuscules, chiffres et tirets. Aperçu :
                <code id="mod-pfx-preview">—</code>
            </p>

            <div class="ui-card" style="margin:14px 0 0"><div class="ui-card-body" style="font-size:12.5px">
                L'adresse d'administration ne change pas. Les anciennes adresses restent
                valides : elles redirigent en permanence vers la nouvelle, donc les liens
                déjà partagés et le référencement sont préservés.
            </div></div>

            <p id="mod-pfx-msg" class="mod-pfx-msg" hidden></p>

            <div class="u-flex u-gap" style="justify-content:space-between;margin-top:16px">
                <button class="ui-btn sm" id="mod-pfx-reset" type="button">↩️ Revenir à l'adresse d'origine</button>
                <span class="u-flex u-gap">
                    <button class="ui-btn" data-pfx-close>Annuler</button>
                    <button class="ui-btn primary" id="mod-pfx-go">Enregistrer</button>
                </span>
            </div>
        </div>
    </div>
</div>

<style>
.mod-pub{display:flex;align-items:center;gap:8px;margin-bottom:10px;padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius);background:var(--surface-2,rgba(127,127,127,.05))}
.mod-pub__k{font-size:11px;font-weight:700;letter-spacing:.3px;text-transform:uppercase;color:var(--text-muted,#8b93a7)}
.mod-pub__url{margin-left:auto;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12.5px;font-weight:700;text-decoration:none}
.mod-pub__url:hover{text-decoration:underline}
.mod-pfx-field{display:flex;align-items:center;gap:0}
.mod-pfx-base{padding:0 10px;height:38px;display:flex;align-items:center;border:1px solid var(--border);border-right:0;border-radius:var(--radius) 0 0 var(--radius);background:var(--surface-2,rgba(127,127,127,.06));font-family:ui-monospace,monospace;font-size:12.5px;color:var(--text-muted,#8b93a7);white-space:nowrap}
.mod-pfx-field .form-control{border-radius:0 var(--radius) var(--radius) 0;font-family:ui-monospace,monospace}
.mod-pfx-msg{margin:14px 0 0;padding:10px 12px;border-radius:var(--radius);font-size:12.5px}
.mod-pfx-msg.is-ko{border:1px solid var(--red-soft);color:var(--red)}
.mod-pfx-msg.is-ok{border:1px solid var(--green-soft,rgba(16,185,129,.35));color:var(--green,#10b981)}
.mod-modal{position:fixed;inset:0;z-index:200;display:flex;align-items:center;justify-content:center;padding:20px}
.mod-modal[hidden]{display:none}
.mod-modal-backdrop{position:absolute;inset:0;background:rgba(8,12,20,.55);backdrop-filter:blur(2px)}
.mod-modal-box{position:relative;width:min(880px,96vw);max-height:88vh;overflow:auto;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:var(--shadow-lg)}
.mod-modal-head{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--surface);z-index:1}
.mod-modal-head h3{margin:0;font-size:17px}
.mod-modal-body{padding:20px}
.mod-cl-version{border-left:3px solid var(--accent);padding:2px 0 2px 14px;margin:0 0 10px}
.mod-cl-version>summary{list-style:none;cursor:pointer;display:flex;align-items:center;gap:10px;padding:4px 0;font-size:14px;font-weight:700;user-select:none}
.mod-cl-version>summary::-webkit-details-marker{display:none}
.mod-cl-version>summary::before{content:'▸';color:var(--accent);font-size:.9rem;transition:transform .15s}
.mod-cl-version[open]>summary::before{transform:rotate(90deg)}
.mod-cl-version .mod-cl-ver{color:var(--text)}
.mod-cl-version .mod-cl-date{font-size:12px;color:var(--text-faint);font-weight:400}
.mod-cl-version ul{margin:6px 0 4px;padding-left:18px}
.mod-cl-version li{margin-bottom:4px;font-size:13px;color:var(--text-soft)}
</style>

<script>
(function () {
    var TOGGLE = '<?= u('/admin/modules/toggle') ?>', CSRF = '<?= $h($csrfToken ?? '') ?>';
    var INFO = '<?= u('/admin/modules/info') ?>';
    var modal = document.getElementById('mod-modal');
    var modalBody = document.getElementById('mod-modal-body');
    var modalTitle = document.getElementById('mod-modal-title');
    var esc = function (s) { var d = document.createElement('div'); d.textContent = (s == null ? '' : String(s)); return d.innerHTML; };

    function closeModal() { modal.hidden = true; }
    modal.querySelectorAll('[data-close]').forEach(function (el) { el.addEventListener('click', closeModal); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });

    // ── Suppression de module (modal de confirmation par saisie du nom) ──
    var DELETE = '<?= u('/admin/modules/delete') ?>';
    var delModal = document.getElementById('mod-del-modal');
    var delInput = document.getElementById('mod-del-confirm');
    var delGo = document.getElementById('mod-del-go');
    var delTarget = null;
    function closeDel() { delModal.hidden = true; delInput.value = ''; delGo.disabled = true; delTarget = null; }
    delModal.querySelectorAll('[data-del-close]').forEach(function (el) { el.addEventListener('click', closeDel); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeDel(); });
    delInput.addEventListener('input', function () { delGo.disabled = (delInput.value.trim() !== delTarget); });

    window.confirmDeleteModule = function (name, display) {
        delTarget = name;
        document.getElementById('mod-del-name').textContent = display || name;
        document.getElementById('mod-del-hint').textContent = name;
        delModal.hidden = false;
        setTimeout(function () { delInput.focus(); }, 50);
    };

    delGo.addEventListener('click', function () {
        if (!delTarget || delInput.value.trim() !== delTarget) return;
        delGo.disabled = true; delGo.textContent = '⏳ Suppression…';
        var fd = new FormData();
        fd.append('module', delTarget); fd.append('csrf_token', CSRF);
        fetch(DELETE, { method: 'POST', body: fd }).then(function (r) { return r.json(); }).then(function (d) {
            alert((d.success ? '✅ ' : '❌ ') + d.message);
            if (d.success) location.reload(); else { delGo.disabled = false; delGo.textContent = '🗑️ Supprimer définitivement'; }
        }).catch(function (e) { alert('❌ ' + e); delGo.disabled = false; delGo.textContent = '🗑️ Supprimer définitivement'; });
    });

    // ── Adresse publique d'un module ────────────────────────────────────
    var PREFIX = '<?= u('/admin/modules/prefix') ?>';
    var BASE   = '<?= $h(rtrim(BASE_URL, '/')) ?>';
    var pfxModal = document.getElementById('mod-pfx-modal');
    var pfxInput = document.getElementById('mod-pfx-input');
    var pfxMsg   = document.getElementById('mod-pfx-msg');
    var pfxGo    = document.getElementById('mod-pfx-go');
    var pfxState = { module: null, canonical: '' };

    function closePfx() { pfxModal.hidden = true; pfxMsg.hidden = true; pfxState.module = null; }
    pfxModal.querySelectorAll('[data-pfx-close]').forEach(function (el) { el.addEventListener('click', closePfx); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closePfx(); });

    function pfxPreview() {
        var v = pfxInput.value.trim().replace(/^\/+|\/+$/g, '').toLowerCase();
        document.getElementById('mod-pfx-preview').textContent = BASE + '/' + (v || '…');
    }
    pfxInput.addEventListener('input', pfxPreview);

    function pfxSay(text, ok) {
        pfxMsg.textContent = text;
        pfxMsg.className = 'mod-pfx-msg ' + (ok ? 'is-ok' : 'is-ko');
        pfxMsg.hidden = false;
    }

    window.renamePrefix = function (name, display, current, canonical, hint) {
        pfxState.module = name;
        pfxState.canonical = canonical;
        document.getElementById('mod-pfx-module').textContent = display || name;
        document.getElementById('mod-pfx-hint').textContent = hint || '';
        document.getElementById('mod-pfx-base').textContent = BASE + '/';
        pfxInput.value = current;
        pfxMsg.hidden = true;
        pfxPreview();
        pfxModal.hidden = false;
        setTimeout(function () { pfxInput.focus(); pfxInput.select(); }, 50);
    };

    document.getElementById('mod-pfx-reset').addEventListener('click', function () {
        pfxInput.value = pfxState.canonical;
        pfxPreview();
    });

    pfxInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') pfxGo.click(); });

    pfxGo.addEventListener('click', function () {
        if (!pfxState.module) return;
        pfxGo.disabled = true; pfxGo.textContent = '⏳ Enregistrement…';

        var fd = new FormData();
        fd.append('module', pfxState.module);
        fd.append('prefix', pfxInput.value.trim());
        fd.append('csrf_token', CSRF);

        // Le statut est lu avant le corps : une erreur serveur renvoie du HTML,
        // que .json() ferait échouer sur un message incompréhensible.
        fetch(PREFIX, { method: 'POST', body: fd })
            .then(function (r) { return r.text().then(function (t) { return { status: r.status, body: t }; }); })
            .then(function (res) {
                var d;
                try { d = JSON.parse(res.body); }
                catch (e) { throw new Error('Réponse inattendue du serveur (HTTP ' + res.status + ').'); }

                if (!d.success) { pfxSay(d.message || 'Enregistrement refusé.', false); return; }

                pfxSay(d.message + ' Rechargement…', true);
                setTimeout(function () { location.reload(); }, 900);
            })
            .catch(function (e) { pfxSay('❌ ' + e.message, false); })
            .finally(function () { pfxGo.disabled = false; pfxGo.textContent = 'Enregistrer'; });
    });

    window.moduleInfo = function (name) {
        modalTitle.textContent = name;
        modalBody.innerHTML = '<div class="u-muted" style="text-align:center;padding:24px">Chargement…</div>';
        modal.hidden = false;
        fetch(INFO + '?module=' + encodeURIComponent(name))
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.success) { modalBody.innerHTML = '<p class="u-muted">' + esc(d.message || 'Erreur') + '</p>'; return; }
                var m = d.module, html = '';
                modalTitle.textContent = (m.name || name) + ' — v' + esc(m.version);
                html += '<p style="margin:0 0 12px">' + esc(m.description) + '</p>';
                html += '<div class="u-flex u-gap" style="flex-wrap:wrap;margin-bottom:8px">';
                html += '<span class="ui-badge">👤 ' + esc(m.author || 'Inconnu') + '</span>';
                html += '<span class="ui-badge">🏷️ v' + esc(m.version) + '</span>';
                if (m.protected) html += '<span class="ui-badge">🔒 Module cœur</span>';
                var deps = m.dependencies || [];
                if (deps && (Array.isArray(deps) ? deps.length : Object.keys(deps).length)) {
                    var list = Array.isArray(deps) ? deps : Object.keys(deps);
                    html += '<span class="ui-badge blue">🔗 ' + esc(list.join(', ')) + '</span>';
                }
                html += '</div><hr style="border:none;border-top:1px solid var(--border);margin:16px 0">';
                html += '<h4 style="margin:0 0 12px;font-size:14px">📜 Journal des modifications</h4>';
                var cl = m.changelog || [];
                if (!cl.length) {
                    html += '<p class="u-muted">Aucun changelog fourni par ce module.</p>';
                } else {
                    // Seule la dernière version (la 1re de la liste) est dépliée ;
                    // les autres sont repliées pour garder le modal lisible.
                    cl.forEach(function (v, i) {
                        var open = (i === 0) ? ' open' : '';
                        html += '<details class="mod-cl-version"' + open + '>';
                        html += '<summary><span class="mod-cl-ver">v' + esc(v.version) + '</span>';
                        if (v.date) html += '<span class="mod-cl-date">' + esc(v.date) + '</span>';
                        html += '</summary>';
                        var changes = v.changes || [];
                        if (changes.length) {
                            html += '<ul>';
                            changes.forEach(function (c) { html += '<li>' + esc(c) + '</li>'; });
                            html += '</ul>';
                        }
                        html += '</details>';
                    });
                }
                modalBody.innerHTML = html;
            })
            .catch(function (e) { modalBody.innerHTML = '<p class="u-muted">Erreur de chargement.</p>'; });
    };

    document.querySelectorAll('#mod-filters [data-filter]').forEach(function (b) {
        b.addEventListener('click', function () {
            document.querySelectorAll('#mod-filters .ui-btn').forEach(x => x.classList.remove('primary'));
            b.classList.add('primary');
            var f = b.dataset.filter;
            document.querySelectorAll('.mod-item').forEach(function (it) {
                it.style.display = (f === 'all' || it.dataset.state === f) ? '' : 'none';
            });
            // Masque les catégories sans module visible
            document.querySelectorAll('.mod-cat').forEach(function (sec) {
                var visible = sec.querySelectorAll('.mod-item').length
                    && Array.prototype.some.call(sec.querySelectorAll('.mod-item'), function (it) { return it.style.display !== 'none'; });
                sec.style.display = visible ? '' : 'none';
            });
        });
    });
    window.toggleModule = function (name, action) {
        var verb = action === 'activate' ? 'activer' : 'désactiver';
        if (!confirm('Voulez-vous ' + verb + ' le module « ' + name + ' » ?')) return;
        var fd = new FormData();
        fd.append('module', name); fd.append('action', action); fd.append('csrf_token', CSRF);
        fetch(TOGGLE, { method: 'POST', body: fd }).then(r => r.json()).then(function (d) {
            alert((d.success ? '✅ ' : '❌ ') + d.message);
            if (d.success) location.reload();
        }).catch(e => alert('❌ ' + e));
    };
})();
</script>

<?php admin_footer(); ?>
