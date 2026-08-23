<?php
$docPage = 'modules/gnp/doc-cron.php';
$seo = ['title' => 'Cron & tâches planifiées — Documentation · GameNodePanel', 'desc' => "Référence des tâches planifiées de GameNodePanel et O.D.I.N : monitoring, sauvegardes, métriques, autoban IA, nettoyage, rapports — avec fréquences conseillées et exemple de crontab.", 'canonical' => 'https://gamenodepanel.com/documentation/doc-cron.php'];
require __DIR__ . '/../../inc/head.php';
?>
    <h1>Cron &amp; tâches planifiées</h1>
    <p class="doc-lead">Une partie de l'intelligence de GameNodePanel tourne en arrière-plan : sauvegardes, collecte de métriques, autoban IA, nettoyage, rapports… Voici la liste complète des tâches, leur rôle et la fréquence conseillée.</p>
    <div class="doc-meta"><span class="doc-pill">5 crons GNP</span><span class="doc-pill">8 crons O.D.I.N</span><span class="doc-pill">Gestionnaire intégré</span></div>

    <h2 id="cron-intro">Pourquoi des crons</h2>
    <p>Certaines opérations ne peuvent pas dépendre d'un humain devant l'écran : interroger les serveurs en continu, sauvegarder la nuit, bannir une IP malveillante en quelques minutes, purger les vieilles données. GameNodePanel délègue tout cela à des <strong>scripts planifiés</strong> exécutés par le cron du serveur.</p>
    <div class="callout"><span class="i">⏱️</span><div>Sans cron actif, le monitoring temps réel, les sauvegardes automatiques et l'autoban ne fonctionnent pas. Le tableau de bord affiche une alerte si les crons attendus sont manquants.</div></div>

    <h2 id="cron-manager">Le gestionnaire de cron</h2>
    <p>Pas besoin d'éditer la <code>crontab</code> à la main : un <strong>gestionnaire intégré</strong> (Cron Manager) permet d'installer, désinstaller, activer/désactiver, exécuter à la demande et consulter les logs de chaque tâche, directement depuis l'interface.</p>
    <table class="doc-table">
      <tr><th>Action</th><th>Effet</th></tr>
      <tr><td>Installer</td><td>Ajoute la tâche à la planification</td></tr>
      <tr><td>Désinstaller</td><td>Retire la tâche</td></tr>
      <tr><td>Activer / Désactiver</td><td>Met en pause sans supprimer</td></tr>
      <tr><td>Exécuter</td><td>Lance la tâche immédiatement (test)</td></tr>
      <tr><td>Logs</td><td>Affiche la dernière sortie d'exécution</td></tr>
    </table>

    <h2 id="cron-gnp">Crons GameNodePanel</h2>
    <p>Tâches liées aux serveurs de jeu et à l'infrastructure.</p>
    <table class="doc-table">
      <tr><th>Tâche</th><th>Rôle</th><th>Fréquence conseillée</th></tr>
      <tr><td><code>cron_game_servers</code></td><td>Interroge les serveurs (joueurs, map, ping) via GNPQ</td><td>1–2 min</td></tr>
      <tr><td><code>cron_alerts</code></td><td>Vérifie les seuils CPU/RAM/disque et notifie</td><td>5 min</td></tr>
      <tr><td><code>cron_backups</code></td><td>Sauvegarde configs, addons, maps, mondes selon le jeu</td><td>Quotidien (nuit)</td></tr>
      <tr><td><code>cron_installations</code></td><td>Suit les installations en cours, détecte les timeouts</td><td>2–5 min</td></tr>
      <tr><td><code>cron_cleanup</code></td><td>Nettoie sessions, métriques, logs, cache</td><td>Quotidien (4h)</td></tr>
    </table>

    <h2 id="cron-odin">Crons O.D.I.N</h2>
    <p>Tâches de supervision et de sécurité (voir <a href="modules/gnp/doc-odin.php">O.D.I.N technique</a>).</p>
    <table class="doc-table">
      <tr><th>Tâche</th><th>Rôle</th><th>Fréquence conseillée</th></tr>
      <tr><td><code>odin_metrics</code></td><td>Collecte les métriques des serveurs (CPU, RAM, réseau…)</td><td>5 min</td></tr>
      <tr><td><code>odin_autoban</code></td><td>Bannissement automatique des IP malveillantes via IA</td><td>10 min</td></tr>
      <tr><td><code>odin_security_scan</code></td><td>Analyse les logs SSH/FTP avec l'IA pour détecter les menaces</td><td>6 h</td></tr>
      <tr><td><code>odin_geolocation</code></td><td>Met à jour la géolocalisation des IP dans le cache</td><td>Horaire / quotidien</td></tr>
      <tr><td><code>odin_nightly</code></td><td>Calculs nocturnes : baselines, prédictions, corrélations</td><td>Quotidien (nuit)</td></tr>
      <tr><td><code>odin_daily_report</code></td><td>Génère et envoie le rapport quotidien de sécurité</td><td>Quotidien</td></tr>
      <tr><td><code>odin_cleanup</code></td><td>Supprime les métriques et logs de plus de 30 jours</td><td>Quotidien</td></tr>
      <tr><td><code>odin_whitelist_cleanup</code></td><td>Retire les IP admin non vues depuis 90 jours</td><td>Quotidien</td></tr>
    </table>
    <div class="callout"><span class="i">🤖</span><div>L'autoban et le security scan utilisent le fournisseur IA configuré dans VEGA/O.D.I.N. Sans clé API valide, ces tâches se contentent des règles statiques.</div></div>

    <h2 id="cron-crontab">Exemple crontab</h2>
    <p>Si vous préférez planifier manuellement (le gestionnaire le fait pour vous), voici un exemple cohérent avec les fréquences ci-dessus :</p>
    <pre><code># ── GameNodePanel ──
