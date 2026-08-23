<?php
/**
 * Journal d'audit — Aegis Framework V4.
 * Variables : $result (items/total/pages/page), $actions[], $filters[]
 */
if (!defined('AEGIS_FRAMEWORK')) die('Access denied');

$pageTitle = $pageTitle ?? 'Journal d\'audit';
admin_header($pageTitle);

$h       = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$flashOk  = $_SESSION['success'] ?? null; unset($_SESSION['success']);
$flashErr = $_SESSION['error'] ?? null;   unset($_SESSION['error']);
$items   = $result['items'] ?? [];
$total   = (int)($result['total'] ?? 0);
$pages   = (int)($result['pages'] ?? 1);
$cur     = (int)($result['page'] ?? 1);
$fAction = $filters['action'] ?? '';
$fQ      = $filters['q'] ?? '';
$qs = function (array $over) use ($fAction, $fQ) {
    $p = array_merge(['action' => $fAction, 'q' => $fQ], $over);
    return u('/admin/audit') . '?' . http_build_query(array_filter($p, fn($v) => $v !== '' && $v !== null));
};
?>
<div class="adm-page-head" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:16px">
  <div>
    <div class="adm-breadcrumb"><a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><span>Journal d'audit</span></div>
    <h1 style="margin:.2em 0 0;font-size:20px">📋 Journal d'audit</h1>
  </div>
  <span style="font-size:13px;color:var(--text-faint)"><?= number_format($total, 0, ',', ' ') ?> entrée<?= $total > 1 ? 's' : '' ?></span>
</div>

<?php if ($flashOk): ?><div class="ui-alert success">✅ <?= $h($flashOk) ?></div><?php endif; ?>
<?php if ($flashErr): ?><div class="ui-alert danger">❌ <?= $h($flashErr) ?></div><?php endif; ?>

<div class="ui-card" style="padding:16px">
  <form method="get" action="<?= u('/admin/audit') ?>" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;margin-bottom:16px">
    <label style="display:grid;gap:4px;font-size:12px;color:var(--text-faint)">
      Action
      <select name="action" class="aud-input" style="min-width:180px">
        <option value="">— Toutes —</option>
        <?php foreach ($actions as $a): ?>
        <option value="<?= $h($a) ?>"<?= $a === $fAction ? ' selected' : '' ?>><?= $h($a) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label style="display:grid;gap:4px;font-size:12px;color:var(--text-faint);flex:1;min-width:180px">
      Recherche
      <input type="search" name="q" value="<?= $h($fQ) ?>" placeholder="Utilisateur, détail, cible…" class="aud-input" style="width:100%">
    </label>
    <button type="submit" class="ui-btn">Filtrer</button>
    <?php if ($fAction !== '' || $fQ !== ''): ?>
    <a href="<?= u('/admin/audit') ?>" class="ui-btn ghost">Réinitialiser</a>
    <?php endif; ?>
  </form>

  <form method="post" action="<?= u('/admin/audit/purge') ?>" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;margin-bottom:16px;padding:12px;border:1px solid var(--border);border-radius:var(--radius-sm)" onsubmit="return confirm('Purger le journal d\'audit ? Cette action est irréversible.');">
    <input type="hidden" name="csrf_token" value="<?= $h($csrfToken ?? '') ?>">
    <label style="display:grid;gap:4px;font-size:12px;color:var(--text-faint)">
      Purger les entrées de plus de (jours, vide = tout l'historique)
      <input type="number" name="older_than_days" min="1" class="aud-input" style="width:140px" placeholder="tout">
    </label>
    <button type="submit" class="ui-btn danger sm">🧹 Purger</button>
  </form>

  <?php if (empty($items)): ?>
  <div style="text-align:center;padding:44px;color:var(--text-faint)">
    <div style="font-size:34px;margin-bottom:8px">📋</div>
    Aucune entrée d'audit pour ce filtre.
  </div>
  <?php else: ?>
  <div style="overflow-x:auto">
  <table class="aud-table">
    <thead>
      <tr>
        <th>Date</th><th>Utilisateur</th><th>Action</th><th>Cible</th><th>Détail</th><th>IP</th><th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $r): ?>
      <tr>
        <td class="aud-date"><?= $h(date('d/m/Y H:i', strtotime($r['created_at']))) ?></td>
        <td><?= $r['username'] !== '' ? $h($r['username']) : '<span style="color:var(--text-faint)">système</span>' ?></td>
        <td><span class="aud-action"><?= $h($r['action']) ?></span></td>
        <td>
          <?php if (!empty($r['target_type']) || !empty($r['target_id'])): ?>
          <span style="color:var(--text-faint)"><?= $h($r['target_type'] ?? '') ?></span>
          <?= $h($r['target_id'] ?? '') ?>
          <?php else: ?>—<?php endif; ?>
        </td>
        <td class="aud-summary"><?= $h($r['summary']) ?></td>
        <td class="aud-ip"><?= $h($r['ip']) ?></td>
        <td>
          <form method="post" action="<?= u('/admin/audit/delete') ?>" onsubmit="return confirm('Supprimer cette entrée du journal ?');" style="margin:0">
            <input type="hidden" name="csrf_token" value="<?= $h($csrfToken ?? '') ?>">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button type="submit" class="ui-btn sm ghost" title="Supprimer">🗑️</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>

  <?php if ($pages > 1): ?>
  <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;margin-top:16px">
    <?php for ($p = max(1, $cur - 3); $p <= min($pages, $cur + 3); $p++): ?>
    <a href="<?= $h($qs(['page' => $p])) ?>" class="ui-btn sm<?= $p === $cur ? ' primary' : ' ghost' ?>" style="min-width:36px;text-align:center"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<style>
.aud-input{height:34px;border:1px solid var(--border);background:var(--surface-2);color:var(--text);border-radius:var(--radius-sm);padding:0 10px;font-size:13px;font-family:inherit}
.aud-input:focus{outline:none;border-color:var(--accent)}
.aud-table{width:100%;border-collapse:collapse;font-size:13px}
.aud-table th{text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--text-faint);padding:9px 12px;border-bottom:2px solid var(--border);white-space:nowrap}
.aud-table td{padding:10px 12px;border-bottom:1px solid var(--border);vertical-align:top}
.aud-table tr:hover td{background:var(--surface-2)}
.aud-date{white-space:nowrap;color:var(--text-faint)}
.aud-action{display:inline-block;font-family:ui-monospace,monospace;font-size:12px;background:var(--accent-soft);color:var(--accent);padding:2px 8px;border-radius:6px;white-space:nowrap}
.aud-summary{max-width:360px}
.aud-ip{font-family:ui-monospace,monospace;font-size:12px;color:var(--text-faint);white-space:nowrap}
.ui-alert{padding:12px 16px;border-radius:var(--radius-sm);margin-bottom:16px;border:1px solid var(--border)}
.ui-alert.success{background:var(--green-soft);color:var(--green);border-color:var(--green-soft)}
.ui-alert.danger{background:var(--red-soft);color:var(--red);border-color:var(--red-soft)}
</style>

<?php admin_footer(); ?>
