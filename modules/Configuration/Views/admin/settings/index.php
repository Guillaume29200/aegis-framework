<?php
/**
 * Configuration / Paramètres — UI maison (onglets, sauvegarde AJAX par section)
 * Variables : $settings[], $recaptchaConfigured, $csrfToken
 */
if (!defined('AEGIS_FRAMEWORK')) die('Access denied');

$pageTitle = $pageTitle ?? 'Configuration';
admin_header($pageTitle);

$settings = $settings ?? [];
$csrf = $csrfToken ?? '';
$h  = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$val = fn($k, $d = '') => $h($settings[$k] ?? $d);
$on  = fn($k) => !empty($settings[$k]) ? 'checked' : '';
?>

<div class="adm-page-head">
    <div class="adm-breadcrumb"><a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><span>Configuration</span></div>
    <h1>⚙️ Configuration</h1>
    <p>Réglages généraux du CMS — identité, système, sécurité, e-mails, SEO, IA et navigation.</p>
</div>

<div id="set-flash" class="set-flash"></div>

<div class="ui-card set-card">
    <div class="set-tabs">
        <button class="set-tab active" data-tab="general">🪪 Général</button>
        <button class="set-tab" data-tab="system">🧩 Système</button>
        <button class="set-tab" data-tab="security">🛡️ Sécurité</button>
        <button class="set-tab" data-tab="email">✉️ E-mails</button>
        <button class="set-tab" data-tab="ai">🤖 IA</button>
        <button class="set-tab" data-tab="turbonav">⚡ TurboNav</button>
    </div>

    <!-- GÉNÉRAL -->
    <div class="set-pane active" id="tab-general">
        <form class="set-form" data-url="<?= u('/admin/configuration/save-general') ?>" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>">
            <div class="set-grid">
                <div class="fld"><label class="form-label">Nom du site</label><input class="form-control" name="site_name" value="<?= $val('site_name') ?>"></div>
                <div class="fld"><label class="form-label">E-mail webmaster</label><input class="form-control" type="email" name="webmaster_email" value="<?= $val('webmaster_email') ?>"></div>
            </div>
            <div class="fld"><label class="form-label">Description du site</label><textarea class="form-control" name="site_description" rows="2"><?= $val('site_description') ?></textarea></div>
            <h4 class="set-sub">Visuel de la page de connexion</h4>
            <div class="set-grid">
                <div class="fld"><label class="form-label">Badge</label><input class="form-control" name="login_visual_badge" value="<?= $val('login_visual_badge') ?>"></div>
                <div class="fld"><label class="form-label">Titre</label><input class="form-control" name="login_visual_title" value="<?= $val('login_visual_title') ?>"></div>
            </div>
            <div class="fld"><label class="form-label">Texte</label><textarea class="form-control" name="login_visual_text" rows="2"><?= $val('login_visual_text') ?></textarea></div>
            <div class="set-grid">
                <div class="fld">
                    <label class="form-label">Logo de connexion</label>
                    <div class="set-drop" data-drop>
                        <?php if (!empty($settings['login_logo_image'])): ?><img src="<?= $h(u($settings['login_logo_image'])) ?>" class="set-drop-img" alt="Logo actuel"><?php else: ?><div class="set-drop-ph">🖼️</div><?php endif; ?>
                        <p class="set-drop-txt">Glissez une image ici ou <strong>cliquez</strong></p>
                        <p class="set-drop-hint">JPG, PNG, WebP · max 5 Mo</p>
                        <input type="file" name="login_logo_image" accept=".jpg,.jpeg,.png,.webp">
                    </div>
                </div>
                <div class="fld">
                    <label class="form-label">Image de couverture (fond)</label>
                    <div class="set-drop" data-drop>
                        <?php if (!empty($settings['login_cover_image'])): ?><img src="<?= $h(u($settings['login_cover_image'])) ?>" class="set-drop-img cover" alt="Couverture actuelle"><?php else: ?><div class="set-drop-ph">🌄</div><?php endif; ?>
                        <p class="set-drop-txt">Glissez une image ici ou <strong>cliquez</strong></p>
                        <p class="set-drop-hint">JPG, PNG, WebP · max 5 Mo · ~1600×1000 px</p>
                        <input type="file" name="login_cover_image" accept=".jpg,.jpeg,.png,.webp">
                    </div>
                </div>
            </div>
            <div class="set-actions"><button class="ui-btn primary" type="submit">💾 Enregistrer</button></div>
        </form>
    </div>

    <!-- SYSTÈME -->
    <div class="set-pane" id="tab-system">
        <form class="set-form" data-url="<?= u('/admin/configuration/save-system') ?>">
            <input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>">
            <label class="set-switch-row"><span><b>🐞 Mode debug</b><small>Affiche les erreurs détaillées (à éviter en production).</small></span><span class="set-sw"><input type="checkbox" name="debug_mode" <?= $on('debug_mode') ?>><i></i></span></label>
            <label class="set-switch-row"><span><b>⚡ Cache activé</b><small>Met en cache les pages pour accélérer le site.</small></span><span class="set-sw"><input type="checkbox" name="cache_enabled" <?= $on('cache_enabled') ?>><i></i></span></label>
            <div class="fld u-mt"><label class="form-label">Durée du cache (secondes)</label><input class="form-control" type="number" name="cache_ttl" value="<?= $val('cache_ttl', '3600') ?>" min="0"></div>
            <label class="set-switch-row"><span><b>🚧 Mode maintenance</b><small>Rend le site inaccessible au public.</small></span><span class="set-sw"><input type="checkbox" name="maintenance_mode" <?= $on('maintenance_mode') ?>><i></i></span></label>
            <div class="fld u-mt"><label class="form-label">Thème de maintenance</label>
                <select class="form-select" name="maintenance_theme">
                    <?php foreach (['moderne'=>'Moderne','minimaliste'=>'Minimaliste','gaming'=>'Gaming','noel'=>'Noël','halloween'=>'Halloween'] as $k=>$lbl): ?>
                        <option value="<?= $k ?>" <?= ($settings['maintenance_theme'] ?? 'moderne') === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <label class="set-switch-row"><span><b>🖼️ Optimiser les images uploadées</b><small>Redimensionne et compresse automatiquement les images (logo, couverture, médias) à l'upload.</small></span><span class="set-sw"><input type="checkbox" name="image_optimize_enabled" <?= $on('image_optimize_enabled') ?>><i></i></span></label>
            <div class="set-grid u-mt">
                <div class="fld"><label class="form-label">Largeur max (px)</label><input class="form-control" type="number" name="image_max_width" value="<?= $val('image_max_width', '1920') ?>" min="320" max="5000"></div>
                <div class="fld"><label class="form-label">Qualité (40–100)</label><input class="form-control" type="number" name="image_quality" value="<?= $val('image_quality', '82') ?>" min="40" max="100"></div>
            </div>
            <div class="set-actions"><button class="ui-btn primary" type="submit">💾 Enregistrer</button></div>
        </form>
    </div>

    <!-- SÉCURITÉ -->
    <div class="set-pane" id="tab-security">
        <form class="set-form" data-url="<?= u('/admin/configuration/save-security') ?>">
            <input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>">
            <label class="set-switch-row"><span><b>📝 Inscriptions ouvertes</b><small>Autorise la création de comptes.</small></span><span class="set-sw"><input type="checkbox" name="registration_enabled" <?= $on('registration_enabled') ?>><i></i></span></label>
            <label class="set-switch-row"><span><b>🤖 reCAPTCHA activé</b><small>Protection anti-bots Google.</small></span><span class="set-sw"><input type="checkbox" name="recaptcha_enabled" <?= $on('recaptcha_enabled') ?>><i></i></span></label>
            <div class="set-grid u-mt">
                <div class="fld"><label class="form-label">Clé site reCAPTCHA</label><input class="form-control" name="recaptcha_site_key" value="<?= $val('recaptcha_site_key') ?>"></div>
                <div class="fld"><label class="form-label">Clé secrète reCAPTCHA</label><input class="form-control" type="password" name="recaptcha_secret_key" value="<?= $val('recaptcha_secret_key') ?>"></div>
            </div>
            <label class="set-switch-row"><span><b>reCAPTCHA sur la connexion</b></span><span class="set-sw"><input type="checkbox" name="recaptcha_login" <?= $on('recaptcha_login') ?>><i></i></span></label>
            <label class="set-switch-row"><span><b>reCAPTCHA sur l'inscription</b></span><span class="set-sw"><input type="checkbox" name="recaptcha_register" <?= $on('recaptcha_register') ?>><i></i></span></label>
            <p class="u-muted u-mt">🍪 La bannière cookies se gère sur la page <a href="<?= u('/admin/configuration/rgpd') ?>">RGPD / Cookies</a>.</p>
            <div class="set-actions"><button class="ui-btn primary" type="submit">💾 Enregistrer</button></div>
        </form>
    </div>

    <!-- E-MAILS -->
    <div class="set-pane" id="tab-email">
        <form class="set-form" data-url="<?= u('/admin/configuration/save-email') ?>">
            <input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>">
            <div class="set-grid">
                <div class="fld"><label class="form-label">E-mail expéditeur (reset)</label><input class="form-control" type="email" name="password_reset_from_email" value="<?= $val('password_reset_from_email') ?>"></div>
                <div class="fld"><label class="form-label">Nom expéditeur</label><input class="form-control" name="password_reset_from_name" value="<?= $val('password_reset_from_name') ?>"></div>
            </div>
            <div class="fld"><label class="form-label">Sujet de l'e-mail de réinitialisation</label><input class="form-control" name="password_reset_email_subject" value="<?= $val('password_reset_email_subject') ?>"></div>
            <div class="fld"><label class="form-label">Corps de l'e-mail</label><textarea class="form-control" name="password_reset_email_body" rows="6"><?= $val('password_reset_email_body') ?></textarea></div>
            <div class="set-actions"><button class="ui-btn primary" type="submit">💾 Enregistrer</button></div>
        </form>
    </div>

    <!-- IA -->
    <div class="set-pane" id="tab-ai">
        <form class="set-form" data-url="<?= u('/admin/configuration/save-ai') ?>">
            <input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>">
            <p class="u-muted">Clés API utilisées par le module IA. Gérez les modèles sur la page <a href="<?= u('/admin/configuration/ai-models') ?>">Modèles IA</a>.</p>
            <div class="fld"><label class="form-label">🤖 Clé OpenAI</label><input class="form-control" type="password" name="openai_api_key" value="<?= $val('openai_api_key') ?>" placeholder="sk-…"></div>
            <div class="fld"><label class="form-label">🧠 Clé Claude</label><input class="form-control" type="password" name="claude_api_key" value="<?= $val('claude_api_key') ?>"></div>
            <div class="fld"><label class="form-label">🌬️ Clé Mistral</label><input class="form-control" type="password" name="mistral_api_key" value="<?= $val('mistral_api_key') ?>"></div>
            <div class="fld"><label class="form-label">Provider par défaut</label>
                <select class="form-select" name="default_ai_provider">
                    <?php foreach (['openai'=>'OpenAI','claude'=>'Claude','mistral'=>'Mistral'] as $k=>$lbl): ?>
                        <option value="<?= $k ?>" <?= ($settings['default_ai_provider'] ?? 'openai') === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="set-actions"><button class="ui-btn primary" type="submit">💾 Enregistrer</button></div>
        </form>
    </div>

    <!-- TURBONAV -->
    <div class="set-pane" id="tab-turbonav">
        <div class="ui-card" style="border:0;background:linear-gradient(135deg,var(--accent-soft),transparent);margin-bottom:18px">
            <div class="ui-card-body">
                <h3 style="margin:0 0 6px;font-size:18px">⚡ TurboNav — navigation instantanée</h3>
                <p class="u-muted" style="margin:0">Transforme le CMS en application web à navigation instantanée, <strong>sans réécrire une ligne de votre code</strong> : les pages sont chargées en arrière-plan et le contenu est échangé sans rechargement complet.</p>
            </div>
        </div>

        <div class="ui-grid cols-3" style="margin-bottom:18px">
            <div class="ui-card"><div class="ui-card-body"><div style="font-size:22px">🚀</div><b>Plus rapide</b><p class="u-muted" style="margin:4px 0 0;font-size:13px">Seul le contenu change : header, sidebar et scripts ne sont pas rechargés.</p></div></div>
            <div class="ui-card"><div class="ui-card-body"><div style="font-size:22px">🧩</div><b>Zéro dépendance</b><p class="u-muted" style="margin:4px 0 0;font-size:13px">Vanilla JS, aucun framework. Se désactive à tout moment sans impact.</p></div></div>
            <div class="ui-card"><div class="ui-card-body"><div style="font-size:22px">🔄</div><b>Transparent</b><p class="u-muted" style="margin:4px 0 0;font-size:13px">Compatible avec vos liens existants. Les liens <code>data-no-turbonav</code> forcent un rechargement complet (ex: déconnexion).</p></div></div>
        </div>

        <form class="set-form" data-url="<?= u('/admin/configuration/save-turbonav') ?>">
            <input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>">
            <label class="set-switch-row">
                <span><b>⚡ Activer TurboNav</b><small>Navigation AJAX sur toutes les pages d'administration.</small></span>
                <span class="set-sw"><input type="checkbox" name="turbonav_enabled" <?= $on('turbonav_enabled') ?>><i></i></span>
            </label>
            <p class="u-muted u-mt" style="font-size:13px">
                <?= !empty($settings['turbonav_enabled'])
                    ? '✅ TurboNav est actuellement <strong>ACTIF</strong> — la navigation AJAX est activée.'
                    : '⏸️ TurboNav est actuellement <strong>désactivé</strong> — navigation classique avec rechargement complet.' ?>
            </p>
            <div class="set-actions"><button class="ui-btn primary" type="submit">💾 Enregistrer</button></div>
        </form>
    </div>
