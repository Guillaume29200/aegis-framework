<?php
/**
 * documentation/doc-faq-gnp.php — FAQ de GameNodePanel (module).
 * Deux niveaux : 🟢 Débutant · 🔵 Avancé.
 */
$docPage = 'faq/doc-faq-gnp.php';
$seo = [
    'title'     => 'FAQ — GameNodePanel',
    'desc'      => "Foire aux questions de GameNodePanel : serveurs hôtes (VPS), serveurs de jeu, installation (SteamCMD/Docker), panels par jeu, VEGA, O.D.I.N, Database Manager, FTP, espace membre et licence. Deux niveaux Débutant / Avancé.",
    'canonical' => 'https://gamenodepanel.com/documentation/doc-faq-gnp.php',
];
require __DIR__ . '/../inc/head.php';

$faq = [
  ['id'=>'gnp-archi','icon'=>'🎮','title'=>'Présentation & architecture','q'=>[
    ['d','Qu\'est-ce que GameNodePanel (GNP) ?','Un panneau d\'administration tout-en-un pour <strong>héberger et gérer vos serveurs de jeu</strong> (installation, console, fichiers, monitoring, IA). C\'est un <strong>module</strong> qui s\'installe sur Aegis Framework.'],
    ['d','Ai-je besoin d\'un serveur séparé pour les jeux ?','Oui. Le panel tourne sur un hébergement web ; les serveurs de jeu tournent sur un <strong>VPS / dédié Debian</strong> que GNP pilote via SSH. Voir <a href="modules/gnp/doc-prerequis.php">Prérequis</a>.'],
    ['a','Qu\'est-ce que l\'architecture « hybride » ?','GNP utilise des <strong>panels natifs</strong> (installation directe via SSH/SteamCMD) pour les jeux populaires, et un <strong>panel Docker</strong> pour les jeux conteneurisés. Le bon mode est choisi automatiquement selon le jeu (<code>game_runtime_type</code> de la version).'],
  ]],
  ['id'=>'gnp-hotes','icon'=>'🖥️','title'=>'Serveurs hôtes (VPS)','q'=>[
    ['d','Comment ajouter un serveur hôte ?','<strong>Game Node Panel → Infrastructure → Serveurs hôtes → Nouveau</strong> : IP, port SSH, utilisateur (souvent <code>root</code>) et mot de passe ou clé SSH. Testez la connexion avant d\'enregistrer.'],
    ['d','Sous quel OS doit tourner l\'hôte ?','<strong>Debian 12 ou 13</strong>, avec SSH actif. Si la connexion root est refusée, voir le tuto <a href="tutoriels/tuto-ssh-root.php">Activer le login root SSH</a>.'],
    ['d','Comment supprimer un serveur hôte ?','Bouton 🗑️ : un modal prévient qu\'il supprime <strong>aussi tous les serveurs de jeu</strong> hébergés. Si l\'hôte ne répond pas (VPS éteint), une option <strong>« Forcer la suppression »</strong> nettoie quand même côté panel.'],
    ['a','Comment GNP dialogue-t-il avec l\'hôte ?','En <strong>SSH</strong> (phpseclib). Les mots de passe SSH sont <strong>chiffrés (AES-256)</strong> en base. SteamCMD, ProFTPD et l\'agent O.D.I.N sont installés automatiquement à la préparation de l\'hôte.'],
    ['d','Comment ajouter des IP failover / additionnelles à un hôte ?','Sur la <strong>fiche détail de l\'hôte</strong> (Serveurs hôtes → votre hôte), carte <strong>« 🌐 Adresses IP de l\'hôte »</strong> : ajoutez/supprimez vos IP failover (avec un libellé). L\'IP principale (celle du SSH) y figure en lecture seule. Détails : <a href="modules/gnp/doc-host.php#host-ips">Prérequis serveur hôte → IP failover</a>.'],
    ['d','À quoi servent ces IP additionnelles ?','À la <strong>création d\'un serveur de jeu</strong>, un menu « Adresse IP » vous laisse choisir sur quelle IP de l\'hôte l\'exposer (principale ou failover). L\'IP choisie est mémorisée et affichée dans le panel du serveur.'],
    ['a','GNP configure-t-il l\'IP au niveau réseau/OS ?','<strong>Non.</strong> GNP se contente de <strong>répertorier, proposer et mémoriser</strong> l\'IP. Le <strong>routage de l\'IP failover vers l\'hôte</strong> (et son éventuelle config <code>netplan</code>/<code>ip addr</code> ou le binding du jeu) reste à faire chez votre hébergeur / sur l\'OS.'],
  ]],
  ['id'=>'gnp-serveurs','icon'=>'🕹️','title'=>'Serveurs de jeu','q'=>[
    ['d','Comment créer un serveur de jeu ?','<strong>Serveurs de jeux → Gérer les serveurs → Créer</strong> : choisissez l\'hôte, le jeu, les ports et le nombre de slots.'],
    ['d','Puis-je attribuer un serveur à un client ?','Oui : un serveur est rattaché à un utilisateur, qui le retrouve dans son <strong>espace membre</strong>.'],
    ['a','Pourquoi GNP refuse-t-il de créer un serveur ?','Parce qu\'aucun <strong>serveur hôte</strong> n\'est encore configuré : la création est volontairement bloquée tant qu\'il n\'y a pas d\'hôte (on ne peut pas héberger un jeu sans machine).'],
  ]],
  ['id'=>'gnp-install','icon'=>'⬇️','title'=>'Installer un jeu','q'=>[
    ['d','Comment installer le jeu sur le serveur ?','Depuis le serveur, bouton <strong>Installer</strong> : choisissez la version puis le mode (⚡ rapide via template, ou 📥 complet via téléchargement). Une page de suivi affiche la progression en direct.'],
    ['d','Le jeu demande un compte Steam, c\'est normal ?','Oui pour certains jeux : saisissez un identifiant Steam (et le code <strong>Steam Guard</strong> reçu par e-mail si activé). D\'autres jeux se téléchargent anonymement.'],
    ['d','L\'installation tourne en rond / mon VPS est injoignable, que faire ?','La page de suivi <strong>détecte l\'absence de réponse</strong> et bascule en échec après un délai (au lieu de tourner à l\'infini). Vous pouvez relancer, ou — pour tester — « marquer comme installé » sans VPS (panel Standard).'],
    ['a','Comment se déroule l\'installation techniquement ?','Un <strong>worker en arrière-plan</strong> lance l\'install (SteamCMD/Git/Java en natif, ou <code>docker pull/run</code>), et les logs sont streamés (SSE/polling) ; le suivi est stocké dans <code>gnp_installations</code>.'],
    ['a','Docker ou installation native, qui décide ?','La <strong>version du jeu</strong> : si <code>game_runtime_type = docker</code>, GNP route vers le panel/installeur Docker (conteneur isolé) ; sinon installation native sur l\'hôte.'],
  ]],
  ['id'=>'gnp-panel','icon'=>'🎛️','title'=>'Gérer un serveur','q'=>[
    ['d','Comment démarrer, arrêter ou redémarrer un serveur ?','Avec les boutons <strong>Démarrer / Arrêter / Redémarrer</strong> du panel du serveur.'],
    ['d','Comment accéder à la console et envoyer des commandes ?','Onglet <strong>Console</strong> du panel : logs en direct + champ pour envoyer des commandes au serveur.'],
    ['d','Comment accéder aux fichiers du serveur ?','Via le <strong>gestionnaire de fichiers (FTP)</strong> intégré au panel, ou via le compte FTP dédié (client externe).'],
    ['d','Comment sauvegarder mon serveur ?','Selon le jeu, onglet <strong>Sauvegardes</strong> (création/restauration), par exemple sur Minecraft.'],
    ['a','Comment fonctionne la console / le suivi temps réel ?','Le panel interroge périodiquement l\'hôte (lecture des logs en SSH) et, pour l\'état du serveur, le protocole maison <strong>GNPQ</strong> (joueurs, map, ping).'],
  ]],
  ['id'=>'gnp-jeux','icon'=>'🧩','title'=>'Panels par jeu','q'=>[
    ['d','Quels jeux ont un panneau sur mesure ?','<strong>Minecraft</strong>, <strong>FiveM/RedM</strong>, <strong>Don\'t Starve Together</strong>, <strong>Battlefield</strong>, <strong>Hytale</strong>, <strong>serveurs vocaux</strong> (TeamSpeak), un panel <strong>Docker</strong> pour les jeux conteneurisés, et un panel <strong>Standard</strong> pour tous les autres jeux Steam/dédiés.'],
    ['d','Minecraft : puis-je gérer mondes, plugins et properties ?','Oui : le panel Minecraft gère <strong>mondes, versions, server.properties, whitelist, sauvegardes</strong> et l\'installation de mods/plugins via le marketplace <strong>Modrinth</strong>.'],
    ['a','Comment GNP choisit-il le bon panel ?','Le <code>GamePanelRouter</code> mappe le <code>viewer_id</code>/catégorie du jeu (ou le runtime Docker) vers le contrôleur de panel adapté. Chaque type de jeu a ses propres vues dans son dossier.'],
  ]],
  ['id'=>'gnp-catalogue','icon'=>'📚','title'=>'Catalogue de jeux','q'=>[
    ['d','Comment ajouter un jeu au catalogue ?','<strong>Catalogues → Jeux & mods → Nouveau</strong> (formulaire), ou via un <strong>import JSON</strong>.'],
    ['d','Puis-je importer / exporter mes jeux ?','Oui : boutons <strong>Import / Export JSON</strong> sur la page Jeux — pratique pour transférer une config entre installations.'],
    ['a','Comment un jeu est-il structuré ?','Un jeu possède une ou plusieurs <strong>versions</strong> (avec <code>game_runtime_type</code> classic/docker, <code>requires_gslt</code>, chemin d\'install) et, optionnellement, un <strong>schéma de configuration JSON</strong> (par <code>viewer_id</code>) pour générer l\'éditeur de réglages.'],
  ]],
  ['id'=>'gnp-plugins','icon'=>'🛒','title'=>'Plugins & mods','q'=>[
    ['d','Comment installer des plugins ou des mods ?','Via le <strong>Marketplace</strong> de plugins (ou <strong>Modrinth</strong> pour Minecraft) : installation en un clic, déployée sur le serveur via SSH.'],
    ['a','Où est suivi ce qui est installé ?','Les plugins installés par serveur sont enregistrés (table <code>gnp_installed_plugins</code>) et affichés dans le panel du serveur.'],
  ]],
  ['id'=>'gnp-vega','icon'=>'🤖','title'=>'VEGA (IA)','q'=>[
    ['d','À quoi sert VEGA ?','C\'est l\'<strong>IA d\'analyse de logs</strong> : elle surveille les logs du serveur en temps réel et signale/explique les anomalies (erreurs, comportements suspects).'],
    ['a','Comment VEGA fonctionne-t-il ?','Collecte des logs → détection par patterns → analyse via un <strong>fournisseur IA</strong> (Claude/GPT/Mistral configuré dans Aegis → Modèles IA) → actions et rapports. Aucun autre panel concurrent n\'offre d\'IA native équivalente.'],
  ]],
  ['id'=>'gnp-odin','icon'=>'📡','title'=>'O.D.I.N (monitoring)','q'=>[
    ['d','Qu\'est-ce qu\'O.D.I.N ?','La <strong>supervision d\'infrastructure</strong> : un agent léger sur l\'hôte remonte CPU, RAM, disque, réseau, et une <strong>carte des connexions/attaques</strong> en direct.'],
    ['d','Comment activer le monitoring d\'un hôte ?','Depuis la fiche du serveur hôte, <strong>déployez l\'agent O.D.I.N</strong> (installation guidée). Le tableau de bord O.D.I.N affiche ensuite les métriques.'],
    ['a','Comment l\'agent transmet-il ses données ?','Il envoie ses métriques en <code>POST /api/odin/metrics</code>, authentifié par une clé <code>X-ODIN-KEY</code> propre au serveur. Les données alimentent les anomalies et corrélations.'],
  ]],
  ['id'=>'gnp-db','icon'=>'🗄️','title'=>'Database Manager','q'=>[
    ['d','Comment créer une base MySQL pour un serveur de jeu ?','<strong>Infrastructure → Serveurs MySQL / Toutes les BDD</strong> : provisionnez une base dédiée sur l\'hôte.'],
    ['a','Comment les bases sont-elles gérées ?','MySQL est piloté <strong>via SSH</strong> sur l\'hôte : bases dédiées, quotas, <strong>console SQL (DbLite)</strong>, sauvegardes. Identifiants chiffrés ; injections SQL/commande verrouillées (identifiants validés, requêtes via stdin/base64).'],
  ]],
  ['id'=>'gnp-ftp','icon'=>'📂','title'=>'FTP','q'=>[
    ['d','Comment donner un accès FTP au client ?','Un <strong>compte FTP</strong> est créé par serveur de jeu (menu <strong>Comptes FTP</strong>) ; les identifiants sont visibles dans le panel du serveur.'],
    ['a','Quel serveur FTP est utilisé ?','<strong>ProFTPD</strong>, installé automatiquement sur l\'hôte ; GNP crée/réinitialise les comptes et cloisonne l\'accès au dossier du serveur.'],
  ]],
  ['id'=>'gnp-membre','icon'=>'👤','title'=>'Espace membre','q'=>[
    ['d','Mon client peut-il gérer son serveur lui-même ?','Oui : dans son <strong>espace membre → Mes serveurs</strong>, il voit ses serveurs et accède à leur panel (selon le niveau d\'accès activé).'],
    ['a','Le client a-t-il accès à l\'administration ?','Non. Les routes <code>/member/*</code> sont gardées par <strong>propriété</strong> (un membre ne touche que ses propres serveurs, vérifié à chaque action) et n\'exposent <strong>aucune action d\'infrastructure</strong> (hôte, SSH, suppression).'],
  ]],
  ['id'=>'gnp-licence','icon'=>'🔑','title'=>'Licence','q'=>[
    ['d','GameNodePanel est-il payant ?','Aegis Framework est gratuit ; <strong>GNP est un module sous licence</strong> (achat « propriétaire à vie »). La licence se gère via le module Licences.'],
    ['a','Que se passe-t-il si la vérification de licence échoue ?','GNP n\'est <strong>jamais bloqué brutalement</strong> : cache local, période de grâce hors-ligne et <strong>fail-open</strong>. Un mode <strong>« ouvert »</strong> permet même d\'offrir le module sans aucune vérification.'],
  ]],
];

$totalD = 0; $totalA = 0;
foreach ($faq as $t) { foreach ($t['q'] as $q) { $q[0] === 'd' ? $totalD++ : $totalA++; } }
$lvlMeta = ['d' => ['🟢 Débutant', 'green'], 'a' => ['🔵 Avancé', 'blue']];
?>

    <h1>FAQ — GameNodePanel</h1>
    <p class="doc-lead">Les questions courantes sur GameNodePanel — hôtes, serveurs de jeu, installation, panels, IA, monitoring et licence — à deux niveaux.</p>
    <div class="doc-meta">
      <span class="doc-pill">🟢 <?= $totalD ?> Débutant</span>
      <span class="doc-pill">🔵 <?= $totalA ?> Avancé</span>
      <span class="doc-pill"><?= count($faq) ?> thèmes</span>
    </div>

    <div class="callout"><span class="i">💡</span><div><strong>Débutant</strong> = « comment faire ». <strong>Avancé</strong> = le fonctionnement technique. Voir aussi la doc <a href="modules/gnp/doc-gnp.php">GameNodePanel</a> et <a href="modules/gnp/doc-prerequis.php">Prérequis</a>.</div></div>

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
