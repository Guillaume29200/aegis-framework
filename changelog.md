# Changelog — eSport-CMS V4

Toutes les modifications notables du projet. Format inspiré de [Keep a Changelog](https://keepachangelog.com/fr/).

---

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

## À venir

- Système RGPD/cookies entièrement administrable (choix, consentement, journalisation).
- Système d'installation / désinstallation de modules (hors modules cœur).
- Réécriture en markup natif des pages encore en compat Bootstrap (Utilisateurs, Modules, Sécurité, Paramètres, Modèles IA, pages d'authentification).
- Suppression définitive des assets Bootstrap/jQuery une fois les pages publiques portées.
