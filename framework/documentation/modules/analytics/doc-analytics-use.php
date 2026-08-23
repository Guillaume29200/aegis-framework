<?php
$docPage = 'modules/analytics/doc-analytics-use.php';
$seo = [
    'title' => 'Analytics — Tableau de bord & objectifs · Documentation Aegis Framework',
    'desc'  => "Utiliser le module Analytics d'Aegis : tableau de bord (audience, pages, sources, appareils, géo), temps réel & visiteurs en ligne, objectifs/conversions, réglages, rétention & purge, réinitialisation et export.",
    'canonical' => 'https://gamenodepanel.com/documentation/doc-analytics-use.php',
];
require __DIR__ . '/../../inc/head.php';
?>
    <h1>Tableau de bord &amp; objectifs</h1>
    <p class="doc-lead">Une fois le module actif, tout se consulte depuis <strong>Configuration → Analytics</strong> : audience, rapports détaillés, temps réel, objectifs de conversion et réglages de confidentialité.</p>
    <div class="doc-meta"><span class="doc-pill">Tableau de bord</span><span class="doc-pill">Temps réel</span><span class="doc-pill">Objectifs</span><span class="doc-pill">Rétention</span></div>

    <h2 id="anu-dashboard">Tableau de bord &amp; rapports</h2>
    <p><code>/admin/analytics/dashboard</code> présente la vue d'audience sur une <strong>période choisie</strong>, avec une <strong>courbe temporelle</strong> (jour/heure) et des indicateurs clés :</p>
    <table class="doc-table">
      <tr><th>Indicateur</th><th>Signification</th></tr>
      <tr><td>Visiteurs &amp; vues</td><td>Visiteurs uniques (anonymes) et pages vues</td></tr>
      <tr><td>Sessions</td><td>Regroupement des vues d'un visiteur (30 min d'inactivité = nouvelle session)</td></tr>
      <tr><td>Durée moyenne &amp; taux de rebond</td><td>Engagement des visiteurs</td></tr>
    </table>
    <p>Et des classements (« rapports ») :</p>
    <ul>
      <li><strong>Pages</strong> les plus vues &amp; <strong>pages d'entrée</strong> ;</li>
      <li><strong>Sources</strong> de trafic &amp; <strong>référents</strong>, <strong>campagnes</strong> (paramètres UTM) ;</li>
      <li><strong>Échelle &amp; géo</strong> : pays, ainsi que <strong>appareil</strong>, <strong>navigateur</strong> et <strong>OS</strong> ;</li>
      <li><strong>Événements</strong> personnalisés.</li>
    </ul>

    <h2 id="anu-realtime">Temps réel &amp; visiteurs en ligne</h2>
    <p><code>/admin/analytics/realtime</code> montre l'activité <strong>en direct</strong> (mini-courbe des dernières minutes). Le module distingue aussi les <strong>membres / administrateurs actuellement connectés</strong>, pour savoir qui est en ligne en temps réel.</p>

    <h2 id="anu-goals">Objectifs &amp; conversions</h2>
    <p><code>/admin/analytics/goals</code> permet de définir des <strong>objectifs</strong> (ex. atteindre une page, déclencher un événement) et de mesurer leur <strong>taux de conversion</strong> sur la période. Vous créez, modifiez et supprimez les objectifs ; les statistiques (conversions, taux) s'affichent sur la période choisie.</p>
    <div class="callout"><span class="i">🎯</span><div>Un objectif relie un événement mesurable à un résultat business : inscription, achat, clic sur un bouton clé… C'est ce qui transforme des « visites » en « résultats ».</div></div>

    <h2 id="anu-settings">Réglages &amp; confidentialité</h2>
    <p><code>/admin/analytics/settings</code> centralise le comportement de collecte :</p>
    <table class="doc-table">
      <tr><th>Réglage</th><th>Effet (défaut)</th></tr>
      <tr><td><strong>Activé</strong></td><td>Active/coupe la collecte (le module peut rester installé mais inactif)</td></tr>
      <tr><td><strong>Suivre le staff</strong></td><td>Inclure ou non les visites des admins/modérateurs connectés (non par défaut)</td></tr>
      <tr><td><strong>Respecter Do Not Track</strong></td><td>Ignorer les visiteurs avec <code>DNT: 1</code> (oui par défaut)</td></tr>
      <tr><td><strong>Rétention (jours)</strong></td><td>Durée de conservation des données brutes — 0 à 3650, <strong>365</strong> par défaut</td></tr>
      <tr><td><strong>URLs exclues</strong></td><td>Chemins à ne pas mesurer (ex. zones privées)</td></tr>
      <tr><td><strong>IP exclues</strong></td><td>Adresses à ignorer (votre propre IP, par ex.)</td></tr>
    </table>

    <h2 id="anu-retention">Rétention, agrégation &amp; purge (cron)</h2>
    <p>Pour rester léger et conforme à la rétention, le module <strong>agrège</strong> les données par jour (table <code>analytics_daily</code>) et <strong>purge</strong> les événements bruts au-delà de la durée de conservation. C'est le rôle du cron <code>cron/analytics_rollup.php</code>, à planifier une fois par jour.</p>
    <pre><code># Rollup quotidien + purge selon la rétention (ex. 3h du matin)
0 3 * * * php /chemin/vers/modules/Analytics/cron/analytics_rollup.php</code></pre>
    <div class="callout"><span class="i">⚙️</span><div>Une purge manuelle est aussi disponible côté admin (action <em>prune</em>), utile pour appliquer immédiatement un changement de durée de rétention.</div></div>

    <h2 id="anu-reset">Réinitialisation &amp; export</h2>
    <ul>
      <li><strong>Export</strong> : <code>/admin/analytics/export</code> télécharge les données pour analyse externe.</li>
      <li><strong>Réinitialisation</strong> : l'action <em>reset</em> <strong>efface toutes les données</strong> de mesure (remise à zéro complète). Irréversible — à utiliser en connaissance de cause.</li>
    </ul>

    <?php require __DIR__ . '/../../inc/foot.php'; ?>
