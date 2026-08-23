<?php
/**
 * documentation/tuto-ssh-root.php — Activer la connexion SSH root (Debian/Ubuntu)
 * pour que GameNodePanel puisse piloter le serveur hôte.
 */
$docPage = 'tutoriels/tuto-ssh-root.php';
$seo = [
    'title'     => 'Activer le login root SSH (Debian / Ubuntu) — Documentation GameNodePanel',
    'desc'      => "Comment autoriser la connexion SSH en root sur Debian 12/13 et Ubuntu pour que GameNodePanel puisse installer et gérer vos serveurs de jeu : PermitRootLogin, mot de passe root, redémarrage de sshd, et alternative sécurisée par clé SSH.",
    'canonical' => 'https://gamenodepanel.com/documentation/tuto-ssh-root.php',
];
require __DIR__ . '/../inc/head.php';
?>

    <h1>Activer le login root SSH</h1>
    <p class="doc-lead">Par défaut, Debian et Ubuntu <strong>refusent la connexion SSH en root</strong>. Voici comment l'autoriser proprement pour que GameNodePanel puisse piloter votre serveur hôte.</p>
    <div class="doc-meta">
      <span class="doc-pill">Debian 12 / 13</span>
      <span class="doc-pill">Ubuntu 20.04+</span>
      <span class="doc-pill">OpenSSH</span>
    </div>

    <h2 id="why">Pourquoi root ?</h2>
    <p>GameNodePanel se connecte en <strong>SSH</strong> à votre serveur hôte pour tout automatiser&nbsp;: installation des dépendances, création des comptes FTP, déploiement et contrôle des serveurs de jeu, agent O.D.I.N… La plupart de ces actions exigent les <strong>droits root</strong>.</p>
    <p>Or, sur une installation fraîche, <code>sshd</code> bloque le compte root (directive <code>PermitRootLogin</code>) — d'où l'erreur <em>« Permission denied »</em> quand GNP (ou vous) tente de se connecter en <code>root</code>.</p>
    <div class="callout">
      <span class="i">🔐</span>
      <div>Deux approches&nbsp;: <strong>(A)</strong> root + mot de passe (rapide, pratique en lab/VM) ou <strong>(B)</strong> root par <strong>clé SSH</strong> (recommandé en production). GNP supporte les deux. La méthode A est décrite d'abord, la méthode B en section <a href="tutoriels/tuto-ssh-root.php#secu">Sécuriser</a>.</div>
    </div>

    <h2 id="debian">Debian 12 / 13</h2>
    <p>Connectez-vous d'abord à la machine (console du VPS, ou un utilisateur disposant de <code>sudo</code>).</p>

    <h3>1. Définir un mot de passe root</h3>
    <p>Sur beaucoup d'installs le compte root n'a pas de mot de passe&nbsp;: il faut en créer un.</p>
    <pre><code>sudo passwd root</code></pre>

    <h3>2. Autoriser root dans la configuration SSH</h3>
    <p>Éditez le fichier de configuration du serveur SSH&nbsp;:</p>
    <pre><code>sudo nano /etc/ssh/sshd_config</code></pre>
    <p>Trouvez (ou ajoutez) ces deux lignes, et assurez-vous qu'elles ne sont <strong>pas commentées</strong> (pas de <code>#</code> devant)&nbsp;:</p>
    <pre><code>PermitRootLogin yes
