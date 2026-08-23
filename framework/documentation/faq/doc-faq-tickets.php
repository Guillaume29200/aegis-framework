<?php
/**
 * documentation/doc-faq-tickets.php — FAQ du module Tickets (alloué à GameNodePanel).
 * Deux niveaux : 🟢 Débutant · 🔵 Avancé.
 */
$docPage = 'faq/doc-faq-tickets.php';
$seo = [
    'title'     => 'FAQ — Module Tickets (support, alloué à GameNodePanel)',
    'desc'      => "Foire aux questions du module Tickets d'Aegis : support lié aux serveurs de jeu (GameNodePanel requis), création côté client, gestion côté admin, anti-spam et sécurité. Deux niveaux Débutant / Avancé.",
    'canonical' => 'https://gamenodepanel.com/documentation/doc-faq-tickets.php',
];
require __DIR__ . '/../inc/head.php';

$faq = [
  ['id'=>'tk-intro','icon'=>'🎫','title'=>'Présentation & dépendance GNP','q'=>[
    ['d','Qu\'est-ce que le module Tickets ?','Un <strong>support / helpdesk</strong> : vos clients ouvrent des tickets <strong>rattachés à leurs serveurs de jeu</strong>, et le staff y répond depuis l\'administration.'],
    ['d','Ai-je besoin de GameNodePanel pour l\'utiliser ?','<strong>Oui — GameNodePanel est obligatoire.</strong> Un ticket est toujours lié à un serveur de jeu (table <code>gnp_game_servers</code>) : sans GNP, le module n\'a pas d\'objet et ne peut pas fonctionner.'],
    ['d','Où trouver Tickets dans l\'administration ?','Il n\'a pas d\'entrée séparée : il se greffe dans le méga-menu <strong>Game Node Panel → Support → Tickets</strong>.'],
    ['a','Comment est faite « l\'allocation » à GNP ?','Le module déclare la catégorie <code>"Game Node Panel"</code> et son entrée de menu porte le même label que GNP : la <strong>fusion de menus par label</strong> (<code>AdminMenuService</code>) la greffe dans le méga-menu GNP. Côté données, la clé étrangère <code>game_server_id</code> pointe vers <code>gnp_game_servers</code> et les requêtes joignent cette table.'],
  ]],
  ['id'=>'tk-membre','icon'=>'🙋','title'=>'Côté client','q'=>[
    ['d','Comment un client ouvre-t-il un ticket ?','Depuis son <strong>espace membre</strong> : il choisit le <strong>serveur concerné</strong>, un type de problème, un sujet et son message.'],
    ['d','Le client peut-il ouvrir un ticket sur n\'importe quel serveur ?','Non : uniquement sur <strong>ses propres serveurs</strong>. Il ne voit que les serveurs qui lui sont rattachés.'],
    ['d','Comment suit-il la conversation ?','Chaque ticket est un <strong>fil de discussion</strong> dans son espace membre : il lit les réponses du staff et répond.'],
    ['a','Comment l\'appartenance client ↔ serveur est-elle vérifiée ?','Par <code>userOwnsGameServer()</code> (contrôle <code>game_server_id</code> + <code>user_id</code>) à la création, et par <code>getByIdForUser()</code> à la lecture : impossible d\'ouvrir ou de consulter un ticket portant sur le serveur d\'un autre (sinon 403).'],
  ]],
  ['id'=>'tk-admin','icon'=>'🛠️','title'=>'Côté administration','q'=>[
    ['d','Comment le staff gère-t-il les tickets ?','Via <strong>Game Node Panel → Support → Tickets</strong> : liste avec KPI et filtres, vue détaillée avec le fil, réponse, changement de statut et suppression.'],
    ['d','Quels sont les statuts d\'un ticket ?','<strong>Ouvert</strong>, <strong>en attente</strong> (pending) et <strong>fermé</strong> (closed).'],
    ['a','Qui voit quoi ?','Le staff voit tous les tickets ; le membre, seulement les siens (via <code>getByUser()</code>/<code>getByIdForUser()</code>). Les vues admin et membre sont en UI maison <code>.ui-*</code>.'],
  ]],
  ['id'=>'tk-antispam','icon'=>'🚦','title'=>'Anti-spam','q'=>[
    ['d','Comment éviter le spam de tickets ?','Un <strong>anti-spam</strong> est intégré : délai minimal entre deux créations et entre deux réponses, et un <strong>nombre maximal de tickets ouverts</strong> par client.'],
    ['a','Quelles sont les limites exactes ?','<code>60 s</code> minimum entre deux créations, <code>15 s</code> entre deux messages, et <strong>10 tickets ouverts</strong> simultanés maximum par utilisateur. Ces limites s\'appliquent <strong>aux membres uniquement</strong> (le staff n\'est pas bridé).'],
  ]],
  ['id'=>'tk-secu','icon'=>'🔒','title'=>'Sécurité','q'=>[
    ['d','Le module est-il sécurisé ?','Oui : un client ne peut accéder qu\'à ses propres tickets/serveurs, et toutes les actions sont protégées contre la falsification de requête (CSRF).'],
    ['a','Quels garde-fous techniques ?','<strong>CSRF</strong> sur tous les POST, protection <strong>IDOR</strong> (<code>getByIdForUser</code> / <code>userOwnsGameServer</code> → 403), requêtes <strong>préparées</strong> (anti-injection SQL) et <strong>échappement XSS</strong> des messages.'],
  ]],
  ['id'=>'tk-maint','icon'=>'🧰','title'=>'Maintenance & données','q'=>[
    ['d','Que se passe-t-il si je désactive le module ?','La désactivation retire l\'accès au support. La <strong>suppression définitive</strong> (depuis la page Modules) exécute <code>uninstall.sql</code> et supprime les tables <code>support_tickets</code> / <code>support_ticket_messages</code>.'],
    ['a','Comment les données sont-elles structurées ?','Deux tables : <code>support_tickets</code> (le ticket : client, serveur, type, sujet, statut) et <code>support_ticket_messages</code> (les messages du fil). La FK <code>game_server_id</code> est alignée sur <code>gnp_game_servers.id</code>.'],
  ]],
];

$totalD = 0; $totalA = 0;
foreach ($faq as $t) { foreach ($t['q'] as $q) { $q[0] === 'd' ? $totalD++ : $totalA++; } }
$lvlMeta = ['d' => ['🟢 Débutant', 'green'], 'a' => ['🔵 Avancé', 'blue']];
?>

    <h1>FAQ — Module Tickets</h1>
    <p class="doc-lead">Le support client lié aux serveurs de jeu — <strong>module alloué à GameNodePanel</strong>. Questions à deux niveaux.</p>
    <div class="doc-meta">
      <span class="doc-pill">🟢 <?= $totalD ?> Débutant</span>
      <span class="doc-pill">🔵 <?= $totalA ?> Avancé</span>
      <span class="doc-pill"><?= count($faq) ?> thèmes</span>
    </div>

    <div class="callout warn"><span class="i">⚠️</span><div><strong>GameNodePanel est requis.</strong> Le module Tickets est <strong>alloué à GNP</strong> : chaque ticket concerne un serveur de jeu géré par GameNodePanel. Sans GNP installé/actif, le module ne peut pas être utilisé.</div></div>

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
