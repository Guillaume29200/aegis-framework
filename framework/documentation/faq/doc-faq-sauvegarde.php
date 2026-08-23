<?php
/**
 * documentation/doc-faq-sauvegarde.php — FAQ du module Sauvegarde (Aegis Backup).
 * Deux niveaux : 🟢 Débutant · 🔵 Avancé.
 */
$docPage = 'faq/doc-faq-sauvegarde.php';
$seo = [
    'title'     => 'FAQ — Module Sauvegarde (Aegis Backup)',
    'desc'      => "Foire aux questions du module Sauvegarde d'Aegis : formats zip/tar.gz, chiffrement AES, sauvegarde BDD sélective, plannings cron, synchronisation cloud S3 et restauration. Deux niveaux Débutant / Avancé.",
    'canonical' => 'https://gamenodepanel.com/documentation/doc-faq-sauvegarde.php',
];
require __DIR__ . '/../inc/head.php';

$faq = [
  ['id'=>'sf-intro','icon'=>'💾','title'=>'Présentation','q'=>[
    ['d','À quoi sert le module Sauvegarde ?','À <strong>sauvegarder l\'ensemble du CMS</strong> : le framework, les modules de ton choix, les médias et la base de données. Il gère les sauvegardes manuelles, automatiques (cron), la synchronisation cloud et la restauration.'],
    ['d','Où le trouver dans l\'administration ?','Menu <strong>💾 Aegis Backup</strong> → Tableau de bord · Sauvegardes · Plannings · Cloud.'],
    ['d','Faut-il GameNodePanel ?','Non. C\'est un module Aegis autonome (il requiert seulement <code>Auth</code>). Il sauvegarde n\'importe quelle installation Aegis.'],
    ['a','Comment se lance une sauvegarde sans bloquer le site ?','Le build tourne dans un <strong>worker CLI détaché</strong> avec suivi de progression par polling — comme l\'installation Docker de GNP. Aucun timeout PHP même sur de gros sites.'],
  ]],
  ['id'=>'sf-formats','icon'=>'📦','title'=>'Formats & chiffrement','q'=>[
    ['d','Quels formats d\'archive ?','<strong>zip</strong> ou <strong>tar.gz</strong>, au choix à la création.'],
    ['d','Puis-je chiffrer une sauvegarde ?','Oui : coche « Chiffrer » et fournis un mot de passe. L\'archive est chiffrée en <strong>AES-256</strong>.'],
    ['a','Où est stocké le mot de passe de chiffrement ?','<strong>Nulle part.</strong> Il n\'est jamais enregistré en base : sans lui, l\'archive est irrécupérable. Le chiffrement se fait en flux (pas de limite mémoire) et chaque archive porte une empreinte SHA-256.'],
    ['a','Le tar.gz dépend-il du binaire tar ?','Non : il est produit via <code>PharData</code> (pur PHP), donc identique sous Windows (WAMP) et Linux.'],
  ]],
  ['id'=>'sf-scope','icon'=>'🗂️','title'=>'Périmètre & base de données','q'=>[
    ['d','Que puis-je inclure ?','Le <strong>cœur framework</strong> (framework/ + public/ + fichiers racine), les <strong>modules</strong> de ton choix, les <strong>médias/uploads</strong>, et la <strong>base de données</strong>.'],
    ['d','Je peux sauvegarder la BDD de certains modules seulement ?','Oui. Tu coches les modules voulus : seules <strong>leurs tables</strong> sont incluses. Cocher un module dans « Fichiers » coche automatiquement sa table.'],
    ['a','Comment le module sait quelles tables appartiennent à quel module ?','Les tables Aegis n\'ont pas de préfixe uniforme (<code>support_*</code>, <code>license_*</code>, <code>gnp_*</code>…). Le module construit une <strong>carte module → tables</strong> en analysant le <code>database/install.sql</code> de chaque module. L\'option « cœur » ajoute les tables non rattachées (users, sessions, settings…).'],
    ['a','Comment le dump SQL est-il généré ?','Via <code>mysqldump</code> s\'il est disponible, sinon un <strong>repli 100 % PHP</strong>. Le fichier désactive <code>FOREIGN_KEY_CHECKS</code> pour gérer les dépendances inter-modules.'],
  ]],
  ['id'=>'sf-manual','icon'=>'🆕','title'=>'Sauvegardes manuelles','q'=>[
    ['d','Comment créer une sauvegarde ?','Aegis Backup → <strong>Nouvelle sauvegarde</strong> : choisis format, périmètre, BDD (et chiffrement éventuel), puis lance. La liste affiche la progression en direct.'],
    ['d','Comment récupérer l\'archive ?','Bouton <strong>⬇️ Télécharger</strong> sur une sauvegarde terminée (téléchargement authentifié).'],
    ['a','Puis-je supprimer une vieille archive ?','Oui, bouton 🗑️ : le fichier et son enregistrement sont supprimés.'],
  ]],
  ['id'=>'sf-cron','icon'=>'⏰','title'=>'Plannings automatiques','q'=>[
    ['d','Puis-je programmer des sauvegardes automatiques ?','Oui, autant que tu veux : par exemple une <strong>hebdomadaire</strong> + une <strong>mensuelle</strong>, chacune avec son périmètre, son format et sa rétention.'],
    ['d','Dois-je configurer le serveur ?','Une <strong>seule</strong> tâche cron système est à poser une fois (la commande exacte Linux et Windows est affichée sur la page Plannings). Ensuite tu gères tout dans l\'interface, sans retoucher au cron.'],
    ['a','Comment fonctionne le runner ?','La tâche cron appelle <code>backup_runner.php</code> toutes les 5 min ; il exécute les plannings « dus » (verrou anti-chevauchement) et recalcule leur prochaine échéance.'],
    ['a','Qu\'est-ce que la rétention ?','Chaque planning ne conserve que les <strong>N dernières archives</strong> ; les plus anciennes sont purgées automatiquement (fichier + ligne BDD).'],
    ['a','Les sauvegardes planifiées sont-elles chiffrées ?','Non : aucun mot de passe n\'est saisi au moment de l\'exécution. Le chiffrement reste disponible pour les sauvegardes manuelles.'],
  ]],
  ['id'=>'sf-cloud','icon'=>'☁️','title'=>'Cloud (S3)','q'=>[
    ['d','Vers quels services puis-je envoyer mes sauvegardes ?','Tout stockage <strong>S3-compatible</strong> : AWS S3, Backblaze B2, Wasabi, Cloudflare R2, OVH, Scaleway, MinIO…'],
    ['d','Comment configurer une cible ?','Aegis Backup → <strong>Cloud</strong> : endpoint, région, bucket, clés d\'accès, préfixe, puis « 🔌 Tester » la connexion.'],
    ['a','Mes clés d\'accès sont-elles protégées ?','Oui, elles sont <strong>chiffrées (AES-256)</strong> en base et jamais réaffichées en clair.'],
    ['a','La synchronisation est-elle automatique ?','Au choix : bouton <strong>☁️ Sync</strong> par archive, ou automatique après chaque exécution d\'un planning ayant une cible cloud.'],
    ['a','Et Google Cloud / Google Drive ?','Google Cloud Storage fonctionne via son endpoint d\'interopérabilité S3 (clés HMAC). Un driver Google Drive natif (OAuth) pourra être ajouté ultérieurement.'],
  ]],
  ['id'=>'sf-restore','icon'=>'♻️','title'=>'Restauration','q'=>[
    ['d','Comment restaurer une sauvegarde ?','Bouton <strong>♻️ Restaurer</strong> sur une archive terminée : choisis fichiers et/ou base, confirme, et suis la progression.'],
    ['d','Est-ce risqué ?','C\'est une opération sensible (elle écrase l\'existant). Plusieurs garde-fous la sécurisent — voir ci-dessous. À tester de préférence sur un environnement de validation.'],
    ['a','Quels garde-fous pendant une restauration ?','1) passage automatique en <strong>mode maintenance</strong> (toujours désactivé en fin, même en cas d\'erreur) ; 2) <strong>sauvegarde de sécurité complète</strong> créée avant toute écriture ; 3) <strong>double confirmation</strong> (saisie de <code>RESTAURER</code>) ; 4) réservé au <strong>super-admin</strong>.'],
    ['a','La restauration supprime-t-elle des fichiers ?','Non : elle fonctionne en <strong>écrasement (merge)</strong> — elle remplace les fichiers présents dans l\'archive mais ne supprime pas ceux qui en sont absents. L\'import BDD se fait via le client <code>mysql</code> ou un repli PHP.'],
    ['a','Comment restaurer une archive chiffrée ?','Le mot de passe est demandé au moment de la restauration pour déchiffrer l\'archive (AES). Sans lui, impossible.'],
  ]],
  ['id'=>'sf-secu','icon'=>'🔒','title'=>'Sécurité & stockage','q'=>[
    ['d','Où sont stockées les sauvegardes ?','Dans <code>framework/storage/backups/</code>, un dossier <strong>verrouillé</strong> : aucun accès HTTP direct.'],
    ['a','Comment ce dossier est-il protégé ?','Triple protection <code>.htaccess</code> (deny-all) + <code>index.php</code> + <code>web.config</code>. Le téléchargement passe uniquement par un contrôleur <strong>authentifié super-admin</strong> qui streame le fichier — jamais d\'URL publique.'],
    ['a','Les archives contiennent des données sensibles : quelles précautions ?','Une sauvegarde contient config, hash de mots de passe, clés API… D\'où : dossier deny-all, chiffrement AES optionnel des archives, clés cloud chiffrées, et accès réservé au super-admin.'],
  ]],
];

