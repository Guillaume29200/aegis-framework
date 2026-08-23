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

<?php
/**
 * Votre propre adresse est-elle en liste blanche ?
 *
 * C'est le cas de 127.0.0.1 et ::1 par défaut — voir `trusted_ips` dans la
 * configuration de sécurité, où le commentaire dit « garder localhost pour
 * WAMP ». Le comportement est voulu : on ne veut pas se bloquer soi-même en
 * développement.
 *
 * Mais rien ne le disait ici. Un développeur qui travaille en local voit donc
 * une page vide depuis le premier jour et en conclut que rien ne fonctionne,
 * alors que la détection tourne — elle ignore simplement son adresse.
 */
$_monIp = '';
try {
    $_scs   = $GLOBALS['securityCenterService'] ?? null;
    $_sfs   = $GLOBALS['securityFirewallService'] ?? null;
    $_monIp = $_sfs && method_exists($_sfs, 'getClientIp') ? (string) $_sfs->getClientIp() : (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $_blanche = $_scs && $_monIp !== '' ? $_scs->isWhitelisted($_monIp) : false;
} catch (\Throwable $e) {
    $_blanche = false;
}
?>
<?php if (!empty($_blanche)): ?>
<div class="ui-alert info" style="display:flex;gap:12px;align-items:flex-start">
    <span style="font-size:18px;line-height:1.2">ℹ️</span>
    <div>
        <strong>Votre adresse (<code><?= $h($_monIp) ?></code>) est en liste blanche.</strong>
        Aucun de vos accès n'est analysé ni enregistré — c'est pourquoi les compteurs
        peuvent rester à zéro alors que la protection fonctionne.
        <br>
        <small>
            Les adresses de confiance se règlent dans <code>framework/config/security.php</code>
            (<code>trusted_ips</code>). <code>127.0.0.1</code> et <code>::1</code> y figurent par défaut
            pour ne pas vous bloquer en développement local. Le trafic des <em>autres</em> adresses est,
            lui, bien inspecté.
        </small>
    </div>
</div>
<?php endif; ?>

<!-- ════ Onglets ════ -->
<div class="sec-tabs" id="sec-tabs">
    <button class="sec-tab active" data-tab="dashboard">📊 Tableau de bord</button>
    <button class="sec-tab" data-tab="detectors">🧩 Détecteurs</button>
    <button class="sec-tab" data-tab="config">⚙️ Seuils &amp; config</button>
    <button class="sec-tab" data-tab="lists">📋 Listes IP</button>
    <button class="sec-tab" data-tab="geo">🌍 Pays</button>
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
                        <span class="ui-switch-track"></span>
                        <span>Catégorie active</span>
                    </label>
                </div>
                <div class="ui-card-body" style="padding:0">
                    <table class="ui-table">
                        <thead><tr><th style="width:60px">Actif</th><th>Détecteur</th><th>Description</th><th style="width:110px">Score</th><th style="width:150px">Gravité</th></tr></thead>
                        <tbody>
                        <?php foreach ($cat['rules'] as $key => $rule): ?>
                            <tr>
                                <td><label class="ui-switch sec-rule-toggle"><input type="checkbox" name="rule_enabled[<?= $h($key) ?>]" <?= (int)$rule['enabled'] ? 'checked' : '' ?>><span class="ui-switch-track"></span></label></td>
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
                    <label class="ui-switch" style="margin-bottom:14px"><input type="checkbox" name="enabled" <?= $globalOn ? 'checked' : '' ?>><span class="ui-switch-track"></span><span>Centre de sécurité activé</span></label>
                    <label class="ui-switch"><input type="checkbox" name="auto_block" <?= (string)($settings['auto_block'] ?? '1') === '1' ? 'checked' : '' ?>><span class="ui-switch-track"></span><span>Blocage automatique des IP malveillantes</span></label>
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

<!-- ════════════════ FILTRAGE GÉOGRAPHIQUE ════════════════ -->
<section class="sec-panel" data-panel="geo">
    <?php
    $geoBase   = $geoBase   ?? ['existe' => false, 'plages' => 0, 'construite_le' => null, 'taille' => 0];
    $geoConnus = $geoConnus ?? [];
    $geoPays   = $geoPays   ?? [];
    $geoMode   = $geoMode   ?? 'off';
    $geoAge    = $geoBase['construite_le'] ? (int) floor((time() - $geoBase['construite_le']) / 86400) : null;
    ?>

    <div class="ui-card" style="margin-bottom:16px">
        <div class="ui-card-head">🌍 À quoi sert le filtrage par pays</div>
        <div class="ui-card-body">
            <p style="margin:0 0 10px;line-height:1.65">
                Chaque visiteur est situé à partir de son adresse IP. Selon le mode choisi, l'accès au site
                lui est refusé avec une page en anglais et un code <code>403</code>. C'est une <b>surcouche</b> :
                elle réduit le bruit de fond — balayages automatisés, tentatives de force brute, pourriel —
                venu de régions dont vous n'attendez aucun visiteur.
            </p>
            <p style="margin:0 0 10px;line-height:1.65">
                La correspondance IP → pays est <b>entièrement locale</b> : aucun appel à un service extérieur,
                donc rien qui ralentisse vos pages ni qui s'effondre sous la charge. Une recherche coûte
                environ <b>0,13 ms</b>.
            </p>
            <p style="margin:0;line-height:1.65;padding:10px 12px;border-radius:8px;background:var(--amber-soft,#fef3c7)">
                ⚠️ Un filtre géographique se contourne avec un VPN : il décourage l'automatisme, il n'arrête pas
                quelqu'un de déterminé. Il ne remplace ni les mots de passe solides, ni la double authentification,
                ni le pare-feu. <b>Un pays inconnu n'est jamais bloqué</b>, et <b>un administrateur connecté non plus</b> —
                de quoi corriger une erreur de réglage sans se retrouver dehors.
            </p>
        </div>
    </div>

    <div class="ui-grid cols-2" style="grid-template-columns:1fr 1fr;align-items:start">
        <!-- État de la base -->
        <div class="ui-card">
            <div class="ui-card-head">🗃️ Base de données des pays</div>
            <div class="ui-card-body">
                <?php if ($geoBase['existe']): ?>
                    <table class="ui-table">
                        <tr><td>Plages référencées</td><td><b><?= number_format($geoBase['plages'], 0, ',', ' ') ?></b></td></tr>
                        <tr><td>Pays couverts</td><td><b><?= count($geoConnus) ?></b></td></tr>
                        <tr><td>Taille du fichier</td><td><?= number_format($geoBase['taille'] / 1024, 0, ',', ' ') ?> Ko</td></tr>
                        <tr><td>Construite le</td><td>
                            <?= date('d/m/Y à H:i', (int) $geoBase['construite_le']) ?>
                            <?php if ($geoAge !== null && $geoAge > 60): ?>
                                <span class="ui-badge amber">⚠️ <?= $geoAge ?> jours</span>
                            <?php elseif ($geoAge !== null): ?>
                                <span class="ui-badge green"><?= $geoAge ?> j</span>
                            <?php endif; ?>
                        </td></tr>
                    </table>
                    <p class="u-muted" style="margin:12px 0 0;font-size:13px;line-height:1.6">
                        Source : les cinq registres régionaux (RIPE, ARIN, APNIC, AFRINIC, LACNIC), qui publient
                        leurs attributions chaque jour. Une reconstruction tous les deux ou trois mois suffit :
                        les attributions bougent peu.
                    </p>
                <?php else: ?>
                    <p style="margin:0 0 12px;line-height:1.6">
                        <span class="ui-badge amber">Aucune base</span><br>
                        Le filtrage ne peut pas fonctionner sans elle. La construction télécharge environ
                        <b>27 Mo</b> depuis les cinq registres et prend <b>une minute</b>.
                    </p>
                <?php endif; ?>

                <form method="post" action="<?= u('/admin/security/geo/rebuild') ?>" style="margin-top:14px"
                      onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='⏳ Construction en cours (~1 min)…'">
                    <input type="hidden" name="csrf_token" value="<?= $h($cz) ?>">
                    <button class="ui-btn <?= $geoBase['existe'] ? '' : 'primary' ?>" type="submit">
                        <?= $geoBase['existe'] ? '🔄 Reconstruire la base' : '⬇️ Construire la base' ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Vérification -->
        <div class="ui-card">
            <div class="ui-card-head">🧪 Vérifier une adresse</div>
            <div class="ui-card-body">
                <p class="u-muted" style="margin:0 0 12px;line-height:1.6">
                    Éprouve une adresse contre la configuration <b>enregistrée</b>. À utiliser avant d'activer :
                    une liste blanche mal réglée ferme le site sans prévenir.
                </p>
                <form method="post" action="<?= u('/admin/security/geo/test') ?>" style="display:flex;gap:8px;flex-wrap:wrap">
                    <input type="hidden" name="csrf_token" value="<?= $h($cz) ?>">
                    <input class="form-control" name="ip" placeholder="ex. 8.8.8.8" required style="flex:1;min-width:150px">
                    <button class="ui-btn" type="submit">Vérifier</button>
                </form>

                <table class="ui-table" style="margin-top:14px">
                    <tr><td>Votre adresse</td><td><code><?= $h($geoIpAdmin ?? '') ?></code></td></tr>
                    <tr><td>Votre pays</td><td>
                        <?php if (!empty($geoPaysAdmin)): ?>
                            <b><?= $h(\Framework\Services\CountryFirewall::nomPays($geoPaysAdmin)) ?></b>
                            <span class="ui-badge"><?= $h($geoPaysAdmin) ?></span>
                        <?php else: ?>
                            <span class="u-muted">inconnu — vous êtes sur le réseau local</span>
                        <?php endif; ?>
                    </td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Configuration -->
    <div class="ui-card" style="margin-top:16px">
        <div class="ui-card-head" style="display:flex;align-items:center;justify-content:space-between">
            <span>⚙️ Configuration du filtrage</span>
            <?php if (!empty($geoActif)): ?>
                <span class="ui-badge green">Actif</span>
            <?php else: ?>
                <span class="ui-badge">Inactif</span>
            <?php endif; ?>
        </div>
        <div class="ui-card-body">
            <?php if (!$geoBase['existe']): ?>
                <p class="u-muted" style="margin:0">Construisez d'abord la base ci-dessus.</p>
            <?php else: ?>
            <form method="post" action="<?= u('/admin/security/geo') ?>">
                <input type="hidden" name="csrf_token" value="<?= $h($cz) ?>">

                <label class="geo-switch">
                    <span class="geo-switch__txt">
                        <b>🌍 Activer le filtrage géographique</b>
                        <small>Coupe ou remet la protection d'un seul geste. La sélection de pays est conservée à l'arrêt.</small>
                    </span>
                    <span class="set-sw"><input type="checkbox" name="geo_enabled" <?= !empty($geoSwitch) ? 'checked' : '' ?>><i></i></span>
                </label>

                <?php if (!empty($geoEmpechement)): ?>
                    <p class="geo-warn">⚠️ Activé, mais sans effet : <?= $h($geoEmpechement) ?></p>
                <?php endif; ?>

                <div class="geo-modes">
                    <?php foreach ([
                        'block' => ['⛔ Liste noire',  'Tout le monde entre, SAUF les pays cochés. Le réflexe habituel : on ferme la porte aux origines d\'où viennent les ennuis.'],
                        'allow' => ['✅ Liste blanche','Personne n\'entre, SAUF les pays cochés. Bien plus étanche si votre public est connu d\'avance.'],
                    ] as $val => [$titre, $aide]): ?>
                        <label class="geo-mode<?= $geoMode === $val ? ' is-on' : '' ?>">
                            <input type="radio" name="geo_mode" value="<?= $val ?>" <?= $geoMode === $val ? 'checked' : '' ?>>
                            <span><b><?= $titre ?></b><small><?= $h($aide) ?></small></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="geo-bar">
                    <input type="search" id="geoSearch" class="form-control" placeholder="🔎 Filtrer les pays…" style="max-width:260px">
                    <span class="u-muted" style="font-size:13px"><b id="geoCount"><?= count($geoPays) ?></b> pays sélectionné(s) sur <?= count($geoConnus) ?></span>
                    <span style="flex:1"></span>
                    <button class="ui-btn sm" type="button" id="geoNone">Tout décocher</button>
                </div>

                <div class="geo-list">
                    <?php foreach ($geoConnus as $code => $nom): ?>
                        <label class="geo-item" data-name="<?= $h(mb_strtolower($nom . ' ' . $code)) ?>">
                            <input type="checkbox" name="geo_countries[]" value="<?= $h($code) ?>"
                                   <?= in_array($code, $geoPays, true) ? 'checked' : '' ?>>
                            <span><?= $h($nom) ?> <em><?= $h($code) ?></em></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div style="margin-top:14px">
                    <button class="ui-btn primary" type="submit">💾 Enregistrer</button>
                </div>
            </form>
            <?php endif; ?>
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

/* Filtrage géographique */
/* L'interrupteur global, en tête : c'est la première chose qu'on cherche
   quand on veut couper la protection en urgence. */
.geo-switch{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 16px;border:1px solid var(--border);border-radius:10px;margin-bottom:14px;cursor:pointer}
.geo-switch:hover{border-color:var(--accent)}
.geo-switch__txt{display:flex;flex-direction:column;gap:3px}
.geo-switch__txt small{color:var(--text-soft);font-size:12px;line-height:1.5}
/* Interrupteur — mêmes dimensions et mêmes couleurs que ceux de Configuration,
   pour qu'un interrupteur ait la même apparence partout dans l'administration. */
.set-sw{position:relative;width:48px;height:27px;flex:0 0 48px}
.set-sw input{opacity:0;width:0;height:0}
.set-sw i{position:absolute;inset:0;background:var(--surface-3);border:1px solid var(--border-strong);border-radius:30px;transition:.2s}
.set-sw i::before{content:"";position:absolute;width:20px;height:20px;left:3px;top:2.5px;background:#fff;border-radius:50%;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.3)}
.set-sw input:checked + i{background:var(--accent);border-color:var(--accent)}
.set-sw input:checked + i::before{transform:translateX(21px)}
.geo-warn{margin:0 0 14px;padding:10px 12px;border-radius:8px;background:var(--amber-soft,#fef3c7);font-size:13.5px;line-height:1.55}
.geo-modes{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:10px;margin-bottom:16px}
.geo-mode{display:flex;gap:10px;align-items:flex-start;padding:12px 14px;border:1px solid var(--border);border-radius:10px;cursor:pointer}
.geo-mode:hover{border-color:var(--accent)}
.geo-mode.is-on{border-color:var(--accent);background:var(--surface-3)}
.geo-mode span{display:flex;flex-direction:column;gap:3px}
.geo-mode small{color:var(--text-soft);font-size:12px;line-height:1.5}
.geo-bar{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:10px}
/* La liste des 239 pays défile dans son propre cadre : sans hauteur bornée,
   le formulaire d'enregistrement se retrouverait à des écrans de distance. */
.geo-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:2px;max-height:330px;overflow-y:auto;padding:10px;border:1px solid var(--border);border-radius:10px}
.geo-item{display:flex;gap:8px;align-items:center;padding:5px 7px;border-radius:6px;cursor:pointer;font-size:13.5px}
.geo-item:hover{background:var(--surface-3)}
.geo-item em{font-style:normal;color:var(--text-faint,#8b949e);font-size:11.5px}
.geo-item[hidden]{display:none!important}
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

/* Sélection des pays : filtre et compteur.
   239 cases à cocher rendues d'un coup — chercher « Russie » à l'œil dans une
   grille de cette taille est vite pénible. */
(function () {
    var liste = document.querySelector('.geo-list');
    if (!liste) { return; }

    var champ    = document.getElementById('geoSearch');
    var compteur = document.getElementById('geoCount');
    var aucun    = document.getElementById('geoNone');
    var items    = [].slice.call(liste.querySelectorAll('.geo-item'));
    var cases    = [].slice.call(liste.querySelectorAll('input[type="checkbox"]'));

    function recompter() {
        if (compteur) { compteur.textContent = cases.filter(function (c) { return c.checked; }).length; }
    }

    if (champ) {
        champ.addEventListener('input', function () {
            var mot = champ.value.trim().toLowerCase();
            items.forEach(function (it) {
                it.hidden = mot !== '' && (it.getAttribute('data-name') || '').indexOf(mot) === -1;
            });
        });
    }

    if (aucun) {
        aucun.addEventListener('click', function () {
            cases.forEach(function (c) { c.checked = false; });
            recompter();
        });
    }

    liste.addEventListener('change', recompter);

    // Le cadre du mode suit le bouton radio choisi.
    var modes = [].slice.call(document.querySelectorAll('.geo-mode'));
    modes.forEach(function (m) {
        m.addEventListener('change', function () {
            modes.forEach(function (x) { x.classList.toggle('is-on', !!x.querySelector('input:checked')); });
        });
    });

    recompter();
})();
</script>

<?php admin_footer(); ?>
