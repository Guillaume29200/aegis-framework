<?php
if (!defined('AEGIS_FRAMEWORK')) die('Access denied');

$settings = $settings ?? [];
$siteName = trim((string)($settings['site_name'] ?? 'Aegis Framework'));
$loginCoverImage = trim((string)($settings['login_cover_image'] ?? ''));
$loginLogoImage = trim((string)($settings['login_logo_image'] ?? ''));
$coverImageUrl = $loginCoverImage !== '' ? u($loginCoverImage) : u('/framework/assets/images/login-images/login-cover.svg');
$logoImageUrl = $loginLogoImage !== '' ? u($loginLogoImage) : u('/framework/assets/images/logo.png');
$loginVisualBadge = trim((string)($settings['login_visual_badge'] ?? '')) ?: 'Espace membre sécurisé';
$loginVisualTitle = trim((string)($settings['login_visual_title'] ?? '')) ?: $siteName;
$loginVisualText = trim((string)($settings['login_visual_text'] ?? '')) ?: "Retrouvez votre panel, vos services et vos outils d'administration depuis un espace clair et protégé.";
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$authTitle = 'Mot de passe oublié';
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
                    <h1>Mot de passe oublié</h1>
                    <p>Indiquez votre e-mail : si le compte existe, un lien de réinitialisation vous sera envoyé.</p>
                </div>
                <div id="alert-container"></div>
                <form id="forgotForm" method="POST" action="<?= u('/auth/forgot-password') ?>" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?= $h($csrfToken ?? '') ?>">
                    <div class="f-field"><label for="email">Adresse e-mail</label><input type="email" id="email" name="email" required autocomplete="email"></div>
                    <button type="submit" class="f-btn"><span class="btn-text">Envoyer le lien</span><span class="btn-loading">Envoi…</span></button>
                    <p class="auth-foot"><a class="auth-link" href="<?= u('/auth/login') ?>">← Retour à la connexion</a></p>
                </form>
            </div>
        </section>
    </main>
    <script>
        var form = document.getElementById('forgotForm');
        var alertBox = document.getElementById('alert-container');
        function escapeHtml(v){return String(v).replace(/[&<>'"]/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c];});}
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = form.querySelector('button[type="submit"]');
            btn.classList.add('loading'); btn.disabled = true; alertBox.innerHTML = '';
            fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(function (d) { alertBox.innerHTML = '<div class="auth-alert ' + (d.success ? 'ok' : 'err') + '">' + escapeHtml(d.message || d.error || 'Demande traitée.') + '</div>'; })
                .catch(function () { alertBox.innerHTML = '<div class="auth-alert err">Une erreur s\'est produite. Veuillez réessayer.</div>'; })
                .finally(function () { btn.classList.remove('loading'); btn.disabled = false; });
        });
    </script>
    <?php require ROOT_PATH . '/framework/Views/theme/public/cookie-banner.php'; ?>
</body>
</html>