$totalD = 0; $totalA = 0;
foreach ($faq as $t) { foreach ($t['q'] as $q) { $q[0] === 'd' ? $totalD++ : $totalA++; } }
$lvlMeta = ['d' => ['🟢 Débutant', 'green'], 'a' => ['🔵 Avancé', 'blue']];
?>

    <h1>FAQ — Module Sauvegarde</h1>
    <p class="doc-lead">Aegis Backup : sauvegarder, planifier, synchroniser dans le cloud et restaurer. Questions à deux niveaux.</p>
    <div class="doc-meta">
      <span class="doc-pill">🟢 <?= $totalD ?> Débutant</span>
      <span class="doc-pill">🔵 <?= $totalA ?> Avancé</span>
      <span class="doc-pill"><?= count($faq) ?> thèmes</span>
    </div>

    <div class="faq-tools">
      <div class="faq-filter" role="tablist">
        <button class="faq-flt active" data-lvl="all">Tout</button>
        <button class="faq-flt" data-lvl="d">🟢 Débutant</button>
        <button class="faq-flt" data-lvl="a">🔵 Avancé</button>
      </div>
      <input type="search" id="faqSearch" class="faq-search" placeholder="🔎 Rechercher une question…" autocomplete="off">
    </div>

    <?php foreach ($faq as $t): ?>
    <section class="faq-theme" id="<?= $t['id'] ?>">
      <h2 class="faq-theme-title"><?= $t['icon'] ?> <?= htmlspecialchars($t['title']) ?></h2>
      <?php foreach ($t['q'] as [$lvl, $q, $a]): $lm = $lvlMeta[$lvl]; ?>
      <details class="faq-item" data-lvl="<?= $lvl ?>" data-text="<?= htmlspecialchars(mb_strtolower($q . ' ' . strip_tags($a)), ENT_QUOTES) ?>">
        <summary>
          <span class="faq-q"><?= htmlspecialchars($q) ?></span>
          <span class="ui-badge <?= $lm[1] ?> faq-lvl"><?= $lm[0] ?></span>
        </summary>
        <div class="faq-a"><?= $a ?></div>
      </details>
      <?php endforeach; ?>
    </section>
    <?php endforeach; ?>
    <div class="faq-noresult" id="faqNoResult" style="display:none">Aucune question ne correspond.</div>

