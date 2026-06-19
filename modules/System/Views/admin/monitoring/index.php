<?php
/**
 * Vue Monitoring — Aegis Framework V4 (UI maison, sans dépendance externe)
 * Supervision : santé, système/PHP, base de données, stockage, sécurité, logs, modules.
 */
if (!defined('AEGIS_FRAMEWORK')) die('Access denied');

$pageTitle = $pageTitle ?? 'Monitoring';
admin_header($pageTitle);

function mon_ok(bool $ok): string {
    return $ok
        ? '<span class="ui-badge green">✅ OK</span>'
        : '<span class="ui-badge red">❌ Non</span>';
}
function mon_num($v): string { return htmlspecialchars((string)($v ?? 0)); }
function mon_text($v): string { return htmlspecialchars((string)($v ?? 'N/A')); }

$level     = $health['level'] ?? 'warning';
$levelTone = $level === 'good' ? 'green' : ($level === 'warning' ? 'amber' : 'red');
$levelText = $level === 'good' ? 'Sain' : ($level === 'warning' ? 'À surveiller' : 'Critique');
$levelEmoji = $level === 'good' ? '💚' : ($level === 'warning' ? '🟡' : '🔴');
$dbLogs = $logs['database'] ?? ['available' => false, 'total' => 0, 'by_level' => [], 'recent' => []];
$diskPct = (float)($filesInfo['disk_used_percent'] ?? 0);
?>

<div class="adm-page-head u-between" style="flex-wrap:wrap;gap:12px">
    <div>
        <div class="adm-breadcrumb"><a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><span>Monitoring</span></div>
        <h1>📡 Monitoring</h1>
        <p>Supervision consolidée : PHP, base de données, sécurité, stockage, logs et modules.</p>
    </div>
    <div class="ui-card" style="padding:14px 20px;text-align:center;min-width:140px">
        <div style="font-size:30px;font-weight:800;line-height:1;color:var(--<?= $levelTone === 'green' ? 'green' : ($levelTone === 'amber' ? 'amber' : 'red') ?>)"><?= (int)($health['score'] ?? 0) ?><span style="font-size:14px;color:var(--text-faint)">/100</span></div>
        <div class="ui-badge <?= $levelTone ?>" style="margin-top:6px"><?= $levelEmoji ?> <?= $levelText ?></div>
    </div>
</div>

<?php if (!empty($health['critical']) || !empty($health['warnings'])): ?>
    <div class="ui-grid cols-2" style="margin-bottom:18px">
        <?php if (!empty($health['critical'])): ?>
            <div class="ui-card" style="border-color:var(--red-soft)">
                <div class="ui-card-head" style="color:var(--red)">🔴 Alertes critiques</div>
                <div class="ui-card-body"><ul style="margin:0;padding-left:18px"><?php foreach ($health['critical'] as $i): ?><li><?= mon_text($i) ?></li><?php endforeach; ?></ul></div>
            </div>
        <?php endif; ?>
        <?php if (!empty($health['warnings'])): ?>
            <div class="ui-card" style="border-color:var(--amber-soft)">
                <div class="ui-card-head" style="color:var(--amber)">🟡 Points à surveiller</div>
                <div class="ui-card-body"><ul style="margin:0;padding-left:18px"><?php foreach ($health['warnings'] as $i): ?><li><?= mon_text($i) ?></li><?php endforeach; ?></ul></div>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="ui-card" style="border-color:var(--green-soft);margin-bottom:18px"><div class="ui-card-body" style="color:var(--green)">✅ <strong>Tout est propre :</strong> aucune anomalie majeure détectée.</div></div>
<?php endif; ?>

