<?php
/**
 * Centre de sécurité — protection anti-abus (UI maison)
 * Variables : $stats, $blocks[], $events[], $csrfToken
 */
if (!defined('ESPORT_CMS')) die('Access denied');

$pageTitle = $pageTitle ?? 'Centre de sécurité';
admin_header($pageTitle);

$stats  = $stats ?? ['active_blocks' => 0, 'events_24h' => 0, 'rate_24h' => 0, 'suspicious_24h' => 0];
$blocks = $blocks ?? [];
$events = $events ?? [];
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$flashOk  = $_SESSION['success'] ?? null; unset($_SESSION['success']);
$flashErr = $_SESSION['error'] ?? null;   unset($_SESSION['error']);

function sec_sev_badge(string $sev): string {
    return match (strtolower($sev)) {
        'high'   => '<span class="ui-badge red">🔴 Haute</span>',
        'medium' => '<span class="ui-badge amber">🟡 Moyenne</span>',
        'low'    => '<span class="ui-badge blue">🔵 Faible</span>',
        default  => '<span class="ui-badge">⚪ ' . htmlspecialchars($sev ?: 'Info') . '</span>',
    };
}
?>

<div class="adm-page-head">
    <div class="adm-breadcrumb"><a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><span>Sécurité</span></div>
    <h1>🛡️ Centre de sécurité</h1>
    <p>Surveillez et gérez la protection anti-abus de votre site.</p>
</div>

<?php if ($flashOk): ?><div class="ui-card" style="border-color:var(--green-soft);margin-bottom:16px"><div class="ui-card-body" style="color:var(--green)">✅ <?= $h($flashOk) ?></div></div><?php endif; ?>
<?php if ($flashErr): ?><div class="ui-card" style="border-color:var(--red-soft);margin-bottom:16px"><div class="ui-card-body" style="color:var(--red)">❌ <?= $h($flashErr) ?></div></div><?php endif; ?>

<div class="ui-grid cols-4" style="margin-bottom:18px">
    <div class="ui-card tone-red"><div class="ui-kpi"><div class="ui-kpi-icon">⛔</div><div><p class="ui-kpi-label">Blocages actifs</p><div class="ui-kpi-value"><?= (int)$stats['active_blocks'] ?></div></div></div></div>
    <div class="ui-card tone-blue"><div class="ui-kpi"><div class="ui-kpi-icon">📈</div><div><p class="ui-kpi-label">Événements 24h</p><div class="ui-kpi-value"><?= (int)$stats['events_24h'] ?></div></div></div></div>
    <div class="ui-card tone-amber"><div class="ui-kpi"><div class="ui-kpi-icon">🚦</div><div><p class="ui-kpi-label">Rate-limit 24h</p><div class="ui-kpi-value"><?= (int)$stats['rate_24h'] ?></div></div></div></div>
    <div class="ui-card tone-accent"><div class="ui-kpi"><div class="ui-kpi-icon">🕵️</div><div><p class="ui-kpi-label">Chemins suspects 24h</p><div class="ui-kpi-value"><?= (int)$stats['suspicious_24h'] ?></div></div></div></div>
</div>

<div class="ui-grid cols-3" style="grid-template-columns:1fr 2fr">
    <!-- Blocage manuel -->
    <div class="ui-card">
        <div class="ui-card-head">➕ Bloquer une IP</div>
        <div class="ui-card-body">
            <form method="post" action="<?= u('/admin/security/block') ?>">
                <input type="hidden" name="csrf_token" value="<?= $h($csrfToken ?? '') ?>">
                <div style="margin-bottom:12px"><label class="form-label">Adresse IP</label><input class="form-control" name="ip_address" placeholder="192.168.0.10" required></div>
                <div style="margin-bottom:12px"><label class="form-label">Raison</label><input class="form-control" name="reason" placeholder="Blocage manuel"></div>
                <div style="margin-bottom:14px"><label class="form-label">Durée (minutes)</label><input class="form-control" type="number" name="minutes" value="60" min="5" max="10080"></div>
                <button class="ui-btn danger w-100" type="submit">⛔ Bloquer cette IP</button>
            </form>
        </div>
    </div>

    <!-- Blocages actifs -->
    <div class="ui-card">
        <div class="ui-card-head">⛔ IP bloquées</div>
        <div class="ui-card-body" style="padding:0">
            <div style="overflow-x:auto">
                <table class="ui-table">
                    <thead><tr><th>IP</th><th>Raison</th><th>Expire</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($blocks as $b): ?>
                        <tr>
                            <td class="u-nowrap" style="font-family:monospace"><?= $h($b['ip_address'] ?? '') ?></td>
                            <td><?= $h($b['reason'] ?? '') ?></td>
                            <td class="u-nowrap"><?= !empty($b['permanent']) ? '<span class="ui-badge red">Permanent</span>' : $h($b['blocked_until'] ?? '') ?></td>
                            <td>
                                <form method="post" action="<?= u('/admin/security/unblock') ?>" onsubmit="return confirm('Débloquer cette IP ?')">
                                    <input type="hidden" name="csrf_token" value="<?= $h($csrfToken ?? '') ?>">
                                    <input type="hidden" name="ip_address" value="<?= $h($b['ip_address'] ?? '') ?>">
                                    <button class="ui-btn sm" type="submit">🔓 Débloquer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($blocks)): ?><tr><td colspan="4" class="u-muted" style="text-align:center;padding:24px">Aucune IP bloquée. 👍</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Événements récents -->
<div class="ui-card u-mt">
    <div class="ui-card-head">🧾 Événements de sécurité récents</div>
    <div class="ui-card-body" style="padding:0">
        <div style="overflow-x:auto">
            <table class="ui-table">
                <thead><tr><th>Type</th><th>Requête</th><th>IP</th><th>Gravité</th><th>Quand</th></tr></thead>
                <tbody>
                <?php foreach ($events as $e): ?>
                    <tr>
                        <td><?= $h($e['event_type'] ?? '') ?></td>
                        <td class="mono" style="font-family:monospace;font-size:12px"><?= $h(($e['request_method'] ?? '') . ' ' . ($e['request_uri'] ?? '')) ?></td>
                        <td class="u-nowrap" style="font-family:monospace"><?= $h($e['ip_address'] ?? '') ?></td>
                        <td><?= sec_sev_badge((string)($e['severity'] ?? '')) ?></td>
                        <td class="u-nowrap u-muted"><?= $h($e['created_at'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($events)): ?><tr><td colspan="5" class="u-muted" style="text-align:center;padding:24px">Aucun événement récent.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php admin_footer(); ?>
