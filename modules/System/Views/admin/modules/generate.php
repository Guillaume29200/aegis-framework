<?php
/**
 * Générateur de module (scaffolding) — Aegis.
 * Variables : $csrfToken
 */
if (!defined('AEGIS_FRAMEWORK')) die('Access denied');

$pageTitle = $pageTitle ?? 'Générateur de module';
admin_header($pageTitle);

$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$flashErr = $_SESSION['error'] ?? null; unset($_SESSION['error']);
?>

<div class="adm-page-head">
    <div class="adm-breadcrumb"><a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><a href="<?= u('/admin/modules') ?>">Modules</a><span>/</span><span>Générateur</span></div>
    <h1>🪄 Générateur de module</h1>
    <p>Crée un squelette de module complet, prêt à activer (manifeste, routes, contrôleur, service, schéma, vues UI).</p>
</div>

<?php if ($flashErr): ?><div class="ui-card" style="border-color:var(--red-soft);margin-bottom:14px"><div class="ui-card-body" style="color:var(--red)"><?= $h($flashErr) ?></div></div><?php endif; ?>

<form method="post" action="<?= u('/admin/modules/generate') ?>">
    <input type="hidden" name="csrf_token" value="<?= $h($csrfToken ?? '') ?>">
    <div class="ui-grid cols-2" style="grid-template-columns:1fr 1fr;gap:18px;align-items:start">
        <div class="ui-card">
            <div class="ui-card-head">📋 Identité</div>
            <div class="ui-card-body">
                <div class="fld" style="margin-bottom:12px"><label class="form-label">Nom technique (PascalCase) *</label><input class="form-control" name="name" placeholder="MonModule" pattern="[A-Za-z0-9]+" required></div>
                <div class="fld" style="margin-bottom:12px"><label class="form-label">Nom affiché</label><input class="form-control" name="display_name" placeholder="Mon Module"></div>
                <div class="fld" style="margin-bottom:12px"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="2"></textarea></div>
                <div class="fld" style="margin-bottom:0"><label class="form-label">Auteur</label><input class="form-control" name="author" placeholder="Studio"></div>
            </div>
        </div>
        <div class="ui-card">
            <div class="ui-card-head">🎨 Apparence &amp; menu</div>
            <div class="ui-card-body">
                <div class="fld" style="margin-bottom:12px"><label class="form-label">Icône (emoji)</label><input class="form-control" name="icon" placeholder="🧩" maxlength="4"></div>
                <div class="fld" style="margin-bottom:12px"><label class="form-label">Catégorie (page Modules)</label><input class="form-control" name="category" placeholder="Autres" list="cat-list">
                    <datalist id="cat-list"><option value="Système"><option value="Communautaire"><option value="e-commerce"><option value="Hébergement"><option value="Autres"></datalist>
                </div>
                <div class="fld" style="margin-bottom:12px"><label class="form-label">Sections (séparées par des virgules)</label><input class="form-control" name="sections" placeholder="Liste, Réglages, Statistiques"><p class="form-text">Un « Tableau de bord » est toujours créé. Chaque section = une page + une entrée de menu.</p></div>
                <label class="ui-switch" style="display:inline-flex;align-items:center;gap:10px"><input type="checkbox" name="mega"><span>Afficher en mega-menu</span></label>
            </div>
        </div>
    </div>
<?php if (!empty($licenseAvailable)): ?>
    <div class="ui-card" style="margin-top:18px">
        <div class="ui-card-head">🔑 Système de licence</div>
        <div class="ui-card-body">
            <label class="ui-switch" style="display:inline-flex;align-items:center;gap:10px;margin-bottom:12px"><input type="checkbox" name="license" id="genLicense"><span>Activer le système de licence pour ce module</span></label>
            <div class="fld" id="genLicenseProduct" style="margin-bottom:0;display:none"><label class="form-label">Slug du produit (licence)</label><input class="form-control" name="license_product" placeholder="mon-module"><p class="form-text">Laissez vide pour utiliser le nom du module en minuscules. Le module sera livré en mode <strong>Ouvert</strong> (sans blocage) ; vous le passerez « Sous licence » depuis <em>Licences → Intégration</em>.</p></div>
        </div>
    </div>
<?php endif; ?>

    <div style="text-align:right;margin:18px 0 24px"><button class="ui-btn primary" type="submit">🪄 Générer le module</button></div>
</form>

<div class="ui-card">
    <div class="ui-card-head">📦 Ce qui sera généré</div>
    <div class="ui-card-body u-muted" style="font-size:13px">
        <code>module.json</code> (menu + catégorie) · <code>&lt;Nom&gt;.php</code> · <code>routes.php</code> ·
        <code>Controllers/AdminController.php</code> · <code>Services/&lt;Nom&gt;Service.php</code> ·
        <code>database/install.sql</code> + <code>uninstall.sql</code> + dossier <code>migrations/</code> ·
        <code>changelog.json</code> · vues <code>Views/admin/</code> (dashboard + sections) en UI maison.
        <br>Le module est créé <strong>inactif</strong> — vous l'activez ensuite depuis la page Modules (avec vérification des tables).
    </div>
</div>

<style>
.ui-switch input[type=checkbox]{appearance:none;-webkit-appearance:none;margin:0;width:42px;height:24px;border-radius:24px;background:var(--border-strong);position:relative;cursor:pointer;transition:background .2s}
.ui-switch input[type=checkbox]::after{content:"";position:absolute;top:2px;left:2px;width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.35);transition:transform .2s}
.ui-switch input[type=checkbox]:checked{background:var(--accent)}
.ui-switch input[type=checkbox]:checked::after{transform:translateX(18px)}
</style>
<script>
(function(){
    var cb = document.getElementById('genLicense'), box = document.getElementById('genLicenseProduct');
    if (cb && box) cb.addEventListener('change', function(){ box.style.display = cb.checked ? '' : 'none'; });
})();
</script>

<?php admin_footer(); ?>
