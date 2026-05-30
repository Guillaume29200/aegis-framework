<?php
/**
 * Debug toolbar - variables disponibles : $debugData.
 */
$summary = $debugData['summary'];
$queries = $debugData['queries'];
$request = $debugData['request'];
$session = $debugData['session'];
$logs = $debugData['logs'];
$security = $debugData['security'];
$files = $debugData['files'];
$marks = $debugData['marks'];
$config = $debugData['config'];

$fmtBytes = static function (int|float $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return number_format((float)$bytes, $i === 0 ? 0 : 2) . ' ' . $units[$i];
};

$json = static fn($value): string => htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
?>

<style>
#gsh-debugbar{position:fixed;left:0;right:0;bottom:0;z-index:2147483000;background:#111827;color:#e5e7eb;font-family:Consolas,Monaco,monospace;font-size:12px;border-top:3px solid #22c55e;box-shadow:0 -10px 30px rgba(0,0,0,.35)}
#gsh-debugbar *{box-sizing:border-box}
#gsh-debugbar.gsh-debug-min{height:38px;overflow:hidden}
#gsh-debugbar .gsh-debug-top{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:9px 14px;background:#0b1120;border-bottom:1px solid #263244}
#gsh-debugbar .gsh-debug-brand{font-weight:700;color:#86efac;white-space:nowrap}
#gsh-debugbar .gsh-debug-stats{display:flex;align-items:center;gap:14px;flex-wrap:wrap;min-width:0}
#gsh-debugbar .gsh-debug-pill{display:inline-flex;align-items:center;gap:5px;padding:3px 8px;border:1px solid #334155;border-radius:999px;background:#172033;color:#cbd5e1}
#gsh-debugbar .warn{color:#fbbf24}.err{color:#f87171}.ok{color:#86efac}
#gsh-debugbar button{font:inherit}
#gsh-debugbar .gsh-debug-actions{display:flex;gap:8px}
#gsh-debugbar .gsh-debug-btn{border:1px solid #334155;background:#1f2937;color:#e5e7eb;border-radius:6px;padding:4px 9px;cursor:pointer}
#gsh-debugbar .gsh-debug-btn:hover{background:#374151}
#gsh-debugbar .gsh-debug-tabs{display:flex;gap:0;background:#0f172a;border-bottom:1px solid #263244;overflow-x:auto}
#gsh-debugbar .gsh-debug-tab{padding:10px 14px;border:0;border-right:1px solid #263244;background:transparent;color:#94a3b8;cursor:pointer;white-space:nowrap}
#gsh-debugbar .gsh-debug-tab.active{color:#86efac;background:#172033}
#gsh-debugbar .gsh-debug-panel{display:none;max-height:430px;overflow:auto;padding:14px}
#gsh-debugbar .gsh-debug-panel.active{display:block}
#gsh-debugbar table{width:100%;border-collapse:collapse}
#gsh-debugbar th,#gsh-debugbar td{padding:7px 8px;border-bottom:1px solid #263244;text-align:left;vertical-align:top}
#gsh-debugbar th{color:#93c5fd;background:#0f172a;position:sticky;top:0}
#gsh-debugbar code,#gsh-debugbar pre{font-family:Consolas,Monaco,monospace}
#gsh-debugbar pre{margin:0;white-space:pre-wrap;word-break:break-word;color:#d1d5db}
#gsh-debugbar .sql{color:#bfdbfe}
#gsh-debugbar .box{background:#0b1120;border:1px solid #263244;border-radius:8px;padding:10px;margin-bottom:10px}
#gsh-debugbar .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:10px}
#gsh-debugbar .muted{color:#94a3b8}
#gsh-debugbar .small{font-size:11px}
body{padding-bottom:48px}
</style>

