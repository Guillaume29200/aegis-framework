<?php
/**
 * documentation/modules/aegishost/doc-aegishost-install.php — AegisHost, partie 1 : installation.
 */
$docPage = 'modules/aegishost/doc-aegishost-install.php';
$seo = [
    'title'     => 'AegisHost — Partie 1 : installation · Documentation',
    'desc'      => "Installer AegisHost sur un VPS ou un serveur dédié Debian 12/13 : déposer install.tar dans /tmp ou /home, l'extraire avec tar -xf, entrer dans le dossier install, lancer ./install.sh et répondre à l'assistant. Options non interactives et dépannage.",
    'canonical' => 'https://gamenodepanel.com/documentation/modules/aegishost/doc-aegishost-install.php',
];
require __DIR__ . '/../../inc/head.php';

$code_upload = <<<'TXT'
# Depuis VOTRE machine — remplacez l'adresse par celle de votre serveur
scp install.tar root@203.0.113.10:/tmp/
TXT;

$code_upload_home = <<<'TXT'
# Ou dans votre dossier personnel, si vous préférez le conserver
scp install.tar root@203.0.113.10:/home/
TXT;

$code_upload_git = <<<'TXT'
# Ou directement sur le serveur, si vous avez git
cd /tmp
git clone https://github.com/Guillaume29200/aegis-framework.git
cd aegis-framework/modules/AegisHost/Bash
TXT;

$code_extract = <<<'TXT'
# Placez-vous là où vous avez déposé l'archive
cd /tmp

# Extraire : l'archive crée un dossier « install »
tar -xf install.tar

# Entrer dedans
cd install
TXT;

$code_extract_list = <<<'TXT'
# Pour voir ce que contient l'archive sans rien extraire
tar -tf install.tar
TXT;

$code_rights = <<<'TXT'
chmod +x install.sh
TXT;

$code_run = <<<'TXT'
./install.sh
TXT;

$code_run_sudo = <<<'TXT'
# Si vous n'êtes pas déjà root
sudo ./install.sh
TXT;

$code_crlf = <<<'TXT'
# Le fichier a été transféré depuis Windows : on retire les retours chariot
sed -i 's/\r$//' install.sh
chmod +x install.sh
TXT;

$code_unattended = <<<'TXT'
./install.sh --unattended \
     --web=nginx --db=mariadb \
     --domain=exemple.com --email=admin@exemple.com \
     --ssl --pma --hardening
TXT;

$code_agent_only = <<<'TXT'
# Reposer uniquement l'agent privilégié, sur un serveur déjà installé
sudo bash install.sh --agent-only
TXT;

