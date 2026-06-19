<?php
/**
 * Diagnostic / Santé — Aegis Framework (UI maison).
 * Variables : $checks[], $summary[], $csrfToken
 */
if (!defined('AEGIS_FRAMEWORK')) die('Access denied');

$pageTitle = $pageTitle ?? 'Diagnostic';
admin_header($pageTitle);

$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$checks  = $checks ?? [];
$summary = $summary ?? ['ok' => 0, 'warn' => 0, 'error' => 0];

$flashOk  = $_SESSION['success'] ?? null; unset($_SESSION['success']);
$flashErr = $_SESSION['error'] ?? null;   unset($_SESSION['error']);

$dot = ['ok' => '🟢', 'warn' => '🟡', 'error' => '🔴'];
$badge = ['ok' => 'green', 'warn' => 'amber', 'error' => 'red'];

// Regroupement par "group"
$groups = [];
foreach ($checks as $c) { $groups[$c['group']][] = $c; }

$fixLabels = [
    'reinstall'      => '🔧 Réinstaller',
    'update'         => '⬆️ Mettre à jour',
    'disable'        => '⏸️ Désactiver',
    'remove_install' => '🗑️ Supprimer /install',
];
?>

<div class="adm-page-head">
    <div class="adm-breadcrumb"><a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><span>Diagnostic</span></div>
    <h1>🩺 Diagnostic &amp; santé</h1>
    <p>Vérifie la cohérence de l'installation et propose des réparations.</p>
</div>

<?php if ($flashOk): ?><div class="ui-alert success" style="margin-bottom:16px"><?= $h($flashOk) ?></div><?php endif; ?>
<?php if ($flashErr): ?><div class="ui-alert danger" style="margin-bottom:16px"><?= $h($flashErr) ?></div><?php endif; ?>

<div class="ui-grid cols-3" style="margin-bottom:18px">
    <div class="ui-card tone-green"><div class="ui-kpi"><div class="ui-kpi-icon">🟢</div><div><p class="ui-kpi-label">OK</p><div class="ui-kpi-value"><?= (int)$summary['ok'] ?></div></div></div></div>
    <div class="ui-card tone-amber"><div class="ui-kpi"><div class="ui-kpi-icon">🟡</div><div><p class="ui-kpi-label">Avertissements</p><div class="ui-kpi-value"><?= (int)$summary['warn'] ?></div></div></div></div>
    <div class="ui-card tone-red"><div class="ui-kpi"><div class="ui-kpi-icon">🔴</div><div><p class="ui-kpi-label">Erreurs</p><div class="ui-kpi-value"><?= (int)$summary['error'] ?></div></div></div></div>
</div>

<?php if ($summary['error'] === 0 && $summary['warn'] === 0): ?>
<div class="ui-alert success" style="margin-bottom:18px">🎉 Tout est en ordre — aucune anomalie détectée.</div>
<?php endif; ?>

<?php foreach ($groups as $groupName => $items): ?>
<div class="ui-card u-mt">
    <div class="ui-card-head"><?= $h($groupName) ?></div>
    <div class="ui-card-body" style="padding:0">
        <table class="ui-table">
            <tbody>
            <?php foreach ($items as $c): ?>
                <tr>
                    <td style="width:34px;font-size:16px"><?= $dot[$c['status']] ?? '⚪' ?></td>
                    <td style="width:32%"><strong><?= $h($c['label']) ?></strong></td>
                    <td class="u-muted" style="font-size:13px"><?= $h($c['detail']) ?></td>
                    <td style="width:200px;text-align:right">
                        <?php if (!empty($c['fix'])): ?>
                        <form method="post" action="<?= u('/admin/diagnostic/repair') ?>" onsubmit="return confirm('Lancer cette réparation ?')" style="margin:0">
                            <input type="hidden" name="csrf_token" value="<?= $h($csrfToken ?? '') ?>">
                            <input type="hidden" name="fix" value="<?= $h($c['fix']) ?>">
                            <input type="hidden" name="target" value="<?= $h($c['target'] ?? '') ?>">
                            <button class="ui-btn sm <?= $c['status'] === 'error' ? 'danger' : '' ?>" type="submit"><?= $fixLabels[$c['fix']] ?? 'Réparer' ?></button>
                        </form>
                        <?php else: ?>
                            <span class="ui-badge <?= $badge[$c['status']] ?? '' ?>"><?= ucfirst($c['status']) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>

<style>
.ui-alert{padding:12px 16px;border-radius:var(--radius-sm);border:1px solid var(--border)}
.ui-alert.success{background:var(--green-soft);color:var(--green);border-color:var(--green-soft)}
.ui-alert.danger{background:var(--red-soft);color:var(--red);border-color:var(--red-soft)}
</style>

<?php admin_footer(); ?>
