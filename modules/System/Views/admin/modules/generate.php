<?php
/**
 * Générateur de module (scaffolding) — Aegis.
 * Variables : $csrfToken, $licenseAvailable, $capabilities
 */
if (!defined('AEGIS_FRAMEWORK')) die('Access denied');

$pageTitle = $pageTitle ?? 'Générateur de module';
admin_header($pageTitle);

$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$flashErr = $_SESSION['error'] ?? null; unset($_SESSION['error']);

$caps      = $capabilities ?? [];
$capsAvail = array_filter($caps, fn($c) => !empty($c['available']));
$capsSoon  = array_filter($caps, fn($c) => empty($c['available']));
?>

<div class="adm-page-head">
    <div class="adm-breadcrumb"><a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><a href="<?= u('/admin/modules') ?>">Modules</a><span>/</span><span>Générateur</span></div>
    <h1>🪄 Générateur de module</h1>
    <p>Crée un squelette de module complet, prêt à activer — et y câble automatiquement les briques transverses (Markdown, Cache, IA…) au lieu de les recoder.</p>
</div>

<?php if ($flashErr): ?><div class="ui-card" style="border-color:var(--red-soft);margin-bottom:14px"><div class="ui-card-body" style="color:var(--red)">⚠️ <?= $h($flashErr) ?></div></div><?php endif; ?>

<!-- Bandeau philosophie : centralisation via Configuration -->
<div class="gen-hero">
    <div class="gen-hero-icon">🎛️</div>
    <div>
        <h2 class="gen-hero-title">Un seul endroit pour tout piloter&nbsp;: <a href="<?= u('/admin/configuration') ?>">Configuration</a></h2>
        <p class="gen-hero-text">
            Les fonctionnalités transverses (Markdown, Cache, IA, SEO, RGPD, Analytics, reCAPTCHA) sont
            <strong>mutualisées au niveau du framework</strong> et se règlent depuis <em>Configuration</em>.
            Fini les sous-configurations dupliquées dans chaque module&nbsp;: on coche une capacité ici, elle est câblée automatiquement —
            <strong>moins de code, aucun fichier recréé, un seul moteur partagé</strong>.
        </p>
    </div>
</div>

<form method="post" action="<?= u('/admin/modules/generate') ?>">
    <input type="hidden" name="csrf_token" value="<?= $h($csrfToken ?? '') ?>">

    <div class="ui-grid cols-2" style="grid-template-columns:1fr 1fr;gap:18px;align-items:start">
        <div class="ui-card">
            <div class="ui-card-head">📋 Identité</div>
            <div class="ui-card-body">
                <div class="fld" style="margin-bottom:12px"><label class="form-label">Nom technique (PascalCase) *</label><input class="form-control" name="name" placeholder="MonModule" pattern="[A-Za-z0-9]+" required></div>
                <div class="fld" style="margin-bottom:12px"><label class="form-label">Nom affiché</label><input class="form-control" name="display_name" placeholder="Mon Module"></div>
                <div class="fld" style="margin-bottom:12px"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="2"></textarea></div>
                <div class="fld" style="margin-bottom:0"><label class="form-label">Auteur</label><input class="form-control" name="author" placeholder="Studio"></div>
            </div>
        </div>
        <div class="ui-card">
            <div class="ui-card-head">🎨 Apparence &amp; menu</div>
            <div class="ui-card-body">
                <div class="fld" style="margin-bottom:12px"><label class="form-label">Icône (emoji)</label><input class="form-control" name="icon" placeholder="🧩" maxlength="4"></div>
                <div class="fld" style="margin-bottom:12px"><label class="form-label">Catégorie (page Modules)</label><input class="form-control" name="category" placeholder="Autres" list="cat-list">
                    <datalist id="cat-list"><option value="Système"><option value="Communautaire"><option value="e-commerce"><option value="Hébergement"><option value="Autres"></datalist>
                </div>
                <div class="fld" style="margin-bottom:12px"><label class="form-label">Sections (séparées par des virgules)</label><input class="form-control" name="sections" placeholder="Liste, Réglages, Statistiques"><p class="form-text">Un « Tableau de bord » est toujours créé. Chaque section = une page + une entrée de menu.</p></div>
                <label class="ui-switch" style="display:inline-flex;align-items:center;gap:10px"><input type="checkbox" name="mega"><span>Afficher en mega-menu</span></label>
            </div>
        </div>
    </div>

