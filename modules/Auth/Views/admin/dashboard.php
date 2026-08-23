<?php
/**
 * Dashboard Admin — Aegis Framework V4 (nouvelle UI)
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
        <!-- Le site tel que le voit un visiteur. Nouvel onglet : on regarde
             sans perdre l'écran d'administration en cours. -->
        <a class="ui-btn" href="<?= u('/') ?>" target="_blank" rel="noopener">🌐 Voir le site</a>
        <a class="ui-btn" href="<?= u('/admin/users') ?>">👥 Utilisateurs</a>
        <a class="ui-btn primary" href="<?= u('/admin/users/create') ?>">➕ Nouvel utilisateur</a>
    </div>
</div>

<?php
/* ═══════════════════════════════════════════════════════════════════════
   SANTÉ DE L'INSTALLATION

   Placé AVANT les chiffres d'audience : la première question d'un
   administrateur qui arrive n'est pas « combien d'inscrits ? » mais
   « est-ce que quelque chose est cassé ? ». Le bandeau ne s'affiche en
   entier que s'il a quelque chose à dire ; tout va bien tient en une ligne.
   ═══════════════════════════════════════════════════════════════════════ */
$health   = $health   ?? ['status' => 'ok', 'ok' => 0, 'warn' => 0, 'error' => 0, 'issues' => [], 'updates' => [], 'env' => []];
$activity = $activity ?? [];
$env      = $health['env'] ?? [];

$hStatus  = $health['status'] ?? 'ok';
$hIssues  = $health['issues'] ?? [];
$hUpdates = $health['updates'] ?? [];

$hTon = ['ok' => 'green', 'warn' => 'amber', 'error' => 'red'][$hStatus] ?? 'green';
$hIco = ['ok' => '🟢', 'warn' => '🟡', 'error' => '🔴'][$hStatus] ?? '🟢';
$hTit = [
    'ok'    => 'Tout est en ordre',
    'warn'  => 'Quelques points méritent votre attention',
    'error' => 'Des erreurs demandent une intervention',
][$hStatus] ?? '';
?>

<div class="ui-card dash-health tone-<?= $hTon ?>" id="dashHealth" data-sig="<?= $h(md5($hStatus . count($hIssues) . count($hUpdates))) ?>">
    <div class="ui-card-body">
        <div class="dash-health__top">
            <span class="dash-health__ico"><?= $hIco ?></span>
            <div class="dash-health__say">
                <b><?= $h($hTit) ?></b>
                <em>
                    <?= (int)($health['ok'] ?? 0) ?> contrôle<?= ($health['ok'] ?? 0) > 1 ? 's' : '' ?> au vert
                    <?php if (($health['warn'] ?? 0) > 0): ?> · <?= (int)$health['warn'] ?> avertissement<?= $health['warn'] > 1 ? 's' : '' ?><?php endif; ?>
                    <?php if (($health['error'] ?? 0) > 0): ?> · <?= (int)$health['error'] ?> erreur<?= $health['error'] > 1 ? 's' : '' ?><?php endif; ?>
                    <?php if ($hUpdates): ?> · <?= count($hUpdates) ?> mise<?= count($hUpdates) > 1 ? 's' : '' ?> à jour<?php endif; ?>
                </em>
            </div>
            <div class="dash-health__go">
                <a class="ui-btn sm" href="<?= u('/admin/diagnostic') ?>">🩺 Diagnostic</a>
                <a class="ui-btn sm" href="<?= u('/admin/audit') ?>">📋 Journal</a>
                <!-- Masquer, pas résoudre : le bandeau revient dès que l'état
                     change, et de toute façon à la prochaine session. -->
                <button type="button" class="dash-health__x" id="dashHealthX"
                        title="Masquer jusqu'à la prochaine visite" aria-label="Masquer ce bandeau">✕</button>
            </div>
        </div>

        <?php if ($hIssues || $hUpdates): ?>
        <div class="dash-health__list">
            <?php foreach ($hIssues as $i): ?>
            <a class="dash-issue" href="<?= u('/admin/diagnostic') ?>">
                <span class="dash-issue__dot dash-issue__dot--<?= $h($i['level']) ?>"></span>
                <b><?= $h($i['label']) ?></b>
                <em><?= $h(mb_strimwidth($i['detail'], 0, 92, '…')) ?></em>
            </a>
            <?php endforeach; ?>

            <?php foreach ($hUpdates as $m): ?>
            <a class="dash-issue" href="<?= u('/admin/diagnostic') ?>">
                <span class="dash-issue__dot dash-issue__dot--warn"></span>
                <b>⬆️ <?= $h($m['name']) ?></b>
                <em>
                    <?php if ($m['from'] !== $m['to']): ?>
                        Mise à jour disponible : v<?= $h($m['from']) ?> → v<?= $h($m['to']) ?>
                    <?php else: ?>
                        <?= (int)$m['migrations'] ?> migration<?= $m['migrations'] > 1 ? 's' : '' ?> en attente
                    <?php endif; ?>
                </em>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="dash-shell">
