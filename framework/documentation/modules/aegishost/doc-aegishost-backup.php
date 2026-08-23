<?php
/**
 * documentation/modules/aegishost/doc-aegishost-backup.php — AegisHost, partie 5 : les sauvegardes.
 */
$docPage = 'modules/aegishost/doc-aegishost-backup.php';
$seo = [
    'title'     => 'AegisHost — Partie 5 : les sauvegardes · Documentation',
    'desc'      => "Sauvegarder un serveur avec AegisHost : sites, bases, données des conteneurs et configuration ; restauration pièce par pièce avec filet de sécurité, planification par minuterie systemd et copie hors-site chiffrée vers un stockage S3.",
    'canonical' => 'https://gamenodepanel.com/documentation/modules/aegishost/doc-aegishost-backup.php',
];
require __DIR__ . '/../../inc/head.php';

$code_layout = <<<'TXT'
/var/backups/aegishost/2026-08-13_2340/
├── manifeste.json
├── sites/
│   └── exemple.com.tar.gz
├── bases/
│   └── exemple.sql.gz
├── docker/
│   └── mc-srv.tar.gz
└── config.tar.gz
TXT;

$code_safety = <<<'TXT'
# Ce qui est remplacé n'est pas détruit, il est déplacé
/var/www/exemple.com/public.avant-restauration-20260813-234512/
/var/backups/aegishost/avant-restauration/exemple-20260813-234512.sql.gz
TXT;