<!-- KPIs -->
<div class="ui-grid cols-4" style="margin-bottom:18px">
    <div class="ui-card tone-blue"><div class="ui-kpi"><div class="ui-kpi-icon">🐘</div><div><p class="ui-kpi-label">PHP</p><div class="ui-kpi-value" style="font-size:20px"><?= mon_text($phpVersion) ?></div><p class="ui-kpi-sub"><?= mon_text($runtime['php_sapi'] ?? '') ?></p></div></div></div>
    <div class="ui-card tone-accent"><div class="ui-kpi"><div class="ui-kpi-icon">🗄️</div><div><p class="ui-kpi-label">Base de données</p><div class="ui-kpi-value" style="font-size:20px"><?= mon_text($database['db_size'] ?? 'N/A') ?></div><p class="ui-kpi-sub"><?= mon_num($database['table_count'] ?? 0) ?> tables</p></div></div></div>
    <div class="ui-card tone-amber"><div class="ui-kpi"><div class="ui-kpi-icon">💾</div><div><p class="ui-kpi-label">Disque utilisé</p><div class="ui-kpi-value" style="font-size:20px"><?= mon_text($diskPct) ?>%</div><p class="ui-kpi-sub"><?= mon_text($filesInfo['disk_free'] ?? 'N/A') ?> libres</p></div></div></div>
    <div class="ui-card tone-green"><div class="ui-kpi"><div class="ui-kpi-icon">🧩</div><div><p class="ui-kpi-label">Modules</p><div class="ui-kpi-value" style="font-size:20px"><?= mon_num($modules['total_modules'] ?? 0) ?></div><p class="ui-kpi-sub"><?= mon_num($logs['log_count'] ?? 0) ?> fichiers de log</p></div></div></div>
</div>

