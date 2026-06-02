<?php
/**
 * Centre de sécurité — Aegis Framework (UI maison, sans dépendance externe).
 *
 * Variables fournies par SecurityController::index() :
 *   $stats, $byCategory[], $bySeverity[], $topIps[], $blocks[], $events[]
 *   $settings[], $rulesByCategory[], $whitelist[], $categoriesMeta[], $severities[]
 *   $csrfToken
 */
if (!defined('AEGIS_FRAMEWORK')) die('Access denied');

$pageTitle = $pageTitle ?? 'Centre de sécurité';
admin_header($pageTitle);

$h  = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$cz = $csrfToken ?? '';

$flashOk  = $_SESSION['success'] ?? null; unset($_SESSION['success']);
$flashErr = $_SESSION['error'] ?? null;   unset($_SESSION['error']);

$sevLabel = [
    'info'     => '⚪ Information',
    'faible'   => '🔵 Faible',
    'moyen'    => '🟡 Moyen',
    'eleve'    => '🟠 Élevé',
    'critique' => '🔴 Critique',
];
$sevBadge = function (string $s) use ($sevLabel): string {
    $cls = ['info' => '', 'faible' => 'blue', 'moyen' => 'amber', 'eleve' => 'amber', 'critique' => 'red'][$s] ?? '';
    return '<span class="ui-badge ' . $cls . '">' . ($sevLabel[$s] ?? htmlspecialchars($s)) . '</span>';
};
$levelBadge = function (string $lvl): string {
    return match ($lvl) {
        'critique' => '<span class="ui-badge red">🔴 Critique</span>',
        'eleve'    => '<span class="ui-badge amber">🟠 Élevé</span>',
        'moyen'    => '<span class="ui-badge amber">🟡 Moyen</span>',
        'faible'   => '<span class="ui-badge blue">🔵 Faible</span>',
        default    => '<span class="ui-badge">⚪ Aucun</span>',
    };
};

// Index rapide catégories par clé d'événement (pour l'historique).
$catByCount = [];
foreach (($byCategory ?? []) as $row) { $catByCount[$row['cat']] = (int)$row['c']; }
$sevByCount = [];
foreach (($bySeverity ?? []) as $row) { $sevByCount[$row['severity']] = (int)$row['c']; }

$globalOn = (string)($settings['enabled'] ?? '1') === '1';
?>

<div class="adm-page-head">
    <div class="adm-breadcrumb"><a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><span>Sécurité</span></div>
    <h1>🛡️ Centre de sécurité</h1>
    <p>Détectez, enregistrez, analysez et administrez les événements de sécurité.</p>
</div>

<?php if ($flashOk): ?><div class="ui-alert success">✅ <?= $h($flashOk) ?></div><?php endif; ?>
<?php if ($flashErr): ?><div class="ui-alert danger">❌ <?= $h($flashErr) ?></div><?php endif; ?>

<?php if (!$globalOn): ?>
<div class="ui-alert warning">⚠️ Le Centre de sécurité est actuellement <strong>désactivé</strong> — aucune détection ni blocage automatique n'est appliqué.</div>
<?php endif; ?>

<!-- ════ Onglets ════ -->
<div class="sec-tabs" id="sec-tabs">
    <button class="sec-tab active" data-tab="dashboard">📊 Tableau de bord</button>
    <button class="sec-tab" data-tab="detectors">🧩 Détecteurs</button>
    <button class="sec-tab" data-tab="config">⚙️ Seuils &amp; config</button>
    <button class="sec-tab" data-tab="lists">📋 Listes IP</button>
    <button class="sec-tab" data-tab="history">🧾 Historique</button>
</div>

