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
$authTitle = 'Nouveau mot de passe';
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
                    <h1>Nouveau mot de passe</h1>
                    <p>Choisissez un nouveau mot de passe pour récupérer l'accès à votre compte.</p>
                </div>
                <div id="alert-container"></div>
                <form id="resetForm" method="POST" action="<?= u('/auth/reset-password') ?>" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?= $h($csrfToken ?? '') ?>">
                    <input type="hidden" name="token" value="<?= $h((string)($token ?? '')) ?>">
                    <div class="f-field"><label for="password">Nouveau mot de passe</label><input type="password" id="password" name="password" required autocomplete="new-password"></div>
                    <div class="f-field"><label for="password_confirm">Confirmer le mot de passe</label><input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password"></div>
                    <button type="submit" class="f-btn"><span class="btn-text">Réinitialiser</span><span class="btn-loading">Traitement…</span></button>
                    <p class="auth-foot"><a class="auth-link" href="<?= u('/auth/login') ?>">← Retour à la connexion</a></p>
                </form>
            </div>
        </section>
    </main>
    <script>
        var form = document.getElementById('resetForm');
        var alertBox = document.getElementById('alert-container');
        function escapeHtml(v){return String(v).replace(/[&<>'"]/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c];});}
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = form.querySelector('button[type="submit"]');
            btn.classList.add('loading'); btn.disabled = true; alertBox.innerHTML = '';
            fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(function (d) {
                    alertBox.innerHTML = '<div class="auth-alert ' + (d.success ? 'ok' : 'err') + '">' + escapeHtml(d.message || d.error || 'Demande traitée.') + '</div>';
                    if (d.success) setTimeout(function () { window.location.href = '<?= u('/auth/login') ?>'; }, 900);
                })
                .catch(function () { alertBox.innerHTML = '<div class="auth-alert err">Une erreur s\'est produite. Veuillez réessayer.</div>'; })
                .finally(function () { btn.classList.remove('loading'); btn.disabled = false; });
        });
    </script>
    <?php require ROOT_PATH . '/framework/Views/theme/public/cookie-banner.php'; ?>
</body>
</html>