<div class="ui-card mon-card">
    <div class="mon-tabs">
        <button class="mon-tab active" data-tab="overview">🌐 Vue globale</button>
        <button class="mon-tab" data-tab="system">⚙️ Système</button>
        <button class="mon-tab" data-tab="database">🗄️ Base de données</button>
        <button class="mon-tab" data-tab="storage">💾 Stockage</button>
        <button class="mon-tab" data-tab="security">🛡️ Sécurité</button>
        <button class="mon-tab" data-tab="logs">📋 Logs</button>
        <button class="mon-tab" data-tab="modules">🧩 Modules</button>
    </div>

    <!-- VUE GLOBALE -->
    <div id="tab-overview" class="mon-pane active">
        <div class="ui-grid cols-2">
            <article class="mon-panel">
                <h4>🖥️ Résumé serveur</h4>
                <table class="ui-table mon-kv">
                    <tr><td>Serveur</td><td><?= mon_text($serverType) ?></td></tr>
                    <tr><td>PHP</td><td><?= mon_text($phpVersion) ?> · <?= mon_text($runtime['php_sapi'] ?? '') ?></td></tr>
                    <tr><td>CMS</td><td><?= mon_text($cmsVersion) ?></td></tr>
                    <tr><td>OS</td><td><?= mon_text($os) ?></td></tr>
                    <tr><td>OPcache</td><td><?= mon_ok(!empty($performance['opcache_enabled'])) ?></td></tr>
                </table>
            </article>
            <article class="mon-panel">
                <h4>🔌 Extensions & prérequis</h4>
                <div class="mon-checks">
                    <?php foreach (($requirements['extensions'] ?? []) as $ext => $loaded): ?>
                        <div class="mon-check"><span><?= mon_text($ext) ?></span><?= mon_ok((bool)$loaded) ?></div>
                    <?php endforeach; ?>
                    <div class="mon-check"><span>mod_rewrite</span><?= mon_ok((bool)($requirements['mod_rewrite'] ?? false)) ?></div>
                    <div class="mon-check"><span>file_uploads</span><?= mon_ok((bool)($requirements['file_uploads'] ?? false)) ?></div>
                    <div class="mon-check"><span>log_errors</span><?= mon_ok((bool)($requirements['log_errors'] ?? false)) ?></div>
                </div>
            </article>
        </div>
    </div>

    <!-- SYSTÈME -->
    <div id="tab-system" class="mon-pane">
        <div class="ui-grid cols-3">
            <article class="mon-panel"><h4>🧰 Runtime</h4><table class="ui-table mon-kv">
                <tr><td>Hostname</td><td><?= mon_text($hardware['hostname'] ?? '') ?></td></tr>
                <tr><td>Architecture</td><td><?= mon_text($hardware['architecture'] ?? '') ?></td></tr>
                <tr><td>Serveur web</td><td><?= mon_text($runtime['server_software'] ?? '') ?></td></tr>
                <tr><td>php.ini</td><td class="mon-path"><?= mon_text($runtime['loaded_ini'] ?? '') ?></td></tr>
                <tr><td>Timezone</td><td><?= mon_text($runtime['timezone'] ?? '') ?></td></tr>
            </table></article>
            <article class="mon-panel"><h4>📐 Limites PHP</h4><table class="ui-table mon-kv">
                <tr><td>Upload max</td><td><?= mon_text($requirements['upload_max_filesize'] ?? '') ?></td></tr>
                <tr><td>POST max</td><td><?= mon_text($requirements['post_max_size'] ?? '') ?></td></tr>
                <tr><td>Memory limit</td><td><?= mon_text($requirements['memory_limit'] ?? '') ?></td></tr>
                <tr><td>Exécution max</td><td><?= mon_text($requirements['max_execution_time'] ?? '') ?>s</td></tr>
                <tr><td>Input vars</td><td><?= mon_text($requirements['max_input_vars'] ?? '') ?></td></tr>
            </table></article>
            <article class="mon-panel"><h4>⚡ Performance</h4><table class="ui-table mon-kv">
                <tr><td>Mémoire actuelle</td><td><?= mon_text($performance['memory_current'] ?? '') ?></td></tr>
                <tr><td>Pic mémoire</td><td><?= mon_text($performance['memory_peak'] ?? '') ?></td></tr>
                <tr><td>OPcache hit rate</td><td><?= mon_text($performance['opcache_hit_rate'] ?? 'N/A') ?></td></tr>
                <tr><td>Scripts OPcache</td><td><?= mon_text($performance['opcache_cached_scripts'] ?? 'N/A') ?></td></tr>
                <tr><td>Realpath cache</td><td><?= mon_text($performance['realpath_cache_size'] ?? '') ?></td></tr>
            </table></article>
        </div>
    </div>

    <!-- BASE DE DONNÉES -->
    <div id="tab-database" class="mon-pane">
        <?php if ($database && empty($database['error'])): ?>
            <div class="ui-grid cols-2">
                <article class="mon-panel"><h4>🗄️ MariaDB / MySQL</h4><table class="ui-table mon-kv">
                    <tr><td>Base</td><td><?= mon_text($database['db_name'] ?? '') ?></td></tr>
                    <tr><td>Taille</td><td><?= mon_text($database['db_size'] ?? '') ?></td></tr>
                    <tr><td>Tables</td><td><?= mon_num($database['table_count'] ?? 0) ?></td></tr>
                    <tr><td>Version</td><td><?= mon_text($database['mysql_version'] ?? '') ?></td></tr>
                    <tr><td>Uptime</td><td><?= mon_text($database['uptime'] ?? '') ?></td></tr>
                    <tr><td>Connexions</td><td><?= mon_text($database['threads_connected'] ?? '') ?> / <?= mon_text($database['max_connections'] ?? '') ?></td></tr>
                </table></article>
                <article class="mon-panel"><h4>📊 Tables les plus volumineuses</h4><div class="mon-scroll"><table class="ui-table">
                    <thead><tr><th>Table</th><th>Lignes ≈</th><th>Taille</th></tr></thead><tbody>
                    <?php foreach (($database['tables'] ?? []) as $t): ?>
                        <tr><td><?= mon_text($t['table_name'] ?? '') ?></td><td><?= mon_num($t['rows_count'] ?? 0) ?></td><td><?= mon_text($t['size_mb'] ?? 0) ?> Mo</td></tr>
                    <?php endforeach; ?>
                    </tbody></table></div></article>
            </div>
        <?php else: ?>
            <div class="ui-empty"><div class="ui-empty-icon">🗄️</div><?= mon_text($database['error'] ?? 'Base de données non disponible') ?></div>
        <?php endif; ?>
    </div>

    <!-- STOCKAGE -->
    <div id="tab-storage" class="mon-pane">
        <div class="ui-grid cols-3">
            <article class="mon-panel"><h4>💾 Espace disque</h4>
                <div class="ui-progress" style="margin-bottom:12px"><span style="width:<?= min(100, $diskPct) ?>%"></span></div>
                <table class="ui-table mon-kv">
                    <tr><td>Total</td><td><?= mon_text($filesInfo['disk_total'] ?? '') ?></td></tr>
                    <tr><td>Libre</td><td><?= mon_text($filesInfo['disk_free'] ?? '') ?></td></tr>
                    <tr><td>Utilisé</td><td><?= mon_text($diskPct) ?>%</td></tr>
                    <tr><td>Projet</td><td><?= mon_text($filesInfo['total_size'] ?? '') ?></td></tr>
                    <tr><td>Fichiers</td><td><?= mon_text($filesInfo['file_count'] ?? '') ?></td></tr>
                </table>
            </article>
            <article class="mon-panel"><h4>✍️ Écritures critiques</h4><table class="ui-table mon-kv"><?php foreach (($filesInfo['writable'] ?? []) as $label => $w): ?><tr><td><?= mon_text($label) ?></td><td><?= mon_ok((bool)$w) ?></td></tr><?php endforeach; ?></table></article>
            <article class="mon-panel"><h4>📁 Répartition</h4><div class="mon-scroll"><table class="ui-table"><thead><tr><th>Zone</th><th>Taille</th><th>Chemin</th></tr></thead><tbody><?php foreach (($filesInfo['breakdown'] ?? []) as $row): ?><tr><td><?= mon_text($row['label'] ?? '') ?></td><td><?= mon_text($row['size'] ?? '') ?></td><td class="mon-path"><?= mon_text($row['path'] ?? '') ?></td></tr><?php endforeach; ?></tbody></table></div></article>
        </div>
    </div>

    <!-- SÉCURITÉ -->
    <div id="tab-security" class="mon-pane">
        <div class="ui-grid cols-3">
            <article class="mon-panel"><h4>🌐 En-têtes HTTP</h4><table class="ui-table mon-kv"><?php foreach (($security['security_headers'] ?? []) as $hd => $present): ?><tr><td><?= mon_text($hd) ?></td><td><?= mon_ok((bool)$present) ?></td></tr><?php endforeach; ?></table></article>
            <article class="mon-panel"><h4>🔐 Sessions & PHP</h4><table class="ui-table mon-kv">
                <tr><td>HTTPS</td><td><?= mon_ok((bool)($security['https_enabled'] ?? false)) ?></td></tr>
                <tr><td>display_errors off</td><td><?= mon_ok(empty($security['display_errors'])) ?></td></tr>
                <tr><td>expose_php off</td><td><?= mon_ok(empty($security['expose_php'])) ?></td></tr>
                <tr><td>Cookie HttpOnly</td><td><?= mon_ok((bool)($security['session_cookie_httponly'] ?? false)) ?></td></tr>
                <tr><td>Cookie Secure</td><td><?= mon_ok((bool)($security['session_cookie_secure'] ?? false)) ?></td></tr>
                <tr><td>SameSite</td><td><?= mon_text($security['session_cookie_samesite'] ?? '') ?></td></tr>
            </table></article>
            <article class="mon-panel"><h4>🔑 Permissions des fichiers critiques</h4><table class="ui-table mon-kv"><?php foreach (($security['file_permissions'] ?? []) as $file => $info): $perm = is_array($info) ? ($info['perms'] ?? '') : $info; $writable = is_array($info) ? !empty($info['writable']) : null; ?><tr><td class="mon-path"><?= mon_text($file) ?></td><td><span class="ui-badge"><?= mon_text($perm) ?></span> <?php if ($writable !== null): ?><?= $writable ? '<span class="ui-badge amber">✏️ inscriptible</span>' : '<span class="ui-badge green">🔒 lecture seule</span>' ?><?php endif; ?></td></tr><?php endforeach; ?><?php if (empty($security['file_permissions'])): ?><tr><td colspan="2" class="u-muted">Aucun fichier critique détecté.</td></tr><?php endif; ?></table></article>
            <article class="mon-panel" style="grid-column:1/-1"><h4>⚠️ Fonctions système PHP actives</h4><p class="u-muted" style="margin-top:-6px">Signalées pour audit (SSH, scripts, installations) — pas forcément à désactiver.</p><div class="mon-tags"><?php foreach (($security['enabled_dangerous_functions'] ?? []) as $fn): ?><span class="ui-badge"><?= mon_text($fn) ?></span><?php endforeach; ?><?php if (empty($security['enabled_dangerous_functions'])): ?><span class="ui-badge green">✅ Aucune</span><?php endif; ?></div></article>
        </div>
    </div>

    <!-- LOGS -->
    <div id="tab-logs" class="mon-pane">
        <div class="ui-grid cols-2">
            <article class="mon-panel"><h4>📄 Résumé logs fichiers</h4><table class="ui-table mon-kv">
                <tr><td>Fichiers</td><td><?= mon_num($logs['log_count'] ?? 0) ?></td></tr>
                <tr><td>Taille totale</td><td><?= mon_text($logs['total_size'] ?? '0 o') ?></td></tr>
                <tr><td>Erreurs/critique</td><td><?= mon_num($logs['critical_files'] ?? 0) ?></td></tr>
                <tr><td>Chemin</td><td class="mon-path"><?= mon_text($logs['logs_path'] ?? '') ?></td></tr>
                <tr><td>PHP error_log</td><td class="mon-path"><?= mon_text($logs['php_error_log'] ?? '') ?></td></tr>
            </table></article>
            <article class="mon-panel"><h4>🗃️ Résumé logs SQL</h4><table class="ui-table mon-kv">
                <tr><td>Total en base</td><td><?= mon_num($dbLogs['total'] ?? 0) ?></td></tr>
                <tr><td>Table</td><td><?= !empty($dbLogs['available']) ? '<span class="ui-badge green">✅ Disponible</span>' : '<span class="ui-badge amber">⚠️ Indisponible</span>' ?></td></tr>
                <?php foreach (($dbLogs['by_level'] ?? []) as $lv): ?><tr><td><?= mon_text($lv['level'] ?? '') ?></td><td><?= mon_num($lv['total'] ?? 0) ?></td></tr><?php endforeach; ?>
            </table><?php if (!empty($dbLogs['available']) && ($dbLogs['total'] ?? 0) > 0): ?><button type="button" class="ui-btn danger sm u-mt" onclick="purgeDbLogs()">🗑️ Vider la table logs</button><?php endif; ?></article>
            <article class="mon-panel" style="grid-column:1/-1"><h4>📁 Fichiers de logs</h4><div class="mon-scroll"><table class="ui-table"><thead><tr><th>Fichier</th><th>Taille</th><th>Modifié</th><th>Actions</th></tr></thead><tbody><?php foreach (($logs['log_files'] ?? []) as $log): ?><tr><td><?= mon_text($log['name'] ?? '') ?></td><td><?= mon_text($log['size'] ?? '') ?></td><td><?= mon_text($log['modified'] ?? '') ?></td><td class="u-nowrap"><button class="ui-btn sm primary" onclick="viewLog('<?= htmlspecialchars($log['name'] ?? '', ENT_QUOTES) ?>')">👁️ Voir</button> <button class="ui-btn sm danger" onclick="deleteLog('<?= htmlspecialchars($log['name'] ?? '', ENT_QUOTES) ?>')">🗑️</button></td></tr><?php endforeach; ?><?php if (empty($logs['log_files'])): ?><tr><td colspan="4" class="u-muted">Aucun fichier de log.</td></tr><?php endif; ?></tbody></table></div></article>
            <article class="mon-panel" style="grid-column:1/-1"><h4>🧾 Logs SQL récents</h4><p class="u-muted" style="margin-top:-6px">Limité aux 100 dernières entrées.</p><div class="mon-scroll"><table class="ui-table"><thead><tr><th>ID</th><th>Niveau</th><th>Message</th><th>URL</th><th>IP</th><th>Date</th><th></th></tr></thead><tbody><?php foreach (($dbLogs['recent'] ?? []) as $log): $lvl = $log['level'] ?? ''; $tone = in_array($lvl, ['ERROR','CRITICAL','SECURITY'], true) ? 'red' : ($lvl === 'WARNING' ? 'amber' : 'green'); ?><tr><td><?= mon_num($log['id'] ?? 0) ?></td><td><span class="ui-badge <?= $tone ?>"><?= mon_text($lvl) ?></span></td><td><?= mon_text($log['message'] ?? '') ?></td><td class="mon-path"><?= mon_text($log['method'] ?? '') ?> <?= mon_text($log['url'] ?? '') ?></td><td><?= mon_text($log['ip_address'] ?? '') ?></td><td><?= mon_text($log['created_at'] ?? '') ?></td><td><button class="ui-btn sm danger" onclick="deleteDbLog(<?= (int)($log['id'] ?? 0) ?>)">🗑️</button></td></tr><?php endforeach; ?><?php if (empty($dbLogs['recent'])): ?><tr><td colspan="7" class="u-muted">Aucun log SQL.</td></tr><?php endif; ?></tbody></table></div></article>
            <article class="mon-panel" style="grid-column:1/-1"><h4>🐞 Dernières erreurs PHP</h4><pre class="mon-pre"><?= mon_text(implode("\n", $logs['last_errors'] ?? [])) ?: 'Aucune erreur récente.' ?></pre></article>
        </div>
    </div>

    <!-- MODULES -->
    <div id="tab-modules" class="mon-pane">
        <article class="mon-panel"><h4>🧩 Modules installés</h4><div class="mon-scroll"><table class="ui-table"><thead><tr><th>Module</th><th>Version</th><th>Statut</th><th>Chemin</th></tr></thead><tbody><?php foreach (($modules['modules'] ?? []) as $m): ?><tr><td><?= mon_text($m['name'] ?? '') ?></td><td><?= mon_text($m['version'] ?? '') ?></td><td><?= !empty($m['enabled']) ? '<span class="ui-badge green">✅ Actif</span>' : '<span class="ui-badge amber">⏸️ Inactif</span>' ?></td><td class="mon-path"><?= mon_text($m['path'] ?? '') ?></td></tr><?php endforeach; ?><?php if (empty($modules['modules'])): ?><tr><td colspan="4" class="u-muted">Aucun module trouvé.</td></tr><?php endif; ?></tbody></table></div></article>
    </div>
