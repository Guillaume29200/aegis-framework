<?php if (!defined('AEGIS_FRAMEWORK')) die('Access denied'); ?>
        </div><!-- /.adm-content -->
    </main><!-- /.adm-main -->
</div><!-- /.adm -->

<!-- Backdrop nav mobile -->
<div class="adm-backdrop" data-action="mobnav-close"></div>

<!-- ═══════════════ PANNEAU DE CONTRÔLE (ouvert depuis le menu utilisateur) ═══════════════ -->
<aside class="adm-panel" aria-label="Panneau de personnalisation">
    <div class="adm-panel-head">
        <div>
            <h3>🎨 Apparence</h3>
            <p>Personnalisez votre interface</p>
        </div>
        <button class="adm-icon-btn" data-action="panel-close" style="margin-left:auto" title="Fermer">✕</button>
    </div>
    <div class="adm-panel-body">

        <div class="adm-panel-section">
            <label>Thème</label>
            <div class="adm-seg">
                <button data-set-theme="light">☀️ Clair</button>
                <button data-set-theme="dark">🌙 Sombre</button>
                <button data-set-theme="auto">🖥️ Auto</button>
            </div>
        </div>

        <div class="adm-panel-section">
            <label>Disposition du menu</label>
            <div class="adm-seg">
                <button data-set-layout="sidebar">▥ Latéral</button>
                <button data-set-layout="topbar">▤ Horizontal</button>
            </div>
        </div>

        <div class="adm-panel-section">
            <label>Couleur d'accent</label>
            <div class="adm-swatches">
                <span class="adm-swatch" data-set-accent="indigo"  style="background:#6366f1" title="Indigo"></span>
                <span class="adm-swatch" data-set-accent="violet"  style="background:#8b5cf6" title="Violet"></span>
                <span class="adm-swatch" data-set-accent="blue"    style="background:#3b82f6" title="Bleu"></span>
                <span class="adm-swatch" data-set-accent="emerald" style="background:#10b981" title="Émeraude"></span>
                <span class="adm-swatch" data-set-accent="rose"    style="background:#f43f5e" title="Rose"></span>
                <span class="adm-swatch" data-set-accent="amber"   style="background:#f59e0b" title="Ambre"></span>
                <span class="adm-swatch" data-set-accent="cyan"    style="background:#06b6d4" title="Cyan"></span>
                <label class="adm-swatch custom" title="Couleur personnalisée">🎨
                    <input type="color" data-accent-custom value="#6366f1">
                </label>
            </div>
        </div>

        <div class="adm-panel-section">
            <label>Affichage</label>
            <button class="adm-panel-btn" data-action="fullscreen">⛶ Basculer en plein écran</button>
        </div>

        <div class="adm-panel-section">
            <button class="adm-panel-btn" data-action="reset-ui">↺ Réinitialiser l'apparence</button>
        </div>

    </div>
</aside>

<!-- Scripts (vanilla, aucune dépendance externe) -->
<script src="<?= u('/framework/assets/js/admin/ui.js') ?>?v=<?= @filemtime(ROOT_PATH . '/framework/assets/js/admin/ui.js') ?: '1' ?>"></script>

<?php // TurboNav — navigation AJAX (accélère le CMS). Activable via Configuration. ?>
<script>window.TURBONAV = { enabled: <?= (defined('TURBONAV_ENABLED') && TURBONAV_ENABLED) ? 'true' : 'false' ?> };</script>
<script src="<?= u('/framework/assets/js/turbo-nav.js') ?>?v=<?= @filemtime(ROOT_PATH . '/framework/assets/js/turbo-nav.js') ?: '1' ?>"></script>

<?php
$cookieBanner = ROOT_PATH . '/framework/Views/theme/public/cookie-banner.php';
if (file_exists($cookieBanner)) require $cookieBanner;
?>

