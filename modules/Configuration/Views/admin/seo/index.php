<?php
/**
 * SEO & médias — UI maison
 * Variables : $seo (config), $csrfToken
 */
if (!defined('AEGIS_FRAMEWORK')) die('Access denied');

$pageTitle = $pageTitle ?? 'SEO & médias';
admin_header($pageTitle);

$seo = $seo ?? [];
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$asset = fn($p) => $p ? u($p) : '';
?>

<div class="adm-page-head u-between" style="flex-wrap:wrap;gap:12px">
    <div>
        <div class="adm-breadcrumb"><a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><a href="<?= u('/admin/configuration') ?>">Configuration</a><span>/</span><span>SEO &amp; médias</span></div>
        <h1>🔍 SEO &amp; médias</h1>
        <p>Logo, favicon, partage social et référencement de votre site.</p>
    </div>
    <button type="submit" form="seo-form" class="ui-btn primary">💾 Enregistrer</button>
</div>

<div id="seo-flash"></div>
<?php
$flashOk  = $_SESSION['success'] ?? null; unset($_SESSION['success']);
$flashErr = $_SESSION['error'] ?? null;   unset($_SESSION['error']);
if ($flashOk): ?><div class="ui-card" style="border-color:var(--green-soft);margin-bottom:16px"><div class="ui-card-body" style="color:var(--green)"><?= $h($flashOk) ?></div></div><?php endif;
if ($flashErr): ?><div class="ui-card" style="border-color:var(--red-soft);margin-bottom:16px"><div class="ui-card-body" style="color:var(--red)"><?= $h($flashErr) ?></div></div><?php endif; ?>

<form id="seo-form" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= $h($csrfToken ?? '') ?>">

    <div class="ui-grid cols-3" style="grid-template-columns:1fr 1fr 1fr;align-items:start">
        <!-- Logo -->
        <div class="ui-card">
            <div class="ui-card-head">🖼️ Logo</div>
            <div class="ui-card-body">
                <div class="seo-preview">
                    <?php if (!empty($seo['logo_url'])): ?><img src="<?= $h($asset($seo['logo_url'])) ?>" alt="Logo"><?php else: ?><span class="seo-ph">🖼️</span><?php endif; ?>
                </div>
                <input type="file" class="form-control" name="logo_file" accept=".svg,.png,.jpg,.jpeg,.webp,.gif">
                <p class="form-text">SVG, PNG, JPG, WebP, GIF · ~200×60 px.</p>
                <?php if (!empty($seo['logo_url'])): ?><label class="form-check"><input type="checkbox" name="remove_logo"> <span>🗑️ Supprimer</span></label><?php endif; ?>
            </div>
        </div>
        <!-- Favicon -->
        <div class="ui-card">
            <div class="ui-card-head">⭐ Favicon</div>
            <div class="ui-card-body">
                <div class="seo-preview small">
                    <?php if (!empty($seo['favicon_url'])): ?><img src="<?= $h($asset($seo['favicon_url'])) ?>" alt="Favicon"><?php else: ?><span class="seo-ph">⭐</span><?php endif; ?>
                </div>
                <input type="file" class="form-control" name="favicon_file" accept=".svg,.png,.ico">
                <p class="form-text">SVG, PNG, ICO · 32×32 ou 64×64 px.</p>
                <?php if (!empty($seo['favicon_url'])): ?><label class="form-check"><input type="checkbox" name="remove_favicon"> <span>🗑️ Supprimer</span></label><?php endif; ?>
            </div>
        </div>
        <!-- OG -->
        <div class="ui-card">
            <div class="ui-card-head">🌐 Image de partage (OG)</div>
            <div class="ui-card-body">
                <div class="seo-preview wide">
                    <?php if (!empty($seo['og_image'])): ?><img src="<?= $h($asset($seo['og_image'])) ?>" alt="OG"><?php else: ?><span class="seo-ph">🌐</span><?php endif; ?>
                </div>
                <input type="file" class="form-control" name="og_file" accept=".png,.jpg,.jpeg,.webp">
                <p class="form-text">Réseaux sociaux · 1200×630 px.</p>
                <?php if (!empty($seo['og_image'])): ?><label class="form-check"><input type="checkbox" name="remove_og"> <span>🗑️ Supprimer</span></label><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="ui-grid cols-2" style="margin-top:18px;align-items:start">
        <div class="ui-card">
            <div class="ui-card-head">📝 Méta-données</div>
            <div class="ui-card-body">
                <div class="fld" style="margin-bottom:16px"><label class="form-label">Modèle de titre</label><input class="form-control" name="meta_title_template" value="<?= $h($seo['meta_title']) ?>"><p class="form-text">Variables : <code>{page_title}</code>, <code>{site_name}</code>.</p></div>
                <div class="fld" style="margin-bottom:16px"><label class="form-label">Description par défaut</label><textarea class="form-control" name="meta_description_default" rows="3" maxlength="160"><?= $h($seo['meta_description']) ?></textarea><p class="form-text">160 caractères max (fallback toutes pages).</p></div>
                <div class="fld"><label class="form-label">Mots-clés par défaut</label><input class="form-control" name="meta_keywords_default" value="<?= $h($seo['meta_keywords']) ?>"></div>
            </div>
        </div>
        <div class="ui-card">
            <div class="ui-card-head">🤖 Indexation &amp; analytics</div>
            <div class="ui-card-body">
                <div class="fld" style="margin-bottom:16px"><label class="form-label">Robots</label>
                    <select class="form-select" name="seo_robots">
                        <?php foreach ([
                            'index,follow' => '✅ index, follow — Indexer et suivre (recommandé)',
                            'noindex,follow' => '🚫 noindex, follow — Ne pas indexer',
                            'index,nofollow' => '⛔ index, nofollow — Indexer sans suivre',
                            'noindex,nofollow' => '🔒 noindex, nofollow — Masquer complètement',
                        ] as $k => $lbl): ?>
                            <option value="<?= $k ?>" <?= ($seo['robots'] ?? 'index,follow') === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fld"><label class="form-label">Google Analytics / Tag Manager</label><input class="form-control" name="seo_ga_id" value="<?= $h($seo['ga_id']) ?>" placeholder="G-XXXXXXXXXX ou GTM-XXXXXXX" style="font-family:monospace"><p class="form-text">Injecté automatiquement dans le <code>&lt;head&gt;</code>.</p></div>
            </div>
        </div>
    </div>

    <div class="u-flex" style="justify-content:flex-end;margin-top:18px"><button type="submit" class="ui-btn primary">💾 Enregistrer le SEO</button></div>
