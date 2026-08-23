<?php
/**
 * documentation/modules/aegishost/doc-aegishost.php — Module AegisHost (présentation).
 */
$docPage = 'modules/aegishost/doc-aegishost.php';
$seo = [
    'title'     => 'AegisHost — Documentation · Aegis Framework',
    'desc'      => "AegisHost : panneau d'administration de serveur pour Debian 12/13. Sites web, PHP-FPM, HTTPS Let's Encrypt, bases de données, Docker, messagerie et sauvegardes — sans jamais faire tourner le panneau en root.",
    'canonical' => 'https://gamenodepanel.com/documentation/modules/aegishost/doc-aegishost.php',
];
require __DIR__ . '/../../inc/head.php';
?>

    <h1>AegisHost</h1>
    <p class="doc-lead">Un panneau d'administration de serveur <strong>léger</strong> pour Debian : créer des sites, gérer PHP et les certificats, piloter Docker, sauvegarder — depuis le navigateur, <strong>sans jamais faire tourner le panneau en root</strong>.</p>
    <div class="doc-meta">
      <span class="doc-pill">modules/AegisHost</span>
      <span class="doc-pill">menu : 🌐 Aegis Host</span>
      <span class="doc-pill">Debian 12 / 13</span>
      <span class="doc-pill">requires: Auth</span>
    </div>

    <style>
    .ah-feat{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:14px;margin:18px 0 6px}
    .ah-feat .f{background:var(--bg2);border:1px solid var(--bd);border-radius:12px;padding:15px 16px}
    .ah-feat .f .ic{font-size:1.5rem;display:block;margin-bottom:7px}
    .ah-feat .f .ti{font-weight:800;font-size:.97rem;color:var(--tx);margin-bottom:3px}
    .ah-feat .f .de{font-size:.85rem;color:var(--tx2);line-height:1.55}
    .ah-split{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin:16px 0}
    @media(max-width:720px){.ah-split{grid-template-columns:1fr}}
    .ah-side{border:1px solid var(--bd);border-radius:12px;padding:14px 16px;background:var(--bg2)}
    .ah-side h4{margin:0 0 8px;font-size:.95rem}
    .ah-side ul{margin:0;padding-left:18px}
    .ah-side li{font-size:.87rem;color:var(--tx2);line-height:1.6}
    .ah-no{border-color:color-mix(in srgb,var(--ac) 25%,var(--bd))}
    </style>

    <h2 id="ah-intro">Présentation</h2>
    <p>AegisHost transforme un <strong>VPS ou un serveur dédié Debian</strong> en hébergement administrable depuis le navigateur. On y crée un site en trois champs, on lui attribue une version de PHP, on lui obtient un certificat, on lui pose une base de données et un compte FTP — puis on sauvegarde le tout.</p>
    <p>L'objectif tenait en une phrase au moment de le concevoir : <em>« un aaPanel sans l'usine à gaz »</em>. Le panneau ne cherche pas à tout couvrir ; il couvre ce dont on se sert, et il le dit honnêtement quand il ne sait pas.</p>

    <div class="callout"><span class="i">🧭</span><div>Menu admin : <strong>🌐 Aegis Host → Tableau de bord · Sites web · Logithèque · Docker · Sauvegardes · Messagerie · Journal · Aide et réparation · Sécurité · Réglages</strong>.</div></div>

    <h2 id="ah-agent">L'agent privilégié</h2>
    <p>C'est le choix d'architecture qui définit tout le reste, et il vaut la peine d'être compris avant d'installer.</p>
    <p>Créer un site demande les droits <strong>root</strong> : écrire un hôte virtuel dans <code>/etc</code>, créer un dossier sous <code>/var/www</code>, poser un pool PHP-FPM, recharger le serveur web. PHP, lui, tourne en <code>www-data</code>.</p>

    <div class="ah-split">
      <div class="ah-side ah-no">
        <h4>❌ Ce que font la plupart des panneaux</h4>
        <ul>
          <li>Le panneau entier tourne en root.</li>
          <li>La moindre faille dans une page PHP donne la machine.</li>
          <li>Une injection dans un formulaire devient une commande root.</li>
        </ul>
      </div>
      <div class="ah-side">
        <h4>✅ Ce que fait AegisHost</h4>
        <ul>
          <li>Un <strong>unique script</strong> root, l'agent, déposé par l'installateur.</li>
          <li>Une règle <code>sudoers</code> NOPASSWD limitée à ce seul fichier.</li>
          <li>Le panneau <strong>nomme une action</strong> — il n'envoie jamais de ligne de commande.</li>
          <li>L'agent <strong>revalide tout</strong> de son côté.</li>
        </ul>
      </div>
    </div>

    <p>Quatre règles gouvernent ce contrat, et elles ne bougeront pas :</p>
    <ol class="steps">
      <li>Le panneau n'envoie <strong>jamais</strong> de commande : il nomme une action d'une liste fermée et fournit des options validées des deux côtés.</li>
      <li>Les secrets passent par l'<strong>entrée standard</strong>, jamais par les arguments — la ligne de commande d'un processus est lisible par tout le monde dans <code>ps</code>.</li>
      <li>L'agent répond en <strong>JSON</strong> : <code>{"ok":true,…}</code> ou <code>{"ok":false,"error":"…"}</code>.</li>
      <li>La validation côté PHP est une <strong>commodité</strong>. La barrière est dans l'agent, et lui seul.</li>
    </ol>

    <div class="callout"><span class="i">🔏</span><div>L'agent est <strong>signé cryptographiquement</strong>. Il vérifie sa propre signature avant toute mise à jour de lui-même : un agent modifié ou remplacé est refusé. Le panneau n'a par ailleurs <strong>jamais</strong> accès à <code>/var/run/docker.sock</code> — pouvoir parler à ce socket équivaut à être root, puisqu'il suffit de lancer un conteneur qui monte <code>/</code>.</div></div>

    <h2 id="ah-features">Ce qu'il sait faire</h2>
    <div class="ah-feat">
      <div class="f"><span class="ic">🌍</span><div class="ti">Sites web</div><div class="de">Création en trois champs, domaines multiples, activation, éditeur d'hôte virtuel en mode Avancé, journaux lisibles depuis le panneau.</div></div>
      <div class="f"><span class="ic">🐘</span><div class="ti">PHP par site</div><div class="de">Plusieurs versions installables côte à côte. Chaque site a son pool FPM et ses propres limites : mémoire, temps d'exécution, taille d'envoi.</div></div>
      <div class="f"><span class="ic">🔒</span><div class="ti">HTTPS</div><div class="de">Certificats Let's Encrypt, renouvellement automatique. La liste des sites affiche l'échéance de chacun — et signale un certificat que plus rien ne sert.</div></div>
      <div class="f"><span class="ic">🗄️</span><div class="ti">Bases de données</div><div class="de">MariaDB ou MySQL, création rattachée à un site, phpMyAdmin optionnel sur un chemin non devinable.</div></div>
      <div class="f"><span class="ic">📁</span><div class="ti">Comptes FTP</div><div class="de">Comptes virtuels pure-ftpd, chacun enfermé dans le dossier de son site.</div></div>
      <div class="f"><span class="ic">🐳</span><div class="ti">Docker</div><div class="de">Catalogue d'applications prêtes, réseaux, volumes, console, publication sur un domaine en HTTPS, jauges de consommation.</div></div>
      <div class="f"><span class="ic">💾</span><div class="ti">Sauvegardes</div><div class="de">Sites, bases, données des conteneurs, configuration. Restauration pièce par pièce, planification, copie hors-site chiffrée.</div></div>
      <div class="f"><span class="ic">✉️</span><div class="ti">Messagerie</div><div class="de">Postfix et Dovecot, domaines et boîtes. L'entrée du menu n'apparaît que si la messagerie est installée.</div></div>
      <div class="f"><span class="ic">🛡️</span><div class="ti">Sécurité</div><div class="de">Pare-feu UFW, Fail2ban, ClamAV, audit du serveur, durcissement SSH avec retour arrière automatique.</div></div>
      <div class="f"><span class="ic">📦</span><div class="ti">Logithèque</div><div class="de">Installation des composants depuis un catalogue déclaratif : Redis, Certbot, Docker, ClamAV… Jamais un script téléchargé, jamais une commande transmise.</div></div>
      <div class="f"><span class="ic">🛟</span><div class="ti">Aide et réparation</div><div class="de">Diagnostic du panneau, remise d'aplomb de la règle sudoers et des droits, journal de l'agent en direct.</div></div>
      <div class="f"><span class="ic">📊</span><div class="ti">Supervision</div><div class="de">Processeur, mémoire, disque, réseau, services, conteneurs — relevés sur la machine, pas déduits d'une base.</div></div>
    </div>

    <h2 id="ah-principe">Le principe qui traverse tout le module</h2>
    <p>Un panneau d'administration a une seule chose à vendre : la <strong>confiance</strong> dans ce qu'il affiche. AegisHost s'y tient par trois habitudes, visibles partout dans l'interface.</p>

    <ul>
      <li><strong>Ne jamais annoncer un succès sans relire ce que la machine a appliqué.</strong> Retirer un composant qui casse le serveur web est rapporté comme un échec, pas comme « Réussi ».</li>
      <li><strong>Ne jamais afficher « 0 » quand la vraie réponse est « je ne sais pas ».</strong> Une mesure interrompue s'affiche « non mesuré », une échéance illisible s'affiche « ? » — jamais zéro, qui voudrait dire « expire aujourd'hui ».</li>
      <li><strong>Lire la machine, pas ses propres notes.</strong> La liste des sauvegardes est relue sur le disque, l'état d'une minuterie vient de systemd, les certificats sont lus dans <code>/etc/letsencrypt</code>. Une intervention faite en SSH se voit dans le panneau.</li>
    </ul>

    <div class="callout warn"><span class="i">⚠️</span><div>Corollaire assumé : le panneau <strong>refuse</strong> parfois. Il refuse de sauvegarder s'il manque de place, refuse d'écraser un hôte virtuel écrit à la main, refuse de restaurer une archive dont il ignore le contenu. Un refus expliqué vaut mieux qu'une opération à moitié faite.</div></div>

    <h2 id="ah-requis">Prérequis</h2>
    <ul>
      <li><strong>Debian 12 (Bookworm)</strong> ou <strong>Debian 13 (Trixie)</strong>, architecture <code>amd64</code> ou <code>arm64</code>. Aucune autre distribution n'est prise en charge : l'installateur s'arrête plutôt que de tenter sa chance.</li>
      <li>Un accès <strong>root</strong> (ou <code>sudo</code>) sur la machine.</li>
      <li><strong>768 Mo de RAM</strong> et <strong>5 Go</strong> d'espace disque libres au minimum.</li>
      <li>Les ports <strong>80</strong> et <strong>443</strong> libres.</li>
      <li>Une connexion Internet — l'installation récupère des paquets.</li>
      <li>Un serveur <strong>vierge de préférence</strong> : l'installateur détecte une pile déjà en place et prévient.</li>
    </ul>

    <div class="callout"><span class="i">🚀</span><div><strong>Prêt à installer ?</strong> La suite se passe en ligne de commande, une seule fois : <a href="modules/aegishost/doc-aegishost-install.php">Partie 1 : installation →</a></div></div>

    <div class="doc-foot">
      <span>AegisHost · documentation officielle</span>
      <span><a href="<?= $h($github) ?>" target="_blank" rel="noopener">Dépôt GitHub ↗</a></span>
    </div>

<?php require __DIR__ . '/../../inc/foot.php'; ?>
