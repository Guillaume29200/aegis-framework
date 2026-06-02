# Changelog — Aegis Framework V4

Toutes les modifications notables du projet. Format inspiré de [Keep a Changelog](https://keepachangelog.com/fr/).

---

## [Non publié] — 2026-05-30 (suite 5)

### 🧹 Nettoyage de tables héritées inutilisées

- Suppression des tables héritées inutilisées **`module_settings`**, **`user_permissions`**, **`user_premium_access`** et **`user_activities`** des fichiers d'installation (`install/schema.sql` ; `user_permissions` aussi dans `modules/Auth/schema.sql`) — vestiges d'anciens développements, aucune donnée ni usage. La méthode morte **`Logger::logActivity()`** (seul écrivain de `user_activities`, jamais appelée) a également été retirée pour rester cohérent.

### 🚧 Bandeau « mode maintenance actif »

- Quand le **mode maintenance** est activé, un **bandeau rouge** s'affiche en haut de l'administration, **sous** le bandeau du dossier `/install` (couleur différente pour les distinguer), avec un lien direct vers Configuration pour le désactiver. Lecture directe du réglage `maintenance_mode` dans le shell admin.

### 🗺️ Sitemap & robots.txt + optimisation des images

- **Génération sitemap.xml / robots.txt** : nouvelle carte sur la page **SEO & médias** (`/admin/configuration/seo`). Service dédié `SitemapService` + contrôleur `SitemapController` (route `POST /admin/configuration/sitemap/generate`). Le sitemap inclut l'accueil et, si le module Forum est actif, le forum, ses catégories/sujets visibles (invités) et ses pages publiques. `robots.txt` référence le sitemap et bloque `/admin`, `/install`, `/framework`, `/auth` (ou tout le site si `seo_robots` = noindex). Si le fichier existe déjà, la carte affiche **la date de génération + nombre d'URL** avec un bouton **« Mettre à jour / régénérer »** et un lien « Voir le sitemap ».
- **Optimisation des images à l'upload** : nouveau service framework `ImageOptimizer` (GD) — redimensionne (largeur max) et recompresse les images bitmap (JPG/PNG/WebP), en n'écrasant l'original que si le résultat est plus léger ; **ignore SVG/ICO/GIF**. Branché sur les uploads SEO (logo/OG) et Configuration (logo/couverture). **Administrable** dans Configuration → Système : interrupteur « Optimiser les images uploadées » + largeur max (déf. 1920 px) + qualité (déf. 82). Test : JPEG 3000 px / 48 Ko → 1920 px / 19 Ko.

### 🧭 TurboNav — état actif du menu resynchronisé

- Après une navigation AJAX, le menu (sidebar + topnav) conservait l'item actif de la **page précédente** (ex. « Configuration » surligné alors qu'on était sur le dashboard Forum), car TurboNav ne remplaçait que `#admin-content`. Ajout de `syncChrome()` : après chaque swap (clic **et** retour/avance navigateur), la sidebar `.adm-nav`, la barre `.adm-topnav` et le titre `.adm-header-title` sont réinjectés depuis la réponse serveur — qui calcule déjà le bon item actif via `AdminMenuService::isActive`.

### 🍪 RGPD — texte du bandeau par défaut

- Le **texte introductif** (et le titre) du bandeau cookies retombent désormais sur un **texte par défaut** (`RgpdService::DEFAULT_TITLE` / `DEFAULT_TEXT`) lorsqu'ils sont vides — aussi bien à l'affichage qu'à l'enregistrement. Avant, un `rgpd_text` enregistré vide était conservé tel quel (la valeur par défaut n'était appliquée que si la clé n'existait pas), d'où un bandeau sans texte.

### 🧭 Menus topbar — fermeture au clic

- **Mega-menu et menus déroulants topbar** : cliquer un lien laissait le panneau ouvert (avec TurboNav la page ne se recharge pas, et le `:hover` maintenait le panneau sous le curseur). Désormais, le clic sur un lien d'un `.adm-topnav-sub` retire l'état `open` et ajoute une classe `closing` qui neutralise le `:hover` jusqu'à ce que la souris quitte l'item → le menu se ferme immédiatement.

### 🧱 Configuration — services dédiés (Mail + Images)

