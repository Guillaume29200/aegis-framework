<?php
/**
 * Cloche de notifications (in-app) — composant autonome réutilisable dans
 * n'importe quel thème (admin ou public). N'affiche rien si non connecté.
 *
 * Styles namespacés .ntf-* (aucune dépendance au thème hôte).
 * Données : /notifications/poll · /notifications/read · /notifications/read-all
 *
 * Variable optionnelle à définir AVANT l'include : $ntfTypes (string[]) —
 * restreint la cloche à ces types de notification (ex. contexte membre d'un
 * module : évite de remonter les notifications admin/système d'autres modules,
 * comme l'activation/désactivation de module, sur une page publique). Non
 * définie = aucune restriction (comportement historique, utilisé par la cloche
 * admin qui doit tout voir).
 */
if (empty($_SESSION['user_id'])) return;

$__ntfUid   = (int) $_SESSION['user_id'];
$__ntfTypes = (isset($ntfTypes) && is_array($ntfTypes) && $ntfTypes) ? array_values($ntfTypes) : null;
$__ntfCount = 0;
try {
    $__ntfDb = $GLOBALS['db'] ?? null;
    if ($__ntfDb instanceof \Framework\Services\Database) {
        $__ntfCount = (new \Framework\Services\NotificationService($__ntfDb))->unreadCount($__ntfUid, $__ntfTypes);
    }
} catch (\Throwable $e) { $__ntfCount = 0; }

$__ntfCsrf = '';
try {
    $__c = $GLOBALS['csrfProtection'] ?? null;
    if ($__c instanceof \Framework\Security\CSRFProtection) $__ntfCsrf = $__c->getToken();
} catch (\Throwable $e) {}

$__ntfBase = function (string $p): string { return (defined('BASE_URL') ? BASE_URL : '') . $p; };
$__ntfPollUrl = $__ntfBase('/notifications/poll') . ($__ntfTypes ? '?types=' . rawurlencode(implode(',', $__ntfTypes)) : '');
?>
<div class="ntf" data-csrf="<?= htmlspecialchars($__ntfCsrf, ENT_QUOTES) ?>"
     data-poll="<?= htmlspecialchars($__ntfPollUrl, ENT_QUOTES) ?>"
     data-read="<?= htmlspecialchars($__ntfBase('/notifications/read'), ENT_QUOTES) ?>"
     data-readall="<?= htmlspecialchars($__ntfBase('/notifications/read-all'), ENT_QUOTES) ?>">
    <button type="button" class="ntf-btn" aria-label="Notifications" title="Notifications">
        <span aria-hidden="true">🔔</span>
        <span class="ntf-badge"<?= $__ntfCount > 0 ? '' : ' hidden' ?>><?= $__ntfCount > 99 ? '99+' : $__ntfCount ?></span>
    </button>
    <div class="ntf-panel" hidden>
        <div class="ntf-head">
            <strong>Notifications</strong>
            <button type="button" class="ntf-allread">Tout marquer lu</button>
        </div>
        <div class="ntf-list"><div class="ntf-empty">Chargement…</div></div>
    </div>
</div>