</div>

<!-- Modale d'affichage de log -->
<div id="logModal" class="mon-modal">
    <div class="mon-modal-box">
        <div class="mon-modal-head"><h3 id="logFileName">📄 Log</h3><button class="adm-icon-btn" onclick="closeLogModal()">✕</button></div>
        <div class="mon-modal-body"><pre id="logContent" class="mon-pre" style="max-height:62vh">Chargement…</pre></div>
        <div class="mon-modal-foot"><button class="ui-btn" onclick="closeLogModal()">Fermer</button></div>
    </div>
</div>

<style>
/* Onglets & panneaux — thémés via les tokens ui.css (dark/light auto) */
.mon-card { padding: 0; overflow: hidden; }
.mon-tabs { display: flex; gap: 0; overflow-x: auto; border-bottom: 1px solid var(--border); }
.mon-tab { padding: 14px 18px; border: 0; background: transparent; color: var(--text-soft); font-weight: 600; white-space: nowrap; cursor: pointer; font-family: inherit; font-size: 13.5px; }
.mon-tab:hover { background: var(--surface-2); color: var(--text); }
.mon-tab.active { color: var(--accent); box-shadow: inset 0 -3px 0 var(--accent); }
.mon-pane { display: none; padding: 18px; }
.mon-pane.active { display: block; }
.mon-panel { background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px; min-width: 0; }
.mon-panel h4 { margin: 0 0 14px; font-size: 15px; }
.mon-kv td:first-child { color: var(--text-soft); font-weight: 600; width: 42%; }
.mon-kv td { padding: 8px 6px; }
.mon-checks { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 8px; }
.mon-check { display: flex; justify-content: space-between; gap: 8px; align-items: center; padding: 8px 10px; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--surface); }
.mon-path { font-family: Consolas, monospace; font-size: 12px; overflow-wrap: anywhere; color: var(--text-soft); }
.mon-scroll { overflow-x: auto; }
.mon-tags { display: flex; flex-wrap: wrap; gap: 8px; }
.mon-pre { margin: 0; background: #0b1120; color: #e5e7eb; border-radius: var(--radius-sm); padding: 14px; max-height: 280px; overflow: auto; font-size: 12px; line-height: 1.55; white-space: pre-wrap; }
.mon-modal { display: none; position: fixed; inset: 0; z-index: 1050; background: rgba(15,23,42,.6); padding: 40px 16px; overflow-y: auto; }
.mon-modal.show { display: block; }
.mon-modal-box { width: min(1000px, 94vw); margin: 0 auto; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); overflow: hidden; }
.mon-modal-head, .mon-modal-foot { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-bottom: 1px solid var(--border); }
.mon-modal-foot { justify-content: flex-end; border-bottom: 0; border-top: 1px solid var(--border); }
.mon-modal-head h3 { margin: 0; font-size: 16px; }
.mon-modal-body { padding: 18px; }
</style>

