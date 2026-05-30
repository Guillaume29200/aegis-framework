<?php
/**
 * Dashboard Admin — eSport-CMS V4 (nouvelle UI)
 * Données fournies par AdminController::dashboard() : $stats, $currentUser
 */
$pageTitle = 'Tableau de bord';
admin_header($pageTitle, ['currentUser' => $currentUser ?? null]);

$stats = $stats ?? [];

$totalUsers  = (int)($stats['total_users']  ?? 0);
$activeUsers = (int)($stats['active_users'] ?? 0);
$newUsers    = (int)($stats['new_users']    ?? 0);

// Répartition par rôle / statut
$roleMap = [];
foreach (($stats['users_by_role'] ?? []) as $r)   { $roleMap[$r['role']]     = (int)$r['count']; }
$statusMap = [];
foreach (($stats['users_by_status'] ?? []) as $s) { $statusMap[$s['status']] = (int)$s['count']; }

// Mini-graphe des inscriptions (30 j) sans dépendance
$chart = $stats['registrations_chart'] ?? [];
$chartMax = 1;
foreach ($chart as $c) { $chartMax = max($chartMax, (int)$c['count']); }

$h = fn($v) => htmlspecialchars((string)$v);
?>

<div class="adm-page-head u-between" style="flex-wrap:wrap;gap:12px">
    <div>
        <div class="adm-breadcrumb"><a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><span>Tableau de bord</span></div>
        <h1>👋 Bonjour <?= $h($currentUser['username'] ?? $currentUser['first_name'] ?? 'Admin') ?></h1>
        <p>Voici un aperçu de votre plateforme aujourd'hui.</p>
    </div>
    <div class="u-flex u-gap">
        <a class="ui-btn" href="<?= u('/admin/users') ?>">👥 Utilisateurs</a>
        <a class="ui-btn primary" href="<?= u('/admin/users/create') ?>">➕ Nouvel utilisateur</a>
    </div>
</div>

<!-- KPI -->
<div class="ui-grid cols-4">
    <div class="ui-card tone-accent">
        <div class="ui-kpi">
            <div class="ui-kpi-icon">👥</div>
            <div>
                <p class="ui-kpi-label">Utilisateurs totaux</p>
                <div class="ui-kpi-value"><?= number_format($totalUsers, 0, ',', ' ') ?></div>
                <p class="ui-kpi-sub">Comptes enregistrés</p>
            </div>
        </div>
    </div>
    <div class="ui-card tone-green">
        <div class="ui-kpi">
            <div class="ui-kpi-icon">✅</div>
            <div>
                <p class="ui-kpi-label">Actifs (7 jours)</p>
                <div class="ui-kpi-value"><?= number_format($activeUsers, 0, ',', ' ') ?></div>
                <p class="ui-kpi-sub"><?= $totalUsers ? round($activeUsers / max(1, $totalUsers) * 100) : 0 ?>% de la base</p>
            </div>
        </div>
    </div>
    <div class="ui-card tone-blue">
        <div class="ui-kpi">
            <div class="ui-kpi-icon">🆕</div>
            <div>
                <p class="ui-kpi-label">Nouveaux (30 jours)</p>
                <div class="ui-kpi-value"><?= number_format($newUsers, 0, ',', ' ') ?></div>
                <p class="ui-kpi-sub">Inscriptions récentes</p>
            </div>
        </div>
    </div>
    <div class="ui-card tone-amber">
        <div class="ui-kpi">
            <div class="ui-kpi-icon">🛡️</div>
            <div>
                <p class="ui-kpi-label">Administrateurs</p>
                <div class="ui-kpi-value"><?= (int)(($roleMap['admin'] ?? 0) + ($roleMap['superadmin'] ?? 0)) ?></div>
                <p class="ui-kpi-sub">Comptes privilégiés</p>
            </div>
        </div>
    </div>
</div>

<div class="ui-grid cols-3 u-mt" style="grid-template-columns:2fr 1fr">
    <!-- Graphe inscriptions -->
    <div class="ui-card">
        <div class="ui-card-head">📈 Inscriptions sur 30 jours
            <span class="ui-card-actions ui-badge accent"><?= count($chart) ?> jours actifs</span>
        </div>
        <div class="ui-card-body">
            <?php if (empty($chart)): ?>
                <div class="ui-empty"><div class="ui-empty-icon">🗓️</div>Aucune inscription sur la période.</div>
            <?php else: ?>
                <div style="display:flex;align-items:flex-end;gap:4px;height:180px;padding-top:10px">
                    <?php foreach ($chart as $c):
                        $val = (int)$c['count'];
                        $pct = max(4, round($val / $chartMax * 100)); ?>
                        <div title="<?= $h($c['day']) ?> — <?= $val ?>" style="flex:1;display:flex;flex-direction:column;justify-content:flex-end;height:100%">
                            <div style="height:<?= $pct ?>%;background:linear-gradient(180deg,var(--accent),var(--accent-soft));border-radius:5px 5px 0 0;min-height:4px"></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="u-flex u-between" style="margin-top:8px;font-size:11px;color:var(--text-faint)">
                    <span><?= $h($chart[0]['day'] ?? '') ?></span>
                    <span><?= $h(end($chart)['day'] ?? '') ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Répartition par rôle -->
    <div class="ui-card">
        <div class="ui-card-head">🪪 Par rôle</div>
        <div class="ui-card-body">
            <?php
            $roleLabels = ['superadmin' => '👑 Super admin', 'admin' => '🛡️ Admin', 'member' => '👤 Membre'];
            if (empty($roleMap)): ?>
                <div class="ui-empty"><div class="ui-empty-icon">🪪</div>Aucune donnée.</div>
            <?php else:
                foreach ($roleMap as $role => $count):
                    $pct = $totalUsers ? round($count / $totalUsers * 100) : 0; ?>
                    <div style="margin-bottom:16px">
                        <div class="u-between" style="margin-bottom:6px">
                            <span class="fw-semibold"><?= $roleLabels[$role] ?? $h(ucfirst($role)) ?></span>
                            <span class="u-muted"><?= $count ?> · <?= $pct ?>%</span>
                        </div>
                        <div class="ui-progress"><span style="width:<?= $pct ?>%"></span></div>
                    </div>
                <?php endforeach;
            endif; ?>
        </div>
    </div>
</div>

<!-- Accès rapides -->
<div class="ui-card u-mt">
    <div class="ui-card-head">⚡ Accès rapides</div>
    <div class="ui-card-body">
        <div class="ui-grid cols-4">
            <a class="ui-btn" href="<?= u('/admin/users') ?>">👥 Utilisateurs</a>
            <a class="ui-btn" href="<?= u('/admin/configuration') ?>">⚙️ Configuration</a>
            <a class="ui-btn" href="<?= u('/admin/modules') ?>">🧩 Modules</a>
            <a class="ui-btn" href="<?= u('/admin/security') ?>">🛡️ Sécurité</a>
            <a class="ui-btn" href="<?= u('/admin/monitoring') ?>">📡 Monitoring</a>
            <a class="ui-btn" href="<?= u('/admin/configuration/ai-models') ?>">🤖 Modèles IA</a>
        </div>
    </div>
</div>

<?php admin_footer(); ?>