<!-- ════════════════ DASHBOARD ════════════════ -->
<section class="sec-panel active" data-panel="dashboard">
    <div class="ui-grid cols-4" style="margin-bottom:18px">
        <div class="ui-card tone-red"><div class="ui-kpi"><div class="ui-kpi-icon">⛔</div><div><p class="ui-kpi-label">Blocages actifs</p><div class="ui-kpi-value"><?= (int)($stats['active_blocks'] ?? 0) ?></div></div></div></div>
        <div class="ui-card tone-blue"><div class="ui-kpi"><div class="ui-kpi-icon">📈</div><div><p class="ui-kpi-label">Événements 24 h</p><div class="ui-kpi-value"><?= (int)($stats['events_24h'] ?? 0) ?></div></div></div></div>
        <div class="ui-card tone-amber"><div class="ui-kpi"><div class="ui-kpi-icon">🎯</div><div><p class="ui-kpi-label">IP surveillées</p><div class="ui-kpi-value"><?= (int)($stats['tracked_ips'] ?? 0) ?></div></div></div></div>
        <div class="ui-card tone-accent"><div class="ui-kpi"><div class="ui-kpi-icon">✅</div><div><p class="ui-kpi-label">Liste blanche</p><div class="ui-kpi-value"><?= (int)($stats['whitelist'] ?? 0) ?></div></div></div></div>
    </div>

    <div class="ui-grid cols-2" style="grid-template-columns:1fr 1fr">
        <div class="ui-card">
            <div class="ui-card-head">🗂️ Événements par catégorie (7 j)</div>
            <div class="ui-card-body" style="padding:0">
                <table class="ui-table">
                    <thead><tr><th>Catégorie</th><th style="text-align:right">Événements</th></tr></thead>
                    <tbody>
                    <?php foreach (($categoriesMeta ?? []) as $ck => $meta): $c = $catByCount[$ck] ?? 0; ?>
                        <tr><td><?= $meta['icon'] ?> <?= $h($meta['label']) ?></td><td style="text-align:right"><strong><?= $c ?></strong></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="ui-card">
            <div class="ui-card-head">🌡️ IP au score le plus élevé</div>
            <div class="ui-card-body" style="padding:0">
                <table class="ui-table">
                    <thead><tr><th>IP</th><th>Score</th><th>Niveau</th><th>Vue</th></tr></thead>
                    <tbody>
                    <?php foreach (($topIps ?? []) as $t):
                        $lvl = \Framework\Services\SecurityCenterService::levelFromScore((int)$t['score']); ?>
                        <tr>
                            <td style="font-family:monospace"><?= $h($t['ip_address']) ?></td>
                            <td><strong><?= (int)$t['score'] ?></strong></td>
                            <td><?= $levelBadge($lvl) ?></td>
                            <td class="u-muted u-nowrap"><?= $h($t['last_seen'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($topIps)): ?><tr><td colspan="4" class="u-muted" style="text-align:center;padding:24px">Aucune IP surveillée pour l'instant. 👍</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════ DÉTECTEURS ════════════════ -->
<section class="sec-panel" data-panel="detectors">
    <form method="post" action="<?= u('/admin/security/rules') ?>">
        <input type="hidden" name="csrf_token" value="<?= $h($cz) ?>">
        <div class="ui-card" style="margin-bottom:14px"><div class="ui-card-body u-muted">
            Activez/désactivez chaque détecteur, ajustez son <strong>score de menace</strong> (points ajoutés à l'IP) et sa <strong>gravité</strong>. La case d'en-tête de catégorie active/désactive toute la catégorie.
        </div></div>

        <?php foreach (($rulesByCategory ?? []) as $ck => $cat): ?>
            <div class="ui-card" style="margin-bottom:14px">
                <div class="ui-card-head" style="display:flex;align-items:center;justify-content:space-between">
                    <span><?= $cat['meta']['icon'] ?> <?= $h($cat['meta']['label']) ?></span>
                    <label class="ui-switch" title="Activer/désactiver la catégorie">
                        <input type="checkbox" name="cat[<?= $h($ck) ?>]" <?= $cat['enabled'] ? 'checked' : '' ?>>
                        <span>Catégorie active</span>
                    </label>
                </div>
                <div class="ui-card-body" style="padding:0">
                    <table class="ui-table">
                        <thead><tr><th style="width:60px">Actif</th><th>Détecteur</th><th>Description</th><th style="width:110px">Score</th><th style="width:150px">Gravité</th></tr></thead>
                        <tbody>
                        <?php foreach ($cat['rules'] as $key => $rule): ?>
                            <tr>
                                <td><label class="ui-switch sec-rule-toggle"><input type="checkbox" name="rule_enabled[<?= $h($key) ?>]" <?= (int)$rule['enabled'] ? 'checked' : '' ?>></label></td>
                                <td><strong><?= $h($rule['label']) ?></strong><br><code class="u-muted" style="font-size:11px"><?= $h($key) ?></code></td>
                                <td class="u-muted" style="font-size:12px"><?= $h($rule['description'] ?? '') ?></td>
                                <td><input class="form-control" type="number" name="rule_score[<?= $h($key) ?>]" value="<?= (int)$rule['score'] ?>" min="0" max="100" style="width:90px"></td>
                                <td>
                                    <select class="form-control" name="rule_severity[<?= $h($key) ?>]">
                                        <?php foreach (($severities ?? []) as $sv): ?>
                                            <option value="<?= $h($sv) ?>" <?= $rule['severity'] === $sv ? 'selected' : '' ?>><?= $h($sevLabel[$sv] ?? $sv) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>

        <div style="text-align:right;margin-bottom:24px"><button class="ui-btn primary" type="submit">💾 Enregistrer les détecteurs</button></div>
    </form>
</section>

<!-- ════════════════ SEUILS & CONFIG ════════════════ -->
<section class="sec-panel" data-panel="config">
    <form method="post" action="<?= u('/admin/security/settings') ?>">
        <input type="hidden" name="csrf_token" value="<?= $h($cz) ?>">
        <div class="ui-grid cols-2" style="grid-template-columns:1fr 1fr">
            <div class="ui-card">
                <div class="ui-card-head">🔌 Activation</div>
                <div class="ui-card-body">
                    <label class="ui-switch" style="margin-bottom:14px"><input type="checkbox" name="enabled" <?= $globalOn ? 'checked' : '' ?>><span>Centre de sécurité activé</span></label>
                    <label class="ui-switch"><input type="checkbox" name="auto_block" <?= (string)($settings['auto_block'] ?? '1') === '1' ? 'checked' : '' ?>><span>Blocage automatique des IP malveillantes</span></label>
                </div>
            </div>
            <div class="ui-card">
                <div class="ui-card-head">🌡️ Seuils de score</div>
                <div class="ui-card-body">
                    <div style="margin-bottom:12px"><label class="form-label">Blocage temporaire à partir de (points)</label><input class="form-control" type="number" name="block_threshold" value="<?= (int)($settings['block_threshold'] ?? 100) ?>" min="1"></div>
                    <div style="margin-bottom:12px"><label class="form-label">Durée du blocage temporaire (heures)</label><input class="form-control" type="number" name="block_duration_hours" value="<?= (int)($settings['block_duration_hours'] ?? 24) ?>" min="1"></div>
                    <div style="margin-bottom:12px"><label class="form-label">Blocage permanent à partir de (points)</label><input class="form-control" type="number" name="ban_threshold" value="<?= (int)($settings['ban_threshold'] ?? 300) ?>" min="1"></div>
                    <div><label class="form-label">Rétention de l'historique (jours)</label><input class="form-control" type="number" name="log_retention_days" value="<?= (int)($settings['log_retention_days'] ?? 30) ?>" min="1"></div>
                </div>
            </div>
        </div>
        <div class="ui-card" style="margin-top:14px">
            <div class="ui-card-head">📐 Échelle des niveaux de menace</div>
            <div class="ui-card-body u-muted">
                <span class="ui-badge blue">🔵 Faible</span> 0–25 &nbsp;·&nbsp;
                <span class="ui-badge amber">🟡 Moyen</span> 26–50 &nbsp;·&nbsp;
                <span class="ui-badge amber">🟠 Élevé</span> 51–75 &nbsp;·&nbsp;
                <span class="ui-badge red">🔴 Critique</span> 76–100+
            </div>
        </div>
        <div style="text-align:right;margin:14px 0 24px"><button class="ui-btn primary" type="submit">💾 Enregistrer la configuration</button></div>
    </form>
</section>

<!-- ════════════════ LISTES IP ════════════════ -->
<section class="sec-panel" data-panel="lists">
    <div class="ui-grid cols-2" style="grid-template-columns:1fr 1fr">
        <!-- Liste blanche -->
        <div class="ui-card">
            <div class="ui-card-head">✅ Liste blanche (jamais bloquée)</div>
            <div class="ui-card-body">
                <form method="post" action="<?= u('/admin/security/whitelist/add') ?>" style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap">
                    <input type="hidden" name="csrf_token" value="<?= $h($cz) ?>">
                    <input class="form-control" name="ip_address" placeholder="IP (ex. 203.0.113.10)" required style="flex:1;min-width:140px">
                    <input class="form-control" name="note" placeholder="Note (optionnel)" style="flex:1;min-width:120px">
                    <button class="ui-btn primary" type="submit">➕ Ajouter</button>
                </form>
                <table class="ui-table">
                    <thead><tr><th>IP</th><th>Note</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach (($whitelist ?? []) as $w): ?>
                        <tr>
                            <td style="font-family:monospace"><?= $h($w['ip_address']) ?></td>
                            <td class="u-muted"><?= $h($w['note'] ?? '') ?></td>
                            <td>
                                <form method="post" action="<?= u('/admin/security/whitelist/remove') ?>" onsubmit="return confirm('Retirer cette IP ?')">
                                    <input type="hidden" name="csrf_token" value="<?= $h($cz) ?>">
                                    <input type="hidden" name="ip_address" value="<?= $h($w['ip_address']) ?>">
                                    <button class="ui-btn sm" type="submit">🗑️</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($whitelist)): ?><tr><td colspan="3" class="u-muted" style="text-align:center;padding:18px">Aucune IP en liste blanche.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Liste noire / blocages -->
        <div class="ui-card">
            <div class="ui-card-head">⛔ Liste noire / blocages actifs</div>
            <div class="ui-card-body">
                <form method="post" action="<?= u('/admin/security/blacklist/add') ?>" style="display:flex;gap:8px;margin-bottom:6px;flex-wrap:wrap">
                    <input type="hidden" name="csrf_token" value="<?= $h($cz) ?>">
                    <input class="form-control" name="ip_address" placeholder="IP à bloquer définitivement" required style="flex:1;min-width:160px">
                    <input class="form-control" name="reason" placeholder="Raison" style="flex:1;min-width:120px">
                    <button class="ui-btn danger" type="submit">⛔ Bloquer (permanent)</button>
                </form>
                <form method="post" action="<?= u('/admin/security/block') ?>" style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap">
                    <input type="hidden" name="csrf_token" value="<?= $h($cz) ?>">
                    <input class="form-control" name="ip_address" placeholder="IP" required style="flex:1;min-width:120px">
                    <input class="form-control" type="number" name="minutes" value="60" min="5" title="Minutes" style="width:90px">
                    <button class="ui-btn" type="submit">⏱️ Bloquer (temp.)</button>
                </form>
                <table class="ui-table">
                    <thead><tr><th>IP</th><th>Raison</th><th>Expire</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach (($blocks ?? []) as $b): ?>
                        <tr>
                            <td style="font-family:monospace"><?= $h($b['ip_address']) ?></td>
                            <td style="font-size:12px"><?= $h($b['reason'] ?? '') ?></td>
                            <td class="u-nowrap"><?= !empty($b['permanent']) ? '<span class="ui-badge red">Permanent</span>' : $h($b['blocked_until'] ?? '') ?></td>
                            <td>
                                <form method="post" action="<?= u('/admin/security/unblock') ?>" onsubmit="return confirm('Débloquer cette IP ?')">
                                    <input type="hidden" name="csrf_token" value="<?= $h($cz) ?>">
                                    <input type="hidden" name="ip_address" value="<?= $h($b['ip_address']) ?>">
                                    <button class="ui-btn sm" type="submit">🔓</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($blocks)): ?><tr><td colspan="4" class="u-muted" style="text-align:center;padding:18px">Aucune IP bloquée. 👍</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════ HISTORIQUE ════════════════ -->
