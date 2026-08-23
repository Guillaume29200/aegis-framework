<?php
/**
 * documentation/modules/aegishost/doc-aegishost-security.php — AegisHost, partie 7 : sécurité.
 */
$docPage = 'modules/aegishost/doc-aegishost-security.php';
$seo = [
    'title'     => 'AegisHost — Partie 7 : la sécurité · Documentation',
    'desc'      => "L'écran Sécurité d'AegisHost : audit du serveur, pare-feu UFW, Fail2ban et débannissement, antivirus ClamAV, et durcissement SSH avec retour arrière automatique pour ne jamais s'enfermer dehors.",
    'canonical' => 'https://gamenodepanel.com/documentation/modules/aegishost/doc-aegishost-security.php',
];
require __DIR__ . '/../../inc/head.php';

$code_newsession = <<<'TXT'
# NOUVELLE session, sans fermer l'ancienne — sur le nouveau port si vous l'avez changé
ssh -p 2222 root@203.0.113.10
TXT;

$code_keys = <<<'TXT'
# Depuis VOTRE machine, avant de couper l'authentification par mot de passe
ssh-copy-id -p 22 root@203.0.113.10
TXT;

$code_ufw = <<<'TXT'
# Voir les règles en place depuis le serveur
sudo ufw status numbered
TXT;
?>

    <h1>AegisHost — Partie 7 : la sécurité</h1>
    <p class="doc-lead">Un audit qui dit ce qui va et ce qui ne va pas, un pare-feu, un banniseur, un antivirus — et un durcissement SSH conçu pour qu'on ne puisse pas s'enfermer dehors.</p>
    <div class="doc-meta">
      <span class="doc-pill">menu : 🛡️ Sécurité</span>
      <span class="doc-pill">UFW · Fail2ban · ClamAV</span>
      <span class="doc-pill">retour arrière automatique</span>
    </div>

    <h2 id="a7-audit">L'audit du serveur</h2>
    <p>L'écran s'ouvre sur un relevé de l'état réel de la machine, avec un score : combien de points sont bons sur combien de contrôles. Chaque ligne dit ce qui est constaté, pourquoi ça compte, et ce qu'il faudrait faire.</p>
    <p>Tout y est <strong>lu sur le système</strong> à l'instant où vous chargez la page — configuration SSH réelle, règles de pare-feu en vigueur, services actifs. Rien n'est déduit d'un réglage enregistré : une modification faite en console se voit ici.</p>

    <h2 id="a7-ssh">Durcir SSH</h2>
    <p>C'est l'opération la plus utile de cet écran, et la plus redoutée — à juste titre. Trois réglages :</p>
    <ul>
      <li><strong>Le port d'écoute</strong> — le déplacer hors du 22 supprime l'essentiel du bruit de fond automatisé. Ce n'est pas une protection en soi, mais ça assainit les journaux.</li>
      <li><strong>La connexion root</strong> — l'interdire, ou ne l'autoriser que par clé.</li>
      <li><strong>L'authentification par mot de passe</strong> — la couper une fois vos clés en place.</li>
    </ul>

    <div class="callout warn"><span class="i">🔑</span><div><strong>Avant de couper les mots de passe</strong>, assurez-vous que votre clé publique est bien en place, et testez-la. Sans cela il ne restera aucun moyen d'entrer :
    <pre style="margin:8px 0 0"><code><?= $h($code_keys) ?></code></pre></div></div>

    <h3>Le retour arrière automatique</h3>
    <p>Voici le mécanisme à comprendre, sans quoi vous croirez à un bug.</p>

    <ol class="steps">
      <li>Vous appliquez les changements. L'agent <strong>arme un retour arrière</strong> avant de recharger SSH.</li>
      <li>Le panneau vous demande d'ouvrir une <strong>NOUVELLE connexion SSH</strong> — sans fermer l'ancienne, qui reste votre filet.</li>
      <li>Si elle réussit, vous cliquez sur <strong>Confirmer</strong>. Les changements deviennent définitifs.</li>
      <li><strong>Si vous ne confirmez pas dans les dix minutes, la machine revient d'elle-même à sa configuration précédente.</strong></li>
    </ol>

    <pre><code><?= $h($code_newsession) ?></code></pre>

    <div class="callout ok"><span class="i">🛟</span><div>C'est ce qui rend l'opération sûre : une erreur de port, une clé mal posée, un pare-feu mal réglé — au pire, vous attendez dix minutes et tout revient. Le bouton <strong>Annuler</strong> fait la même chose immédiatement.</div></div>

    <div class="callout warn"><span class="i">⚠️</span><div>Le corollaire : si vous confirmez <strong>sans</strong> avoir testé une nouvelle connexion, vous désarmez le filet. Ne confirmez que depuis une session ouverte <em>après</em> le changement.</div></div>

    <p>L'agent refuse par ailleurs d'appliquer ce qui vous enfermerait à coup sûr dehors — c'est lui qui sait si une clé est réellement en place, et il ne se contente pas de votre parole.</p>

    <h2 id="a7-firewall">Le pare-feu</h2>
    <p>UFW est posé par l'installateur si vous avez accepté le durcissement, et l'écran affiche les règles en vigueur.</p>
    <p>Le panneau ouvre lui-même les ports quand c'est nécessaire : à la création d'un conteneur, la case <strong>« Ouvrir le port dans le pare-feu »</strong> pose la règle — avec le bon protocole, TCP ou UDP selon l'application. C'est ce qui évite le grand classique du serveur de jeu qui tourne parfaitement mais que personne ne peut rejoindre.</p>
    <pre><code><?= $h($code_ufw) ?></code></pre>

    <h2 id="a7-fail2ban">Fail2ban</h2>
    <p>Fail2ban lit les journaux et bannit les adresses qui s'acharnent — SSH en premier lieu. L'écran affiche les <strong>prisons actives</strong> et les adresses actuellement bannies.</p>

    <div class="callout"><span class="i">🙃</span><div><strong>Vous vous êtes banni vous-même ?</strong> Ça arrive à tout le monde, et c'est justement pour cela que le débannissement est dans le panneau : si vous n'êtes plus le bienvenu en SSH, il vous reste le navigateur. Trouvez votre adresse dans la liste, cliquez sur <strong>Débannir</strong>.</div></div>

    <h2 id="a7-clamav">L'antivirus</h2>
    <p>ClamAV s'installe depuis la <a href="modules/aegishost/doc-aegishost-market.php">Logithèque</a>. Il ne surveille pas en permanence : il analyse <strong>à la demande</strong>, ce qui est le bon compromis sur un serveur web — un antivirus résident coûterait cher en performance pour un gain douteux.</p>
    <p>Deux gestes depuis l'écran :</p>
    <ul>
      <li><strong>Mettre à jour les signatures</strong> — à faire avant une analyse qui compte.</li>
      <li><strong>Lancer une analyse</strong> sur les dossiers des sites. Elle est <strong>lourde</strong> : elle part en tâche de fond et se suit en direct.</li>
    </ul>
    <p>Son usage le plus utile : contrôler ce que vos visiteurs déposent, quand un site accepte des envois de fichiers.</p>

    <h2 id="a7-rest">Ce qui protège sans qu'on le demande</h2>
    <p>Plusieurs garde-fous travaillent en dehors de cet écran. Ils sont détaillés ailleurs dans cette documentation, mais valent d'être rassemblés :</p>
    <ul>
      <li>Le <strong>panneau ne tourne jamais en root</strong> : un unique script privilégié, une liste fermée d'actions, tout revalidé de son côté. Voir la <a href="modules/aegishost/doc-aegishost.php#ah-agent">présentation</a>.</li>
      <li>Le panneau <strong>n'accède jamais</strong> à <code>/var/run/docker.sock</code> — y avoir accès équivaut à être root.</li>
      <li>Les <strong>secrets ne passent jamais en argument</strong> de commande : la ligne de commande d'un processus est lisible par tous dans <code>ps</code>.</li>
      <li>Les <strong>sauvegardes</strong> sont en <code>700</code>, lisibles du seul root — elles contiennent les mots de passe de vos bases et les clés privées de vos certificats.</li>
      <li>Le <strong>site qui porte le panneau</strong> ne peut être ni supprimé depuis le panneau, ni privé des fonctions PHP dont le panneau a besoin.</li>
      <li>Les <strong>identifiants générés</strong> sont affichés une fois et conservés nulle part.</li>
    </ul>

    <h2 id="a7-advice">Quelques conseils</h2>
    <ol class="steps">
      <li>Posez vos <strong>clés SSH</strong>, testez-les, puis coupez l'authentification par mot de passe. C'est le geste au meilleur rapport effort / bénéfice.</li>
      <li>Laissez <strong>Fail2ban</strong> en marche même après : il vous dira ce qui frappe à la porte.</li>
      <li>Donnez à phpMyAdmin un <strong>chemin non devinable</strong> — l'installateur en propose un aléatoire, gardez-le.</li>
      <li>N'exposez que ce qui doit l'être. Un conteneur qui n'a pas besoin d'être joignable de l'extérieur ne doit pas publier de port : Redis, dans le catalogue, en est l'exemple.</li>
      <li>Faites des <a href="modules/aegishost/doc-aegishost-backup.php">sauvegardes hors-site chiffrées</a>. Contre un rançongiciel, c'est la seule chose qui marche.</li>
    </ol>

    <div class="doc-foot">
      <span>AegisHost · partie 7 : la sécurité</span>
      <span><a href="<?= $h($github) ?>" target="_blank" rel="noopener">Dépôt GitHub ↗</a></span>
    </div>

<?php require __DIR__ . '/../../inc/foot.php'; ?>
