<?php if (!defined('ESPORT_CMS')) die('Access denied'); ?>
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
<script src="<?= u('/framework/assets/js/admin/ui.js') ?>"></script>

<?php // TurboNav — navigation AJAX (accélère le CMS). Activable via Configuration. ?>
<script>window.TURBONAV = { enabled: <?= (defined('TURBONAV_ENABLED') && TURBONAV_ENABLED) ? 'true' : 'false' ?> };</script>
<script src="<?= u('/framework/assets/js/turbo-nav.js') ?>"></script>

<?php
$cookieBanner = ROOT_PATH . '/framework/Views/theme/public/cookie-banner.php';
if (file_exists($cookieBanner)) require $cookieBanner;
?>
</body>
</html>
