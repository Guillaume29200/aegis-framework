<?php
/**
 * Liste des utilisateurs — UI maison
 * Variables : $users[], $csrfToken
 */
if (!defined('ESPORT_CMS')) die('Access denied');

$pageTitle = 'Gestion des utilisateurs';
admin_header($pageTitle);

$users = $users ?? [];
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$roleBadge = ['superadmin' => 'accent', 'admin' => 'blue', 'moderator' => 'amber', 'member' => 'green'];
$roleLabel = ['superadmin' => 'Super Admin', 'admin' => 'Admin', 'moderator' => 'Modérateur', 'member' => 'Membre'];
$statusBadge = ['active' => 'green', 'banned' => 'red', 'inactive' => 'amber'];
$statusLabel = ['active' => 'Actif', 'banned' => 'Banni', 'inactive' => 'Inactif'];

$nbAdmins = count(array_filter($users, fn($u) => in_array($u['role'], ['admin', 'superadmin'], true)));
$nbActive = count(array_filter($users, fn($u) => $u['status'] === 'active'));
$nbBanned = count(array_filter($users, fn($u) => $u['status'] === 'banned'));
?>

<div class="adm-page-head u-between" style="flex-wrap:wrap;gap:12px">
    <div>
        <div class="adm-breadcrumb"><a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><span>Utilisateurs</span></div>
        <h1>👥 Utilisateurs</h1>
        <p>Gérez les comptes, rôles et statuts.</p>
    </div>
    <a class="ui-btn primary" href="<?= u('/admin/users/create') ?>">➕ Nouvel utilisateur</a>
</div>

<?php if (isset($_GET['deleted'])): ?>
<div class="ui-card" style="border-color:var(--green-soft);margin-bottom:16px"><div class="ui-card-body" style="color:var(--green)">✅ L'utilisateur <strong><?= $h($_GET['username'] ?? 'inconnu') ?></strong> a été supprimé.</div></div>
<?php endif; ?>
<?php if (isset($_GET['error'])): $em = match($_GET['error']) {
    'self_delete' => 'Vous ne pouvez pas supprimer votre propre compte.',
    'not_found' => 'Utilisateur introuvable.',
    'delete_failed' => 'Erreur lors de la suppression : ' . ($_GET['reason'] ?? 'inconnue'),
    default => 'Une erreur est survenue.' }; ?>
<div class="ui-card" style="border-color:var(--red-soft);margin-bottom:16px"><div class="ui-card-body" style="color:var(--red)">❌ <?= $h($em) ?></div></div>
<?php endif; ?>

<div class="ui-grid cols-4" style="margin-bottom:18px">
    <div class="ui-card tone-accent"><div class="ui-kpi"><div class="ui-kpi-icon">👥</div><div><p class="ui-kpi-label">Total</p><div class="ui-kpi-value"><?= count($users) ?></div></div></div></div>
    <div class="ui-card tone-blue"><div class="ui-kpi"><div class="ui-kpi-icon">🛡️</div><div><p class="ui-kpi-label">Admins</p><div class="ui-kpi-value"><?= $nbAdmins ?></div></div></div></div>
    <div class="ui-card tone-green"><div class="ui-kpi"><div class="ui-kpi-icon">⚡</div><div><p class="ui-kpi-label">Actifs</p><div class="ui-kpi-value"><?= $nbActive ?></div></div></div></div>
    <div class="ui-card tone-red"><div class="ui-kpi"><div class="ui-kpi-icon">⛔</div><div><p class="ui-kpi-label">Bannis</p><div class="ui-kpi-value"><?= $nbBanned ?></div></div></div></div>
</div>