<style>
.faq-tools{display:flex;gap:12px;flex-wrap:wrap;align-items:center;justify-content:space-between;margin:18px 0 22px}
.faq-filter{display:inline-flex;gap:4px;background:var(--bg2);border:1px solid var(--bd);border-radius:10px;padding:4px}
.faq-flt{border:none;background:none;padding:7px 14px;border-radius:7px;font-weight:600;font-size:.85rem;color:var(--tx2);cursor:pointer;font-family:inherit}
.faq-flt:hover{color:var(--tx)}
.faq-flt.active{background:var(--ac);color:#fff}
.faq-search{flex:1;min-width:220px;max-width:420px;padding:9px 13px;border:1px solid var(--bd2);border-radius:9px;background:var(--bg2);color:var(--tx);font-size:.88rem;font-family:inherit}
.faq-search:focus{outline:none;border-color:var(--ac)}
.faq-theme-title{font-size:1.25rem;margin:30px 0 12px;padding-top:14px;border-top:1px solid var(--bd)}
.faq-item{border:1px solid var(--bd);border-radius:10px;margin-bottom:8px;background:var(--bg2);overflow:hidden}
.faq-item[open]{border-color:var(--ac)}
.faq-item summary{display:flex;align-items:center;gap:12px;padding:13px 16px;cursor:pointer;list-style:none;font-weight:600;color:var(--tx)}
.faq-item summary::-webkit-details-marker{display:none}
.faq-item summary::before{content:'▸';color:var(--ac);font-size:.9rem;transition:transform .2s;flex-shrink:0}
.faq-item[open] summary::before{transform:rotate(90deg)}
.faq-q{flex:1}
.faq-lvl{flex-shrink:0;font-size:10px}
.faq-a{padding:0 16px 15px 40px;color:var(--tx2);font-size:.9rem;line-height:1.65}
.faq-a code{font-size:.85em;background:var(--code-bg);border:1px solid var(--bd);padding:.05em .35em;border-radius:4px;color:var(--tx)}
.faq-a strong{color:var(--tx)}
.faq-noresult{padding:20px;text-align:center;color:var(--tx3)}
</style>
<script>
(function () {
    var flts = document.querySelectorAll('.faq-flt'),
        items = Array.prototype.slice.call(document.querySelectorAll('.faq-item')),
        themes = Array.prototype.slice.call(document.querySelectorAll('.faq-theme')),
        search = document.getElementById('faqSearch'),
        noRes = document.getElementById('faqNoResult'),
        curLvl = 'all';
    function apply() {
        var q = (search.value || '').trim().toLowerCase(), any = false;
        items.forEach(function (it) {
            var okLvl = curLvl === 'all' || it.getAttribute('data-lvl') === curLvl;
            var okTxt = !q || (it.getAttribute('data-text') || '').indexOf(q) !== -1;
            var show = okLvl && okTxt;
            it.style.display = show ? '' : 'none';
            if (show && q) it.open = true; else if (!q) it.open = false;
            if (show) any = true;
        });
        themes.forEach(function (th) {
            var visible = th.querySelectorAll('.faq-item:not([style*="display: none"])').length;
            th.style.display = visible ? '' : 'none';
        });
        if (noRes) noRes.style.display = any ? 'none' : 'block';
    }
    flts.forEach(function (b) {
        b.addEventListener('click', function () {
            flts.forEach(function (x) { x.classList.remove('active'); });
            b.classList.add('active'); curLvl = b.getAttribute('data-lvl'); apply();
        });
    });
    if (search) search.addEventListener('input', apply);
}());
</script>

<?php require __DIR__ . '/../inc/foot.php'; ?>
