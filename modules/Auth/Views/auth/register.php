<?php
if (!defined('ESPORT_CMS')) die('Access denied');

$settings = $settings ?? [];
$siteName = trim((string)($settings['site_name'] ?? 'eSport-CMS'));
$loginCoverImage = trim((string)($settings['login_cover_image'] ?? ''));
$loginLogoImage = trim((string)($settings['login_logo_image'] ?? ''));
$coverImageUrl = $loginCoverImage !== '' ? u($loginCoverImage) : u('/framework/assets/images/login-images/login-cover.svg');
$logoImageUrl = $loginLogoImage !== '' ? u($loginLogoImage) : u('/framework/assets/images/logo.png');
$loginVisualBadge = trim((string)($settings['login_visual_badge'] ?? '')) ?: 'Espace membre sécurisé';
$loginVisualTitle = trim((string)($settings['login_visual_title'] ?? '')) ?: $siteName;
$loginVisualText = trim((string)($settings['login_visual_text'] ?? '')) ?: "Retrouvez votre panel, vos services et vos outils d'administration depuis un espace clair et protégé.";
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$authTitle = 'Inscription';
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
                    <h1>Créer un compte</h1>
                    <p>Rejoignez <?= $h($siteName) ?> et accédez à votre espace membre.</p>
                </div>

                <div id="alert-container"></div>

                <form id="registerForm" method="POST" action="<?= u('/auth/register') ?>" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?= $h($csrfToken ?? '') ?>">
                    <div class="f-grid2">
                        <div class="f-field"><label for="first_name">Prénom</label><input type="text" id="first_name" name="first_name" autocomplete="given-name"><div id="error-first_name" class="error-message"></div></div>
                        <div class="f-field"><label for="last_name">Nom</label><input type="text" id="last_name" name="last_name" autocomplete="family-name"><div id="error-last_name" class="error-message"></div></div>
                    </div>
                    <div class="f-field"><label for="username">Nom d'utilisateur *</label><input type="text" id="username" name="username" required autocomplete="username"><div id="error-username" class="error-message"></div></div>
                    <div class="f-field"><label for="email">Adresse e-mail *</label><input type="email" id="email" name="email" required autocomplete="email"><div id="error-email" class="error-message"></div></div>
                    <div class="f-grid2">
                        <div class="f-field"><label for="password">Mot de passe *</label><input type="password" id="password" name="password" required autocomplete="new-password"><div id="error-password" class="error-message"></div></div>
                        <div class="f-field"><label for="password_confirm">Confirmer *</label><input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password"><div id="error-password_confirm" class="error-message"></div></div>
                    </div>
                    <button type="submit" class="f-btn"><span class="btn-text">Créer mon compte</span><span class="btn-loading">Création…</span></button>
                    <p class="auth-foot">Déjà un compte ? <a class="auth-link" href="<?= u('/auth/login') ?>">Se connecter</a></p>
                </form>

                <?php if (function_exists('recaptcha_script')) recaptcha_script(); ?>
            </div>
        </section>
    </main>

    <script>
        var form = document.getElementById('registerForm');
        var alertBox = document.getElementById('alert-container');
        function escapeHtml(v){return String(v).replace(/[&<>'"]/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c];});}
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = form.querySelector('button[type="submit"]'); var fd = new FormData(form);
            document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
            document.querySelectorAll('.f-field input').forEach(el => el.classList.remove('error'));
            alertBox.innerHTML = ''; btn.classList.add('loading'); btn.disabled = true;
            <?php if (function_exists('recaptcha_active') && recaptcha_active('register')): ?>
            grecaptcha.ready(function () {
                grecaptcha.execute('<?= recaptcha_site_key() ?>', { action: 'register' }).then(function (t) { fd.append('recaptcha_token', t); submitRegister(fd, btn); })
                    .catch(function () { fail(btn, 'Erreur reCAPTCHA. Veuillez réessayer.'); });
            });
            <?php else: ?>
            submitRegister(fd, btn);
            <?php endif; ?>
        });
        function fail(btn, msg){ alertBox.innerHTML = '<div class="auth-alert err">' + escapeHtml(msg) + '</div>'; btn.classList.remove('loading'); btn.disabled = false; }
        function submitRegister(fd, btn) {
            fetch(form.action, { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(function (d) {
                    if (d.success) { alertBox.innerHTML = '<div class="auth-alert ok">' + escapeHtml(d.message || 'Compte créé.') + '</div>'; setTimeout(function(){ window.location.href = d.redirect; }, 800); return; }
                    if (d.errors) { for (var f in d.errors) { var er = document.getElementById('error-' + f), inp = document.getElementById(f); if (er) er.textContent = d.errors[f]; if (inp) inp.classList.add('error'); } }
                    if (d.error || (!d.errors && d.message)) alertBox.innerHTML = '<div class="auth-alert err">' + escapeHtml(d.error || d.message) + '</div>';
                    btn.classList.remove('loading'); btn.disabled = false;
                })
                .catch(function () { fail(btn, "Une erreur s'est produite. Veuillez réessayer."); });
        }
    </script>
    <?php require ROOT_PATH . '/framework/Views/theme/public/cookie-banner.php'; ?>
</body>
</html>
