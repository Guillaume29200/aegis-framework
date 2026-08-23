<?php
/**
 * documentation/modules/gne/doc-gne-themes.php — Les thèmes de GameNodeEsport.
 */
$docPage = 'modules/gne/doc-gne-themes.php';
$seo = [
    'title'     => 'GameNodeEsport — Les thèmes · Documentation Aegis Framework',
    'desc'      => "Les thèmes de GameNodeEsport : deux thèmes gratuits livrés, des thèmes premium sur lavelia-studio.com, options déclarées en meta.json, repli par gabarit et téléversement sécurisé.",
    'canonical' => 'https://gamenodepanel.com/documentation/modules/gne/doc-gne-themes.php',
];
require __DIR__ . '/../../inc/head.php';
?>

    <h1>Les thèmes</h1>
    <p class="doc-lead">Un site d'équipe se juge d'abord à l'œil. GameNodeEsport livre <strong>deux thèmes gratuits</strong> complets, et propose des <strong>thèmes premium</strong> pour ceux qui veulent se démarquer — c'est le seul élément payant du module.</p>
    <div class="doc-meta">
      <span class="doc-pill">2 thèmes offerts</span>
      <span class="doc-pill">HTML sans PHP</span>
      <span class="doc-pill">31 gabarits</span>
    </div>

    <h2 id="gnet-modele">Le modèle</h2>
    <div class="callout ok"><span class="i">🎁</span><div>
      <strong>Le module est gratuit, les thèmes premium sont payants.</strong> Vous pouvez monter un site complet et le faire vivre sans dépenser un centime : les deux thèmes livrés couvrent l'intégralité des pages. Les thèmes premium s'achètent sur <a href="https://lavelia-studio.com" target="_blank" rel="noopener">lavelia-studio.com</a>.
    </div></div>

    <h2 id="gnet-livres">Les thèmes livrés</h2>
    <table class="doc-table">
      <tr><th>Thème</th><th>Style</th><th>Accès</th></tr>
      <tr><td>🎮 <strong>Gratuit</strong></td><td>Thème eSport sombre, typographie massive. C'est aussi le thème de <strong>repli</strong> du module.</td><td>Livré, actif par défaut</td></tr>
      <tr><td>⛏️ <strong>BlockCraft</strong></td><td>Ambiance bloc et grotte, pensé pour les communautés Minecraft. Bannière et fond <strong>téléversables</strong>.</td><td>Livré, offert</td></tr>
    </table>
    <p>Les deux couvrent les <strong>31 gabarits</strong> du module : aucune page ne tombe en repli, et rien ne manque à l'installation.</p>

    <h2 id="gnet-premium">Les thèmes premium</h2>
    <table class="doc-table">
      <tr><th>Thème</th><th>Style</th></tr>
      <tr><td>🛡️ <strong>Aegis</strong></td><td>Coque sombre à trois colonnes, navigation latérale à icônes, allure tableau de bord.</td></tr>
      <tr><td>⚡ <strong>Arena</strong></td><td>Thème clair tourné vers le direct : bandeau supérieur, cartes, mise en avant du live.</td></tr>
      <tr><td>👑 <strong>GoldGaming</strong></td><td>Or et noir, bandeau pleine largeur, carrousels, doubles colonnes latérales. Le plus richement paramétrable : <strong>306 options</strong>.</td></tr>
    </table>
    <div class="callout"><span class="i">🎚️</span><div>Ce que « premium » signifie concrètement : GoldGaming expose <strong>306 réglages répartis en 31 groupes</strong> — couleurs, blocs, carrousels, colonnes, bannières téléversables. Deux sites sous le même thème peuvent être méconnaissables.</div></div>

    <h2 id="gnet-html">Un thème est du HTML, jamais du PHP</h2>
    <p>C'est la règle qui rend un thème <strong>sûr à téléverser</strong> : la syntaxe des gabarits ne sait ni comparer, ni calculer, ni appeler une fonction. Installer un thème ne peut donc pas exécuter de code sur votre serveur.</p>
    <p>Tout ce qui relève d'une décision est préparé en PHP et arrive dans les données — y compris les libellés de dates, les pluriels, les scores et jusqu'aux <strong>coordonnées des courbes de forme</strong>. Voir <a href="framework/doc-templating.php">Moteur de templates &amp; thèmes publics</a>.</p>

    <h2 id="gnet-options">Les options d'un thème</h2>
    <p>Un thème déclare ses réglages dans son <code>meta.json</code> ; l'administration construit le formulaire à partir de cette déclaration. Aucun écran n'est écrit pour un thème en particulier.</p>
    <p>Huit types sont disponibles : <code>toggle</code>, <code>select</code>, <code>color</code>, <code>text</code>, <code>number</code>, <code>textarea</code>, <code>links</code> et <code>image</code>.</p>
    <div class="callout"><span class="i">🖼️</span><div>Le type <code>image</code> permet à l'administrateur de téléverser une bannière ou un fond depuis l'écran d'options. Sans fichier joint, <strong>la valeur en place est conservée</strong> — enregistrer un formulaire ne vide pas l'image existante.</div></div>
    <p>Les valeurs sont rangées par thème : changer de thème puis revenir <strong>retrouve ses réglages</strong>.</p>

    <h2 id="gnet-repli">Le repli est par gabarit, pas par thème</h2>
    <p>Un gabarit absent du thème actif est cherché dans le thème <strong>Gratuit</strong>. La page reçoit alors l'en-tête et le pied du thème actif, autour d'un corps écrit dans le vocabulaire de classes <code>gne-*</code>.</p>
    <div class="callout warn"><span class="i">⚠️</span><div>
      <strong>Si le thème actif n'habille pas ces classes, le corps sort sans aucun style.</strong> C'est ce qui rendait cinq pages illisibles sur un thème incomplet, dont la confirmation de commande. Un thème partiel doit donc skinner le vocabulaire <code>gne-*</code> dans sa feuille de style.
    </div></div>
    <p>BlockCraft a été bâti ainsi : livré au départ avec trois gabarits seulement mais une feuille qui habillait tout le vocabulaire, il était correct dès l'installation. Chaque gabarit écrit ensuite a remplacé un repli sans rien casser — et sa feuille garde ce skin, qui protège les pages que le module ajouterait demain.</p>

    <h2 id="gnet-coque">Le piège de la coque</h2>
    <div class="callout"><span class="i">📐</span><div>
      Une grille à deux colonnes doit <strong>se replier quand la colonne n'est pas rendue</strong>, sinon un vide reste à droite. Chaque thème a sa parade — <code>:not(:has(...))</code>, une classe <code>is-full</code>, une classe <code>content-full</code>. À vérifier sur toute nouvelle page d'un thème que vous créez.
    </div></div>

    <h2 id="gnet-installer">Installer un thème</h2>
    <p>Depuis <em>GameNode eSport → Thèmes</em> : activation, téléversement par ZIP, suppression, et l'écran d'options du thème actif. Une documentation de création est fournie et téléchargeable depuis cet écran.</p>
    <p>L'archive est refusée si elle contient une extension non autorisée — <strong>aucun type exécutable n'est accepté</strong> — ou un chemin remontant.</p>

    <h2 id="gnet-structure">Structure d'un thème</h2>
    <div class="tree">themes/mon-theme/
├── meta.json        identité + options déclarées
├── header.html      footer.html      home.html
├── ... les 31 gabarits
├── preview.png      la vignette montrée en administration
└── assets/
    ├── css/         js/
    └── images/      uploads/</div>
    <p>Un dossier, une clé, rien qui traîne ailleurs : le thème se zippe, se partage et s'installe tel quel.</p>

    <div class="doc-foot">
      <span>GameNodeEsport · thèmes</span>
      <span><a href="modules/gne/doc-gne-comparatif.php">Comparatif →</a></span>
    </div>

<?php require __DIR__ . '/../../inc/foot.php'; ?>
