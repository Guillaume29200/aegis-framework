<?php
$docPage = 'framework/doc-migrations.php';
$seo = ['title' => 'Migrations versionnées — Documentation · GameNodePanel', 'desc' => "Faire évoluer le schéma d'un module proprement avec les migrations versionnées d'Aegis Framework : création, exécution, bonnes pratiques.", 'canonical' => 'https://gamenodepanel.com/documentation/doc-migrations.php'];
require __DIR__ . '/../inc/head.php';
$code_tree = <<<'TXT'
MonModule/
└── database/
    ├── install.sql        # schéma initial (activation)
    ├── uninstall.sql      # nettoyage (désactivation)
    └── migrations/
        ├── 001_add_column_notes.sql
        ├── 002_create_table_logs.sql
        └── 003_index_on_created_at.sql
TXT;
$code_mig = <<<'SQL'
-- 001_add_column_notes.sql
ALTER TABLE `monmodule_items`
  ADD COLUMN `notes` VARCHAR(255) NULL AFTER `title`;
SQL;
?>
    <h1>Migrations versionnées</h1>
    <p class="doc-lead"><code>install.sql</code> crée le schéma initial. Pour le faire <strong>évoluer</strong> sans casser les installations existantes, on utilise des migrations numérotées.</p>

    <h2 id="mig-intro">Principe</h2>
    <p>Chaque changement de schéma postérieur à la v1 est un fichier SQL <strong>numéroté et ordonné</strong> dans <code>database/migrations/</code>. Aegis applique les migrations non encore exécutées et garde la trace de celles déjà passées — chaque migration ne s'exécute donc qu'une fois.</p>
    <pre class="tree"><?= $h($code_tree) ?></pre>

    <h2 id="mig-create">Créer une migration</h2>
    <p>Ajoutez un fichier préfixé d'un numéro croissant (ex. <code>001_</code>, <code>002_</code>…) décrivant le changement. Une migration = une intention claire.</p>
    <pre><code><?= $h($code_mig) ?></code></pre>
    <div class="callout"><span class="i">💡</span><div>Nommez explicitement : <code>003_index_on_created_at.sql</code> est plus parlant que <code>003.sql</code>. Le numéro garantit l'ordre d'application.</div></div>

    <h2 id="mig-run">Exécution</h2>
    <p>Les migrations en attente sont appliquées à la mise à jour du module (ou via l'outil de migration de l'admin). L'ordre est strictement croissant ; une migration déjà appliquée est ignorée.</p>

    <h2 id="mig-best">Bonnes pratiques</h2>
    <ul>
      <li><strong>Idempotence quand c'est possible</strong> : <code>CREATE TABLE IF NOT EXISTS</code>, <code>ADD COLUMN</code> prudents.</li>
      <li><strong>Petites migrations atomiques</strong> plutôt qu'un gros fichier fourre-tout.</li>
      <li>Ne jamais modifier une migration déjà publiée — créez-en une nouvelle.</li>
      <li>Tester sur une copie avant la production ; sauvegarder la base avant d'appliquer.</li>
      <li>Refléter le changement dans <code>install.sql</code> pour les nouvelles installations.</li>
    </ul>

    <?php require __DIR__ . '/../inc/foot.php'; ?>