<style>
.ntf{position:relative;display:inline-flex}
.ntf-btn{position:relative;background:transparent;border:none;cursor:pointer;font-size:18px;line-height:1;padding:6px;border-radius:8px;color:inherit}
.ntf-btn:hover{background:rgba(127,127,127,.15)}
.ntf-badge{position:absolute;top:-1px;right:-2px;min-width:16px;height:16px;padding:0 4px;border-radius:9px;background:#e11d48;color:#fff;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;box-sizing:border-box}
.ntf-panel{position:absolute;top:calc(100% + 8px);right:0;width:320px;max-width:88vw;background:var(--surface,#fff);color:var(--text,#111);border:1px solid var(--border,rgba(0,0,0,.12));border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.18);z-index:1000;overflow:hidden}
.ntf-head{display:flex;align-items:center;justify-content:space-between;padding:11px 14px;border-bottom:1px solid var(--border,rgba(0,0,0,.1))}
.ntf-head strong{font-size:13px}
.ntf-allread{background:transparent;border:none;color:#6366f1;font-size:12px;font-weight:600;cursor:pointer;padding:2px 4px}
.ntf-allread:hover{text-decoration:underline}
.ntf-list{max-height:380px;overflow-y:auto}
.ntf-item{display:flex;gap:10px;padding:11px 14px;border-bottom:1px solid var(--border,rgba(0,0,0,.06));text-decoration:none;color:inherit;cursor:pointer}
.ntf-item:hover{background:rgba(127,127,127,.08)}
.ntf-item.unread{background:rgba(99,102,241,.07)}
.ntf-ico{flex:none;font-size:17px;line-height:1.3}
.ntf-body{min-width:0;flex:1}
.ntf-t{font-size:13px;font-weight:600;margin:0 0 1px;line-height:1.3}
.ntf-x{font-size:12px;color:var(--text-faint,#6b7280);margin:0;line-height:1.35;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
.ntf-ago{font-size:11px;color:var(--text-faint,#9ca3af);margin-top:2px}
.ntf-empty{padding:26px 14px;text-align:center;color:var(--text-faint,#9ca3af);font-size:13px}
</style>

<script>
(function(){
  var el = document.currentScript && document.currentScript.previousElementSibling;
  // Retrouver le conteneur .ntf le plus proche (robuste au streaming)
  var root = document.querySelector('.ntf:not([data-bound])');
  if (!root) return;
  root.setAttribute('data-bound','1');

  var btn   = root.querySelector('.ntf-btn');
  var panel = root.querySelector('.ntf-panel');
  var list  = root.querySelector('.ntf-list');
  var badge = root.querySelector('.ntf-badge');
  var CSRF  = root.dataset.csrf;
  var URLS  = {poll:root.dataset.poll, read:root.dataset.read, readall:root.dataset.readall};
  var loaded = false;

  function esc(s){ var d=document.createElement('div'); d.textContent=s==null?'':s; return d.innerHTML; }
  function setBadge(n){
    if (n>0){ badge.textContent = n>99?'99+':n; badge.hidden=false; }
    else { badge.hidden=true; }
  }

  function render(items){
    if (!items || !items.length){ list.innerHTML='<div class="ntf-empty">Aucune notification.</div>'; return; }
    list.innerHTML = items.map(function(n){
      var cls = n.is_read? 'ntf-item':'ntf-item unread';
      var tag = n.url? 'a':'div';
      var href = n.url? (' href="'+esc(n.url)+'"'):'';
      return '<'+tag+' class="'+cls+'"'+href+' data-id="'+n.id+'">'
        + '<span class="ntf-ico">'+esc(n.icon)+'</span>'
        + '<div class="ntf-body"><p class="ntf-t">'+esc(n.title)+'</p>'
        + (n.body? '<p class="ntf-x">'+esc(n.body)+'</p>':'')
        + '<div class="ntf-ago">'+esc(n.ago)+'</div></div></'+tag+'>';
    }).join('');
    list.querySelectorAll('.ntf-item').forEach(function(it){
      it.addEventListener('click', function(){
        var id = it.dataset.id; if (!id) return;
        post(URLS.read, {id:id});
        it.classList.remove('unread');
      });
    });
  }

  function post(url, extra){
    var body = Object.assign({csrf_token:CSRF}, extra||{});
    return fetch(url, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':CSRF},
      body:new URLSearchParams(body).toString()}).then(function(r){return r.json();})
      .then(function(d){ if (d && typeof d.count!=='undefined') setBadge(d.count); return d; }).catch(function(){});
  }

  function load(){
    fetch(URLS.poll, {headers:{'X-Requested-With':'fetch'}}).then(function(r){return r.json();})
      .then(function(d){ setBadge(d.count||0); render(d.items||[]); loaded=true; }).catch(function(){});
  }

  btn.addEventListener('click', function(e){
    e.stopPropagation();
    var open = !panel.hidden;
    panel.hidden = open;
    if (!open) load();
  });
  document.addEventListener('click', function(e){ if (!root.contains(e.target)) panel.hidden = true; });
  root.querySelector('.ntf-allread').addEventListener('click', function(e){
    e.stopPropagation();
    post(URLS.readall, {}).then(function(){ list.querySelectorAll('.ntf-item.unread').forEach(function(i){i.classList.remove('unread');}); });
  });

  // Rafraîchit le compteur périodiquement (léger)
  setInterval(function(){
    fetch(URLS.poll, {headers:{'X-Requested-With':'fetch'}}).then(function(r){return r.json();})
      .then(function(d){ setBadge(d.count||0); if (!panel.hidden) render(d.items||[]); }).catch(function(){});
  }, 60000);
})();
</script>