<script>
const MONITORING_CSRF = '<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES) ?>';

document.querySelectorAll('.mon-tab').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.mon-tab').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.mon-pane').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        var pane = document.getElementById('tab-' + btn.dataset.tab);
        if (pane) pane.classList.add('active');
    });
});

function post(url, body) {
    return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(Object.assign({ csrf_token: MONITORING_CSRF }, body)) }).then(r => r.json());
}
window.viewLog = function (filename) {
    var modal = document.getElementById('logModal');
    document.getElementById('logFileName').textContent = '📄 ' + filename;
    document.getElementById('logContent').textContent = 'Chargement…';
    modal.classList.add('show');
    fetch('<?= u('/admin/monitoring/view-log') ?>?file=' + encodeURIComponent(filename))
        .then(r => r.text()).then(d => { document.getElementById('logContent').textContent = d; })
        .catch(e => { document.getElementById('logContent').textContent = 'Erreur : ' + e; });
};
window.deleteLog = function (filename) {
    if (!confirm('Supprimer ce fichier de log : ' + filename + ' ?')) return;
    post('<?= u('/admin/monitoring/delete-log') ?>', { file: filename }).then(d => { alert((d.success ? '✅ ' : '❌ ') + d.message); if (d.success) location.reload(); });
};
window.deleteDbLog = function (id) {
    if (!id || !confirm('Supprimer ce log SQL #' + id + ' ?')) return;
    post('<?= u('/admin/monitoring/delete-db-log') ?>', { log_id: String(id) }).then(d => { alert((d.success ? '✅ ' : '❌ ') + d.message); if (d.success) location.reload(); });
};
window.purgeDbLogs = function () {
    if (!confirm('Vider toute la table logs ? Cette action est irréversible.')) return;
    post('<?= u('/admin/monitoring/purge-db-logs') ?>', { confirmation: 'PURGE_SQL_LOGS' }).then(d => { alert((d.success ? '✅ ' : '❌ ') + d.message); if (d.success) location.reload(); });
};
window.closeLogModal = function () { document.getElementById('logModal').classList.remove('show'); };
document.addEventListener('keydown', e => { if (e.key === 'Escape') window.closeLogModal(); });
document.getElementById('logModal')?.addEventListener('click', e => { if (e.target.id === 'logModal') window.closeLogModal(); });
</script>

<?php admin_footer(); ?>