*/2 * * * *  php /chemin/AILogGuard/ODIN/cron/cron_game_servers.php
*/5 * * * *  php /chemin/AILogGuard/ODIN/cron/cron_alerts.php
*/5 * * * *  php /chemin/AILogGuard/ODIN/cron/cron_installations.php
0 3 * * *    php /chemin/AILogGuard/ODIN/cron/cron_backups.php
0 4 * * *    php /chemin/AILogGuard/ODIN/cron/cron_cleanup.php

# ── O.D.I.N ──
*/5 * * * *   php /chemin/AILogGuard/ODIN/cron/odin_metrics.php
*/10 * * * *  php /chemin/AILogGuard/ODIN/cron/odin_autoban.php
0 */6 * * *   php /chemin/AILogGuard/ODIN/cron/odin_security_scan.php
30 * * * *    php /chemin/AILogGuard/ODIN/cron/odin_geolocation.php
0 2 * * *     php /chemin/AILogGuard/ODIN/cron/odin_nightly.php
0 7 * * *     php /chemin/AILogGuard/ODIN/cron/odin_daily_report.php
15 4 * * *    php /chemin/AILogGuard/ODIN/cron/odin_cleanup.php
30 4 * * *    php /chemin/AILogGuard/ODIN/cron/odin_whitelist_cleanup.php</code></pre>
    <div class="callout"><span class="i">💡</span><div>Adaptez <code>/chemin/</code> à l'emplacement réel du module et <code>php</code> au binaire PHP de votre hôte. Le Cron Manager génère ces lignes automatiquement avec les bons chemins.</div></div>

    <h2 id="cron-logs">Logs &amp; exécution manuelle</h2>
    <p>Chaque tâche peut être <strong>lancée à la demande</strong> depuis le Cron Manager (bouton « Exécuter ») pour tester son comportement, et sa <strong>dernière sortie</strong> est consultable via les logs. C'est le moyen le plus simple de diagnostiquer un cron qui ne « prend » pas : on l'exécute manuellement et on lit le résultat.</p>

    <?php require __DIR__ . '/../../inc/foot.php'; ?>
