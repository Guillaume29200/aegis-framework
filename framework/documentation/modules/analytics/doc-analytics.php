<?php
$docPage = 'modules/analytics/doc-analytics.php';
$seo = [
    'title' => 'Analytics — Vue d\'ensemble · Documentation Aegis Framework',
    'desc'  => "Le module Analytics d'Aegis : analytics web auto-hébergé et sans cookie (façon Plausible), respectueux du RGPD. Fourni par défaut mais désactivable. Collecte, traceur, architecture et confidentialité.",
    'canonical' => 'https://gamenodepanel.com/documentation/doc-analytics.php',
];
require __DIR__ . '/../../inc/head.php';
?>
    <h1>Analytics — Vue d'ensemble</h1>
    <p class="doc-lead">Le module <strong>Analytics</strong> est une solution de mesure d'audience <strong>auto-hébergée et sans cookie</strong> (façon Plausible), intégrée à Aegis et <strong>respectueuse du RGPD</strong> : visiteurs, pages, sources, appareils, temps réel et objectifs — sans dépendre d'un service tiers.</p>
    <div class="doc-meta"><span class="doc-pill">Fourni par défaut</span><span class="doc-pill">Désactivable</span><span class="doc-pill">Sans cookie</span><span class="doc-pill">RGPD-friendly</span></div>

    <div class="callout"><span class="i">🧩</span><div><strong>Module « cœur » mais désactivable.</strong> Analytics est <strong>livré par défaut</strong> avec Aegis Framework, mais <strong>vous n'êtes pas obligé de l'utiliser</strong> : contrairement aux modules cœur non désactivables (<code>Auth</code>, <code>Configuration</code>, <code>System</code>), vous pouvez le <strong>désactiver</strong> depuis <code>Administration → Modules</code> si vous préférez Google Analytics, Matomo ou rien du tout. Il reste présent, prêt à être réactivé.</div></div>

    <h2 id="an-intro">Qu'est-ce que le module Analytics ?</h2>
    <p>Un outil de statistiques web qui collecte les visites <strong>sur votre propre serveur</strong> : aucune donnée n'est envoyée à un tiers, aucun cookie n'est posé. Il fournit un tableau de bord complet (audience, pages, sources, appareils, géo), un suivi <strong>temps réel</strong> et des <strong>objectifs / conversions</strong>.</p>
    <div class="callout ok"><span class="i">🎯</span><div>En bref : activé, il injecte un petit traceur sur vos pages publiques et alimente le tableau de bord <code>/admin/analytics/dashboard</code> (rubrique <strong>Configuration → Analytics</strong>).</div></div>

    <h2 id="an-why">Pourquoi « sans cookie » &amp; RGPD ?</h2>
    <p>Analytics n'utilise <strong>ni cookie, ni identifiant persistant</strong>. Les visiteurs sont distingués par un <strong>hash anonyme qui change chaque jour</strong> (dérivé d'un sel secret + signaux de la requête), ce qui empêche tout suivi inter-journalier ou inter-sites.</p>
    <table class="doc-table">
      <tr><th>Garantie</th><th>Détail</th></tr>
      <tr><td>Aucun cookie</td><td>Rien n'est stocké sur le navigateur du visiteur</td></tr>
      <tr><td>Hash visiteur rotatif</td><td>Identifiant anonyme régénéré chaque jour (sel + requête), non réversible</td></tr>
      <tr><td>Respect du <em>Do Not Track</em></td><td>Si le navigateur envoie <code>DNT: 1</code>, la visite n'est pas enregistrée (activable)</td></tr>
      <tr><td>Filtrage des bots</td><td>Les robots connus sont ignorés (<code>UserAgentParser</code>)</td></tr>
      <tr><td>Exclusions</td><td>Possibilité d'exclure le staff connecté, certaines URL et certaines IP</td></tr>
    </table>
    <div class="callout"><span class="i">🔒</span><div>Comme aucun cookie ni donnée personnelle persistante n'est utilisé, Analytics évite — dans la plupart des cas — la bannière de consentement requise par les solutions à cookies. (Vérifiez toujours votre obligation légale locale.)</div></div>

    <h2 id="an-collect">Comment la collecte fonctionne</h2>
    <p>Une fois le module actif, un traceur léger est injecté automatiquement dans les pages publiques :</p>
    <table class="doc-table">
      <tr><th>Point d'entrée</th><th>Rôle</th></tr>
      <tr><td><code>/analytics/a.js</code></td><td>Le script traceur (chargé en <code>defer</code>) qui envoie les vues de page</td></tr>
      <tr><td><code>/analytics/collect</code> (POST)</td><td>L'endpoint qui reçoit et enregistre les événements</td></tr>
      <tr><td><code>/analytics/pixel.gif</code></td><td>Fallback sans JavaScript (pixel de mesure)</td></tr>
    </table>
    <p>Côté serveur, chaque événement valide est filtré (bot / DNT / staff / exclusions) puis enregistré ; les <strong>sessions</strong> sont reconstituées (regroupement des vues d'un même visiteur avec un délai d'inactivité de 30 min).</p>

    <h2 id="an-archi">Architecture</h2>
    <table class="doc-table">
      <tr><th>Composant</th><th>Rôle</th></tr>
      <tr><td><code>CollectController</code></td><td>Traceur public : <code>a.js</code>, <code>collect</code>, <code>pixel.gif</code></td></tr>
      <tr><td><code>AdminController</code></td><td>Tableau de bord, données, temps réel, objectifs, réglages, réinitialisation</td></tr>
      <tr><td><code>AnalyticsService</code></td><td>Collecte, agrégation, rapports, objectifs, rétention</td></tr>
      <tr><td><code>UserAgentParser</code></td><td>Détection bots / navigateur / OS / appareil</td></tr>
      <tr><td><code>cron/analytics_rollup.php</code></td><td>Agrégation quotidienne &amp; purge selon la rétention</td></tr>
    </table>
    <p>Tables : <code>analytics_events</code> (événements bruts), <code>analytics_daily</code> (agrégats journaliers), <code>analytics_goals</code> (objectifs), <code>analytics_settings</code> (réglages + sel).</p>

    <h2 id="an-install">Installation &amp; activation</h2>
    <ol>
      <li>Le module est <strong>présent par défaut</strong> dans <code>/modules/Analytics</code>.</li>
      <li><code>Administration → Modules</code> : <strong>Activer</strong> (crée les tables <code>analytics_*</code>) ou <strong>Désactiver</strong> si vous utilisez une autre solution.</li>
      <li>Une fois actif, le lien <strong>Analytics</strong> apparaît dans le méga-menu <strong>Configuration</strong> et le traceur est injecté sur le site public.</li>
    </ol>
    <div class="callout"><span class="i">ℹ️</span><div>Désactiver le module retire son entrée de menu <strong>et</strong> arrête l'injection du traceur — aucune collecte ne se fait tant qu'il est désactivé.</div></div>

    <h2 id="an-secu">Sécurité &amp; confidentialité</h2>
    <ul>
      <li><strong>Données chez vous</strong> : auto-hébergé, aucun envoi à un tiers.</li>
      <li><strong>Anonymat</strong> : pas de cookie, hash visiteur rotatif non réversible, pas d'IP brute stockée comme identifiant.</li>
      <li><strong>CSRF</strong> sur les actions d'administration ; l'endpoint de collecte public est exempté (il ne mute pas de données admin).</li>
      <li><strong>SQL préparé</strong> &amp; sorties échappées ; endpoints du traceur en sortie « brute » terminée par <code>exit</code> (pas de pollution).</li>
    </ul>

    <?php require __DIR__ . '/../../inc/foot.php'; ?>