<?php if (!empty($caps)): $ce = $h; ?>
    <div class="ui-card gen-caps-card" style="margin-top:18px">
        <div class="ui-card-head">
            🧩 Fonctionnalités
            <span class="gen-caps-count" id="genCapsCount">0 sélectionnée</span>
        </div>
        <div class="ui-card-body">
            <p class="u-muted" style="margin-top:0">Cochez les briques à câbler automatiquement. Chacune est <strong>gérée globalement depuis Configuration</strong> — le module généré s'y branche, sans réglage local à maintenir.</p>

            <?php if ($capsAvail): ?>
                <div class="gen-caps">
                    <?php foreach ($capsAvail as $key => $cap): ?>
                        <label class="gen-cap">
                            <input type="checkbox" name="capabilities[]" value="<?= $ce($key) ?>" class="gen-cap-input">
                            <span class="gen-cap-check" aria-hidden="true"></span>
                            <span class="gen-cap-body">
                                <span class="gen-cap-title"><span class="gen-cap-icon"><?= $ce($cap['icon']) ?></span><?= $ce($cap['name']) ?></span>
                                <span class="gen-cap-desc"><?= $ce($cap['description']) ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($capsSoon): ?>
                <div class="gen-caps-soon-label">🔜 Bientôt disponibles</div>
                <div class="gen-caps">
                    <?php foreach ($capsSoon as $key => $cap): ?>
                        <label class="gen-cap is-soon">
                            <input type="checkbox" disabled>
                            <span class="gen-cap-check" aria-hidden="true"></span>
                            <span class="gen-cap-body">
                                <span class="gen-cap-title"><span class="gen-cap-icon"><?= $ce($cap['icon']) ?></span><?= $ce($cap['name']) ?><span class="gen-cap-badge">bientôt</span></span>
                                <span class="gen-cap-desc"><?= $ce($cap['description']) ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>


    <div class="ui-card gen-tpl-card" style="margin-top:18px">
        <div class="ui-card-head">🖼️ Moteur de templates <span class="u-muted" style="font-weight:400;font-size:12px">— partie publique</span></div>
        <div class="ui-card-body">
            <p class="u-muted" style="margin-top:0">
                Le moteur de gabarits et la gestion des thèmes vivent <strong>dans le framework</strong> : on ne les recopie plus d'un module à l'autre.
                Cochez cette case et le module généré arrive avec une page publique, un thème livré, et les écrans pour en changer ou en installer d'autres.
                <br><strong>Un thème est un dossier autonome</strong> — ses gabarits à la racine, son <code>assets/</code> avec <code>css</code>, <code>js</code>, <code>images</code> et <code>uploads</code> —
                qu'on zippe et qu'on partage tel quel.
            </p>

            <label class="ui-switch" style="display:inline-flex;align-items:center;gap:10px;margin-bottom:4px">
                <input type="checkbox" name="public" id="genPublic">
                <span>Ce module disposera-t-il d'une partie publique&nbsp;?</span>
            </label>

            <div id="genPublicBox" style="display:none;margin-top:16px">
                <div class="ui-grid cols-2" style="grid-template-columns:1fr 1fr;gap:16px">
                    <div class="fld" style="margin:0">
                        <label class="form-label">Clé du thème livré</label>
                        <input class="form-control" name="theme" id="genThemeKey" placeholder="default" pattern="[A-Za-z0-9_-]+">
                        <p class="form-text">Le nom du dossier : <code>Views/themes/&lt;clé&gt;</code>. Minuscules, sans espace. Par défaut&nbsp;: <code>default</code>.</p>
                    </div>
                    <div class="fld" style="margin:0">
                        <label class="form-label">Nom affiché du thème</label>
                        <input class="form-control" name="theme_name" placeholder="Default">
                        <p class="form-text">Ce que l'administrateur lira dans la liste des thèmes.</p>
                    </div>
                </div>


                <div class="gen-tpl-pick">
                    <b class="gen-tpl-pick__t">📄 Gabarits à générer</b>
                    <p class="form-text" style="margin:0 0 10px">Une page d'accueil est toujours créée. Cochez ce que le module doit savoir faire de plus&nbsp;: vous recevrez les gabarits <em>et</em> le code qui les alimente.</p>

                    <label class="gen-tpl-opt">
                        <input type="checkbox" name="tpl_list" id="genTplList">
                        <span>
                            <b>Liste + fiche</b>
                            <em>Deux gabarits, deux routes, et les méthodes du service qui vont avec. Trois exemples sont semés à l'installation pour que la liste montre quelque chose tout de suite.</em>
                        </span>
                    </label>

                    <div id="genTplSub" style="display:none">
                        <label class="gen-tpl-opt gen-tpl-opt--sub">
                            <input type="checkbox" name="tpl_pagination">
                            <span>
                                <b>avec pagination</b>
                                <em>Douze éléments par page, liens calculés côté serveur.</em>
                            </span>
                        </label>

                        <label class="gen-tpl-opt gen-tpl-opt--sub">
                            <input type="checkbox" name="tpl_search">
                            <span>
                                <b>avec recherche</b>
                                <em>Un champ qui interroge le serveur — une liste paginée ne se filtre pas dans le navigateur sans mentir sur le nombre trouvé.</em>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="gen-tpl-what">
                    <div class="gen-tpl-col">
                        <b>🎨 Le thème livré — un dossier autonome</b>
                        <code>themes/&lt;clé&gt;/meta.json</code>
                        <code>themes/&lt;clé&gt;/header.html · footer.html · home.html</code>
                        <code>themes/&lt;clé&gt;/assets/css/theme.css</code>
                        <code>themes/&lt;clé&gt;/assets/js/theme.js</code>
                        <code>themes/&lt;clé&gt;/assets/images/</code>
                        <code>themes/&lt;clé&gt;/assets/uploads/</code>
                        <code>themes/&lt;clé&gt;/README.md</code>
                    </div>
                    <div class="gen-tpl-col">
                        <b>⚙️ Côté administration</b>
                        <span>Choix du thème actif</span>
                        <span>Installation d'un thème (ZIP)</span>
                        <span>Suppression d'un thème</span>
                        <span>Options du thème, par onglets</span>
                        <span>Images téléversables</span>
                    </div>
                    <div class="gen-tpl-col">
                        <b>🔒 Ce qu'un thème ne peut pas faire</b>
                        <span>Aucun PHP dans les gabarits</span>
                        <span>Archive filtrée à l'installation</span>
                        <span>Un gabarit manquant retombe sur le thème livré</span>
                        <span>Les capacités cochées sont posées par le thème</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php if (!empty($licenseAvailable)): ?>
    <div class="ui-card" style="margin-top:18px">
        <div class="ui-card-head">🔑 Système de licence <span class="u-muted" style="font-weight:400;font-size:12px">— optionnel</span></div>
        <div class="ui-card-body">
            <label class="ui-switch" style="display:inline-flex;align-items:center;gap:10px;margin-bottom:12px"><input type="checkbox" name="license" id="genLicense"><span>Activer le système de licence pour ce module</span></label>
            <div class="fld" id="genLicenseProduct" style="margin-bottom:0;display:none"><label class="form-label">Slug du produit (licence)</label><input class="form-control" name="license_product" placeholder="mon-module"><p class="form-text">Laissez vide pour utiliser le nom du module en minuscules. Le module sera livré en mode <strong>Ouvert</strong> (sans blocage) ; vous le passerez « Sous licence » depuis <em>Licences → Intégration</em>.</p></div>
        </div>
    </div>
