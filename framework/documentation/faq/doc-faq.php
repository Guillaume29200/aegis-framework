<?php
/**
 * documentation/doc-faq.php — Foire aux questions de l'administration Aegis Framework.
 * Deux niveaux : 🟢 Débutant (réponse courte) · 🔵 Avancé (technique).
 */
$docPage = 'faq/doc-faq.php';
$seo = [
    'title'     => 'FAQ — Administration Aegis Framework',
    'desc'      => "Foire aux questions de l'administration d'Aegis Framework, deux niveaux (Débutant / Avancé) : configuration, sécurité, sessions, modules, utilisateurs, RGPD, SEO, versions et licences.",
    'canonical' => 'https://gamenodepanel.com/documentation/doc-faq.php',
];
require __DIR__ . '/../inc/head.php';

/* Données FAQ : thèmes → questions [lvl: d=débutant, a=avancé]. */
$faq = [
  ['id'=>'faq-demarrage','icon'=>'🚪','title'=>'Démarrage & connexion','q'=>[
    ['d','Comment accéder à l\'administration ?','Rendez-vous sur <code>/auth/login</code> et connectez-vous avec le compte administrateur créé pendant l\'installation. Le back-office est ensuite accessible via <code>/admin</code>.'],
    ['d','J\'ai oublié mon mot de passe, que faire ?','Cliquez sur <strong>« Mot de passe oublié ? »</strong> sous le formulaire de connexion : un e-mail de réinitialisation vous est envoyé (si l\'envoi d\'e-mails est configuré).'],
    ['d','À quoi sert « Se souvenir de moi » ?','À rester connecté après fermeture du navigateur, via un cookie sécurisé. Vous pouvez le désactiver globalement dans <strong>Configuration → Sessions</strong> (la case disparaît alors du formulaire).'],
    ['a','Comment les mots de passe sont-ils stockés ?','Hachés en <strong>Argon2id</strong> (jamais en clair, jamais réversibles). Le login est en plus protégé par un <strong>rate-limiting</strong> par compte et par IP (5 tentatives / 5 min → blocage 15 min).'],
  ]],
  ['id'=>'faq-general','icon'=>'🔧','title'=>'Paramètres généraux','q'=>[
    ['d','Comment changer le nom du site ?','<strong>Configuration → Paramètres généraux</strong>, champ « Nom du site », puis Enregistrer.'],
    ['d','Comment activer le mode maintenance ?','Dans Paramètres généraux, activez l\'interrupteur « Mode maintenance ». Le site public affiche une page de maintenance ; les administrateurs continuent d\'accéder normalement (un bandeau rouge le rappelle en haut de l\'admin).'],
    ['d','Le cache, ça sert à quoi et comment l\'activer ?','Il accélère le site en mémorisant certains calculs. Activez-le dans Paramètres généraux (avec une durée de vie / TTL). En cas de comportement bizarre après une modif, désactivez-le puis réactivez-le.'],
    ['a','Où sont stockés les réglages ?','Dans la table <code>settings</code> (<code>param_key</code> / <code>param_value</code> / <code>param_type</code>), via <code>SettingsService</code> (cast automatique bool/int/string). Pas de fichier de config à éditer pour les réglages applicatifs.'],
    ['a','À quoi sert le « Mode debug » exactement ?','C\'est l\'<strong>interrupteur maître</strong> : activé, il force l\'affichage des erreurs PHP et l\'injection de la debug bar, <em>quel que soit l\'environnement</em>. À laisser <strong>désactivé en production</strong>.'],
  ]],
  ['id'=>'faq-apparence','icon'=>'🎨','title'=>'Apparence & TurboNav','q'=>[
    ['d','Comment passer l\'admin en thème sombre ?','Ouvrez le <strong>menu utilisateur</strong> (en haut à droite) → panneau d\'apparence → thème <strong>Clair / Sombre / Auto</strong>. La préférence est mémorisée sur votre navigateur.'],
    ['d','Puis-je changer la couleur ou la disposition du menu ?','Oui, dans le même panneau d\'apparence : couleur d\'accent, et disposition <strong>barre latérale</strong> ou <strong>barre horizontale</strong> (mega-menu).'],
    ['a','Qu\'est-ce que TurboNav ?','Une navigation type <strong>SPA</strong> maison (vanilla JS, zéro dépendance) qui remplace la zone <code>#admin-content</code> en AJAX au lieu de recharger la page. Activable dans <strong>Configuration → TurboNav</strong>. Détails complets dans <a href="framework/doc-turbonav.php">la doc TurboNav</a>.'],
    ['a','Mon JavaScript ne se relance plus après une navigation, pourquoi ?','Parce que le document n\'est pas rechargé. Réinitialisez votre code sur l\'événement <code>turbonav:after-swap</code> (et nettoyez vos timers sur <code>turbonav:before-swap</code>). Voir <a href="framework/doc-turbonav.php#tn-js">Écrire du JS compatible</a>.'],
  ]],
  ['id'=>'faq-sessions','icon'=>'🔐','title'=>'Sessions','q'=>[
    ['d','Pourquoi suis-je déconnecté tout seul ?','Soit par le <strong>délai d\'inactivité</strong> (réglable), soit, avant la correction récente, par un faux positif en local (bascule IPv4/IPv6 de <code>localhost</code>). Réglez le délai dans <strong>Configuration → Sessions</strong>.'],
    ['d','Comment régler le temps avant déconnexion automatique ?','<strong>Configuration → Sessions</strong> → « Délai d\'inactivité » (en minutes). Une fenêtre d\'avertissement prévient avant la déconnexion, avec un bouton « Rester connecté ».'],
    ['d','Comment déconnecter mes autres appareils/sessions ?','Dans <strong>Configuration → Sessions</strong>, bouton « Déconnecter les autres sessions » : seule votre session courante reste active.'],
    ['a','Comment fonctionne la « liaison IP » d\'une session ?','Trois modes : <code>off</code> (aucune vérif), <code>subnet</code> (tolère un changement dans le même /24 — défaut) et <code>strict</code> (IP exacte). Les adresses de bouclage <code>127.0.0.1</code> et <code>::1</code> sont traitées comme équivalentes pour éviter les déconnexions en local.'],
    ['a','Où sont définis les réglages de session ?','Dans la table <code>settings</code> (clés <code>session_*</code>). Au démarrage, <code>bootstrap.php</code> surcharge <code>securityConfig[\'session\']</code> (<code>gc_maxlifetime</code>, <code>regenerate_interval</code>, <code>ip_binding</code>) avant de construire le <code>SessionManager</code>.'],
  ]],
  ['id'=>'faq-securite','icon'=>'🛡️','title'=>'Sécurité','q'=>[
    ['d','Où voir les attaques détectées/bloquées ?','<strong>Configuration → Centre de sécurité</strong> → onglet Tableau de bord : événements par catégorie, top IP par score de menace.'],
    ['d','Une IP légitime est bloquée, comment la débloquer ?','Centre de sécurité → onglet <strong>Listes IP</strong> : ajoutez-la en <strong>liste blanche</strong> (elle est débloquée immédiatement et ne sera plus jamais bloquée).'],
    ['a','Comment fonctionne le score de menace et le blocage auto ?','Chaque IP accumule un score selon les détecteurs déclenchés (27 règles, par gravité). Seuils par défaut : <strong>100 pts → blocage 24 h</strong>, <strong>300 pts → blocage permanent</strong> (configurables).'],
    ['a','Le firewall inspecte-t-il le contenu des formulaires ?','Non. Il analyse la <strong>surface URL</strong> (chemin + query string + User-Agent), <strong>jamais les corps POST</strong>, pour éviter les faux positifs sur du contenu légitime (forum, réglages…).'],
    ['a','Comment est gérée la protection CSRF ?','Garde globale dans le routeur : tout <code>POST/PUT/PATCH/DELETE</code> exige un jeton valide (déni par défaut), avec une liste d\'exceptions configurable (webhooks, API publiques signées).'],
  ]],
  ['id'=>'faq-rgpd','icon'=>'🍪','title'=>'RGPD / cookies','q'=>[
    ['d','Comment afficher / personnaliser la bannière cookies ?','<strong>Configuration → RGPD / Cookies</strong> : activez la bannière, modifiez titre, texte, position, couleurs et catégories.'],
    ['a','Comment redemander le consentement à tous les visiteurs ?','Bouton <strong>« Réinitialiser les consentements »</strong> : le consentement est versionné, donc la bannière réapparaît pour tout le monde après réinitialisation.'],
  ]],
  ['id'=>'faq-seo','icon'=>'🔍','title'=>'SEO & médias','q'=>[
    ['d','Comment changer le logo et le favicon ?','<strong>Configuration → SEO & médias</strong> : uploads du logo, favicon et image Open Graph (avec aperçu).'],
    ['d','Comment générer un sitemap ?','Sur la même page, bouton <strong>« Générer / régénérer »</strong> le sitemap. Le <code>robots.txt</code> est mis à jour en conséquence.'],
    ['a','Comment empêcher l\'indexation du site par Google ?','Réglez la directive <strong>robots</strong> sur <code>noindex</code> dans SEO & médias : le <code>robots.txt</code> bloque alors l\'ensemble du site.'],
  ]],
  ['id'=>'faq-ia','icon'=>'🤖','title'=>'Modèles IA','q'=>[
    ['d','Comment ajouter ma clé d\'API IA ?','<strong>Configuration → Modèles IA</strong> : renseignez la clé du provider et activez les modèles souhaités.'],
    ['a','Quels fournisseurs sont supportés ?','OpenAI, Claude (Anthropic) et Mistral, avec un catalogue de modèles activables et un modèle par défaut. Les capacités (vision, code, audio…) sont déclarées par modèle.'],
  ]],
  ['id'=>'faq-users','icon'=>'👤','title'=>'Utilisateurs & rôles','q'=>[
    ['d','Comment créer ou modifier un utilisateur ?','Menu <strong>Utilisateurs</strong> (<code>/admin/users</code>) : liste, fiche, création et édition.'],
    ['d','Comment changer le rôle d\'un compte ?','Depuis la fiche de l\'utilisateur, sélectionnez son rôle puis enregistrez.'],
    ['a','Quels sont les rôles et leurs droits ?','<strong>Staff</strong> : <code>admin</code>, <code>superadmin</code>, <code>moderator</code> (accès à <code>/admin</code>, contrôlé par une garde globale dans le front controller). <strong>Membre</strong> : accès à l\'espace client uniquement. Les liens et routes sont filtrés selon le rôle.'],
  ]],
  ['id'=>'faq-modules','icon'=>'🧩','title'=>'Modules','q'=>[
    ['d','Comment activer ou désactiver un module ?','<strong>Configuration → Modules</strong> : interrupteur par module. Les modules cœur (Auth, Configuration, System) ne sont pas désactivables.'],
    ['d','Comment installer un module reçu en .zip ?','Page Modules → bouton <strong>« ⬆️ Installer un module (.zip) »</strong>. Le module est extrait et placé dans <code>modules/</code>, puis vous l\'activez.'],
    ['d','Comment créer mon propre module ?','Page Modules → <strong>« 🪄 Générer un module »</strong> (<code>/admin/modules/generate</code>) : un squelette complet et activable est créé à partir d\'un formulaire.'],
    ['d','Comment supprimer un module ?','Bouton 🗑️ sur la carte du module (saisie du nom pour confirmer). Les modules cœur sont refusés.'],
    ['d','Mon module peut-il avoir un site visible par les visiteurs ?','Oui. Dans le générateur, bloc <strong>« 🖼️ Moteur de templates »</strong>, répondez oui à « Ce module disposera-t-il d\'une partie publique ? ». Vous obtenez un thème complet, son administration (thème actif, téléversement de thèmes, options) et les pages visiteur. Voir <a href="framework/doc-templating.php">Moteur de templates</a>.'],
    ['d','J\'ai coché RGPD / Cookies mais le bandeau ne s\'affiche pas','Le bandeau est posé par la variable <code>{{{ body_end }}}</code> du gabarit, avec <strong>trois</strong> accolades. Si votre thème est écrit à la main et ne la contient pas, rien ne s\'affichera. Les thèmes générés la portent déjà. Même chose pour <code>{{{ head_extra }}}</code> côté SEO et Analytics.'],
    ['d','J\'ai coché TurboNav mais rien ne change','Normal : le script est bien posé, mais il reste en veille tant que TurboNav n\'est pas activé dans <strong>Administration → Configuration</strong>. Ce choix appartient à l\'administrateur, pas au module.'],
    ['a','Où sont rangés les thèmes d\'un module ?','Dans <code>modules/MonModule/themes/&lt;clé&gt;/</code>, avec obligatoirement <code>meta.json</code> et un dossier <code>assets/</code> contenant <code>css</code>, <code>js</code>, <code>images</code> et <code>uploads</code>. Cette structure est créée d\'office à la génération et vérifiée à l\'installation d\'un thème par ZIP.'],
    ['a','Puis-je mettre du PHP dans un thème ?','Non, et c\'est délibéré : un thème est du contenu téléversé. La syntaxe ne sait ni comparer, ni calculer, ni appeler une fonction — installer un thème ne peut donc pas exécuter de code. Tout ce qui relève d\'une décision se prépare côté PHP et arrive dans les données.'],
    ['a','Que se passe-t-il techniquement à l\'activation ?','Activation <strong>atomique</strong> : exécution du schéma SQL → vérification que toutes les tables déclarées existent réellement → hook <code>install()</code>. À la moindre erreur, rien n\'est activé et les tables partielles sont nettoyées (pas de « menu actif sans tables »).'],
    ['a','Comment mettre à jour un module sans casser son schéma ?','Via les <strong>migrations versionnées</strong> : <code>database/migrations/*.sql</code> appliquées dans l\'ordre, suivies dans la table <code>module_migrations</code>. <code>install.sql</code> sert de baseline pour une nouvelle installation.'],
  ]],
  ['id'=>'faq-monitoring','icon'=>'📡','title'=>'Monitoring & diagnostic','q'=>[
    ['d','Comment vérifier que mon installation est saine ?','<strong>Configuration → Diagnostic</strong> : contrôle des tables cœur, modules actifs sans tables, migrations en attente, droits d\'écriture, présence de <code>/install</code>… avec une synthèse OK / Avertissements / Erreurs.'],
    ['d','Le diagnostic signale un problème, puis-je le réparer ?','Oui : réparations en un clic (réinstaller un module, appliquer les migrations, désactiver un module orphelin, supprimer <code>/install</code>).'],
    ['a','Que montre la page Monitoring ?','Versions PHP / MySQL / framework, SAPI, modules installés, état des dossiers (permissions), et les logs applicatifs. La version du framework provient de <code>framework/changelog.json</code>.'],
  ]],
  ['id'=>'faq-versions','icon'=>'🗒️','title'=>'Versions & changelog','q'=>[
    ['d','Où voir les nouveautés et la version actuelle ?','<strong>Configuration → Changelog</strong> : la timeline des versions (la plus récente ouverte), avec type (correctif / fonctionnalité / module / sécurité) et date.'],
    ['a','Comment la version du framework est-elle gérée ?','Source unique : <code>framework/changelog.json</code> (clé <code>version</code>). Tant que la 4.0.0 n\'est pas publiée, on est en <strong>pré-versions</strong> (<code>4.0.0-alpha.N</code>). La compatibilité des modules se base sur le <strong>cœur numérique</strong> (ex. <code>4.0.0</code>), pas sur le suffixe alpha.'],
  ]],
  ['id'=>'faq-licences','icon'=>'🔑','title'=>'Licences (module)','q'=>[
    ['d','Comment offrir un module à un client sans licence ?','Dans <strong>Licences → Intégration</strong>, passez le module en mode <strong>« Ouvert »</strong> : il fonctionne librement, sans aucune vérification ni appel réseau.'],
    ['d','Comment générer une licence pour un client ?','Module <strong>Licences → Licences</strong> : créez une licence (client, produit, nombre de domaines, expiration éventuelle). Une clé <code>AEG-XXXX-…</code> est générée.'],
    ['a','Que se passe-t-il si le serveur de licence est injoignable ?','Le client n\'est <strong>jamais bloqué brutalement</strong> : cache local de la dernière validation, <strong>période de grâce hors-ligne</strong> configurable, puis <strong>fail-open</strong> (accès maintenu avec avertissement). Tout est paramétrable côté serveur de licence.'],
  ]],
];

