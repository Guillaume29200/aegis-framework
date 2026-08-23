<?php
/**
 * Indicateur de mises à jour de modules, dans l'en-tête d'administration.
 *
 * L'information vivait uniquement dans le diagnostic : il fallait penser à
 * y aller pour découvrir qu'une mise à jour attendait depuis trois semaines.
 * Elle est désormais visible partout, en permanence.
 *
 * N'affiche RIEN quand il n'y a rien à signaler : un indicateur toujours
 * présent finit par ne plus être vu, et occuperait la place d'un autre.
 *
 * Réservé aux administrateurs — un rédacteur n'a rien à faire d'une migration
 * SQL en attente, et ne pourrait pas l'appliquer.
 */
if (empty($_SESSION['logged_in'])) return;
if (!in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'], true)) return;

$__majListe = [];
try {
    $__majDb = $GLOBALS['db'] ?? null;
    $__majMm = $GLOBALS['moduleManager'] ?? null;

    if ($__majDb instanceof \Framework\Services\Database
        && $__majMm instanceof \Framework\ModuleManager\ModuleManager) {
        $__majListe = (new \Framework\Services\ModuleUpdateService($__majDb, $__majMm))->pending();
    }
} catch (\Throwable $e) {
    // Un en-tête ne tombe pas pour un indicateur.
    $__majListe = [];
}

if ($__majListe === []) return;

$__majN   = count($__majListe);
$__majUrl = (defined('BASE_URL') ? BASE_URL : '') . '/admin/diagnostic';
$__majH   = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<style>
/* Les couleurs suivent EXACTEMENT la convention de la cloche de
   notifications, qui cohabite avec ce bouton : --surface et --text, posées
   ensemble sur le panneau.

   Le défaut précédent venait de là : le fond était forcé en sombre pendant
   que le texte restait en `inherit`, donc en noir sur le thème clair. Un
   panneau doit toujours déclarer SES deux couleurs, jamais une seule. */
.maj-wrap { position: relative; }

.maj-btn {
  position: relative;
  background: transparent; border: none; cursor: pointer;
  font-size: 18px; line-height: 1; padding: 6px;
  border-radius: 8px; color: inherit;
}
.maj-btn:hover { background: rgba(127,127,127,.15); }

.maj-badge {
  position: absolute; top: -1px; right: -2px;
  min-width: 16px; height: 16px; padding: 0 4px;
  border-radius: 9px;
  background: #f59e0b; color: #1a1206;
  font-size: 10px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  box-sizing: border-box;
}

.maj-panel {
  position: absolute; top: calc(100% + 8px); right: 0;
  width: 320px; max-width: 88vw;
  background: var(--surface, #fff);
  color: var(--text, #111);
  border: 1px solid var(--border, rgba(0,0,0,.12));
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(0,0,0,.18);
  z-index: 1000;
  overflow: hidden;
}

.maj-head {
  padding: 11px 14px;
  font-size: 12.5px; font-weight: 700;
  border-bottom: 1px solid var(--border, rgba(0,0,0,.12));
}

.maj-row {
  display: flex; align-items: center; justify-content: space-between; gap: 10px;
  padding: 10px 14px;
  color: inherit;              /* hérite du panneau, qui a une couleur explicite */
  text-decoration: none;
  font-size: 13px;
  border-bottom: 1px solid var(--border, rgba(0,0,0,.06));
}
.maj-row:last-of-type { border-bottom: none; }
.maj-row:hover { background: rgba(127,127,127,.08); }
.maj-row b { font-weight: 600; }
.maj-row em {
  font-style: normal; font-size: 11.5px;
  opacity: .7;                 /* lisible sur clair comme sur sombre */
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
}

.maj-go {
  display: block;
  padding: 10px 14px;
  border-top: 1px solid var(--border, rgba(0,0,0,.12));
  background: rgba(245,158,11,.12);
  font-size: 12.5px; font-weight: 700; text-align: center;
  /* Couleur héritée du panneau : un ambre lisible sur blanc l'est mal sur
     noir, et inversement. C'est le fond teinté qui porte le sens. */
  color: inherit; text-decoration: none;
}
.maj-go:hover { background: rgba(245,158,11,.16); }
</style>

<div class="maj-wrap" id="majWrap">
    <button type="button" class="maj-btn" id="majBtn"
            aria-haspopup="true" aria-expanded="false"
            title="<?= $__majN ?> mise<?= $__majN > 1 ? 's' : '' ?> à jour disponible<?= $__majN > 1 ? 's' : '' ?>"
            aria-label="<?= $__majN ?> mise<?= $__majN > 1 ? 's' : '' ?> à jour disponible<?= $__majN > 1 ? 's' : '' ?>">
        <span aria-hidden="true">⬆️</span>
        <span class="maj-badge"><?= $__majN > 9 ? '9+' : $__majN ?></span>
    </button>

    <div class="maj-panel" id="majPanel" hidden>
        <div class="maj-head">
            <?= $__majN ?> module<?= $__majN > 1 ? 's' : '' ?> à mettre à jour
        </div>

        <?php foreach ($__majListe as $__m): ?>
        <a class="maj-row" href="<?= $__majH($__majUrl) ?>">
            <b><?= $__majH($__m['name']) ?></b>
            <em>
                <?php if ($__m['from'] !== $__m['to']): ?>
                    v<?= $__majH($__m['from']) ?> → v<?= $__majH($__m['to']) ?>
                <?php else: ?>
                    <?= (int) $__m['migrations'] ?> migration<?= $__m['migrations'] > 1 ? 's' : '' ?>
                <?php endif; ?>
            </em>
        </a>
        <?php endforeach; ?>

        <a class="maj-go" href="<?= $__majH($__majUrl) ?>">Ouvrir le diagnostic →</a>
    </div>
</div>

<script>
(function () {
  var wrap  = document.getElementById('majWrap');
  var btn   = document.getElementById('majBtn');
  var panel = document.getElementById('majPanel');
  if (!wrap || !btn || !panel) { return; }

  function ferme() {
    panel.hidden = true;
    btn.setAttribute('aria-expanded', 'false');
  }

  btn.addEventListener('click', function (e) {
    e.stopPropagation();
    var ouvert = !panel.hidden;
    panel.hidden = ouvert;
    btn.setAttribute('aria-expanded', ouvert ? 'false' : 'true');
  });

  // Un panneau qui ne se referme qu'en recliquant sur son bouton reste
  // ouvert quand on part ailleurs : on ferme au clic extérieur et à Échap.
  document.addEventListener('click', function (e) {
    if (!wrap.contains(e.target)) { ferme(); }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { ferme(); }
  });
})();
</script>
