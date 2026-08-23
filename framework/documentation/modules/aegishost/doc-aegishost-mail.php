<?php
/**
 * documentation/modules/aegishost/doc-aegishost-mail.php — AegisHost, partie 8 : la messagerie.
 */
$docPage = 'modules/aegishost/doc-aegishost-mail.php';
$seo = [
    'title'     => 'AegisHost — Partie 8 : la messagerie · Documentation',
    'desc'      => "Héberger ses e-mails avec AegisHost : Postfix et Dovecot, domaines, boîtes aux lettres, alias et quotas — et les enregistrements DNS sans lesquels rien n'arrive à destination.",
    'canonical' => 'https://gamenodepanel.com/documentation/modules/aegishost/doc-aegishost-mail.php',
];
require __DIR__ . '/../../inc/head.php';

$code_dns = <<<'TXT'
; L'essentiel, dans la zone DNS de votre domaine
@       MX   10  mail.exemple.com.
mail    A        203.0.113.10
@       TXT      "v=spf1 mx -all"
_dmarc  TXT      "v=DMARC1; p=none; rua=mailto:postmaster@exemple.com"
TXT;

$code_client = <<<'TXT'
Serveur entrant  (IMAP) : mail.exemple.com   port 993   SSL/TLS
Serveur sortant  (SMTP) : mail.exemple.com   port 587   STARTTLS
Identifiant             : l'adresse complète — contact@exemple.com
TXT;

