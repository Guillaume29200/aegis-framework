<?php
/**
 * documentation/doc-gnp.php — GameNodePanel expliqué (modules, architecture, sécurité).
 */
$docPage = 'modules/gnp/doc-gnp.php';
$seo = [
    'title'     => 'GameNodePanel — Documentation de la plateforme',
    'desc'      => "Comprendre GameNodePanel : un ensemble de modules (gestion de serveurs de jeu, VEGA, O.D.I.N, Database Manager, Marketplace) bâti sur Aegis Framework, avec une architecture hybride panels natifs + Docker.",
    'canonical' => 'https://gamenodepanel.com/documentation/doc-gnp.php',
];
require __DIR__ . '/../../inc/head.php';
?>

    <h1>GameNodePanel</h1>
    <p class="doc-lead">GameNodePanel est une plateforme de gestion de serveurs de jeu construite <strong>au-dessus d'Aegis Framework</strong>. Concrètement : un ensemble de modules qui partagent le même socle (routeur, base, sécurité, thème admin).</p>
    <div class="doc-meta">
      <span class="doc-pill">Sur Aegis Framework</span>
      <span class="doc-pill">200+ jeux</span>
      <span class="doc-pill">Hybride · natif + Docker</span>
    </div>

    <h2 id="gnp-intro">Qu'est-ce que GameNodePanel ?</h2>
    <p>Là où Aegis fournit le « moteur » (voir la rubrique <a href="documentation.php">Aegis Framework</a>), GameNodePanel apporte le « métier » : créer, installer, configurer et surveiller des serveurs de jeu, gérer des bases de données, analyser les logs par IA, etc. Chaque grande fonctionnalité est un <strong>module Aegis</strong> indépendant.</p>
    <div class="callout"><span class="i">🧭</span><div>Pour une présentation visuelle de la plateforme, voir la page publique <a href="../pages/plateforme.php">La plateforme</a> du site.</div></div>

    <h2 id="gnp-archi">Architecture hybride</h2>
    <p>Tous les jeux ne se gèrent pas de la même façon. GameNodePanel est donc <strong>hybride</strong> :</p>
    <table class="doc-table">
      <tr><th>Approche</th><th>Pour quoi</th><th>Exemples</th></tr>
      <tr><td><strong>Panels natifs</strong></td><td>Installation directe sur l'hôte via SSH / SteamCMD, interface sur mesure</td><td>Minecraft, FiveM, Battlefield, DST, Hytale, vocal, standard</td></tr>
      <tr><td><strong>Panel Docker</strong></td><td>Déploiement en conteneur isolé, reproductible</td><td>Conan Exiles, Enshrouded, Abiotic Factor, Windrose…</td></tr>
    </table>
    <p>Le routeur de panels (<code>GamePanelRouter</code>) aiguille automatiquement chaque serveur vers le bon panel selon son type de jeu.</p>

    <h2 id="gnp-panels">Les panels</h2>
    <p>Chaque panel partage un socle commun (console temps réel, statistiques, bande passante, protocole <strong>GNPQ</strong>, installation en streaming SSE) et ajoute ses écrans spécifiques :</p>
    <ul>
      <li><strong>Minecraft</strong> — le plus complet : install Paper/Fabric/Forge/Bedrock, marketplace Modrinth, mondes, <code>server.properties</code>, ops, whitelist, IP bannies, sauvegardes, logs.</li>
      <li><strong>FiveM / CFX</strong> — ressources, joueurs, configuration.</li>
      <li><strong>Battlefield</strong> — maps, mods, banlist, configuration.</li>
      <li><strong>Don't Starve Together</strong> — cluster multi-shards, mondes.</li>
      <li><strong>Hytale</strong> — config, mondes, sauvegardes.</li>
      <li><strong>Serveurs vocaux</strong> — contrôle, infos, viewer live.</li>
      <li><strong>Standard</strong> — panel générique pour tout jeu dédié.</li>
    </ul>
    <div class="callout"><span class="i">🧰</span><div>Détail visuel et exemples : page <a href="../pages/panels.php">Les panels</a> du site.</div></div>

    <h2 id="gnp-modules">Les modules GameNodePanel</h2>
    <table class="doc-table">
      <tr><th>Module</th><th>Rôle</th><th>En savoir plus</th></tr>
      <tr><td>Cœur — Serveurs de jeu</td><td>Création/install/contrôle des serveurs (200+ jeux)</td><td><a href="../pages/games.php">Jeux</a></td></tr>
      <tr><td>TurboNav</td><td>Navigation SPA instantanée, sans framework</td><td><a href="../pages/turbonav.php">TurboNav</a></td></tr>
      <tr><td>VEGA AI LogGuard</td><td>Analyse IA des logs, AutoBan</td><td><a href="../pages/vega.php">VEGA</a></td></tr>
      <tr><td>O.D.I.N Monitoring</td><td>Supervision d'infrastructure (agent Python)</td><td><a href="../pages/odin.php">O.D.I.N</a></td></tr>
      <tr><td>Database Manager</td><td>MySQL provisionné en SSH, console SQL</td><td><a href="../pages/database-manager.php">Database Manager</a></td></tr>
      <tr><td>Modrinth Marketplace</td><td>Mods/plugins Minecraft en 1 clic</td><td><a href="../pages/modrinth.php">Modrinth</a></td></tr>
      <tr><td>Intégration IA de plugins</td><td>Suggestions/installation, tous jeux</td><td><a href="../pages/plugins.php">Plugins IA</a></td></tr>
    </table>

    <h2 id="gnp-vega">VEGA — surveillance IA des logs</h2>
    <p>VEGA lit les logs de chaque serveur en temps réel (collecte SSH), les fait analyser par une IA <strong>multi-provider</strong> (Claude, GPT-4o, Mistral, Llama, Gemini, avec bascule automatique), détecte via <strong>63 patterns</strong> extensibles (triche, exploits, crashs, spam…) et déclenche des actions automatiques (AutoBan, alerte, rapport). Cloisonné par serveur, clés API chiffrées.</p>

    <h2 id="gnp-odin">O.D.I.N — monitoring d'infrastructure</h2>
    <p>Un agent Python léger (<code>psutil</code>), déployé en SSH, remonte CPU/RAM/disque/réseau/processus en continu, géolocalise les connexions (cascade <code>ipapi.co → ipinfo.io → ip-api.com</code>), alerte sur seuils et détecte les anomalies via une analyse nocturne. Multi-VPS, carte des connexions, IP protégées.</p>

    <h2 id="gnp-db">Database Manager</h2>
    <p>Provisionne MySQL/MariaDB automatiquement via SSH (ou se connecte à une instance existante), crée des bases dédiées par client, fournit une console SQL intégrée (DbLite : parcours, éditeur de lignes, recherche, import/export <code>.sql</code>), des quotas avec alertes et blocage des écritures, des sauvegardes (ZIP, restauration), des comptes secondaires RO/RW restreints par IP, et un journal d'activité.</p>

    <h2 id="gnp-securite">Sécurité GameNodePanel</h2>
    <ul>
      <li><strong>Tout via SSH</strong> (phpseclib) : les ports sensibles (ex. MySQL 3306) ne sont jamais exposés.</li>
      <li><strong>Secrets chiffrés</strong> en AES-256 (mots de passe SSH, root MySQL, bases, clés API).</li>
      <li><strong>Anti-injection</strong> : identifiants validés, valeurs échappées, commandes shell transmises en base64 sur stdin.</li>
      <li><strong>CSRF</strong> sur chaque action, héritée du socle Aegis.</li>
      <li>Bénéficie de tout l'arsenal du framework (rate limiting, firewall, Security Center).</li>
    </ul>

    <div class="doc-foot">
      <span>GameNodePanel · bâti sur Aegis Framework</span>
      <span><a href="../index.php">Retour au site ↗</a></span>
    </div>

<?php require __DIR__ . '/../../inc/foot.php'; ?>