- Poursuite de la séparation (comme SEO/RGPD/Sitemap) : extraction de **`MailService`** (réglages e-mail + validation de l'expéditeur) et **`ImageSettingsService`** (réglages d'optimisation **+ point d'entrée unique `optimize()`**). `ConfigurationController` s'allège : `saveEmail()` et la partie images de `saveSystem()` délèguent à ces services. **`SeoService` et l'upload de Configuration** appellent désormais `ImageSettingsService::optimize()` au lieu de dupliquer la lecture des réglages + l'appel à `ImageOptimizer`.

### 🪟 Compat Bootstrap — shim `bootstrap.Modal`

- Ajout d'un **shim minimal de l'API Bootstrap** (`window.bootstrap.Modal` / `Collapse` / `Tab`) dans `ui.js` : bascule la classe `.show` (CSS déjà fournie par `compat.css`), cohérent avec la délégation `data-bs-*`. Permet aux modules qui ouvrent des modales **par JS** (ex. le wizard de configuration de GameNodePanel) de fonctionner sans Bootstrap. Ajout de `modal-xl`, `modal-dialog-scrollable`, `btn-close-white` et en-têtes `.modal-header.bg-*` dans `compat.css`.

### 🖥️ Module GameNodePanel (ex-GameServerHub)

- Le plus gros module (≈700 fichiers) intégré au framework, puis **rebrandé GameServerHub → GameNodePanel (GNP)** : namespace/dossier/classe, routes `/admin/gamenodepanel`, préfixe des 80 tables `gsh_`→`gnp_`, sous-système GSHQ→GNPQ. Catégorie « Game Node Panel ». Détails dans son `changelog.json`. *(Reste : intégration shell fine — Phase 1.)*
- **Modal changelog de la page Modules élargi** (620 → 880 px) pour les longs journaux.

### 🧩 Runner SQL robuste (dumps, triggers, FK) + changelog tolérant

- **`ModuleManager` exécute désormais les scripts SQL statement par statement** avec gestion de la directive **`DELIMITER`** (triggers/procédures à corps `BEGIN…END`) et **`FOREIGN_KEY_CHECKS=0`** le temps du script (CREATE TABLE avec FK « en avant », typiques des dumps phpMyAdmin/mysqldump). Permet d'installer des modules au schéma complexe (ex. GameServerHub : 80 tables/vues + triggers).
- **Page Modules — modal changelog tolérant** : `info()` normalise les 3 formats de `changelog.json` rencontrés (liste, `{"versions":[…]}`, objet indexé par version) → le modal s'affiche correctement quel que soit le module.

### 🗑️ Suppression de module + correctif bouton ZIP

- **Suppression définitive d'un module** depuis la page Modules (bouton 🗑️ sur les modules non-cœur) : modal de **confirmation par saisie du nom** (anti-erreur), puis `ModuleManager::deleteModule()` exécute `uninstall.sql` (**suppression des tables/données**), retire les lignes `modules` + `module_migrations` et **supprime le dossier** du module sur le disque. Les **modules cœur** (Auth/Configuration/System) sont refusés.
- **Correctif** : le bouton « ⬆️ Installer (.zip) » n'ouvrait pas le formulaire (le `style="display:none"` inline l'emportait sur la classe `.open`) → géré entièrement en CSS désormais.

### 🪄 Générateur de module (scaffolding)

- Nouvel assistant **`/admin/modules/generate`** (bouton « 🪄 Générer un module » sur la page Modules). À partir d'un formulaire (nom PascalCase, nom affiché, description, auteur, icône, catégorie, sections, mega-menu), `ModuleGeneratorService` génère un **module complet et activable** : `module.json` (menu + catégorie), classe `BaseModule`, `routes.php`, `Controllers/AdminController` (dashboard + une méthode par section), `Services/<Nom>Service`, `database/install.sql` + `uninstall.sql` + dossier `migrations/`, `changelog.json`, et les **vues UI maison** (.ui-*). Slugs de sections translittérés (accents). Module créé **inactif** (activation ensuite, avec vérification des tables).

### 📦 Installeur de module depuis un ZIP

- Bouton **« ⬆️ Installer un module (.zip) »** sur la page Modules. Service dédié `ModuleInstallerService` : validation (taille 50 Mo max, extension .zip, extension PHP zip), **protection zip-slip**, détection du `module.json` (racine ou sous-dossier), validation du manifeste (`name`/`class`), extraction puis déplacement vers `modules/<Name>` avec **sauvegarde de l'ancienne version** en cas de mise à jour. L'archive est **installée mais pas activée automatiquement** : l'activation passe par la couche de vérification des tables.

### 🩺 Page Diagnostic / santé (nouveau)