$code_timer = <<<'TXT'
# Vérifier la minuterie depuis le serveur
systemctl list-timers aegishost-backup.timer
systemctl status aegishost-backup.service
TXT;
?>

    <h1>AegisHost — Partie 5 : les sauvegardes</h1>
    <p class="doc-lead">Sauvegarder les sites, les bases, les données des conteneurs et la configuration du serveur. Puis les <strong>restaurer</strong> — parce qu'une sauvegarde qu'on ne sait pas restaurer ne vaut rien.</p>
    <div class="doc-meta">
      <span class="doc-pill">menu : 💾 Sauvegardes</span>
      <span class="doc-pill">local + hors-site</span>
      <span class="doc-pill">S3 chiffré</span>
    </div>

    <style>
    .ah-states{width:100%;border-collapse:collapse;margin:14px 0;font-size:.89rem}
    .ah-states th,.ah-states td{border:1px solid var(--bd);padding:9px 12px;text-align:left;vertical-align:top}
    .ah-states th{background:var(--bg3);font-weight:700;font-size:.83rem}
    .ah-states td:first-child{white-space:nowrap;font-weight:700}
    </style>

    <h2 id="a5-intro">Ce qui est sauvegardé</h2>
    <p>Quatre familles, cochées une par une. <strong>Rien n'est coché d'avance</strong>, et c'est délibéré : une sauvegarde « tout par défaut » remplit le disque, et personne ne s'en aperçoit avant le jour où il est plein.</p>
    <ul>
      <li><strong>🌍 Fichiers des sites</strong> — le contenu servi : thèmes, extensions, fichiers déposés.</li>
      <li><strong>🗄️ Bases de données</strong> — un export par base. Sans elles, restaurer les fichiers d'un WordPress ne rend rien : articles, comptes et réglages vivent en base.</li>
      <li><strong>🐳 Données des conteneurs</strong> — les dossiers partagés : monde Minecraft, automatisations n8n, historique Uptime Kuma, configuration TeamSpeak.</li>
      <li><strong>⚙️ Configuration du serveur</strong> — hôtes virtuels, pools PHP-FPM, certificats. De quoi remonter un serveur identique sur une machine neuve.</li>
    </ul>

    <p>Chaque élément est affiché avec <strong>son poids</strong>, pour que le choix se fasse en connaissance de cause. Cet inventaire arrive une seconde après la page : il mesure des dossiers, et faire attendre l'écran entier pour des chiffres qui arriveront seuls n'aurait servi personne.</p>

    <h2 id="a5-form">La forme d'une sauvegarde</h2>
    <p>Une sauvegarde est un <strong>dossier</strong>, pas une archive unique. Chaque élément a son fichier :</p>
    <pre><code><?= $h($code_layout) ?></code></pre>

    <p>Ce choix sert deux fois : restaurer <strong>un</strong> site sans dérouler quarante gigaoctets, et envoyer les pièces une à une hors-site sans tout retransmettre.</p>

    <div class="callout"><span class="i">🔒</span><div>Le dossier est en <code>700</code>, les fichiers en <code>600</code>. Une sauvegarde contient les mots de passe de vos bases et les clés privées de vos certificats : c'est le point le plus sensible de la machine.</div></div>

    <h2 id="a5-run">Lancer une sauvegarde</h2>
    <p><strong>💾 Sauvegardes → ➕ Nouvelle sauvegarde.</strong> Cochez, ajoutez une <strong>note</strong> — « avant la mise à jour de WordPress » — et lancez.</p>

    <p>Deux garde-fous méritent d'être connus, parce qu'ils peuvent vous <em>refuser</em> une sauvegarde :</p>
    <ul>
      <li><strong>La place disponible est exigée à hauteur de la taille brute.</strong> C'est volontairement pessimiste : le taux de compression ne se connaît qu'après coup, et le supposer reviendrait à parier le disque du serveur. Remplir <code>/var</code> met <strong>tous</strong> vos sites hors ligne — bien pire que refuser une sauvegarde.</li>
      <li><strong>La purge des anciennes passe après.</strong> Supprimer une ancienne pour faire de la place à une nouvelle reviendrait à jeter la seule copie valide au moment précis où la nouvelle échoue.</li>
    </ul>

    <h2 id="a5-states">Les trois états</h2>
    <p>La nuance n'est pas cosmétique : elle décide de ce qu'on peut faire de l'archive.</p>

    <table class="ah-states">
      <tr><th>État</th><th>Ce que ça veut dire</th></tr>
      <tr><td>✅ Complète</td><td>Tous les éléments demandés ont été écrits. Elle se restaure.</td></tr>
      <tr><td>⚠️ Partielle</td><td>Un élément au moins a échoué. Le reste est utilisable ; l'écran dit lequel manque et pourquoi. Les pièces absentes ne sont pas proposées à la restauration — elles n'existent pas dans l'archive.</td></tr>
      <tr><td>⛔ Interrompue</td><td>La sauvegarde n'est jamais allée à son terme : son manifeste manque, on ignore ce qu'elle contient. <strong>Elle ne se restaure pas</strong> et n'offre aucun bouton.</td></tr>
    </table>

    <p>Présenter les deux dernières comme valides serait le mensonge le plus coûteux de ce panneau : on ne s'en aperçoit qu'au moment de s'en servir, c'est-à-dire au pire moment.</p>

    <h2 id="a5-restore">Restaurer</h2>
    <p>Chaque archive porte un bouton <strong>♻️ Restaurer</strong>. On choisit pièce par pièce ce qui doit revenir — rien n'est restauré par défaut.</p>

    <div class="callout ok"><span class="i">🛟</span><div><strong>Rien n'est écrasé sans être mis de côté d'abord.</strong> C'est la règle qui gouverne toute l'opération, et c'est ce qui permet d'oser s'en servir le jour où ça compte.</div></div>

    <pre><code><?= $h($code_safety) ?></code></pre>

    <p>Deux corollaires :</p>
    <ul>
      <li>Si l'extraction échoue, <strong>l'état précédent revient tout seul</strong>. Une restauration ratée ne laisse pas un site sans fichiers.</li>
      <li>Si l'export préalable d'une base échoue, <strong>la base n'est pas écrasée</strong>. Restaurer sans pouvoir revenir en arrière ne se rattrape jamais.</li>
    </ul>

    <p>Selon la pièce :</p>
    <ul>
      <li><strong>Un site</strong> — ses fichiers actuels sont renommés à côté d'eux-mêmes, puis l'archive est déballée à leur place.</li>
      <li><strong>Une base</strong> — exportée avant d'être réécrite entièrement.</li>
      <li><strong>Un conteneur</strong> — il est <strong>arrêté</strong> le temps de l'opération, puis redémarré. Réécrire des fichiers sous un programme qui les tient ouverts donne un monde Minecraft à moitié d'hier : c'est-à-dire corrompu.</li>
      <li><strong>La configuration</strong> — jamais réappliquée automatiquement. Elle est déballée <em>à côté</em>, et l'écran dit où.</li>
    </ul>

    <div class="callout warn"><span class="i">⚠️</span><div>Pourquoi la configuration n'est pas réappliquée : réécrire hôtes virtuels, pools PHP-FPM et certificats sur un serveur qui tourne mettrait tous les sites hors ligne d'un coup — <strong>y compris celui qui porte ce panneau</strong>, donc sans moyen de revenir en arrière depuis l'écran. Un site restauré se répare ; un serveur web qui ne démarre plus se répare en SSH.</div></div>

    <p>Le mot <strong>RESTAURER</strong> doit être tapé pour confirmer. Ce n'est pas une sécurité contre un appelant hostile — il l'écrirait aussi — c'est une sécurité contre le geste trop rapide, sur l'écran qui remplace des données vivantes.</p>

    <h2 id="a5-auto">Sauvegardes automatiques</h2>
    <p>Une sauvegarde qu'il faut penser à lancer est une sauvegarde qu'on n'aura pas le jour où elle compte.</p>
    <p>Depuis l'écran de sélection, le bouton <strong>🕰️ Planifier</strong> enregistre exactement le même choix et le rejoue <strong>chaque nuit</strong> ou <strong>chaque lundi</strong>, à l'heure voulue. C'est le <em>même code</em> qui tourne : mêmes validations, même garde-fou de place. Il n'y a pas de chemin automatique distinct qui pourrait diverger sans qu'on s'en aperçoive.</p>

    <p>L'écran affiche l'état <strong>réel</strong> de la minuterie, relu sur la machine : dernière exécution, prochaine, et le sort du dernier passage. Une minuterie coupée en console apparaît comme inactive — dire « planifié » sur la foi d'un fichier qu'on a écrit soi-même ne prouve rien.</p>

    <div class="callout warn"><span class="i">🔔</span><div>Quand le dernier passage a échoué, l'écran le <strong>crie</strong>. Une planification qui échoue chaque nuit en silence est pire que pas de planification du tout : on se croit couvert.</div></div>

    <p>Machine éteinte à l'heure dite ? Le tour est rattrapé au démarrage suivant plutôt que sauté. Depuis le serveur, la minuterie s'inspecte comme n'importe quelle autre :</p>
    <pre><code><?= $h($code_timer) ?></code></pre>

    <h2 id="a5-remote">La copie hors-site</h2>
    <p>Voici le point le plus important de cette page.</p>

    <div class="callout warn"><span class="i">📍</span><div><strong>Une sauvegarde posée sur la machine qu'elle protège n'est pas une sauvegarde.</strong> Elle vous protège de l'erreur de manipulation — un site supprimé, une base vidée, une mise à jour ratée — et c'est déjà l'essentiel de ce qui arrive. Elle ne protège pas de la panne de disque, du VPS résilié, ni d'un rançongiciel.</div></div>

    <p><strong>💾 Sauvegardes → ☁️ Copie hors-site.</strong> N'importe quel stockage compatible <strong>S3</strong> convient : Scaleway, Backblaze B2, OVH, Wasabi, un MinIO chez vous, AWS. Ce n'est pas un choix d'éditeur mais de dialecte — vous n'êtes attaché à personne, et changer de fournisseur ne demande que de changer quelques champs.</p>

    <p>Quatre renseignements : l'<strong>adresse du service</strong> (vide pour AWS), la <strong>région</strong>, le <strong>bac</strong> et un <strong>dossier</strong> facultatif — utile si plusieurs serveurs déposent dans le même bac. Puis vos deux clés.</p>

    <div class="callout"><span class="i">🔑</span><div>Créez de préférence une clé <strong>dédiée à ces sauvegardes</strong>, avec le droit d'écrire dans ce seul bac. Si elle fuite un jour, elle ne donnera rien d'autre. Les clés ne sont d'ailleurs <strong>jamais réaffichées</strong> par le panneau : une clé rendue à l'écran se pose dans un cache de navigateur et se retrouve dans une capture.</div></div>

    <h3>Le chiffrement</h3>
    <p>Une archive qui quitte la machine atterrit chez un tiers. Avec une <strong>phrase secrète</strong>, chaque pièce est chiffrée (AES-256) <em>avant</em> de partir.</p>

    <div class="callout warn"><span class="i">🔐</span><div><strong>Cette phrase n'est récupérable nulle part.</strong> Elle est enregistrée sur cette machine, et sur elle seule. Le jour où vous restaurerez depuis le dépôt distant, ce sera probablement parce que cette machine n'existe plus — et il vous la faudra. <strong>Notez-la ailleurs</strong> : gestionnaire de mots de passe, ou papier. Sans elle, la copie hors-site est un tas d'octets illisibles.</div></div>

    <p>Sans phrase secrète, l'écran affiche un <strong>avertissement</strong> plutôt qu'un succès : tout ce que vous confiez au dépôt est alors lisible par quiconque y a accès, l'hébergeur compris.</p>

    <h3>L'essai</h3>
    <p>Le bouton <strong>🔌 Essayer le dépôt</strong> y <strong>dépose</strong> puis retire un objet minuscule. Se contenter de lire prouverait qu'on sait se connecter, pas qu'on saura écrire — et c'est la nuit venue, sur une archive de trente gigaoctets, qu'on découvrirait le contraire.</p>
    <p>Les trois échecs courants sont expliqués séparément : <strong>403</strong> (la clé est acceptée mais n'a pas le droit d'écrire, ou la clé secrète est fausse), <strong>404</strong> (bac introuvable : vérifiez son nom, la région, l'adresse — et essayez la case « adresser le bac dans le chemin », nécessaire pour MinIO), et <strong>aucune réponse</strong> (adresse injoignable).</p>

    <h3>Automatiser jusqu'au bout</h3>
    <p>Cochez <strong>« Envoyer aussi la copie hors-site »</strong> à la planification. Sans elle, l'automatisme s'arrête à mi-chemin : la machine sauvegarde toute seule, mais sur elle-même.</p>
    <p>Un dépôt injoignable ne fait pas passer la sauvegarde locale pour ratée — elle est faite, elle est bonne, et l'écran le dit.</p>

    <h2 id="a5-advice">En pratique</h2>
    <ol class="steps">
      <li>Configurez le <strong>dépôt hors-site</strong> et faites l'essai. C'est la première chose à faire, pas la dernière.</li>
      <li>Planifiez une sauvegarde <strong>quotidienne</strong> à une heure creuse, avec l'envoi hors-site coché.</li>
      <li>Gardez <strong>sept</strong> sauvegardes : de quoi remonter une semaine, ce qui couvre le délai habituel avant qu'on s'aperçoive d'un problème.</li>
      <li>Lancez une sauvegarde <strong>à la main</strong> avant toute opération risquée — mise à jour, changement de version PHP — avec une note qui dit pourquoi.</li>
      <li><strong>Essayez une restauration</strong> une fois, sur un site de test, pendant que tout va bien. C'est le seul moyen de savoir que ça marche.</li>
    </ol>

    <div class="callout"><span class="i">✅</span><div>Le dernier point est le plus négligé, et le seul qui compte vraiment. Une sauvegarde jamais restaurée est une hypothèse, pas une garantie.</div></div>

    <div class="doc-foot">
      <span>AegisHost · partie 5 : les sauvegardes</span>
      <span><a href="<?= $h($github) ?>" target="_blank" rel="noopener">Dépôt GitHub ↗</a></span>
    </div>

<?php require __DIR__ . '/../../inc/foot.php'; ?>
