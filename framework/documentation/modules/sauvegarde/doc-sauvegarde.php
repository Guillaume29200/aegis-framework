<?php
/**
 * documentation/doc-sauvegarde.php — Module Sauvegarde (Aegis Backup).
 */
$docPage = 'modules/sauvegarde/doc-sauvegarde.php';
$seo = [
    'title'     => 'Sauvegarde (Aegis Backup) — Documentation · GameNodePanel',
    'desc'      => "Le module Sauvegarde d'Aegis Framework : sauvegarde du framework, des modules et de la base de données (zip / tar.gz, chiffrement AES), plannings cron administrables, synchronisation cloud S3 et restauration avec garde-fous.",
    'canonical' => 'https://gamenodepanel.com/documentation/doc-sauvegarde.php',
];
require __DIR__ . '/../../inc/head.php';

$code_env = <<<'TXT'
GNP_SERVER_NAME, GNP_GAME_PORT…  (variables propres à chaque image)
TXT;

$code_config = <<<'JSON'
{
  "AutoStartWorldName": "",
  "Password": "",
  "Port": 8050,
  "MaxPlayers": 8
}
JSON;

$code_cron_linux = <<<'TXT'
*/5 * * * * /usr/bin/php /chemin/v4_classic/modules/Sauvegarde/cron/backup_runner.php >> /chemin/v4_classic/framework/storage/backups/.tmp/cron.log 2>&1
TXT;