<div class="dash-main">

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

<div class="ui-grid u-mt dash-row" style="grid-template-columns:2fr 1fr">
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


<!-- ═══ Sécurité · Stockage · Audience ══════════════════════════════════ -->
<div class="ui-grid cols-3 u-mt dash-row">

    <!-- La porte d'entrée. Ces chiffres existaient dans `login_attempts`
         depuis toujours, mais ne se voyaient nulle part : une série d'échecs
         sur un même compte est le premier signe d'une attaque. -->
    <div class="ui-card<?= !empty($security['suspect']) ? ' tone-red' : '' ?>">
        <div class="ui-card-head">
            🛡️ Connexions (24 h)
            <span class="ui-card-actions"><a class="ui-btn sm" href="<?= u('/admin/security') ?>">Sécurité</a></span>
        </div>
        <div class="ui-card-body">
            <div class="dash-sec">
                <div class="dash-sec__n">
                    <b><?= (int)($security['success_24h'] ?? 0) ?></b>
                    <span>réussies</span>
                </div>
                <div class="dash-sec__n<?= ($security['failed_24h'] ?? 0) > 0 ? ' is-warn' : '' ?>">
                    <b><?= (int)($security['failed_24h'] ?? 0) ?></b>
                    <span>échouées</span>
                </div>
                <div class="dash-sec__n<?= ($security['blocked'] ?? 0) > 0 ? ' is-warn' : '' ?>">
                    <b><?= (int)($security['blocked'] ?? 0) ?></b>
                    <span>bloqués</span>
                </div>
            </div>

            <?php if (!empty($security['targets'])): ?>
            <div class="dash-sec__list">
                <span class="dash-sec__k">Comptes les plus visés</span>
                <?php foreach ($security['targets'] as $t): ?>
                <div class="dash-sec__t">
                    <em><?= $h(mb_strimwidth((string)$t['identifier'], 0, 28, '…')) ?></em>
                    <b><?= (int)$t['n'] ?></b>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($security['suspect'])): ?>
            <p class="dash-sec__alert">
                ⚠️ Beaucoup d'échecs pour peu de réussites — vérifiez le journal de sécurité.
            </p>
            <?php elseif (empty($security['targets'])): ?>
            <p class="u-muted" style="margin:12px 0 0;font-size:12.5px">Aucun échec sur les dernières 24 heures.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Un disque plein ne se manifeste que par des téléversements qui
         échouent en silence. Autant le voir venir. -->
    <?php
    $pct     = $storage['used_pct'] ?? null;
    $tonDisk = $pct === null ? '' : ($pct >= 90 ? ' tone-red' : ($pct >= 75 ? ' tone-amber' : ''));
    $go      = fn($o) => $o === null ? '?' : number_format($o / 1073741824, 1, ',', ' ') . ' Go';
    ?>
    <div class="ui-card<?= $tonDisk ?>">
        <div class="ui-card-head">💾 Stockage</div>
        <div class="ui-card-body">
            <?php if ($pct !== null): ?>
            <div class="dash-disk">
                <div class="dash-disk__top">
                    <b><?= $pct ?> %</b>
                    <em><?= $go($storage['free'] ?? null) ?> libres sur <?= $go($storage['total'] ?? null) ?></em>
                </div>
                <div class="dash-disk__bar">
                    <div style="width:<?= max(2, min(100, $pct)) ?>%"></div>
                </div>
            </div>
            <?php endif; ?>

            <div class="dash-dirs">
                <?php foreach (($storage['dirs'] ?? []) as $d): ?>
                <div class="dash-dirs__i">
                    <span><?= $d['writable'] ? '✅' : '⛔' ?> <?= $h($d['label']) ?></span>
                    <em><?= $d['writable'] ? 'inscriptible' : 'lecture seule' ?></em>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($storage['unwritable'])): ?>
            <p class="dash-sec__alert">
                ⚠️ <?= count($storage['unwritable']) ?> dossier<?= count($storage['unwritable']) > 1 ? 's' : '' ?> non inscriptible<?= count($storage['unwritable']) > 1 ? 's' : '' ?> : les écritures échoueront.
            </p>
            <?php elseif ($pct !== null && $pct >= 90): ?>
            <p class="dash-sec__alert">⚠️ Le disque est presque plein.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- L'audience, seulement si Analytics est là. On distingue « pas
         installé » de « installé mais sans trafic » : ce n'est pas la même
         information, et la seconde n'appelle aucune action. -->
    <div class="ui-card">
        <?php $audState = $audience['state'] ?? 'absent'; ?>
        <div class="ui-card-head">
            📈 Audience (7 j)
            <?php if ($audState === 'live' || $audState === 'idle'): ?>
            <span class="ui-card-actions"><a class="ui-btn sm" href="<?= u('/admin/analytics/dashboard') ?>">Analytics</a></span>
            <?php endif; ?>
        </div>
        <div class="ui-card-body">
            <?php if ($audState === 'absent'): ?>
                <p class="u-muted" style="margin:0;font-size:13px;line-height:1.6">
                    Le module <strong>Analytics</strong> n'est pas installé.
                    <br><a href="<?= u('/admin/modules') ?>">L'ajouter</a> pour mesurer votre audience,
                    sans cookie et sans service tiers.
                </p>
            <?php elseif ($audState === 'inactive'): ?>
                <!-- Désactivé : ses tables existent encore, mais plus rien ne
                     s'y écrit. Afficher les anciens chiffres laisserait croire
                     que la mesure continue. -->
                <p class="u-muted" style="margin:0;font-size:13px;line-height:1.6">
                    ⏸️ Le module <strong>Analytics</strong> est <strong>désactivé</strong>.
                    <br>Plus aucune visite n'est enregistrée depuis.
                    <br><a href="<?= u('/admin/modules') ?>">Le réactiver</a> pour reprendre la mesure.
                </p>
            <?php elseif ($audState === 'idle'): ?>
                <p class="u-muted" style="margin:0;font-size:13px;line-height:1.6">
                    Analytics tourne, mais aucune visite n'a été enregistrée cette semaine.
                </p>
            <?php else: ?>
                <div class="dash-aud">
                    <div class="dash-aud__n"><b><?= (int)$audience['pageviews'] ?></b><span>pages vues</span></div>
                    <div class="dash-aud__n"><b><?= (int)$audience['visitors'] ?></b><span>visiteurs</span></div>
                </div>
                <div class="dash-spark">
                    <?php foreach ($audience['days'] as $d): ?>
                    <span class="dash-spark__b" title="<?= $h($d['day']) ?> — <?= (int)$d['n'] ?> vues">
                        <i style="height:<?= max(3, (int)$d['pct']) ?>%"></i>
                    </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* ── Connexions ───────────────────────────────────────────────────────── */
