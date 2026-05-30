<?php
/**
 * Administration RGPD / Cookies (conforme CNIL) — UI maison
 * Variables : $rgpd (config), $csrfToken
 */
if (!defined('ESPORT_CMS')) die('Access denied');

$pageTitle = $pageTitle ?? 'RGPD / Cookies';
admin_header($pageTitle);

$rgpd = $rgpd ?? [];
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$c = $rgpd['colors'] ?? ['bg' => '#16162a', 'text' => '#e8e8f0', 'accent' => '#6366f1', 'refuse' => '#2a2a3e'];
$catOn = [];
foreach ($rgpd['categories'] ?? [] as $cat) { $catOn[$cat['code']] = !empty($cat['active']); }
?>

<div class="adm-page-head u-between" style="flex-wrap:wrap;gap:12px">
    <div>
        <div class="adm-breadcrumb"><a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><a href="<?= u('/admin/configuration') ?>">Configuration</a><span>/</span><span>RGPD / Cookies</span></div>
        <h1>🍪 Cookies &amp; RGPD</h1>
        <p>Bandeau de consentement conforme aux recommandations CNIL.</p>
    </div>
    <div class="u-flex u-gap">
        <button type="button" class="ui-btn" id="rgpd-reset">🔄 Réinitialiser les consentements</button>
        <button type="submit" form="rgpd-form" class="ui-btn primary">💾 Enregistrer</button>
    </div>
</div>

<div id="rgpd-flash"></div>

<div class="ui-card" style="border-color:var(--blue-soft);margin-bottom:18px">
    <div class="ui-card-body" style="color:var(--blue);font-size:13px">⚖️ <strong>Conformité CNIL</strong> — refus aussi visible que l'acceptation, aucune case pré-cochée, consentement révocable. La fermeture du bandeau équivaut à un refus.</div>
</div>

<form id="rgpd-form" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?= $h($csrfToken ?? '') ?>">

    <div class="ui-grid cols-2" style="align-items:start">
        <!-- Activation + comportement -->
        <div class="ui-card">
            <div class="ui-card-head">⚙️ Activation &amp; comportement</div>
            <div class="ui-card-body">
                <label class="set-switch-row" style="border:0;padding-top:0">
                    <span><b>Afficher le bandeau</b><small>Demande le consentement à la première visite.</small></span>
                    <span class="set-sw"><input type="checkbox" name="cookies_banner_enabled" <?= !empty($rgpd['enabled']) ? 'checked' : '' ?>><i></i></span>
                </label>
                <div class="fld u-mt"><label class="form-label">Position</label>
                    <select class="form-select" name="cookie_banner_position">
                        <option value="bottom" <?= ($rgpd['position'] ?? 'bottom') === 'bottom' ? 'selected' : '' ?>>⬇️ Bas de page (recommandé)</option>
                        <option value="top" <?= ($rgpd['position'] ?? '') === 'top' ? 'selected' : '' ?>>⬆️ Haut de page</option>
                    </select>
                </div>
                <div class="fld"><label class="form-label">Durée de validité du consentement</label>
                    <select class="form-select" name="cookie_validity_days">
                        <?php foreach ([30=>'30 jours',90=>'90 jours (3 mois)',180=>'180 jours — 6 mois (recommandé)',365=>'365 jours (1 an)',395=>'395 jours — 13 mois (max légal)'] as $d=>$lbl): ?>
                            <option value="<?= $d ?>" <?= (int)($rgpd['validity_days'] ?? 180) === $d ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fld"><label class="form-label">Lien politique de confidentialité</label><input class="form-control" name="rgpd_policy_url" value="<?= $h($rgpd['policy_url'] ?? '') ?>" placeholder="/privacy"></div>
            </div>
        </div>

        <!-- Textes -->
        <div class="ui-card">
            <div class="ui-card-head">✏️ Textes du bandeau</div>
            <div class="ui-card-body">
                <div class="fld"><label class="form-label">Titre</label><input class="form-control" name="rgpd_title" value="<?= $h($rgpd['title'] ?? '') ?>" maxlength="100"></div>
                <div class="fld"><label class="form-label">Texte introductif</label><textarea class="form-control" name="rgpd_text" rows="5" maxlength="400"><?= $h($rgpd['intro'] ?? '') ?></textarea></div>
            </div>
        </div>
    </div>

    <!-- Catégories -->
    <div class="ui-card u-mt">
        <div class="ui-card-head">📋 Catégories de cookies</div>
        <div class="ui-card-body">
            <p class="u-muted" style="margin-top:-4px">Activez uniquement les catégories réellement utilisées. Les cookies essentiels sont toujours présents.</p>
            <label class="set-switch-row"><span><b>🔒 Cookies essentiels</b><small>Session, authentification, sécurité, consentement.</small></span><span class="ui-badge">Toujours actifs</span></label>
            <label class="set-switch-row"><span><b>📊 Cookies analytiques</b><small>Statistiques de navigation anonymes.</small></span><span class="set-sw"><input type="checkbox" name="cookie_cat_analytics" <?= !empty($catOn['analytics']) ? 'checked' : '' ?>><i></i></span></label>
            <label class="set-switch-row"><span><b>🎯 Cookies marketing</b><small>Publicités personnalisées, retargeting.</small></span><span class="set-sw"><input type="checkbox" name="cookie_cat_marketing" <?= !empty($catOn['marketing']) ? 'checked' : '' ?>><i></i></span></label>
            <label class="set-switch-row" style="border-bottom:0"><span><b>💬 Cookies réseaux sociaux</b><small>Widgets de partage, intégrations.</small></span><span class="set-sw"><input type="checkbox" name="cookie_cat_social" <?= !empty($catOn['social']) ? 'checked' : '' ?>><i></i></span></label>
        </div>
    </div>

    <!-- Apparence -->
    <div class="ui-card u-mt">
        <div class="ui-card-head">🎨 Apparence du bandeau</div>
        <div class="ui-card-body">
            <div class="ui-grid cols-4">
                <div class="fld"><label class="form-label">Fond</label><input type="color" class="rgpd-color" name="cookie_bg" value="<?= $h($c['bg']) ?>"></div>
                <div class="fld"><label class="form-label">Texte</label><input type="color" class="rgpd-color" name="cookie_text_color" value="<?= $h($c['text']) ?>"></div>
                <div class="fld"><label class="form-label">Accent (Accepter)</label><input type="color" class="rgpd-color" name="cookie_accent" value="<?= $h($c['accent']) ?>"></div>
                <div class="fld"><label class="form-label">Bouton Refuser</label><input type="color" class="rgpd-color" name="cookie_btn_refuse_bg" value="<?= $h($c['refuse']) ?>"></div>
            </div>
            <div class="fld"><label class="form-label">Arrondi des coins : <span id="radlabel"><?= (int)($rgpd['radius'] ?? 14) ?></span> px</label>
                <input type="range" name="cookie_border_radius" min="0" max="24" value="<?= (int)($rgpd['radius'] ?? 14) ?>" style="width:100%" oninput="document.getElementById('radlabel').textContent=this.value">
            </div>
        </div>
    </div>

    <div class="u-flex" style="justify-content:flex-end;margin-top:18px"><button type="submit" class="ui-btn primary">💾 Enregistrer les paramètres RGPD</button></div>
