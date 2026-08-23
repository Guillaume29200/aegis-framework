<?php
$docPage = 'framework/doc-package.php';
$seo = ['title' => 'Empaqueter & distribuer un module — Documentation · GameNodePanel', 'desc' => "Packager un module Aegis en ZIP, l'installer depuis l'administration et gérer ses mises à jour via migrations.", 'canonical' => 'https://gamenodepanel.com/documentation/doc-package.php'];
require __DIR__ . '/../inc/head.php';
$c_tree = <<<'TXT'
MonModule.zip
└── MonModule/                # le dossier porte le nom du module
    ├── module.json
    ├── MonModule.php
    ├── routes.php
    ├── changelog.json
    ├── Controllers/
    ├── Services/
    ├── Views/
    └── database/
        ├── install.sql
        ├── uninstall.sql
        └── migrations/
TXT;
$c_zip = <<<'BASH'
# Depuis le dossier parent du module
zip -r MonModule.zip MonModule -x "*/.git/*" "*/node_modules/*"
BASH;
?>
    <h1>Empaqueter &amp; distribuer</h1>
    <p class="doc-lead">Un module se partage sous forme d'archive ZIP. Aegis sait l'installer depuis l'administration, exécuter son schéma et l'enregistrer automatiquement.</p>

    <h2 id="pk-structure">Préparer le module</h2>
    <p>Vérifiez que le module est complet et cohérent avant de l'empaqueter :</p>
    <ul>
      <li><code>module.json</code> à jour (nom, version, <code>class</code>, <code>requires</code>).</li>
      <li>La version dans <code>module.json</code> correspond au dernier <code>changelog.json</code>.</li>
      <li><code>database/install.sql</code> reflète le schéma complet ; migrations à jour.</li>
      <li>Aucune donnée sensible / fichier temporaire embarqué.</li>
    </ul>
    <pre class="tree"><?= $h($c_tree) ?></pre>
    <div class="callout"><span class="i">📁</span><div>L'archive doit contenir le <strong>dossier du module</strong> (et non son contenu en vrac) — le nom du dossier = le nom du module.</div></div>

    <h2 id="pk-zip">Créer le ZIP</h2>
    <pre><code><?= $h($c_zip) ?></code></pre>
    <p>Sous Windows : clic droit sur le dossier <code>MonModule</code> → « Envoyer vers » → « Dossier compressé ».</p>

    <h2 id="pk-install">Installer le module</h2>
    <ol class="steps">
      <li>Administration → gestion des modules → <strong>Importer un ZIP</strong>.</li>
      <li>Aegis décompresse le module dans <code>modules/</code>.</li>
      <li>À l'activation : exécution de <code>install.sql</code> + hook <code>install()</code>, de façon <strong>atomique</strong> avec vérification des tables.</li>
      <li>Le menu déclaré dans <code>module.json</code> apparaît automatiquement.</li>
    </ol>

    <h2 id="pk-update">Mettre à jour</h2>
    <ul>
      <li>Incrémentez la <code>version</code> dans <code>module.json</code> et ajoutez une entrée dans <code>changelog.json</code>.</li>
      <li>Ajoutez les changements de schéma en <a href="framework/doc-migrations.php">migrations versionnées</a> (ne modifiez pas une migration publiée).</li>
      <li>Réimportez le ZIP : les migrations en attente s'appliquent, les données sont préservées.</li>
    </ul>
    <div class="callout ok"><span class="i">✅</span><div>Désactivation propre : <code>uninstall()</code> est appelé et, si présent, <code>uninstall.sql</code> nettoie. Sans <code>uninstall.sql</code>, les données restent.</div></div>

    <?php require __DIR__ . '/../inc/foot.php'; ?>
