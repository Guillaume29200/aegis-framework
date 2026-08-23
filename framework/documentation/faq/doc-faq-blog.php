<?php
/**
 * documentation/doc-faq-blog.php — FAQ du module Blogging.
 * Deux niveaux : 🟢 Débutant · 🔵 Avancé.
 */
$docPage = 'faq/doc-faq-blog.php';
$seo = [
    'title'     => 'FAQ — Blog (module Blogging)',
    'desc'      => "Foire aux questions du module Blogging d'Aegis : articles en Markdown, image de couverture, publication programmée, catégories & tags, commentaires modérés, likes, thèmes ZIP, RSS, sitemap et SEO. Deux niveaux Débutant / Avancé.",
    'canonical' => 'https://gamenodepanel.com/documentation/doc-faq-blog.php',
];
require __DIR__ . '/../inc/head.php';

$faq = [
  ['id'=>'fb-intro','icon'=>'✍️','title'=>'Présentation','q'=>[
    ['d','Qu\'est-ce que le module Blog ?','Un <strong>blog complet</strong> : articles en Markdown, catégories &amp; tags, commentaires modérés, likes, thèmes importables et SEO. Côté admin : <strong>Blog</strong> ; côté public : <code>/blog</code>.'],
    ['d','Le blog est-il fourni par défaut ?','C\'est un <strong>module officiel</strong> activable depuis <strong>Administration → Modules</strong> (il dépend du module Auth). Une fois activé, ses tables <code>blog_*</code> sont créées.'],
    ['a','Où sont stockées les données ?','Dans des tables dédiées : <code>blog_posts</code>, <code>blog_categories</code>, <code>blog_tags</code> (+ <code>blog_post_tags</code>), <code>blog_comments</code>, <code>blog_post_likes</code> et <code>blog_settings</code>.'],
  ]],
  ['id'=>'fb-articles','icon'=>'📝','title'=>'Articles & rédaction','q'=>[
    ['d','Comment écrire un article ?','<strong>Blog → Nouvel article</strong> : titre, contenu en <strong>Markdown</strong> (avec aperçu live), extrait, image de couverture, catégorie et tags.'],
    ['d','Puis-je insérer des images dans un article ?','Oui : l\'éditeur permet d\'<strong>uploader des images</strong> (couverture et images insérées). Les fichiers sont validés (type &amp; contenu).'],
    ['d','C\'est quoi l\'extrait et le « à la une » ?','L\'<strong>extrait</strong> est le résumé affiché dans les listes (sinon généré automatiquement). « <strong>À la une</strong> » met l\'article en avant sur l\'accueil du blog.'],
    ['a','Le temps de lecture et les articles liés sont-ils automatiques ?','Oui : le <strong>temps de lecture</strong> est calculé à partir du contenu, et des <strong>articles liés</strong> sont suggérés sur la fiche (même catégorie / tags).'],
  ]],
  ['id'=>'fb-publish','icon'=>'🚀','title'=>'Publication & programmation','q'=>[
    ['d','Quels sont les états d\'un article ?','<strong>Brouillon</strong> (invisible), <strong>Publié</strong> (visible tout de suite) et <strong>Programmé</strong> (visible automatiquement à une date future).'],
    ['d','Comment programmer une parution ?','Choisissez l\'état <strong>Programmé</strong> et une <strong>date de parution</strong> future : l\'article apparaîtra tout seul le moment venu.'],
    ['a','La programmation a-t-elle besoin d\'un cron ?','<strong>Non.</strong> La visibilité est filtrée à l\'affichage (<code>published_at &le; NOW()</code>) : aucun cron requis. Une date <em>passée</em> saisie en « programmé » bascule l\'article en « publié ».'],
  ]],
  ['id'=>'fb-organize','icon'=>'🗂️','title'=>'Catégories & tags','q'=>[
    ['d','Catégories ou tags, quelle différence ?','La <strong>catégorie</strong> classe l\'article dans une rubrique principale (<code>/blog/c/&lt;slug&gt;</code>) ; les <strong>tags</strong> sont des étiquettes transverses (<code>/blog/tag/&lt;slug&gt;</code>), cumulables.'],
    ['a','Y a-t-il un classement des tags ?','Oui, le blog expose des <strong>tags populaires</strong> (les plus utilisés) pour faciliter la navigation.'],
  ]],
  ['id'=>'fb-comments','icon'=>'💬','title'=>'Commentaires & likes','q'=>[
    ['d','Comment fonctionnent les commentaires ?','Selon les réglages : activés ou non, <strong>modérés</strong> (validation avant publication) ou non, ouverts ou non aux <strong>invités</strong>. La modération se fait dans <strong>Blog → Commentaires</strong>.'],
    ['d','Quels statuts pour un commentaire ?','<code>pending</code> (en attente), <code>approved</code> (approuvé &amp; visible) ou <code>spam</code> (indésirable, masqué).'],
    ['d','Les likes nécessitent-ils un compte ?','Non : un visiteur peut « aimer » un article <strong>sans se connecter</strong>.'],
    ['a','Comment les likes sont-ils dédoublonnés ?','Par <strong>hash d\'IP</strong> : un même visiteur ne compte qu\'une fois (bascule on/off). L\'admin peut aussi être <strong>notifié</strong> des nouveaux commentaires.'],
  ]],
  ['id'=>'fb-themes','icon'=>'🎨','title'=>'Thèmes & apparence','q'=>[
    ['d','Comment changer l\'apparence du blog ?','<strong>Blog → Thèmes</strong> : activez un thème (deux fournis : <em>default</em> et <em>magazine</em>), importez-en un par <strong>ZIP</strong>, ou supprimez-en.'],
    ['a','Comment est structuré un thème ?','Un dossier sous <code>Themes/</code> avec un <code>theme.json</code> et ses gabarits (<code>layout</code>, <code>index</code>, <code>category</code>, <code>single</code>). Le CSS est servi via <code>/blog/theme.css</code>. Changer de thème ne touche ni articles ni commentaires.'],
  ]],
  ['id'=>'fb-seo','icon'=>'🔍','title'=>'SEO, RSS & sitemap','q'=>[
    ['d','Le blog est-il bon pour le référencement ?','Oui : URL propres, métadonnées par article (titre, description, Open Graph), <strong>sitemap</strong> (<code>/blog/sitemap.xml</code>) et <strong>flux RSS</strong> (<code>/blog/feed</code>).'],
    ['a','Où se règlent le titre et la description du blog ?','Dans <strong>Blog → Réglages</strong> (titre, description, articles par page…) — ces valeurs alimentent aussi le SEO et le flux RSS.'],
  ]],
  ['id'=>'fb-secu','icon'=>'🔒','title'=>'Sécurité','q'=>[
    ['d','Le blog est-il sécurisé ?','Oui : administration réservée au staff, commentaires modérés par défaut, et rendu Markdown sûr (pas de HTML malveillant exécuté).'],
    ['a','Quels garde-fous techniques ?','<strong>CSRF</strong> sur toutes les actions (admin + commentaires/likes), <strong>SQL préparé</strong>, sorties <strong>échappées</strong>, <strong>uploads d\'images validés</strong>, likes anti-doublon par hash d\'IP.'],
  ]],
];

$totalD = 0; $totalA = 0;
foreach ($faq as $t) { foreach ($t['q'] as $q) { $q[0] === 'd' ? $totalD++ : $totalA++; } }
$lvlMeta = ['d' => ['🟢 Débutant', 'green'], 'a' => ['🔵 Avancé', 'blue']];
?>

    <h1>FAQ — Blog</h1>
    <p class="doc-lead">Les questions courantes sur le module <strong>Blogging</strong> d'Aegis — articles, publication, commentaires, thèmes, SEO — à deux niveaux.</p>
    <div class="doc-meta">
      <span class="doc-pill">🟢 <?= $totalD ?> Débutant</span>
      <span class="doc-pill">🔵 <?= $totalA ?> Avancé</span>
      <span class="doc-pill"><?= count($faq) ?> thèmes</span>
      <span class="doc-pill">Markdown &amp; commentaires</span>
    </div>

    <div class="callout"><span class="i">💡</span><div><strong>Débutant</strong> = « comment faire » · <strong>Avancé</strong> = précisions techniques. Pour le guide complet, voir la rubrique <a href="modules/blog/doc-blog.php">Blog</a>.</div></div>

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
