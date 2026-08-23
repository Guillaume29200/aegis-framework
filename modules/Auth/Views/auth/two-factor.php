<?php
/**
 * Saisie du code de vérification (double authentification par e-mail).
 *
 * Aucune donnée du compte n'est affichée — ni pseudonyme, ni adresse complète.
 * Cette page est atteignable dès qu'un mot de passe correct a été fourni : en
 * dire davantage renseignerait quelqu'un qui possède le mot de passe mais pas
 * la boîte de réception.
 */
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
$authTitle = 'Vérification en deux étapes';
$minutes = (int)($minutes ?? 10);
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
                    <h1>🔐 Vérification en deux étapes</h1>
                    <p>Un code à 6 chiffres vient d'être envoyé par e-mail. Il expire dans <?= $minutes ?> minutes.</p>
                </div>
                <div id="alert-container"></div>
                <form id="twofaForm" method="POST" action="<?= u('/auth/2fa/verify') ?>" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?= $h($csrfToken ?? '') ?>">
                    <div class="f-field">
                        <label for="code">Code de vérification</label>
                        <input type="text" id="code" name="code" class="tfa-code"
                               required autocomplete="one-time-code" inputmode="numeric"
                               pattern="[0-9]{6}" maxlength="6" placeholder="000000" autofocus>
                        <p class="f-hint">📬 Pensez à vérifier vos courriers indésirables.</p>
                    </div>
                    <button type="submit" class="f-btn"><span class="btn-text">Vérifier</span><span class="btn-loading">Vérification…</span></button>
                    <p class="auth-foot">
                        <a class="auth-link" href="#" id="resendLink">Renvoyer un code</a>
                        &nbsp;·&nbsp;
                        <a class="auth-link" href="<?= u('/auth/logout') ?>">Annuler</a>
                    </p>
                </form>

                <?php if ($recoveryExiste ?? false): ?>
                <!-- Secours. Replié par défaut : c'est le chemin exceptionnel,
                     et le montrer d'emblée inviterait à s'en servir. -->
                <div class="tfa-secours">
                    <a class="auth-link" href="#" id="recoveryToggle">🆘 Je n'ai plus accès à ma boîte e-mail</a>
                    <form id="recoveryForm" method="POST" action="<?= u('/auth/2fa/recover') ?>" class="auth-form" hidden>
                        <input type="hidden" name="csrf_token" value="<?= $h($csrfToken ?? '') ?>">
                        <p class="f-hint" style="margin:10px 0">
                            Saisissez le <b>code de secours</b> noté lors de l'activation.
                            Il désactivera la double authentification pour tout le site.
                        </p>
                        <div class="f-field">
                            <input type="text" id="recovery_code" name="recovery_code" required
                                   autocomplete="off" spellcheck="false" maxlength="19"
                                   placeholder="XXXX-XXXX-XXXX-XXXX" class="tfa-recovery">
                        </div>
                        <button type="submit" class="f-btn"><span class="btn-text">Reprendre la main</span><span class="btn-loading">Vérification…</span></button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <style>
        /* Un code se lit chiffre par chiffre : chasse fixe, gros corps, et un
           interlettrage large pour qu'on les distingue d'un coup d'œil. */
        .tfa-code {
            font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace;
            font-size: 26px; font-weight: 700; text-align: center;
            letter-spacing: .38em; text-indent: .38em;
        }
        .tfa-secours { margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--au-line); text-align: center; }
        .tfa-recovery {
            font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace;
            font-size: 16px; text-align: center; letter-spacing: .12em; text-transform: uppercase;
        }
    </style>
    <script>
        var form = document.getElementById('twofaForm');
        var alertBox = document.getElementById('alert-container');
        var champ = document.getElementById('code');
        var lienRenvoi = document.getElementById('resendLink');

        function escapeHtml(v){return String(v).replace(/[&<>'"]/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c];});}
        function annoncer(ok, texte){ alertBox.innerHTML = '<div class="auth-alert ' + (ok ? 'ok' : 'err') + '">' + escapeHtml(texte) + '</div>'; }

        // Le code arrive souvent par copier-coller, avec des espaces.
        champ.addEventListener('input', function () {
            var propre = champ.value.replace(/\D/g, '').slice(0, 6);
            if (propre !== champ.value) { champ.value = propre; }
            // Six chiffres saisis : inutile de faire cliquer.
            if (propre.length === 6) { form.requestSubmit(); }
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = form.querySelector('button[type="submit"]');
            btn.classList.add('loading'); btn.disabled = true; alertBox.innerHTML = '';

            fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(function (d) {
                    annoncer(!!d.success, d.message || d.error || 'Demande traitée.');
                    if (d.success && d.redirect) {
                        setTimeout(function () { window.location.href = d.redirect; }, 600);
                        return;
                    }
                    if (d.redirect) { setTimeout(function () { window.location.href = d.redirect; }, 1500); return; }
                    champ.value = ''; champ.focus();
                })
                .catch(function () { annoncer(false, 'Une erreur s\'est produite. Veuillez réessayer.'); })
                .finally(function () { btn.classList.remove('loading'); btn.disabled = false; });
        });

        // Secours : déplier, puis soumettre.
        var bascule = document.getElementById('recoveryToggle');
        var formSec = document.getElementById('recoveryForm');

        if (bascule && formSec) {
            bascule.addEventListener('click', function (e) {
                e.preventDefault();
                formSec.hidden = !formSec.hidden;
                if (!formSec.hidden) { document.getElementById('recovery_code').focus(); }
            });

            formSec.addEventListener('submit', function (e) {
                e.preventDefault();
                var btn = formSec.querySelector('button[type="submit"]');
                btn.classList.add('loading'); btn.disabled = true; alertBox.innerHTML = '';

                fetch(formSec.action, { method: 'POST', body: new FormData(formSec), headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(function (d) {
                        annoncer(!!d.success, d.message || d.error || '');
                        if (d.redirect) { setTimeout(function () { window.location.href = d.redirect; }, d.success ? 1200 : 1500); }
                    })
                    .catch(function () { annoncer(false, 'Une erreur s\'est produite.'); })
                    .finally(function () { btn.classList.remove('loading'); btn.disabled = false; });
            });
        }

        lienRenvoi.addEventListener('click', function (e) {
            e.preventDefault();
            var donnees = new FormData();
            donnees.append('csrf_token', form.querySelector('[name="csrf_token"]').value);

            fetch('<?= u('/auth/2fa/resend') ?>', { method: 'POST', body: donnees, headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(function (d) {
                    annoncer(!!d.success, d.message || d.error || '');
                    if (!d.success && d.redirect) { setTimeout(function () { window.location.href = d.redirect; }, 1500); }
                })
                .catch(function () { annoncer(false, 'Le renvoi a échoué.'); });
        });
    </script>
</body>
</html>