<div class="ui-card" style="margin-bottom:16px">
    <div class="ui-card-body">
        <div class="ui-grid cols-3" style="grid-template-columns:2fr 1fr 1fr;gap:12px;align-items:end">
            <div><label class="form-label">🔎 Rechercher</label><input class="form-control" id="uSearch" placeholder="Nom ou email…"></div>
            <div><label class="form-label">Rôle</label><select class="form-select" id="uRole"><option value="">Tous</option><option value="superadmin">Super Admin</option><option value="admin">Admin</option><option value="moderator">Modérateur</option><option value="member">Membre</option></select></div>
            <div><label class="form-label">Statut</label><select class="form-select" id="uStatus"><option value="">Tous</option><option value="active">Actif</option><option value="inactive">Inactif</option><option value="banned">Banni</option></select></div>
        </div>
    </div>
</div>

<div class="ui-card">
    <div class="ui-card-body" style="padding:0">
        <div style="overflow-x:auto">
            <table class="ui-table">
                <thead><tr><th>#</th><th>Utilisateur</th><th>Rôle</th><th>Statut</th><th>Connexions</th><th>Dernière</th><th>Créé</th><th class="u-right">Actions</th></tr></thead>
                <tbody id="uBody">
                <?php foreach ($users as $user): ?>
                    <tr data-search="<?= $h(strtolower($user['username'] . ' ' . $user['email'])) ?>" data-role="<?= $h($user['role']) ?>" data-status="<?= $h($user['status']) ?>">
                        <td class="u-muted">#<?= (int)$user['id'] ?></td>
                        <td>
                            <div class="fw-semibold"><?= $h($user['username']) ?></div>
                            <div class="u-muted" style="font-size:12px"><?= $h($user['email']) ?></div>
                        </td>
                        <td><span class="ui-badge <?= $roleBadge[$user['role']] ?? '' ?>"><?= $h($roleLabel[$user['role']] ?? ucfirst($user['role'])) ?></span></td>
                        <td><span class="ui-badge <?= $statusBadge[$user['status']] ?? '' ?>"><?= $h($statusLabel[$user['status']] ?? $user['status']) ?></span></td>
                        <td><?= number_format((int)($user['login_count'] ?? 0), 0, ',', ' ') ?></td>
                        <td class="u-nowrap u-muted"><?= $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Jamais' ?></td>
                        <td class="u-nowrap u-muted"><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                        <td class="u-right u-nowrap">
                            <a class="ui-btn sm" href="<?= u('/admin/users/' . (int)$user['id']) ?>">🔍</a>
                            <a class="ui-btn sm" href="<?= u('/admin/users/' . (int)$user['id'] . '/edit') ?>">✏️</a>
                            <?php if ((int)$user['id'] !== (int)($_SESSION['user_id'] ?? 0)): ?>
                            <form method="post" action="<?= u('/admin/users/' . (int)$user['id'] . '/delete') ?>" style="display:inline" onsubmit="return confirm('Supprimer cet utilisateur et ses données liées ?')">
                                <input type="hidden" name="csrf_token" value="<?= $h($csrfToken ?? '') ?>">
                                <button class="ui-btn sm danger" type="submit">🗑️</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div id="uEmpty" class="ui-empty" style="display:none"><div class="ui-empty-icon">🔍</div>Aucun résultat.</div>
        </div>
    </div>
</div>

<script>
(function () {
    var s = document.getElementById('uSearch'), r = document.getElementById('uRole'), st = document.getElementById('uStatus');
    var rows = Array.prototype.slice.call(document.querySelectorAll('#uBody tr'));
    var empty = document.getElementById('uEmpty');
    function apply() {
        var q = (s.value || '').toLowerCase(), role = r.value, status = st.value, n = 0;
        rows.forEach(function (row) {
            var ok = (!q || row.dataset.search.includes(q)) && (!role || row.dataset.role === role) && (!status || row.dataset.status === status);
            row.style.display = ok ? '' : 'none'; if (ok) n++;
        });
        empty.style.display = (n === 0 && rows.length) ? '' : 'none';
    }
    [s, r, st].forEach(function (el) { el.addEventListener('input', apply); el.addEventListener('change', apply); });
})();
</script>

<?php admin_footer(); ?>
