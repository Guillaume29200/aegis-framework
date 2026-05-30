<?php require __DIR__ . '/layout-start.php'; ?>
<?php require __DIR__ . '/navbar.php'; ?>
<main class="member-main">
    <header class="member-topbar"><h1>🕓 Sessions</h1><div class="member-actions"><button class="member-btn" onclick="toggleMemberTheme()">Mode sombre</button></div></header>
    <section class="member-content">
        <article class="panel">
            <h3>📜 Historique des sessions</h3>
            <p class="muted">Chaque ligne correspond a une session connue avec son IP, son navigateur et sa derniere activite.</p>
            <div class="table-wrap"><table class="member-table"><thead><tr><th>🟢 Statut</th><th>📡 IP</th><th>🖥️ Appareil</th><th>🌍 Navigateur</th><th>⚙️ OS</th><th>📍 Localisation</th><th>📅 Creation</th><th>⏱️ Derniere activite</th></tr></thead><tbody>
                <?php foreach ($sessions as $session): $isCurrent = ($session['session_id'] ?? '') === session_id(); ?>
                    <tr><td><?= $isCurrent ? '<span class="badge ok">Actuelle</span>' : '<span class="badge warn">Ancienne</span>' ?></td><td><?= member_h($session['ip_address'] ?? '') ?></td><td><?= member_h($session['device_type'] ?: 'Inconnu') ?></td><td><?= member_h($session['browser'] ?: 'Inconnu') ?></td><td><?= member_h($session['os'] ?: 'Inconnu') ?></td><td><?= member_h($session['location'] ?: 'Non renseignee') ?></td><td><?= member_h(date('d/m/Y H:i', strtotime($session['created_at']))) ?></td><td><?= member_h(date('d/m/Y H:i', strtotime($session['last_activity']))) ?></td></tr>
                <?php endforeach; ?>
                <?php if (empty($sessions)): ?><tr><td colspan="8">Aucune session connue.</td></tr><?php endif; ?>
            </tbody></table></div>
        </article>
    </section>
</main>
<?php require __DIR__ . '/layout-end.php'; ?>