$code_cron_win = <<<'TXT'
schtasks /Create /SC MINUTE /MO 5 /TN AegisBackup /TR "php.exe \"C:\wamp64\www\v4_classic\modules\Sauvegarde\cron\backup_runner.php\""
TXT;
?>

    <h1>Sauvegarde — Aegis Backup</h1>
    <p class="doc-lead">Le module <strong>Sauvegarde</strong> sauvegarde l'ensemble du CMS — framework, modules au choix, médias et base de données — au format <strong>zip</strong> ou <strong>tar.gz</strong>, avec chiffrement optionnel, plannings automatiques, synchronisation cloud et restauration sécurisée.</p>
    <div class="doc-meta">
      <span class="doc-pill">modules/Sauvegarde</span>
      <span class="doc-pill">menu : 💾 Aegis Backup</span>
      <span class="doc-pill">requires: Auth</span>
    </div>

    <h2 id="sv-intro">Présentation</h2>
    <p>Aegis Backup couvre tout le cycle de vie d'une sauvegarde, en quatre volets :</p>
    <ul>
      <li><strong>Sauvegarde manuelle</strong> — on choisit le format, le périmètre (framework, modules, médias) et la base de données.</li>
      <li><strong>Plannings automatiques</strong> — plusieurs tâches cron administrables (ex. une hebdomadaire + une mensuelle), avec rétention.</li>
      <li><strong>Synchronisation cloud</strong> — envoi des archives vers un stockage <strong>S3-compatible</strong>.</li>
      <li><strong>Restauration</strong> — remise en place d'une archive (fichiers et/ou BDD) avec garde-fous.</li>
    </ul>
    <div class="callout"><span class="i">🧭</span><div>Menu admin : <strong>💾 Aegis Backup → Tableau de bord · Sauvegardes · Plannings · Cloud</strong>. Le bouton « ♻️ Restaurer » se trouve sur chaque archive complétée.</div></div>

    <h2 id="sv-storage">Stockage protégé</h2>
    <p>Les archives sont stockées dans <code>framework/storage/backups/</code>, un dossier <strong>verrouillé</strong> : <code>.htaccess</code> (deny-all), <code>index.php</code> et <code>web.config</code> bloquent tout accès HTTP direct. Le téléchargement passe <strong>uniquement</strong> par un contrôleur authentifié (super-admin) qui streame le fichier — aucune URL publique.</p>

    <h2 id="sv-formats">Formats &amp; chiffrement</h2>
    <ul>
      <li><strong>zip</strong> (ZipArchive) ou <strong>tar.gz</strong> (PharData, pur PHP — pas besoin du binaire <code>tar</code>).</li>
      <li><strong>Chiffrement AES-256</strong> optionnel par mot de passe (flux, sans limite mémoire). Le mot de passe n'est <strong>jamais stocké</strong> : sans lui l'archive est irrécupérable.</li>
      <li>Chaque archive embarque un <code>backup-manifest.json</code> (date, version, périmètre, tables, format) + une empreinte <strong>SHA-256</strong>.</li>
    </ul>

    <h2 id="sv-scope">Périmètre (fichiers)</h2>
    <p>À la création, on coche ce qu'on veut inclure :</p>
    <ul>
      <li><strong>Cœur framework</strong> : <code>framework/</code> + <code>public/</code> + fichiers racine (exclut logs, cache et le dossier des sauvegardes lui-même).</li>
      <li><strong>Modules</strong> : sélection individuelle des dossiers de <code>modules/</code>.</li>
      <li><strong>Médias / uploads</strong>.</li>
    </ul>

    <h2 id="sv-db">Sauvegarde BDD sélective</h2>
    <p>Les tables n'ayant pas de préfixe uniforme dans Aegis (<code>support_*</code>, <code>license_*</code>, <code>gnp_*</code>…), le module construit une <strong>carte module → tables</strong> en analysant le <code>database/install.sql</code> de chaque module. Cocher un module dans « Fichiers » coche automatiquement sa table correspondante.</p>
    <ul>
      <li>Modules sélectionnés → uniquement leurs tables.</li>
      <li>Option « cœur » → toutes les tables non rattachées à un module (users, sessions, settings…).</li>
      <li>Dump via <code>mysqldump</code> si disponible, sinon repli 100 % PHP ; <code>FOREIGN_KEY_CHECKS=0</code> pour gérer les dépendances inter-modules.</li>
    </ul>

    <h2 id="sv-manual">Sauvegarde manuelle</h2>
    <p>Le build tourne dans un <strong>worker de fond</strong> (process CLI détaché) avec suivi de progression en direct (polling), exactement comme l'installation Docker de GNP — pas de timeout PHP même sur de gros sites. La liste des sauvegardes affiche statut, taille, format, et propose télécharger / restaurer / synchroniser / supprimer.</p>

    <h2 id="sv-cron">Plannings automatiques</h2>
    <p>On crée autant de <strong>plannings</strong> que voulu (quotidien / hebdomadaire / mensuel), chacun avec son périmètre, son format, sa rétention et sa cible cloud. Une <strong>seule</strong> tâche cron système est nécessaire : elle appelle le runner qui exécute les plannings « dus » (verrou anti-chevauchement). L'utilisateur ne touche plus jamais au crontab ensuite.</p>
    <p>🐧 Linux — à ajouter une fois dans <code>crontab -e</code> :</p>
    <pre><code><?= $h($code_cron_linux) ?></code></pre>
    <p>🪟 Windows — Planificateur de tâches (toutes les 5 min) :</p>
    <pre><code><?= $h($code_cron_win) ?></code></pre>
    <div class="callout"><span class="i">♻️</span><div>Chaque planning applique sa <strong>rétention</strong> : seules les N dernières archives sont conservées (les plus anciennes sont purgées, fichier + ligne BDD).</div></div>

    <h2 id="sv-cloud">Synchronisation cloud (S3)</h2>
    <p>Le module envoie les archives vers un stockage <strong>S3-compatible</strong> via un driver pur PHP (AWS Signature V4, upload en flux) : AWS S3, Backblaze B2, Wasabi, Cloudflare R2, OVH, Scaleway, MinIO… Les clés d'accès sont <strong>chiffrées (AES-256)</strong>. On configure une cible (endpoint, région, bucket, clés, préfixe, path-style), on teste la connexion, puis on synchronise manuellement une archive ou automatiquement à chaque exécution d'un planning.</p>

    <h2 id="sv-restore">Restauration</h2>
    <p>Depuis la liste des sauvegardes, « ♻️ Restaurer » permet de remettre en place une archive (fichiers et/ou base). L'opération est entourée de <strong>garde-fous</strong> :</p>
    <ol class="steps">
      <li>Passage automatique en <strong>mode maintenance</strong> (toujours désactivé en fin, même en cas d'erreur).</li>
      <li>Création d'une <strong>sauvegarde de sécurité complète</strong> avant toute écriture.</li>
      <li>Déchiffrement si l'archive est chiffrée (mot de passe demandé).</li>
      <li><strong>Double confirmation</strong> (saisie de <code>RESTAURER</code>), réservé super-admin.</li>
      <li>Restauration fichiers en écrasement (ne supprime pas l'existant absent de l'archive) + import SQL (client <code>mysql</code> ou repli PHP).</li>
    </ol>
    <div class="callout"><span class="i">⚠️</span><div>La restauration écrase le code en cours d'exécution : à tester sur un environnement de validation. La sauvegarde de sécurité créée juste avant permet de revenir en arrière.</div></div>

    <h2 id="sv-secu">Sécurité</h2>
    <ul>
      <li>Dossier de stockage <strong>deny-all</strong> + téléchargement authentifié (super-admin).</li>
      <li>Chiffrement AES-256 optionnel des archives ; clés cloud chiffrées.</li>
      <li>Mots de passe (archive, secret S3) <strong>jamais exposés</strong> en clair côté client.</li>
      <li>Restauration réservée au super-admin avec double confirmation et sauvegarde de sécurité.</li>
    </ul>

    <div class="doc-foot">
      <span>Module Sauvegarde · Aegis Backup</span>
      <span><a href="<?= $h($github) ?>" target="_blank" rel="noopener">Dépôt GitHub ↗</a></span>
    </div>

<?php require __DIR__ . '/../../inc/foot.php'; ?>