- Nouvelle page **`/admin/diagnostic`** (Configuration → Diagnostic) avec `DiagnosticService` + `DiagnosticController` dédiés. Contrôles : tables cœur (users/settings/modules), **module actif sans ses tables**, **version module.json ≠ base / migrations en attente**, dossier actif manquant, dossiers non inscriptibles (uploads/logs/racine), présence de `/install`. Synthèse OK / Avertissements / Erreurs.
- **Réparations en un clic** : réinstaller un module (recrée les tables), mettre à jour (migrations), désactiver un module orphelin, supprimer `/install`.

### 🔄 Migrations versionnées par module

- Nouveau système de **migrations** : un module peut fournir `database/migrations/*.sql` (fichiers ordonnés par nom). Table de suivi **`module_migrations`** (module + migration appliqués).
- **`install.sql` = baseline** (état complet à jour) : à l'activation, les migrations présentes sont marquées « appliquées » sans être rejouées. Les migrations ne servent qu'aux **mises à jour** d'un module déjà installé.
- **`ModuleManager::updateModule()`** exécute les migrations en attente (dans l'ordre, chacune enregistrée ; arrêt au 1ᵉʳ échec sans marquer la migration fautive) et synchronise la version en base sur celle du `module.json`. `pendingMigrationCount()` pour l'UI.
- Permet les **mises à jour de modules payants** (v1.0 → v1.1) sans casser le schéma.

### 🛡️ Installation de module — sécurisée et atomique

- **`ModuleManager::activateModule()` durci** : l'activation ne réussit que si **(1)** le schéma SQL s'exécute sans erreur, **(2)** toutes les tables déclarées (`CREATE TABLE`) existent réellement après coup (vérification via `information_schema`), et **(3)** le hook `install()` réussit. À la moindre erreur, le module **n'est pas activé**, les **tables partiellement créées sont nettoyées** (`uninstall.sql`), et `active` est remis à 0. Plus jamais de **menu actif sans tables**.
- **Message d'erreur exposé** : l'erreur réelle (ex. syntaxe SQL) est conservée (`getLastError()`) et **affichée dans la page Modules** lors d'une tentative d'activation échouée, au lieu d'un message générique.
- `deactivateModule()` aligné (capture `Throwable` + `getLastError()`).

### 🗂️ Modules — regroupement par catégorie

- La page **Modules** (`/admin/modules`) regroupe désormais les modules par **catégorie**, avec un en-tête par section (les modules **cœur** sont côte à côte sous « 🛠️ Système »). Nouvelle clé **`"category"`** dans `module.json` (ex. Forum → « Communautaire », Marketplace → « e-commerce ») ; à défaut, un module cœur tombe dans « Système », les autres dans « Autres ». Tri : Système en premier, Autres en dernier. Les filtres Actifs/Inactifs masquent les sections devenues vides.

### 🧩 Modules — modal de détails / changelog

- La page **Modules** (`/admin/modules`) affiche de nouveau, via un **bouton « ℹ️ Détails »** par carte, un **modal** récapitulant nom, version, auteur, dépendances, statut « cœur » et le **journal des modifications** lu depuis le `changelog.json` du module (endpoint `/admin/modules/info` déjà existant). Modal en UI maison (fermeture backdrop/Échap).

### 🧭 Mega-menu en opt-in + switchs sécurité

- **Mega-menu désormais explicite** : il ne se déclenche plus automatiquement au-delà de 6 entrées (ce qui transformait à tort le menu **Configuration** en mega-menu bogué). Il faut maintenant déclarer **`"mega": true`** dans le `module.json` de l'item. Seul **Forum** l'active ; Configuration redevient un menu déroulant classique. (`AdminMenuService::normalize()` propage la clé `mega`.)
- **Centre de sécurité — interrupteurs** : les activations (globale, blocage auto, par catégorie, et la colonne « Actif » de chaque détecteur) sont rendues par de **vrais switchs coulissants** (CSS maison, accent du thème) au lieu de cases à cocher.
- **Mega-menu Forum — pleine largeur** : le panneau était ancré sous le libellé « Forum » et débordait à droite. Il est désormais un **panneau pleine largeur ancré au bas réel du header** : l'item porteur passe en `position: static` et le panneau en `position: absolute; top: 100%; left:0; right:0` (s'ancre sur le header `sticky`), grille 4 colonnes → 3 sous 900px → 2 sous 640px, en mode topbar. `top: 100%` suit la hauteur réelle du header (bannière `/install`, nav sur 2 lignes) → plus de chevauchement ni de débordement.

## [Non publié] — 2026-05-30 (suite 4)

### 🏷️ Renommage — Aegis Framework