<?php
// Widget « Protéger mon IP » (O.D.I.N) — présent dans le DOM, mais affiché
// uniquement sur les pages Game Node Panel (visibilité gérée par son JS,
// réévaluée à chaque navigation TurboNav).
$__gnpIpWidget = ROOT_PATH . '/modules/GameNodePanel/AILogGuard/ODIN/Views/admin/odin/protect_ip_widget.php';
if (is_file($__gnpIpWidget)) require $__gnpIpWidget;
?>

<?php
// ── Déconnexion auto sur inactivité (configurable : Configuration → Sessions) ──
$__su = $GLOBALS['session_ui'] ?? null;
if (!empty($_SESSION['user_id']) && is_array($__su) && !empty($__su['idle_logout'])):
?>
<div id="aegis-idle-modal" role="dialog" aria-modal="true" aria-hidden="true">
  <div class="aim-box">
    <div class="aim-icon">⏲️</div>
    <h3 class="aim-title">Toujours là ?</h3>
    <p class="aim-text">Par sécurité, vous serez déconnecté pour inactivité dans <strong id="aim-count">60</strong> s.</p>
    <div class="aim-actions">
      <a href="<?= u('/auth/logout') ?>" class="ui-btn">Se déconnecter</a>
      <button type="button" class="ui-btn primary" id="aim-stay">Rester connecté</button>
    </div>
  </div>
</div>
<style>
#aegis-idle-modal{position:fixed;inset:0;z-index:9000;display:none;align-items:center;justify-content:center;background:rgba(8,10,20,.6);backdrop-filter:blur(3px)}
#aegis-idle-modal.show{display:flex}
#aegis-idle-modal .aim-box{background:var(--surface,#fff);border:1px solid var(--border,#e4e9f2);border-radius:16px;box-shadow:0 24px 60px rgba(0,0,0,.35);max-width:380px;width:calc(100% - 32px);padding:26px;text-align:center}
#aegis-idle-modal .aim-icon{font-size:2.2rem}
#aegis-idle-modal .aim-title{margin:.4rem 0 .3rem;font-size:1.2rem;font-weight:800;color:var(--text,#1f2330)}
#aegis-idle-modal .aim-text{color:var(--text-soft,#64748b);font-size:.92rem;margin:0 0 18px}
#aegis-idle-modal .aim-actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
</style>
<script>
(function () {
    'use strict';
    var IDLE = <?= (int)$__su['idle_seconds'] ?>;          // secondes avant déconnexion
    var WARN = <?= (int)$__su['warn_seconds'] ?>;          // début de l'avertissement
    var PING = <?= json_encode(u('/auth/session/ping')) ?>;
    var LOGOUT = <?= json_encode(u('/auth/logout') . '?session_expired=1') ?>;
    if (IDLE <= 0) return;
    if (WARN >= IDLE) WARN = Math.max(10, Math.floor(IDLE / 2));

    var last = Date.now(), modal = document.getElementById('aegis-idle-modal'),
        countEl = document.getElementById('aim-count'), shown = false, dead = false;

    function reset() { last = Date.now(); if (shown) { shown = false; modal.classList.remove('show'); modal.setAttribute('aria-hidden', 'true'); } }

    ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach(function (ev) {
        window.addEventListener(ev, function () { if (!shown && !dead) reset(); }, { passive: true });
    });

    document.getElementById('aim-stay').addEventListener('click', function () {
        fetch(PING, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { if (r.status === 401) { window.location.href = LOGOUT; return null; } return r.json(); })
            .then(function () { reset(); })
            .catch(function () { reset(); });
    });

    setInterval(function () {
        if (dead) return;
        var remaining = IDLE - Math.floor((Date.now() - last) / 1000);
        if (remaining <= 0) { dead = true; window.location.href = LOGOUT; return; }
        if (remaining <= WARN) {
            if (!shown) { shown = true; modal.classList.add('show'); modal.setAttribute('aria-hidden', 'false'); }
            countEl.textContent = remaining;
        }
    }, 1000);
})();
</script>
<?php endif; ?>
</body>
</html>
