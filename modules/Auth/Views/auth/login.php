<?php
if (!defined('AEGIS_FRAMEWORK')) {
    die('Access denied');
}

$settings = $settings ?? [];
$siteName = trim((string)($settings['site_name'] ?? 'Aegis Framework'));
$loginCoverImage = trim((string)($settings['login_cover_image'] ?? ''));
$loginLogoImage = trim((string)($settings['login_logo_image'] ?? ''));
$coverImageUrl = $loginCoverImage !== '' ? u($loginCoverImage) : u('/framework/assets/images/login-images/login-cover.svg');
$logoImageUrl = $loginLogoImage !== '' ? u($loginLogoImage) : u('/framework/assets/images/logo.png');
$loginVisualBadge = trim((string)($settings['login_visual_badge'] ?? '')) ?: 'Espace membre sécurisé';
$loginVisualTitle = trim((string)($settings['login_visual_title'] ?? '')) ?: $siteName;
$loginVisualText = trim((string)($settings['login_visual_text'] ?? '')) ?: "Retrouvez votre panel, vos services et vos outils d'administration depuis un espace clair et protégé.";

$sessionExpired = false;
$expiredMessage = '';
if (isset($_GET['session_expired']) && $_GET['session_expired'] == 1) {
    $sessionExpired = true;
    $expiredMessage = "Votre session a expiré suite à une période d'inactivité. Veuillez vous reconnecter.";
}
if (isset($sessionManager) && method_exists($sessionManager, 'flash')) {
    $flashExpired = $sessionManager->flash('session_expired');
    if ($flashExpired) {
        $sessionExpired = true;
        $reason = $sessionManager->flash('session_expired_reason') ?? '';
        $expiredMessage = str_contains($reason, 'hijacking')
            ? 'Session invalide détectée. Veuillez vous reconnecter pour des raisons de sécurité.'
            : "Votre session a expiré suite à une période d'inactivité. Veuillez vous reconnecter.";
    }
}
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$authTitle = 'Connexion';
require __DIR__ . '/_head.php';
?>
<body class="auth-body">
    <main class="auth-shell">
        <section class="auth-visual" style="background-image:linear-gradient(90deg,rgba(10,17,31,.82),rgba(10,17,31,.32)),url('<?= $h($coverImageUrl) ?>')">
            <div class="auth-visual-content">
                <span class="auth-kicker">🔒 <?= $h($loginVisualBadge) ?></span>
                <h2 class="auth-visual-title"><?= $h($loginVisualTitle) ?></h2>
                <p class="auth-visual-text"><?= $h($loginVisualText) ?></p>
            </div>
        </section>

        <section class="auth-panel">
            <div class="auth-card">
                <div class="auth-logo"><img src="<?= $h($logoImageUrl) ?>" alt="<?= $h($siteName) ?>"></div>
                <div class="auth-heading">
                    <h1>Connexion</h1>
                    <p>Connectez-vous à votre compte <?= $h($siteName) ?>.</p>
                </div>

                <?php if ($sessionExpired): ?><div class="auth-alert err"><?= $h($expiredMessage) ?></div><?php endif; ?>
                <?php if (isset($_GET['logout'])): ?><div class="auth-alert ok">Vous avez été déconnecté avec succès.</div><?php endif; ?>
                <?php if (isset($_GET['registered'])): ?><div class="auth-alert ok">Inscription réussie. Vous pouvez maintenant vous connecter.</div><?php endif; ?>
                <div id="alert-container"></div>

                <form id="loginForm" method="POST" action="<?= u('/auth/login') ?>" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?= $h($csrfToken ?? '') ?>">
                    <input type="hidden" name="screen_resolution" id="screen_resolution" value="">
                    <div class="f-field">
                        <label for="identifier">Nom d'utilisateur ou e-mail</label>
                        <input type="text" id="identifier" name="identifier" placeholder="vous@exemple.fr" required autofocus autocomplete="username">
                    </div>
                    <div class="f-field">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" required autocomplete="current-password">
                    </div>
                    <?php $rememberOn = !isset($settings['session_remember_enabled']) || !empty($settings['session_remember_enabled']); ?>
                    <div class="f-row"<?= $rememberOn ? '' : ' style="justify-content:flex-end"' ?>>
                        <?php if ($rememberOn): ?>
                        <label class="f-switch"><input type="checkbox" id="remember_me" name="remember_me"><i></i><span>Se souvenir de moi</span></label>
                        <?php endif; ?>
                        <a class="auth-link" href="<?= u('/auth/forgot-password') ?>">Mot de passe oublié ?</a>
                    </div>
                    <button type="submit" class="f-btn">
                        <span class="btn-text">Se connecter</span>
                        <span class="btn-loading">Connexion…</span>
                    </button>
                    <p class="auth-foot">Pas encore de compte ? <a class="auth-link" href="<?= u('/auth/register') ?>">S'inscrire</a></p>
                </form>

                <?php if (function_exists('recaptcha_script')) recaptcha_script(); ?>
            </div>
        </section>
    </main>

    <script>
        document.getElementById('screen_resolution').value = window.screen.width + 'x' + window.screen.height;
        var form = document.getElementById('loginForm');
        var alertBox = document.getElementById('alert-container');
        function escapeHtml(v){return String(v).replace(/[&<>'"]/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c];});}
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = form.querySelector('button[type="submit"]');
            var fd = new FormData(form);
            btn.classList.add('loading'); btn.disabled = true; alertBox.innerHTML = '';
            <?php if (function_exists('recaptcha_active') && recaptcha_active('login')): ?>
            grecaptcha.ready(function () {
                grecaptcha.execute('<?= recaptcha_site_key() ?>', { action: 'login' }).then(function (token) {
                    fd.append('recaptcha_token', token); submitLogin(fd, btn);
                }).catch(function () { fail(btn, 'Erreur reCAPTCHA. Veuillez réessayer.'); });
            });
            <?php else: ?>
            submitLogin(fd, btn);
            <?php endif; ?>
        });
        function fail(btn, msg){ alertBox.innerHTML = '<div class="auth-alert err">' + escapeHtml(msg) + '</div>'; btn.classList.remove('loading'); btn.disabled = false; }
        function submitLogin(fd, btn) {
            fetch(form.action, { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(function (d) {
                    if (d.success) { alertBox.innerHTML = '<div class="auth-alert ok">' + escapeHtml(d.message || 'Connexion réussie.') + '</div>'; setTimeout(function(){ window.location.href = d.redirect; }, 600); return; }
                    fail(btn, d.error || 'Connexion impossible.');
                })
                .catch(function () { fail(btn, "Une erreur s'est produite. Veuillez réessayer."); });
        }
    </script>
    <?php require ROOT_PATH . '/framework/Views/theme/public/cookie-banner.php'; ?>
</body>
</html>