- Toutes les références **eSport-CMS / ESPORT-CMS / ESPORTCMS / ESPORT_CMS** remplacées par **Aegis Framework / AEGIS_FRAMEWORK** sur l'ensemble du code (76 remplacements, 54 fichiers). La **constante de garde** `ESPORT_CMS` → `AEGIS_FRAMEWORK` (define + tous les `defined()` modifiés ensemble), les libellés affichés, les domaines/e-mails (`esport-cms` → `aegis-framework`), docs incluses.

### 🐛 Correctif connexion

- **`Auth::checkRememberMe()`** utilisait `$this->db` (inexistant sur un module) → erreur fatale « Undefined property: Auth\\Auth::$db » dès qu'un cookie « se souvenir de moi » était présent, ce qui cassait la page de connexion. La base est désormais récupérée via le conteneur global avec garde-fou.

### 🛡️ Centre de sécurité — Phase 1 (fondation)

- Nouveau **`SecurityCenterService`** (cerveau), construit **sur** le `SecurityFirewallService` existant (enforcement/blocage déjà câblé dans le pipeline).
- **Catalogue de 27 détecteurs** répartis en 6 catégories (🌐 Web, 🔍 Scans, 🔐 Auth/Sessions, 📤 Uploads, 🚦 Anti-abus, 🖥️ Admin), chacun avec **gravité** (info/faible/moyen/élevé/critique) et **score de menace** — administrable.
- **Score de menace par IP** + niveaux (0–25 Faible, 26–50 Moyen, 51–75 Élevé, 76–100+ Critique) avec **blocage automatique** : seuil (défaut **100 pts → 24 h**) et seuil critique (défaut **300 pts → permanent**), configurables.
- **Listes IP administrables** : liste blanche (jamais bloquée, déblocage auto à l'ajout) en base + liste noire (blocage permanent manuel) et blocage temporaire.
- **Configuration complète** : activation globale, activation par catégorie, seuils, durée de blocage, rétention de l'historique.
- **Nouvelles tables** : `security_settings`, `security_rules` (27 règles seedées), `security_threat_scores`, `security_ip_whitelist` ; colonnes `category`/`score`/`rule_key` ajoutées à `security_events` (idempotent).
- **Page admin refondue** (`/admin/security`) en UI maison à onglets : 📊 Tableau de bord (KPI, événements par catégorie, top IP par score), 🧩 Détecteurs (toggle par catégorie/détecteur, score, gravité), ⚙️ Seuils & config, 📋 Listes IP (blanche/noire/blocages), 🧾 Historique (filtres + purge). Contrôleur + routes dédiés.
### 🛡️ Centre de sécurité — Phase 2 (détecteurs réels)

- **Moteur de détection HTTP** (`SecurityCenterService::inspectHttpRequest()`) + middleware **`SecurityCenterDetector`** branché dans le pipeline juste après le firewall. Analyse la **surface URL** (chemin + query string + User-Agent) — **jamais les corps POST**, pour éviter les faux positifs sur les contenus légitimes (forum, réglages…). Blocage immédiat (403) si un seuil est franchi.
- **Batch 1 — surface URL (17 détecteurs)** : `path_traversal`, `lfi`, `rfi`, `sql_injection`, `xss_attempt` (🌐) ; `git_probe`, `env_probe`, `backup_probe`, `sensitive_file_probe`, `cms_scan`, `scanner_detected`, `directory_scan` (🔍) ; `admin_panel_probe` (🖥️, exclut notre propre `/admin`) ; `suspicious_ua`, `suspicious_pattern` (🚦). Testé : 15/15 (attaques détectées, **zéro faux positif** sur `/admin`, `/forum`, `/auth`, assets).
- **Batch 2 — Authentification** : `csrf_attack` (échec de token sur login), `brute_force` (échec d'identifiants), `auth_flood` (limite de tentatives atteinte) signalés depuis `AuthController` via le conteneur global (`$GLOBALS['securityCenterService']`, couplage souple — la sécurité ne peut jamais casser l'authentification).
- **Batch 3 — Uploads, Session, Rate-limit** :
  - **Uploads** (inspection centralisée dans le middleware sur `$_FILES`, basée sur noms/extensions) : `malicious_upload` (webshells connus : shell.php, c99, r57…), `double_extension` (ex. `image.jpg.php`), `executable_upload` (.php/.phtml/.exe/.sh/.py…). Faible taux de faux positifs (les uploads légitimes sont images/docs/archives).
  - **Session** : `session_hijacking` — `SessionManager` pose un drapeau global à la divergence d'empreinte (IP/User-Agent), relevé par le middleware (score 50 = critique).
  - **Pont rate-limit** : les détections de `SecurityFirewallService` alimentent désormais le score (`rate_limit_exceeded`, ou `request_flood` si dépassement > ×3) via un *sink* branché dans bootstrap.
- **Bilan : 24 des 27 détecteurs actifs.** Non déclenchés volontairement : `clickjacking` (déjà neutralisé par l'en-tête `X-Frame-Options`), `account_enumeration` (l'app ne divulgue pas l'existence des comptes — messages d'erreur uniformes), `invalid_session` (faible surface) — conservés au catalogue, administrables.
- Correctif : suppression de la dernière mention « Protection GSH » (page de blocage du firewall) → « Aegis Framework ».

## [Non publié] — 2026-05-30 (suite 3)

### 🗨️ Module Forum installé + mega-menu

- **Module Forum activé** via le système d'installation : **26 tables `forum_*`** créées (catégories, sujets, messages, signalements, thèmes, widgets sidebar, sondages, liens de navigation, permissions modérateurs, shoutbox, réputation/récompenses, etc.). **110 routes** enregistrées (front `/forum/*`, API `/forum/api/*`, admin `/admin/forum/*`). Aucun couplage GSH — clés étrangères uniquement vers `users` et `forum_*`.
- **`ModuleManager::runModuleSchema()`** : nouvelle convention d'installation supportant **deux formats** — soit `database/install.sql` (fichier unique), soit `schema.sql` + `schema_*.sql` (racine du module, triés alphabétiquement). Chaque fichier est exécuté dans son **propre try/catch** : une migration facultative déjà appliquée n'interrompt pas l'installation (avertissement loggé). `activateModule()` appelle ce schéma avant `install()`.
- **Mega-menu** : un menu d'administration avec **plus de 6 entrées** déclenche automatiquement l'affichage en mega-menu (mode topbar) — Forum expose **14 sections** (📊 Tableau de bord, 📁 Catégories, 💬 Sujets, 📝 Messages, 🚩 Signalements, 👥 Utilisateurs, 📄 Pages statiques, 🎨 Thèmes, 🧩 Widgets sidebar, 🗳️ Sondages, 🔗 Liens de navigation, 🛡️ Permissions modérateurs, 💭 Shoutbox, ⚙️ Réglages).
  - **Topbar** : `<ul class="adm-topnav-sub mega">` → grille **3 colonnes** (`repeat(3, minmax(180px,1fr))`, min 580px, max 92vw), repositionnement vers la gauche pour les derniers items, repli 2 colonnes < 700px.
  - **Sidebar** : hauteur du sous-menu déplié portée de 500px à **900px** pour accueillir les 14 entrées sans coupure.
- **Désactivation non destructive** : le module Forum n'a pas de `database/uninstall.sql` → désactiver le module **conserve les données** (tables `forum_*` non supprimées). À documenter pour l'utilisateur.

## [Non publié] — 2026-05-30 (suite 2)

### 🧩 Installation / désinstallation de modules

- **ModuleManager exécute les SQL** : à l'activation d'un module → `modules/<Nom>/database/install.sql` (création des tables) ; à la désactivation → `database/uninstall.sql` (suppression). Convention générique pour tous les modules, pilotable depuis la page **Modules**.
- **Module Tickets intégré** : support de tickets (admin + espace membre), installable/désinstallable via l'interface.
  - **Dé-couplé de GameServerHub** : retrait de la clé étrangère vers `gsh_game_servers`, des JOIN `gsh_*` du service et de la sélection de serveur (création de ticket possible sans serveur). `problem_type` conservé.
  - **Vues admin réécrites** en UI maison `.ui-*` (liste avec KPI/filtres + détail avec fil de discussion, réponse, statut, suppression). Menu : entrée simple « 🎫 Tickets » (pas de mega-menu nécessaire).
  - Vues membre conservées (thème espace membre existant), création adaptée sans serveur.

## [Non publié] — 2026-05-30 (suite)

### 📡 Monitoring — corrections

- **Section « Permissions » corrigée** : `getAppRoot()` pointait vers `modules/System` (au lieu de la racine) depuis le déplacement du service → aucun fichier critique détecté. Corrigé via `ROOT_PATH`. La section liste désormais index.php, .htaccess, bootstrap, config DB/sécurité, .env — avec permissions octales **et** état lecture seule / inscriptible.
- **Onglet Stockage corrigé** : la taille du projet et la répartition étaient calculées sur le mauvais dossier (même cause) → désormais correctes (racine réelle).

### 🐞 Mode debug fonctionnel

- Le réglage **« Mode debug »** est désormais l'**interrupteur maître** : quand il est actif, il force l'affichage des erreurs et l'injection de la debug bar, **quel que soit l'environnement**. Avant, l'injection était gardée par `APP_ENV` (debug_bar) et ignorait le réglage admin (rien ne se passait en production).

### 🗂️ Réorganisation des assets + nettoyage final

- **Plus de dossier `/assets/` à la racine** : tout déplacé dans **`framework/assets/`** (css/admin, js/admin, images, flags). Toutes les références (`u('/assets/…')`, installeur, firewall, contrôleurs) mises à jour vers `/framework/assets/`.
- **TurboNav déplacé** dans `framework/assets/js/turbo-nav.js`.
- **Boxicons entièrement retiré** : `icons.css` + polices d'icônes (boxicons, LineIcons) supprimées, lien retiré du shell admin. Aucune référence restante.
- **Général (Configuration)** : upload du logo et de l'image de couverture en **glisser-déposer** avec aperçu de l'image en place.

## [Non publié] — 2026-05-30

### 🔐 Pages d'authentification + suppression de Bootstrap

- **Login, inscription, mot de passe oublié, réinitialisation** réécrits en UI maison autonome (CSS partagé `auth/_head.php`, zéro Bootstrap/jQuery/plugin) : layout visuel + formulaire, interrupteur « se souvenir », alertes, reCAPTCHA et bannière cookies conservés. Favicon injecté si défini.
- **Bootstrap & dépendances supprimés définitivement** : `bootstrap.min.css`, `bootstrap-extended.css`, `bootstrap.bundle.min.js`, `jquery.min.js`, `app.css/js`, `pace`, anciens thèmes (`dark-theme`, `semi-dark`, `header-colors`), `index/index2/index3/widgets.js` et tout le dossier `assets/plugins/` (simplebar, perfect-scrollbar, metismenu, vectormap, chartjs). Conservés : `assets/css/icons.css` (boxicons), `assets/css/admin/`, `assets/js/admin/`, `assets/js/turbo-nav.js`.

### 🍪 RGPD avancé (conforme CNIL)

- Page RGPD enrichie : **position** (haut/bas), **durée de validité** du consentement (30→395 j, max légal CNIL 13 mois), lien politique de confidentialité, titre/texte.
- **Catégories** : essentiels (toujours actifs) + analytiques / marketing / réseaux sociaux (interrupteurs).
- **Apparence** : couleurs (fond, texte, accent « Accepter », bouton « Refuser »), arrondi des coins.
- **Réinitialisation des consentements** (bouton) → le bandeau réapparaît pour tous les visiteurs (versionnement).
- **Bandeau public** réécrit : applique position/couleurs/arrondi/catégories, durée de cookie selon la validité, refus aussi visible que l'acceptation (CNIL).

### 🔍 SEO & médias (nouveau)

- Nouvelle page **Configuration → SEO & médias** (`/admin/configuration/seo`) avec **contrôleur + service dédiés** (`SeoController` / `SeoService`).
- **Uploads** : logo (SVG/PNG/JPG/WebP/GIF), favicon (SVG/PNG/ICO), image Open Graph (PNG/JPG/WebP) — avec aperçu et suppression, validation MIME + taille.
- **Référencement** : modèle de titre, meta description (160 car.), mots-clés, directive **robots** (index/noindex…), **Google Analytics / Tag Manager**.
- L'onglet « SEO » de la page Paramètres est retiré (remplacé par cette page dédiée).

### 🔧 Corrections & ajustements

- **Réglages restaurés** : la table `settings` avait été recréée vide → debug, cache, mode maintenance, reCAPTCHA, SEO (nom du site) et e-mails semblaient « cassés » car ils lisaient des valeurs absentes. Les 33 réglages ont été restaurés ; ces fonctionnalités refonctionnent. L'installeur seed désormais l'ensemble complet des réglages par défaut.
- **Menu réorganisé** : suppression du groupe « Système ». **Modules, Centre de sécurité et Monitoring** sont déplacés dans le menu **Configuration**.
- **Module System protégé** : `isProtectedModule()` se base désormais sur le flag `core` du module.json → Auth, Configuration et System ne sont plus désactivables.
- **Transition clair/sombre** retravaillée : fondu uniforme et coordonné (classe `theme-anim` activée le temps du basculement) au lieu du fondu inégal entre éléments.
- **Onglet TurboNav** enrichi (présentation, bénéfices, état actuel) dans Configuration.

## [Non publié] — 2026-05-29

### 🚀 Installeur `/install/` (nouveau)

- **Assistant d'installation multi-étapes** (sidebar + design maison, sans dépendance) : 1) présentation, 2) vérification des prérequis & droits d'écriture, 3) connexion base de données, 4) compte administrateur, 5) **installation en AJAX** (barre de progression, étapes animées).
- **Vérification d'hébergement** : PHP 8.5+ requis, extensions (mbstring, pdo_mysql, curl, gd, fileinfo, openssl, zip, json, intl), directives (file_uploads, log_errors), mod_rewrite, droits d'écriture — avec **aide contextuelle** si un élément manque.
- **Installation automatique** : création de la base, import du schéma (20 tables), compte super-admin (Argon2id), activation des modules cœur (Auth/Configuration/System), réglages par défaut, écriture du `.env`, verrou `install/installed.lock` anti-réinstallation.
- `install/schema.sql` : schéma complet de la base généré.
- **UX affinée** : étape « Prérequis » avec **loader animé** (roue de chargement, résultats révélés un par un ✅/❌) et grille compacte (moins de défilement) ; **champs de saisie redessinés** (icônes, focus, espacement) sur les étapes Base de données et Administrateur.
- **Bannière de sécurité admin** : un bandeau discret apparaît en haut de l'administration tant que le dossier `/install` existe (rappel de le supprimer). Masquable pour la session.

