<?php
/**
 * Création d'un utilisateur — UI maison (soumission AJAX)
 * Variables : $csrfToken
 */
if (!defined('ESPORT_CMS')) die('Access denied');

$pageTitle = "Création d'utilisateur";
admin_header($pageTitle);

$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$roles = ['member' => 'Membre', 'moderator' => 'Modérateur', 'admin' => 'Admin', 'superadmin' => 'Super Admin'];
$statuses = ['active' => 'Actif', 'inactive' => 'Inactif', 'banned' => 'Banni', 'pending' => 'En attente'];
?>

<div class="adm-page-head">
    <div class="adm-breadcrumb"><a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><a href="<?= u('/admin/users') ?>">Utilisateurs</a><span>/</span><span>Nouveau</span></div>
    <h1>➕ Nouvel utilisateur</h1>
    <p>Créez un compte manuellement.</p>
</div>

<div id="uc-flash"></div>

<form id="uc-form" action="<?= u('/admin/users/create') ?>">
    <input type="hidden" name="csrf_token" value="<?= $h($csrfToken ?? '') ?>">
    <div class="ui-grid cols-2" style="align-items:start">
        <div class="ui-card">
            <div class="ui-card-head">🪪 Identité</div>
            <div class="ui-card-body">
                <div class="fld"><label class="form-label">Nom d'utilisateur *</label><input class="form-control" name="username" required></div>
                <div class="fld"><label class="form-label">E-mail *</label><input class="form-control" type="email" name="email" required></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="fld"><label class="form-label">Prénom</label><input class="form-control" name="first_name"></div>
                    <div class="fld"><label class="form-label">Nom</label><input class="form-control" name="last_name"></div>
                </div>
            </div>
        </div>
        <div class="ui-card">
            <div class="ui-card-head">🔐 Accès & mot de passe</div>
            <div class="ui-card-body">
                <div class="fld"><label class="form-label">Rôle</label>
                    <select class="form-select" name="role"><?php foreach ($roles as $k => $lbl): ?><option value="<?= $k ?>" <?= $k === 'member' ? 'selected' : '' ?>><?= $lbl ?></option><?php endforeach; ?></select>
                </div>
                <div class="fld"><label class="form-label">Statut</label>
                    <select class="form-select" name="status"><?php foreach ($statuses as $k => $lbl): ?><option value="<?= $k ?>" <?= $k === 'active' ? 'selected' : '' ?>><?= $lbl ?></option><?php endforeach; ?></select>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="fld"><label class="form-label">Mot de passe *</label><input class="form-control" type="password" name="password" required></div>
                    <div class="fld"><label class="form-label">Confirmer *</label><input class="form-control" type="password" name="password_confirm" required></div>
                </div>
                <label class="form-check"><input type="checkbox" class="form-check-input" name="send_email"> <span>✉️ Envoyer un e-mail de bienvenue</span></label>
            </div>
        </div>
    </div>
    <div class="u-flex" style="justify-content:flex-end;gap:10px;margin-top:18px">
        <a class="ui-btn" href="<?= u('/admin/users') ?>">Annuler</a>
        <button type="submit" class="ui-btn primary">💾 Créer l'utilisateur</button>
    </div>
</form>

<script>
(function () {
    var form = document.getElementById('uc-form'), flash = document.getElementById('uc-flash');
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var btn = form.querySelector('button[type="submit"]'), label = btn.textContent;
        btn.disabled = true; btn.textContent = '⏳ Création…';
        fetch(form.action, { method: 'POST', body: new FormData(form) })
            .then(r => r.json())
            .then(function (d) {
                if (d.success) { window.location = d.redirect || '<?= u('/admin/users') ?>'; return; }
                var msg = d.error || (d.errors ? Object.values(d.errors).join(' · ') : 'Erreur lors de la création.');
                flash.innerHTML = '<div class="ui-card" style="border-color:var(--red-soft);margin-bottom:16px"><div class="ui-card-body" style="color:var(--red)">❌ ' + msg + '</div></div>';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            })
            .catch(function (err) { flash.innerHTML = '<div class="ui-card"><div class="ui-card-body" style="color:var(--red)">❌ ' + err + '</div></div>'; })
            .finally(function () { btn.disabled = false; btn.textContent = label; });
    });
})();
</script>

<?php admin_footer(); ?>
