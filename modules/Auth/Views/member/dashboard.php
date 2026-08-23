<?php require __DIR__ . '/layout-start.php'; ?>
<?php require __DIR__ . '/navbar.php'; ?>
<main class="member-main">
    <header class="member-topbar"><h1>🖥️ Tableau de bord</h1><div class="member-actions"><button class="member-btn" onclick="toggleMemberTheme()">Mode sombre</button></div></header>
    <section class="member-content">
        <div class="member-hero">
            <h2>Bienvenue <?= member_h($displayName) ?></h2>
            <p>Votre IP actuelle est <strong><?= member_h($sessionInfo['current_ip']) ?></strong>. Lors de votre ancienne session, votre IP etait <strong><?= member_h($sessionInfo['previous_ip'] ?? 'non disponible') ?></strong>. Si vous n'etes pas a l'origine de cette connexion, changez votre mot de passe depuis vos informations personnelles.</p>
        </div>
        <div class="grid three">
            <article class="panel kpi"><span>Connexions</span><strong><?= number_format((int)($user['login_count'] ?? 0), 0, ',', ' ') ?></strong><span>Total historique</span></article>
            <article class="panel kpi"><span>Derniere connexion</span><strong style="font-size:18px"><?= member_h(!empty($user['last_login']) ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Jamais') ?></strong><span>Compte <?= member_h($user['status'] ?? '') ?></span></article>
            <article class="panel kpi"><span>IP inscription</span><strong style="font-size:18px"><?= member_h($sessionInfo['registration_ip'] ?? 'Non disponible') ?></strong><span>Premiere session connue</span></article>
        </div>
        <div class="grid two" style="margin-top:18px">
            <article class="panel">
                <h3>👤 Informations du compte</h3>
                <table class="member-table">
					<tr>
						<td>🆔 Identifiant</td>
						<td><?= member_h($user['username']) ?></td>
					</tr>
					<tr>
						<td>📧 Email</td>
						<td><?= member_h($user['email']) ?></td>
					</tr>
					<tr>
						<td>🏷️ Role</td>
						<td><?= member_h($user['role']) ?></td>
					</tr>
					<tr>
						<td>📅 Membre depuis</td>
						<td><?= member_h(date('d/m/Y', strtotime($user['created_at']))) ?></td>
					</tr>
				</table>
            </article>
            <article class="panel">
                <h3>🛡️ Securite du compte</h3>
                <div class="security-note">Verifiez regulierement vos sessions. Une IP inconnue peut indiquer un acces non autorise.</div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px">
					<a class="member-btn primary" href="<?= u('/member/sessions') ?>">👀 Voir mes sessions</a>
					<a class="member-btn" href="<?= u('/member/profile') ?>">📝 Modifier mes informations</a>
				</div>
            </article>
        </div>
    </section>
</main>
<?php require __DIR__ . '/layout-end.php'; ?>