### 🍪 RGPD / Cookies (nouveau, administrable)

- **Refactor (architecture)** : RGPD déplacé dans son **propre `RgpdController` + `RgpdService`** (séparés de Configuration/SettingsService) — règle « un contrôleur + un service par fonctionnalité ».
- **Interface simplifiée** : catégories standard fixes (Nécessaires/Préférences/Statistiques/Marketing) avec interrupteurs on/off et libellés éditables — fini l'éditeur technique (codes/JSON).

- **Table `settings` manquante créée** : la base ne contenait pas la table `settings` attendue par `SettingsService` → toute la configuration du CMS échouait silencieusement (retour aux valeurs par défaut). Table recréée (`param_key` / `param_value` / `param_type`). **La configuration se sauvegarde de nouveau.**
- **Système RGPD complet et administrable** : nouvel écran *Configuration → 🍪 RGPD / Cookies* (`/admin/configuration/rgpd`) — activation de la bannière, titre/texte, lien politique, et **éditeur de catégories de cookies** dynamiques (ajout/suppression, requise/active, description).
- **Bannière publique réécrite** : dynamique (catégories configurées), consentement par catégorie, boutons Tout accepter / Refuser / Personnaliser, **versionnée** (toute modification redemande le consentement), stockage cookie + localStorage, sans dépendance.

