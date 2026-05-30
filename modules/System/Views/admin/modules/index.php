<?php
/**
 * Gestion des modules — eSport-CMS V4 (UI maison)
 * Variables : $modules[], $stats[], $csrfToken
 */
if (!defined('ESPORT_CMS')) die('Access denied');

$pageTitle = $pageTitle ?? 'Modules';
admin_header($pageTitle);

$modules = $modules ?? [];
$stats   = $stats ?? ['total' => 0, 'active' => 0, 'inactive' => 0, 'loaded' => 0];
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>

<div class="adm-page-head">
    <div class="adm-breadcrumb"><a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><span>Modules</span></div>
    <h1>🧩 Modules</h1>
    <p>Activez ou désactivez les fonctionnalités de votre CMS.</p>
</div>

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

<div class="ui-grid cols-3" id="mod-list">
    <?php foreach ($modules as $m):
        $active = !empty($m['active']);
        $protected = !empty($m['is_protected']); ?>
    <div class="ui-card mod-item" data-state="<?= $active ? 'active' : 'inactive' ?>">
        <div class="ui-card-body">
            <div class="u-flex u-gap" style="align-items:center;margin-bottom:10px">
                <div style="width:46px;height:46px;border-radius:12px;background:var(--accent-soft);display:grid;place-items:center;font-size:22px;flex-shrink:0">🧩</div>
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
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($modules)): ?>
        <div class="ui-card"><div class="ui-empty"><div class="ui-empty-icon">📭</div>Aucun module trouvé.</div></div>
    <?php endif; ?>
</div>

<script>
(function () {
    var TOGGLE = '<?= u('/admin/modules/toggle') ?>', CSRF = '<?= $h($csrfToken ?? '') ?>';
    document.querySelectorAll('#mod-filters [data-filter]').forEach(function (b) {
        b.addEventListener('click', function () {
            document.querySelectorAll('#mod-filters .ui-btn').forEach(x => x.classList.remove('primary'));
            b.classList.add('primary');
            var f = b.dataset.filter;
            document.querySelectorAll('.mod-item').forEach(function (it) {
                it.style.display = (f === 'all' || it.dataset.state === f) ? '' : 'none';
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