<?php endif; ?>

    <div class="gen-submit">
        <div class="gen-submit-hint u-muted">Le module est créé <strong>inactif</strong> — vous l'activez ensuite depuis la page Modules.</div>
        <button class="ui-btn primary" type="submit">🪄 Générer le module</button>
    </div>
</form>

<div class="ui-card">
    <div class="ui-card-head">📦 Ce qui sera généré</div>
    <div class="ui-card-body u-muted" style="font-size:13px">
        <code>module.json</code> (menu + catégorie + <code>capabilities</code>) · <code>&lt;Nom&gt;.php</code> · <code>routes.php</code> ·
        <code>Controllers/AdminController.php</code> · <code>Services/&lt;Nom&gt;Service.php</code> ·
        <code>database/install.sql</code> + <code>uninstall.sql</code> + dossier <code>migrations/</code> ·
        <code>changelog.json</code> · vues <code>Views/admin/</code> (dashboard + sections) en UI maison.
        <br>Avec une <strong>partie publique</strong> : <code>Controllers/PublicController.php</code> · <code>Controllers/ThemeAdminController.php</code> ·
        <code>Services/&lt;Nom&gt;Themes.php</code> · le dossier <code>themes/&lt;clé&gt;/</code> complet ·
        les écrans <code>Views/admin/themes.php</code> et <code>theme-options.php</code> · la table <code>&lt;prefixe&gt;_settings</code>.
        <br>Les capacités cochées sont câblées via les helpers du framework — <strong>aucun code dupliqué</strong>, et
        <strong>posées automatiquement dans le thème</strong> quand une partie publique est demandée.
    </div>