### 🔒 Sécurité (audit complet)

- **Injection SQL corrigée** dans `AIModelService` (`getByCapability`, `update`) — requêtes désormais préparées.
- **Injection SQL corrigée** dans `MonitoringService` (`information_schema`) — passage de `PDO::quote()` à des requêtes préparées.
- **Injection SQL (LIMIT)** sécurisée dans `MemberController::getUserSessions` (cast entier explicite).
- **Injection de commande** corrigée dans `AdminController` (arrêt serveur SSH) — `escapeshellarg()` + validation stricte des PIDs et ports.
- **CSRF centralisé** : `Router::enableCsrfGuard()` valide automatiquement le token sur toute requête `POST/PUT/PATCH/DELETE` (déni par défaut, liste d'exceptions configurable). Plus de dépendance à une validation manuelle par contrôleur.
- **Rate limiting du login (compte + IP)** : `RateLimiter` branché sur `AuthService::login()` — clé par compte (anti-attaque ciblée) et par IP (anti-énumération). 5 tentatives / 5 min → blocage 15 min. IP via `REMOTE_ADDR` uniquement (anti-spoof).
- **CSP durcie** : suppression de `unsafe-eval`.
- **Uploads** : `framework/uploads/.htaccess` renforcé (moteur PHP désactivé, handlers retirés, exécutables bloqués) — protection contre l'exécution de scripts.
- **Logs** : rotation par taille (10 Mo) + rétention (30 j) dans `Logger` ; `framework/logs/.htaccess` interdit tout accès web.
- **Pollution de variables** : `extract()` remplacé par `EXTR_SKIP` dans les rendus de vues.
- **API** : endpoint `/api/system/status` ne divulgue plus l'environnement.

### 🤖 Modèles IA — simplification

- **Champs inutiles supprimés** : `context_window`, `pricing_input`, `pricing_output` (colonnes SQL + UI + service + contrôleur + schéma + seed). La fiche/édition ne montre plus le bloc « Paramètres techniques ». Conservé : capacités (vision/code/audio…), provider, nom, notes, actif/défaut.
- Formulaires **création/édition** réécrits en UI maison.

### 🖌️ Portage des pages admin vers l'UI maison — TERMINÉ

- **Toutes les vues d'administration sont désormais sans Bootstrap** (vérifié : zéro marqueur `data-bs-`/`col-*`/`page-wrapper`…). Pages portées en `.ui-*` : Tableau de bord, Utilisateurs (liste, fiche, création, édition), Configuration/Paramètres (onglets + sauvegarde AJAX par section), Modèles IA (liste, création, édition), Modules, Sécurité, Monitoring, RGPD.
- La bannière cookies n'est plus pilotée par la section Sécurité (doublon retiré) — seule la page RGPD la gère.

- **Correctif** : badge de rôle « Super Admin » invisible dans la liste des utilisateurs (classe `.bg-dark` manquante dans la couche de compatibilité → texte blanc sur fond clair). Ajout de `.bg-dark`, `.bg-light`, `.text-dark`.
- **Pages réécrites en markup natif `.ui-*`** (fin de la dépendance au markup Bootstrap, fini le « trop large/trop gros ») : **Utilisateurs (liste + fiche détaillée)**, **Modules**, **Centre de sécurité**, **Modèles IA (liste)**. KPI, tableaux, cartes et badges cohérents, dark/light géré. La fiche utilisateur abandonne le plugin de carte (jVectorMap) au profit d'un lien OpenStreetMap.
- **Plein écran corrigé** : l'appel `requestFullscreen` détaché provoquait une « Illegal invocation » → le bouton plein écran ne faisait rien. Corrigé (appel lié à l'élément + préfixes navigateurs).

