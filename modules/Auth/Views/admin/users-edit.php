<?php
/**
 * Édition d'un utilisateur — UI maison
 * Variables : $user, $csrfToken
 */
if (!defined('ESPORT_CMS')) die('Access denied');

$pageTitle = 'Éditer — ' . ($user['username'] ?? '');
admin_header($pageTitle);

$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$roles = ['member' => 'Membre', 'moderator' => 'Modérateur', 'admin' => 'Admin', 'superadmin' => 'Super Admin'];
$statuses = ['active' => 'Actif', 'inactive' => 'Inactif', 'banned' => 'Banni', 'pending' => 'En attente'];
?>

<div class="adm-page-head">
    <div class="adm-breadcrumb"><a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><a href="<?= u('/admin/users') ?>">Utilisateurs</a><span>/</span><span>Éditer</span></div>
    <h1>✏️ <?= $h($user['username'] ?? '') ?></h1>
    <p>Modifiez les informations du compte.</p>
</div>

<?php if (isset($_GET['error'])): ?>
<div class="ui-card" style="border-color:var(--red-soft);margin-bottom:16px"><div class="ui-card-body" style="color:var(--red)">❌ Une erreur est survenue lors de la mise à jour.</div></div>
<?php endif; ?>

<form method="POST" action="<?= u('/admin/users/' . (int)($user['id'] ?? 0) . '/update') ?>">
    <input type="hidden" name="csrf_token" value="<?= $h($csrfToken ?? '') ?>">
    <div class="ui-grid cols-2" style="align-items:start">
        <div class="ui-card">
            <div class="ui-card-head">🪪 Identité</div>
            <div class="ui-card-body">
                <div class="fld"><label class="form-label">Nom d'utilisateur</label><input class="form-control" name="username" value="<?= $h($user['username'] ?? '') ?>" required></div>
                <div class="fld"><label class="form-label">E-mail</label><input class="form-control" type="email" name="email" value="<?= $h($user['email'] ?? '') ?>" required></div>
                <div class="set-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="fld"><label class="form-label">Prénom</label><input class="form-control" name="first_name" value="<?= $h($user['first_name'] ?? '') ?>"></div>
                    <div class="fld"><label class="form-label">Nom</label><input class="form-control" name="last_name" value="<?= $h($user['last_name'] ?? '') ?>"></div>
                </div>
            </div>
        </div>
        <div class="ui-card">
            <div class="ui-card-head">🛡️ Rôle & accès</div>
            <div class="ui-card-body">
                <div class="fld"><label class="form-label">Rôle</label>
                    <select class="form-select" name="role">
                        <?php foreach ($roles as $k => $lbl): ?><option value="<?= $k ?>" <?= ($user['role'] ?? '') === $k ? 'selected' : '' ?>><?= $lbl ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="fld"><label class="form-label">Statut</label>
                    <select class="form-select" name="status">
                        <?php foreach ($statuses as $k => $lbl): ?><option value="<?= $k ?>" <?= ($user['status'] ?? '') === $k ? 'selected' : '' ?>><?= $lbl ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="fld"><label class="form-label">Nouveau mot de passe</label><input class="form-control" type="password" name="new_password" placeholder="Laisser vide pour ne pas changer"><p class="form-text">Renseignez uniquement pour réinitialiser le mot de passe.</p></div>
            </div>
        </div>
    </div>
    <div class="u-flex" style="justify-content:flex-end;gap:10px;margin-top:18px">
        <a class="ui-btn" href="<?= u('/admin/users') ?>">Annuler</a>
        <button type="submit" class="ui-btn primary">💾 Enregistrer</button>
    </div>
</form>

<?php admin_footer(); ?>
