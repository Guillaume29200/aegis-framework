<?php $activePage = $activePage ?? 'dashboard'; ?>
<aside class="member-sidebar">
    <div class="member-brand">
        <div class="member-brand-icon">EC</div>
        <div><strong>Aegis Framework</strong><span>Espace client</span></div>
    </div>
    <nav class="member-nav">
        <a class="<?= $activePage === 'dashboard' ? 'active' : '' ?>" href="<?= u('/member/dashboard') ?>"><span class="ico">🏠</span> Tableau de bord</a>
        <?php if (module_active('GameNodePanel')): ?>
        <a class="<?= $activePage === 'game-servers' ? 'active' : '' ?>" href="<?= u('/member/game-servers') ?>"><span class="ico">🎮</span> Mes serveurs</a>
        <?php endif; ?>
        <?php if (module_active('Tickets')): ?>
        <a class="<?= $activePage === 'tickets' ? 'active' : '' ?>" href="<?= u('/member/tickets') ?>"><span class="ico">🎫</span> Mes tickets</a>
        <?php endif; ?>
        <a class="<?= $activePage === 'sessions' ? 'active' : '' ?>" href="<?= u('/member/sessions') ?>"><span class="ico">🕓</span> Sessions</a>
        <a class="<?= $activePage === 'profile' ? 'active' : '' ?>" href="<?= u('/member/profile') ?>"><span class="ico">ℹ️</span> Informations</a>
        <?php if (in_array(($user['role'] ?? ''), ['admin','superadmin','moderator'], true)): ?>
            <a href="<?= u('/admin/dashboard') ?>"><span class="ico">👑</span> Administration</a>
        <?php endif; ?>
    </nav>
    <div class="member-sidebar-footer">
        <div class="member-mini-user"><strong><?= member_h($displayName ?? ($user['username'] ?? 'Membre')) ?></strong><span><?= member_h($user['email'] ?? '') ?></span></div>
        <a class="member-btn danger" style="text-align:center" href="<?= u('/auth/logout') ?>">🚪 Deconnexion</a>
    </div>
</aside>