$totalD = 0; $totalA = 0;
foreach ($faq as $t) { foreach ($t['q'] as $q) { $q[0] === 'd' ? $totalD++ : $totalA++; } }
$lvlMeta = ['d' => ['🟢 Débutant', 'green'], 'a' => ['🔵 Avancé', 'blue']];
?>

    <h1>Foire aux questions</h1>
    <p class="doc-lead">Les réponses aux questions courantes sur l'administration d'Aegis Framework — à deux niveaux selon votre profil.</p>
    <div class="doc-meta">
      <span class="doc-pill">🟢 <?= $totalD ?> Débutant</span>
      <span class="doc-pill">🔵 <?= $totalA ?> Avancé</span>
      <span class="doc-pill"><?= count($faq) ?> thèmes</span>
    </div>

    <div class="callout"><span class="i">💡</span><div><strong>Débutant</strong> = réponse courte « comment faire ». <strong>Avancé</strong> = le fonctionnement technique (où c'est stocké, le mécanisme, la sécurité). Utilisez le filtre et la recherche ci-dessous.</div></div>

    <div class="faq-tools">
      <div class="faq-filter" role="tablist">
        <button class="faq-flt active" data-lvl="all">Tout</button>
        <button class="faq-flt" data-lvl="d">🟢 Débutant</button>
        <button class="faq-flt" data-lvl="a">🔵 Avancé</button>
      </div>
      <input type="search" id="faqSearch" class="faq-search" placeholder="🔎 Rechercher une question…" autocomplete="off">
    </div>

    <?php foreach ($faq as $t): ?>
    <section class="faq-theme" id="<?= $t['id'] ?>" data-theme="1">
      <h2 class="faq-theme-title"><?= $t['icon'] ?> <?= htmlspecialchars($t['title']) ?></h2>
      <?php foreach ($t['q'] as [$lvl, $q, $a]): $lm = $lvlMeta[$lvl]; ?>
      <details class="faq-item" data-lvl="<?= $lvl ?>" data-text="<?= htmlspecialchars(mb_strtolower($q . ' ' . strip_tags($a)), ENT_QUOTES) ?>">
        <summary>
          <span class="faq-q"><?= htmlspecialchars($q) ?></span>
          <span class="ui-badge <?= $lm[1] ?> faq-lvl"><?= $lm[0] ?></span>
        </summary>
        <div class="faq-a"><?= $a ?></div>
      </details>
      <?php endforeach; ?>
    </section>
    <?php endforeach; ?>
    <div class="faq-noresult" id="faqNoResult" style="display:none">Aucune question ne correspond.</div>

<style>
.faq-tools{display:flex;gap:12px;flex-wrap:wrap;align-items:center;justify-content:space-between;margin:18px 0 22px}
.faq-filter{display:inline-flex;gap:4px;background:var(--bg2);border:1px solid var(--bd);border-radius:10px;padding:4px}
.faq-flt{border:none;background:none;padding:7px 14px;border-radius:7px;font-weight:600;font-size:.85rem;color:var(--tx2);cursor:pointer;font-family:inherit}
.faq-flt:hover{color:var(--tx)}
.faq-flt.active{background:var(--ac);color:#fff}
.faq-search{flex:1;min-width:220px;max-width:420px;padding:9px 13px;border:1px solid var(--bd2);border-radius:9px;background:var(--bg2);color:var(--tx);font-size:.88rem;font-family:inherit}
.faq-search:focus{outline:none;border-color:var(--ac)}
.faq-theme-title{font-size:1.25rem;margin:30px 0 12px;padding-top:14px;border-top:1px solid var(--bd)}
.faq-item{border:1px solid var(--bd);border-radius:10px;margin-bottom:8px;background:var(--bg2);overflow:hidden}
.faq-item[open]{border-color:var(--ac)}
.faq-item summary{display:flex;align-items:center;gap:12px;padding:13px 16px;cursor:pointer;list-style:none;font-weight:600;color:var(--tx)}
.faq-item summary::-webkit-details-marker{display:none}
.faq-item summary::before{content:'▸';color:var(--ac);font-size:.9rem;transition:transform .2s;flex-shrink:0}
.faq-item[open] summary::before{transform:rotate(90deg)}
.faq-q{flex:1}
.faq-lvl{flex-shrink:0;font-size:10px}
.faq-a{padding:0 16px 15px 40px;color:var(--tx2);font-size:.9rem;line-height:1.65}
.faq-a code{font-size:.85em;background:var(--code-bg);border:1px solid var(--bd);padding:.05em .35em;border-radius:4px;color:var(--tx)}
.faq-a strong{color:var(--tx)}
.faq-noresult{padding:20px;text-align:center;color:var(--tx3)}
</style>
<script>
(function () {
    var flts = document.querySelectorAll('.faq-flt'),
        items = Array.prototype.slice.call(document.querySelectorAll('.faq-item')),
        themes = Array.prototype.slice.call(document.querySelectorAll('.faq-theme')),
        search = document.getElementById('faqSearch'),
        noRes = document.getElementById('faqNoResult'),
        curLvl = 'all';

    function apply() {
        var q = (search.value || '').trim().toLowerCase(), any = false;
        items.forEach(function (it) {
            var okLvl = curLvl === 'all' || it.getAttribute('data-lvl') === curLvl;
            var okTxt = !q || (it.getAttribute('data-text') || '').indexOf(q) !== -1;
            var show = okLvl && okTxt;
            it.style.display = show ? '' : 'none';
            if (show && q) it.open = true; else if (!q) it.open = false;
            if (show) any = true;
        });
        // Masque un thème si toutes ses questions sont cachées.
        themes.forEach(function (th) {
            var visible = th.querySelectorAll('.faq-item:not([style*="display: none"])').length;
            th.style.display = visible ? '' : 'none';
        });
        if (noRes) noRes.style.display = any ? 'none' : 'block';
    }
    flts.forEach(function (b) {
        b.addEventListener('click', function () {
            flts.forEach(function (x) { x.classList.remove('active'); });
            b.classList.add('active'); curLvl = b.getAttribute('data-lvl'); apply();
        });
    });
    if (search) search.addEventListener('input', apply);
}());
</script>

<?php require __DIR__ . '/../inc/foot.php'; ?>
