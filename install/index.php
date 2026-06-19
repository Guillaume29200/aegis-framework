<?php
declare(strict_types=1);

/**
 * Installeur Aegis Framework V4 — assistant multi-étapes.
 * Autonome (ne charge pas le CMS). Accès : /install/
 */
session_start();
require __DIR__ . '/Installer.php';
require __DIR__ . '/InstallController.php';

$installer  = new Installer(dirname(__DIR__));
$controller = new InstallController($installer);
$csrfToken  = $controller->csrfToken();
$action     = $_GET['action'] ?? null;

// ── Actions AJAX (JSON) — entièrement déléguées au contrôleur sécurisé ──
// (garde « déjà installé » + CSRF + validation centralisés dans InstallController)
if ($action !== null) {
    $controller->handle((string) $action);
    exit; // par sécurité (handle() termine déjà via exit)
}

// ────────────────────────────── Rendu des étapes ─────────────────────────────
$steps = [
    1 => ['icon' => '👋', 'title' => 'Bienvenue'],
    2 => ['icon' => '✅', 'title' => 'Prérequis'],
    3 => ['icon' => '🗄️', 'title' => 'Base de données'],
    4 => ['icon' => '👤', 'title' => 'Administrateur'],
    5 => ['icon' => '🚀', 'title' => 'Installation'],
];
$step = (int) ($_GET['step'] ?? 1);
if ($step < 1 || $step > 5) $step = 1;
$installed = $installer->isInstalled();
$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Installation — Aegis Framework V4</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../framework/assets/css/admin/ui.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #eef2ff, #f4f6fb); }
        .ins-wrap { display: grid; grid-template-columns: 290px 1fr; min-height: 100vh; }
        .ins-aside { background: #0b1120; color: #e6ebf5; padding: 32px 26px; display: flex; flex-direction: column; }
        .ins-brand { display: flex; align-items: center; gap: 12px; font-weight: 700; font-size: 18px; margin-bottom: 36px; }
        .ins-brand .logo { width: 40px; height: 40px; border-radius: 11px; display: grid; place-items: center; background: linear-gradient(135deg, #6366f1, #4f46e5); font-size: 22px; }
        .ins-steps { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 6px; }
        .ins-step { display: flex; align-items: center; gap: 14px; padding: 12px 14px; border-radius: 12px; color: #9aa7c2; }
        .ins-step .n { width: 30px; height: 30px; border-radius: 50%; display: grid; place-items: center; background: #1b2335; font-size: 14px; font-weight: 700; flex-shrink: 0; }
        .ins-step.active { background: rgba(99,102,241,.16); color: #fff; }
        .ins-step.active .n { background: #6366f1; color: #fff; }
        .ins-step.done { color: #cbd5e1; }
        .ins-step.done .n { background: #10b981; color: #fff; }
        .ins-aside-foot { margin-top: auto; font-size: 12px; color: #6b7895; }
        .ins-main { padding: 48px 56px; overflow-y: auto; }
        .ins-card { max-width: 720px; }
        .ins-h { font-size: 28px; font-weight: 700; letter-spacing: -.5px; margin: 0 0 6px; }
        .ins-sub { color: var(--text-soft); margin: 0 0 28px; font-size: 15px; }
        .ins-actions { display: flex; justify-content: space-between; margin-top: 30px; gap: 12px; }
        .ins-check { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border: 1px solid var(--border); border-radius: 10px; margin-bottom: 8px; background: var(--surface); }
        .ins-check .ic { font-size: 18px; }
        .ins-check .lb { font-weight: 600; }
        .ins-check .vl { margin-left: auto; font-size: 13px; color: var(--text-soft); }
        .ins-check.ko { border-color: var(--red-soft); }
        .ins-help { font-size: 12.5px; color: var(--amber); background: var(--amber-soft); border-radius: 8px; padding: 8px 12px; margin: -2px 0 10px; }
        .ins-field { margin-bottom: 16px; }
        .ins-progress-line { display: flex; align-items: center; gap: 12px; padding: 11px 14px; border-radius: 10px; margin-bottom: 8px; background: var(--surface); border: 1px solid var(--border); opacity: .5; transition: .2s; }
        .ins-progress-line.run { opacity: 1; border-color: var(--accent); }
        .ins-progress-line.ok { opacity: 1; border-color: var(--green-soft); }
        .ins-progress-line.ko { opacity: 1; border-color: var(--red-soft); }
        .ins-progress-line .st { margin-left: auto; font-size: 13px; }
        @media (max-width: 860px) { .ins-wrap { grid-template-columns: 1fr; } .ins-aside { flex-direction: row; align-items: center; padding: 16px; } .ins-steps { flex-direction: row; overflow-x: auto; } .ins-brand { margin: 0 20px 0 0; } .ins-aside-foot { display: none; } .ins-main { padding: 28px 20px; } }

        /* Spinner */
        @keyframes ins-spin { to { transform: rotate(360deg); } }
        .ins-spin { width: 16px; height: 16px; border: 2px solid var(--border-strong); border-top-color: var(--accent); border-radius: 50%; animation: ins-spin .6s linear infinite; display: inline-block; }

        /* Étape 2 — checks compacts en grille, révélés 1 par 1 */
        .ins-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }
        .ins-grid.one { grid-template-columns: 1fr; }
        .ins-row { display: flex; align-items: center; gap: 10px; padding: 10px 13px; border: 1px solid var(--border); border-radius: 10px; background: var(--surface); opacity: 0; transform: translateY(6px); transition: opacity .25s, transform .25s, border-color .2s; }
        .ins-row.show { opacity: 1; transform: none; }
        .ins-row .ic { font-size: 16px; width: 18px; text-align: center; }
        .ins-row .lb { font-weight: 600; font-size: 13.5px; }
        .ins-row .vl { margin-left: auto; font-size: 12px; color: var(--text-faint); }
        .ins-row.ko { border-color: var(--red-soft); background: var(--red-soft); }
        .ins-sec-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: var(--text-faint); margin: 18px 0 8px; }
        .ins-help { font-size: 12.5px; color: #92591a; background: var(--amber-soft); border-radius: 8px; padding: 7px 11px; margin: 4px 0 0; }
        .ins-scan { display: flex; align-items: center; gap: 10px; color: var(--text-soft); font-weight: 600; padding: 6px 0 14px; }

        /* Inputs soignés (étapes 3 & 4) */
        .fld { margin-bottom: 16px; }
        .fld label { display: block; font-size: 12.5px; font-weight: 600; color: var(--text-soft); margin-bottom: 6px; }
        .fld .box { display: flex; align-items: center; gap: 10px; background: var(--surface); border: 1.5px solid var(--border-strong); border-radius: 11px; padding: 0 14px; transition: border-color .15s, box-shadow .15s; }
        .fld .box:focus-within { border-color: var(--accent); box-shadow: 0 0 0 4px var(--accent-soft); }
        .fld .box .ico { font-size: 17px; opacity: .7; }
        .fld .box input { flex: 1; border: 0; background: transparent; outline: none; padding: 13px 0; font-size: 14.5px; color: var(--text); font-family: inherit; }
        .fld .box input::placeholder { color: var(--text-faint); }
        .fld .hint { font-size: 11.5px; color: var(--text-faint); margin-top: 5px; }
        .fld-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        @media (max-width: 620px) { .fld-2 { grid-template-columns: 1fr; } .ins-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<script>window.INSTALL_CSRF = <?= json_encode($csrfToken) ?>;</script>
<div class="ins-wrap">
    <aside class="ins-aside">
        <div class="ins-brand"><span class="logo">⚡</span> Aegis Framework</div>
        <ul class="ins-steps">
            <?php foreach ($steps as $n => $s): ?>
                <li class="ins-step <?= $n === $step ? 'active' : ($n < $step ? 'done' : '') ?>">
                    <span class="n"><?= $n < $step ? '✓' : $n ?></span>
                    <span><?= $s['icon'] ?> <?= $h($s['title']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="ins-aside-foot">Assistant d'installation · v<?php
            $clFile = __DIR__ . '/../framework/changelog.json';
            $clVer = '4.0.0';
            if (is_file($clFile)) {
                $clData = json_decode((string)@file_get_contents($clFile), true);
                if (is_array($clData) && !empty($clData['version'])) { $clVer = (string)$clData['version']; }
            }
            echo htmlspecialchars($clVer, ENT_QUOTES, 'UTF-8');
        ?></div>
    </aside>

    <main class="ins-main">
        <div class="ins-card">
        <?php if ($installed && $step !== 1): ?>
            <h1 class="ins-h">✅ Déjà installé</h1>
            <p class="ins-sub">Aegis Framework est déjà installé. Pour relancer l'assistant, supprimez le fichier <code>install/installed.lock</code>.</p>
            <a class="ui-btn primary" href="../admin/dashboard">Aller à l'administration →</a>
        <?php else: ?>

        <?php if ($step === 1): ?>
            <h1 class="ins-h">👋 Bienvenue</h1>
            <p class="ins-sub">Cet assistant va installer <strong>Aegis Framework V4</strong> en quelques étapes. Préparez les identifiants de votre base de données MySQL.</p>
            <div class="ui-grid cols-2" style="gap:14px">
                <div class="ui-card"><div class="ui-card-body"><div style="font-size:24px">🧩</div><b>Modulaire</b><p class="u-muted" style="margin:4px 0 0">Architecture extensible par modules.</p></div></div>
                <div class="ui-card"><div class="ui-card-body"><div style="font-size:24px">🔒</div><b>Sécurisé</b><p class="u-muted" style="margin:4px 0 0">CSRF, rate-limiting, Argon2id, firewall.</p></div></div>
                <div class="ui-card"><div class="ui-card-body"><div style="font-size:24px">🎨</div><b>UI moderne</b><p class="u-muted" style="margin:4px 0 0">Sans dépendance externe, thème clair/sombre.</p></div></div>
                <div class="ui-card"><div class="ui-card-body"><div style="font-size:24px">⚡</div><b>Rapide</b><p class="u-muted" style="margin:4px 0 0">Navigation AJAX TurboNav intégrée.</p></div></div>
            </div>
            <?php if ($installed): ?><p class="ins-help" style="margin-top:20px">⚠️ Une installation existe déjà. Continuer écrasera la configuration.</p><?php endif; ?>
            <div class="ins-actions"><span></span><a class="ui-btn primary" href="?step=2">Commencer →</a></div>

        <?php elseif ($step === 2):
            $reqs = $installer->checkRequirements();
            $writes = $installer->checkWritable();
            $pass = $installer->requirementsPass();
            $renderRow = function (string $label, bool $ok, string $value, string $helpText) use ($h) {
                echo '<div class="ins-row" data-ok="' . ($ok ? '1' : '0') . '"' . (!$ok && $helpText !== '' ? ' data-help="' . $h($helpText) . '"' : '') . '>';
                echo '<span class="ic"><span class="ins-spin"></span></span>';
                echo '<span class="lb">' . $h($label) . '</span><span class="vl">' . $h($value) . '</span></div>';
            }; ?>
            <h1 class="ins-h">✅ Vérification des prérequis</h1>
            <p class="ins-sub">Analyse automatique de votre hébergement.</p>

            <div class="ins-scan" id="ins-scan"><span class="ins-spin"></span> Analyse en cours…</div>

            <div class="ins-sec-title">PHP &amp; extensions</div>
            <div class="ins-grid">
                <?php foreach ($reqs as $c) $renderRow($c['label'], $c['ok'], $c['value'], $c['help']); ?>
            </div>

            <div class="ins-sec-title">Droits d'écriture</div>
            <div class="ins-grid">
                <?php foreach ($writes as $w) $renderRow($w['label'], $w['ok'], $w['ok'] ? 'Accessible' : 'Lecture seule', $w['ok'] ? '' : "Donnez les droits d'écriture (CHMOD 755) sur : " . $w['path']); ?>
            </div>

            <div id="reqs-help"></div>

            <div class="ins-actions" id="reqs-actions" style="display:none">
                <a class="ui-btn" href="?step=1">← Retour</a>
                <?php if ($pass): ?>
                    <a class="ui-btn primary" href="?step=3">Tout est bon, continuer →</a>
                <?php else: ?>
                    <a class="ui-btn primary" href="?step=2">↻ Relancer l'analyse</a>
                <?php endif; ?>
            </div>

            <script>
            (function () {
                var rows = Array.prototype.slice.call(document.querySelectorAll('.ins-row'));
                var scan = document.getElementById('ins-scan');
                var help = document.getElementById('reqs-help');
                var actions = document.getElementById('reqs-actions');
                var allOk = <?= $pass ? 'true' : 'false' ?>;
                var i = 0;
                function next() {
                    if (i >= rows.length) {
                        scan.innerHTML = allOk
                            ? '<span style="color:var(--green)">✅ Votre hébergement est compatible.</span>'
                            : '<span style="color:var(--amber)">⚠️ Certains prérequis doivent être corrigés.</span>';
                        actions.style.display = 'flex';
                        return;
                    }
                    var r = rows[i];
                    r.classList.add('show');
                    setTimeout(function () {
                        var ok = r.getAttribute('data-ok') === '1';
                        r.querySelector('.ic').textContent = ok ? '✅' : '❌';
                        if (!ok) {
                            r.classList.add('ko');
                            var hp = r.getAttribute('data-help');
                            if (hp) { var d = document.createElement('div'); d.className = 'ins-help'; d.textContent = '💡 ' + hp; help.appendChild(d); }
                        }
                        i++; setTimeout(next, 80);
                    }, 220);
                }
                next();
            })();
            </script>

        <?php elseif ($step === 3):
            $db = $_SESSION['install_db'] ?? ['host' => 'localhost', 'port' => 3306, 'name' => '', 'user' => 'root', 'pass' => '']; ?>
            <h1 class="ins-h">🗄️ Base de données</h1>
            <p class="ins-sub">Renseignez les informations de connexion à votre base MySQL / MariaDB.</p>
            <div id="db-flash"></div>
            <form id="db-form">
                <div class="fld-2">
                    <div class="fld"><label>Hôte</label><div class="box"><span class="ico">🌐</span><input name="host" value="<?= $h($db['host']) ?>" placeholder="localhost"></div></div>
                    <div class="fld"><label>Port</label><div class="box"><span class="ico">🔌</span><input name="port" value="<?= $h($db['port']) ?>" placeholder="3306"></div></div>
                </div>
                <div class="fld"><label>Nom de la base</label><div class="box"><span class="ico">🗄️</span><input name="name" value="<?= $h($db['name']) ?>" placeholder="esport_cms" required></div><div class="hint">Elle sera créée automatiquement si elle n'existe pas.</div></div>
                <div class="fld-2">
                    <div class="fld"><label>Utilisateur</label><div class="box"><span class="ico">👤</span><input name="user" value="<?= $h($db['user']) ?>" placeholder="root"></div></div>
                    <div class="fld"><label>Mot de passe</label><div class="box"><span class="ico">🔑</span><input type="password" name="pass" value="<?= $h($db['pass']) ?>" placeholder="••••••••"></div></div>
                </div>
                <div class="ins-actions">
                    <a class="ui-btn" href="?step=2">← Retour</a>
                    <button type="submit" class="ui-btn primary" id="db-submit">Tester la connexion →</button>
                </div>
            </form>
            <script>
            document.getElementById('db-form').addEventListener('submit', function (e) {
                e.preventDefault();
                var flash = document.getElementById('db-flash');
                flash.innerHTML = '<div class="ins-help">⏳ Test de connexion…</div>';
                var fd = new FormData(this); fd.append('csrf', window.INSTALL_CSRF);
                fetch('?action=test-db', { method: 'POST', body: fd })
                    .then(r => r.json()).then(function (d) {
                        if (d.success) { window.location = '?step=4'; }
                        else { flash.innerHTML = '<div class="ins-check ko"><span class="ic">❌</span><span class="lb">' + d.message + '</span></div>'; }
                    }).catch(e => flash.innerHTML = '<div class="ins-check ko"><span class="ic">❌</span><span class="lb">' + e + '</span></div>');
            });
            </script>

        <?php elseif ($step === 4):
            $a = $_SESSION['install_admin'] ?? ['username' => '', 'email' => '', 'site_name' => 'Aegis Framework']; ?>
            <h1 class="ins-h">👤 Compte administrateur</h1>
            <p class="ins-sub">Créez le compte super-administrateur qui gérera le site.</p>
            <div id="adm-flash"></div>
            <form id="adm-form">
                <div class="fld"><label>Nom du site</label><div class="box"><span class="ico">🏷️</span><input name="site_name" value="<?= $h($a['site_name'] ?? 'Aegis Framework') ?>" placeholder="Mon site"></div></div>
                <div class="fld-2">
                    <div class="fld"><label>Nom d'utilisateur</label><div class="box"><span class="ico">👤</span><input name="username" value="<?= $h($a['username']) ?>" placeholder="admin" required></div></div>
                    <div class="fld"><label>E-mail</label><div class="box"><span class="ico">✉️</span><input type="email" name="email" value="<?= $h($a['email']) ?>" placeholder="vous@exemple.fr" required></div></div>
                </div>
                <div class="fld-2">
                    <div class="fld"><label>Mot de passe</label><div class="box"><span class="ico">🔑</span><input type="password" name="password" placeholder="8 caractères min." required></div></div>
                    <div class="fld"><label>Confirmer</label><div class="box"><span class="ico">🔁</span><input type="password" name="confirm" placeholder="••••••••" required></div></div>
                </div>
                <div class="ins-actions">
                    <a class="ui-btn" href="?step=3">← Retour</a>
                    <button type="submit" class="ui-btn primary">Valider →</button>
                </div>
            </form>
            <script>
            document.getElementById('adm-form').addEventListener('submit', function (e) {
                e.preventDefault();
                var flash = document.getElementById('adm-flash');
                var fd = new FormData(this); fd.append('csrf', window.INSTALL_CSRF);
                fetch('?action=save-admin', { method: 'POST', body: fd })
                    .then(r => r.json()).then(function (d) {
                        if (d.success) { window.location = '?step=5'; }
                        else { flash.innerHTML = '<div class="ins-check ko"><span class="ic">❌</span><span class="lb">' + d.message + '</span></div>'; }
                    });
            });
            </script>

        <?php elseif ($step === 5): ?>
            <h1 class="ins-h">🚀 Installation</h1>
            <p class="ins-sub">Tout est prêt. Lancez l'installation : création de la base, des tables, du compte admin et des modules.</p>
            <div id="run-list">
                <div class="ins-progress-line" data-task="env"><span>📝</span> Écriture de la configuration <span class="st">…</span></div>
                <div class="ins-progress-line" data-task="database"><span>🗄️</span> Préparation de la base <span class="st">…</span></div>
                <div class="ins-progress-line" data-task="schema"><span>🧱</span> Création des tables <span class="st">…</span></div>
                <div class="ins-progress-line" data-task="admin"><span>👤</span> Compte administrateur <span class="st">…</span></div>
                <div class="ins-progress-line" data-task="modules"><span>🧩</span> Activation des modules cœur <span class="st">…</span></div>
                <div class="ins-progress-line" data-task="seed"><span>🌱</span> Données par défaut (modèles IA…) <span class="st">…</span></div>
                <div class="ins-progress-line" data-task="finalize"><span>🔒</span> Finalisation <span class="st">…</span></div>
            </div>
            <div class="ui-progress u-mt" style="height:10px"><span id="run-bar" style="width:0%"></span></div>
            <div id="run-flash" class="u-mt"></div>
            <div class="ins-actions">
                <a class="ui-btn" href="?step=4">← Retour</a>
                <button id="run-btn" class="ui-btn primary">Lancer l'installation 🚀</button>
            </div>
            <script>
            (function () {
                var tasks = ['env', 'database', 'schema', 'admin', 'modules', 'seed', 'finalize'];
                var btn = document.getElementById('run-btn'), bar = document.getElementById('run-bar'), flash = document.getElementById('run-flash');
                btn.addEventListener('click', function () {
                    btn.disabled = true; btn.textContent = 'Installation en cours…';
                    var i = 0;
                    function next() {
                        if (i >= tasks.length) {
                            flash.innerHTML = '<div class="ins-check"><span class="ic">🎉</span><span class="lb">Installation terminée !</span></div>' +
                                '<a class="ui-btn primary u-mt" href="../admin/dashboard">Accéder à l\'administration →</a>';
                            return;
                        }
                        var task = tasks[i];
                        var line = document.querySelector('[data-task="' + task + '"]');
                        line.classList.add('run'); line.querySelector('.st').textContent = '⏳';
                        fetch('?action=run&task=' + task + '&csrf=' + encodeURIComponent(window.INSTALL_CSRF)).then(r => r.json()).then(function (d) {
                            line.classList.remove('run');
                            line.classList.add(d.success ? 'ok' : 'ko');
                            line.querySelector('.st').textContent = d.success ? '✅' : '❌';
                            if (!d.success) { flash.innerHTML = '<div class="ins-check ko"><span class="ic">❌</span><span class="lb">' + d.message + '</span></div>'; btn.disabled = false; btn.textContent = 'Réessayer'; return; }
                            i++; bar.style.width = Math.round(i / tasks.length * 100) + '%';
                            setTimeout(next, 300);
                        }).catch(function (e) { line.classList.add('ko'); flash.innerHTML = '<div class="ins-check ko"><span class="ic">❌</span><span class="lb">' + e + '</span></div>'; btn.disabled = false; });
                    }
                    next();
                });
            })();
            </script>
        <?php endif; ?>

        <?php endif; /* installed */ ?>
        </div>
    </main>
</div>
</body>
</html>
