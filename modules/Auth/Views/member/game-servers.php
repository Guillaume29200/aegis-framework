<?php require __DIR__ . '/layout-start.php'; ?>
<?php require __DIR__ . '/navbar.php'; ?>
<?php
$statusLabels = [
    'running' => ['En ligne', 'ok'],
    'starting' => ['Demarrage', 'warn'],
    'stopped' => ['Arrete', 'warn'],
    'stopping' => ['Arret', 'warn'],
    'installing' => ['Installation', 'warn'],
    'error' => ['Erreur', 'danger'],
    'uninstalled' => ['Non installe', 'warn'],
];
$totalServers = count($gameServers ?? []);
$runningServers = count(array_filter($gameServers ?? [], static fn($server) => ($server['status'] ?? '') === 'running'));
$totalSlots = array_sum(array_map(static fn($server) => (int)($server['slots'] ?? 0), $gameServers ?? []));
?>
<main class="member-main">
    <header class="member-topbar">
        <h1>🎮 Mes serveurs de jeux</h1>
        <div class="member-actions"><button class="member-btn" onclick="toggleMemberTheme()">Mode sombre</button></div>
    </header>
    <section class="member-content">
        <div class="member-hero">
            <h2>Serveurs alloues a <?= member_h($displayName) ?></h2>
            <p>Retrouvez ici les serveurs de jeux rattaches a votre compte.</p>
        </div>

        <div class="grid three">
            <article class="panel kpi"><span>Total</span><strong><?= number_format($totalServers, 0, ',', ' ') ?></strong><span>Serveurs alloues</span></article>
            <article class="panel kpi"><span>En ligne</span><strong><?= number_format($runningServers, 0, ',', ' ') ?></strong><span>Serveurs actifs</span></article>
            <article class="panel kpi"><span>Slots</span><strong><?= number_format($totalSlots, 0, ',', ' ') ?></strong><span>Capacite totale</span></article>
        </div>

        <article class="panel" style="margin-top:18px">
            <h3>🖥️ Liste des serveurs</h3>
            <div class="table-wrap">
                <table class="member-table">
                    <thead>
                        <tr><th>🖥️ Serveur</th><th>🕹️ Jeu</th><th>⚡ Statut</th><th>🌐 Adresse</th><th>👥 Joueurs</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach (($gameServers ?? []) as $server): ?>
                        <?php $status = $statusLabels[$server['status'] ?? ''] ?? [ucfirst((string)($server['status'] ?? 'Inconnu')), 'warn']; ?>
                        <tr>
                            <td><strong><?= member_h($server['name'] ?? 'Serveur') ?></strong></td>
                            <td><?= member_h($server['game_name'] ?? 'Jeu inconnu') ?></td>
                            <td><span class="badge <?= member_h($status[1]) ?>"><?= member_h($status[0]) ?></span></td>
                            <td><?= member_h($server['host_ip'] ?? 'IP indisponible') ?>:<?= member_h($server['port'] ?? '') ?><?php if (!empty($server['query_port'])): ?><br><span class="muted">Query <?= member_h($server['query_port']) ?></span><?php endif; ?></td>
                            <td><?= member_h((int)($server['current_players'] ?? 0)) ?> / <?= member_h((int)($server['slots'] ?? 0)) ?><?php if (!empty($server['ping'])): ?><br><span class="muted"><?= member_h($server['ping']) ?> ms</span><?php endif; ?></td>
                            <td style="text-align:right"><a class="member-btn primary" href="<?= u('/member/game-servers/' . (int)($server['id'] ?? 0)) ?>" style="padding:7px 12px">⚙️ Gérer</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($gameServers)): ?><tr><td colspan="7">Aucun serveur de jeux ne vous est encore alloue.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</main>
<?php require __DIR__ . '/layout-end.php'; ?>