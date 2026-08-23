<?php
/**
 * documentation/modules/aegishost/doc-aegishost-docker.php — AegisHost, partie 3 : Docker.
 */
$docPage = 'modules/aegishost/doc-aegishost-docker.php';
$seo = [
    'title'     => 'AegisHost — Partie 3 : Docker · Documentation',
    'desc'      => "Docker dans AegisHost : quinze applications prêtes (WordPress, Nextcloud, Vaultwarden, Jellyfin, Ollama, n8n, Uptime Kuma, Roundcube, Minecraft, Terraria, TeamSpeak…), image libre, réseaux, volumes, publication sur un domaine en HTTPS et jauges de consommation.",
    'canonical' => 'https://gamenodepanel.com/documentation/modules/aegishost/doc-aegishost-docker.php',
];
require __DIR__ . '/../../inc/head.php';

$code_data = <<<'TXT'
/srv/<nom-de-l-installation>/
└── donnees/          ← monté dans le conteneur
TXT;
?>

    <h1>AegisHost — Partie 3 : Docker</h1>
    <p class="doc-lead">Un catalogue d'applications prêtes à installer en trois clics, et tout ce qu'il faut pour les suivre ensuite : consommation, journaux, console, publication sur un domaine en HTTPS.</p>
    <div class="doc-meta">
      <span class="doc-pill">menu : 🐳 Docker</span>
      <span class="doc-pill">15 applications</span>
      <span class="doc-pill">composant : docker</span>
    </div>

    <style>
    .ah-apps{display:grid;grid-template-columns:repeat(auto-fill,minmax(268px,1fr));gap:14px;margin:18px 0 6px}
    .ah-app{background:var(--bg2);border:1px solid var(--bd);border-radius:12px;padding:15px 16px}
    .ah-app .hd{display:flex;align-items:baseline;gap:8px;margin-bottom:6px}
    .ah-app .ic{font-size:1.3rem}
    .ah-app .ti{font-weight:800;font-size:.99rem;color:var(--tx)}
    .ah-app .de{font-size:.86rem;color:var(--tx2);line-height:1.58}
    .ah-app .po{margin-top:9px;font-family:var(--mono);font-size:.74rem;color:var(--tx3)}
    .ah-app .mn{margin-left:auto;flex:none;font-size:.68rem;font-weight:700;letter-spacing:.03em;text-transform:uppercase;padding:2px 7px;border-radius:20px;background:var(--bg3);color:var(--tx2);border:1px solid var(--bd)}
    .ah-app .mn.beta{background:#f59e0b1f;color:#b45309;border-color:#f59e0b55}
    [data-theme="dark"] .ah-app .mn.beta{color:#fbbf24;border-color:#f59e0b66}
    .ah-app .no{margin-top:8px;font-size:.81rem;color:var(--tx2);border-left:2px solid var(--ac);padding-left:9px;line-height:1.5}
    </style>

    <div class="callout"><span class="i">📦</span><div>Docker n'est pas installé d'office. Posez-le depuis la <a href="modules/aegishost/doc-aegishost-market.php">Logithèque</a> — <strong>📦 Logithèque → Docker → Installer</strong> — puis revenez ici.</div></div>

    <h2 id="a3-why">Pourquoi passer par le panneau</h2>
    <p>Le panneau n'accède <strong>jamais</strong> à <code>/var/run/docker.sock</code>, et ce n'est pas un détail : pouvoir parler à ce socket équivaut à être root sur la machine, puisqu'il suffit de lancer un conteneur qui monte <code>/</code> pour prendre le serveur entier. La plupart des panneaux donnent cet accès à PHP. Ici, tout passe par l'agent privilégié, qui n'accepte qu'une poignée d'opérations et refuse le reste.</p>

    <p>Le catalogue apporte trois choses qu'un <code>docker run</code> tapé à la main ne donne pas :</p>
    <ul>
      <li><strong>Les ports sont proposés puis vérifiés.</strong> Si le port conseillé est déjà pris, le panneau en propose un libre — et il distingue TCP et UDP, qui sont deux ressources différentes.</li>
      <li><strong>Les données atterrissent dans un dossier ordinaire</strong> de la machine, pas dans un volume Docker. Un dossier se visite en SSH, se copie, et se <a href="modules/aegishost/doc-aegishost-backup.php">sauvegarde</a>.</li>
      <li><strong>Les droits sont réglés en demandant à l'image</strong> sous quel identifiant elle tourne. C'est la cause n°1 des conteneurs qui redémarrent en boucle, et personne ne le documente.</li>
    </ul>

    <h2 id="a3-apps">Les applications disponibles</h2>
    <p>Quinze entrées, chacune décrite avec ce qu'elle installe, ce qu'elle expose et ce qu'il faut savoir <em>avant</em> de la lancer.</p>

    <h3>Web, outils &amp; médias</h3>
    <div class="ah-apps">
      <div class="ah-app">
        <div class="hd"><span class="ic">📝</span><span class="ti">WordPress</span></div>
        <div class="de">Le moteur de site le plus répandu, livré <strong>avec sa base de données</strong> : deux conteneurs sur un réseau privé, reliés sans que vous ayez rien à saisir.</div>
        <div class="po">port 8080 · 2 conteneurs</div>
        <div class="no">Le mot de passe de la base est généré et montré une fois. Publiez-le ensuite sur un domaine pour l'atteindre en HTTPS.</div>
      </div>
      <div class="ah-app">
        <div class="hd"><span class="ic">🔗</span><span class="ti">n8n</span></div>
        <div class="de">Automatise des tâches entre vos services — « quand un formulaire est envoyé, crée une ligne dans un tableur et préviens-moi sur Discord » — dans une interface visuelle. Tout tourne chez vous.</div>
        <div class="po">port 5678</div>
        <div class="no"><strong>Notez la clé de chiffrement</strong> affichée à l'installation : sans elle, un jour de restauration, aucune connexion enregistrée ne pourra être relue.</div>
      </div>
      <div class="ah-app">
        <div class="hd"><span class="ic">📡</span><span class="ti">Uptime Kuma</span></div>
        <div class="de">Surveille vos sites et vous prévient quand l'un d'eux tombe — courriel, Discord, Telegram. Utile pour apprendre une panne avant vos visiteurs.</div>
        <div class="po">port 3001</div>
        <div class="no">Créez le compte administrateur <strong>tout de suite</strong> : tant qu'il n'existe pas, n'importe qui atteignant ce port peut le créer.</div>
      </div>
      <div class="ah-app">
        <div class="hd"><span class="ic">🗄️</span><span class="ti">phpMyAdmin</span></div>
        <div class="de">Administration des bases, en conteneur isolé — pratique si vous ne l'avez pas installé avec le serveur.</div>
        <div class="po">port 8081</div>
      </div>
      <div class="ah-app">
        <div class="hd"><span class="ic">⚡</span><span class="ti">Redis</span></div>
        <div class="de">Cache mémoire, pour accélérer un site qui interroge trop sa base.</div>
        <div class="po">aucun port publié</div>
        <div class="no">Volontairement <strong>non exposé</strong> : Redis n'a pas d'authentification par défaut, et l'ouvrir sur Internet revient à publier son contenu.</div>
      </div>
      <div class="ah-app">
        <div class="hd"><span class="ic">🧠</span><span class="ti">Ollama + Open WebUI</span></div>
        <div class="de">Un modèle de langage qui tourne sur <strong>votre</strong> serveur, avec son interface de discussion. Vos conversations ne sortent pas de la machine.</div>
        <div class="po">port 3000 · 2 conteneurs</div>
        <div class="no">Demande de la <strong>mémoire</strong> : comptez 8 Go pour un petit modèle, davantage au-delà. À réserver aux serveurs qui en ont.</div>
      </div>
      <div class="ah-app">
        <div class="hd"><span class="ic">🔐</span><span class="ti">Vaultwarden</span></div>
        <div class="de">Gestionnaire de mots de passe, compatible avec les applications et extensions <strong>Bitwarden</strong> officielles — Windows, Android, iOS, Firefox, Chrome. Un seul conteneur, une base SQLite : ni base à installer, ni second service.</div>
        <div class="po">port 8222</div>
        <div class="no"><strong>Publiez-le sur un domaine en HTTPS avant de vous en servir.</strong> Ce n'est pas un conseil de prudence : les navigateurs refusent les fonctions de chiffrement sur une adresse non sécurisée, et le coffre web restera inutilisable tant qu'on l'atteindra par <code>http://adresse:port</code>. Créez votre compte à la première visite — tant qu'aucun n'existe, quiconque atteint l'adresse peut le créer.</div>
      </div>
      <div class="ah-app">
        <div class="hd"><span class="ic">☁️</span><span class="ti">Nextcloud</span></div>
        <div class="de">Le nuage personnel : fichiers synchronisés entre vos machines, agenda et contacts partagés, galerie photo, édition à plusieurs. Livré <strong>avec sa base MariaDB</strong>, deux conteneurs sur un réseau privé.</div>
        <div class="po">port 8090 · 2 conteneurs</div>
        <div class="no">Le <strong>compte administrateur est créé pour vous</strong> et son mot de passe n'est affiché qu'une fois : notez-le. Nextcloud refuse ensuite les adresses qu'il ne connaît pas — après publication sur un domaine, ajoutez la vôtre à <code>NEXTCLOUD_TRUSTED_DOMAINS</code> depuis l'onglet Configuration. Comptez au moins 1 Go de mémoire.</div>
      </div>
      <div class="ah-app">
        <div class="hd"><span class="ic">📬</span><span class="ti">Roundcube</span></div>
        <div class="de">Le webmail qui complète la <a href="modules/aegishost/doc-aegishost-mail.php">messagerie du panneau</a> : Postfix et Dovecot reçoivent le courrier, Roundcube permet de le lire depuis un navigateur.</div>
        <div class="po">port 8095</div>
        <div class="no">Indiquez votre serveur de courrier à la première connexion, par exemple <code>ssl://mail.mondomaine.fr</code>. Il faut évidemment que la messagerie soit installée et qu'au moins une boîte existe. Publiez-le en HTTPS avant un usage réel : un webmail en clair expose les mots de passe de boîte à chaque connexion.</div>
      </div>
      <div class="ah-app">
        <div class="hd"><span class="ic">🎬</span><span class="ti">Jellyfin</span></div>
        <div class="de">Votre médiathèque : films, séries et musique, lisibles depuis n'importe quel écran — téléviseur, téléphone, navigateur. Entièrement libre, sans compte ni abonnement.</div>
        <div class="po">port 8096</div>
        <div class="no">Déposez vos fichiers dans le dossier <strong>medias</strong> du conteneur par FTP, rangés par genre. Le <strong>transcodage sera logiciel</strong>, donc gourmand : le panneau ne sait pas donner une carte graphique à un conteneur. Et à la sauvegarde, cochez la configuration mais <strong>laissez la médiathèque de côté</strong> — les films se reprennent, une base de visionnage non.</div>
      </div>
    </div>

    <h3>Serveurs de jeu &amp; vocal</h3>
    <div class="ah-apps">
      <div class="ah-app">
        <div class="hd"><span class="ic">⛏️</span><span class="ti">Serveur Minecraft</span></div>
        <div class="de">La version <strong>Java</strong>, celle qu'on lance depuis un launcher sur ordinateur. Réglé en PAPER par défaut — la variante la plus économe et compatible avec les greffons.</div>
        <div class="po">port 25565/tcp</div>
        <div class="no">Le premier démarrage est long : le serveur télécharge sa version. Comptez 2 Go de mémoire au minimum. Le contrat de licence Minecraft est accepté en votre nom, l'écran le dit.</div>
      </div>
      <div class="ah-app">
        <div class="hd"><span class="ic">🧱</span><span class="ti">Minecraft Bedrock</span></div>
        <div class="de">L'<strong>autre</strong> Minecraft : consoles, téléphones, application Windows. Un joueur Java ne peut <strong>pas</strong> rejoindre un serveur Bedrock, ni l'inverse.</div>
        <div class="po">port 19132/udp</div>
        <div class="no">Gardez le port 19132 si vous le pouvez : c'est celui que les consoles et les mobiles essaient par défaut.</div>
      </div>
      <div class="ah-app">
        <div class="hd"><span class="ic">🌳</span><span class="ti">Serveur Terraria</span></div>
        <div class="de">Un monde Terraria persistant, ouvert à vos amis. Version vanilla, sans greffon.</div>
        <div class="po">port 7777/tcp</div>
        <div class="no"><strong>Deux étapes en console SSH au premier démarrage</strong>, et il n'y a pas moyen de faire autrement : Terraria n'a aucune option de création automatique de monde, il pose la question au clavier. La fiche de l'application détaille les deux commandes. Retenez surtout <code>Ctrl-P</code> puis <code>Ctrl-Q</code> pour vous détacher — <strong>jamais <code>Ctrl-C</code></strong>, qui arrêterait le serveur.</div>
      </div>
      <div class="ah-app">
        <div class="hd"><span class="ic">🎙️</span><span class="ti">TeamSpeak 3</span><span class="mn">Stable</span></div>
        <div class="de">Le serveur vocal éprouvé, celui que tout le monde utilise. Image officielle. <strong>C'est celui à prendre</strong> si vous voulez simplement que ça marche.</div>
        <div class="po">ports 9987/udp · 30033/tcp</div>
        <div class="no"><strong>La clé d'administration et le mot de passe ServerQuery n'apparaissent qu'une fois</strong>, dans les journaux du conteneur, au tout premier démarrage — et rien ensuite ne les redonne. Allez les chercher dès l'installation finie : fiche du conteneur → onglet Journaux.</div>
      </div>
      <div class="ah-app">
        <div class="hd"><span class="ic">🎙️</span><span class="ti">TeamSpeak 6</span><span class="mn beta">Bêta</span></div>
        <div class="de">La nouvelle génération du serveur vocal, image officielle elle aussi — mais encore en <strong>bêta</strong> chez TeamSpeak. Fonctionnelle, et susceptible de changer d'une version à l'autre.</div>
        <div class="po">ports 9987/udp · 30033/tcp</div>
        <div class="no">Même règle pour la clé d'administration : une seule apparition, dans les journaux du conteneur. Préférez la <strong>version 3</strong> pour un serveur que vous ne voulez pas surveiller.</div>
      </div>
    </div>

    <div class="callout"><span class="i">🔌</span><div>Pour les serveurs de jeu et le vocal, ces ports <strong>ne sont pas des pages web</strong> : on ne les ouvre pas dans un navigateur. On s'y connecte depuis le jeu ou depuis le client TeamSpeak, avec l'adresse IP du serveur.</div></div>

    <div class="callout warn"><span class="i">🔥</span><div>Cochez <strong>« Ouvrir le port dans le pare-feu »</strong> à la création pour les serveurs de jeu et le vocal. Sans cela, le port reste fermé et personne ne vous rejoindra — le conteneur, lui, tournera parfaitement.</div></div>

    <h2 id="a3-create">Installer une application</h2>
    <p><strong>🐳 Docker → ➕ Nouveau conteneur</strong>, onglet <strong>📦 Applications prêtes</strong>.</p>
    <ol class="steps">
      <li>Choisissez l'application : sa description et ses avertissements s'affichent.</li>
      <li>Donnez un <strong>nom d'installation</strong> — il préfixe les conteneurs et nomme le dossier de données. On peut donc installer deux WordPress côte à côte.</li>
      <li>Les <strong>ports</strong> sont pré-remplis et vérifiés. Un port occupé est refusé, avec le nom de ce qui l'occupe et une proposition libre.</li>
      <li>Fixez éventuellement une <strong>limite de mémoire et de processeur</strong>. Elles valent <em>par conteneur</em> : une application de deux conteneurs bornés à 1 Go peut en prendre deux.</li>
      <li>La <strong>revue</strong> montre ce qui sera créé — conteneurs, dossiers, ports — avant de lancer quoi que ce soit.</li>
    </ol>

    <p>Les données de l'application vivent ici :</p>
    <pre><code><?= $h($code_data) ?></code></pre>

    <div class="callout"><span class="i">💡</span><div>C'est pour cela que l'écran <strong>Volumes</strong> affiche souvent « aucun volume » alors que vos applications tournent : elles n'utilisent pas de volumes Docker, mais des dossiers ordinaires. Cet écran sert surtout à traquer les <strong>volumes anonymes</strong> que des images lancées à la main laissent derrière elles.</div></div>

    <h2 id="a3-free">L'image libre</h2>
    <p>Second onglet, réservé au <strong>mode Avancé</strong> : pour une image que vous connaissez déjà, avec vos ports, vos variables et vos dossiers. C'est ce geste-là qui demande le mode Avancé, pas la page — il lance du code que le panneau n'a pas décrit.</p>

    <h2 id="a3-manage">Suivre un conteneur</h2>
    <p>La fiche d'un conteneur a cinq onglets :</p>
    <ul>
      <li><strong>Aperçu</strong> — état, image, redémarrages, et <em>pourquoi</em> il s'est arrêté. Un conteneur tué faute de mémoire affiche « exited » comme un conteneur qui a fini son travail : la fiche distingue les deux, ce qui devient crucial dès qu'on pose des limites.</li>
      <li><strong>Configuration</strong> — ports, dossiers, variables, limites, rotation des journaux. Modifier recrée le conteneur ; l'écran prévient de ce qui ne survivrait pas.</li>
      <li><strong>Domaine</strong> — publier sur un nom de domaine, avec HTTPS. Voir plus bas.</li>
      <li><strong>Console</strong> — une commande, une réponse, exécutée <em>dans</em> le conteneur. Ce n'est pas un terminal interactif, et l'écran le dit plutôt que de faire semblant.</li>
      <li><strong>Journaux</strong> — la sortie du conteneur, fond sombre, lue par la fin.</li>
    </ul>

    <p>Les jauges de consommation sont plafonnées à 100 % <strong>de ce que le conteneur a le droit de prendre</strong> : avec une limite d'un cœur, 100 % veut dire « il consomme tout son quota », pas « il consomme tout le serveur ».</p>

    <h2 id="a3-publish">Publier sur un domaine</h2>
    <p>Un conteneur répond sur un port : <code>http://mon-serveur:8080</code>. C'est utilisable, mais ce n'est pas une adresse qu'on donne — et un port nu ne peut pas porter de certificat.</p>
    <p>Onglet <strong>Domaine</strong> : le panneau pose devant lui un hôte virtuel qui relaie. Le domaine devient alors une adresse ordinaire du serveur web, ce qui lui donne gratuitement tout ce que le panneau sait déjà faire — certificat, journaux, activation.</p>

    <div class="callout"><span class="i">🎮</span><div>Cela vaut pour les applications <strong>web</strong>. Un serveur de jeu ou vocal ne se publie pas sur un domaine : on s'y connecte depuis le jeu, avec l'adresse IP du serveur et son port. L'écran de ces applications le rappelle.</div></div>

    <h2 id="a3-res">Volumes et réseaux</h2>
    <p><strong>Mode Avancé.</strong> Deux questions récurrentes y trouvent leur réponse : « pourquoi mon disque se remplit-il ? » — des volumes anonymes que plus rien ne réclame — et « pourquoi ces deux conteneurs ne se voient-ils pas ? » — ils ne partagent aucun réseau.</p>
    <p>Les applications du catalogue qui comptent plusieurs conteneurs reçoivent automatiquement un réseau privé : sans lui, ils ne pourraient pas se joindre par leur nom, et l'application ne fonctionnerait pas quoi qu'on saisisse par ailleurs.</p>

    <div class="callout warn"><span class="i">⚠️</span><div>Supprimer un volume efface son contenu, définitivement. Un volume encore utilisé par un conteneur est refusé, et chaque retrait est inscrit au journal.</div></div>

    <div class="doc-foot">
      <span>AegisHost · partie 3 : Docker</span>
      <span><a href="<?= $h($github) ?>" target="_blank" rel="noopener">Dépôt GitHub ↗</a></span>
    </div>

<?php require __DIR__ . '/../../inc/foot.php'; ?>