</div>

<style>
.ui-switch input[type=checkbox]{appearance:none;-webkit-appearance:none;opacity:1;flex:none;margin:0;width:42px;height:24px;border-radius:24px;background:var(--border-strong);position:relative;cursor:pointer;transition:background .2s}
.ui-switch input[type=checkbox]::after{content:"";position:absolute;top:2px;left:2px;width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.35);transition:transform .2s}
.ui-switch input[type=checkbox]:checked{background:var(--accent)}
.ui-switch input[type=checkbox]:checked::after{transform:translateX(18px)}

/* Hero philosophie */
.gen-hero{display:flex;gap:16px;align-items:flex-start;padding:18px 20px;margin-bottom:18px;border:1px solid var(--border);border-left:4px solid var(--accent);border-radius:12px;background:linear-gradient(120deg,color-mix(in srgb,var(--accent) 8%,transparent),transparent 60%),var(--surface-2)}
.gen-hero-icon{font-size:30px;line-height:1;flex:none}
.gen-hero-title{margin:0 0 6px;font-size:16px;font-weight:700}
.gen-hero-title a{color:var(--accent);text-decoration:none}
.gen-hero-title a:hover{text-decoration:underline}
.gen-hero-text{margin:0;font-size:13px;line-height:1.6;color:var(--text-muted,#64748b)}

/* Compteur capacités */
.gen-caps-count{float:right;font-size:11px;font-weight:700;padding:2px 10px;border-radius:999px;background:var(--surface-3);color:var(--text-muted,#64748b)}
.gen-caps-count.has{background:var(--accent);color:#fff}

/* Cartes capacités */
.gen-caps{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px}
.gen-cap{position:relative;display:flex;gap:12px;align-items:flex-start;padding:14px 15px 14px 14px;border:1.5px solid var(--border);border-radius:12px;background:var(--surface-2);cursor:pointer;transition:border-color .15s,background .15s,box-shadow .15s}
.gen-cap:hover{border-color:var(--accent)}
.gen-cap .gen-cap-input{position:absolute;opacity:0;pointer-events:none}
.gen-cap-check{flex:none;width:20px;height:20px;margin-top:1px;border:2px solid var(--border-strong);border-radius:6px;position:relative;transition:border-color .15s,background .15s}
.gen-cap-check::after{content:"";position:absolute;left:6px;top:2px;width:5px;height:10px;border:solid #fff;border-width:0 2px 2px 0;transform:rotate(45deg) scale(0);transition:transform .12s}
.gen-cap-input:checked ~ .gen-cap-check{background:var(--accent);border-color:var(--accent)}
.gen-cap-input:checked ~ .gen-cap-check::after{transform:rotate(45deg) scale(1)}
.gen-cap:has(.gen-cap-input:checked){border-color:var(--accent);background:color-mix(in srgb,var(--accent) 7%,var(--surface-2));box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 14%,transparent)}
.gen-cap-body{display:flex;flex-direction:column;gap:4px}
.gen-cap-title{display:flex;align-items:center;gap:7px;font-weight:600}
.gen-cap-icon{font-size:16px}
.gen-cap-desc{font-size:12.5px;line-height:1.45;color:var(--text-muted,#64748b)}
.gen-cap-badge{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;padding:2px 7px;border-radius:999px;background:var(--border-strong);color:var(--text-muted,#64748b)}
.gen-cap.is-soon{opacity:.6;cursor:not-allowed}
.gen-cap.is-soon:hover{border-color:var(--border)}
.gen-caps-soon-label{margin:18px 0 10px;font-size:12px;font-weight:700;letter-spacing:.03em;text-transform:uppercase;color:var(--text-muted,#64748b)}

/* Moteur de templates */
.gen-tpl-what{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:14px;margin-top:18px;padding-top:16px;border-top:1px solid var(--border)}
.gen-tpl-col{display:flex;flex-direction:column;gap:5px;font-size:12.5px}
.gen-tpl-col b{font-size:12.5px;margin-bottom:3px}
.gen-tpl-col code,.gen-tpl-col span{color:var(--text-muted,#64748b);font-size:12px}
.gen-tpl-card:has(#genPublic:checked){border-color:var(--accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 12%,transparent)}

.gen-tpl-pick{margin-top:18px;padding-top:16px;border-top:1px solid var(--border)}
.gen-tpl-pick__t{display:block;font-size:13px;margin-bottom:4px}
.gen-tpl-opt{display:flex;gap:10px;align-items:flex-start;padding:10px 12px;margin-bottom:8px;border:1.5px solid var(--border);border-radius:10px;background:var(--surface-2);cursor:pointer}
.gen-tpl-opt:hover{border-color:var(--accent)}
.gen-tpl-opt:has(input:checked){border-color:var(--accent);background:color-mix(in srgb,var(--accent) 7%,var(--surface-2))}
.gen-tpl-opt input{margin-top:3px;accent-color:var(--accent);width:16px;height:16px;flex:none}
.gen-tpl-opt span{display:flex;flex-direction:column;gap:3px}
.gen-tpl-opt b{font-size:13px}
.gen-tpl-opt em{font-style:normal;font-size:12px;line-height:1.5;color:var(--text-muted,#64748b)}
.gen-tpl-opt--sub{margin-left:26px}

/* Barre de soumission */
.gen-submit{display:flex;align-items:center;justify-content:flex-end;gap:16px;flex-wrap:wrap;margin:18px 0 22px}
.gen-submit-hint{font-size:12.5px;margin-right:auto}
</style>
<script>
(function(){
    var lic = document.getElementById('genLicense'), box = document.getElementById('genLicenseProduct');
    if (lic && box) lic.addEventListener('change', function(){ box.style.display = lic.checked ? '' : 'none'; });

    // La partie publique n'a de réglages que si elle est demandée.
    var pub = document.getElementById('genPublic'), pubBox = document.getElementById('genPublicBox');
    if (pub && pubBox) {
        pub.addEventListener('change', function () {
            pubBox.style.display = pub.checked ? '' : 'none';
            var k = document.getElementById('genThemeKey');
            if (pub.checked && k && !k.value) { k.value = 'default'; }
        });
    }

    // Pagination et recherche n'ont de sens qu'avec une liste.
    var lst = document.getElementById('genTplList'), sub = document.getElementById('genTplSub');
    if (lst && sub) {
        lst.addEventListener('change', function () {
            sub.style.display = lst.checked ? '' : 'none';
            if (!lst.checked) {
                sub.querySelectorAll('input').forEach(function (i) { i.checked = false; });
            }
        });
    }

    var count = document.getElementById('genCapsCount');
    var inputs = document.querySelectorAll('.gen-cap-input');
    function refresh(){
        var n = 0;
        inputs.forEach(function(i){ if (i.checked) n++; });
        if (count){
            count.textContent = n + ' sélectionnée' + (n > 1 ? 's' : '');
            count.classList.toggle('has', n > 0);
        }
    }
    inputs.forEach(function(i){ i.addEventListener('change', refresh); });
    refresh();
})();
</script>

<?php admin_footer(); ?>
