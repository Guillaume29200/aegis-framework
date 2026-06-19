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
$categories = $categories ?? [];
$catIcons = ['Système' => '🛠️', 'Communautaire' => '💬', 'e-commerce' => '🛒', 'Autres' => '🧩'];
?>
<div id="mod-list">
    <?php foreach ($categories as $catName => $catModules):
        $catIcon = $catIcons[$catName] ?? '📦'; ?>
    <section class="mod-cat" data-cat="<?= $h($catName) ?>">
        <div class="mod-cat-head">
            <span class="mod-cat-title"><?= $catIcon ?> <?= $h($catName) ?></span>
            <span class="ui-badge"><?= count($catModules) ?></span>
        </div>
        <div class="ui-grid cols-3">
            <?php foreach ($catModules as $m):
                $active = !empty($m['active']);
                $protected = !empty($m['is_protected']); ?>
            <div class="ui-card mod-item" data-state="<?= $active ? 'active' : 'inactive' ?>">
                <div class="ui-card-body">
                    <div class="u-flex u-gap" style="align-items:center;margin-bottom:10px">
                        <div style="width:46px;height:46px;border-radius:12px;background:var(--accent-soft);display:grid;place-items:center;font-size:22px;flex-shrink:0"><?= $catIcon ?></div>
                        <div style="min-width:0">
                            <div class="fw-bold" style="font-size:15px"><?= $h($m['display_name']) ?></div>
                            <div class="u-muted" style="font-size:12px">v<?= $h($m['version']) ?> · <?= $h($m['author']) ?></div>
                        </div>
                        <span class="ui-badge <?= $active ? 'green' : 'amber' ?>" style="margin-left:auto"><?= $active ? '🟢 Actif' : '🟡 Inactif' ?></span>
                    </div>
                    <p class="u-muted" style="font-size:13px;min-height:38px;margin:0 0 14px"><?= $h($m['description']) ?></p>
                    <div class="u-flex u-gap" style="align-items:center">
                        <?php if ($protected): ?>
                            <span class="ui-badge">🔒 Module cœur</span>
                        <?php elseif ($active): ?>
                            <button class="ui-btn sm danger" onclick="toggleModule('<?= $h($m['name']) ?>','deactivate')">⏸️ Désactiver</button>
                        <?php else: ?>
                            <button class="ui-btn sm primary" onclick="toggleModule('<?= $h($m['name']) ?>','activate')">✅ Activer</button>
                        <?php endif; ?>
                        <button class="ui-btn sm" style="margin-left:auto" onclick="moduleInfo('<?= $h($m['name']) ?>')">ℹ️ Détails</button>
                        <?php if (!$protected): ?>
                        <button class="ui-btn sm danger" title="Supprimer définitivement" onclick="confirmDeleteModule('<?= $h($m['name']) ?>','<?= $h($m['display_name']) ?>')">🗑️</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
    <?php if (empty($categories)): ?>
        <div class="ui-card"><div class="ui-empty"><div class="ui-empty-icon">📭</div>Aucun module trouvé.</div></div>
    <?php endif; ?>
</div>

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

<style>
.mod-modal{position:fixed;inset:0;z-index:200;display:flex;align-items:center;justify-content:center;padding:20px}
.mod-modal[hidden]{display:none}
.mod-modal-backdrop{position:absolute;inset:0;background:rgba(8,12,20,.55);backdrop-filter:blur(2px)}
.mod-modal-box{position:relative;width:min(880px,96vw);max-height:88vh;overflow:auto;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:var(--shadow-lg)}
.mod-modal-head{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--surface);z-index:1}
.mod-modal-head h3{margin:0;font-size:17px}
.mod-modal-body{padding:20px}
.mod-cl-version{border-left:3px solid var(--accent);padding:4px 0 4px 14px;margin:0 0 16px}
.mod-cl-version h4{margin:0 0 2px;font-size:14px}
.mod-cl-version .mod-cl-date{font-size:12px;color:var(--text-faint);margin-bottom:8px}
.mod-cl-version ul{margin:0;padding-left:18px}
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
                    cl.forEach(function (v) {
                        html += '<div class="mod-cl-version"><h4>v' + esc(v.version) + '</h4>';
                        if (v.date) html += '<div class="mod-cl-date">' + esc(v.date) + '</div>';
                        var changes = v.changes || [];
                        if (changes.length) {
                            html += '<ul>';
                            changes.forEach(function (c) { html += '<li>' + esc(c) + '</li>'; });
                            html += '</ul>';
                        }
                        html += '</div>';
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
