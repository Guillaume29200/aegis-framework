<?php
$docPage = 'modules/gnp/doc-database.php';
$seo = ['title' => 'Database Manager (technique) — Documentation · GameNodePanel', 'desc' => "Fonctionnement technique de Database Manager : provisioning MySQL via SSH, console DbLite, quotas, sauvegardes ZIP, comptes RO/RW restreints par IP.", 'canonical' => 'https://gamenodepanel.com/documentation/doc-database.php'];
require __DIR__ . '/../../inc/head.php';
?>
    <h1>Database Manager <span style="font-weight:500;color:var(--tx3);font-size:1rem">· technique</span></h1>
    <p class="doc-lead">Database Manager provisionne et gère MySQL/MariaDB via SSH. Détails internes ici ; présentation sur <a href="../pages/database-manager.php">la page Database Manager</a> du site.</p>

    <h2 id="db-provision">Provisioning</h2>
    <p>À l'ajout d'un serveur hôte, Database Manager <strong>installe MySQL/MariaDB automatiquement</strong> (apt/yum) ou se connecte à une instance existante. Il gère le cas fréquent où <strong>root n'est joignable qu'en local (socket)</strong> : repli automatique TCP → socket, syntaxes MySQL et MariaDB. Les bases dédiées sont créées avec identifiants générés et chiffrés.</p>
    <div class="callout"><span class="i">🔒</span><div>Tout passe par SSH (port 3306 jamais exposé). Identifiants validés par regex, valeurs échappées, requêtes transmises en <strong>base64 sur stdin</strong> (anti-injection).</div></div>

    <h2 id="db-dblite">Console DbLite</h2>
    <p>Un mini-gestionnaire SQL intégré, façon phpMyAdmin léger :</p>
    <ul>
      <li>Parcours des tables et de leur structure.</li>
      <li><strong>Éditeur de lignes</strong> (ajouter/modifier/supprimer sans écrire de SQL).</li>
      <li>Recherche dans une table (LIKE sur toutes les colonnes).</li>
      <li>Exécution de requêtes libres.</li>
      <li><strong>Import / export</strong> de dumps <code>.sql</code> (FK désactivées le temps de l'import).</li>
      <li>Suppression de table à l'unité ou en masse.</li>
    </ul>

    <h2 id="db-quota">Quotas</h2>
    <p>Limites par <strong>nombre de bases</strong> et par <strong>stockage</strong>, avec jauges visuelles. Alerte dès 80 %, et <strong>blocage des écritures</strong> (INSERT/UPDATE/CREATE…) au-delà du quota — la lecture et la libération d'espace restant possibles.</p>

    <h2 id="db-backup">Sauvegardes</h2>
    <ul>
      <li>Création d'une sauvegarde (mysqldump) en un clic.</li>
      <li><strong>Téléchargement en .zip</strong> (repli .sql si l'extension zip manque).</li>
      <li><strong>Restauration</strong> en un clic depuis l'historique.</li>
      <li>Suppression d'une sauvegarde. Journal d'activité par base.</li>
    </ul>

    <h2 id="db-users">Utilisateurs secondaires (RO/RW + IP)</h2>
    <p>Création de comptes MySQL supplémentaires par base : <strong>lecture seule</strong> (SELECT/SHOW VIEW/EXECUTE) ou <strong>lecture-écriture</strong> (ALL), restreints à un <strong>hôte autorisé</strong> (<code>%</code> = partout, ou une IP précise = whitelist d'accès distant). Mot de passe affiché une seule fois, stocké chiffré.</p>
    <div class="callout warn"><span class="i">⚠️</span><div>L'accès distant via <code>%</code>/IP suppose aussi que MySQL écoute sur le réseau et que le pare-feu l'autorise — sinon seul l'accès local fonctionne.</div></div>

    <?php require __DIR__ . '/../../inc/foot.php'; ?>
