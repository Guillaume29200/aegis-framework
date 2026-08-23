<?php
/**
 * documentation/doc-faq-forum.php — FAQ du module Forum.
 * Deux niveaux : 🟢 Débutant · 🔵 Avancé.
 */
$docPage = 'faq/doc-faq-forum.php';
$seo = [
    'title'     => 'FAQ — Module Forum (Aegis)',
    'desc'      => "Foire aux questions du module Forum d'Aegis : catégories, sujets & messages (Markdown), modération, récompenses/XP, sondages, réactions, thèmes, widgets, shoutbox, profils et sécurité. Deux niveaux Débutant / Avancé.",
    'canonical' => 'https://gamenodepanel.com/documentation/doc-faq-forum.php',
];
require __DIR__ . '/../inc/head.php';

$faq = [
  ['id'=>'fq-intro','icon'=>'🗨️','title'=>'Présentation','q'=>[
    ['d','Qu\'est-ce que le module Forum ?','Un <strong>forum communautaire complet</strong> : catégories/sous-catégories, sujets, messages, réactions, sondages, système de réputation/récompenses, profils, shoutbox… Il s\'installe sur Aegis Framework comme n\'importe quel module.'],
    ['d','Comment y accéder ?','Le forum public est sur <code>/forum</code> ; l\'administration sur <strong>Admin → Forum</strong> (<code>/admin/forum</code>).'],
    ['a','Quelle est l\'ampleur technique du module ?','Environ <strong>26 tables</strong> <code>forum_*</code> et <strong>~110 routes</strong> (front <code>/forum/*</code>, API <code>/forum/api/*</code>, admin <code>/admin/forum/*</code>). Le rendu des messages utilise un <strong>Markdown maison</strong> (aucune dépendance). Couplage uniquement vers <code>users</code> et <code>forum_*</code>.'],
  ]],
  ['id'=>'fq-categories','icon'=>'📁','title'=>'Catégories','q'=>[
    ['d','Comment créer une catégorie ?','<strong>Admin → Forum → Catégories → Nouvelle</strong> : titre, description, icône et ordre d\'affichage.'],
    ['d','Puis-je faire des sous-catégories ?','Oui : choisissez une <strong>catégorie parente</strong> lors de la création pour créer une hiérarchie.'],
    ['a','Comment restreindre l\'accès à une catégorie ?','La visibilité s\'appuie sur les rôles/permissions ; combinée aux <strong>permissions modérateurs</strong> par catégorie pour la gestion.'],
  ]],
  ['id'=>'fq-sujets','icon'=>'💬','title'=>'Sujets & messages','q'=>[
    ['d','Comment épingler ou verrouiller un sujet ?','Depuis l\'admin <strong>Sujets</strong> (ou les outils de modération) : un sujet épinglé reste en haut ; verrouillé, il n\'accepte plus de réponses.'],
    ['d','Comment se met en forme un message ?','Avec le <strong>Markdown maison</strong> du forum : gras, italique, listes, citations, blocs de code, liens — sans éditeur lourd ni dépendance JS.'],
    ['a','Comment fonctionnent les tags ?','Les sujets sont reliés aux tags via <code>forum_topic_tags</code> (table de liaison), ce qui permet le filtrage et le regroupement par étiquette.'],
  ]],
  ['id'=>'fq-moderation','icon'=>'🛡️','title'=>'Modération & signalements','q'=>[
    ['d','Comment traiter un message signalé ?','<strong>Admin → Forum → Signalements</strong> : consultez le contenu signalé, agissez (avertir/supprimer/déplacer) puis clôturez le signalement.'],
    ['d','Comment nommer un modérateur ?','<strong>Admin → Forum → Permissions modérateurs</strong> : attribuez des droits, le cas échéant <strong>par catégorie</strong>.'],
    ['a','Quels droits un modérateur peut-il avoir ?','Des permissions granulaires (épingler, verrouiller, déplacer, supprimer, éditer…) configurables par catégorie, indépendamment du rôle global.'],
  ]],
  ['id'=>'fq-recompenses','icon'=>'🏆','title'=>'Réputation & récompenses','q'=>[
    ['d','C\'est quoi le système de récompenses ?','Les membres gagnent de l\'<strong>XP / réputation</strong> selon leur activité (messages, réactions reçues…) et peuvent débloquer des <strong>récompenses / paliers</strong>.'],
    ['a','Comment est-ce suivi techniquement ?','Un journal d\'XP (<code>forum_xp_log</code>) trace les gains, et <code>forum_rewards</code> définit les récompenses/paliers. Les règles d\'attribution sont paramétrables.'],
  ]],
  ['id'=>'fq-sondages','icon'=>'🗳️','title'=>'Sondages','q'=>[
    ['d','Comment ajouter un sondage à un sujet ?','À la création du sujet : ajoutez la question et les options (choix unique ou multiple selon le réglage).'],
    ['d','Puis-je mettre un sondage dans la barre latérale ?','Oui : un <strong>widget de sondage</strong> indépendant du fil de discussion peut être affiché dans la sidebar.'],
    ['a','Où sont stockés les sondages ?','Sujets : <code>forum_polls</code> / <code>forum_poll_options</code> / <code>forum_poll_votes</code>. Sidebar : <code>forum_poll_widgets</code> (+ <code>_options</code> / <code>_votes</code>).'],
  ]],
  ['id'=>'fq-reactions','icon'=>'👍','title'=>'Réactions & votes','q'=>[
    ['d','Les membres peuvent-ils réagir aux messages ?','Oui : <strong>réactions</strong> (emoji) sur les messages et, selon la configuration, des <strong>votes</strong> (pour/contre).'],
    ['a','Quelles tables ?','<code>forum_reactions</code> pour les réactions et <code>forum_votes</code> pour les votes.'],
  ]],
  ['id'=>'fq-themes','icon'=>'🎨','title'=>'Thèmes & apparence','q'=>[
    ['d','Comment changer l\'apparence du forum ?','<strong>Admin → Forum → Thèmes</strong> : sélection/configuration du thème appliqué au forum public.'],
    ['a','Comment c\'est géré ?','Les thèmes sont définis en base et appliqués au front. L\'interface d\'administration du forum est progressivement portée de l\'ancien style <code>.af-*</code> vers le design natif <code>.ui-*</code> d\'Aegis.'],
  ]],
  ['id'=>'fq-widgets','icon'=>'🧩','title'=>'Widgets & navigation','q'=>[
    ['d','Comment personnaliser la barre latérale du forum ?','<strong>Admin → Forum → Widgets sidebar</strong> : activez/ordonnez les widgets (derniers sujets, sondage, shoutbox, etc.).'],
    ['d','Comment ajouter des liens de menu ou des pages ?','<strong>Liens de navigation</strong> pour le menu du forum, et <strong>Pages statiques</strong> pour des pages de contenu (règlement, à propos…).'],
    ['a','Où est-ce stocké ?','<code>forum_sidebar_widgets</code>, <code>forum_nav_links</code> et <code>forum_pages</code>.'],
  ]],
  ['id'=>'fq-shoutbox','icon'=>'📣','title'=>'Shoutbox','q'=>[
    ['d','Qu\'est-ce que la shoutbox ?','Un <strong>mini-chat</strong> pour des messages courts en direct, affichable dans la sidebar. Activable/désactivable depuis l\'admin.'],
    ['a','Stockage ?','Les messages sont dans <code>forum_shoutbox_messages</code>.'],
  ]],
  ['id'=>'fq-profils','icon'=>'👤','title'=>'Profils membres','q'=>[
    ['d','Les membres ont-ils un profil / avatar ?','Oui : <strong>avatar et bannière</strong> personnalisables, plus les infos de profil affichées à côté des messages.'],
    ['a','Comment sont gérés les uploads de profil ?','Stockés via <code>forum_user_profiles</code> ; le dossier d\'uploads est protégé par un <code>.htaccess</code> (exécution PHP désactivée) pour empêcher tout dépôt de script.'],
  ]],
  ['id'=>'fq-reglages','icon'=>'⚙️','title'=>'Réglages','q'=>[
    ['d','Où règle-t-on le forum ?','<strong>Admin → Forum → Réglages</strong> : titre du forum, options d\'affichage et de fonctionnement.'],
    ['a','Comment les réglages sont-ils stockés ?','Dans <code>forum_settings</code> (paires clé/valeur propres au module, indépendantes des réglages globaux d\'Aegis).'],
  ]],
  ['id'=>'fq-secu','icon'=>'🔒','title'=>'Sécurité & maintenance','q'=>[
    ['d','Si je désactive le module, je perds les données ?','Non : le Forum n\'a <strong>pas de script de désinstallation</strong>, donc désactiver le module <strong>conserve toutes les tables</strong> <code>forum_*</code> (données préservées). Vous pourrez le réactiver tel quel.'],
    ['a','Le forum est-il sécurisé ?','Oui : <strong>CSRF</strong> sur tous les POST (helper maison <code>afPost</code> qui ajoute le jeton automatiquement), <strong>uploads protégés</strong> (.htaccess), <strong>requêtes préparées</strong> et <strong>échappement XSS</strong> sur les contenus. Le module ne dépend que de la table <code>users</code>.'],
  ]],
];

$totalD = 0; $totalA = 0;
foreach ($faq as $t) { foreach ($t['q'] as $q) { $q[0] === 'd' ? $totalD++ : $totalA++; } }
$lvlMeta = ['d' => ['🟢 Débutant', 'green'], 'a' => ['🔵 Avancé', 'blue']];
?>

    <h1>FAQ — Module Forum</h1>
    <p class="doc-lead">Les questions courantes sur le forum communautaire d'Aegis — catégories, modération, récompenses, sondages, thèmes — à deux niveaux.</p>
    <div class="doc-meta">
      <span class="doc-pill">🟢 <?= $totalD ?> Débutant</span>
      <span class="doc-pill">🔵 <?= $totalA ?> Avancé</span>
      <span class="doc-pill"><?= count($faq) ?> thèmes</span>
    </div>

    <div class="callout"><span class="i">💡</span><div><strong>Débutant</strong> = « comment faire ». <strong>Avancé</strong> = le fonctionnement technique (tables, sécurité). Filtre et recherche ci-dessous.</div></div>

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
