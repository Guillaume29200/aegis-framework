<?php
/**
 * Changelog d'Aegis Framework — lit framework/changelog.json.
 * Variable : $changelog (array : product, version, releases[])
 */
if (!defined('AEGIS_FRAMEWORK')) die('Access denied');
admin_header('Changelog — ' . ($changelog['product'] ?? 'Aegis Framework'));

$releases = $changelog['releases'] ?? [];

/** Rendu inline « markdown léger » : **gras**, `code`, échappé sinon. */
$rt = function (string $s): string {
    $s = htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    $s = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $s);
    $s = preg_replace('/`([^`]+?)`/s', '<code>$1</code>', $s);
    return $s;
};

/** Date ISO → français lisible (ex. « 14 juin 2026 »). */
$frDate = function (?string $iso): string {
    $mois = [1=>'janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
    if ($iso && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $iso, $m)) {
        return ((int)$m[3]) . ' ' . ($mois[(int)$m[2]] ?? $m[2]) . ' ' . $m[1];
    }
    return (string)$iso;
};

/** Métadonnées des types de version. */
$typeMeta = [
    'feature'  => ['🚀 Fonctionnalité', 'blue'],
    'fix'      => ['🐛 Correctif',       'amber'],
    'security' => ['🔒 Sécurité',        'red'],
    'module'   => ['📦 Module',          'green'],
    'release'  => ['🏷️ Version',         'accent'],
];

$totalItems = 0;
foreach ($releases as $r) { foreach ($r['groups'] ?? [] as $g) { $totalItems += count($g['items'] ?? []); } }
?>

<div class="adm-page-head" style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
    <div>
        <div class="adm-breadcrumb"><a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><a href="<?= u('/admin/configuration') ?>">Configuration</a><span>/</span><span>Changelog</span></div>
        <h1>🗒️ Changelog — <?= htmlspecialchars($changelog['product'] ?? 'Aegis Framework') ?></h1>
        <p>Suivi des versions et modifications du framework.</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <?php if (!empty($changelog['version'])): ?><span class="ui-badge accent" style="font-size:13px">Version actuelle&nbsp;: v<?= htmlspecialchars($changelog['version']) ?></span><?php endif; ?>
        <span class="ui-badge blue"><?= count($releases) ?> version<?= count($releases) > 1 ? 's' : '' ?></span>
    </div>
</div>

<?php if (empty($releases)): ?>
    <div class="ui-card"><div class="ui-card-body ui-empty" style="padding:36px"><div class="ui-empty-icon">🗒️</div>Aucune entrée. <code>framework/changelog.json</code> est vide ou introuvable.</div></div>
<?php else: ?>

<div class="cl-search-wrap" style="margin-bottom:18px">
    <input type="search" id="clSearch" class="cl-search" placeholder="🔎 Filtrer (mot-clé, ex. « session », « sécurité »…)" autocomplete="off">
</div>

<div class="cl-timeline">
    <?php foreach ($releases as $idx => $rel):
        $open = ($idx === 0); // seule la dernière version est ouverte par défaut
        $haystack = strtolower(($rel['title'] ?? '') . ' v' . ($rel['version'] ?? ''));
        foreach ($rel['groups'] ?? [] as $g) { $haystack .= ' ' . strtolower($g['title'] ?? ''); foreach ($g['items'] ?? [] as $it) { $haystack .= ' ' . strtolower(is_array($it) ? ($it['text'] ?? '') : $it); } }
    ?>
    <section class="cl-rel<?= $open ? ' open' : '' ?>" data-search="<?= htmlspecialchars($haystack, ENT_QUOTES) ?>">
        <span class="cl-dot<?= $open ? ' cur' : '' ?>"></span>
        <button type="button" class="cl-rel-head" aria-expanded="<?= $open ? 'true' : 'false' ?>">
            <span class="cl-ver">v<?= htmlspecialchars($rel['version'] ?? '?') ?></span>
            <span class="cl-rel-title"><?= htmlspecialchars($rel['title'] ?? 'Version') ?></span>
            <span class="cl-rel-tags">
                <?php foreach (($rel['types'] ?? []) as $t): $tm = $typeMeta[$t] ?? null; if ($tm): ?>
                    <span class="ui-badge <?= $tm[1] ?>" style="font-size:10px"><?= $tm[0] ?></span>
                <?php endif; endforeach; ?>
            </span>
            <span class="cl-rel-date">📅 <?= htmlspecialchars($frDate($rel['date'] ?? '')) ?></span>
            <span class="cl-chevron">›</span>
        </button>

        <div class="cl-rel-body">
            <div class="ui-card"><div class="ui-card-body" style="display:flex;flex-direction:column;gap:14px">
                <?php foreach ($rel['groups'] ?? [] as $g): ?>
                <div class="cl-group">
                    <?php if (!empty($g['title'])): ?><div class="cl-group-title"><?= htmlspecialchars($g['title']) ?></div><?php endif; ?>
                    <ul class="cl-items">
                        <?php foreach ($g['items'] ?? [] as $it):
                            $text = is_array($it) ? ($it['text'] ?? '') : (string)$it;
                            $sub  = is_array($it) ? !empty($it['sub']) : false; ?>
                            <li class="cl-item<?= $sub ? ' cl-sub' : '' ?>"><?= $rt($text) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endforeach; ?>
            </div></div>
        </div>
    </section>
    <?php endforeach; ?>