</form>

<?php $sm = $sitemapStatus ?? ['sitemap_exists' => false, 'writable' => true]; ?>
<div class="ui-card u-mt">
    <div class="ui-card-head">🗺️ Sitemap &amp; robots.txt</div>
    <div class="ui-card-body">
        <p class="u-muted" style="margin-top:0">Génère <code>sitemap.xml</code> (accueil + forum : catégories, sujets et pages publiques) et <code>robots.txt</code> à la racine du site.</p>

        <?php if (empty($sm['writable'])): ?>
            <div class="ui-card" style="border-color:var(--amber-soft);margin-bottom:14px"><div class="ui-card-body" style="color:var(--amber)">⚠️ La racine du site n'est pas accessible en écriture — la génération échouera. Vérifiez les permissions.</div></div>
        <?php endif; ?>

        <div class="u-flex" style="gap:24px;flex-wrap:wrap;align-items:center;margin-bottom:14px">
            <div>
                <div class="u-muted" style="font-size:12px">sitemap.xml</div>
                <?php if (!empty($sm['sitemap_exists'])): ?>
                    <div><span class="ui-badge green">✅ Généré</span> le <strong><?= $h($sm['sitemap_date']) ?></strong> · <?= (int)($sm['sitemap_urls'] ?? 0) ?> URL(s)</div>
                <?php else: ?>
                    <div><span class="ui-badge amber">⚠️ Non généré</span></div>
                <?php endif; ?>
            </div>
            <div>
                <div class="u-muted" style="font-size:12px">robots.txt</div>
                <?php if (!empty($sm['robots_exists'])): ?>
                    <div><span class="ui-badge green">✅ Généré</span> le <strong><?= $h($sm['robots_date']) ?></strong></div>
                <?php else: ?>
                    <div><span class="ui-badge amber">⚠️ Non généré</span></div>
                <?php endif; ?>
            </div>
        </div>

        <form method="post" action="<?= u('/admin/configuration/sitemap/generate') ?>">
            <input type="hidden" name="csrf_token" value="<?= $h($csrfToken ?? '') ?>">
            <button type="submit" class="ui-btn primary">
                <?= !empty($sm['sitemap_exists']) ? '🔄 Mettre à jour / régénérer' : '⚙️ Générer le sitemap' ?>
            </button>
            <?php if (!empty($sm['sitemap_exists'])): ?>
                <a class="ui-btn" href="<?= u('/sitemap.xml') ?>" target="_blank" rel="noopener">👁️ Voir le sitemap</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<style>
.seo-preview { display: grid; place-items: center; height: 90px; border: 1px dashed var(--border-strong); border-radius: var(--radius); background: var(--surface-2); margin-bottom: 12px; overflow: hidden; }
.seo-preview img { max-height: 70px; max-width: 100%; object-fit: contain; }
.seo-preview.small img { width: 40px; height: 40px; }
.seo-preview.wide img { max-height: 84px; width: 100%; object-fit: cover; }
.seo-preview .seo-ph { font-size: 30px; opacity: .35; }
</style>

<script>
(function () {
    var form = document.getElementById('seo-form'), flash = document.getElementById('seo-flash');
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        fetch('<?= u('/admin/configuration/seo/save') ?>', { method: 'POST', body: new FormData(form) })
            .then(r => r.json())
            .then(function (d) {
                flash.innerHTML = '<div class="ui-card" style="border-color:var(--' + (d.success ? 'green' : 'red') + '-soft);margin-bottom:16px"><div class="ui-card-body" style="color:var(--' + (d.success ? 'green' : 'red') + ')">' + (d.success ? '✅ ' : '❌ ') + d.message + '</div></div>';
                window.scrollTo({ top: 0, behavior: 'smooth' });
                if (d.success) setTimeout(function () { location.reload(); }, 900);
            })
            .catch(function (err) { flash.innerHTML = '<div class="ui-card"><div class="ui-card-body" style="color:var(--red)">❌ ' + err + '</div></div>'; });
    });
})();
</script>

<?php admin_footer(); ?>