PasswordAuthentication yes</code></pre>
    <div class="callout warn"><span class="i">⚠️</span><div>Si une ligne existe déjà avec <code>PermitRootLogin prohibit-password</code> ou <code>no</code>, remplacez-la par <code>yes</code> (ne créez pas de doublon&nbsp;: c'est la <em>dernière</em> occurrence valable qui compte).</div></div>

    <h3>3. Redémarrer le service SSH</h3>
    <pre><code>sudo systemctl restart ssh</code></pre>
    <p>C'est tout. Vous pouvez maintenant ajouter cet hôte dans GNP avec l'utilisateur <code>root</code> et son mot de passe.</p>

    <h2 id="ubuntu">Ubuntu</h2>
    <p>La procédure est <strong>identique</strong> (même OpenSSH)&nbsp;: <code>passwd root</code>, puis <code>PermitRootLogin yes</code> dans <code>/etc/ssh/sshd_config</code>, puis <code>sudo systemctl restart ssh</code>.</p>
    <div class="callout"><span class="i">🟡</span><div><strong>Piège spécifique Ubuntu (et images cloud)</strong>&nbsp;: un fichier dans <code>/etc/ssh/sshd_config.d/</code> peut <strong>écraser</strong> votre réglage (souvent <code>50-cloud-init.conf</code> avec <code>PasswordAuthentication no</code>). Vérifiez&nbsp;:</div></div>
    <pre><code>sudo grep -R "PasswordAuthentication\|PermitRootLogin" /etc/ssh/sshd_config /etc/ssh/sshd_config.d/</code></pre>
    <p>Si un fichier de ce dossier impose <code>no</code>, éditez-le (ex. <code>sudo nano /etc/ssh/sshd_config.d/50-cloud-init.conf</code>) pour mettre <code>yes</code>, puis redémarrez SSH.</p>

    <h2 id="verif">Vérifier la connexion</h2>
    <p>Avant d'ajouter l'hôte dans GNP, testez depuis votre poste&nbsp;:</p>
    <pre><code>ssh root@IP_DU_SERVEUR</code></pre>
    <p>Vous pouvez aussi valider la syntaxe de la config SSH côté serveur&nbsp;:</p>
    <pre><code>sudo sshd -t   # n'affiche rien si la config est valide</code></pre>
    <div class="callout ok"><span class="i">✅</span><div>Connexion root OK&nbsp;? Rendez-vous dans <strong>GNP → Serveurs hôtes → Nouveau serveur</strong>, saisissez l'IP, l'utilisateur <code>root</code> et le mot de passe (ou la clé), puis testez la connexion.</div></div>

    <h2 id="secu">Sécuriser : root par clé SSH (recommandé)</h2>
    <p>En production, évitez le mot de passe&nbsp;: utilisez une <strong>clé SSH</strong>, bien plus sûre contre le brute-force.</p>
    <ol class="steps">
      <li><strong>Générer une clé</strong> sur votre poste&nbsp;: <code>ssh-keygen -t ed25519</code></li>
      <li><strong>Copier la clé publique</strong> vers le serveur&nbsp;: <code>ssh-copy-id root@IP_DU_SERVEUR</code></li>
      <li><strong>Durcir la config</strong> dans <code>/etc/ssh/sshd_config</code>&nbsp;:
        <pre><code>PermitRootLogin prohibit-password
PasswordAuthentication no</code></pre>
      </li>
      <li><strong>Redémarrer</strong>&nbsp;: <code>sudo systemctl restart ssh</code></li>
    </ol>
    <div class="callout"><span class="i">🛡️</span><div>Dans GNP, collez la <strong>clé privée</strong> dans le champ prévu lors de l'ajout de l'hôte&nbsp;: la connexion se fait alors sans mot de passe. Bonus&nbsp;: restreignez l'accès SSH à l'IP de votre hébergement web via le pare-feu.</div></div>

    <h2 id="tshoot">Dépannage</h2>
    <table class="doc-table">
      <tr><th>Symptôme</th><th>Cause probable / solution</th></tr>
      <tr><td><code>Permission denied (publickey,password)</code></td><td><code>PermitRootLogin</code> encore à <code>no</code>/<code>prohibit-password</code>, ou un fichier de <code>sshd_config.d/</code> l'écrase. Vérifiez et redémarrez SSH.</td></tr>
      <tr><td>Mot de passe refusé alors qu'il est bon</td><td><code>PasswordAuthentication no</code> quelque part. Repassez à <code>yes</code> (ou utilisez une clé).</td></tr>
      <tr><td><code>Connection refused</code></td><td>Service SSH arrêté ou port fermé. <code>sudo systemctl status ssh</code> + ouvrez le port 22 sur le pare-feu.</td></tr>
      <tr><td><code>Connection timed out</code></td><td>Pare-feu/box bloque le port, ou mauvaise IP. Vérifiez la redirection de port (si VM en NAT, préférez le mode <strong>Bridge</strong>).</td></tr>
      <tr><td>Le service ne redémarre pas</td><td>Erreur de syntaxe&nbsp;: lancez <code>sudo sshd -t</code> pour la localiser.</td></tr>
    </table>
    <div class="callout warn"><span class="i">⚠️</span><div>Astuce VM locale (VMware/VirtualBox)&nbsp;: mettez la carte réseau en <strong>Bridge (ponté)</strong> pour que la VM ait une IP de votre réseau local, directement joignable par WAMP/GNP. En NAT, il faut rediriger le port 22.</div></div>

    <?php require __DIR__ . '/../inc/foot.php'; ?>