### 🌱 Données par défaut (seed)

- **Modèles IA restaurés** : la table `ai_models` était vide. Ajout de `install/seed.sql` (13 modèles OpenAI/Claude/Mistral) exécuté par l'installeur (nouvelle étape « Données par défaut »), et restauration dans la base courante.

### 🎨 Interface d'administration (refonte complète, zéro dépendance externe)

- **Suppression de Bootstrap/jQuery** du back-office au profit d'une **UI maison** (`assets/css/admin/ui.css`, `assets/js/admin/ui.js`).
- **Shell commutable** : disposition **sidebar** ou **header horizontal** (mega-menu), thème **clair/sombre/auto**, **plein écran**, **couleur d'accent** personnalisable. Préférences en `localStorage` (anti-FOUC).
- **Panneau d'apparence** accessible depuis le menu utilisateur (plus de bouton flottant).
- **Emoji** comme iconographie principale du chrome.
- **Couche de compatibilité** (`assets/css/admin/compat.css`) : les pages non encore réécrites restent fonctionnelles et au nouveau look.
- **Dashboard** refait (KPI, graphe d'inscriptions sans librairie, répartition par rôle).
- **Page Monitoring** refaite : emoji, thème dark/light, onglets en JS vanilla.

### 🧩 Architecture modulaire

- **Menu admin 100 % JSON** : chaque `module.json` déclare sa clé `menu` (label, icône, url, position, sous-menus). `AdminMenuService` agrège les modules actifs. Plus aucun menu codé en dur.
- **Module `System`** créé (cœur, non désactivable) : regroupe Modules, Centre de sécurité et Monitoring (contrôleurs + service + vues + routes), auparavant éparpillés dans `framework/`.
- **Modules cœur** (`"core": true`) non désactivables : Auth, Configuration, System.
- **Réorganisation des fichiers** :
  - Shell admin → `framework/Views/theme/admin/` (helpers `admin_header()` / `admin_footer()`).
  - Pages publiques (maintenance, bannière cookies) → `framework/Views/theme/public/`.
  - `framework/Views/admin/` et `framework/Controllers/` supprimés.

### 🐛 Corrections

- **`/admin/users`** : erreur « Undefined variable `$basePath` » corrigée (passage à `BASE_URL`).
- **`/admin/configuration/ai-models`** : 404 corrigé (URL de menu alignée sur la route réelle).
- **Mode horizontal** : suppression de l'ascenseur horizontal parasite ; le mega-menu n'est plus rogné (cause : `overflow` du conteneur de nav).
- **TurboNav** rétabli dans le nouveau shell (et dé-référencé de GSH : global `TURBONAV`, conteneur `#admin-content`).
- **Logs parasites** : `GeolocService` ne journalise plus à chaque appel (logging derrière un flag `DEBUG` désactivé). Les `error_log()` PHP vont dans `C:/wamp64/logs/php_error.log`.

---
