<?php
/**
 * documentation/faq/doc-faq-aegishost.php — FAQ du module AegisHost.
 * Deux niveaux : 🟢 Débutant · 🔵 Avancé.
 */
$docPage = 'faq/doc-faq-aegishost.php';
$seo = [
    'title'     => 'FAQ — Module AegisHost',
    'desc'      => "Foire aux questions d'AegisHost : installation sur Debian, agent privilégié, sites et PHP par site, HTTPS, Docker, Logithèque, sauvegardes et restauration, sécurité. Deux niveaux Débutant / Avancé.",
    'canonical' => 'https://gamenodepanel.com/documentation/faq/doc-faq-aegishost.php',
];
require __DIR__ . '/../inc/head.php';

$faq = [
  ['id'=>'ah-general','icon'=>'🌐','title'=>'Généralités','q'=>[
    ['d','À quoi sert AegisHost ?','À administrer un <strong>serveur Debian</strong> depuis le navigateur : créer des sites, leur attribuer une version de PHP, obtenir leurs certificats, poser des bases de données et des comptes FTP, piloter Docker, sauvegarder. Ce qu\'on fait d\'habitude en SSH devient une case à cocher.'],
    ['d','Quelle différence avec le module Sauvegarde ?','Ils ne se recouvrent pas. <strong>Sauvegarde</strong> sauvegarde le CMS — le framework, ses modules, ses tables. <strong>AegisHost</strong> sauvegarde la <strong>machine</strong> : les sites hébergés, leurs bases, les données des conteneurs, la configuration du serveur. Deux métiers, deux endroits.'],
    ['d','Faut-il GameNodePanel ?','Non. AegisHost est un module Aegis autonome : il ne requiert que <code>Auth</code>.'],
    ['d','Sur quels systèmes ça tourne ?','<strong>Debian 12 (Bookworm)</strong> et <strong>Debian 13 (Trixie)</strong>, en amd64 ou arm64. L\'installateur refuse les autres distributions plutôt que de tenter sa chance : chemins, noms de paquets et dépôts diffèrent, et une installation à moitié réussie est plus dure à réparer qu\'une installation refusée.'],
    ['d','Puis-je l\'installer sur un serveur qui héberge déjà des sites ?','C\'est possible mais déconseillé. AegisHost pose sa propre pile ; l\'installateur détecte ce qui est déjà là et vous prévient. Le plus sûr reste un serveur neuf.'],
    ['a','Combien de ressources faut-il ?','<strong>768 Mo de RAM</strong> et <strong>5 Go</strong> de disque libres au minimum, ports 80 et 443 disponibles. Ce sont des planchers : un serveur qui héberge plusieurs sites PHP et des conteneurs demandera davantage.'],
  ]],

  ['id'=>'ah-agent','icon'=>'🔐','title'=>'L\'agent privilégié','q'=>[
    ['d','Pourquoi un script root sur ma machine ?','Parce que créer un site demande les droits root — écrire un hôte virtuel, poser un pool PHP-FPM, recharger le serveur web — et que PHP tourne en <code>www-data</code>. Plutôt que de faire tourner le panneau entier en root, comme le font la plupart des panneaux, un <strong>unique script</strong> a ces droits.'],
    ['d','C\'est plus sûr, vraiment ?','Oui, et c\'est mesurable : si le panneau tourne en root, la moindre faille dans une page PHP donne la machine. Ici, une faille donne au mieux la possibilité d\'appeler une action d\'une <strong>liste fermée</strong>, avec des options que l\'agent revalide de son côté.'],
    ['a','Le panneau peut-il envoyer une commande arbitraire ?','Non. Il <strong>nomme une action</strong> et fournit des options. Aucune ligne de commande ne transite. Les secrets passent par l\'entrée standard, jamais en argument — la ligne de commande d\'un processus est lisible par tous dans <code>ps</code>.'],
    ['a','Et l\'accès à Docker ?','Le panneau n\'accède <strong>jamais</strong> à <code>/var/run/docker.sock</code>. Pouvoir parler à ce socket équivaut à être root : il suffit de lancer un conteneur qui monte <code>/</code>. Tout passe par l\'agent.'],
    ['a','L\'agent peut-il être remplacé par un fichier malveillant ?','Il est <strong>signé cryptographiquement</strong> et vérifie sa propre signature avant toute mise à jour de lui-même. Un fichier modifié, tronqué ou substitué est refusé.'],
  ]],

  ['id'=>'ah-install','icon'=>'⚙️','title'=>'Installation & mise à jour','q'=>[
    ['d','« le répertoire lib/ est introuvable »','Vous n\'avez copié que <code>install.sh</code>. Reprenez le dossier <code>install/</code> <strong>en entier</strong> : l\'installateur est découpé en modules.'],
    ['d','« bad interpreter » ou « $\'\\r\': command not found »','Le fichier a été transféré depuis Windows et porte des retours chariot. Corrigez-le : <code>sed -i \'s/\\r$//\' install.sh</code> puis <code>chmod +x install.sh</code>.'],
    ['d','Le panneau me dit que l\'agent est trop ancien. Que faire ?','Ouvrez <strong>🛟 Aide et réparation</strong> : la mise à jour de l\'agent y est proposée en un clic, avec l\'agent signé livré dans le module. Si elle n\'apparaît pas, l\'agent en place est trop ancien pour savoir se remplacer — passez une fois par <code>sudo bash install.sh --agent-only</code>.'],
    ['d','Le module et l\'agent doivent-ils avoir la même version ?','Pas la même, mais des versions <strong>compatibles</strong>. Chaque version du module annonce l\'agent qu\'elle réclame dans son journal des modifications. L\'inverse — agent récent, module ancien — ne pose aucun problème.'],
    ['d','L\'installation s\'est interrompue.','Relancez <code>./install.sh</code> : les étapes terminées sont reconnues et passées, vos réponses sont conservées. <code>--force</code> rejoue une étape malgré tout, <code>--fresh</code> repose toutes les questions.'],
    ['a','Puis-je installer sans interaction ?','Oui : <code>--unattended</code> avec au minimum <code>--domain</code> et <code>--email</code>. Toutes les réponses de l\'assistant ont leur option, ou se chargent depuis un fichier avec <code>--config=</code>.'],
    ['a','Où sont les journaux d\'installation ?','<code>/var/log/aegishost-install.log</code>, suivi en direct avec <code>tail -f</code>. Le journal de l\'agent, lui, est dans <code>/var/log/aegishost-agent.log</code> et s\'affiche dans <strong>📜 Journal</strong>.'],
  ]],

  ['id'=>'ah-sites','icon'=>'🌍','title'=>'Sites, PHP & HTTPS','q'=>[
    ['d','Puis-je avoir plusieurs versions de PHP ?','Oui, elles cohabitent. Chaque site choisit la sienne dans sa fiche : un vieux site reste en 8.1 pendant que le suivant se développe en 8.5.'],
    ['d','Le certificat Let\'s Encrypt échoue.','Neuf fois sur dix, le domaine ne pointe pas encore vers le serveur ou la propagation DNS n\'est pas finie. C\'est Let\'s Encrypt qui vérifie, pas le panneau. Attendez, puis relancez depuis <strong>Sites web → le site → onglet HTTPS</strong>.'],
    ['d','La liste affiche « ⚠️ Non servi » sur un certificat. Pourquoi ?','Le certificat est valide, mais <strong>aucun hôte virtuel ne le sert</strong> : le site répond en clair avec un certificat qui dort sur le disque. Cela arrive quand un site est recréé sous le même nom. Réémettez-le depuis l\'onglet HTTPS.'],
    ['d','Pourquoi mon site est-il dans un sous-dossier « public » ?','Parce que lui seul est servi par le serveur web. Tout ce qui vit à côté — dépendances, <code>.env</code>, fichiers de configuration — reste hors de portée d\'un visiteur. Sans ce sous-dossier, déposer un <code>.env</code> à la racine le publierait sur Internet, et ça ne se voit pas.'],
    ['a','Puis-je régler PHP site par site ?','Oui : chaque site a son propre pool FPM. Mémoire, temps d\'exécution, taille d\'envoi, nombre de variables. Un site qui réclame 512 Mo ne les impose pas aux autres.'],
    ['a','Pourquoi ne puis-je pas désactiver shell_exec sur mon site principal ?','Parce que c\'est le site qui <strong>héberge le panneau</strong>, et que ce sont ces fonctions qui lui permettent d\'appeler son agent. Les couper là rendrait le panneau muet. Sur les autres sites, vous en faites ce que vous voulez.'],
    ['a','J\'ai des sites créés à la main. Puis-je les récupérer ?','Oui : le panneau sait scanner les hôtes virtuels présents et proposer de les adopter. Un hôte virtuel écrit à la main n\'est jamais écrasé — le panneau reconnaît sa propre signature et oriente vers l\'import.'],
  ]],

  ['id'=>'ah-docker','icon'=>'🐳','title'=>'Docker','q'=>[
    ['d','L\'écran Volumes dit « aucun volume » alors que mes applications tournent.','C\'est normal. Les applications du catalogue n\'utilisent <strong>pas</strong> de volumes Docker : elles écrivent dans des dossiers ordinaires, sous <code>/srv/&lt;nom&gt;/donnees/</code>. Un dossier se visite en SSH, se copie et se sauvegarde ; un volume Docker ne se regarde qu\'à travers Docker.'],
    ['d','Mon serveur de jeu tourne mais personne ne peut le rejoindre.','Le port n\'est pas ouvert dans le pare-feu. Cochez « Ouvrir le port dans le pare-feu » à la création, ou ajoutez la règle depuis l\'écran Sécurité. Le conteneur, lui, va très bien.'],
    ['d','Un joueur Minecraft ne peut pas rejoindre mon serveur.','Vérifiez la <strong>version</strong> : Java et Bedrock sont deux mondes séparés. Un joueur Java ne peut pas rejoindre un serveur Bedrock, ni l\'inverse. Consoles et mobiles, c\'est Bedrock ; launcher sur ordinateur, c\'est Java.'],
    ['d','Où est la clé d\'administration de TeamSpeak ?','Dans les <strong>journaux du conteneur</strong>, au tout premier démarrage, et elle n\'apparaît qu\'une fois. Fiche du conteneur → onglet Journaux, cherchez « token ». Sans elle, vous ne pourrez pas administrer votre serveur.'],
    ['a','Mon conteneur redémarre en boucle sur une erreur de permission.','L\'image tourne sous un identifiant qui n\'a pas le droit d\'écrire dans son dossier. Le panneau <strong>demande à l\'image</strong> sous quel identifiant elle tourne et attribue le dossier en conséquence — mais il ne touche qu\'aux dossiers qu\'il vient de créer ou qui sont vides. Supprimez le conteneur et son dossier, puis réinstallez.'],
    ['a','Puis-je publier un conteneur sur un domaine ?','Oui, onglet <strong>Domaine</strong> : le panneau pose devant lui un hôte virtuel qui relaie. Le domaine devient une adresse ordinaire du serveur web, avec certificat et journaux. Cela vaut pour les applications web — un serveur de jeu se joint par IP et port.'],
    ['a','Les jauges affichent 100 %. Mon serveur est saturé ?','Pas forcément : elles sont plafonnées à 100 % de ce que le <strong>conteneur</strong> a le droit de prendre. Avec une limite d\'un cœur, 100 % veut dire « il consomme tout son quota », pas « il consomme tout le serveur ».'],
  ]],

  ['id'=>'ah-backup','icon'=>'💾','title'=>'Sauvegardes','q'=>[
    ['d','Une sauvegarde locale suffit-elle ?','Non. Elle protège de l\'<strong>erreur de manipulation</strong> — un site supprimé, une base vidée — et c\'est déjà l\'essentiel de ce qui arrive. Elle ne protège pas de la panne de disque, du VPS résilié ni d\'un rançongiciel. Pour cela il faut une copie <strong>ailleurs</strong>.'],
    ['d','Le panneau refuse ma sauvegarde faute de place.','Il exige autant de place libre que la <strong>taille brute</strong> de ce qui est coché. C\'est volontairement pessimiste : le taux de compression ne se connaît qu\'après coup, et remplir <code>/var</code> mettrait tous vos sites hors ligne. Décochez, ou faites de la place.'],
    ['d','Que veut dire « Interrompue » ?','La sauvegarde n\'est jamais allée à son terme : son manifeste manque, on ignore ce qu\'elle contient. Elle <strong>ne se restaure pas</strong>, et n\'offre aucun bouton. Supprimez-la et relancez-en une.'],
    ['d','Que se passe-t-il si je restaure la mauvaise archive ?','Rien d\'irréversible. Ce qui est remplacé est <strong>mis de côté</strong>, pas détruit : les fichiers d\'un site sont renommés en <code>.avant-restauration-&lt;date&gt;</code> à côté d\'eux-mêmes, une base est exportée avant d\'être réécrite.'],
    ['d','Puis-je restaurer un seul site ?','Oui. Une sauvegarde est un dossier de pièces séparées, et l\'on coche ce qui doit revenir. C\'est précisément pour cela qu\'elle n\'est pas une archive unique.'],
    ['a','Pourquoi la configuration n\'est-elle pas réappliquée automatiquement ?','Parce que réécrire hôtes virtuels, pools et certificats sur un serveur qui tourne mettrait tous les sites hors ligne d\'un coup — <strong>dont celui qui porte le panneau</strong>, donc sans moyen de revenir en arrière depuis l\'écran. Elle est déballée à côté, et vous copiez ce dont vous avez besoin.'],
    ['a','Quels stockages hors-site sont pris en charge ?','Tout ce qui parle <strong>S3</strong> : Scaleway, Backblaze B2, OVH, Wasabi, MinIO, AWS. Ce n\'est pas un choix d\'éditeur mais de dialecte — changer de fournisseur ne demande que de changer quelques champs.'],
    ['a','Que se passe-t-il si je perds la phrase secrète ?','La copie hors-site devient <strong>illisible</strong>, définitivement. Elle est enregistrée sur cette machine et sur elle seule — or le jour où vous restaurerez depuis le dépôt distant, ce sera probablement parce que cette machine n\'existe plus. Notez-la ailleurs.'],
    ['a','Comment vérifier que la planification tourne vraiment ?','L\'écran affiche l\'état réel de la minuterie, relu sur la machine : dernière exécution, prochaine, sort du dernier passage. Depuis le serveur : <code>systemctl list-timers aegishost-backup.timer</code>. Un échec du dernier passage est signalé en rouge.'],
  ]],

  ['id'=>'ah-secu','icon'=>'🛡️','title'=>'Sécurité','q'=>[
    ['d','Vais-je m\'enfermer dehors en durcissant SSH ?','Non, si vous suivez l\'écran. L\'agent <strong>arme un retour arrière</strong> avant de recharger SSH : sans confirmation depuis une <strong>nouvelle</strong> connexion dans les dix minutes, la machine revient d\'elle-même à sa configuration précédente. Ne confirmez qu\'après avoir testé.'],
    ['d','Je me suis banni moi-même avec Fail2ban.','Ça arrive à tout le monde. Le débannissement est dans le panneau, précisément pour ce cas : si vous n\'êtes plus le bienvenu en SSH, il vous reste le navigateur. <strong>🛡️ Sécurité</strong> → trouvez votre adresse → Débannir.'],
    ['d','ClamAV surveille-t-il en permanence ?','Non, il analyse <strong>à la demande</strong>. C\'est le bon compromis sur un serveur web : un antivirus résident coûterait cher en performance pour un gain douteux. Son usage le plus utile est de contrôler ce que vos visiteurs déposent.'],
    ['a','Le panneau a refusé de retirer un composant. Pourquoi ?','Parce que ce retrait couperait l\'accès à la machine ou mettrait tous les sites hors ligne. Et quand un retrait passe, le panneau <strong>relit la configuration du serveur web</strong> juste après : si elle ne passe plus le test, l\'opération est rapportée comme un échec — vos sites tournent encore, mais tomberaient au prochain redémarrage.'],
  ]],

  ['id'=>'ah-mail','icon'=>'✉️','title'=>'Messagerie','q'=>[
    ['d','Je ne vois pas l\'entrée Messagerie dans le menu.','Elle n\'apparaît qu\'une fois la messagerie <strong>installée</strong>. Posez « Envoi d\'e-mails » depuis la Logithèque. Un menu qui mène vers un écran vide ne rend service à personne.'],
    ['d','Mes messages partent en indésirables.','C\'est presque toujours le DNS. Vérifiez dans l\'ordre : <strong>SPF</strong>, <strong>DMARC</strong>, et surtout le <strong>reverse DNS</strong> de votre IP — celui-ci se règle chez votre hébergeur, pas dans votre zone ni dans ce panneau.'],
    ['d','Mes messages ne partent pas du tout.','Beaucoup d\'hébergeurs bloquent le <strong>port 25 sortant</strong> par défaut sur les VPS, pour lutter contre le spam. C\'est la première chose à vérifier ; l\'ouverture se demande au support.'],
    ['a','Faut-il vraiment héberger sa messagerie ?','C\'est faisable, et c\'est le service le plus exigeant qu\'on puisse mettre sur un serveur : la réputation d\'une IP se construit lentement et se perd vite. Si votre besoin se limite à <strong>envoyer</strong> les messages de vos sites, Postfix seul — sans boîtes — demande beaucoup moins d\'entretien.'],
  ]],
];

$totalD = 0; $totalA = 0;
foreach ($faq as $t) { foreach ($t['q'] as $q) { $q[0] === 'd' ? $totalD++ : $totalA++; } }
$lvlMeta = ['d' => ['🟢 Débutant', 'green'], 'a' => ['🔵 Avancé', 'blue']];
?>

    <h1>FAQ — Module AegisHost</h1>
    <p class="doc-lead">Installer, administrer et dépanner un serveur avec AegisHost. Questions à deux niveaux.</p>
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