$code_ptr = <<<'TXT'
# Vérifier le reverse DNS de votre IP (doit répondre mail.exemple.com)
dig -x 203.0.113.10 +short
TXT;
?>

    <h1>AegisHost — Partie 8 : la messagerie</h1>
    <p class="doc-lead">Héberger ses propres boîtes aux lettres : domaines, adresses, alias et quotas depuis le panneau. La partie facile. La partie difficile — et cette page ne vous le cachera pas — se joue dans votre zone DNS.</p>
    <div class="doc-meta">
      <span class="doc-pill">menu : ✉️ Messagerie</span>
      <span class="doc-pill">Postfix + Dovecot</span>
      <span class="doc-pill">comptes virtuels</span>
    </div>

    <h2 id="a8-install">Installer la messagerie</h2>
    <p>Elle n'est pas posée d'office. <strong>📦 Logithèque → Envoi d'e-mails → Installer</strong> met en place Postfix et Dovecot.</p>

    <div class="callout"><span class="i">👀</span><div>L'entrée <strong>✉️ Messagerie</strong> n'apparaît dans le menu <em>qu'une fois la messagerie installée</em>. Un menu qui mène vers un écran vide ne rend service à personne — si vous ne le voyez pas, c'est que le composant n'est pas là.</div></div>

    <p>Deux usages bien distincts, qu'il vaut mieux ne pas confondre :</p>
    <ul>
      <li><strong>Envoyer</strong> depuis vos sites — inscription, mot de passe oublié, notifications. C'est ce que fait Postfix seul, et cela suffit à beaucoup de monde.</li>
      <li><strong>Recevoir et consulter</strong> — de vraies boîtes aux lettres, relevées depuis un client. C'est ce que Dovecot ajoute, et c'est là que le DNS devient exigeant.</li>
    </ul>

    <h2 id="a8-dns">Le DNS d'abord</h2>
    <p>Commençons par là, parce que c'est ce qui décide de tout. Un serveur de messagerie parfaitement configuré dont le DNS est incomplet enverra des messages qui finiront en indésirables — ou qui n'arriveront pas du tout.</p>

    <pre><code><?= $h($code_dns) ?></code></pre>

    <ul>
      <li><strong>MX</strong> — désigne le serveur qui reçoit le courrier du domaine. Sans lui, personne ne peut vous écrire.</li>
      <li><strong>A</strong> — l'adresse de ce serveur.</li>
      <li><strong>SPF</strong> — déclare qui a le droit d'envoyer <em>au nom</em> de votre domaine. Sans lui, vos messages sont suspects par défaut.</li>
      <li><strong>DMARC</strong> — dit aux destinataires quoi faire quand ça ne colle pas. Commencez par <code>p=none</code> : vous observez sans rien casser.</li>
    </ul>

    <div class="callout warn"><span class="i">🔁</span><div><strong>Le reverse DNS est le point le plus souvent oublié</strong>, et l'un des plus regardés par les grands fournisseurs. Il se règle chez votre <em>hébergeur</em>, dans l'interface du VPS — pas dans votre zone DNS, et pas depuis ce panneau.
    <pre style="margin:8px 0 0"><code><?= $h($code_ptr) ?></code></pre></div></div>

    <div class="callout"><span class="i">🚧</span><div><strong>Certains hébergeurs bloquent le port 25 sortant</strong> par défaut sur les VPS, pour lutter contre le spam. Si vos messages ne partent pas alors que tout semble juste, c'est la première chose à vérifier — l'ouverture se demande au support.</div></div>

    <h2 id="a8-domain">Ajouter un domaine</h2>
    <p><strong>✉️ Messagerie → Ajouter un domaine.</strong> Le domaine peut être l'un de ceux de vos sites, ou un autre : les deux sont indépendants.</p>
    <p>Un domaine peut être <strong>désactivé</strong> sans être supprimé — pratique pour couper temporairement la réception sans perdre la configuration des boîtes.</p>

    <h2 id="a8-box">Créer une boîte</h2>
    <p>Depuis la fiche d'un domaine. Trois champs : la partie gauche de l'adresse, le mot de passe, et un <strong>quota</strong> en méga-octets.</p>

    <div class="callout warn"><span class="i">💾</span><div>Le quota n'est pas une formalité. Une boîte sans limite qui reçoit des pièces jointes finit par <strong>remplir le disque du serveur</strong> — et un disque plein met tous vos sites hors ligne, pas seulement la messagerie.</div></div>

    <p>Les comptes sont <strong>virtuels</strong> : ils n'existent que pour Postfix et Dovecot, jamais comme comptes système. Une boîte compromise ne donne aucun accès au serveur.</p>

    <p>Depuis la même fiche, on peut ensuite : <strong>réinitialiser le mot de passe</strong>, <strong>désactiver</strong> une boîte sans la supprimer, ou la <strong>supprimer</strong> — auquel cas les messages qu'elle contient partent avec.</p>

    <h2 id="a8-alias">Les alias</h2>
    <p>Un alias redirige une adresse vers une autre, sans créer de boîte. C'est ce qui permet d'avoir <code>contact@</code>, <code>info@</code> et <code>support@</code> qui arrivent tous dans la même boîte réelle.</p>
    <p>Deux adresses méritent d'exister sur tout domaine qui envoie du courrier : <code>postmaster@</code> et <code>abuse@</code>. Certains services les vérifient, et leur absence compte contre vous.</p>

    <h2 id="a8-client">Relever son courrier</h2>
    <p>Les réglages à donner à un client de messagerie :</p>
    <pre><code><?= $h($code_client) ?></code></pre>
    <p>L'identifiant est <strong>l'adresse complète</strong>, pas seulement la partie gauche — c'est la confusion la plus fréquente au premier essai.</p>

    <div class="callout"><span class="i">🔒</span><div>Pour que le chiffrement fonctionne sans avertissement, le nom du serveur — <code>mail.exemple.com</code> — doit porter un certificat. Obtenez-le comme pour un site : créez-le dans <strong>Sites web</strong>, puis demandez son certificat depuis l'onglet HTTPS.</div></div>

    <h2 id="a8-check">Vérifier que ça marche</h2>
    <ol class="steps">
      <li><strong>Envoyez-vous un message</strong> depuis une adresse extérieure. S'il n'arrive pas, le problème est en réception : MX ou pare-feu.</li>
      <li><strong>Envoyez vers l'extérieur</strong>, vers une adresse chez un grand fournisseur. Regardez s'il arrive, et surtout <em>où</em> — boîte de réception ou indésirables.</li>
      <li>S'il tombe en indésirables, <strong>ouvrez l'en-tête complet</strong> du message reçu : il indique le résultat des contrôles SPF et DMARC, ce qui pointe directement l'enregistrement à corriger.</li>
      <li>Un service de test d'envoi en ligne donne le même diagnostic, en plus lisible, y compris le reverse DNS.</li>
    </ol>

    <div class="callout"><span class="i">⏳</span><div>Une modification DNS ne prend pas effet immédiatement. Comptez de quelques minutes à quelques heures selon le TTL de votre zone. Ne changez pas cinq choses à la fois : vous ne sauriez plus laquelle a agi.</div></div>

    <h2 id="a8-honest">Ce qu'il faut savoir avant de se lancer</h2>
    <p>Héberger sa messagerie est parfaitement faisable, et cette page vous en donne les moyens. Mais autant le dire franchement : <strong>c'est le service le plus exigeant qu'on puisse mettre sur un serveur</strong>.</p>
    <ul>
      <li>La <strong>réputation</strong> de votre adresse IP se construit lentement et se perd vite. Une IP neuve démarre sans historique, ce qui n'aide pas.</li>
      <li>Les grands fournisseurs appliquent leurs propres règles, qui changent.</li>
      <li>Un serveur mal réglé peut devenir relais à spam, et l'IP finit sur des listes noires dont on sort difficilement.</li>
    </ul>
    <p>Si votre besoin se limite à <strong>envoyer</strong> les messages de vos sites, Postfix seul — sans boîtes — répond à la question avec beaucoup moins d'entretien. Et si l'enjeu est un courrier professionnel qui doit arriver à coup sûr, un service dédié reste plus sage. Ce panneau vous laisse choisir en connaissance de cause plutôt que de vous vendre la facilité.</p>

    <div class="doc-foot">
      <span>AegisHost · partie 8 : la messagerie</span>
      <span><a href="<?= $h($github) ?>" target="_blank" rel="noopener">Dépôt GitHub ↗</a></span>
    </div>

<?php require __DIR__ . '/../../inc/foot.php'; ?>
