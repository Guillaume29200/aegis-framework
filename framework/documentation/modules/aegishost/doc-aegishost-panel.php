<?php
/**
 * documentation/modules/aegishost/doc-aegishost-panel.php — AegisHost, partie 2 : le panneau.
 */
$docPage = 'modules/aegishost/doc-aegishost-panel.php';
$seo = [
    'title'     => 'AegisHost — Partie 2 : le panneau · Documentation',
    'desc'      => "Déposer Aegis Framework et le module AegisHost dans le dossier public du domaine, puis prendre en main le panneau : tableau de bord, sites web, PHP par site, HTTPS, bases, FTP, modes Simple et Avancé.",
    'canonical' => 'https://gamenodepanel.com/documentation/modules/aegishost/doc-aegishost-panel.php',
];
require __DIR__ . '/../../inc/head.php';

$code_where = <<<'TXT'
/var/www/exemple.com/public/     ← c'est ICI que tout est déposé
├── framework/
├── modules/
│   └── AegisHost/
├── index.php
└── .env
TXT;

$code_upload = <<<'TXT'
# Depuis votre machine, vers la racine servie indiquée par l'installateur
scp -r aegis-framework/* root@203.0.113.10:/var/www/exemple.com/public/
TXT;

$code_rights = <<<'TXT'
# Les fichiers doivent appartenir à l'utilisateur du serveur web
chown -R www-data:www-data /var/www/exemple.com/public
find /var/www/exemple.com/public -type d -exec chmod 755 {} \;
find /var/www/exemple.com/public -type f -exec chmod 644 {} \;
TXT;

$code_repair = <<<'TXT'
sudo bash install.sh --agent-only
TXT;
?>

    <h1>AegisHost — Partie 2 : le panneau</h1>
    <p class="doc-lead">Le serveur est prêt. Il reste à y déposer <strong>Aegis Framework</strong> et le module <strong>AegisHost</strong>, puis à découvrir le panneau : dix écrans, et tout ce qu'on fait d'habitude en SSH devient une case à cocher.</p>
    <div class="doc-meta">
      <span class="doc-pill">après la partie 1</span>
      <span class="doc-pill">~10 min</span>
      <span class="doc-pill">menu : 🌐 Aegis Host</span>
    </div>

    <style>
    .ah-tour{display:grid;grid-template-columns:repeat(auto-fill,minmax(255px,1fr));gap:14px;margin:18px 0 6px}
    .ah-tour .t{background:var(--bg2);border:1px solid var(--bd);border-radius:12px;padding:15px 16px}
    .ah-tour .t .ti{font-weight:800;font-size:.98rem;color:var(--tx);margin-bottom:5px}
    .ah-tour .t .de{font-size:.86rem;color:var(--tx2);line-height:1.58}
    .ah-tour .t .de b{color:var(--tx)}
    .ah-modes{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin:16px 0}
    @media(max-width:720px){.ah-modes{grid-template-columns:1fr}}
    .ah-mode{border:1px solid var(--bd);border-radius:12px;padding:14px 16px;background:var(--bg2)}
    .ah-mode h4{margin:0 0 8px;font-size:.95rem}
    .ah-mode ul{margin:0;padding-left:18px}
    .ah-mode li{font-size:.87rem;color:var(--tx2);line-height:1.6}
    </style>

    <h2 id="a2-deploy">1. Déposer Aegis Framework</h2>
    <p>L'installateur a créé un site pour votre domaine et vous a indiqué sa <strong>racine servie</strong> — le dossier <code>public</code>. C'est là, et nulle part ailleurs, que le framework se dépose :</p>
    <pre><code><?= $h($code_where) ?></code></pre>

    <div class="callout"><span class="i">📁</span><div>Le sous-dossier <code>public</code> n'est pas décoratif : c'est <strong>lui seul</strong> qui est servi par le serveur web. Tout ce qui vit à côté — dépendances, fichiers de configuration, sauvegardes — reste hors de portée d'un visiteur. Sans lui, déposer un <code>.env</code> à la racine du site le publierait sur Internet, et cela ne se voit pas.</div></div>

    <p>Récupérez le framework depuis le dépôt (voir la <a href="../../framework/doc-aegis.php">documentation d'Aegis Framework</a>), puis transférez son contenu :</p>
    <pre><code><?= $h($code_upload) ?></code></pre>

    <p>Remettez ensuite les droits à l'utilisateur du serveur web, sans quoi PHP ne pourra ni lire ni écrire :</p>
    <pre><code><?= $h($code_rights) ?></code></pre>

    <h2 id="a2-module">2. Installer le module AegisHost</h2>
    <p>Le module se dépose dans <code>modules/AegisHost/</code>, à côté des autres. Puis :</p>
    <ol class="steps">
      <li>Ouvrez <strong>https://votre-domaine.com</strong> — l'installateur d'Aegis Framework se lance.</li>
      <li>Renseignez la <strong>base du panneau</strong>, son utilisateur et son mot de passe : ceux que le rapport de la partie 1 a affichés, et qui sont aussi dans <code>/root/aegishost-credentials.txt</code>.</li>
      <li>Créez votre compte administrateur.</li>
      <li>Une fois connecté, allez dans <strong>Modules</strong> et activez <strong>AegisHost</strong>.</li>
    </ol>

    <div class="callout ok"><span class="i">🌐</span><div>Le menu <strong>🌐 Aegis Host</strong> apparaît aussitôt dans la barre latérale. Vous administrez désormais votre serveur depuis votre serveur.</div></div>

    <h2 id="a2-first">3. Première visite</h2>
    <p>Le <strong>tableau de bord</strong> s'ouvre sur l'état réel de la machine : processeur, mémoire, disque, réseau, services actifs, conteneurs Docker, sites en ligne. Tout y est relevé sur le système à l'instant où vous chargez la page — rien n'est déduit d'une base de données.</p>

    <p>Si un bandeau d'alerte apparaît en haut, il vient de <strong>Aide et réparation</strong> : le panneau a repéré quelque chose qui l'empêche de travailler correctement — le plus souvent l'agent absent ou une règle <code>sudoers</code> abîmée. Le cas échéant, une commande suffit sur le serveur :</p>
    <pre><code><?= $h($code_repair) ?></code></pre>

    <h2 id="a2-tour">4. Le panneau, écran par écran</h2>
    <div class="ah-tour">
      <div class="t"><div class="ti">📊 Tableau de bord</div><div class="de">La santé de la machine d'un coup d'œil : charge, mémoire, disque, réseau, services, conteneurs. Les jauges se rafraîchissent seules.</div></div>
      <div class="t"><div class="ti">🌍 Sites web</div><div class="de">La liste de vos sites avec leur version de PHP, leur dossier, leurs accès et <b>l'échéance de leur certificat</b>. Le bouton « Nouveau site » est là.</div></div>
      <div class="t"><div class="ti">📦 Logithèque</div><div class="de">Le catalogue des composants installables : serveurs web, bases, PHP, Redis, Certbot, Docker, antivirus. <a href="modules/aegishost/doc-aegishost-market.php">Partie 4 →</a></div></div>
      <div class="t"><div class="ti">🐳 Docker</div><div class="de">Vos conteneurs, leur consommation, et un catalogue d'applications prêtes à installer. <a href="modules/aegishost/doc-aegishost-docker.php">Partie 3 →</a></div></div>
      <div class="t"><div class="ti">💾 Sauvegardes</div><div class="de">Sauvegarder, restaurer, planifier, copier hors-site. <a href="modules/aegishost/doc-aegishost-backup.php">Partie 5 →</a></div></div>
      <div class="t"><div class="ti">✉️ Messagerie</div><div class="de">Domaines et boîtes aux lettres (Postfix + Dovecot). <b>Cette entrée n'apparaît que si la messagerie est installée</b> — un menu qui mène nulle part ne rend service à personne.</div></div>
      <div class="t"><div class="ti">📜 Journal</div><div class="de">Ce que l'agent a fait, en direct. C'est ici qu'on va quand une opération s'est mal passée : le détail y est, pas dans un message d'écran raccourci.</div></div>
      <div class="t"><div class="ti">🛟 Aide et réparation</div><div class="de">Diagnostic du panneau et remise d'aplomb : droits, règle sudoers, agent. Plus un aide-mémoire des commandes utiles en console.</div></div>
      <div class="t"><div class="ti">🛡️ Sécurité</div><div class="de">Pare-feu UFW, Fail2ban, antivirus ClamAV, audit du serveur, durcissement SSH — avec retour arrière automatique.</div></div>
      <div class="t"><div class="ti">⚙️ Réglages</div><div class="de">Dossier des sites, mode d'affichage, préférences du panneau.</div></div>
    </div>

    <h2 id="a2-site">5. Créer votre premier site</h2>
    <p><strong>Sites web → ➕ Nouveau site.</strong> Trois champs suffisent : le ou les domaines, la version de PHP, et le dossier — que le panneau propose tout seul.</p>

    <p>Deux volets facultatifs se déplient en dessous :</p>
    <ul>
      <li><strong>Compte FTP</strong> — créé et enfermé dans le dossier du site. Le mot de passe est proposé, et affiché <em>une fois</em>, à la fin.</li>
      <li><strong>Base de données</strong> — créée avec son utilisateur dédié, rattachée au site.</li>
    </ul>

    <div class="callout warn"><span class="i">🔑</span><div>Les identifiants générés sont affichés <strong>à la fin de la création, une seule fois</strong>, et ne sont conservés nulle part. Notez-les à ce moment-là. Ils ne sont montrés qu'une fois l'installation réellement terminée : les afficher au lancement donnerait des mots de passe pour des comptes qui pourraient très bien ne jamais naître.</div></div>

    <p>La fiche du site s'ouvre ensuite sur neuf onglets : <strong>Aperçu, Domaines, PHP, HTTPS, Bases, FTP, Journaux, Hôte virtuel</strong> (mode Avancé) et <strong>Maintenance</strong>.</p>

    <h3>Obtenir le certificat</h3>
    <p>Onglet <strong>HTTPS</strong>, une adresse de contact, un bouton. Let's Encrypt vérifie que le domaine pointe bien vers ce serveur — c'est la seule condition, et elle ne dépend pas du panneau. Le renouvellement est ensuite automatique.</p>
    <p>La liste des sites affiche l'échéance de chaque certificat, en ambre sous quinze jours. Elle sait aussi signaler un cas particulier : un certificat <strong>valide que plus rien ne sert</strong>, parce que l'hôte virtuel HTTPS a disparu — le site répond alors en clair avec un certificat impeccable qui dort sur le disque.</p>

    <h3>Régler PHP pour ce site</h3>
    <p>Onglet <strong>PHP</strong>. Chaque site a son propre pool FPM : mémoire, temps d'exécution, taille d'envoi, nombre de variables. Un site qui a besoin de 512 Mo ne les impose pas aux autres.</p>

    <h2 id="a2-adopt">6. Récupérer des sites déjà en place</h2>
    <p>Vous arrivez sur un serveur qui héberge déjà des sites, créés à la main ou par un autre panneau ? Ils ne sont pas perdus pour AegisHost.</p>
    <p>Le panneau sait <strong>parcourir les hôtes virtuels</strong> présents sur la machine et vous proposer de les adopter : il lit le domaine servi, la racine, la version de PHP en vigueur, et les inscrit dans sa liste. Rien n'est réécrit — l'adoption ne fait qu'ajouter ce qui existe à ce que le panneau connaît.</p>

    <div class="callout"><span class="i">✋</span><div>Un hôte virtuel <strong>écrit à la main n'est jamais écrasé</strong>. Le panneau reconnaît sa propre signature en tête de fichier ; ce qui vient d'ailleurs est le travail de quelqu'un, dont la configuration n'existe nulle part ailleurs. Si vous tentez de créer un site portant un nom déjà pris par un fichier étranger, l'écran nomme le fichier en cause et oriente vers l'adoption plutôt que de le remplacer.</div></div>

    <p>Après adoption, le site se gère comme les autres : version de PHP, certificat, journaux, sauvegardes.</p>

    <h2 id="a2-modes">7. Mode Simple et mode Avancé</h2>
    <p>Le panneau a deux niveaux d'affichage, propres à chaque compte : passer en Avancé n'expose pas les réglages dangereux à tout le monde sur un serveur administré à plusieurs.</p>

    <div class="ah-modes">
      <div class="ah-mode">
        <h4>🙂 Simple <span style="font-weight:400;color:var(--tx3)">— par défaut</span></h4>
        <ul>
          <li>Créer et gérer des sites, PHP, HTTPS, bases, FTP.</li>
          <li>Installer des applications Docker <b>depuis le catalogue</b>.</li>
          <li>Sauvegarder, restaurer, planifier.</li>
        </ul>
      </div>
      <div class="ah-mode">
        <h4>🧰 Avancé</h4>
        <ul>
          <li>Éditeur d'<b>hôte virtuel</b> en toutes lettres.</li>
          <li>Docker : <b>image libre</b>, volumes, réseaux, console.</li>
          <li>Fichiers de configuration bruts des composants.</li>
          <li>Suppression de conteneurs et de volumes.</li>
        </ul>
      </div>
    </div>

    <p>Le mode par défaut est <strong>Simple</strong>, et c'est délibéré : quelqu'un qui découvre le panneau ne doit pas tomber d'emblée sur des réglages capables de mettre ses sites hors ligne.</p>

    <h2 id="a2-securite">8. Ce que le panneau protège tout seul</h2>
    <p>Quelques garde-fous travaillent sans qu'on les demande. Les connaître évite de les prendre pour des bugs.</p>
    <ul>
      <li><strong>Le site qui porte le panneau ne peut pas être supprimé</strong> depuis le panneau. Ni ses fonctions PHP vitales désactivées : couper <code>shell_exec</code> sur ce site précis rendrait le panneau incapable d'appeler son propre agent.</li>
      <li><strong>Un hôte virtuel écrit à la main n'est jamais écrasé.</strong> Le panneau reconnaît sa propre signature ; ce qui vient d'ailleurs est le travail de quelqu'un, et l'écran oriente vers l'import plutôt que de le remplacer.</li>
      <li><strong>Retirer un composant qui casserait le serveur web est refusé</strong> — et si le retrait passe malgré tout, le panneau relit la configuration et rapporte un échec plutôt qu'un « Réussi ».</li>
      <li><strong>Le durcissement SSH s'annule tout seul</strong> si vous ne confirmez pas depuis une nouvelle connexion. Personne ne s'enferme dehors.</li>
    </ul>

    <div class="callout"><span class="i">➡️</span><div>La suite : <a href="modules/aegishost/doc-aegishost-docker.php">Partie 3 — Docker</a>, <a href="modules/aegishost/doc-aegishost-market.php">Partie 4 — la Logithèque</a>, <a href="modules/aegishost/doc-aegishost-backup.php">Partie 5 — les sauvegardes</a>.</div></div>

    <div class="doc-foot">
      <span>AegisHost · partie 2 : le panneau</span>
      <span><a href="<?= $h($github) ?>" target="_blank" rel="noopener">Dépôt GitHub ↗</a></span>
    </div>

<?php require __DIR__ . '/../../inc/foot.php'; ?>