<section class="sec-panel" data-panel="history">
    <div class="ui-card">
        <div class="ui-card-head" style="display:flex;align-items:center;justify-content:space-between">
            <span>🧾 Historique des événements (200 derniers)</span>
            <form method="post" action="<?= u('/admin/security/purge') ?>" onsubmit="return confirm('Purger l\'historique ?')" style="display:flex;gap:8px;align-items:center">
                <input type="hidden" name="csrf_token" value="<?= $h($cz) ?>">
                <input class="form-control" type="number" name="older_than_days" placeholder="jours (vide = tout)" style="width:150px" min="1">
                <button class="ui-btn sm danger" type="submit">🧹 Purger</button>
            </form>
        </div>
        <div class="ui-card-body" style="padding:0">
            <div style="overflow-x:auto">
                <table class="ui-table">
                    <thead><tr><th>Quand</th><th>Catégorie</th><th>Détecteur</th><th>Gravité</th><th>Score</th><th>IP</th><th>Requête</th></tr></thead>
                    <tbody>
                    <?php foreach (($events ?? []) as $e):
                        $cat = $e['category'] ?? null;
                        $cm = $categoriesMeta[$cat] ?? null; ?>
                        <tr>
                            <td class="u-nowrap u-muted" style="font-size:12px"><?= $h($e['created_at'] ?? '') ?></td>
                            <td class="u-nowrap"><?= $cm ? $cm['icon'] . ' ' . $h($cm['label']) : '<span class="u-muted">—</span>' ?></td>
                            <td><?= $h($e['event_type'] ?? '') ?></td>
                            <td><?= $sevBadge((string)($e['severity'] ?? 'info')) ?></td>
                            <td><?= (int)($e['score'] ?? 0) ?></td>
                            <td class="u-nowrap" style="font-family:monospace"><?= $h($e['ip_address'] ?? '') ?></td>
                            <td class="u-muted" style="font-family:monospace;font-size:11px"><?= $h(mb_substr(($e['request_method'] ?? '') . ' ' . ($e['request_uri'] ?? ''), 0, 80)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($events)): ?><tr><td colspan="7" class="u-muted" style="text-align:center;padding:24px">Aucun événement enregistré.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<style>