<div id="gsh-debugbar">
    <div class="gsh-debug-top">
        <div class="gsh-debug-brand">Debug Mode</div>
        <div class="gsh-debug-stats">
            <span class="gsh-debug-pill">HTTP <strong><?= (int)$summary['response_code'] ?></strong></span>
            <span class="gsh-debug-pill">Time <strong><?= number_format($summary['load_time'], 3) ?>s</strong></span>
            <span class="gsh-debug-pill">Memory <strong><?= $fmtBytes($summary['memory_usage_bytes']) ?></strong> peak <?= $fmtBytes($summary['memory_peak_bytes']) ?></span>
            <span class="gsh-debug-pill">SQL <strong><?= (int)$queries['count'] ?></strong> / <?= number_format((float)$queries['total_time'], 3) ?>s</span>
            <span class="gsh-debug-pill">Files <strong><?= (int)$summary['included_files_count'] ?></strong></span>
            <span class="gsh-debug-pill"><?= htmlspecialchars($request['method']) ?> <?= htmlspecialchars($request['uri']) ?></span>
        </div>
        <div class="gsh-debug-actions">
            <button type="button" class="gsh-debug-btn" data-debug-toggle>Min</button>
            <button type="button" class="gsh-debug-btn" data-debug-close>Close</button>
        </div>
    </div>

    <div class="gsh-debug-tabs">
        <button type="button" class="gsh-debug-tab active" data-debug-tab="overview">Overview</button>
        <button type="button" class="gsh-debug-tab" data-debug-tab="request">Request</button>
        <button type="button" class="gsh-debug-tab" data-debug-tab="sql">SQL <?= (int)$queries['count'] ?></button>
        <button type="button" class="gsh-debug-tab" data-debug-tab="session">Session</button>
        <button type="button" class="gsh-debug-tab" data-debug-tab="security">Security</button>
        <button type="button" class="gsh-debug-tab" data-debug-tab="timeline">Timeline</button>
        <button type="button" class="gsh-debug-tab" data-debug-tab="files">Files</button>
        <button type="button" class="gsh-debug-tab" data-debug-tab="logs">Logs</button>
    </div>

    <div class="gsh-debug-panel active" id="gsh-debug-overview">
        <div class="grid">
            <div class="box"><strong>PHP</strong><br><?= htmlspecialchars($summary['php_version']) ?> / <?= htmlspecialchars($summary['sapi']) ?></div>
            <div class="box"><strong>Environment</strong><br><?= htmlspecialchars($config['environment']) ?> · TZ <?= htmlspecialchars($config['timezone']) ?></div>
            <div class="box"><strong>TurboNav</strong><br><?= $config['turbonav'] ? 'enabled' : 'disabled' ?></div>
            <div class="box"><strong>OPcache</strong><br><?= $config['opcache'] ? 'enabled' : 'disabled' ?></div>
        </div>
    </div>

    <div class="gsh-debug-panel" id="gsh-debug-request">
        <div class="grid">
            <div class="box"><strong>GET</strong><pre><?= $json($request['get']) ?></pre></div>
            <div class="box"><strong>POST</strong><pre><?= $json($request['post']) ?></pre></div>
            <div class="box"><strong>Cookies</strong><pre><?= $json($request['cookies']) ?></pre></div>
            <div class="box"><strong>Server</strong><pre><?= $json($request['server']) ?></pre></div>
            <div class="box"><strong>Headers</strong><pre><?= $json($request['headers']) ?></pre></div>
        </div>
    </div>

    <div class="gsh-debug-panel" id="gsh-debug-sql">
        <?php if (empty($queries['items'])): ?>
            <div class="muted">Aucune requete SQL capturee.</div>
        <?php else: ?>
            <table>
                <thead><tr><th>#</th><th>Time</th><th>SQL</th><th>Params</th></tr></thead>
                <tbody>
                <?php foreach ($queries['items'] as $i => $query): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="<?= !empty($query['slow']) ? 'warn' : '' ?>"><?= number_format((float)$query['time'], 4) ?>s</td>
                        <td class="sql"><?= htmlspecialchars($query['sql']) ?></td>
                        <td><pre><?= $json($query['params']) ?></pre></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="gsh-debug-panel" id="gsh-debug-session">
        <div class="box"><strong>Name:</strong> <?= htmlspecialchars($session['name']) ?> · <strong>ID:</strong> <?= htmlspecialchars((string)$session['id']) ?></div>
        <pre><?= $json($session['data']) ?></pre>
    </div>

    <div class="gsh-debug-panel" id="gsh-debug-security">
        <table>
            <thead><tr><th>Check</th><th>Status</th><th>Message</th></tr></thead>
            <tbody>
            <?php foreach ($security as $check): ?>
                <tr>
                    <td><?= htmlspecialchars($check['check']) ?></td>
                    <td class="<?= $check['passed'] ? 'ok' : 'warn' ?>"><?= $check['passed'] ? 'OK' : 'WARN' ?></td>
                    <td><?= htmlspecialchars($check['message'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="gsh-debug-panel" id="gsh-debug-timeline">
        <table>
            <thead><tr><th>Mark</th><th>Time</th><th>Memory</th></tr></thead>
            <tbody>
            <?php foreach ($marks as $mark): ?>
                <tr>
                    <td><?= htmlspecialchars($mark['name']) ?></td>
                    <td><?= number_format((float)$mark['time'], 4) ?>s</td>
                    <td><?= $fmtBytes($mark['memory']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="gsh-debug-panel" id="gsh-debug-files">
        <table>
            <thead><tr><th>File</th><th>Size</th></tr></thead>
            <tbody>
            <?php foreach ($files as $file): ?>
                <tr><td><?= htmlspecialchars($file['path']) ?></td><td><?= $fmtBytes($file['size']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="gsh-debug-panel" id="gsh-debug-logs">
        <?php if (empty($logs)): ?>
            <div class="muted">Aucun log runtime capture.</div>
        <?php else: ?>
            <table>
                <thead><tr><th>Time</th><th>Level</th><th>Message</th><th>Context</th></tr></thead>
                <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= number_format((float)$log['time'], 4) ?>s</td>
                        <td><?= htmlspecialchars($log['level']) ?></td>
                        <td><?= htmlspecialchars($log['message']) ?></td>
                        <td><pre><?= $json($log['context']) ?></pre></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
(function(){
    if (window.GSH_DEBUGBAR_BOOTED) {
        if (typeof window.GSH_DEBUGBAR_INIT === 'function') {
            window.GSH_DEBUGBAR_INIT(document.getElementById('gsh-debugbar'));
        }
        return;
    }

    window.GSH_DEBUGBAR_BOOTED = true;
    window.GSH_DEBUGBAR_CLOSED = window.GSH_DEBUGBAR_CLOSED || false;
    window.GSH_DEBUGBAR_REFRESHING = false;

    function activateTab(bar, tab) {
        if (!bar || !tab) return;

        bar.querySelectorAll('.gsh-debug-tab').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-debug-tab') === tab);
        });

        bar.querySelectorAll('.gsh-debug-panel').forEach(panel => {
            panel.classList.toggle('active', panel.id === 'gsh-debug-' + tab);
        });
    }

    window.GSH_DEBUGBAR_INIT = function(bar, preferredTab) {
        if (!bar || bar.dataset.debugReady === '1' || window.GSH_DEBUGBAR_CLOSED) return;

        bar.dataset.debugReady = '1';
        document.body.style.paddingBottom = '48px';

        const activeTab = preferredTab || bar.querySelector('.gsh-debug-tab.active')?.getAttribute('data-debug-tab') || 'overview';
        activateTab(bar, activeTab);

        bar.querySelectorAll('[data-debug-tab]').forEach(btn => {
            btn.addEventListener('click', () => {
                const tab = btn.getAttribute('data-debug-tab');
                activateTab(bar, tab);
                bar.classList.remove('gsh-debug-min');
            });
        });

        const toggle = bar.querySelector('[data-debug-toggle]');
        if (toggle) {
            toggle.addEventListener('click', () => bar.classList.toggle('gsh-debug-min'));
        }

        const close = bar.querySelector('[data-debug-close]');
        if (close) {
            close.addEventListener('click', () => {
                window.GSH_DEBUGBAR_CLOSED = true;
                bar.remove();
                document.body.style.paddingBottom = '';
            });
        }
    };

    async function refreshAfterTurboNav() {
        if (window.GSH_DEBUGBAR_CLOSED || window.GSH_DEBUGBAR_REFRESHING) return;

        const currentBar = document.getElementById('gsh-debugbar');
        if (!currentBar) return;

        window.GSH_DEBUGBAR_REFRESHING = true;
        const wasMinimized = currentBar.classList.contains('gsh-debug-min');
        const activeTab = currentBar.querySelector('.gsh-debug-tab.active')?.getAttribute('data-debug-tab') || 'overview';

        try {
            const response = await fetch(window.location.href, {
                cache: 'no-store',
                credentials: 'same-origin',
                headers: {
                    'X-DebugBar-Refresh': '1',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) return;

            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const freshBar = doc.getElementById('gsh-debugbar');
            if (!freshBar) return;

            if (wasMinimized) {
                freshBar.classList.add('gsh-debug-min');
            }

            currentBar.replaceWith(freshBar);
            window.GSH_DEBUGBAR_INIT(freshBar, activeTab);
        } catch (error) {
            console.warn('[DebugBar] Refresh TurboNav impossible', error);
        } finally {
            window.GSH_DEBUGBAR_REFRESHING = false;
        }
    }

    document.addEventListener('turbonav:after-swap', refreshAfterTurboNav);
    window.GSH_DEBUGBAR_INIT(document.getElementById('gsh-debugbar'));
})();
</script>