</div>

<style>
.set-card { padding: 0; overflow: hidden; }
.set-tabs { display: flex; gap: 0; overflow-x: auto; border-bottom: 1px solid var(--border); }
.set-tab { padding: 14px 18px; border: 0; background: transparent; color: var(--text-soft); font-weight: 600; white-space: nowrap; cursor: pointer; font-family: inherit; font-size: 13.5px; }
.set-tab:hover { background: var(--surface-2); color: var(--text); }
.set-tab.active { color: var(--accent); box-shadow: inset 0 -3px 0 var(--accent); }
.set-pane { display: none; padding: 22px; }
.set-pane.active { display: block; }
.set-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 620px) { .set-grid { grid-template-columns: 1fr; } }
.fld { margin-bottom: 16px; }
.set-sub { margin: 8px 0 14px; font-size: 14px; color: var(--text-soft); border-top: 1px solid var(--border); padding-top: 16px; }
.set-actions { margin-top: 10px; display: flex; justify-content: flex-end; }
.set-switch-row { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 12px 0; border-bottom: 1px solid var(--border); }
.set-switch-row span b { display: block; font-size: 14px; }
.set-switch-row span small { color: var(--text-faint); font-size: 12.5px; }
.set-sw { position: relative; width: 48px; height: 27px; flex: 0 0 48px; }
.set-sw input { opacity: 0; width: 0; height: 0; }
.set-sw i { position: absolute; inset: 0; background: var(--surface-3); border: 1px solid var(--border-strong); border-radius: 30px; transition: .2s; }
.set-sw i::before { content: ""; position: absolute; width: 20px; height: 20px; left: 3px; top: 2.5px; background: #fff; border-radius: 50%; transition: .2s; box-shadow: 0 1px 3px rgba(0,0,0,.3); }
.set-sw input:checked + i { background: var(--accent); border-color: var(--accent); }
.set-sw input:checked + i::before { transform: translateX(21px); }
.set-flash { position: sticky; top: 12px; z-index: 20; }
.set-drop { position: relative; border: 2px dashed var(--border-strong); border-radius: var(--radius); padding: 18px; text-align: center; cursor: pointer; transition: border-color .15s, background .15s; background: var(--surface-2); }
.set-drop.drag { border-color: var(--accent); background: var(--accent-soft); }
.set-drop input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
.set-drop-img { max-height: 80px; max-width: 100%; object-fit: contain; border-radius: 8px; margin: 0 auto 8px; display: block; }
.set-drop-img.cover { height: 80px; width: 100%; object-fit: cover; }
.set-drop-ph { font-size: 34px; opacity: .4; margin-bottom: 6px; }
.set-drop-txt { margin: 0; font-size: 13px; color: var(--text-soft); }
.set-drop-hint { margin: 4px 0 0; font-size: 11.5px; color: var(--text-faint); }
</style>

<script>
(function () {
    // Glisser-déposer pour les images (logo / couverture)
    function preview(zone, input) {
        if (!input.files || !input.files[0]) return;
        var img = zone.querySelector('.set-drop-img');
        if (!img) { img = document.createElement('img'); img.className = 'set-drop-img'; zone.insertBefore(img, zone.firstChild); var ph = zone.querySelector('.set-drop-ph'); if (ph) ph.remove(); }
        img.src = URL.createObjectURL(input.files[0]);
    }
    document.querySelectorAll('[data-drop]').forEach(function (zone) {
        var input = zone.querySelector('input[type=file]');
        ['dragover', 'dragenter'].forEach(ev => zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.add('drag'); }));
        ['dragleave', 'dragend'].forEach(ev => zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.remove('drag'); }));
        zone.addEventListener('drop', function (e) { e.preventDefault(); zone.classList.remove('drag'); if (e.dataTransfer.files.length) { input.files = e.dataTransfer.files; preview(zone, input); } });
        input.addEventListener('change', function () { preview(zone, input); });
    });

    document.querySelectorAll('.set-tab').forEach(function (b) {
        b.addEventListener('click', function () {
            document.querySelectorAll('.set-tab').forEach(x => x.classList.remove('active'));
            document.querySelectorAll('.set-pane').forEach(x => x.classList.remove('active'));
            b.classList.add('active');
            document.getElementById('tab-' + b.dataset.tab).classList.add('active');
        });
    });
    var flash = document.getElementById('set-flash');
    function showFlash(ok, msg) {
        flash.innerHTML = '<div class="ui-card" style="border-color:var(--' + (ok ? 'green' : 'red') + '-soft);margin-bottom:14px"><div class="ui-card-body" style="color:var(--' + (ok ? 'green' : 'red') + ')">' + (ok ? '✅ ' : '❌ ') + msg + '</div></div>';
        window.scrollTo({ top: 0, behavior: 'smooth' });
        clearTimeout(window.__setFlashT); window.__setFlashT = setTimeout(function () { flash.innerHTML = ''; }, 5000);
    }
    document.querySelectorAll('.set-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = form.querySelector('button[type="submit"]'), label = btn.textContent;
            btn.disabled = true; btn.textContent = '⏳ Enregistrement…';
            fetch(form.dataset.url, { method: 'POST', body: new FormData(form) })
                .then(r => r.json())
                .then(function (d) { showFlash(!!d.success, d.message || (d.success ? 'Enregistré.' : 'Erreur.')); })
                .catch(function (err) { showFlash(false, '' + err); })
                .finally(function () { btn.disabled = false; btn.textContent = label; });
        });
    });
})();
</script>

<?php admin_footer(); ?>
