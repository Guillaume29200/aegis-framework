<?php
/**
 * File de modération — signalements groupés par contenu.
 * Variables : $items[], $pending (int), $reasons[], $status (via $_GET)
 */
if (!defined('AEGIS_FRAMEWORK')) die('Access denied');

$pageTitle = $pageTitle ?? 'Modération';
admin_header($pageTitle);

$h        = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$status   = $_GET['status'] ?? 'pending';
$reasons  = $reasons ?? [];
$items    = $items ?? [];
$csrf     = '';
try { $c = $GLOBALS['csrfProtection'] ?? null; if ($c) $csrf = $c->getToken(); } catch (\Throwable $e) {}

$typeLabels = [
    'guestbook' => '📖 Livre d\'or', 'blog_comment' => '💬 Commentaire blog',
    'blog_post' => '📰 Article', 'forum_post' => '💬 Message forum',
    'forum_topic' => '🧵 Sujet forum', 'profile' => '👤 Profil',
    'marketplace_review' => '🛒 Avis marketplace',
];
$tabs = ['pending' => 'En attente', 'resolved' => 'Traités', 'rejected' => 'Rejetés', 'all' => 'Tous'];
?>
<div class="adm-page-head" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:16px">
  <div>
    <div class="adm-breadcrumb"><a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><span>Modération</span></div>
    <h1 style="margin:.2em 0 0;font-size:20px">🚩 Modération</h1>
  </div>
  <span class="ui-badge" style="font-size:12px;<?= ($pending ?? 0) > 0 ? 'background:var(--amber-soft);color:var(--amber)' : 'background:var(--surface-3);color:var(--text-faint)' ?>">
    <?= (int)($pending ?? 0) ?> en attente
  </span>
</div>

<div class="ui-card" style="padding:16px" data-csrf="<?= $h($csrf) ?>">
  <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px">
    <?php foreach ($tabs as $k => $label): ?>
    <a href="<?= u('/admin/moderation?status=' . $k) ?>"
       class="ui-btn sm<?= $status === $k ? ' primary' : ' ghost' ?>"><?= $h($label) ?></a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($items)): ?>
  <div style="text-align:center;padding:44px;color:var(--text-faint)">
    <div style="font-size:34px;margin-bottom:8px">✅</div>
    Aucun signalement <?= $status === 'pending' ? 'en attente' : 'ici' ?>.
  </div>
  <?php else: ?>
  <div style="overflow-x:auto">
  <table class="aud-table" id="mod-table">
    <thead>
      <tr><th>Contenu</th><th>Signalements</th><th>Motifs</th><th>Signalé par</th><th>Note</th><th>Dernier</th><th>Actions</th></tr>
    </thead>
    <tbody>
      <?php foreach ($items as $r):
        $reasonList = array_filter(array_map('trim', explode(',', (string)($r['reasons'] ?? ''))));
        $reasonTxt  = implode(', ', array_map(fn($x) => $reasons[$x] ?? $x, $reasonList));
      ?>
      <tr data-type="<?= $h($r['content_type']) ?>" data-id="<?= (int)$r['content_id'] ?>">
        <td><strong><?= $h($typeLabels[$r['content_type']] ?? $r['content_type']) ?></strong>
            <small style="color:var(--text-faint)">#<?= (int)$r['content_id'] ?></small></td>
        <td><span class="aud-action"><?= (int)$r['reports'] ?></span></td>
        <td><?= $h($reasonTxt) ?></td>
        <td style="max-width:180px"><?= $h($r['reporters'] ?? '') ?></td>
        <td class="aud-summary"><?= $h($r['sample_note'] ?? '') ?: '<span style="color:var(--text-faint)">—</span>' ?></td>
        <td class="aud-date"><?= $h(date('d/m/Y H:i', strtotime($r['last_at']))) ?></td>
        <td style="white-space:nowrap">
          <?php if (($r['status'] ?? 'pending') === 'pending'): ?>
          <button class="ui-btn sm" onclick="modResolve(this,'resolve')" title="Marquer traité">✓</button>
          <button class="ui-btn sm ghost" onclick="modResolve(this,'reject')" title="Rejeter">✕</button>
          <?php else: ?>
          <span class="ui-badge" style="font-size:11px;background:var(--surface-3);color:var(--text-faint)"><?= $h($r['status']) ?></span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<style>
.aud-table{width:100%;border-collapse:collapse;font-size:13px}
.aud-table th{text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--text-faint);padding:9px 12px;border-bottom:2px solid var(--border);white-space:nowrap}
.aud-table td{padding:10px 12px;border-bottom:1px solid var(--border);vertical-align:top}
.aud-action{display:inline-block;font-weight:700;background:var(--bg-warning,rgba(245,158,11,.15));color:var(--accent);padding:2px 9px;border-radius:6px}
.aud-summary{max-width:260px}
.aud-date{white-space:nowrap;color:var(--text-faint)}
</style>

<script>
(function(){
  var card = document.querySelector('.ui-card[data-csrf]');
  window.modResolve = function(btn, action){
    var tr = btn.closest('tr');
    var CSRF = card ? card.dataset.csrf : '';
    fetch(<?= json_encode(u('/admin/moderation/resolve')) ?>, {
      method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':CSRF},
      body:new URLSearchParams({csrf_token:CSRF, content_type:tr.dataset.type, content_id:tr.dataset.id, action:action}).toString()
    }).then(r=>r.json()).then(function(d){
      if (d && d.success){ tr.style.transition='opacity .3s'; tr.style.opacity='0'; setTimeout(()=>tr.remove(),300);
        if (typeof atNotify==='function') atNotify(action==='reject'?'Signalement rejeté':'Signalement traité','ok'); }
    }).catch(function(){ if (typeof atNotify==='function') atNotify('Erreur réseau','err'); });
  };
})();
</script>

<?php admin_footer(); ?>