.dash-sec { display: flex; gap: 8px; }
.dash-sec__n {
  flex: 1; text-align: center;
  padding: 11px 6px; border-radius: 9px;
  background: rgba(127,127,127,.07);
}
.dash-sec__n b { display: block; font-size: 20px; font-weight: 700; font-variant-numeric: tabular-nums; }
.dash-sec__n span { font-size: 11px; opacity: .68; }
.dash-sec__n.is-warn { background: rgba(245,158,11,.13); }
.dash-sec__n.is-warn b { color: var(--amber, #f59e0b); }

.dash-sec__list { margin-top: 13px; padding-top: 11px; border-top: 1px solid var(--border, rgba(127,127,127,.15)); }
.dash-sec__k { display: block; font-size: 10.5px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; opacity: .55; margin-bottom: 6px; }
.dash-sec__t { display: flex; justify-content: space-between; gap: 10px; padding: 3px 0; font-size: 12.5px; }
.dash-sec__t em { font-style: normal; opacity: .8; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dash-sec__t b { font-variant-numeric: tabular-nums; }

.dash-sec__alert {
  margin: 12px 0 0; padding: 9px 11px;
  border-radius: 8px; background: rgba(245,158,11,.12);
  font-size: 12px; line-height: 1.5;
}

/* ── Stockage ─────────────────────────────────────────────────────────── */
.dash-disk__top { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; margin-bottom: 7px; }
.dash-disk__top b { font-size: 20px; font-weight: 700; font-variant-numeric: tabular-nums; }
.dash-disk__top em { font-style: normal; font-size: 11.5px; opacity: .65; }
.dash-disk__bar { height: 7px; border-radius: 4px; background: rgba(127,127,127,.15); overflow: hidden; }
.dash-disk__bar > div { height: 100%; background: var(--accent, #6366f1); }
.tone-amber .dash-disk__bar > div { background: var(--amber, #f59e0b); }
.tone-red   .dash-disk__bar > div { background: var(--red, #ef4444); }

.dash-dirs { margin-top: 13px; display: flex; flex-direction: column; gap: 1px; }
.dash-dirs__i {
  display: flex; justify-content: space-between; gap: 10px;
  padding: 6px 0; font-size: 12.5px;
  border-bottom: 1px solid var(--border, rgba(127,127,127,.1));
}
.dash-dirs__i:last-child { border-bottom: 0; }
.dash-dirs__i em { font-style: normal; opacity: .6; font-size: 11.5px; }

/* ── Audience ─────────────────────────────────────────────────────────── */
.dash-aud { display: flex; gap: 8px; margin-bottom: 13px; }
.dash-aud__n { flex: 1; text-align: center; padding: 11px 6px; border-radius: 9px; background: rgba(127,127,127,.07); }
.dash-aud__n b { display: block; font-size: 20px; font-weight: 700; font-variant-numeric: tabular-nums; }
.dash-aud__n span { font-size: 11px; opacity: .68; }

/* Courbe en barres, sans bibliothèque : sept jours, sept barres. */
.dash-spark { display: flex; align-items: flex-end; gap: 4px; height: 46px; }
.dash-spark__b { flex: 1; height: 100%; display: flex; align-items: flex-end; }
.dash-spark__b > i {
  display: block; width: 100%;
  border-radius: 3px 3px 0 0;
  background: var(--accent, #6366f1);
  opacity: .75;
}
.dash-spark__b:hover > i { opacity: 1; }
</style>

<!-- ═══ Activité récente & environnement ═══════════════════════════════ -->
<div class="ui-grid cols-2 u-mt dash-row">

    <!-- Le journal d'audit : qui a fait quoi. Sept lignes suffisent à voir
         si quelque chose d'inattendu s'est produit ; le reste est dans le
         journal complet. -->
    <div class="ui-card">
        <div class="ui-card-head">
            📋 Activité récente
            <span class="ui-card-actions"><a class="ui-btn sm" href="<?= u('/admin/audit') ?>">Tout le journal</a></span>
        </div>
        <div class="ui-card-body" style="padding:0">
            <?php if (!$activity): ?>
                <p class="u-muted" style="text-align:center;padding:26px;margin:0">
                    Aucune action enregistrée pour l'instant.
                </p>
            <?php else: ?>
            <div class="dash-log">
                <?php foreach ($activity as $a):
                    // L'action est un identifiant technique (« module.activate ») :
                    // on n'en garde que la partie utile pour l'œil.
                    $act   = (string)($a['action'] ?? '');
                    $bout  = strrchr($act, '.');
                    $verbe = $bout !== false ? ltrim($bout, '.') : $act;
                    $icones = [
                        'activate' => '✅', 'deactivate' => '⏸️', 'delete' => '🗑️',
                        'create' => '➕', 'update' => '✏️', 'login' => '🔑', 'install' => '📦',
                    ];
                ?>
                <div class="dash-log__i">
                    <span class="dash-log__ico"><?= $icones[$verbe] ?? '•' ?></span>
                    <span class="dash-log__say">
                        <b><?= $h($a['summary'] ?: $act) ?></b>
                        <em>
                            <?= $h($a['username'] ?: 'système') ?>
                            <?php if (!empty($a['target_id'])): ?> · <?= $h($a['target_id']) ?><?php endif; ?>
                            · <code><?= $h($act) ?></code>
                        </em>
                    </span>
                    <span class="dash-log__when"><?= $h($a['created_at']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ce sur quoi ça tourne. Discret, mais c'est la première chose qu'on
         demande quand quelque chose ne va pas — et la première qu'on ne
         trouve pas. -->
    <div class="ui-card">
        <div class="ui-card-head">🖥️ Environnement</div>
        <div class="ui-card-body">
            <div class="dash-env">
                <div class="dash-env__i"><span>Aegis Framework</span><b><?= $h($env['framework'] ?? '?') ?></b></div>
                <div class="dash-env__i"><span>PHP</span><b><?= $h($env['php'] ?? '?') ?></b></div>
                <div class="dash-env__i"><span>MySQL</span><b><?= $h($env['mysql'] ?? '?') ?></b></div>
                <div class="dash-env__i">
                    <span>Environnement</span>
                    <b><?= ($env['is_prod'] ?? false) ? '🏭 production' : '🧪 ' . $h($env['app_env'] ?? 'dev') ?></b>
                </div>
            </div>

            <?php
            // Deux réglages qui n'ont rien à faire sur un site en ligne, et
            // qui ne se remarquent nulle part ailleurs dans l'interface.
            $alertes = [];
            if (!empty($env['install_dir'])) {
                $alertes[] = ['Le dossier <code>/install</code> est encore présent.', 'Il permet de relancer l’installation : supprimez-le avant la mise en ligne.'];
            }
            if (!empty($env['debug']) && !empty($env['is_prod'])) {
                $alertes[] = ['Le mode debug est actif en production.', 'Les erreurs détaillées sont exposées aux visiteurs.'];
            }
            ?>
            <?php if ($alertes): ?>
            <div class="dash-warn">
                <?php foreach ($alertes as [$titre, $quoi]): ?>
                <div class="dash-warn__i">
                    <span>⚠️</span>
                    <div><b><?= $titre ?></b><em><?= $h($quoi) ?></em></div>
                </div>
                <?php endforeach; ?>
                <a class="ui-btn sm" href="<?= u('/admin/diagnostic') ?>" style="width:100%;justify-content:center">Ouvrir le diagnostic</a>
            </div>
            <?php else: ?>
            <p class="u-muted" style="margin:14px 0 0;font-size:13px">
                ✅ Rien à signaler côté configuration.
            </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* ── Bandeau de santé ─────────────────────────────────────────────────── */
.dash-health__top { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
.dash-health__ico { font-size: 26px; line-height: 1; flex-shrink: 0; }
.dash-health__say { display: flex; flex-direction: column; gap: 3px; min-width: 0; flex: 1 1 260px; }
.dash-health__say b { font-size: 15px; }
.dash-health__say em { font-style: normal; font-size: 12.5px; opacity: .75; }
.dash-health__go { display: flex; gap: 7px; flex-shrink: 0; }

.dash-health__list {
  display: flex; flex-direction: column; gap: 2px;
  margin-top: 14px; padding-top: 13px;
  border-top: 1px solid var(--border, rgba(127,127,127,.18));
}
.dash-issue {
  display: flex; align-items: baseline; gap: 10px;
  padding: 8px 10px; border-radius: 8px;
  color: inherit; text-decoration: none; font-size: 13px;
}
.dash-issue:hover { background: rgba(127,127,127,.08); }
.dash-issue b { flex-shrink: 0; font-weight: 600; }
.dash-issue em {
  font-style: normal; font-size: 12px; opacity: .7;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.dash-issue__dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; align-self: center; }
.dash-issue__dot--warn  { background: var(--amber, #f59e0b); }
.dash-issue__dot--error { background: var(--red, #ef4444); }

/* ── Journal ──────────────────────────────────────────────────────────── */
.dash-log { display: flex; flex-direction: column; }
.dash-log__i {
  display: flex; align-items: center; gap: 11px;
  padding: 10px 16px;
  border-bottom: 1px solid var(--border, rgba(127,127,127,.12));
}
.dash-log__i:last-child { border-bottom: 0; }
.dash-log__ico { flex-shrink: 0; width: 22px; text-align: center; }
.dash-log__say { display: flex; flex-direction: column; gap: 2px; min-width: 0; flex: 1; }
.dash-log__say b { font-size: 13px; font-weight: 600; }
.dash-log__say em {
  font-style: normal; font-size: 11.5px; opacity: .62;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.dash-log__say code { font-size: 10.5px; }
.dash-log__when {
  flex-shrink: 0; font-size: 11px; opacity: .55;
  font-variant-numeric: tabular-nums; white-space: nowrap;
}

/* ── Environnement ────────────────────────────────────────────────────── */
.dash-env { display: flex; flex-direction: column; gap: 1px; }
.dash-env__i {
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
  padding: 9px 2px;
  border-bottom: 1px solid var(--border, rgba(127,127,127,.1));
  font-size: 13px;
}
.dash-env__i:last-child { border-bottom: 0; }
.dash-env__i span { opacity: .7; }
.dash-env__i b { font-variant-numeric: tabular-nums; }

.dash-warn { display: flex; flex-direction: column; gap: 10px; margin-top: 14px; }
.dash-warn__i {
  display: flex; gap: 10px; align-items: flex-start;
  padding: 11px 13px; border-radius: 9px;
  background: rgba(245,158,11,.1);
  border: 1px solid rgba(245,158,11,.3);
  font-size: 12.5px;
}
.dash-warn__i b { display: block; font-weight: 600; margin-bottom: 2px; }
.dash-warn__i em { font-style: normal; opacity: .78; line-height: 1.5; }
.dash-warn__i code { font-size: 11.5px; }

@media (max-width: 900px) {
  .dash-health__go { width: 100%; }
  .dash-health__go .ui-btn { flex: 1; justify-content: center; }
  .dash-log__when { display: none; }
}
</style>

</div><!-- /.dash-main -->

<aside class="dash-side">
<!-- Accès rapides -->
<div class="ui-card u-mt">
    <div class="ui-card-head">⚡ Accès rapides</div>
    <div class="ui-card-body">
        <div class="dash-links">
            <a class="ui-btn" href="<?= u('/admin/users') ?>">👥 Utilisateurs</a>
            <a class="ui-btn" href="<?= u('/admin/configuration') ?>">⚙️ Configuration</a>
            <a class="ui-btn" href="<?= u('/admin/modules') ?>">🧩 Modules</a>
            <a class="ui-btn" href="<?= u('/admin/security') ?>">🛡️ Sécurité</a>
            <a class="ui-btn" href="<?= u('/admin/monitoring') ?>">📡 Monitoring</a>
            <a class="ui-btn" href="<?= u('/admin/configuration/ai-models') ?>">🤖 Modèles IA</a>
        </div>
    </div>
</div>

<?php
/* Tableaux de bord des modules ACTIFS — basé sur la liste réelle des modules
   chargés (pas sur les groupes de menu, qui fusionnent plusieurs modules).
   Chaque module actif exposant un tableau de bord = un bouton.
   Les modules cœur (Auth/System/Configuration) sont exclus : leurs pages sont
   déjà dans « Accès rapides » ci-dessus. */
$mm       = $GLOBALS['moduleManager'] ?? null;
$modItems = [];
if ($mm && method_exists($mm, 'discoverModules') && method_exists($mm, 'getLoadedModules')) {
    $configs = (array) $mm->discoverModules();
    $loaded  = (array) $mm->getLoadedModules();     // modules chargés = actifs
    $core    = ['System' => 1, 'Auth' => 1, 'Configuration' => 1];
    $seen    = [];
    foreach ($loaded as $name => $_inst) {
        if (isset($core[$name])) { continue; }
        $cfg  = $configs[$name] ?? null;
        if (!is_array($cfg)) { continue; }
        $menu = $cfg['menu'] ?? $cfg['admin_menu'] ?? [];
        $m0   = (is_array($menu) && isset($menu[0]) && is_array($menu[0])) ? $menu[0] : [];
        // URL du tableau de bord : 1er enfant, sinon url/match du groupe.
        $url = $m0['children'][0]['url'] ?? ($m0['url'] ?? ($m0['match'] ?? null));
        if (!$url || $url === '/admin/dashboard' || isset($seen[$url])) { continue; }
        $seen[$url] = 1;
        $modItems[] = [
            'icon'  => (string) ($m0['icon'] ?? '🧩'),
            'label' => (string) ($cfg['name'] ?? $name),
            'url'   => (string) $url,
        ];
    }
    usort($modItems, fn($a, $b) => strcasecmp($a['label'], $b['label']));
}
?>
<?php if ($modItems): ?>
<!-- Tableaux de bord des modules activés -->
<div class="ui-card u-mt">
    <div class="ui-card-head">🚀 Tableaux de bord des modules
        <span class="ui-card-actions ui-badge accent"><?= count($modItems) ?></span>
    </div>
    <div class="ui-card-body">
        <div class="dash-links">
            <?php foreach ($modItems as $m): ?>
                <a class="ui-btn" href="<?= u($m['url']) ?>"><?= htmlspecialchars($m['icon']) ?> <?= htmlspecialchars($m['label']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

    <?php
    // Les jauges ferment la colonne : on veut voir la charge sans quitter le
    // tableau de bord, mais elle ne prime pas sur les accès.
    $gaugesCompact = true;
    require ROOT_PATH . '/framework/Views/theme/components/metrics-gauges.php';
    ?>
</aside>
</div><!-- /.dash-shell -->


<style>
/* ═══ La coque du tableau de bord ═════════════════════════════════════════
   Le contenu à gauche, les accès à droite. La colonne suit le défilement :
   on veut pouvoir sauter vers un module en lisant n'importe quel bloc, sans
   remonter. Sous 1100 px elle repasse dessous — un rail de 260 px sur un
   écran de portable ne laisserait rien à la colonne principale. */
.dash-shell {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 280px;
  gap: 18px;
  align-items: start;
}
.dash-main { min-width: 0; display: flex; flex-direction: column; gap: 18px; }
/* Les blocs internes portaient leur propre marge : la coque s'en charge. */
.dash-main > .u-mt { margin-top: 0 !important; }

.dash-side {
  position: sticky;
  top: 84px;
  display: flex; flex-direction: column; gap: 18px;
  max-height: calc(100vh - 104px);
  overflow-y: auto;
  scrollbar-width: thin;
}
.dash-side .u-mt { margin-top: 0 !important; }

/* Les accès s'empilent : dans 280 px, une grille de quatre colonnes de
   boutons ne tient pas. */
.dash-links { display: flex; flex-direction: column; gap: 6px; }
.dash-links .ui-btn { width: 100%; justify-content: flex-start; }

@media (max-width: 1100px) {
  .dash-shell { grid-template-columns: minmax(0, 1fr); }
  .dash-side { position: static; max-height: none; overflow: visible; }
  /* Rendue à toute la largeur, la liste peut redevenir une grille. */
  .dash-links { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); }
}
</style>

<style>
/* ═══ Harmonisation ═══════════════════════════════════════════════════════
   Les cartes d'une même rangée avaient chacune la hauteur de son contenu :
   des blocs de tailles différentes, alignés en haut, et des trous en bas.
   On les étire toutes à la hauteur de la plus grande, et on pousse ce qui
   vient en dernier — alerte ou courbe — contre le bas. La rangée retrouve
   une ligne de base. */
.dash-row { align-items: stretch; }
.dash-row > .ui-card { display: flex; flex-direction: column; height: 100%; }
.dash-row > .ui-card > .ui-card-body { flex: 1; display: flex; flex-direction: column; }

/* Ce qui doit rester collé au bas de sa carte, quelle qu'en soit la hauteur. */
.dash-row .dash-sec__alert,
.dash-row .dash-spark { margin-top: auto; }
.dash-row .dash-sec__list { margin-top: 13px; }
.dash-row .dash-dirs { margin-top: 13px; }

/* La zone de journal ne doit pas étirer sa rangée indéfiniment : au-delà,
   elle défile chez elle plutôt que d'imposer sa hauteur à la voisine. */
.dash-log { max-height: 340px; overflow-y: auto; scrollbar-width: thin; }

/* ── Bandeau de santé ─────────────────────────────────────────────────────
   Il se place AVANT la coque à deux colonnes, donc en dehors du flux qui
   espace les blocs. Sans marge propre, il se retrouvait collé aux quatre
   compteurs et au rail — il faut la lui donner ici. */
.dash-health { margin-bottom: 18px; }
.dash-health__x {
  width: 30px; height: 30px;
  display: grid; place-items: center;
  border: 1px solid transparent; border-radius: 8px;
  background: transparent; color: inherit;
  font-size: 13px; cursor: pointer; opacity: .5;
  transition: opacity .15s, background .15s;
}
.dash-health__x:hover { opacity: 1; background: rgba(127,127,127,.15); }

/* ── La colonne latérale ne défile pas ────────────────────────────────────
   Elle portait `max-height` + `overflow-y: auto`, ce qui lui ajoutait son
   propre ascenseur — deux barres de défilement sur un même écran, dont une
   qu'on ne voit qu'en survolant. Elle suit désormais la page. */
.dash-side { max-height: none; overflow: visible; }
</style>

<script>
/* Le bandeau de santé se masque jusqu'à la prochaine visite.

   sessionStorage, pas localStorage : masquer pour toujours un avertissement
   de sécurité serait un mauvais service. L'onglet fermé, il revient.

   La signature de l'état est mémorisée avec : si un nouveau problème
   apparaît, le bandeau réapparaît sans qu'on ait à le redemander. */
(function () {
  var bandeau = document.getElementById('dashHealth');
  var croix   = document.getElementById('dashHealthX');
  if (!bandeau || !croix) { return; }

  var CLE = 'aegis_health_masque';
  var sig = bandeau.getAttribute('data-sig') || '';

  try {
    if (sessionStorage.getItem(CLE) === sig) { bandeau.hidden = true; }
  } catch (e) {}

  croix.addEventListener('click', function () {
    bandeau.hidden = true;
    try { sessionStorage.setItem(CLE, sig); } catch (e) {}
  });
})();
</script>

<?php admin_footer(); ?>
