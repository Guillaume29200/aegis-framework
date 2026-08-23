<?php
/**
 * documentation/modules/aegishost/doc-aegishost-market.php — AegisHost, partie 4 : la Logithèque.
 */
$docPage = 'modules/aegishost/doc-aegishost-market.php';
$seo = [
    'title'     => 'AegisHost — Partie 4 : la Logithèque · Documentation',
    'desc'      => "La Logithèque d'AegisHost : les douze composants installables — Nginx, Apache, PHP, MariaDB, MySQL, SQLite, pure-ftpd, Redis, ClamAV, Postfix, Certbot, Docker — leur rôle, leurs réglages et ce que le panneau refuse de retirer.",
    'canonical' => 'https://gamenodepanel.com/documentation/modules/aegishost/doc-aegishost-market.php',
];
require __DIR__ . '/../../inc/head.php';
?>

    <h1>AegisHost — Partie 4 : la Logithèque</h1>
    <p class="doc-lead">Douze composants installables d'un bouton, décrits en français plutôt qu'en noms de paquets. Le panneau ne télécharge aucun script et ne transmet aucune commande : il nomme une intention, et l'agent décide.</p>
    <div class="doc-meta">
      <span class="doc-pill">menu : 📦 Logithèque</span>
      <span class="doc-pill">12 composants</span>
      <span class="doc-pill">catalogue signé</span>
    </div>

    <style>
    .ah-cmp{border:1px solid var(--bd);border-radius:12px;background:var(--bg2);padding:15px 17px;margin-bottom:12px}
    .ah-cmp .hd{display:flex;align-items:baseline;gap:9px;flex-wrap:wrap;margin-bottom:6px}
    .ah-cmp .ti{font-weight:800;font-size:1.02rem;color:var(--tx)}
    .ah-cmp .tag{font-family:var(--mono);font-size:.7rem;padding:2px 8px;border-radius:999px;
      background:var(--bg3);border:1px solid var(--bd);color:var(--tx2)}
    .ah-cmp .de{font-size:.88rem;color:var(--tx2);line-height:1.62}
    .ah-cmp .cf{margin-top:9px;font-size:.83rem;color:var(--tx2);border-left:2px solid var(--ac);padding-left:10px;line-height:1.55}
    .ah-cmp .cf b{color:var(--tx)}
    </style>

    <h2 id="a4-how">Comment ça marche</h2>
    <p>La Logithèque repose sur un <strong>catalogue déclaratif</strong> livré avec le module et vérifié par une empreinte. Chaque entrée décrit une <em>intention</em> — un nom, des paquets, éventuellement un dépôt officiel — jamais une commande, jamais un script, jamais une adresse de téléchargement.</p>

    <p>Quand vous cliquez sur « Installer », le panneau nomme le composant. L'agent, lui, connaît la liste des paquets autorisés, sait quels dépôts il accepte de poser, et refuse tout le reste. Un catalogue modifié en chemin ne peut donc pas faire installer autre chose.</p>

    <div class="callout"><span class="i">⏱️</span><div>Les installations sont <strong>suivies en direct</strong> : la page n'est pas figée pendant qu'<code>apt</code> travaille. L'opération se poursuit sur le serveur même si vous fermez l'onglet, et le détail complet reste dans <strong>📜 Journal</strong>.</div></div>

    <h2 id="a4-web">Serveurs web</h2>

    <div class="ah-cmp">
      <div class="hd"><span class="ti">Nginx</span><span class="tag">nginx</span></div>
      <div class="de">Serveur web léger et très rapide, économe en mémoire. <strong>Ne lit pas les fichiers <code>.htaccess</code></strong> : les règles de réécriture se posent dans l'hôte virtuel.</div>
      <div class="cf"><b>À choisir si</b> vos sites sont des applications modernes, ou si le serveur est modeste.</div>
    </div>

    <div class="ah-cmp">
      <div class="hd"><span class="ti">Apache 2</span><span class="tag">apache2</span></div>
      <div class="de">Serveur web historique. Le seul à prendre en charge les <code>.htaccess</code> intégralement — ce que réclament WordPress, PrestaShop et la plupart des applications livrées clés en main.</div>
      <div class="cf"><b>À choisir si</b> vous installez des applications PHP classiques. En cas de doute : Apache.</div>
    </div>

    <div class="callout warn"><span class="i">⚔️</span><div>Les deux ne peuvent pas cohabiter : ils écoutent les mêmes ports. Le panneau signale l'incompatibilité et n'en propose qu'un à la fois. La case <strong>« Voir les incompatibles »</strong> montre ce qui est écarté et pourquoi.</div></div>

    <h2 id="a4-php">PHP</h2>
    <div class="ah-cmp">
      <div class="hd"><span class="ti">PHP</span><span class="tag">php</span></div>
      <div class="de">Plusieurs versions peuvent <strong>cohabiter</strong> : chaque site choisit la sienne dans sa fiche. C'est ce qui permet de garder un vieux site sur 8.1 tout en développant le suivant sur 8.5.</div>
      <div class="cf"><b>Réglages disponibles :</b> mémoire, temps d'exécution, taille d'envoi, fuseau horaire, affichage des erreurs. Ils valent pour la machine ; les réglages <b>par site</b> se font dans la fiche du site, onglet PHP.</div>
    </div>

    <div class="callout"><span class="i">🔐</span><div>Le panneau <strong>refuse</strong> de désactiver <code>shell_exec</code> et <code>proc_open</code> sur le site qui l'héberge : ce sont ces fonctions qui lui permettent d'appeler son agent. Les couper là rendrait le panneau muet — sur les autres sites, en revanche, vous en faites ce que vous voulez.</div></div>

    <h2 id="a4-db">Bases de données</h2>

    <div class="ah-cmp">
      <div class="hd"><span class="ti">MariaDB</span><span class="tag">mariadb-server</span></div>
      <div class="de">Base relationnelle compatible MySQL, développée par les auteurs d'origine. C'est le choix par défaut, et le bon dans la quasi-totalité des cas.</div>
      <div class="cf"><b>Réglages disponibles :</b> connexions simultanées, cache de tables, taille du tampon InnoDB, délai d'inactivité, taille maximale d'un paquet.</div>
    </div>

    <div class="ah-cmp">
      <div class="hd"><span class="ti">MySQL</span><span class="tag">mysql-server</span></div>
      <div class="de">La base d'Oracle. À choisir <strong>seulement</strong> si une application l'exige explicitement.</div>
      <div class="cf"><b>Incompatible avec MariaDB</b> : les deux occupent la même place. Le panneau ne propose que celui qui peut être installé.</div>
    </div>

    <div class="ah-cmp">
      <div class="hd"><span class="ti">SQLite</span><span class="tag">sqlite3</span></div>
      <div class="de">Base rangée dans un simple fichier, sans serveur ni mot de passe. Parfaite pour un petit site ou un outil interne ; inadaptée dès que plusieurs processus écrivent en même temps.</div>
    </div>

    <h2 id="a4-tools">Services</h2>

    <div class="ah-cmp">
      <div class="hd"><span class="ti">Certbot</span><span class="tag">certbot</span></div>
      <div class="de">Certificats HTTPS gratuits Let's Encrypt, renouvelés automatiquement par une minuterie posée à l'installation. C'est lui qui alimente l'onglet HTTPS de chaque site.</div>
      <div class="cf">Installé avec le <b>greffon</b> correspondant à votre serveur web. Sans Certbot, l'onglet HTTPS explique pourquoi il ne peut rien proposer plutôt que d'afficher un bouton mort.</div>
    </div>

    <div class="ah-cmp">
      <div class="hd"><span class="ti">Serveur FTP</span><span class="tag">pure-ftpd</span></div>
      <div class="de">Comptes FTP <strong>virtuels</strong> : ils n'existent que pour pure-ftpd, pas sur le système. Chacun est enfermé dans le dossier de son site et ne peut pas en sortir.</div>
      <div class="cf">Les comptes se créent depuis la fiche d'un site, onglet FTP. Le mot de passe est affiché une fois et n'est conservé nulle part.</div>
    </div>

    <div class="ah-cmp">
      <div class="hd"><span class="ti">Redis</span><span class="tag">redis-server</span></div>
      <div class="de">Stockage clé-valeur en mémoire, pour le cache et les sessions. Soulage une base sollicitée par les mêmes requêtes en boucle.</div>
      <div class="cf"><b>Réglages disponibles :</b> mémoire maximale, politique d'éviction, port d'écoute, sauvegarde sur disque.</div>
    </div>

    <div class="ah-cmp">
      <div class="hd"><span class="ti">Envoi d'e-mails</span><span class="tag">postfix</span></div>
      <div class="de">Permet à vos sites d'envoyer des messages : inscription, mot de passe oublié, notifications. Sans lui, ces fonctions échouent en silence dans la plupart des applications.</div>
      <div class="cf">Une fois installé, l'entrée <b>✉️ Messagerie</b> apparaît dans le menu et permet de gérer domaines et boîtes aux lettres.</div>
    </div>

    <div class="ah-cmp">
      <div class="hd"><span class="ti">ClamAV</span><span class="tag">clamav</span></div>
      <div class="de">Antivirus à la demande, pour contrôler ce que vos visiteurs déposent sur le serveur. Analyse lancée depuis l'écran Sécurité.</div>
      <div class="cf">La base de signatures se met à jour depuis le panneau. Une analyse complète est <b>lourde</b> : elle se lance en tâche de fond et se suit en direct.</div>
    </div>

    <div class="ah-cmp">
      <div class="hd"><span class="ti">Docker</span><span class="tag">docker-ce</span></div>
      <div class="de">Conteneurs applicatifs. Ouvre l'écran <strong>🐳 Docker</strong> et son catalogue d'applications prêtes.</div>
      <div class="cf">Posé depuis le <b>dépôt officiel Docker</b>, dont l'agent écrit lui-même l'adresse et la clé — le panneau ne fait que le nommer. Voir la <a href="modules/aegishost/doc-aegishost-docker.php">partie 3</a>.</div>
    </div>

    <h2 id="a4-config">Régler un composant</h2>
    <p>Les composants qui s'y prêtent portent un bouton <strong>⚙️ Réglages</strong>. Chaque directive y est décrite en français, avec ses bornes.</p>

    <p>Deux garde-fous s'appliquent à toute écriture :</p>
    <ul>
      <li>Le panneau ne dit <strong>ni quel fichier ouvrir, ni où</strong>. Le schéma, les bornes et les chemins vivent dans l'agent, qui refuse toute directive absente de sa liste — un maximum posé sur un écran ne protège de rien contre un appel fabriqué.</li>
      <li>Si le composant <strong>rejette</strong> la configuration obtenue, l'écriture est annulée et l'ancienne remise en place. Un serveur web qui refuse sa configuration ne redémarre plus : on ne prend pas ce risque pour un réglage.</li>
    </ul>

    <p>En <strong>mode Avancé</strong>, un second onglet donne accès au fichier de configuration en toutes lettres — avec le même filet : refusé par le composant, il est immédiatement rétabli.</p>

    <h2 id="a4-remove">Retirer un composant</h2>
    <p>Le retrait est possible, et volontairement plus prudent que l'installation.</p>

    <div class="callout warn"><span class="i">🚨</span><div>Le panneau <strong>refuse</strong> de retirer ce qui couperait l'accès à la machine ou mettrait tous les sites hors ligne. Et quand un retrait passe, il <strong>relit la configuration du serveur web</strong> juste après : si elle ne passe plus le test, l'opération est rapportée comme un <strong>échec</strong>, pas comme « Réussi ». Vos sites tournent encore à ce moment-là — ils tomberaient au prochain redémarrage, des heures plus tard, sans rapport apparent avec ce retrait.</div></div>

    <p>C'est le genre de panne différée qui coûte un serveur. La détecter à la seconde où elle est créée, et le dire franchement, vaut mieux qu'un message vert.</p>

    <div class="callout"><span class="i">➡️</span><div>La suite : <a href="modules/aegishost/doc-aegishost-backup.php">Partie 5 — les sauvegardes</a>.</div></div>

    <div class="doc-foot">
      <span>AegisHost · partie 4 : la Logithèque</span>
      <span><a href="<?= $h($github) ?>" target="_blank" rel="noopener">Dépôt GitHub ↗</a></span>
    </div>

<?php require __DIR__ . '/../../inc/foot.php'; ?>