$code_log = <<<'TXT'
# Suivre l'installation en direct depuis une seconde session SSH
tail -f /var/log/aegishost-install.log
TXT;
?>

    <h1>AegisHost — Partie 1 : installation</h1>
    <p class="doc-lead">Une archive à déposer, trois commandes à taper, une fois. L'assistant pose les questions, contrôle le terrain avant de toucher à quoi que ce soit, puis installe la pile complète : serveur web, base de données, PHP 8.5, l'agent privilégié, et ce que vous aurez choisi en plus.</p>
    <div class="doc-meta">
      <span class="doc-pill">install.sh v1.3.0</span>
      <span class="doc-pill">Debian 12 / 13</span>
      <span class="doc-pill">root requis</span>
      <span class="doc-pill">~10 min</span>
    </div>

    <div class="callout"><span class="i">📖</span><div>Vous découvrez le module ? Commencez par la <a href="modules/aegishost/doc-aegishost.php">Présentation</a> — et notamment la section sur l'<a href="modules/aegishost/doc-aegishost.php#ah-agent">agent privilégié</a>, qui explique pourquoi l'installation dépose un script root sur votre machine.</div></div>

    <h2 id="ai-requis">Avant de commencer</h2>
    <p>Vérifiez ces cinq points. L'installateur les contrôle lui aussi et <strong>s'arrête</strong> plutôt que d'installer à moitié — mais autant le savoir avant de vous connecter.</p>
    <ul>
      <li><strong>Debian 12 (Bookworm) ou Debian 13 (Trixie)</strong>, en <code>amd64</code> ou <code>arm64</code>. Ubuntu, Rocky, Alma : non pris en charge, l'installateur refuse.</li>
      <li><strong>768 Mo de RAM</strong> et <strong>5 Go</strong> libres au minimum.</li>
      <li>Les ports <strong>80</strong> et <strong>443</strong> libres. Si un serveur web tourne déjà, l'installateur le dit et s'arrête.</li>
      <li>Un accès <strong>root</strong>, direct ou par <code>sudo</code>.</li>
      <li>Si vous voulez HTTPS dès l'installation : votre <strong>domaine doit déjà pointer</strong> vers l'IP du serveur. Let's Encrypt le vérifie, et il ne triche pas.</li>
    </ul>

    <div class="callout warn"><span class="i">⚠️</span><div><strong>Installez sur un serveur vierge.</strong> AegisHost pose sa propre pile. Sur une machine où tourne déjà un site en production, l'installateur détecte ce qui est en place et vous prévient — mais le plus sûr reste de partir d'un serveur neuf.</div></div>

    <h2 id="ai-upload">1. Déposer l'archive sur le serveur</h2>
    <p>Tout l'installateur tient dans un seul fichier, livré avec le module :</p>
    <p><code>modules/AegisHost/Bash/<strong>install.tar</strong></code></p>
    <p>C'est <strong>à vous de le déposer</strong> sur votre VPS ou votre serveur dédié — rien ne le télécharge à votre place, et c'est voulu : un installateur qui va chercher son propre code sur Internet est un installateur dont vous ne savez pas ce qu'il exécute.</p>
    <p>L'emplacement n'a aucune importance, choisissez selon vos habitudes. <code>/tmp</code> convient très bien pour une installation unique — le dossier est nettoyé au redémarrage, ce qui évite de laisser traîner l'archive. <code>/home</code> ou <code>/root</code> si vous préférez la garder sous la main pour reposer l'agent plus tard.</p>
    <pre><code><?= $h($code_upload) ?></code></pre>
    <pre><code><?= $h($code_upload_home) ?></code></pre>
    <p>N'importe quel client SFTP fait aussi bien l'affaire — FileZilla, WinSCP, l'explorateur de fichiers de votre hébergeur. Vous pouvez également récupérer le dépôt directement sur le serveur :</p>
    <pre><code><?= $h($code_upload_git) ?></code></pre>

    <h2 id="ai-extract">2. Extraire l'archive</h2>
    <p>Une archive <code>.tar</code> est un dossier empaqueté dans un fichier unique : elle ne compresse rien, elle regroupe. Celle-ci contient <code>install.sh</code>, le répertoire <code>lib/</code> qui porte le gros de l'installateur, et l'agent privilégié avec sa signature.</p>
    <p>Connectez-vous en SSH, puis :</p>
    <pre><code><?= $h($code_extract) ?></code></pre>
    <p>L'option <code>-x</code> veut dire « extraire » et <code>-f</code> annonce le nom du fichier qui suit. L'archive crée d'elle-même un dossier <code>install/</code> : vos autres fichiers ne risquent rien, et vous saurez toujours où elle s'est posée.</p>

    <div class="callout"><span class="i">🔍</span><div>Vous voulez voir ce qu'elle contient avant de l'ouvrir ? <code>-t</code> liste sans rien écrire sur le disque :
    <pre style="margin:8px 0 0"><code><?= $h($code_extract_list) ?></code></pre>
    Bonne habitude en général, et rien n'empêche de la prendre ici.</div></div>

    <h2 id="ai-rights">3. Donner les droits d'exécution</h2>
    <p>Selon la façon dont l'archive a voyagé, <code>install.sh</code> peut arriver sans son droit d'exécution. Toujours dans le dossier <code>install/</code> :</p>
    <pre><code><?= $h($code_rights) ?></code></pre>

    <div class="callout"><span class="i">🪟</span><div><strong>Transfert depuis Windows ?</strong> Si le lancement échoue sur un message du genre <code>bad interpreter: No such file or directory</code> ou <code>$'\r': command not found</code>, le fichier porte des retours chariot Windows. Une ligne les enlève :
    <pre style="margin:8px 0 0"><code><?= $h($code_crlf) ?></code></pre>
    L'installateur nettoie déjà lui-même les fichiers de <code>lib/</code>, mais il ne peut évidemment pas se nettoyer avant d'être lu.</div></div>

    <h2 id="ai-run">4. Lancer l'assistant</h2>
    <p>Toujours dans le dossier <code>install/</code>, en <strong>root</strong> :</p>
    <pre><code><?= $h($code_run) ?></code></pre>
    <pre><code><?= $h($code_run_sudo) ?></code></pre>

    <p>L'installateur commence par un <strong>contrôle du terrain</strong>, avant toute modification : distribution, architecture, mémoire, disque, ports libres, accès réseau, pile déjà présente. Si quelque chose manque, il s'arrête là — rien n'a encore été touché.</p>

    <div class="callout"><span class="i">📜</span><div>Tout est journalisé dans <code>/var/log/aegishost-install.log</code>. Depuis une seconde session SSH, vous pouvez suivre le détail en direct :
    <pre style="margin:8px 0 0"><code><?= $h($code_log) ?></code></pre></div></div>

    <h2 id="ai-wizard">5. Répondre aux questions</h2>
    <p>L'assistant pose sept questions, dans cet ordre. Aucune n'est piégeuse, et chacune a une valeur par défaut raisonnable.</p>

    <ol class="steps">
      <li><strong>Serveur web</strong> — <code>nginx</code> ou <code>apache</code>. Prenez Apache si vos sites utilisent des <code>.htaccess</code> (WordPress, Laravel en hébergement classique) ; Nginx sinon.</li>
      <li><strong>Moteur de base de données</strong> — <code>mariadb</code> (défaut) ou <code>mysql</code>. En cas d'hésitation, MariaDB.</li>
      <li><strong>Nom de domaine principal</strong> — sans le préfixe <code>www.</code>, par exemple <code>monsite.com</code>. C'est ce domaine qui servira le panneau.</li>
      <li><strong>Adresse e-mail de l'administrateur</strong> — utilisée par Let's Encrypt pour les alertes d'expiration, et par le serveur pour ses avis.</li>
      <li><strong>HTTPS (Let's Encrypt)</strong> — demande que le domaine pointe <em>déjà</em> vers ce serveur. Si ce n'est pas encore le cas, répondez non : le certificat s'obtient ensuite depuis le panneau, en un clic, quand le DNS aura pris.</li>
      <li><strong>phpMyAdmin</strong> — optionnel. S'il est installé, l'assistant propose un <strong>chemin d'accès aléatoire</strong> plutôt que <code>/phpmyadmin</code> : un chemin non devinable réduit énormément les tentatives automatisées. Gardez celui qu'il propose.</li>
      <li><strong>Serveur FTP</strong> (pure-ftpd) puis <strong>durcissement</strong> (UFW + Fail2ban, recommandé : oui).</li>
    </ol>

    <p>PHP <strong>8.5</strong> est installé dans tous les cas, avec ses extensions courantes (PDO, MySQL, mbstring, GD, intl, opcache). Il provient du dépôt <code>packages.sury.org</code>, Debian ne fournissant pas cette version.</p>

    <div class="callout ok"><span class="i">💾</span><div><strong>Vos réponses sont mémorisées avant l'installation.</strong> Si elle s'interrompt — coupure réseau, redémarrage — la reprise ne repose aucune question. Pour repartir de zéro et tout ressaisir : <code>./install.sh --fresh</code>.</div></div>

    <p>Un <strong>résumé</strong> s'affiche ensuite. Rien n'est installé avant que vous l'ayez validé.</p>

    <h2 id="ai-after">6. Après l'installation</h2>
    <p>Le rapport final affiche à l'écran ce dont vous avez besoin tout de suite :</p>
    <ul>
      <li>L'<strong>adresse du site</strong> et, le cas échéant, celle de phpMyAdmin avec son chemin secret.</li>
      <li>Le <strong>compte SQL administrateur</strong> et son mot de passe.</li>
      <li>La <strong>base du panneau</strong>, son utilisateur et son mot de passe — ceux à renseigner à l'installation d'Aegis Framework.</li>
    </ul>

    <div class="callout warn"><span class="i">🔑</span><div><strong>Notez ces identifiants maintenant.</strong> Ils sont aussi écrits dans <code>/root/aegishost-credentials.txt</code>, lisible du seul compte root — mais personne ne va ouvrir ce fichier juste après une installation réussie, et c'est précisément le moment où l'on en a besoin.</div></div>

    <p>Il reste alors à déposer Aegis Framework dans la racine web indiquée par le rapport, à lancer son propre installateur avec les identifiants de base fournis, puis à activer le module <strong>AegisHost</strong> depuis l'administration. Le menu <strong>🌐 Aegis Host</strong> apparaît aussitôt.</p>

    <h2 id="ai-options">Installation non interactive</h2>
    <p>Pour un déploiement scripté ou reproductible, toutes les réponses se passent en options. <code>--unattended</code> exige au minimum <code>--domain</code> et <code>--email</code> :</p>
    <pre><code><?= $h($code_unattended) ?></code></pre>

    <p>Les options principales :</p>
    <ul>
      <li><code>--web=nginx|apache</code> · <code>--db=mariadb|mysql</code></li>
      <li><code>--domain=</code> · <code>--email=</code></li>
      <li><code>--ssl</code> / <code>--no-ssl</code> · <code>--pma</code> / <code>--no-pma</code> · <code>--ftp</code> / <code>--no-ftp</code></li>
      <li><code>--pma-path=SEGMENT</code> — chemin d'accès à phpMyAdmin (aléatoire par défaut)</li>
      <li><code>--hardening</code> / <code>--no-hardening</code> — UFW + Fail2ban</li>
      <li><code>--skip-dns-check</code> — n'exige pas que le domaine pointe déjà vers ce serveur</li>
      <li><code>--config=FICHIER</code> — charge les réponses depuis un fichier (voir <code>aegishost.conf.example</code>)</li>
      <li><code>--force</code> — rejoue les étapes déjà terminées · <code>--fresh</code> — oublie les réponses mémorisées</li>
      <li><code>--log=FICHIER</code> — chemin du journal</li>
    </ul>
    <p>La liste complète est toujours disponible avec <code>./install.sh --help</code>.</p>

    <h2 id="ai-agent">Reposer l'agent seul</h2>
    <p>Le module et l'agent évoluent ensemble : une nouvelle version du panneau demande souvent une nouvelle version de l'agent, et le panneau vous le dit quand c'est le cas. Pour ne reposer que lui, sans toucher au reste du système :</p>
    <pre><code><?= $h($code_agent_only) ?></code></pre>
    <p>C'est aussi la commande à lancer si le panneau signale « l'agent privilégié n'est pas installé ». Elle est sans danger sur un serveur en production : elle n'installe rien d'autre et ne redémarre aucun service au-delà du strict nécessaire.</p>

    <h2 id="ai-tshoot">Dépannage</h2>

    <h3>« le répertoire lib/ est introuvable »</h3>
    <p>Vous avez lancé <code>install.sh</code> depuis ailleurs que le dossier extrait, ou vous n'avez copié que ce fichier au lieu de l'archive entière. L'installateur est découpé en modules et cherche <code>lib/</code> à côté de lui : replacez-vous dans <code>install/</code> avec <code>cd</code>, ou reprenez l'archive complète.</p>

    <h3>« tar: install.tar: Cannot open »</h3>
    <p>L'archive n'est pas dans le dossier où vous vous trouvez. Vérifiez où elle a atterri avec <code>ls</code>, ou donnez son chemin complet : <code>tar -xf /tmp/install.tar</code>.</p>

    <h3>« bad interpreter » ou « $'\r': command not found »</h3>
    <p>Retours chariot Windows. Voir l'encadré de l'<a href="#ai-rights">étape 3</a>.</p>

    <h3>« Seul Debian est pris en charge »</h3>
    <p>L'installateur refuse volontairement les autres distributions plutôt que de tenter sa chance : les chemins, les noms de paquets et les dépôts diffèrent, et une installation à moitié réussie est plus difficile à réparer qu'une installation refusée.</p>

    <h3>« Libérez les ports 80 et 443 »</h3>
    <p>Un serveur web tourne déjà. L'installateur nomme le processus qui les occupe. Arrêtez-le, ou installez sur une machine neuve.</p>

    <h3>Le certificat Let's Encrypt échoue</h3>
    <p>Neuf fois sur dix, le domaine ne pointe pas encore vers le serveur, ou la propagation DNS n'est pas terminée. Répondez <strong>non</strong> à la question HTTPS et reprenez le certificat depuis le panneau plus tard : <strong>Sites web → votre site → onglet HTTPS</strong>. Aucune raison de bloquer l'installation entière pour ça.</p>

    <h3>L'installation s'est interrompue</h3>
    <p>Relancez simplement <code>./install.sh</code> : les étapes déjà terminées sont reconnues et passées, et vos réponses sont conservées. Pour rejouer une étape malgré tout, ajoutez <code>--force</code>.</p>

    <div class="callout"><span class="i">📜</span><div>Dans tous les cas, <code>/var/log/aegishost-install.log</code> contient le détail complet de ce qui a été tenté et de ce que les commandes ont répondu.</div></div>

    <div class="doc-foot">
      <span>AegisHost · partie 1 : installation</span>
      <span><a href="<?= $h($github) ?>" target="_blank" rel="noopener">Dépôt GitHub ↗</a></span>
    </div>

<?php require __DIR__ . '/../../inc/foot.php'; ?>
