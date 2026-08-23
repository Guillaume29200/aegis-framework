<?php
/**
 * Jauges circulaires CPU / mémoire, rafraîchies en direct.
 *
 * Réutilisable : le tableau de bord et la page Monitoring l'incluent tous
 * les deux. Un seul jeu de styles et un seul script, quel que soit le nombre
 * d'inclusions sur la page.
 *
 * Rien n'est enregistré. La mesure est demandée à /admin/metrics/live, qui
 * la calcule à la volée ; l'échantillon précédent nécessaire au calcul de la
 * charge vit dans le cache applicatif, pas en base.
 *
 * Variable facultative avant l'include :
 *   $gaugesTitle  — titre de la carte (défaut : « Charge machine »)
 *   $gaugesCompact — true pour la version étroite d'une colonne latérale
 */
if (!defined('AEGIS_FRAMEWORK')) die('Access denied');
if (empty($_SESSION['logged_in'])) return;
if (!in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'], true)) return;

$__gTitre   = $gaugesTitle ?? '📟 Charge machine';
$__gCompact = !empty($gaugesCompact);
$__gUrl     = (defined('BASE_URL') ? BASE_URL : '') . '/admin/metrics/live';
?>
<div class="ui-card mx-card<?= $__gCompact ? ' mx-card--compact' : '' ?>" data-metrics="<?= htmlspecialchars($__gUrl, ENT_QUOTES) ?>">
    <div class="ui-card-head">
        <?= htmlspecialchars($__gTitre) ?>
        <span class="ui-card-actions">
            <span class="mx-live" title="Rafraîchi toutes les 5 secondes"><i></i> direct</span>
        </span>
    </div>
    <div class="ui-card-body">
        <div class="mx-gauges">
            <?php foreach ([['cpu', 'CPU', '⚙️'], ['ram', 'Mémoire', '🧠']] as [$cle, $libelle, $emoji]): ?>
            <figure class="mx-g" data-g="<?= $cle ?>">
                <svg viewBox="0 0 120 120" role="img" aria-label="<?= $libelle ?>">
                    <!-- L'anneau de fond, puis l'arc de valeur. Le tracé est
                         piloté par stroke-dashoffset : une seule propriété
                         animée, donc pas de recalcul de mise en page. -->
                    <circle class="mx-g__bg"  cx="60" cy="60" r="50" />
                    <circle class="mx-g__arc" cx="60" cy="60" r="50" />
                </svg>
                <div class="mx-g__mid">
                    <b class="mx-g__v">—</b>
                    <span class="mx-g__u">%</span>
                </div>
                <figcaption>
                    <span class="mx-g__k"><?= $emoji ?> <?= $libelle ?></span>
                    <em class="mx-g__d"></em>
                </figcaption>
            </figure>
            <?php endforeach; ?>
        </div>
        <p class="mx-note"></p>
    </div>
</div>

<?php if (!defined('AEGIS_METRICS_ASSETS')): define('AEGIS_METRICS_ASSETS', true); ?>
<style>
.mx-card .ui-card-body { padding-top: 14px; }

.mx-live {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 11px; font-weight: 600; letter-spacing: .05em;
  text-transform: uppercase; opacity: .6;
}
.mx-live > i {
  width: 7px; height: 7px; border-radius: 50%;
  background: var(--green, #22c55e);
  animation: mxPulse 2s ease-in-out infinite;
}
.mx-card.is-stale .mx-live > i { background: var(--text-faint, #8b949e); animation: none; }
@keyframes mxPulse { 0%,100% { opacity: 1; } 50% { opacity: .3; } }

.mx-gauges { display: flex; gap: 18px; justify-content: center; }
.mx-card--compact .mx-gauges { gap: 10px; }

.mx-g {
  position: relative; margin: 0; flex: 1;
  display: flex; flex-direction: column; align-items: center;
  max-width: 150px;
}
.mx-g svg { width: 100%; height: auto; display: block; transform: rotate(-90deg); }

.mx-g__bg {
  fill: none; stroke: currentColor; stroke-width: 9;
  opacity: .12;
}
.mx-g__arc {
  fill: none; stroke-width: 9; stroke-linecap: round;
  /* 2 × π × 50 ≈ 314,16 : la circonférence complète de l'anneau. */
  stroke-dasharray: 314.16;
  stroke-dashoffset: 314.16;          /* vide au départ */
  stroke: var(--accent, #6366f1);
  transition: stroke-dashoffset .6s ease, stroke .3s ease;
}
/* Le vert cède à l'orange puis au rouge : la couleur doit dire la même chose
   que le chiffre, sans avoir à le lire. */
.mx-g.is-ok   .mx-g__arc { stroke: var(--green, #22c55e); }
.mx-g.is-warn .mx-g__arc { stroke: var(--amber, #f59e0b); }
.mx-g.is-hot  .mx-g__arc { stroke: var(--red,   #ef4444); }

.mx-g__mid {
  position: absolute; left: 0; right: 0; top: 0;
  height: 100%;
  display: flex; align-items: center; justify-content: center; gap: 1px;
  pointer-events: none;
  /* Le pied de figure occupe le bas : on remonte le chiffre dans l'anneau. */
  padding-bottom: 38px;
}
.mx-card--compact .mx-g__mid { padding-bottom: 34px; }
.mx-g__v { font-size: 21px; font-weight: 800; font-variant-numeric: tabular-nums; line-height: 1; }
.mx-g__u { font-size: 11px; opacity: .6; align-self: flex-start; margin-top: 3px; }

.mx-g figcaption {
  margin-top: -4px; text-align: center;
  display: flex; flex-direction: column; gap: 1px;
}
.mx-g__k { font-size: 12px; font-weight: 600; }
.mx-g__d { font-style: normal; font-size: 10.5px; opacity: .6; font-variant-numeric: tabular-nums; }

.mx-note {
  margin: 12px 0 0; text-align: center;
  font-size: 11.5px; opacity: .6; line-height: 1.5;
}
.mx-note:empty { display: none; }

@media (prefers-reduced-motion: reduce) {
  .mx-g__arc { transition: none; }
  .mx-live > i { animation: none; }
}
</style>

<script>
/* Jauges CPU / mémoire.

   Une seule boucle pour toutes les cartes présentes sur la page : deux
   inclusions ne doivent pas déclencher deux appels réseau.

   Le rythme s'adapte : quand l'onglet passe en arrière-plan, on cesse
   d'interroger. Mesurer une machine que personne ne regarde ne sert à rien,
   et chaque relevé coûte ~300 ms au serveur sous Windows. */
(function () {
  var cartes = [].slice.call(document.querySelectorAll('[data-metrics]'));
  if (!cartes.length) { return; }

  var URL_MESURE = cartes[0].getAttribute('data-metrics');
  var PERIODE    = 5000;
  var CIRC       = 314.16;
  var minuteur   = null;
  var enCours    = false;

  function arrondi(v) { return Math.max(0, Math.min(100, v)); }

  function octets(o) {
    if (o === null || o === undefined) { return ''; }
    var go = o / 1073741824;
    return go >= 1 ? go.toFixed(1) + ' Go' : Math.round(o / 1048576) + ' Mo';
  }

  function peindre(carte, cle, pct, detail) {
    var g = carte.querySelector('[data-g="' + cle + '"]');
    if (!g) { return; }

    var arc = g.querySelector('.mx-g__arc');
    var val = g.querySelector('.mx-g__v');
    var det = g.querySelector('.mx-g__d');

    g.classList.remove('is-ok', 'is-warn', 'is-hot');

    if (pct === null || pct === undefined) {
      // Pas encore de point de comparaison : on le dit, on n'affiche pas 0.
      val.textContent = '—';
      arc.style.strokeDashoffset = CIRC;
      if (det) { det.textContent = detail || 'mesure en cours'; }
      return;
    }

    var p = arrondi(pct);
    val.textContent = Math.round(p);
    arc.style.strokeDashoffset = CIRC - (CIRC * p / 100);
    g.classList.add(p >= 90 ? 'is-hot' : (p >= 70 ? 'is-warn' : 'is-ok'));
    if (det) { det.textContent = detail || ''; }
  }

  function appliquer(d) {
    cartes.forEach(function (carte) {
      carte.classList.remove('is-stale');

      if (!d || d.source === 'unavailable') {
        peindre(carte, 'cpu', null, 'indisponible');
        peindre(carte, 'ram', null, 'indisponible');
        carte.classList.add('is-stale');
        var n0 = carte.querySelector('.mx-note');
        if (n0) {
          n0.textContent = "La machine n'expose pas ses compteurs — hébergement mutualisé, "
                         + "ou fonctions système désactivées.";
        }
        return;
      }

      peindre(carte, 'cpu', d.cpu, d.cores ? d.cores + ' cœurs' : '');
      peindre(
        carte, 'ram',
        d.ram ? d.ram.used_pct : null,
        d.ram && d.ram.total ? octets(d.ram.used) + ' / ' + octets(d.ram.total) : ''
      );

      var n = carte.querySelector('.mx-note');
      if (n) { n.textContent = d.at ? 'Relevé à ' + d.at : ''; }
    });
  }

  function interroger() {
    if (enCours || document.hidden) { return; }
    enCours = true;

    fetch(URL_MESURE, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (d) { appliquer(d); })
      .catch(function () {
        // Réseau coupé ou session expirée : on grise plutôt que de figer sur
        // une valeur qui n'a plus cours.
        cartes.forEach(function (c) { c.classList.add('is-stale'); });
      })
      .then(function () { enCours = false; });
  }

  function demarrer() {
    if (minuteur) { return; }
    interroger();
    minuteur = setInterval(interroger, PERIODE);
  }

  function arreter() {
    if (!minuteur) { return; }
    clearInterval(minuteur);
    minuteur = null;
  }

  document.addEventListener('visibilitychange', function () {
    if (document.hidden) { arreter(); } else { demarrer(); }
  });

  demarrer();
})();
</script>
<?php endif; ?>