</form>

<style>
.set-switch-row { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 12px 0; border-bottom: 1px solid var(--border); }
.set-switch-row span b { display: block; font-size: 14px; } .set-switch-row span small { color: var(--text-faint); font-size: 12.5px; }
.set-sw { position: relative; width: 48px; height: 27px; flex: 0 0 48px; }
.set-sw input { opacity: 0; width: 0; height: 0; }
.set-sw i { position: absolute; inset: 0; background: var(--surface-3); border: 1px solid var(--border-strong); border-radius: 30px; transition: .2s; }
.set-sw i::before { content: ""; position: absolute; width: 20px; height: 20px; left: 3px; top: 2.5px; background: #fff; border-radius: 50%; transition: .2s; box-shadow: 0 1px 3px rgba(0,0,0,.3); }
.set-sw input:checked + i { background: var(--accent); border-color: var(--accent); } .set-sw input:checked + i::before { transform: translateX(21px); }
.rgpd-color { width: 100%; height: 42px; border: 1px solid var(--border-strong); border-radius: var(--radius-sm); background: var(--surface); cursor: pointer; padding: 4px; }
</style>

<script>
(function () {
    var form = document.getElementById('rgpd-form'), flash = document.getElementById('rgpd-flash');
    function note(ok, msg) {
        flash.innerHTML = '<div class="ui-card" style="border-color:var(--' + (ok ? 'green' : 'red') + '-soft);margin-bottom:16px"><div class="ui-card-body" style="color:var(--' + (ok ? 'green' : 'red') + ')">' + (ok ? '✅ ' : '❌ ') + msg + '</div></div>';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        fetch('<?= u('/admin/configuration/save-rgpd') ?>', { method: 'POST', body: new FormData(form) })
            .then(r => r.json()).then(d => note(!!d.success, d.message)).catch(err => note(false, '' + err));
    });
    document.getElementById('rgpd-reset').addEventListener('click', function () {
        if (!confirm('Réinitialiser tous les consentements ? Le bandeau réapparaîtra pour tous les visiteurs.')) return;
        var fd = new FormData(); fd.append('csrf_token', '<?= $h($csrfToken ?? '') ?>');
        fetch('<?= u('/admin/configuration/rgpd/reset') ?>', { method: 'POST', body: fd })
            .then(r => r.json()).then(d => note(!!d.success, d.message)).catch(err => note(false, '' + err));
    });
})();
</script>

<?php admin_footer(); ?>
