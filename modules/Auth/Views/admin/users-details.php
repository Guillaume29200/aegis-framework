<?php
/**
 * Détails utilisateur — UI maison
 * Variables : $user, $logins[], $registrationData, $lastLogin, $mapData, $totalLogins, $uniqueIPs, $devices, $browsers, $csrfToken
 */
if (!defined('AEGIS_FRAMEWORK')) die('Access denied');

$pageTitle = 'Utilisateur — ' . ($user['username'] ?? '');
admin_header($pageTitle);

$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$logins = $logins ?? [];
$registrationData = $registrationData ?? null;
$mapData = $mapData ?? [];
$totalLogins = $totalLogins ?? count($logins);
$uniqueIPs = $uniqueIPs ?? 0;
$devices = $devices ?? [];
$browsers = $browsers ?? [];

$roleBadge = ['superadmin' => 'accent', 'admin' => 'blue', 'moderator' => 'amber', 'member' => 'green'];
$statusBadge = ['active' => 'green', 'banned' => 'red', 'inactive' => 'amber'];
?>

<div class="adm-page-head u-between" style="flex-wrap:wrap;gap:12px">
    <div>
        <div class="adm-breadcrumb"><a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><a href="<?= u('/admin/users') ?>">Utilisateurs</a><span>/</span><span><?= $h($user['username'] ?? '') ?></span></div>
        <h1>👤 <?= $h($user['username'] ?? '') ?></h1>
        <p><?= $h($user['email'] ?? '') ?> · <span class="ui-badge <?= $roleBadge[$user['role']] ?? '' ?>"><?= $h(ucfirst($user['role'] ?? '')) ?></span> · <span class="ui-badge <?= $statusBadge[$user['status']] ?? '' ?>"><?= $h(ucfirst($user['status'] ?? '')) ?></span></p>
    </div>
    <div class="u-flex u-gap">
        <a class="ui-btn" href="<?= u('/admin/users') ?>">← Retour</a>
        <a class="ui-btn primary" href="<?= u('/admin/users/' . (int)($user['id'] ?? 0) . '/edit') ?>">✏️ Éditer</a>
    </div>
</div>

<div class="ui-grid cols-4" style="margin-bottom:18px">
    <div class="ui-card tone-accent"><div class="ui-kpi"><div class="ui-kpi-icon">🔗</div><div><p class="ui-kpi-label">Connexions</p><div class="ui-kpi-value"><?= (int)$totalLogins ?></div></div></div></div>
    <div class="ui-card tone-blue"><div class="ui-kpi"><div class="ui-kpi-icon">🌐</div><div><p class="ui-kpi-label">IPs uniques</p><div class="ui-kpi-value"><?= (int)$uniqueIPs ?></div></div></div></div>
    <div class="ui-card tone-green"><div class="ui-kpi"><div class="ui-kpi-icon">💻</div><div><p class="ui-kpi-label">Appareils</p><div class="ui-kpi-value"><?= count($devices) ?></div></div></div></div>
    <div class="ui-card tone-amber"><div class="ui-kpi"><div class="ui-kpi-icon">🧭</div><div><p class="ui-kpi-label">Navigateurs</p><div class="ui-kpi-value"><?= count($browsers) ?></div></div></div></div>
</div>

<div class="ui-grid cols-2" style="align-items:start">
    <!-- Dernière localisation -->
    <div class="ui-card">
        <div class="ui-card-head">🌍 Dernière localisation</div>
        <div class="ui-card-body">
            <?php if (!empty($mapData)): ?>
                <table class="ui-table">
                    <tr><td class="u-muted">Ville</td><td><?= $h($mapData['city'] ?? 'Inconnu') ?></td></tr>
                    <tr><td class="u-muted">Pays</td><td><?= $h($mapData['country'] ?? 'Inconnu') ?></td></tr>
                    <tr><td class="u-muted">IP</td><td style="font-family:monospace"><?= $h($mapData['ip'] ?? 'N/A') ?></td></tr>
                    <tr><td class="u-muted">FAI</td><td><?= $h($mapData['isp'] ?? 'Inconnu') ?></td></tr>
                </table>
                <a class="ui-btn sm u-mt" target="_blank" rel="noopener" href="https://www.openstreetmap.org/?mlat=<?= urlencode((string)$mapData['lat']) ?>&mlon=<?= urlencode((string)$mapData['lng']) ?>#map=10/<?= $h($mapData['lat']) ?>/<?= $h($mapData['lng']) ?>">🗺️ Voir sur la carte</a>
            <?php else: ?>
                <div class="ui-empty"><div class="ui-empty-icon">🗺️</div>Aucune donnée de localisation. Elle apparaîtra après la prochaine connexion.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Données d'inscription -->
    <div class="ui-card">
        <div class="ui-card-head">📋 Données d'inscription</div>
        <div class="ui-card-body">
            <?php if (!empty($registrationData)): ?>
                <table class="ui-table">
                    <tr><td class="u-muted">IP</td><td style="font-family:monospace"><?= $h($registrationData['registration_ip'] ?? '—') ?></td></tr>
                    <tr><td class="u-muted">Ville / Pays</td><td><?= $h(($registrationData['registration_city'] ?? '—') . ' / ' . ($registrationData['registration_country'] ?? '—')) ?></td></tr>
                    <tr><td class="u-muted">Appareil</td><td><?= $h($registrationData['registration_device'] ?? '—') ?></td></tr>
                    <tr><td class="u-muted">OS / Navigateur</td><td><?= $h(($registrationData['registration_os'] ?? '—') . ' · ' . ($registrationData['registration_browser'] ?? '—')) ?></td></tr>
                    <tr><td class="u-muted">FAI</td><td><?= $h($registrationData['registration_isp'] ?? '—') ?></td></tr>
                </table>
            <?php else: ?>
                <div class="ui-empty"><div class="ui-empty-icon">📋</div>Aucune donnée d'inscription (compte créé via l'administration ou avant le tracking).</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Historique des connexions -->
<div class="ui-card u-mt">
    <div class="ui-card-head">🔐 Historique des connexions</div>
    <div class="ui-card-body" style="padding:0">
        <div style="overflow-x:auto">
            <table class="ui-table">
                <thead><tr><th>Date</th><th>IP</th><th>Ville / Pays</th><th>Appareil</th><th>OS / Navigateur</th><th>Statut</th></tr></thead>
                <tbody>
                <?php foreach ($logins as $login): ?>
                    <tr>
                        <td class="u-nowrap u-muted"><?= $h(!empty($login['created_at']) ? date('d/m/Y H:i', strtotime($login['created_at'])) : '—') ?></td>
                        <td class="u-nowrap" style="font-family:monospace"><?= $h($login['ip_address'] ?? '—') ?></td>
                        <td><?= $h(trim(($login['city'] ?? '') . ' / ' . ($login['country_name'] ?? ''), ' /')) ?: '—' ?></td>
                        <td><?= $h($login['device_type'] ?? '—') ?></td>
                        <td><?= $h(trim(($login['os'] ?? '') . ' · ' . ($login['browser'] ?? ''), ' ·')) ?: '—' ?></td>
                        <td><?= !empty($login['login_success']) ? '<span class="ui-badge green">✅ Réussie</span>' : '<span class="ui-badge red">❌ Échec</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($logins)): ?>
                    <tr><td colspan="6"><div class="ui-empty"><div class="ui-empty-icon">🔐</div>Aucune connexion enregistrée. L'historique apparaîtra après la prochaine connexion.</div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php admin_footer(); ?>