</div>
<div class="cl-noresult" id="clNoResult" style="display:none">Aucune version ne correspond à votre recherche.</div>

<?php endif; ?>

<style>
.cl-search{width:100%;max-width:480px;padding:10px 14px;border:1px solid var(--border);background:var(--surface);color:var(--text);border-radius:var(--radius-sm);font-size:14px}
.cl-search:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-soft)}
.cl-timeline{position:relative;padding-left:26px}
.cl-timeline::before{content:'';position:absolute;left:7px;top:8px;bottom:8px;width:2px;background:var(--border)}
.cl-rel{position:relative;margin-bottom:12px}
.cl-dot{position:absolute;left:-26px;top:12px;width:16px;height:16px;border-radius:50%;background:var(--border-strong,#94a3b8);border:3px solid var(--surface)}
.cl-dot.cur{background:var(--accent);box-shadow:0 0 0 3px var(--accent-soft)}
.cl-rel-head{display:flex;align-items:center;gap:12px;flex-wrap:wrap;width:100%;text-align:left;cursor:pointer;
    background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-sm);padding:11px 14px;font-family:inherit}
.cl-rel-head:hover{border-color:var(--accent)}
.cl-rel.open .cl-rel-head{border-color:var(--accent);border-bottom-left-radius:0;border-bottom-right-radius:0}
.cl-ver{font-weight:800;font-size:.9rem;color:var(--accent);background:var(--accent-soft);padding:2px 9px;border-radius:99px;flex-shrink:0}
.cl-rel-title{font-weight:700;color:var(--text);flex:1;min-width:160px}
.cl-rel-tags{display:flex;gap:5px;flex-wrap:wrap}
.cl-rel-date{font-size:12px;color:var(--text-soft);white-space:nowrap}
.cl-chevron{font-size:1.1rem;color:var(--text-soft);transition:transform .2s}
.cl-rel.open .cl-chevron{transform:rotate(90deg)}
.cl-rel-body{display:none}
.cl-rel.open .cl-rel-body{display:block}
.cl-rel-body .ui-card{border-top:none;border-top-left-radius:0;border-top-right-radius:0;margin:0}
.cl-group-title{font-weight:700;font-size:.9rem;color:var(--text);margin:2px 0 6px}
.cl-items{margin:0;padding-left:18px;display:flex;flex-direction:column;gap:5px}
.cl-item{font-size:.86rem;color:var(--text-soft);line-height:1.55}
.cl-item code{font-size:.82em;background:var(--surface-2);border:1px solid var(--border);padding:.05em .35em;border-radius:4px;color:var(--text)}
.cl-item strong{color:var(--text);font-weight:700}
.cl-item.cl-sub{list-style:'↳ ';margin-left:14px;opacity:.92}
.cl-noresult{padding:18px;color:var(--text-soft);text-align:center}
</style>
<script>
(function () {
    var rels = Array.prototype.slice.call(document.querySelectorAll('.cl-rel'));
    // Accordéon : clic sur l'en-tête ouvre/ferme la version.
    rels.forEach(function (r) {
        var head = r.querySelector('.cl-rel-head');
        head && head.addEventListener('click', function () {
            var open = r.classList.toggle('open');
            head.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });
    // Recherche : ouvre les versions qui matchent, referme sinon (1ʳᵉ ouverte si vide).
    var s = document.getElementById('clSearch'), noRes = document.getElementById('clNoResult');
    if (s) s.addEventListener('input', function () {
        var q = s.value.trim().toLowerCase(), any = false;
        rels.forEach(function (r, i) {
            var match = !q || (r.getAttribute('data-search') || '').indexOf(q) !== -1;
            r.style.display = match ? '' : 'none';
            if (q) r.classList.toggle('open', match);
            else r.classList.toggle('open', i === 0);
            if (match) any = true;
        });
        if (noRes) noRes.style.display = any ? 'none' : 'block';
    });
}());
</script>

<?php admin_footer(); ?>
