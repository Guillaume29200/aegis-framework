<?php
$docPage = 'modules/gnp/doc-odin.php';
$seo = ['title' => 'O.D.I.N Monitoring (technique) — Documentation · GameNodePanel', 'desc' => "Fonctionnement technique d'O.D.I.N : agent Python (psutil) déployé en SSH, métriques temps réel, géolocalisation en cascade, détection d'anomalies.", 'canonical' => 'https://gamenodepanel.com/documentation/doc-odin.php'];
require __DIR__ . '/../../inc/head.php';
?>
    <h1>O.D.I.N Monitoring <span style="font-weight:500;color:var(--tx3);font-size:1rem">· technique</span></h1>
    <p class="doc-lead">O.D.I.N supervise l'infrastructure. Détails internes ici ; présentation sur <a href="../pages/odin.php">la page O.D.I.N</a> du site.</p>

    <h2 id="odin-agent">Agent Python</h2>
    <p>Un agent <strong>Python léger</strong> est déployé sur chaque VPS <strong>via SSH, en un clic</strong>. Il s'appuie sur <code>psutil</code> pour relever les métriques système, à <strong>intervalle configurable</strong>, sans impacter les serveurs de jeu. Aucun port à exposer.</p>

    <h2 id="odin-metrics">Métriques temps réel</h2>
    <ul>
      <li>CPU, mémoire RAM, espace disque.</li>
      <li>Réseau (débit), processus.</li>
      <li>État des services et disponibilité (uptime).</li>
    </ul>
    <p>Les valeurs sont remontées en continu et affichées en direct dans le tableau de bord. Des <strong>seuils configurables par métrique</strong> déclenchent des alertes immédiates.</p>

    <h2 id="odin-geoloc">Géolocalisation des connexions</h2>
    <p>Chaque connexion entrante est localisée et affichée sur une carte. La résolution se fait <strong>en cascade</strong>, pour toujours disposer d'une source :</p>
    <table class="doc-table">
      <tr><th>Ordre</th><th>Fournisseur</th></tr>
      <tr><td>1</td><td><code>ipapi.co</code></td></tr>
      <tr><td>2</td><td><code>ipinfo.io</code> (si le 1<sup>er</sup> échoue)</td></tr>
      <tr><td>3</td><td><code>ip-api.com</code> (dernier recours)</td></tr>
    </table>
    <p>Complété par une liste d'<strong>IP protégées</strong> (whitelist) et un historique de sécurité par serveur.</p>

    <h2 id="odin-anomaly">Détection d'anomalies</h2>
    <p>Une <strong>analyse nocturne</strong> dégage les tendances et repère les comportements inhabituels avant qu'ils ne deviennent des pannes. Les réglages (intervalle de collecte, timeout SSH, seuils, rétention des métriques) sont ajustables.</p>

    <?php require __DIR__ . '/../../inc/foot.php'; ?>
