<?php
/**
 * documentation/modules/gnh/doc-gnh-client.php — Espace client et panel de jeu.
 */
$docPage = 'modules/gnh/doc-gnh-client.php';
$seo = [
    'title'     => 'GameNodeHosting — Espace client & panel de jeu · Documentation Aegis Framework',
    'desc'      => "L'espace client de GameNodeHosting : serveurs, panel de gestion aux couleurs de l'hébergeur, configuration du jeu, extensions, V.E.G.A, gestionnaire de fichiers, factures, sessions et profil.",
    'canonical' => 'https://gamenodepanel.com/documentation/modules/gnh/doc-gnh-client.php',
];
require __DIR__ . '/../../inc/head.php';

$code_routes = <<<'TXT'
/hosting/mon-espace                          tableau de bord
/hosting/mon-espace/serveurs                 mes serveurs
/hosting/mon-espace/serveur/{id}             panel du serveur
/hosting/mon-espace/serveur/{id}/configuration
/hosting/mon-espace/serveur/{id}/extensions
/hosting/mon-espace/serveur/{id}/vega
/hosting/mon-espace/serveur/{id}/fichiers
/hosting/mon-espace/factures                 factures + renouvellement auto
/hosting/mon-espace/factures/{id}.pdf
/hosting/mon-espace/sessions                 historique de connexion
/hosting/mon-espace/informations             identité + mot de passe
/hosting/tickets                             support
TXT;
?>

    <h1>Espace client &amp; panel de jeu</h1>
    <p class="doc-lead">Le client ne met jamais les pieds dans l'administration de GameNodePanel. Il gère son serveur depuis un panel <strong>aux couleurs de l'hébergeur</strong>, qui n'expose que ce que l'hébergeur a décidé d'ouvrir.</p>
    <div class="doc-meta">
      <span class="doc-pill">Thème « panel »</span>
      <span class="doc-pill">Thème « gamepanel »</span>
      <span class="doc-pill">Lecture seule</span>
    </div>

    <h2 id="gnhp-espace">Les écrans</h2>
    <div class="tree"><?= $h($code_routes) ?></div>

    <h2 id="gnhp-principe">Le principe : afficher, ne pas agir</h2>
    <p>C'est la règle qui gouverne tout ce chapitre, et elle mérite d'être comprise avant le reste.</p>
    <table class="doc-table">
      <tr><th>Qui</th><th>Fait quoi</th></tr>
      <tr><td>GameNodeHosting</td><td><strong>Lit</strong> le schéma de GameNodePanel et <strong>affiche</strong> aux couleurs de l'hébergeur.</td></tr>
      <tr><td>GameNodePanel</td><td><strong>Agit</strong> : démarrer, arrêter, installer, envoyer une commande — et vérifie sur <strong>chacune</strong> de ses routes que le serveur appartient bien à la session.</td></tr>
    </table>
    <div class="callout ok"><span class="i">🛡️</span><div>Le contrôle de propriété fait par GameNodeHosting <strong>double</strong> celui de GameNodePanel, il ne le remplace pas. Il sert à ne pas afficher un écran que l'action refuserait ensuite. Le vrai garde reste chez celui qui exécute.</div></div>

    <h2 id="gnhp-permissions">Ce que le client a le droit de faire</h2>
    <p>Toutes les fonctions du panel ne se donnent pas : certaines <strong>coûtent</strong> (une analyse par intelligence artificielle), d'autres <strong>engagent</strong> (une réinstallation efface le serveur). L'hébergeur décide, depuis <em>Réglages → GamePanel</em> :</p>
    <table class="doc-table">
      <tr><th>Autorisation</th><th>Ce qu'elle ouvre</th><th>Pourquoi elle se refuse</th></tr>
      <tr><td>🤖 V.E.G.A</td><td>L'analyse de santé du serveur</td><td>Chaque analyse a un coût</td></tr>
      <tr><td>🧩 Marketplace</td><td>Le catalogue d'extensions et leur installation</td><td>Une extension modifie la machine</td></tr>
      <tr><td>♻️ Réinstallation</td><td>Remettre le serveur à neuf</td><td><strong>Efface tout</strong></td></tr>
    </table>
    <div class="callout"><span class="i">🏷️</span><div>Ces autorisations vivent chez l'hébergeur, <strong>pas</strong> chez GameNodePanel : c'est une décision commerciale, pas une règle technique. GameNodePanel n'a pas à connaître les réglages de la boutique.</div></div>

    <h2 id="gnhp-config">La configuration du jeu</h2>
    <p>GameNodePanel produit les données brutes — sections, champs, valeurs lues sur la machine. Un présentateur les range pour le gabarit, sans rien recalculer.</p>
    <div class="callout"><span class="i">🧩</span><div>Pourquoi un présentateur plutôt que de la logique dans le gabarit : le moteur de templates <strong>teste une valeur, il ne compare pas</strong>. Un champ de type liste doit donc arriver avec un drapeau <code>is_select</code>, et l'option retenue avec <code>selected</code>. Décider cela dans le gabarit exigerait un langage qu'il n'a pas — voir <a href="framework/doc-templating.php">Moteur de templates</a>.</div></div>
    <p>L'enregistrement se fait en <strong>POST</strong> : c'est une écriture sur la machine, elle ne doit pas se déclencher sur une simple visite d'URL.</p>

    <h2 id="gnhp-extensions">Les extensions</h2>
    <p>Le catalogue est celui de GameNodePanel (<code>gnp_marketplace_*</code>), lu et jamais écrit. Installer, mettre à jour ou retirer reste l'affaire de GameNodePanel, qui répond en JSON — c'est le thème qui appelle ses adresses.</p>
    <p>Pourquoi lire le catalogue ici plutôt que renvoyer le client vers la page de GameNodePanel : celle-ci <strong>exige l'en-tête d'administration</strong>. Un client qui l'ouvrait se retrouvait dans l'interface d'administration, ce qui n'est ni son écran ni sa place.</p>
    <p>Les trois actions sont déclarées en POST et <strong>avant</strong> la route du catalogue — le routeur retient la première route qui correspond.</p>

    <h2 id="gnhp-vega">V.E.G.A, côté client</h2>
    <p>V.E.G.A analyse les journaux d'un serveur et en tire un score, des incidents et des recommandations. Son écran d'administration expose bien davantage : approbation de motifs, historique, outillage. Un client n'a que faire de tout cela — il veut savoir <strong>si son serveur va bien, ce qui cloche, et quoi faire</strong>.</p>
    <div class="callout ok"><span class="i">🎯</span><div>Le service <strong>ne recalcule rien</strong> : il appelle ceux de V.E.G.A, qui sont publics. Réécrire leur calcul aurait produit <strong>deux scores pour un même serveur</strong> — le genre d'écart qu'on ne découvre qu'au pire moment.</div></div>
    <p>Le déclenchement d'une analyse est en <strong>POST</strong> : elle coûte, elle ne doit pas partir sur un préchargement de navigateur.</p>

    <h2 id="gnhp-fichiers">Le gestionnaire de fichiers</h2>
    <p>Accessible sous <code>/hosting/mon-espace/serveur/{id}/fichiers</code>, rendu par le thème. La route est déclarée <strong>avant</strong> celle du panel : un paramètre d'URL ne franchit pas les barres obliques, mais l'ordre de déclaration reste la règle à respecter.</p>

    <h2 id="gnhp-reinstall">La réinstallation</h2>
    <div class="callout warn"><span class="i">⚠️</span><div>
      La route d'administration de GameNodePanel agit <strong>dès un GET, sans jeton</strong>. Une adresse pareille se déclenche sur un simple préchargement de navigateur. GameNodeHosting <strong>ne reproduit pas cela pour un client</strong> : la réinstallation est exposée en POST uniquement.
    </div></div>

    <h2 id="gnhp-factures">Factures &amp; renouvellement</h2>
    <p>Le client retrouve toutes ses factures, les télécharge en PDF, et pilote le <strong>renouvellement automatique</strong> de chaque location depuis le même écran.</p>

    <h2 id="gnhp-sessions">L'historique des connexions</h2>
    <p>Lit <code>user_sessions</code>, alimentée par le module Auth. La table peut compter plusieurs centaines de lignes pour un compte ancien : tout afficher rendrait la page illisible et la requête coûteuse, d'où la pagination.</p>
    <div class="callout"><span class="i">🔎</span><div>La session en cours est repérée par <code>$_SESSION['session_id']</code>, <strong>pas</strong> par <code>session_id()</code> : le gestionnaire de sessions fait tourner l'identifiant PHP à intervalle régulier, alors que la colonne en base garde celui posé à la connexion. Utiliser le mauvais aurait affiché « aucune session courante ».</div></div>

    <h2 id="gnhp-profil">Le profil</h2>
    <p>Identité et mot de passe, chacun avec sa propre route POST. Le changement de mot de passe passe par les règles du module Auth — GameNodeHosting n'en réimplémente aucune.</p>

    <div class="doc-foot">
      <span>GameNodeHosting · espace client</span>
      <span><a href="modules/gnh/doc-gnh-site.php">Site public &amp; thèmes →</a></span>
    </div>

<?php require __DIR__ . '/../../inc/foot.php'; ?>
