<?php
/**
 * documentation/modules/gne/doc-gne-comparatif.php — GameNodeEsport face à NeoFrag et Contentify.
 */
$docPage = 'modules/gne/doc-gne-comparatif.php';
$seo = [
    'title'     => 'GameNodeEsport face à NeoFrag & Contentify · Documentation Aegis Framework',
    'desc'      => "Comparatif factuel des trois CMS esport : GameNodeEsport, NeoFrag et Contentify. Technologies, maintenance, fonctionnalités, thèmes et modèle économique.",
    'canonical' => 'https://gamenodepanel.com/documentation/modules/gne/doc-gne-comparatif.php',
];
require __DIR__ . '/../../inc/head.php';
?>

    <h1>Face à NeoFrag &amp; Contentify</h1>
    <p class="doc-lead">Le CMS pour équipes esport est un domaine étroit : <strong>trois projets sérieux</strong> l'occupent. Voici ce qui les sépare, sur des faits vérifiables plutôt que sur des promesses.</p>
    <div class="doc-meta">
      <span class="doc-pill">Août 2026</span>
      <span class="doc-pill">Sources publiques</span>
    </div>

    <div class="callout"><span class="i">⚖️</span><div>
      Ce comparatif est écrit par l'éditeur de GameNodeEsport : lisez-le comme tel. Il s'en tient donc à des éléments <strong>vérifiables par vous-même</strong> — dépôts publics, sites officiels, versions annoncées — et signale honnêtement les points où les concurrents sont devant.
    </div></div>

    <h2 id="cmp-qui">Les trois projets</h2>
    <table class="doc-table">
      <tr><th></th><th>GameNodeEsport</th><th>NeoFrag</th><th>Contentify</th></tr>
      <tr><td><strong>Cible</strong></td><td>Équipes, teams et communautés multi-gaming</td><td>Équipes de jeu, associations sportives et esportives</td><td>Communautés et organisations esport</td></tr>
      <tr><td><strong>Nature</strong></td><td>Module d'Aegis Framework</td><td>CMS autonome</td><td>CMS autonome</td></tr>
      <tr><td><strong>Licence</strong></td><td>Propriétaire, gratuit</td><td>LGPL-3.0 / GPL-3.0</td><td>Open source</td></tr>
      <tr><td><strong>Version</strong></td><td>1.4.0</td><td><strong>Alpha 0.2.3</strong></td><td>3.0</td></tr>
    </table>

    <h2 id="cmp-tech">Les technologies</h2>
    <table class="doc-table">
      <tr><th></th><th>GameNodeEsport</th><th>NeoFrag</th><th>Contentify</th></tr>
      <tr><td>PHP minimum</td><td><strong>8.1+</strong></td><td>5.6+</td><td>Laravel 5 / 6</td></tr>
      <tr><td>Socle</td><td>Aegis Framework (maison)</td><td>Framework maison + Bootstrap</td><td>Laravel + Bootstrap + LESS</td></tr>
      <tr><td>Dépendances externes</td><td>Aucune bibliothèque tierce</td><td>curl, gd, intl, mbstring, mysqli, zip</td><td>L'écosystème Composer de Laravel</td></tr>
      <tr><td>Gabarits</td><td><strong>HTML pur, aucun PHP</strong></td><td>PHP</td><td>Blade (PHP)</td></tr>
    </table>
    <div class="callout"><span class="i">📌</span><div>
      <strong>Une nuance sur Laravel.</strong> Contentify s'appuie sur un framework grand public, ce qui est un vrai avantage : documentation abondante, développeurs faciles à trouver. Le revers est la dépendance à son cycle de vie — <strong>Laravel 5 et 6 ne sont plus maintenus</strong>, et une montée de version d'un CMS entier n'est pas un petit chantier.
    </div></div>

    <h2 id="cmp-maintenance">La maintenance — le point décisif</h2>
    <table class="doc-table">
      <tr><th></th><th>Dernière évolution publique</th><th>État</th></tr>
      <tr><td><strong>GameNodeEsport</strong></td><td>Août 2026</td><td>Développement actif</td></tr>
      <tr><td>NeoFrag</td><td>Release Alpha 0.2 annoncée en <strong>juin 2018</strong></td><td>Toujours en alpha, activité très réduite</td></tr>
      <tr><td>Contentify</td><td><strong>Avril 2023</strong></td><td>Stable, mais sans évolution récente</td></tr>
    </table>
    <div class="callout warn"><span class="i">🕰️</span><div>
      C'est l'écart le plus lourd de conséquences. Un CMS qui n'évolue plus <strong>ne reçoit plus de correctifs de sécurité</strong> — ni pour lui-même, ni pour le socle sur lequel il repose. NeoFrag n'a jamais quitté l'alpha en plus de dix ans d'existence ; Contentify n'a rien publié depuis 2023.
    </div></div>

    <h3 id="cmp-secu">Ce que « plus maintenu » implique concrètement</h3>
    <p>Trois faits publics, que vous pouvez vérifier vous-même :</p>
    <table class="doc-table">
      <tr><th>Fait</th><th>Conséquence</th></tr>
      <tr><td>NeoFrag annonce <strong>PHP 5.6</strong> comme version minimale</td><td>PHP 5.6 a atteint sa <strong>fin de vie le 31 décembre 2018</strong>. Il ne reçoit plus aucun correctif de sécurité depuis près de huit ans.</td></tr>
      <tr><td>Aucune release depuis <strong>l'Alpha 0.2, juin 2018</strong></td><td>Une faille découverte dans le CMS depuis cette date n'a, par construction, pas été corrigée en amont.</td></tr>
      <tr><td>Contentify repose sur <strong>Laravel 5 / 6</strong></td><td>Ces versions ne sont plus supportées. Les correctifs de sécurité du framework ne remontent plus jusqu'au CMS.</td></tr>
    </table>
    <div class="callout"><span class="i">🔐</span><div>
      Nous ne relayons <strong>aucune vulnérabilité précise</strong> : nous n'en avons pas vérifié, et propager une rumeur ne serait ni honnête ni utile. Le constat suffit — un logiciel exposé sur Internet, qui n'a pas été mis à jour depuis des années et s'appuie sur des socles en fin de vie, <strong>est un risque que vous acceptez en connaissance de cause</strong>. À vous d'évaluer s'il est tenable pour votre structure.
    </div></div>

    <h2 id="cmp-fonctions">Les fonctionnalités</h2>
    <p>Comparer des « nombres de modules » n'a pas de sens : les trois projets ne découpent pas leurs fonctions de la même façon. Voici plutôt les <strong>domaines couverts</strong>.</p>
    <table class="doc-table">
      <tr><th>Domaine</th><th>GameNodeEsport</th><th>NeoFrag</th><th>Contentify</th></tr>
      <tr><td>Équipes &amp; roster</td><td>✅</td><td>✅</td><td>✅</td></tr>
      <tr><td>Matchs &amp; calendrier</td><td>✅</td><td>✅</td><td>✅</td></tr>
      <tr><td>Statistiques &amp; courbe de forme</td><td>✅</td><td>Palmarès</td><td>✅</td></tr>
      <tr><td>Actualités &amp; pages</td><td>✅</td><td>✅</td><td>✅</td></tr>
      <tr><td>Recrutement &amp; candidatures</td><td>✅</td><td>✅</td><td>—</td></tr>
      <tr><td>Partenaires &amp; sponsors</td><td>✅</td><td>✅</td><td>✅</td></tr>
      <tr><td>Galerie photo</td><td>✅</td><td>✅</td><td>✅</td></tr>
      <tr><td>Forum</td><td>Module Aegis séparé</td><td><strong>✅ intégré</strong></td><td><strong>✅ intégré</strong></td></tr>
      <tr><td>Configurations PC des joueurs</td><td><strong>✅</strong></td><td>—</td><td>—</td></tr>
      <tr><td>Streamers Twitch en direct</td><td><strong>✅</strong></td><td>—</td><td>—</td></tr>
      <tr><td>Vidéos multi-plateformes</td><td><strong>✅</strong> (4 plateformes)</td><td>—</td><td>✅</td></tr>
      <tr><td>Sondages</td><td>✅</td><td>✅</td><td>✅</td></tr>
      <tr><td>Boutique e-commerce</td><td><strong>✅</strong> avec déclinaisons, transporteurs, suivi</td><td>—</td><td>—</td></tr>
      <tr><td>Dons &amp; cagnottes</td><td><strong>✅</strong></td><td>—</td><td>—</td></tr>
      <tr><td>Widgets par zone</td><td>✅</td><td>✅</td><td>✅</td></tr>
    </table>
    <div class="callout ok"><span class="i">🎯</span><div>
      <strong>Là où GameNodeEsport se distingue vraiment :</strong> la boutique e-commerce complète, les cagnottes de dons, les configurations PC des joueurs et l'intégration Twitch en direct. Ce sont quatre fonctions qu'aucun des deux autres ne propose, et qui correspondent à ce que fait réellement une équipe aujourd'hui — vendre son maillot, financer un déplacement, montrer son matériel, streamer.
    </div></div>
    <div class="callout"><span class="i">👐</span><div>
      <strong>Là où les autres sont devant :</strong> NeoFrag et Contentify intègrent un <strong>forum</strong>, là où GameNodeEsport demande d'activer un module séparé. NeoFrag propose aussi un module « jeux et cartes » qui n'a pas d'équivalent direct. Et Contentify, adossé à Laravel, sera plus familier à un développeur qui connaît déjà ce framework.
    </div></div>

    <h2 id="cmp-themes">Les thèmes</h2>
    <table class="doc-table">
      <tr><th></th><th>GameNodeEsport</th><th>NeoFrag</th><th>Contentify</th></tr>
      <tr><td>Thèmes livrés</td><td><strong>2 gratuits, complets</strong></td><td>Thèmes de base</td><td>Bootstrap personnalisable</td></tr>
      <tr><td>Thèmes premium</td><td>Payants (lavelia-studio.com)</td><td>Aucun — tout est gratuit</td><td>Aucun — tout est gratuit</td></tr>
      <tr><td>Personnalisation</td><td>Options déclarées — jusqu'à <strong>306 réglages</strong></td><td>Options de thème</td><td>Couleurs et LESS</td></tr>
      <tr><td>Téléverser un thème</td><td>ZIP, <strong>sans risque d'exécution</strong></td><td>ZIP</td><td>Fichiers</td></tr>
    </table>
    <div class="callout ok"><span class="i">🛡️</span><div>
      <strong>La différence de fond est là.</strong> Chez GameNodeEsport, un thème est du HTML et du JSON : la syntaxe ne sait ni comparer, ni calculer, ni appeler une fonction. <strong>Installer un thème téléchargé ne peut donc pas exécuter de code sur votre serveur.</strong> Chez les CMS dont les thèmes contiennent du PHP ou du Blade, téléverser un thème revient à faire confiance à son auteur.
    </div></div>

    <h2 id="cmp-prix">Le modèle économique</h2>
    <table class="doc-table">
      <tr><th></th><th>Le logiciel</th><th>Les thèmes</th></tr>
      <tr><td><strong>GameNodeEsport</strong></td><td><strong>Gratuit</strong> — son prédécesseur eSport-CMS était vendu 29,90 €</td><td>2 offerts et complets, premium payants</td></tr>
      <tr><td>NeoFrag</td><td>Gratuit, open source — « 100 % free » revendiqué</td><td><strong>Tout est gratuit</strong></td></tr>
      <tr><td>Contentify</td><td>Gratuit, open source</td><td><strong>Tout est gratuit</strong></td></tr>
    </table>
    <p>Les trois logiciels sont gratuits. NeoFrag et Contentify le sont <strong>intégralement</strong>, thèmes compris — c'est un avantage réel, et il faut le dire.</p>
    <div class="callout"><span class="i">💬</span><div>
      <strong>Le revers, qui n'en est pas un détail :</strong> un projet dont rien n'est vendu n'a pas de revenu pour financer sa maintenance. C'est précisément le point suivant. GameNodeEsport fait le choix inverse — le logiciel gratuit, quelques thèmes payants — pour que le développement continue d'être financé.
    </div></div>

    <h2 id="cmp-ecosysteme">Ce qu'aucun comparatif de fonctionnalités ne montre</h2>
    <p>GameNodeEsport n'est pas un CMS autonome : c'est un <strong>module</strong>. Sur la même installation, la même base d'utilisateurs et la même administration, vous pouvez activer :</p>
    <table class="doc-table">
      <tr><th>Module</th><th>Ce qu'il ajoute</th></tr>
      <tr><td><a href="modules/gnp/doc-gnp.php">GameNodePanel</a></td><td>L'exploitation réelle de serveurs de jeu</td></tr>
      <tr><td><a href="modules/gnh/doc-gnh.php">GameNodeHosting</a></td><td>La <strong>location</strong> de serveurs — la structure devient hébergeur</td></tr>
      <tr><td><a href="modules/forum/doc-forum.php">Forum</a></td><td>Les discussions de la communauté</td></tr>
      <tr><td>Tournaments</td><td>Tournois et brackets</td></tr>
      <tr><td><a href="modules/analytics/doc-analytics.php">Analytics</a></td><td>Mesure d'audience sans cookie</td></tr>
    </table>
    <div class="callout ok"><span class="i">🚀</span><div>
      Une équipe qui grandit peut donc <strong>ouvrir un service d'hébergement</strong> sans changer de site, sans seconde base d'utilisateurs et sans réécrire son identité visuelle. Ni NeoFrag ni Contentify n'ont d'équivalent : ce sont des CMS de site d'équipe, pas des socles.
    </div></div>

    <h2 id="cmp-choisir">Comment choisir</h2>
    <table class="doc-table">
      <tr><th>Votre situation</th><th>Le choix raisonnable</th></tr>
      <tr><td>Vous voulez un site d'équipe moderne, maintenu, avec boutique et dons</td><td><strong>GameNodeEsport</strong></td></tr>
      <tr><td>Vous voulez pouvoir héberger ou louer des serveurs plus tard</td><td><strong>GameNodeEsport</strong>, pour l'écosystème</td></tr>
      <tr><td>Le forum intégré est votre besoin numéro un et vous ne voulez rien activer d'autre</td><td>NeoFrag ou Contentify</td></tr>
      <tr><td>Votre équipe technique vit dans Laravel et veut tout modifier</td><td>Contentify</td></tr>
      <tr><td>Vous tenez à une licence GPL</td><td>NeoFrag</td></tr>
    </table>

    <h2 id="cmp-sources">Sources</h2>
    <ul>
      <li><a href="https://github.com/NeoFrag/neofrag" target="_blank" rel="noopener">github.com/NeoFrag/neofrag</a> — dépôt officiel : version, licence, prérequis</li>
      <li><a href="https://neofr.ag/fr/index" target="_blank" rel="noopener">neofr.ag</a> — site officiel : modules et modèle économique</li>
      <li><a href="https://github.com/Contentify/Contentify" target="_blank" rel="noopener">github.com/Contentify/Contentify</a> — dépôt officiel</li>
      <li><a href="https://www.contentify.org/" target="_blank" rel="noopener">contentify.org</a> — site officiel</li>
    </ul>
    <p class="u-muted"><small>Informations relevées en août 2026. Les projets évoluent : vérifiez les sources avant de décider.</small></p>

    <div class="doc-foot">
      <span>GameNodeEsport · comparatif</span>
      <span><a href="modules/gne/doc-gne.php">← Retour à la vue d'ensemble</a></span>
    </div>

<?php require __DIR__ . '/../../inc/foot.php'; ?>
