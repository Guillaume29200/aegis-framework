<?php require __DIR__ . '/layout-start.php'; ?>
<?php require __DIR__ . '/navbar.php'; ?>
<main class="member-main">
    <header class="member-topbar"><h1>ℹ️ Mes informations</h1><div class="member-actions"><button class="member-btn" onclick="toggleMemberTheme()">Mode sombre</button></div></header>
    <section class="member-content">
        <?php if (isset($_GET['updated'])): ?><div class="notice ok">Informations mises a jour.</div><?php endif; ?>
        <?php if (isset($_GET['password_updated'])): ?><div class="notice ok">Mot de passe mis a jour.</div><?php endif; ?>
        <?php if (isset($_GET['error'])): ?><div class="notice err">Une erreur est survenue : <?= member_h($_GET['error']) ?></div><?php endif; ?>
        <div class="grid two">
            <article class="panel">
                <h3>🪪 Informations personnelles</h3>
                <form method="post" action="<?= u('/member/profile/update') ?>">
                    <div class="form-grid"><div class="field"><label>Prenom</label><input name="first_name" value="<?= member_h($user['first_name'] ?? '') ?>"></div><div class="field"><label>Nom</label><input name="last_name" value="<?= member_h($user['last_name'] ?? '') ?>"></div><div class="field" style="grid-column:1/-1"><label>Email</label><input type="email" name="email" required value="<?= member_h($user['email'] ?? '') ?>"></div></div>
                    <button class="member-btn primary" style="margin-top:16px" type="submit">💾 Enregistrer</button>
                </form>
            </article>
            <article class="panel">
                <h3>🔑 Changer le mot de passe</h3>
                <form method="post" action="<?= u('/member/password/update') ?>">
                    <div class="field"><label>Mot de passe actuel</label><input type="password" name="current_password" required></div><br>
                    <div class="field"><label>Nouveau mot de passe</label><input type="password" name="new_password" minlength="8" required></div><br>
                    <div class="field"><label>Confirmation</label><input type="password" name="confirm_password" minlength="8" required></div>
                    <button class="member-btn primary" style="margin-top:16px" type="submit">💾 Mettre a jour</button>
                </form>
            </article>
        </div>
        <article class="panel" style="margin-top:18px"><h3>🔐 Connexion & inscription</h3><table class="member-table"><tr><td>IP actuelle</td><td><?= member_h($sessionInfo['current_ip']) ?></td></tr><tr><td>Ancienne IP</td><td><?= member_h($sessionInfo['previous_ip'] ?? 'Non disponible') ?></td></tr><tr><td>IP d'inscription</td><td><?= member_h($sessionInfo['registration_ip'] ?? 'Non disponible') ?></td></tr></table></article>
    </section>
</main>
<?php require __DIR__ . '/layout-end.php'; ?>