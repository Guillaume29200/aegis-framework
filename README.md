# Aegis Framework V4 — Documentation du framework

CMS modulaire en PHP **8.5+**, architecture MVC légère, **sans aucune dépendance front externe** (ni Bootstrap ni jQuery — UI 100% maison). Conçu pour être étendu par des **modules** autonomes.

> Version : 4.0.0 · PHP requis : ≥ 8.5 (testé sous 8.5.6) · BDD : MySQL / MariaDB

---

## Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Arborescence](#arborescence)
3. [Cycle de vie d'une requête](#cycle-de-vie-dune-requête)
4. [Routing](#routing)
5. [Système de modules](#système-de-modules)
6. [Menu d'administration (JSON)](#menu-dadministration-json)
7. [Thème d'administration](#thème-dadministration)
8. [Sécurité](#sécurité)
9. [Services & helpers](#services--helpers)
10. [Installation (/install/)](#installation-install)
11. [Créer un module](#créer-un-module)
12. [Conventions](#conventions)

---

## Vue d'ensemble

- **Front controller** unique : `index.php` (toutes les requêtes HTTP y sont routées via `.htaccess`).
- **Bootstrap** : `framework/bootstrap.php` initialise constantes, autoloader, configuration, gestion d'erreurs, services et modules.
- **Modulaire** : chaque fonctionnalité vit dans `modules/<Nom>/` (contrôleurs, services, vues, routes, menu). Activable/désactivable via la table `modules`.
- **Back-office maison** : UI vanilla (CSS + JS), thème clair/sombre, disposition commutable, menu auto-généré depuis les modules actifs.

---

## Arborescence

```
v4_classic/
├── index.php                  # Front controller
├── routes.php                 # Routes système (racine)
├── changelog.md · FRAMEWORK.md
├── .htaccess                  # Réécriture + en-têtes de sécurité
├── framework/assets/          # CSS / JS / images (servis en statique, plus de /assets racine)
│   ├── css/admin/{ui,compat}.css
│   ├── js/admin/ui.js · js/turbo-nav.js
│   └── images/ · flags/
├── framework/
│   ├── bootstrap.php          # Initialisation
│   ├── config/{environment,database,security}.php
│   ├── Interfaces/            # ModuleInterface, BaseModule
│   ├── ModuleManager/         # Découverte & chargement des modules
│   ├── Middleware/            # MaintenanceMode, SecurityFirewall, SecurityHeaders
│   ├── Security/              # CSRFProtection, RateLimiter, SessionManager, XSSProtection
│   ├── Services/              # Router, Database, Logger, AdminMenuService, …
│   ├── Helpers/               # Recaptcha, Cache, AIModels
│   ├── Views/
│   │   ├── theme/admin/       # Shell admin (header.php, footer.php)
│   │   ├── theme/public/      # Maintenance, bannière cookies
│   │   └── debug-bar.php
│   └── logs/                  # Logs fichiers (accès web interdit)
└── modules/
    ├── Auth/                  # Authentification, utilisateurs, dashboard (cœur)
    ├── Configuration/         # Réglages, modèles IA (cœur)
    └── System/               # Modules, sécurité, monitoring (cœur)
```

---

## Cycle de vie d'une requête

1. `.htaccess` envoie tout vers `index.php` (sauf fichiers statiques réels).
2. `index.php` → `require framework/bootstrap.php` :
   - définit `ROOT_PATH`, `BASE_URL`, helpers d'URL (`u()`, `url()`, `redirect()`), helpers de thème (`admin_header()`, `admin_footer()`) ;
   - charge `.env`, la configuration, l'**autoloader** (PSR-like maison) ;
   - installe les gestionnaires d'erreurs/exceptions ;
   - instancie les services (DB, Logger, sécurité, cache, ModuleManager…) ;
   - applique middlewares globaux (en-têtes de sécurité, firewall, maintenance, filtre XSS) ;
   - **charge les modules actifs** (`ModuleManager::loadModules()`).
3. `index.php` crée le `Router`, injecte les dépendances, active la **garde CSRF**.
4. Les modules enregistrent leurs routes (`registerRoutes()`), puis `routes.php` racine.
5. `Router::dispatch($method, $uri)` applique la garde CSRF puis exécute le handler.

---

## Routing

`framework/Services/Router.php`. Méthodes : `get/post/put/delete`, groupes (`group()`), paramètres `{id}`.

**Deux styles de handler :**

```php
// 1. Closure
$router->get('/api/system/status', function () { /* … */ });

// 2. Controller@method (résolution de dépendances automatique)
$router->get('/admin/users', 'Auth\\Controllers\\AdminController@users');
```

L'injection de dépendances utilise la liste déclarée dans `index.php` via `setDependencies()` (Database, CSRFProtection, RateLimiter, Logger, ModuleManager, SecurityFirewallService, …), résolue par type du constructeur.

**Garde CSRF globale** : `enableCsrfGuard($csrf, $except)` valide automatiquement `csrf_token` (champ POST ou en-tête `X-CSRF-Token`) sur toute requête mutante. Exceptions configurables dans `security.php` (`csrf.except`).

---

## Système de modules

Un module = un dossier `modules/<Nom>/` contenant **au minimum** :

```
modules/MonModule/
├── module.json        # Manifeste
├── MonModule.php      # Classe principale (extends BaseModule)
├── routes.php         # (optionnel) retourne une closure(routes)
├── Controllers/ · Services/ · Views/
```

### `module.json`

```json
{
  "name": "MonModule",
  "version": "1.0.0",
  "description": "…",
  "author": "…",
  "class": "MonModule\\MonModule",
  "core": false,
  "menu": [
    {
      "label": "Mon module",
      "icon": "🚀",
      "position": 300,
      "match": "/admin/mon-module",
      "children": [
        { "label": "Liste", "icon": "📋", "url": "/admin/mon-module" }
      ]
    }
  ]
}
```

| Clé      | Rôle |
|----------|------|
| `class`  | Classe principale (namespace = nom du dossier). |
| `core`   | `true` = module non désactivable (Auth, Configuration, System). |
| `category` | Catégorie de regroupement sur la page Modules (ex. « Système », « Communautaire », « e-commerce »). Défaut : « Système » si `core`, sinon « Autres ». |
| `menu`   | Entrées de menu admin (voir ci-dessous). |

Les modules **actifs** sont listés dans la table SQL `modules` (`active = 1`), chargés par ordre de `priority`.

### Classe du module

Étend `Framework\Interfaces\BaseModule` :

```php
namespace MonModule;
use Framework\Interfaces\BaseModule;

class MonModule extends BaseModule
{
    public function getName(): string { return 'MonModule'; }
    public function getVersion(): string { return '1.0.0'; }
    public function getDescription(): string { return '…'; }

    public function registerRoutes($router): void
    {
        $register = require __DIR__ . '/routes.php';
        if (is_callable($register)) $register($router);
    }
}
```

`getAdminMenu()` est fourni par `BaseModule` : il lit la clé `menu` du `module.json` (pas besoin de l'écrire). Surchargez-le seulement pour un menu **dynamique** (badges, conditions).

---

## Menu d'administration (JSON)

100 % déclaratif. `framework/Services/AdminMenuService::build()` agrège les `menu` de tous les modules actifs, triés par `position`.

**Format d'un item :**

| Champ      | Type    | Description |
|------------|---------|-------------|
| `label`    | string  | Texte affiché |
| `icon`     | string  | Emoji |
| `url`      | string  | Lien (absent pour un groupe) |
| `position` | int     | Ordre croissant |
| `match`    | string  | Préfixe d'URL pour l'état « actif » (défaut : `url`) |
| `badge`    | string  | Compteur optionnel |
| `children` | array   | Sous-menu |

État actif : `AdminMenuService::isActive($item, $currentPath)`. **Activer/désactiver un module ajoute/retire automatiquement ses entrées.**

**Mega-menu (opt-in) :** en mode topbar, un item de menu déclaré avec **`"mega": true`** dans son `module.json` est rendu avec la classe `mega` → grille 3 colonnes (`repeat(3, minmax(180px,1fr))`, min 580px / max 92vw), repli 2 colonnes < 700px et repositionnement vers la gauche pour les items de fin de barre. En mode sidebar, le sous-menu déplié peut atteindre 900px de haut. **Aucun déclenchement automatique** (un menu à 7 entrées sans `mega` reste un menu déroulant classique). Exemple : module **Forum** (`"mega": true`, 14 sections).

---

## Thème d'administration

- **Emplacement unique** : `framework/Views/theme/admin/{header,footer}.php`.
- Les pages n'incluent jamais le chemin : elles appellent
  ```php
  admin_header('Titre de page', ['currentUser' => $user]);
  // … contenu HTML …
  admin_footer();
  ```
- **CSS** : `assets/css/admin/ui.css` (design system + composants `.ui-*`) ; `compat.css` (re-style des classes Bootstrap héritées).
- **JS** : `assets/js/admin/ui.js` (thème, disposition, plein écran, accent, panneau, mega-menu, compat widgets) ; `turbo-nav.js` (navigation AJAX, activable via Configuration).
- **Préférences** (localStorage) : `adm.theme` (light/dark/auto), `adm.layout` (sidebar/topbar), `adm.accent`, `adm.sidebar`. Pré-appliquées par un script inline (anti-FOUC). Attributs portés par `<html>` : `data-theme`, `data-layout`, `data-sidebar`, `data-panel`, `data-fullscreen`, `data-mobnav`.
- **Composants** réutilisables : `.ui-card`, `.ui-kpi`, `.ui-grid.cols-N`, `.ui-btn`, `.ui-badge`, `.ui-table`, `.ui-progress`, `.ui-empty`.
- **RGPD / Cookies** : page dédiée `/admin/configuration/rgpd` (`RgpdController` + `RgpdService`) — bandeau CNIL configurable (position, validité, catégories, couleurs), réinitialisation des consentements ; bandeau public `framework/Views/theme/public/cookie-banner.php`.
- **SEO & médias** : page dédiée `/admin/configuration/seo` (`SeoController` + `SeoService`) — uploads logo/favicon/Open Graph, meta, robots, Google Analytics.
- **Toutes les pages d'administration** sont en markup natif `.ui-*` (zéro Bootstrap) : Dashboard, Utilisateurs (liste/fiche/création/édition), Configuration/Paramètres, Modèles IA (liste/création/édition), Modules, Sécurité, Monitoring, RGPD. Les **pages d'authentification** (login/register/forgot/reset) sont également en UI maison (CSS partagé `modules/Auth/Views/auth/_head.php`).

---

## Sécurité

| Domaine | Implémentation |
|---------|----------------|
| **CSRF** | `CSRFProtection` + garde globale du Router (pool de tokens, `hash_equals`). |
| **Rate limiting** | `RateLimiter` (table `rate_limits` / `rate_limit_blocks`). Login limité par **compte** et par **IP**. |
| **Firewall applicatif** | `SecurityFirewall` + `SecurityFirewallService` (anti-flood, chemins/UA suspects, IP de confiance, écriture des blocages). |
| **Centre de sécurité** | `SecurityCenterService` (au-dessus du firewall) : catalogue de détecteurs catégorisés, score de menace par IP, blocage auto par seuils, listes blanche/noire, config. Page `/admin/security`. |
| **En-têtes** | `SecurityHeaders` + `.htaccess` (CSP, X-Frame-Options, nosniff, Referrer-Policy). |
| **Sessions** | `SessionManager` (cookies HttpOnly/Secure/SameSite, régénération). |
| **XSS** | `XSSProtection::filterGlobals()` + échappement systématique en vue. |
| **Mots de passe** | Argon2id. |
| **Uploads** | Validation MIME + extension, dossier protégé (`.htaccess` : exécution PHP désactivée). |
| **Logs** | `Logger` (SQL + fichiers), rotation par taille + rétention, dossier interdit au web. |

Configuration centralisée : `framework/config/security.php`.

### Centre de sécurité (`SecurityCenterService`)

Couche d'analyse au-dessus du firewall. Méthode pivot : `recordEvent($ip, $ruleKey, $details, $meta)` qui journalise l'événement (`security_events`), cumule le **score** de l'IP (`security_threat_scores`) et déclenche le **blocage automatique** via le firewall si les seuils sont franchis.

- **Détecteurs** (`security_rules`, 27 règles seedées depuis `SecurityCenterService::RULES`) : `category`, `label`, `severity`, `score`, `enabled` — éditables en admin.
- **Catégories** : `web`, `scan`, `auth`, `upload`, `abuse`, `admin` (activables individuellement).
- **Niveaux** : `levelFromScore()` → faible (0–25) / moyen (26–50) / élevé (51–75) / critique (76+).
- **Seuils** (`security_settings`) : `block_threshold` (déf. 100 → blocage temporaire `block_duration_hours`, déf. 24 h), `ban_threshold` (déf. 300 → permanent), `auto_block`, `enabled`, `log_retention_days`.
- **Listes** : `security_ip_whitelist` (jamais bloquée ; ajout = déblocage auto) ; liste noire = blocages permanents (`security_ip_blocks`).
- Injecté dans le conteneur (`SecurityCenterService`), schéma auto-créé (idempotent) via `ensureSchema()`.

**Détecteurs en pipeline** : le middleware `SecurityCenterDetector` (après `SecurityFirewall`) appelle `inspectHttpRequest()` qui scanne la **surface URL** (chemin + query + User-Agent, **pas les POST**) et lance `recordEvent()` pour chaque motif trouvé (XSS, SQLi, traversal, LFI/RFI, sondes git/env/backup/CMS, scanners, panels tiers, UA/motifs suspects). Les détecteurs d'**authentification** (`csrf_attack`, `brute_force`, `auth_flood`) sont signalés depuis `AuthController` via `$GLOBALS['securityCenterService']` (couplage souple). Les **uploads** sont inspectés dans le middleware sur `$_FILES` (`inspectUploadedFiles()` : webshells, doubles extensions, extensions exécutables). Le **détournement de session** (`session_hijacking`) est posé par `SessionManager` (drapeau global) puis relevé par le middleware. Le **rate-limit** du firewall alimente le score via `setDetectionSink()`. **24/27 détecteurs actifs** ; non déclenchés : `clickjacking` (en-tête `X-Frame-Options`), `account_enumeration`, `invalid_session` (conservés au catalogue, administrables).

> **Mode debug** : le réglage admin `debug_mode` (Configuration → Système) est l'interrupteur maître — activé, il force l'affichage des erreurs + la debug bar, quel que soit `APP_ENV`.

---

## Services & helpers

**Services** (`framework/Services/`) : `Router`, `Database` (PDO + query log), `Logger`, `AdminMenuService`, `CacheService`, `DebugBar`, `AuthTracker`, `DeviceDetector`, `GeolocService`, `RecaptchaService`, `SecurityFirewallService`, `SecurityCenterService`, `ImageOptimizer` (redimensionnement/compression GD, sûr : ignore SVG/ICO/GIF, n'écrase que si plus léger).

**Helpers globaux** (définis dans bootstrap) :

```php
u('/admin/users');          // URL absolue (respecte BASE_URL)
url('/x'); redirect('/x');  // navigation
admin_header($titre, $ctx); admin_footer();
```

---

## Installation (/install/)

Assistant d'installation autonome (ne charge pas le CMS) : `install/index.php`.

- **5 étapes** (sidebar) : Bienvenue → Prérequis → Base de données → Administrateur → Installation.
- **Étape Prérequis** : analyse animée (loader, résultats révélés un par un) — PHP ≥ `Installer::MIN_PHP` (8.5), extensions (`mbstring, pdo_mysql, curl, gd, fileinfo, openssl, zip, json, intl`), directives (`file_uploads`, `log_errors`), `mod_rewrite`, droits d'écriture (`/`, `framework/logs|cache|uploads`), avec aide contextuelle si un point échoue.
- **Installation (AJAX)** : tâches séquentielles `env → database → schema → admin → modules → finalize` avec barre de progression.
  - `install/Installer.php` : moteur (vérifs + `createDatabase`, `runSchema`, `createAdmin` [Argon2id], `seedDefaults`, `writeEnv`).
  - `install/schema.sql` : schéma complet (toutes les tables).
  - Écrit `.env` à la racine (lu par `config/database.php` via `getenv`).
  - Crée `install/installed.lock` → empêche toute réinstallation (à supprimer pour relancer).

> ⚠️ Après installation, supprimer le dossier `install/` (ou conserver `installed.lock`) en production.

Tant que le dossier `install/` existe, un **bandeau discret** s'affiche en haut de l'administration pour rappeler de le supprimer (rendu conditionnel dans `framework/Views/theme/admin/header.php`, masquable pour la session).

## Créer un module

**Le plus simple : le générateur.** `/admin/modules/generate` (bouton « 🪄 Générer un module » sur la page Modules) crée un squelette complet et activable (manifeste + menu, classe, routes, contrôleur dashboard+sections, service, `database/install.sql`+`uninstall.sql`+`migrations/`, changelog, vues `.ui-*`).

**À la main :**
1. Créer `modules/MonModule/` avec `module.json` + `MonModule.php`.
2. Déclarer routes dans `routes.php` (closure recevant `$router`).
3. Déclarer le menu dans `module.json` (clé `menu`) et la `category`.
4. Placer les vues dans `Views/` et utiliser `admin_header()` / `admin_footer()`.
5. Fournir `database/install.sql` (+ `uninstall.sql`), puis activer via la page **Modules** (activation atomique + vérification des tables).

**Distribution :** un module se livre en **ZIP** (contenant le dossier du module avec son `module.json`) ; il s'installe via **page Modules → « ⬆️ Installer (.zip) »** (extraction sécurisée), puis activation.

Le contrôleur est auto-instancié par le Router (DI). Pour des dépendances spécifiques, ajoutez-les à `setDependencies()` dans `index.php`.

### Installation / désinstallation

- **Deux conventions de schéma** (gérées par `ModuleManager::runModuleSchema()`) :
  1. `modules/<Nom>/database/install.sql` (+ `uninstall.sql`) — fichier unique ;
  2. `modules/<Nom>/schema.sql` + `schema_*.sql` (racine du module) — exécutés dans l'ordre alphabétique, **chacun dans son propre try/catch** (une migration déjà appliquée n'interrompt pas l'installation, simple avertissement loggé). Convention utilisée par le module **Forum** (26 tables).
- À l'**activation** (page Modules ou `ModuleManager::activateModule`), le schéma est exécuté puis le hook PHP `install()` ; à la **désactivation**, `uninstall()` puis `database/uninstall.sql` s'il existe.
- ⚠️ **Sans `uninstall.sql`, la désactivation est non destructive** : les tables du module sont conservées (cas du Forum). Pour purger les données, fournir un `uninstall.sql`.
- **Activation atomique** : `activateModule()` exécute le schéma, **vérifie que toutes les tables `CREATE TABLE` existent**, puis appelle `install()`. En cas d'échec, le module n'est **pas** activé, les tables partielles sont nettoyées (`uninstall.sql`) et l'erreur est disponible via `getLastError()` (affichée dans la page Modules). Garantit qu'on n'a jamais un module actif (menu visible) sans ses tables.

### Migrations (`database/migrations/*.sql`)

- `install.sql` = **baseline** (schéma complet à jour pour une install fraîche). `database/migrations/NNN_xxx.sql` = étapes **incrémentales** pour faire évoluer un module déjà installé.
- Suivi dans la table `module_migrations`. À l'activation, les migrations présentes sont marquées appliquées (baseline). `ModuleManager::updateModule($name)` rejoue les migrations en attente et synchronise la version. `pendingMigrationCount($name)` indique s'il y a des migrations à appliquer.
- Convention de nommage : préfixe ordonnable, ex. `2026_05_31_001_add_column.sql`.

### Exécution SQL (dumps complexes)

`ModuleManager` exécute les scripts SQL (`install.sql`, `uninstall.sql`, migrations) **statement par statement** : prise en charge de la directive **`DELIMITER`** (triggers / procédures stockées à corps `BEGIN…END`) et désactivation de **`FOREIGN_KEY_CHECKS`** le temps du script (CREATE TABLE avec clés étrangères « en avant », typiques des dumps phpMyAdmin / mysqldump). Un module peut donc fournir un dump SQL complet (tables + vues + triggers + données) comme `database/install.sql`.
- Le module apparaît dans la page **Modules** dès que son `module.json` est présent (découverte), et peut être activé/désactivé d'un clic (sauf modules `core`).

---

## Conventions

- **Aucune dépendance externe** dans le back-office (pas de Bootstrap/jQuery). Iconographie : emoji.
- **Un contrôleur + un service par fonctionnalité** (ne pas tout empiler dans un seul contrôleur/service). Ex : RGPD = `RgpdController` + `RgpdService`.
- **Interfaces simples** (utilisateur lambda) : pas d'éditeurs techniques (codes, JSON brut) sur les écrans courants.
- Échapper toute sortie utilisateur (`htmlspecialchars`).
- Requêtes SQL **toujours préparées**.
- Menus en **JSON**, jamais en dur. Un module peut ajouter ses entrées sous le groupe d'un autre (ex : Modules/Sécurité/Monitoring du module System figurent sous le menu « Configuration »).
- Pages admin via les **helpers** de thème, jamais d'`include` de chemin en dur.
- Modules cœur (`core: true`) : Auth, Configuration, System — non désactivables.
- **Documentation** : à chaque modification/ajout, mettre à jour `changelog.md` **et** `FRAMEWORK.md`.