.sec-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:18px;border-bottom:1px solid var(--border);padding-bottom:2px}
.sec-tab{background:none;border:none;border-bottom:2px solid transparent;padding:10px 14px;border-radius:8px 8px 0 0;cursor:pointer;color:var(--text-soft);font-weight:600;font-size:14px;font-family:inherit}
.sec-tab:hover{background:var(--surface-3);color:var(--text)}
.sec-tab.active{color:var(--accent);border-bottom-color:var(--accent)}
.sec-panel{display:none}
.sec-panel.active{display:block}
.ui-switch{display:inline-flex;align-items:center;gap:10px;cursor:pointer;user-select:none}
.ui-switch input[type=checkbox]{
    appearance:none;-webkit-appearance:none;margin:0;flex:0 0 auto;
    width:42px;height:24px;border-radius:24px;background:var(--border-strong);
    position:relative;cursor:pointer;transition:background .2s ease;
}
.ui-switch input[type=checkbox]::after{
    content:"";position:absolute;top:2px;left:2px;width:20px;height:20px;border-radius:50%;
    background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.35);transition:transform .2s ease;
}
.ui-switch input[type=checkbox]:checked{background:var(--accent)}
.ui-switch input[type=checkbox]:checked::after{transform:translateX(18px)}
.ui-switch input[type=checkbox]:focus-visible{outline:2px solid var(--accent);outline-offset:2px}
.sec-rule-toggle{justify-content:center}
.ui-alert{padding:12px 16px;border-radius:var(--radius-sm);margin-bottom:16px;border:1px solid var(--border)}
.ui-alert.success{background:var(--green-soft);color:var(--green);border-color:var(--green-soft)}
.ui-alert.danger{background:var(--red-soft);color:var(--red);border-color:var(--red-soft)}
.ui-alert.warning{background:var(--amber-soft);color:var(--amber);border-color:var(--amber-soft)}
</style>
<script>
(function () {
    var tabs = document.querySelectorAll('#sec-tabs .sec-tab');
    var panels = document.querySelectorAll('.sec-panel');
    function show(name) {
        tabs.forEach(function (t) { t.classList.toggle('active', t.dataset.tab === name); });
        panels.forEach(function (p) { p.classList.toggle('active', p.dataset.panel === name); });
        try { localStorage.setItem('sec.tab', name); } catch (e) {}
    }
    tabs.forEach(function (t) { t.addEventListener('click', function () { show(t.dataset.tab); }); });
    var saved; try { saved = localStorage.getItem('sec.tab'); } catch (e) {}
    if (saved && document.querySelector('.sec-panel[data-panel="' + saved + '"]')) show(saved);
})();
</script>

<?php admin_footer(); ?>
