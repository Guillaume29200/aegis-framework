<?php
/**
 * Bandeau de consentement aux cookies (RGPD / CNIL) — piloté depuis l'admin.
 * Configuration : Admin → Configuration → RGPD / Cookies.
 */
if (!defined('ESPORT_CMS')) {
    return;
}

$rgpd = null;
try {
    $db = $GLOBALS['db'] ?? null;
    if ($db instanceof \Framework\Services\Database && class_exists('Configuration\\Services\\RgpdService')) {
        $rgpd = (new \Configuration\Services\RgpdService($db))->getConfig();
    }
} catch (\Throwable $e) {
    $rgpd = null;
}

if (!$rgpd || empty($rgpd['enabled'])) {
    return;
}

$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$policyUrl = $rgpd['policy_url'] !== '' ? $rgpd['policy_url'] : '';
if ($policyUrl !== '' && $policyUrl[0] === '/' && function_exists('u')) {
    $policyUrl = u($policyUrl);
}
$col = $rgpd['colors'];
$pos = $rgpd['position'] === 'top' ? 'top' : 'bottom';
$rad = (int)$rgpd['radius'];
$visibleCats = array_filter($rgpd['categories'], fn($c) => !empty($c['active']) || !empty($c['required']));
?>
<style>
.rgpd-banner{position:fixed;left:16px;right:16px;<?= $pos ?>:16px;z-index:2147482000;display:none;max-width:1080px;margin:0 auto;
    background:<?= $h($col['bg']) ?>;color:<?= $h($col['text']) ?>;border-radius:<?= $rad ?>px;
    box-shadow:0 18px 48px rgba(0,0,0,.4);font-family:system-ui,-apple-system,"Segoe UI",sans-serif}
.rgpd-banner.is-visible{display:block}
.rgpd-inner{display:grid;grid-template-columns:1fr auto;gap:16px;align-items:center;padding:18px}
.rgpd-title{font-weight:700;font-size:16px;margin-bottom:4px}
.rgpd-text{font-size:13px;line-height:1.5;margin:0;opacity:.85}
.rgpd-text a{color:<?= $h($col['accent']) ?>;text-decoration:none}
.rgpd-actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
.rgpd-btn{border:1px solid transparent;border-radius:<?= max(4,$rad-4) ?>px;padding:9px 14px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;color:#fff}
.rgpd-btn.accept{background:<?= $h($col['accent']) ?>}
.rgpd-btn.refuse{background:<?= $h($col['refuse']) ?>}
.rgpd-btn.ghost{background:transparent;border-color:<?= $h($col['refuse']) ?>;color:<?= $h($col['text']) ?>}
.rgpd-btn:hover{filter:brightness(1.12)}
.rgpd-panel{display:none;border-top:1px solid rgba(255,255,255,.12);padding:14px 18px}
.rgpd-panel.is-open{display:block}
.rgpd-cats{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:10px}
.rgpd-cat{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:12px}
.rgpd-cat label{display:flex;gap:8px;align-items:flex-start;font-size:13px;font-weight:600}
.rgpd-cat small{display:block;margin-top:4px;opacity:.7;line-height:1.4;font-weight:400}
@media (max-width:720px){.rgpd-inner{grid-template-columns:1fr}.rgpd-actions{justify-content:stretch}.rgpd-btn{flex:1 1 auto}}
</style>

<div class="rgpd-banner" id="rgpd-banner" role="dialog" aria-live="polite" aria-label="Consentement cookies">
    <div class="rgpd-inner">
        <div>
            <div class="rgpd-title"><?= $h($rgpd['title']) ?></div>
            <p class="rgpd-text"><?= $h($rgpd['intro']) ?><?php if ($policyUrl !== ''): ?> <a href="<?= $h($policyUrl) ?>">En savoir plus</a>.<?php endif; ?></p>
        </div>
        <div class="rgpd-actions">
            <button type="button" class="rgpd-btn ghost" data-rgpd="settings">Personnaliser</button>
            <button type="button" class="rgpd-btn refuse" data-rgpd="refuse">Refuser</button>
            <button type="button" class="rgpd-btn accept" data-rgpd="accept">Tout accepter</button>
        </div>
    </div>
    <div class="rgpd-panel" id="rgpd-panel">
        <div class="rgpd-cats">
            <?php foreach ($visibleCats as $cat): ?>
                <div class="rgpd-cat">
                    <label>
                        <input type="checkbox" data-cat="<?= $h($cat['code']) ?>" <?= $cat['required'] ? 'checked disabled' : '' ?>>
                        <span><?= $h($cat['icon'] ?? '') ?> <?= $h($cat['name']) ?><?= $cat['required'] ? ' (obligatoire)' : '' ?></span>
                    </label>
                    <?php if (!empty($cat['description'])): ?><small><?= $h($cat['description']) ?></small><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="rgpd-actions" style="margin-top:12px">
            <button type="button" class="rgpd-btn accept" data-rgpd="save">Enregistrer mes choix</button>
        </div>
    </div>
</div>

<script>
(function () {
    var banner = document.getElementById('rgpd-banner');
    if (!banner || window.__RGPD_READY) return;
    window.__RGPD_READY = true;
    var KEY = 'rgpd_consent', VERSION = <?= (int)$rgpd['version'] ?>, MAXAGE = <?= max(1, (int)$rgpd['validity_days']) * 86400 ?>;
    var panel = document.getElementById('rgpd-panel'), boxes = banner.querySelectorAll('[data-cat]');
    function stored() { try { return JSON.parse(localStorage.getItem(KEY) || 'null'); } catch (e) { return null; } }
    function persist(consent) {
        var payload = { version: VERSION, categories: consent, accepted_at: new Date().toISOString() };
        try { localStorage.setItem(KEY, JSON.stringify(payload)); } catch (e) {}
        document.cookie = KEY + '=' + encodeURIComponent(JSON.stringify(payload)) + '; Max-Age=' + MAXAGE + '; Path=/; SameSite=Lax';
        banner.classList.remove('is-visible');
        window.dispatchEvent(new CustomEvent('rgpd:consent', { detail: payload }));
    }
    function collect(all, none) {
        var c = {};
        boxes.forEach(function (b) { var k = b.getAttribute('data-cat'); c[k] = b.disabled ? true : (all ? true : (none ? false : b.checked)); });
        return c;
    }
    var s = stored();
    if (!s || s.version !== VERSION) banner.classList.add('is-visible');
    banner.addEventListener('click', function (e) {
        var a = e.target.closest('[data-rgpd]'); if (!a) return;
        switch (a.getAttribute('data-rgpd')) {
            case 'accept': persist(collect(true, false)); break;
            case 'refuse': persist(collect(false, true)); break;
            case 'settings': panel.classList.toggle('is-open'); break;
            case 'save': persist(collect(false, false)); break;
        }
    });
})();
</script>
