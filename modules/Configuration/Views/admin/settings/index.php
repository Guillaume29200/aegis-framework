<?php
/**
 * Configuration / Paramètres — UI maison (onglets, sauvegarde AJAX par section)
 * Variables : $settings[], $recaptchaConfigured, $csrfToken
 */
if (!defined('AEGIS_FRAMEWORK')) die('Access denied');

$pageTitle = $pageTitle ?? 'Configuration';
admin_header($pageTitle);

$settings = $settings ?? [];
$csrf = $csrfToken ?? '';
$h  = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$val = fn($k, $d = '') => $h($settings[$k] ?? $d);
$on  = fn($k) => !empty($settings[$k]) ? 'checked' : '';
?>

<div class="adm-page-head">
    <div class="adm-breadcrumb"><a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><span>Configuration</span></div>
    <h1>⚙️ Configuration</h1>
    <p>Réglages généraux du CMS — identité, système, sécurité, e-mails, SEO, IA et navigation.</p>
</div>

<div id="set-flash" class="set-flash"></div>

<div class="ui-card set-card">
    <div class="set-tabs">
        <button class="set-tab active" data-tab="general">🪪 Général</button>
        <button class="set-tab" data-tab="system">🧩 Système</button>
        <button class="set-tab" data-tab="security">🛡️ Sécurité</button>
        <button class="set-tab" data-tab="sessions">🔐 Sessions</button>
        <button class="set-tab" data-tab="twofa">🔑 2FA</button>
        <button class="set-tab" data-tab="domains">📧 Domaines e-mail</button>
        <button class="set-tab" data-tab="email">✉️ E-mails</button>
        <button class="set-tab" data-tab="ai">🤖 IA</button>
        <button class="set-tab" data-tab="turbonav">⚡ TurboNav</button>
    </div>

    <!-- GÉNÉRAL -->
    <div class="set-pane active" id="tab-general">
        <form class="set-form" data-url="<?= u('/admin/configuration/save-general') ?>" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>">
            <div class="set-grid">
                <div class="fld"><label class="form-label">Nom du site</label><input class="form-control" name="site_name" value="<?= $val('site_name') ?>"></div>
                <div class="fld"><label class="form-label">E-mail webmaster</label><input class="form-control" type="email" name="webmaster_email" value="<?= $val('webmaster_email') ?>"></div>
            </div>
            <div class="fld"><label class="form-label">Description du site</label><textarea class="form-control" name="site_description" rows="2"><?= $val('site_description') ?></textarea></div>

            <h4 class="set-sub">🏠 Page d'accueil par défaut</h4>
            <?php $landingOptions = $landingOptions ?? []; $defaultLanding = $defaultLanding ?? ''; ?>
            <div class="fld">
                <label class="form-label">Vers quoi rediriger quand un visiteur arrive sur la racine du site</label>
                <select class="form-control" name="default_landing">
                    <option value="auth" <?= ($defaultLanding === '' || $defaultLanding === 'auth') ? 'selected' : '' ?>>🔐 Page de connexion (Auth) — par défaut</option>
                    <?php foreach ($landingOptions as $opt): ?>
                        <?php
                        // La valeur enregistrée est canonique ; l'adresse montrée
                        // est celle réellement en vigueur si le module a été renommé.
                        $isCurrent = ($defaultLanding === $opt['value'] || $defaultLanding === $opt['url']);
                        ?>
                        <option value="<?= $h($opt['value']) ?>" <?= $isCurrent ? 'selected' : '' ?>><?= $h($opt['label']) ?> (<?= $h($opt['url']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <p class="u-muted" style="font-size:12.5px;margin:8px 0 0">
                    Détermine la <strong>page d'accueil publique</strong> du site : tout visiteur arrivant sur « / » y est redirigé.
                    La liste ne contient que les <strong>modules actifs disposant d'une interface publique</strong>.
                    Si vous renommez l'adresse publique d'un module depuis <em>Modules</em>, ce réglage suit automatiquement.
                    Choisissez <strong>« Page de connexion (Auth) »</strong> pour conserver le comportement classique (redirection vers la connexion) —
                    c'est aussi le repli automatique si aucun module public n'est disponible (Aegis fraîchement installé).
                </p>
            </div>

            <h4 class="set-sub">Visuel de la page de connexion</h4>
            <div class="set-grid">
                <div class="fld"><label class="form-label">Badge</label><input class="form-control" name="login_visual_badge" value="<?= $val('login_visual_badge') ?>"></div>
                <div class="fld"><label class="form-label">Titre</label><input class="form-control" name="login_visual_title" value="<?= $val('login_visual_title') ?>"></div>
            </div>
            <div class="fld"><label class="form-label">Texte</label><textarea class="form-control" name="login_visual_text" rows="2"><?= $val('login_visual_text') ?></textarea></div>
            <div class="set-grid">
                <div class="fld">
                    <label class="form-label">Logo de connexion</label>
                    <div class="set-drop" data-drop>
                        <?php if (!empty($settings['login_logo_image'])): ?><img src="<?= $h(u($settings['login_logo_image'])) ?>" class="set-drop-img" alt="Logo actuel"><?php else: ?><div class="set-drop-ph">🖼️</div><?php endif; ?>
                        <p class="set-drop-txt">Glissez une image ici ou <strong>cliquez</strong></p>
                        <p class="set-drop-hint">JPG, PNG, WebP · max 5 Mo</p>
                        <input type="file" name="login_logo_image" accept=".jpg,.jpeg,.png,.webp">
                    </div>
                </div>
                <div class="fld">
                    <label class="form-label">Image de couverture (fond)</label>
                    <div class="set-drop" data-drop>
                        <?php if (!empty($settings['login_cover_image'])): ?><img src="<?= $h(u($settings['login_cover_image'])) ?>" class="set-drop-img cover" alt="Couverture actuelle"><?php else: ?><div class="set-drop-ph">🌄</div><?php endif; ?>
                        <p class="set-drop-txt">Glissez une image ici ou <strong>cliquez</strong></p>
                        <p class="set-drop-hint">JPG, PNG, WebP · max 5 Mo · ~1600×1000 px</p>
                        <input type="file" name="login_cover_image" accept=".jpg,.jpeg,.png,.webp">
                    </div>
                </div>
            </div>

            <h4 class="set-sub">Visuel de la page d'inscription</h4>
            <p class="u-muted" style="font-size:12px;margin:-6px 0 12px">Laissez vide pour réutiliser automatiquement les visuels de la page de connexion ci-dessus.</p>
            <div class="set-grid">
                <div class="fld"><label class="form-label">Badge</label><input class="form-control" name="register_visual_badge" value="<?= $val('register_visual_badge') ?>"></div>
                <div class="fld"><label class="form-label">Titre</label><input class="form-control" name="register_visual_title" value="<?= $val('register_visual_title') ?>"></div>
            </div>
            <div class="fld"><label class="form-label">Texte</label><textarea class="form-control" name="register_visual_text" rows="2"><?= $val('register_visual_text') ?></textarea></div>
            <div class="set-grid">
                <div class="fld">
                    <label class="form-label">Logo d'inscription</label>
                    <div class="set-drop" data-drop>
                        <?php if (!empty($settings['register_logo_image'])): ?><img src="<?= $h(u($settings['register_logo_image'])) ?>" class="set-drop-img" alt="Logo actuel"><?php else: ?><div class="set-drop-ph">🖼️</div><?php endif; ?>
                        <p class="set-drop-txt">Glissez une image ici ou <strong>cliquez</strong></p>
                        <p class="set-drop-hint">JPG, PNG, WebP · max 5 Mo</p>
                        <input type="file" name="register_logo_image" accept=".jpg,.jpeg,.png,.webp">
                    </div>
                </div>
                <div class="fld">
                    <label class="form-label">Image de couverture (fond)</label>
                    <div class="set-drop" data-drop>
                        <?php if (!empty($settings['register_cover_image'])): ?><img src="<?= $h(u($settings['register_cover_image'])) ?>" class="set-drop-img cover" alt="Couverture actuelle"><?php else: ?><div class="set-drop-ph">🌄</div><?php endif; ?>
                        <p class="set-drop-txt">Glissez une image ici ou <strong>cliquez</strong></p>
                        <p class="set-drop-hint">JPG, PNG, WebP · max 5 Mo · ~1600×1000 px</p>
                        <input type="file" name="register_cover_image" accept=".jpg,.jpeg,.png,.webp">
                    </div>
                </div>
            </div>
            <div class="set-actions"><button class="ui-btn primary" type="submit">💾 Enregistrer</button></div>
        </form>
    </div>

    <!-- SYSTÈME -->
    <div class="set-pane" id="tab-system">
        <form class="set-form" data-url="<?= u('/admin/configuration/save-system') ?>">
            <input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>">
            <label class="set-switch-row"><span><b>🐞 Mode debug</b><small>Affiche les erreurs détaillées (à éviter en production).</small></span><span class="set-sw"><input type="checkbox" name="debug_mode" <?= $on('debug_mode') ?>><i></i></span></label>
            <label class="set-switch-row"><span><b>⚡ Cache activé</b><small>Met en cache les pages pour accélérer le site.</small></span><span class="set-sw"><input type="checkbox" name="cache_enabled" <?= $on('cache_enabled') ?>><i></i></span></label>
            <div class="fld u-mt"><label class="form-label">Durée du cache (secondes)</label><input class="form-control" type="number" name="cache_ttl" value="<?= $val('cache_ttl', '3600') ?>" min="0"></div>
            <label class="set-switch-row"><span><b>🚧 Mode maintenance</b><small>Rend le site inaccessible au public.</small></span><span class="set-sw"><input type="checkbox" name="maintenance_mode" <?= $on('maintenance_mode') ?>><i></i></span></label>
            <div class="fld u-mt"><label class="form-label">Thème de maintenance</label>
                <select class="form-select" name="maintenance_theme">
                    <?php foreach (['moderne'=>'Moderne','minimaliste'=>'Minimaliste','gaming'=>'Gaming','noel'=>'Noël','halloween'=>'Halloween'] as $k=>$lbl): ?>
                        <option value="<?= $k ?>" <?= ($settings['maintenance_theme'] ?? 'moderne') === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <label class="set-switch-row"><span><b>🖼️ Optimiser les images uploadées</b><small>Redimensionne et compresse automatiquement les images (logo, couverture, médias) à l'upload.</small></span><span class="set-sw"><input type="checkbox" name="image_optimize_enabled" <?= $on('image_optimize_enabled') ?>><i></i></span></label>
            <div class="set-grid u-mt">
                <div class="fld"><label class="form-label">Largeur max (px)</label><input class="form-control" type="number" name="image_max_width" value="<?= $val('image_max_width', '1920') ?>" min="320" max="5000"></div>
                <div class="fld"><label class="form-label">Qualité (40–100)</label><input class="form-control" type="number" name="image_quality" value="<?= $val('image_quality', '82') ?>" min="40" max="100"></div>
            </div>
            <div class="set-actions"><button class="ui-btn primary" type="submit">💾 Enregistrer</button></div>
        </form>
    </div>

    <!-- SÉCURITÉ -->
    <div class="set-pane" id="tab-security">
        <form class="set-form" data-url="<?= u('/admin/configuration/save-security') ?>">
            <input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>">
            <label class="set-switch-row"><span><b>📝 Inscriptions ouvertes</b><small>Autorise la création de comptes.</small></span><span class="set-sw"><input type="checkbox" name="registration_enabled" <?= $on('registration_enabled') ?>><i></i></span></label>
            <label class="set-switch-row"><span><b>🤖 reCAPTCHA activé</b><small>Protection anti-bots Google.</small></span><span class="set-sw"><input type="checkbox" name="recaptcha_enabled" <?= $on('recaptcha_enabled') ?>><i></i></span></label>
            <div class="set-grid u-mt">
                <div class="fld"><label class="form-label">Clé site reCAPTCHA</label><input class="form-control" name="recaptcha_site_key" value="<?= $val('recaptcha_site_key') ?>"></div>
                <div class="fld"><label class="form-label">Clé secrète reCAPTCHA</label><input class="form-control" type="password" name="recaptcha_secret_key" value="<?= $val('recaptcha_secret_key') ?>"></div>
            </div>
            <label class="set-switch-row"><span><b>reCAPTCHA sur la connexion</b></span><span class="set-sw"><input type="checkbox" name="recaptcha_login" <?= $on('recaptcha_login') ?>><i></i></span></label>
            <label class="set-switch-row"><span><b>reCAPTCHA sur l'inscription</b></span><span class="set-sw"><input type="checkbox" name="recaptcha_register" <?= $on('recaptcha_register') ?>><i></i></span></label>
            <p class="u-muted u-mt">🍪 La bannière cookies se gère sur la page <a href="<?= u('/admin/configuration/rgpd') ?>">RGPD / Cookies</a>.</p>
            <div class="set-actions"><button class="ui-btn primary" type="submit">💾 Enregistrer</button></div>
        </form>
    </div>

    <!-- SESSIONS -->
    <div class="set-pane" id="tab-sessions">
        <form class="set-form" data-url="<?= u('/admin/configuration/save-sessions') ?>">
            <input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>">

            <label class="set-switch-row"><span><b>⏲️ Déconnexion auto après inactivité</b><small>Déconnecte un utilisateur inactif (avec avertissement avant).</small></span><span class="set-sw"><input type="checkbox" name="session_idle_logout" <?= (!isset($settings['session_idle_logout']) || !empty($settings['session_idle_logout'])) ? 'checked' : '' ?>><i></i></span></label>

            <div class="set-grid u-mt">
                <div class="fld"><label class="form-label">Délai d'inactivité (minutes)</label><input class="form-control" type="number" min="1" max="1440" name="session_idle_minutes" value="<?= $val('session_idle_minutes', '120') ?>"><small class="u-muted">Durée sans activité avant déconnexion (défaut 120).</small></div>
                <div class="fld"><label class="form-label">Avertir avant (secondes)</label><input class="form-control" type="number" min="10" max="600" name="session_warn_seconds" value="<?= $val('session_warn_seconds', '60') ?>"><small class="u-muted">Compte à rebours de la modale « rester connecté ».</small></div>
            </div>

            <div class="set-grid">
                <div class="fld"><label class="form-label">🔗 Liaison de session à l'IP</label>
                    <select class="form-select" name="session_ip_binding">
                        <?php $ipb = $settings['session_ip_binding'] ?? 'subnet'; foreach (['off'=>'Désactivée (la plus tolérante)','subnet'=>'Sous-réseau (recommandé)','strict'=>'IP exacte (la plus stricte)'] as $k=>$lbl): ?>
                            <option value="<?= $k ?>" <?= $ipb === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="u-muted">« Strict » peut déconnecter si l'IP change. « Sous-réseau » évite les faux positifs (localhost, proxy).</small>
                </div>
                <div class="fld"><label class="form-label">Régénération de l'ID (minutes)</label><input class="form-control" type="number" min="1" max="240" name="session_regenerate_minutes" value="<?= $val('session_regenerate_minutes', '5') ?>"><small class="u-muted">Anti-fixation ; n'a jamais lieu pendant les requêtes AJAX.</small></div>
            </div>

            <label class="set-switch-row"><span><b>🔒 « Se souvenir de moi »</b><small>Autorise la reconnexion automatique via cookie sécurisé.</small></span><span class="set-sw"><input type="checkbox" name="session_remember_enabled" <?= (!isset($settings['session_remember_enabled']) || !empty($settings['session_remember_enabled'])) ? 'checked' : '' ?>><i></i></span></label>
            <div class="set-grid">
                <div class="fld"><label class="form-label">Durée « se souvenir » (jours)</label><input class="form-control" type="number" min="1" max="365" name="session_remember_days" value="<?= $val('session_remember_days', '30') ?>"></div>
            </div>

            <div class="set-actions" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                <button class="ui-btn primary" type="submit">💾 Enregistrer</button>
                <button class="ui-btn danger" type="button" id="btnLogoutOthers">🚪 Déconnecter mes autres sessions</button>
                <span id="logoutOthersMsg" class="u-muted" style="font-size:13px"></span>
            </div>
        </form>
    </div>

    <!-- 2FA -->
    <div class="set-pane" id="tab-twofa">
        <?php
        // L'état du transport conditionne tout : sans agent capable d'envoyer un
        // message, activer le 2FA revient à se verrouiller dehors.
        $twofaTransport = \Framework\Services\Mailer::transportDisponible();
        $twofaActif     = !empty($settings['twofa_enabled']);
        ?>

        <div class="ui-card" style="margin-bottom:16px">
            <div class="ui-card-body">
                <p style="margin:0 0 10px"><b>🔑 Qu'est-ce que la double authentification ?</b></p>
                <p class="u-muted" style="margin:0 0 10px;line-height:1.6">
                    Après avoir saisi son mot de passe, l'utilisateur reçoit <b>un code à 6 chiffres par e-mail</b>
                    et doit le saisir pour entrer. Un mot de passe volé ne suffit donc plus : il faut aussi
                    accéder à la boîte de réception. C'est la protection la plus efficace contre la
                    réutilisation d'un même mot de passe entre plusieurs sites, première cause de
                    compromission de compte.
                </p>
                <p class="u-muted" style="margin:0 0 10px;line-height:1.6">
                    Le code expire, ne sert qu'une fois, et se bloque après 5 essais.
                    <b>Aucun SMS n'est envoyé</b> — l'envoi par texto est payant et le détournement de carte SIM
                    en fait un facteur plus faible que l'e-mail.
                </p>
                <p style="margin:0;line-height:1.6;padding:10px 12px;border-radius:8px;background:var(--amber-soft,#fef3c7)">
                    🆘 <b>En cas de perte d'accès à votre boîte</b>, un <b>code de secours</b> vous est remis
                    à l'activation. Il s'utilise directement sur la page de vérification
                    (« Je n'ai plus accès à ma boîte e-mail ») et désactive la double authentification.
                    <b>Notez-le</b> : il n'est affiché qu'une fois.
                </p>
            </div>
        </div>

        <div class="ui-card" style="margin-bottom:16px;border-color:<?= $twofaTransport ? 'var(--green-soft,#bbf7d0)' : 'var(--red-soft,#fecaca)' ?>">
            <div class="ui-card-body">
                <p style="margin:0 0 10px"><b><?= $twofaTransport ? '✅' : '❌' ?> Envoi d'e-mail</b> —
                    <?= $twofaTransport
                        ? 'un agent de transport est configuré sur ce serveur.'
                        : 'aucun agent de transport n\'est configuré (directive <code>SMTP</code> sous Windows, <code>sendmail_path</code> sinon).' ?>
                </p>
                <p class="u-muted" style="margin:0 0 12px;line-height:1.6">
                    Cette vérification ne lit que la configuration de PHP : elle ne garantit pas la bonne
                    réception. <b>Faites un envoi de test avant d'activer.</b>
                </p>
                <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                    <input class="form-control" type="email" id="twofaTestEmail" placeholder="votre@adresse.fr" style="max-width:280px"
                           value="<?= $h($settings['webmaster_email'] ?? '') ?>">
                    <button class="ui-btn" type="button" id="btnTwofaTest">✉️ Envoyer un test</button>
                    <span id="twofaTestMsg" class="u-muted" style="font-size:13px"></span>
                </div>
            </div>
        </div>

        <form class="set-form" data-url="<?= u('/admin/configuration/save-twofa') ?>">
            <input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>">

            <label class="set-switch-row"><span><b>🔑 Activer la double authentification</b><small>Un code par e-mail sera demandé après le mot de passe.</small></span><span class="set-sw"><input type="checkbox" name="twofa_enabled" <?= $twofaActif ? 'checked' : '' ?>><i></i></span></label>

            <div class="set-grid u-mt">
                <div class="fld"><label class="form-label">👥 Comptes concernés</label>
                    <select class="form-select" name="twofa_scope">
                        <?php $sc = $settings['twofa_scope'] ?? 'admins'; foreach (['admins'=>'Administrateurs seulement (recommandé)','all'=>'Tous les comptes'] as $k=>$lbl): ?>
                            <option value="<?= $k ?>" <?= $sc === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="u-muted">Ce sont les comptes d'administration qui valent d'être protégés ; l'imposer à tous les membres fait chuter les connexions.</small>
                </div>
                <div class="fld"><label class="form-label">⏱️ Validité du code (minutes)</label><input class="form-control" type="number" min="2" max="60" name="twofa_ttl_minutes" value="<?= $val('twofa_ttl_minutes', '10') ?>"><small class="u-muted">Au-delà, le code est refusé. 10 minutes couvrent le délai d'acheminement d'un e-mail.</small></div>
            </div>

            <div class="set-actions"><button class="ui-btn primary" type="submit">💾 Enregistrer</button></div>
        </form>

        <?php $twofaRecovery = !empty($settings['twofa_recovery_hash']); ?>
        <div class="ui-card u-mt">
            <div class="ui-card-body">
                <p style="margin:0 0 8px"><b>🆘 Code de secours</b> —
                    <?= $twofaRecovery ? 'un code est en place.' : 'aucun code pour l\'instant ; il sera généré à l\'activation.' ?>
                </p>
                <p class="u-muted" style="margin:0 0 12px;line-height:1.6">
                    Il s'utilise depuis la page de vérification et désactive la double authentification.
                    Seule son empreinte est conservée : <b>il ne peut pas être réaffiché</b>.
                    Si vous l'avez égaré, générez-en un nouveau — l'ancien cessera aussitôt de fonctionner.
                </p>
                <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                    <button class="ui-btn" type="button" id="btnTwofaRecovery">🔄 Générer un nouveau code</button>
                    <span id="twofaRecoveryMsg" class="u-muted" style="font-size:13px"></span>
                </div>
                <p id="twofaRecoveryOut" hidden style="margin:12px 0 0;padding:14px;border-radius:8px;background:var(--amber-soft,#fef3c7);text-align:center;font-family:ui-monospace,Consolas,monospace;font-size:20px;font-weight:700;letter-spacing:.12em"></p>
            </div>
        </div>
    </div>

    <!-- DOMAINES E-MAIL -->
    <div class="set-pane" id="tab-domains">
        <?php
        $domPolitique = \Framework\Security\EmailDomainPolicy::depuisBase($GLOBALS['db'] ?? null);
        $domListe     = (string)($settings['email_domains_allowed'] ?? '');
        if (trim($domListe) === '') {
            $domListe = implode("\n", \Framework\Security\EmailDomainPolicy::DEFAUTS);
        }
        $domActif = !empty($settings['email_domains_enabled']);
        ?>

        <div class="ui-card" style="margin-bottom:16px">
            <div class="ui-card-body">
                <p style="margin:0 0 10px"><b>📧 Pourquoi une liste blanche plutôt qu'une liste noire ?</b></p>
                <p class="u-muted" style="margin:0 0 10px;line-height:1.6">
                    Interdire les fournisseurs d'adresses jetables suppose de tous les connaître.
                    Il en naît chaque semaine : la liste grossit sans fin et devient périmée le jour
                    où on la fige. <b>Une liste blanche renverse la charge</b> — une trentaine de domaines
                    couvrent la quasi-totalité des inscrits réels, et tout ce qui apparaîtra demain
                    est refusé sans qu'on ait à le savoir.
                </p>
                <p style="margin:0;line-height:1.6;padding:10px 12px;border-radius:8px;background:var(--amber-soft,#fef3c7)">
                    ⚠️ <b>Le revers :</b> une adresse sur un domaine propre — <code>contact@votre-studio.com</code> —
                    est refusée elle aussi. <b>Ajoutez vos domaines à la liste ci-dessous.</b>
                    Les sous-domaines d'un domaine autorisé passent automatiquement.
                    Et vous pouvez toujours créer un compte à la main : ce filtre ne concerne
                    que l'inscription publique.
                </p>
            </div>
        </div>

        <form class="set-form" data-url="<?= u('/admin/configuration/save-domains') ?>">
            <input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>">

            <label class="set-switch-row"><span><b>📧 Filtrer les domaines à l'inscription</b><small>Seules les adresses des domaines listés seront acceptées.</small></span><span class="set-sw"><input type="checkbox" name="email_domains_enabled" <?= $domActif ? 'checked' : '' ?>><i></i></span></label>

            <div class="fld u-mt">
                <label class="form-label">Domaines autorisés</label>
                <textarea class="form-control" name="email_domains_allowed" rows="12" spellcheck="false" style="font-family:ui-monospace,Consolas,monospace;font-size:13px"><?= $h($domListe) ?></textarea>
                <small class="u-muted">
                    Un domaine par ligne (les virgules et espaces sont acceptés aussi).
                    Coller une adresse complète fonctionne : seul le domaine est retenu.
                    <b><?= count($domPolitique->autorises()) ?: count(\Framework\Security\EmailDomainPolicy::DEFAUTS) ?></b> domaine(s) en vigueur.
                </small>
            </div>

            <div class="fld">
                <label class="form-label">🧪 Tester une adresse</label>
                <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                    <input class="form-control" type="email" id="domTestEmail" placeholder="quelquun@exemple.fr" style="max-width:280px">
                    <button class="ui-btn" type="button" id="btnDomTest">Vérifier</button>
                    <span id="domTestMsg" class="u-muted" style="font-size:13px"></span>
                </div>
                <small class="u-muted">Le test porte sur les réglages <b>enregistrés</b>, pas sur la saisie en cours.</small>
            </div>

            <div class="set-actions"><button class="ui-btn primary" type="submit">💾 Enregistrer</button></div>
        </form>
    </div>

    <!-- E-MAILS -->
    <div class="set-pane" id="tab-email">
        <form class="set-form" data-url="<?= u('/admin/configuration/save-email') ?>">
            <input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>">
            <div class="set-grid">
                <div class="fld"><label class="form-label">E-mail expéditeur (reset)</label><input class="form-control" type="email" name="password_reset_from_email" value="<?= $val('password_reset_from_email') ?>"></div>
                <div class="fld"><label class="form-label">Nom expéditeur</label><input class="form-control" name="password_reset_from_name" value="<?= $val('password_reset_from_name') ?>"></div>
            </div>
            <div class="fld"><label class="form-label">Sujet de l'e-mail de réinitialisation</label><input class="form-control" name="password_reset_email_subject" value="<?= $val('password_reset_email_subject') ?>"></div>
            <div class="fld"><label class="form-label">Corps de l'e-mail</label><textarea class="form-control" name="password_reset_email_body" rows="6"><?= $val('password_reset_email_body') ?></textarea></div>
            <div class="set-actions"><button class="ui-btn primary" type="submit">💾 Enregistrer</button></div>
        </form>
    </div>

    <!-- IA -->
    <div class="set-pane" id="tab-ai">
        <?php $aiProviderLabels = ['openai' => 'OpenAI', 'claude' => 'Claude', 'mistral' => 'Mistral', 'gemini' => 'Gemini', 'groq' => 'Groq', 'ollama' => 'Ollama (local)']; ?>
        <?php if (empty($aiProviderHasActiveModels)): ?>
            <div class="ui-card" style="border-color:var(--red-soft);margin-bottom:16px">
                <div class="ui-card-body" style="color:var(--red)">
                    ❌ Aucun modèle actif pour <strong><?= $h($aiProviderLabels[$aiDefaultProvider] ?? $aiDefaultProvider) ?></strong>, votre IA par défaut. Les appels IA échoueront tant qu'aucun modèle n'est activé pour ce provider.
                    <a href="<?= u('/admin/configuration/ai-models') ?>">Gérer les modèles IA →</a>
                </div>
            </div>
        <?php elseif (empty($aiProviderHasDefaultModel)): ?>
            <div class="ui-card" style="border-color:var(--amber-soft);margin-bottom:16px">
                <div class="ui-card-body" style="color:var(--amber)">
                    ⚠️ Aucun modèle par défaut défini pour <strong><?= $h($aiProviderLabels[$aiDefaultProvider] ?? $aiDefaultProvider) ?></strong>. Un modèle sera choisi arbitrairement (ordre alphabétique) tant que vous n'en définissez pas un explicitement.
                    <a href="<?= u('/admin/configuration/ai-models') ?>">Définir un modèle par défaut →</a>
                </div>
            </div>
        <?php endif; ?>
        <form class="set-form" data-url="<?= u('/admin/configuration/save-ai') ?>">
            <input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>">
            <p class="u-muted">Clés API utilisées par le module IA. Gérez les modèles sur la page <a href="<?= u('/admin/configuration/ai-models') ?>">Modèles IA</a>.</p>
            <?php $aiKeyPh = fn($k, $ph) => empty($settings[$k]) ? $ph : '🔒 Clé enregistrée — laisser vide pour conserver'; ?>
            <div class="set-grid">
                <div class="fld"><label class="form-label">🤖 Clé OpenAI</label><input class="form-control" type="password" name="openai_api_key" value="" placeholder="<?= $h($aiKeyPh('openai_api_key', 'sk-…')) ?>" autocomplete="off"></div>
                <div class="fld"><label class="form-label">🧠 Clé Claude</label><input class="form-control" type="password" name="claude_api_key" value="" placeholder="<?= $h($aiKeyPh('claude_api_key', 'sk-ant-…')) ?>" autocomplete="off"></div>
                <div class="fld"><label class="form-label">🌬️ Clé Mistral</label><input class="form-control" type="password" name="mistral_api_key" value="" placeholder="<?= $h($aiKeyPh('mistral_api_key', '')) ?>" autocomplete="off"></div>
                <div class="fld"><label class="form-label">💎 Clé Gemini</label><input class="form-control" type="password" name="gemini_api_key" value="" placeholder="<?= $h($aiKeyPh('gemini_api_key', 'AIza…')) ?>" autocomplete="off"></div>
                <div class="fld"><label class="form-label">⚡ Clé Groq</label><input class="form-control" type="password" name="groq_api_key" value="" placeholder="<?= $h($aiKeyPh('groq_api_key', 'gsk_…')) ?>" autocomplete="off"></div>
                <div class="fld"><label class="form-label">Provider par défaut</label>
                    <select class="form-select" name="default_ai_provider">
                        <?php foreach ($aiProviderLabels as $k=>$lbl): ?>
                            <option value="<?= $k ?>" <?= ($settings['default_ai_provider'] ?? 'openai') === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="fld u-mt">
                <label class="form-label">🦙 Serveur Ollama (local)</label>
                <input class="form-control" type="text" name="ollama_base_url" value="<?= $val('ollama_base_url') ?>" placeholder="http://localhost:11434">
                <p class="form-text">Pas de clé API — Ollama tourne en local sans authentification. Laisser vide pour utiliser <code>http://localhost:11434</code> par défaut. Installez Ollama et téléchargez un modèle (<code>ollama pull llama3.2</code>) avant de l'ajouter dans <a href="<?= u('/admin/configuration/ai-models') ?>">Modèles IA</a>.</p>
            </div>
            <div class="set-actions"><button class="ui-btn primary" type="submit">💾 Enregistrer</button></div>
        </form>
    </div>

    <!-- TURBONAV -->
    <div class="set-pane" id="tab-turbonav">
        <div class="ui-card" style="border:0;background:linear-gradient(135deg,var(--accent-soft),transparent);margin-bottom:18px">
            <div class="ui-card-body">
                <h3 style="margin:0 0 6px;font-size:18px">⚡ TurboNav — navigation instantanée</h3>
                <p class="u-muted" style="margin:0">Transforme le CMS en application web à navigation instantanée, <strong>sans réécrire une ligne de votre code</strong> : les pages sont chargées en arrière-plan et le contenu est échangé sans rechargement complet.</p>
            </div>
        </div>

        <div class="ui-grid cols-3" style="margin-bottom:18px">
            <div class="ui-card"><div class="ui-card-body"><div style="font-size:22px">🚀</div><b>Plus rapide</b><p class="u-muted" style="margin:4px 0 0;font-size:13px">Seul le contenu change : header, sidebar et scripts ne sont pas rechargés.</p></div></div>
            <div class="ui-card"><div class="ui-card-body"><div style="font-size:22px">🧩</div><b>Zéro dépendance</b><p class="u-muted" style="margin:4px 0 0;font-size:13px">Vanilla JS, aucun framework. Se désactive à tout moment sans impact.</p></div></div>
            <div class="ui-card"><div class="ui-card-body"><div style="font-size:22px">🔄</div><b>Transparent</b><p class="u-muted" style="margin:4px 0 0;font-size:13px">Compatible avec vos liens existants. Les liens <code>data-no-turbonav</code> forcent un rechargement complet (ex: déconnexion).</p></div></div>
        </div>

        <form class="set-form" data-url="<?= u('/admin/configuration/save-turbonav') ?>">
            <input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>">
            <label class="set-switch-row">
                <span><b>⚡ Activer TurboNav</b><small>Navigation AJAX sur toutes les pages d'administration.</small></span>
                <span class="set-sw"><input type="checkbox" name="turbonav_enabled" <?= $on('turbonav_enabled') ?>><i></i></span>
            </label>
            <p class="u-muted u-mt" style="font-size:13px">
                <?= !empty($settings['turbonav_enabled'])
                    ? '✅ TurboNav est actuellement <strong>ACTIF</strong> — la navigation AJAX est activée.'
                    : '⏸️ TurboNav est actuellement <strong>désactivé</strong> — navigation classique avec rechargement complet.' ?>
            </p>
            <div class="set-actions"><button class="ui-btn primary" type="submit">💾 Enregistrer</button></div>
        </form>
    </div>
</div>

<style>
.set-card { padding: 0; overflow: hidden; }
.set-tabs { display: flex; gap: 0; overflow-x: auto; border-bottom: 1px solid var(--border); }
.set-tab { padding: 14px 18px; border: 0; background: transparent; color: var(--text-soft); font-weight: 600; white-space: nowrap; cursor: pointer; font-family: inherit; font-size: 13.5px; }
.set-tab:hover { background: var(--surface-2); color: var(--text); }
.set-tab.active { color: var(--accent); box-shadow: inset 0 -3px 0 var(--accent); }
.set-pane { display: none; padding: 22px; }
.set-pane.active { display: block; }
.set-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 620px) { .set-grid { grid-template-columns: 1fr; } }
.fld { margin-bottom: 16px; }
.set-sub { margin: 8px 0 14px; font-size: 14px; color: var(--text-soft); border-top: 1px solid var(--border); padding-top: 16px; }
.set-actions { margin-top: 10px; display: flex; justify-content: flex-end; }
.set-switch-row { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 12px 0; border-bottom: 1px solid var(--border); }
.set-switch-row span b { display: block; font-size: 14px; }
.set-switch-row span small { color: var(--text-faint); font-size: 12.5px; }
.set-sw { position: relative; width: 48px; height: 27px; flex: 0 0 48px; }
.set-sw input { opacity: 0; width: 0; height: 0; }
.set-sw i { position: absolute; inset: 0; background: var(--surface-3); border: 1px solid var(--border-strong); border-radius: 30px; transition: .2s; }
.set-sw i::before { content: ""; position: absolute; width: 20px; height: 20px; left: 3px; top: 2.5px; background: #fff; border-radius: 50%; transition: .2s; box-shadow: 0 1px 3px rgba(0,0,0,.3); }
.set-sw input:checked + i { background: var(--accent); border-color: var(--accent); }
.set-sw input:checked + i::before { transform: translateX(21px); }
.set-flash { position: sticky; top: 12px; z-index: 20; }
.set-drop { position: relative; border: 2px dashed var(--border-strong); border-radius: var(--radius); padding: 18px; text-align: center; cursor: pointer; transition: border-color .15s, background .15s; background: var(--surface-2); }
.set-drop.drag { border-color: var(--accent); background: var(--accent-soft); }
.set-drop input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
.set-drop-img { max-height: 80px; max-width: 100%; object-fit: contain; border-radius: 8px; margin: 0 auto 8px; display: block; }
.set-drop-img.cover { height: 80px; width: 100%; object-fit: cover; }
.set-drop-ph { font-size: 34px; opacity: .4; margin-bottom: 6px; }
.set-drop-txt { margin: 0; font-size: 13px; color: var(--text-soft); }
.set-drop-hint { margin: 4px 0 0; font-size: 11.5px; color: var(--text-faint); }
</style>

<script>
(function () {
    // Glisser-déposer pour les images (logo / couverture)
    function preview(zone, input) {
        if (!input.files || !input.files[0]) return;
        var img = zone.querySelector('.set-drop-img');
        if (!img) { img = document.createElement('img'); img.className = 'set-drop-img'; zone.insertBefore(img, zone.firstChild); var ph = zone.querySelector('.set-drop-ph'); if (ph) ph.remove(); }
        img.src = URL.createObjectURL(input.files[0]);
    }
    document.querySelectorAll('[data-drop]').forEach(function (zone) {
        var input = zone.querySelector('input[type=file]');
        ['dragover', 'dragenter'].forEach(ev => zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.add('drag'); }));
        ['dragleave', 'dragend'].forEach(ev => zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.remove('drag'); }));
        zone.addEventListener('drop', function (e) { e.preventDefault(); zone.classList.remove('drag'); if (e.dataTransfer.files.length) { input.files = e.dataTransfer.files; preview(zone, input); } });
        input.addEventListener('change', function () { preview(zone, input); });
    });

    document.querySelectorAll('.set-tab').forEach(function (b) {
        b.addEventListener('click', function () {
            document.querySelectorAll('.set-tab').forEach(x => x.classList.remove('active'));
            document.querySelectorAll('.set-pane').forEach(x => x.classList.remove('active'));
            b.classList.add('active');
            document.getElementById('tab-' + b.dataset.tab).classList.add('active');
        });
    });
    var flash = document.getElementById('set-flash');
    function showFlash(ok, msg) {
        flash.innerHTML = '<div class="ui-card" style="border-color:var(--' + (ok ? 'green' : 'red') + '-soft);margin-bottom:14px"><div class="ui-card-body" style="color:var(--' + (ok ? 'green' : 'red') + ')">' + (ok ? '✅ ' : '❌ ') + msg + '</div></div>';
        window.scrollTo({ top: 0, behavior: 'smooth' });
        clearTimeout(window.__setFlashT); window.__setFlashT = setTimeout(function () { flash.innerHTML = ''; }, 5000);
    }
    document.querySelectorAll('.set-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = form.querySelector('button[type="submit"]'), label = btn.textContent;
            btn.disabled = true; btn.textContent = '⏳ Enregistrement…';
            fetch(form.dataset.url, { method: 'POST', body: new FormData(form) })
                .then(r => r.json())
                .then(function (d) {
                    showFlash(!!d.success, d.message || (d.success ? 'Enregistré.' : 'Erreur.'));
                    // Certaines sections renvoient plus qu'un message : le 2FA
                    // remet un code de secours à l'activation, qu'il faut
                    // afficher immédiatement — il ne le sera plus jamais.
                    document.dispatchEvent(new CustomEvent('aegis:settings-saved', { detail: d }));
                })
                .catch(function (err) { showFlash(false, '' + err); })
                .finally(function () { btn.disabled = false; btn.textContent = label; });
        });
    });

    // Déconnecter les autres sessions du compte
    var btnLO = document.getElementById('btnLogoutOthers');
    if (btnLO) btnLO.addEventListener('click', function () {
        if (!confirm('Fermer toutes vos AUTRES sessions (autres navigateurs/appareils) ? Vous resterez connecté ici.')) return;
        var msg = document.getElementById('logoutOthersMsg');
        btnLO.disabled = true; msg.textContent = '⏳ …';
        var csrf = document.querySelector('#tab-sessions input[name="csrf_token"]').value;
        fetch('<?= u('/auth/session/logout-others') ?>', {
            method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: 'csrf_token=' + encodeURIComponent(csrf)
        }).then(r => r.json()).then(function (d) {
            btnLO.disabled = false;
            msg.textContent = d.success ? ('✅ ' + (d.closed || 0) + ' session(s) fermée(s).') : ('❌ ' + (d.message || 'Erreur'));
        }).catch(function () { btnLO.disabled = false; msg.textContent = '❌ Erreur réseau'; });
    });

    // Envoi de test — à faire AVANT d'activer le 2FA : c'est le seul moyen de
    // savoir si les codes partiront réellement.
    var btnTest = document.getElementById('btnTwofaTest');
    if (btnTest) btnTest.addEventListener('click', function () {
        var msg = document.getElementById('twofaTestMsg');
        var dest = (document.getElementById('twofaTestEmail').value || '').trim();
        if (!dest) { msg.textContent = '❌ Indiquez une adresse de destination.'; return; }

        btnTest.disabled = true; msg.textContent = '⏳ Envoi…';
        var csrf = document.querySelector('#tab-twofa input[name="csrf_token"]').value;
        fetch('<?= u('/admin/configuration/twofa-test') ?>', {
            method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: 'csrf_token=' + encodeURIComponent(csrf) + '&email=' + encodeURIComponent(dest)
        }).then(r => r.json()).then(function (d) {
            btnTest.disabled = false;
            msg.textContent = (d.success ? '✅ ' : '❌ ') + (d.message || '');
        }).catch(function () { btnTest.disabled = false; msg.textContent = '❌ Erreur réseau'; });
    });

    // Affiche un code de secours fraîchement produit. C'est le seul moment où
    // il est lisible : seule son empreinte est conservée.
    function montrerCodeDeSecours(code) {
        var out = document.getElementById('twofaRecoveryOut');
        if (!out || !code) { return; }
        out.textContent = code;
        out.hidden = false;
        out.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    var btnRec = document.getElementById('btnTwofaRecovery');
    if (btnRec) btnRec.addEventListener('click', function () {
        if (!confirm("Générer un nouveau code de secours ?\n\nL'ancien cessera immédiatement de fonctionner.")) return;
        var msg = document.getElementById('twofaRecoveryMsg');
        btnRec.disabled = true; msg.textContent = '⏳ …';
        var csrf = document.querySelector('#tab-twofa input[name="csrf_token"]').value;
        fetch('<?= u('/admin/configuration/twofa-recovery') ?>', {
            method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: 'csrf_token=' + encodeURIComponent(csrf)
        }).then(r => r.json()).then(function (d) {
            btnRec.disabled = false;
            msg.textContent = (d.success ? '✅ ' : '❌ ') + (d.message || '');
            if (d.recovery) { montrerCodeDeSecours(d.recovery); }
        }).catch(function () { btnRec.disabled = false; msg.textContent = '❌ Erreur réseau'; });
    });

    // Éprouver une adresse contre la liste enregistrée.
    var btnDom = document.getElementById('btnDomTest');
    if (btnDom) btnDom.addEventListener('click', function () {
        var msg = document.getElementById('domTestMsg');
        var adr = (document.getElementById('domTestEmail').value || '').trim();
        if (!adr) { msg.textContent = '❌ Saisissez une adresse.'; return; }

        btnDom.disabled = true; msg.textContent = '⏳ …';
        var csrf = document.querySelector('#tab-domains input[name="csrf_token"]').value;
        fetch('<?= u('/admin/configuration/domain-test') ?>', {
            method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: 'csrf_token=' + encodeURIComponent(csrf) + '&email=' + encodeURIComponent(adr)
        }).then(r => r.json()).then(function (d) {
            btnDom.disabled = false;
            msg.textContent = (d.success ? '✅ ' : '⛔ ') + (d.message || '');
        }).catch(function () { btnDom.disabled = false; msg.textContent = '❌ Erreur réseau'; });
    });

    // L'enregistrement du formulaire 2FA peut lui aussi renvoyer un code, quand
    // l'activation vient d'en produire un.
    document.addEventListener('aegis:settings-saved', function (e) {
        if (e.detail && e.detail.recovery) { montrerCodeDeSecours(e.detail.recovery); }
    });
})();
</script>

<?php admin_footer(); ?>
