# Aegis Framework V4 — Documentation du framework

CMS modulaire en PHP **8.5+**, architecture MVC légère, **sans aucune dépendance front externe** (ni Bootstrap ni jQuery — UI 100 % maison). Conçu pour être étendu par des **modules** autonomes.

> Version : **4.0.0-beta.1** · PHP requis : ≥ 8.5 (testé sous 8.5.9) · BDD : MySQL / MariaDB

---

## Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Arborescence](#arborescence)
3. [Cycle de vie d'une requête](#cycle-de-vie-dune-requête)
4. [Routing](#routing)
5. [Système de modules](#système-de-modules)
6. [Menu d'administration (JSON)](#menu-dadministration-json)
7. [Thème d'administration](#thème-dadministration)
8. [Moteur de templates & thèmes publics](#moteur-de-templates--thèmes-publics)
9. [Capacités](#capacités)
10. [Sécurité](#sécurité)
11. [Journalisation](#journalisation)
12. [Services & helpers](#services--helpers)
13. [Journal des versions](#journal-des-versions)
14. [Installation (/install/)](#installation-install)
15. [Créer un module](#créer-un-module)
16. [Conventions](#conventions)

---

## Vue d'ensemble

- **Front controller** unique : `index.php` (toutes les requêtes HTTP y sont routées via `.htaccess`).
- **Bootstrap** : `framework/bootstrap.php` initialise constantes, autoloader, configuration, gestion d'erreurs, services et modules.
- **Modulaire** : chaque fonctionnalité vit dans `modules/<Nom>/` (contrôleurs, services, vues, routes, menu). Activable/désactivable via la table `modules`.
- **Back-office maison** : UI vanilla (CSS + JS), thème clair/sombre, disposition commutable, menu auto-généré depuis les modules actifs.
- **Thèmes publics en HTML pur** : un module qui expose des pages visiteur les rend via un moteur de gabarits qui n'exécute aucun PHP (voir [Moteur de templates](#moteur-de-templates--thèmes-publics)).

---

## Arborescence

```
v4_beta/
├── index.php                  # Front controller
├── routes.php                 # Routes système (racine)
├── FRAMEWORK.md               # Ce document
├── .htaccess                  # Réécriture + en-têtes de sécurité
├── framework/
│   ├── bootstrap.php          # Initialisation
│   ├── config/{environment,database,security}.php
│   ├── Interfaces/            # ModuleInterface, BaseModule
│   ├── ModuleManager/         # Découverte & chargement des modules
│   ├── Middleware/            # SecurityHeaders, SecurityFirewall, CountryBlock,
│   │                          #   SecurityCenterDetector, MaintenanceMode
│   ├── Security/              # CSRFProtection, RateLimiter, SessionManager,
│   │                          #   XSSProtection, PasswordPolicy, EmailDomainPolicy
│   ├── Templating/            # TemplateEngine, ThemeManager, ThemeSettings, SettingsStore
│   ├── Capabilities/          # CapabilityRegistry, CapabilityManager, CapabilityOutput
│   ├── Storage/               # ImageUploader
│   ├── Services/              # Router, Database, Logger, Mailer, CountryDatabase, …
│   ├── Helpers/               # Cache, Markdown, Recaptcha, Seo, Rgpd, TurboNav, Analytics, AI
│   ├── Views/
│   │   ├── theme/admin/       # Shell admin (header.php, footer.php, components/)
│   │   ├── theme/public/      # Maintenance, bannière cookies
│   │   ├── errors/            # country-blocked.php (page de refus géographique)
│   │   └── debug-bar.php
│   ├── assets/                # CSS / JS / images (servis en statique)
│   │   ├── css/admin/{ui,compat}.css
│   │   ├── js/admin/ui.js · js/turbo-nav.js
│   │   └── images/ · flags/
│   ├── changelog/             # Un fichier JSON par version (4.0.0-beta.1.json…)
│   ├── data/geoip/            # Base IPv4 → pays (binaire, accès web interdit)
│   ├── cache/ · uploads/ · storage/
│   └── logs/                  # Logs fichiers (accès web interdit)
└── modules/
    ├── Auth/                  # Authentification, utilisateurs, dashboard, 2FA (cœur)
    ├── Configuration/         # Réglages, RGPD, SEO, modèles IA (cœur)
    ├── System/                # Modules, sécurité, monitoring, changelog (cœur)
    └── Analytics/             # Audience (non cœur, désactivable)
```

---

## Cycle de vie d'une requête

1. `.htaccess` envoie tout vers `index.php` (sauf fichiers statiques réels).
2. `index.php` → `require framework/bootstrap.php` :
   - définit `ROOT_PATH`, `BASE_URL`, helpers d'URL (`u()`, `url()`, `redirect()`), helpers de thème (`admin_header()`, `admin_footer()`) ;
   - charge `.env`, la configuration, l'**autoloader** (PSR-like maison) ;
   - installe les gestionnaires d'erreurs/exceptions ;
   - démarre la session, puis instancie les services (DB, Logger, sécurité, cache, ModuleManager…) ;
   - applique les middlewares globaux, **dans cet ordre** : en-têtes de sécurité → pare-feu IP → **filtrage géographique** → détecteurs du Centre de sécurité → maintenance → filtre XSS ;
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

**`HEAD` est résolu comme `GET`**, conformément à la norme HTTP : PHP omet le corps de lui-même. Sans cela, tout ce qui interroge un site en HEAD — supervision, vérificateurs de liens, robots d'indexation, certains relais — recevait un 404 sur la page d'accueil.

**Préfixes publics renommés** : si le préfixe public d'un module change, ses anciennes adresses répondent en **301** vers la nouvelle (`PublicPrefix::renamed()`), pour que liens partagés, favoris et référencement survivent.

**Garde CSRF globale** : `enableCsrfGuard($csrf, $except)` valide automatiquement `csrf_token` (champ POST ou en-tête `X-CSRF-Token`) sur toute requête mutante. Exceptions configurables dans `security.php` (`csrf.except`).

**Deux exceptions distinctes** : `RouteNotFoundException` (aucune route ne correspond — du trafic ordinaire) et `RouterException` (contrôleur absent, handler invalide — une vraie panne). Cette distinction pilote la [journalisation](#journalisation).

---

## Système de modules

Un module = un dossier `modules/<Nom>/` contenant **au minimum** :

```
modules/MonModule/
├── module.json        # Manifeste
├── MonModule.php      # Classe principale (extends BaseModule)
├── routes.php         # (optionnel) retourne une closure(routes)
├── Controllers/ · Services/ · Views/
└── themes/            # (optionnel) thèmes publics — voir Moteur de templates
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
  "category": "Communautaire",
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

| Clé | Rôle |
|-----|------|
| `class` | Classe principale (namespace = nom du dossier). |
| `core` | `true` = module non désactivable (Auth, Configuration, System). |
| `category` | Catégorie de regroupement sur la page Modules (ex. « Système », « Communautaire », « e-commerce »). Défaut : « Système » si `core`, sinon « Autres ». |
| `menu` | Entrées de menu admin (voir ci-dessous). |

Les modules **actifs** sont listés dans la table SQL `modules` (`active = 1`), chargés par ordre de `priority`.

> La version affichée est lue depuis `module.json`, jamais codée en dur dans la classe : plusieurs modules annonçaient une version dans leur manifeste et une autre dans leur code.

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

| Champ | Type | Description |
|-------|------|-------------|
| `label` | string | Texte affiché |
| `icon` | string | Emoji |
| `url` | string | Lien (absent pour un groupe) |
| `position` | int | Ordre croissant |
| `match` | string | Préfixe d'URL pour l'état « actif » (défaut : `url`) |
| `badge` | string | Compteur optionnel |
| `children` | array | Sous-menu |

État actif : `AdminMenuService::isActive($item, $currentPath)`. **Activer/désactiver un module ajoute/retire automatiquement ses entrées.**

**Mega-menu (opt-in) :** en mode topbar, un item déclaré avec **`"mega": true`** est rendu en grille 3 colonnes (`repeat(3, minmax(180px,1fr))`, min 580 px / max 92 vw), repli 2 colonnes < 700 px, repositionnement vers la gauche en fin de barre. En mode sidebar, le sous-menu déplié peut atteindre 900 px de haut. **Aucun déclenchement automatique** : un menu à 7 entrées sans `mega` reste un déroulant classique.

---

## Thème d'administration

- **Emplacement unique** : `framework/Views/theme/admin/{header,footer}.php`.
- Les pages n'incluent jamais le chemin : elles appellent
  ```php
  admin_header('Titre de page', ['currentUser' => $user]);
  // … contenu HTML …
  admin_footer();
  ```
- **CSS** : `framework/assets/css/admin/ui.css` (design system + composants `.ui-*`) ; `compat.css` (re-style des classes Bootstrap héritées).
- **JS** : `framework/assets/js/admin/ui.js` (thème, disposition, plein écran, accent, panneau, mega-menu) ; `turbo-nav.js` (navigation AJAX, activable via Configuration).
- **Préférences** (localStorage) : `adm.theme` (light/dark/auto), `adm.layout` (sidebar/topbar), `adm.accent`, `adm.sidebar`. Pré-appliquées par un script inline (anti-FOUC). Attributs portés par `<html>` : `data-theme`, `data-layout`, `data-sidebar`, `data-panel`, `data-fullscreen`, `data-mobnav`.
- **Composants** : `.ui-card`, `.ui-kpi`, `.ui-grid.cols-N`, `.ui-btn`, `.ui-badge`, `.ui-table`, `.ui-progress`, `.ui-empty`.
- **Composants d'en-tête** (`theme/admin/components/`) : cloche de notifications, **indicateur de mises à jour de modules**, **jauges CPU/mémoire** (SVG, rafraîchies toutes les 5 s, **aucune écriture en base**, interrogation suspendue quand l'onglet passe en arrière-plan).
- **Pied de page** : logo, version lue depuis le journal des versions, copyright avec année automatique.
- **RGPD / Cookies** : `/admin/configuration/rgpd` — bandeau CNIL configurable, réinitialisation des consentements ; bandeau public `framework/Views/theme/public/cookie-banner.php`.
- **SEO & médias** : `/admin/configuration/seo` — uploads logo/favicon/Open Graph, meta, robots, Google Analytics.
- **Toutes les pages d'administration** sont en markup natif `.ui-*` (zéro Bootstrap). Les **pages d'authentification** (login, register, forgot, reset, 2FA) partagent `modules/Auth/Views/auth/_head.php`.

---

## Moteur de templates & thèmes publics

Un module qui expose des pages visiteur les rend depuis un **thème**, jamais depuis du PHP mêlé au HTML.

### Le moteur (`Framework\Templating\TemplateEngine`)

Syntaxe volontairement **réduite** : elle ne sait ni comparer, ni calculer, ni appeler une fonction. Un thème téléversé ne peut donc pas exécuter de code sur le serveur.

| Construction | Effet |
|--------------|-------|
| `{{ variable }}` | Affichage échappé |
| `{{{ variable }}}` | Affichage brut (réservé aux sorties du framework) |
| `{{ var \| filtre:arg }}` | Filtre |
| `{% if x %}…{% else %}…{% endif %}` | Condition (`{% if not x %}` accepté) |
| `{% for x in liste %}…{% empty %}…{% endfor %}` | Boucle (`limit:N` avec un entier littéral) |
| `{% include "partiel" %}` | Inclusion |

**Filtres** : `upper`, `lower`, `date`, `datetime`, `truncate`, `nl2br`, `number`, `default`, `count`, `initials`.

> ⚠️ `default:` traite son argument comme une **chaîne littérale**, jamais comme un chemin de variable.

Les mois et jours sont rendus en français par `frenchFormat()`.

### La structure d'un thème (imposée)

```
modules/<Module>/themes/<cle>/
├── meta.json          # Nom, version, auteur, options
├── *.html             # Gabarits
├── preview.png        # Aperçu
└── assets/
    ├── css/ · js/ · images/ · uploads/
```

Les quatre sous-dossiers d'`assets/` sont créés systématiquement (`ThemeManager::ASSET_DIRS`). Un thème se zippe, se partage et s'installe tel quel.

- **Repli par gabarit** : un gabarit absent du thème actif est repris du thème de repli — un thème incomplet donne une page complète plutôt qu'une page blanche.
- **Options de thème** (déclarées dans `meta.json`) : `toggle`, `select`, `color`, `text`, `number`, `textarea`, `links`, `image`. Stockées sous `theme_option.<theme>.<cle>`.
- **Installation par ZIP** : chemins remontants refusés, extensions non listées refusées (aucune n'est exécutable), `meta.json` racine obligatoire. **Le contrôle a lieu avant extraction.**

### Brancher son propre stockage de réglages

`ThemeManager` est `final` et reçoit un `SettingsStore` : le service de réglages du module fournit `get`, `all`, `set`, `forget`. On instancie donc par une fabrique statique, jamais par héritage :

```php
$themes = MonModuleThemes::manager($db);
```

---

## Capacités

Une capacité est un service transversal qu'un module **déclare utiliser**, et que l'administrateur active ou non. Huit capacités : `markdown`, `cache`, `ai`, `seo`, `rgpd`, `analytics`, `turbonav`, `recaptcha`.

`CapabilityOutput::forPage()` rassemble ce qui doit aller dans le `<head>` et avant `</body>` ; le gabarit les pose via `{{{ head_extra }}}` et `{{{ body_end }}}`.

**Règle absolue** : une capacité **améliore** un fonctionnement, elle ne le conditionne jamais. Le code généré teste toujours la disponibilité avant usage :

```php
if (function_exists('cache_remember')) { /* … */ }
```

---

## Sécurité

| Domaine | Implémentation |
|---------|----------------|
| **CSRF** | `CSRFProtection` + garde globale du Router (pool de tokens, `hash_equals`). |
| **Rate limiting** | `RateLimiter` (tables `rate_limits` / `rate_limit_blocks`). Login limité par **compte** et par **IP**. |
| **Firewall applicatif** | `SecurityFirewall` + `SecurityFirewallService` (anti-flood, chemins/UA suspects, IP de confiance, écriture des blocages). |
| **Centre de sécurité** | `SecurityCenterService` : 27 détecteurs catégorisés, score de menace par IP, blocage auto par seuils, listes blanche/noire. Page `/admin/security`. |
| **Filtrage géographique** | `CountryFirewall` + `CountryBlock` — liste noire ou blanche par pays. Voir ci-dessous. |
| **Mots de passe** | Argon2id + `PasswordPolicy` : longueur et complexité lues depuis `security.php`, refus des mots de passe présents dans une fuite connue. |
| **Double authentification** | `TwoFactorService` — code à 6 chiffres par e-mail, code de secours. Voir ci-dessous. |
| **Domaines e-mail** | `EmailDomainPolicy` — liste blanche de fournisseurs à l'inscription (anti-adresses jetables). |
| **En-têtes** | `SecurityHeaders` + `.htaccess` (CSP, X-Frame-Options, nosniff, Referrer-Policy). |
| **Sessions** | `SessionManager` (cookies HttpOnly/Secure/SameSite, régénération, liaison IP configurable). |
| **XSS** | `XSSProtection::filterGlobals()` + échappement systématique en vue. |
| **Uploads d'images** | `Framework\Storage\ImageUploader` : taille plafonnée, type lu dans le **contenu** et non dans le nom, **ré-encodage GD** qui purge toute charge dissimulée, SVG refusé sauf demande explicite, dossier durci par `.htaccess`. |
| **Journalisation** | `Logger` (SQL + fichiers), rotation par taille + rétention, dossier interdit au web. |

Configuration centralisée : `framework/config/security.php`.

> Les sections `ip_whitelist` et `upload` de ce fichier portent une mention **non implémenté / non lu** : elles sont conservées à titre de référence. `ip_whitelist` (restreindre `/admin` à des adresses) n'existe pas ; ne pas la confondre avec la table `security_ip_whitelist` du Centre de sécurité, qui est active mais fait l'inverse (adresses dont on ignore les signalements).

### Politique de mot de passe (`Framework\Security\PasswordPolicy`)

Source unique pour l'inscription **et** la réinitialisation — les deux points divergeaient auparavant.

- Règles lues depuis `security.php` : `min_length` (12), `require_uppercase`, `require_lowercase`, `require_numbers`, `require_special`.
- `check_compromised` interroge Have I Been Pwned par **k-anonymity** : seuls les 5 premiers caractères de l'empreinte SHA-1 quittent le serveur. Échec réseau ⇒ on n'empêche pas l'inscription.
- Plafond de 200 caractères (au-delà, Argon2id travaille pour rien — levier de déni de service).
- `enonce()` rend la règle en une phrase, affichée sous le champ de saisie.

> `max_age_days` et `prevent_reuse` ont été **retirés** plutôt qu'implémentés : la rotation périodique imposée est déconseillée par le NIST (SP 800-63B §5.1.1.2), elle pousse à décliner un même mot de passe.

### Double authentification (`Auth\Services\TwoFactorService`)

Réglages dans `security_settings` (Configuration → 2FA) : `twofa_enabled`, `twofa_scope` (`admins` | `all`), `twofa_ttl_minutes`.

- Code à 6 chiffres (`random_int`), **empreinte SHA-256 seule en base**, usage unique, 5 tentatives, un seul code vivant par compte, délai de 60 s entre deux envois.
- Le contrôle s'insère **avant** `createUserSession()` : tant que le code n'est pas vérifié, aucune session authentifiée n'existe.
- **Code de secours** produit à l'activation et affiché une seule fois. Il s'emploie depuis la page de vérification et désactive le 2FA — sans lui, une panne d'envoi d'e-mail enfermerait l'administrateur dehors, la page permettant de couper la protection étant elle-même derrière la protection.
- **Par e-mail seulement.** Pas de SMS (payant, et le détournement de carte SIM en fait le facteur le plus faible), pas de TOTP (application tierce requise).
- L'interface impose un **envoi de test** avant activation : `Mailer::transportDisponible()` ouvre réellement la connexion SMTP, car lire la directive `SMTP` ne suffit pas (WAMP la remplit avec `localhost` sans que rien n'écoute).

### Filtrage géographique (`Framework\Services\CountryFirewall`)

Onglet **🌍 Pays** du Centre de sécurité. Interrupteur global, puis **liste noire** (tout passe sauf) ou **liste blanche** (rien ne passe sauf). Page de refus autonome en anglais, code **403**, sans ressource externe.

La correspondance IP → pays est **entièrement locale** (`CountryDatabase`) :

- Source : les fichiers d'attribution des cinq registres régionaux (RIPE, ARIN, APNIC, AFRINIC, LACNIC) — ~27 Mo, sans compte ni clé, publiés quotidiennement.
- Convertis en un binaire de 2,5 Mo : **260 903 plages, 239 pays**, dans `framework/data/geoip/`.
- Un **index par premier octet** ramène la recherche à une seule lecture : **0,13 ms**, sans requête SQL ni appel réseau.

> Interroger une API de géolocalisation par requête entrante ferait de la protection un **amplificateur** : sous attaque, chaque requête hostile déclencherait une connexion sortante depuis le serveur, et les quotas des services gratuits (45 appels/minute) rendraient le filtre aveugle dès que le trafic monte.

**Trois garde-fous**, parce que se fermer soi-même la porte est l'erreur facile ici :

1. Un **pays inconnu n'est jamais bloqué** (base absente, IPv6, plage non attribuée, réseau local).
2. Un **administrateur connecté n'est jamais refoulé** — de quoi corriger un réglage depuis l'endroit où on l'a commis.
3. L'enregistrement est **refusé** si la configuration bloquerait le pays de l'administrateur, ou si la liste est vide.

Le middleware réutilise la résolution d'adresse et la liste blanche du pare-feu : une seconde implémentation aurait fini par diverger, et diverger ici signifie bloquer le mauvais visiteur.

### Centre de sécurité (`SecurityCenterService`)

Couche d'analyse au-dessus du firewall. Méthode pivot : `recordEvent($ip, $ruleKey, $details, $meta)` — journalise (`security_events`), cumule le **score** de l'IP (`security_threat_scores`), déclenche le **blocage automatique** si les seuils sont franchis.

- **Détecteurs** (`security_rules`, 27 règles seedées depuis `SecurityCenterService::RULES`) : `category`, `label`, `severity`, `score`, `enabled` — éditables en admin.
- **Catégories** : `web`, `scan`, `auth`, `upload`, `abuse`, `admin`.
- **Niveaux** : `levelFromScore()` → faible (0–25) / moyen (26–50) / élevé (51–75) / critique (76+).
- **Seuils** (`security_settings`) : `block_threshold` (100 → blocage temporaire de `block_duration_hours`, 24 h), `ban_threshold` (300 → permanent), `auto_block`, `enabled`, `log_retention_days`.
- **Listes** : `security_ip_whitelist` (jamais bloquée ; ajout = déblocage auto) ; liste noire = blocages permanents (`security_ip_blocks`).

**Détecteurs en pipeline** : `SecurityCenterDetector` appelle `inspectHttpRequest()` qui scanne la **surface URL** (chemin + query + User-Agent, **pas les POST**). Les détecteurs d'**authentification** (`csrf_attack`, `brute_force`, `auth_flood`) sont signalés depuis `AuthController` via `$GLOBALS['securityCenterService']`. Les **uploads** sont inspectés sur `$_FILES`. Le **détournement de session** est posé par `SessionManager` puis relevé par le middleware. Le **rate-limit** du firewall alimente le score via `setDetectionSink()`.

> `127.0.0.1` et `::1` figurent d'office dans les IP de confiance : en développement, la page affiche donc des compteurs vides. Un bandeau l'explique, faute de quoi on croit le Centre en panne.

> **Mode debug** : le réglage `debug_mode` (Configuration → Système) est l'interrupteur maître — activé, il force l'affichage des erreurs + la debug bar, quel que soit `APP_ENV`.

---

## Journalisation

**On ne journalise que l'essentiel.** Le principe s'est imposé après constat : sur 912 entrées `CRITICAL`, 756 étaient de simples pages introuvables et 29 des jetons CSRF périmés — 95 % de bruit qui masquait la quinzaine de vraies pannes.

| Situation | Traitement |
|-----------|-----------|
| Route non trouvée (404) | **non journalisée** — lien périmé, faute de frappe, robot en maraude : du trafic ordinaire. Les sondages hostiles restent du ressort du Centre de sécurité, qui les détecte sur des critères autrement plus sûrs. |
| Jeton CSRF invalide | `warning` — attendu dès qu'une session expire avec un formulaire ouvert, mais une rafale peut signaler une attaque. |
| Session expirée par inactivité | **non journalisée** — c'est la sécurité qui fonctionne, pas une erreur. |
| Toute autre exception | `critical`, avec fichier, ligne et trace. |

Le point de capture est `catch (\Throwable)` et non `catch (\Exception)` : ce dernier laissait passer tout ce qui hérite de `\Error` (TypeError, appel de méthode inexistante), c'est-à-dire que les pannes les plus graves étaient les seules à n'être jamais consignées.

Les journaux fichiers vivent dans `framework/logs/` (accès web interdit) ; la page **Monitoring** permet de les consulter, de les paginer, de les supprimer un par un, de vider la table `logs` et de tronquer le fichier d'erreurs de PHP.

---

## Services & helpers

**Services** (`framework/Services/`) :

| Service | Rôle |
|---------|------|
| `Router`, `Database`, `Logger` | Socle. |
| `AdminMenuService`, `CacheService`, `DebugBar` | Back-office. |
| `Mailer` | **Unique point d'envoi de courriel.** Résout l'expéditeur depuis les réglages, encode, sonde la disponibilité du transport. Un seul endroit à modifier le jour où SMTP authentifié sera nécessaire. |
| `ChangelogService` | Lit `framework/changelog/*.json`. |
| `CountryDatabase`, `CountryFirewall` | Filtrage géographique. |
| `SecurityFirewallService`, `SecurityCenterService` | Sécurité. |
| `DashboardHealthService` | Diagnostic, mises à jour, sécurité, stockage, audience du tableau de bord. |
| `SystemMetricsService` | CPU / mémoire, multiplateforme, **sans écriture en base**. |
| `ModuleUpdateService` | Migrations de modules en attente. |
| `AuditService`, `NotificationService` | Journal d'audit, notifications utilisateur. |
| `AuthTracker`, `DeviceDetector`, `GeolocService` | Suivi des connexions. |
| `PublicPrefix` | Préfixes publics des modules et leurs renommages. |
| `ImageOptimizer` | Redimensionnement/compression GD (ignore SVG/ICO/GIF, n'écrase que si plus léger). |
| `RecaptchaService`, `AIClientService`, `SecretBox`, `LicenseGuard` | Divers. |

**Mesure des métriques système** : sous Windows, `Win32_Processor.LoadPercentage` met 1 200 ms et `PerfFormattedData` plus de 11 s ; on lit donc les compteurs **bruts** (`Win32_PerfRawData_PerfOS_Processor`, ~50 ms) et on calcule la différence. Sous Linux, `/proc/stat` et `/proc/meminfo`.

**Helpers globaux** (définis dans bootstrap) :

```php
u('/admin/users');          // URL absolue (respecte BASE_URL)
url('/x'); redirect('/x');  // navigation
admin_header($titre, $ctx); admin_footer();
```

---

## Journal des versions

Un **fichier JSON par version** dans `framework/changelog/`, le nom du fichier portant le numéro :

```
framework/changelog/
    4.0.0-alpha.1.json
    …
    4.0.0-beta.1.json
```

Publier une version, c'est **déposer un fichier** : rien à modifier, rien à fusionner, rien à renuméroter. Le fichier unique précédent obligeait à toucher un gros tableau partagé où deux publications parallèles se marchaient dessus.

Le nom du fichier fait foi pour le numéro — il ne peut pas diverger du contenu — et la version courante est simplement la plus haute (`version_compare` connaît l'ordre `alpha < beta < rc < stable`). Les étiquettes absentes sont déduites des titres de groupes. Lecture mémorisée 600 s ; page `/admin/changelog`.

---

## Installation (/install/)

Assistant d'installation autonome (ne charge pas le CMS) : `install/index.php`.

- **5 étapes** (sidebar) : Bienvenue → Prérequis → Base de données → Administrateur → Installation.
- **Étape Prérequis** : PHP ≥ `Installer::MIN_PHP` (8.5.0), extensions (`mbstring, pdo_mysql, curl, gd, fileinfo, openssl, zip, json, intl`), directives (`file_uploads`, `log_errors`), `mod_rewrite`, droits d'écriture (`/`, `framework/logs|cache|uploads`), avec aide contextuelle si un point échoue.
- **Installation (AJAX)** : tâches séquentielles `env → database → schema → admin → modules → finalize` avec barre de progression.
  - `install/Installer.php` : moteur (`createDatabase`, `runSchema`, `createAdmin` [Argon2id], `seedDefaults`, `writeEnv`).
  - `install/schema.sql` : schéma complet.
  - Écrit `.env` à la racine (lu par `config/database.php` via `getenv`).
  - Crée `install/installed.lock` → empêche toute réinstallation.

> ⚠️ Après installation, supprimer le dossier `install/` en production. Tant qu'il existe, un bandeau discret le rappelle en haut de l'administration.

> `intl` n'est pas décoratif : il fournit les noms de pays localisés du filtrage géographique. Sans lui, l'interface retombe sur les codes ISO.

---

## Créer un module

**Le plus simple : le générateur.** `/admin/modules/generate` crée un squelette complet et activable (manifeste + menu, classe, routes, contrôleur dashboard + sections, service, `database/install.sql` + `uninstall.sql` + `migrations/`, changelog, vues `.ui-*`).

Répondre **oui** à « ce module aura-t-il une partie publique ? » produit en plus un **thème complet**, son administration (thème actif, téléversement ZIP, options générées depuis `meta.json`) et les pages visiteur — au choix : liste + fiche, avec pagination, avec recherche. Le préfixe public est **validé** : un segment réservé par le cœur ou déjà pris par un autre module est refusé, en nommant le module fautif.

> Le module **Exemple**, livré inactif, coche toutes les cases en état de marche : à lire comme référence.

**À la main :**

1. Créer `modules/MonModule/` avec `module.json` + `MonModule.php`.
2. Déclarer les routes dans `routes.php` (closure recevant `$router`).
3. Déclarer le menu dans `module.json` (clé `menu`) et la `category`.
4. Placer les vues dans `Views/` et utiliser `admin_header()` / `admin_footer()`.
5. Fournir `database/install.sql` (+ `uninstall.sql`), puis activer via la page **Modules**.

**Distribution :** un module se livre en **ZIP** (contenant le dossier avec son `module.json`) ; il s'installe via **Modules → « ⬆️ Installer (.zip) »** (extraction sécurisée), puis activation.

### Installation / désinstallation

- **Deux conventions de schéma** (`ModuleManager::runModuleSchema()`) :
  1. `database/install.sql` (+ `uninstall.sql`) — fichier unique ;
  2. `schema.sql` + `schema_*.sql` à la racine du module — exécutés dans l'ordre alphabétique, **chacun dans son propre try/catch**.
- À l'**activation**, le schéma est exécuté puis le hook PHP `install()` ; à la **désactivation**, `uninstall()` puis `database/uninstall.sql` s'il existe.
- ⚠️ **Sans `uninstall.sql`, la désactivation est non destructive** : les tables sont conservées.
- **Activation atomique** : `activateModule()` exécute le schéma, **vérifie que toutes les tables `CREATE TABLE` existent**, puis appelle `install()`. En cas d'échec, le module n'est **pas** activé, les tables partielles sont nettoyées et l'erreur est disponible via `getLastError()`. Garantit qu'on n'a jamais un module actif (menu visible) sans ses tables.

### Migrations (`database/migrations/*.sql`)

- `install.sql` = **baseline** (schéma complet pour une install fraîche). `database/migrations/NNN_xxx.sql` = étapes **incrémentales**.
- Suivi dans `module_migrations`. À l'activation, les migrations présentes sont marquées appliquées (baseline). `ModuleManager::updateModule($name)` rejoue les migrations en attente et synchronise la version. `pendingMigrationCount($name)` indique s'il en reste.
- Nommage : préfixe ordonnable, ex. `2026_05_31_001_add_column.sql`.
- **Écrire des migrations rejouables** (`IF NOT EXISTS`, vérification via `information_schema`) : une migration appliquée à la main puis rejouée échouerait sur « Column already exists ».

### Exécution SQL (dumps complexes)

`ModuleManager` exécute les scripts **statement par statement** : prise en charge de **`DELIMITER`** (triggers / procédures à corps `BEGIN…END`) et désactivation de **`FOREIGN_KEY_CHECKS`** le temps du script (clés étrangères « en avant », typiques des dumps phpMyAdmin / mysqldump). Un module peut donc fournir un dump complet comme `database/install.sql`.

---

## Conventions

- **Aucune dépendance externe** dans le back-office (pas de Bootstrap/jQuery). Iconographie : emoji.
- **Un contrôleur + un service par fonctionnalité**. Ex : RGPD = `RgpdController` + `RgpdService`.
- **Interfaces simples** : pas d'éditeurs techniques (codes, JSON brut) sur les écrans courants.
- Échapper toute sortie utilisateur (`htmlspecialchars`).
- Requêtes SQL **toujours préparées**.
- Menus en **JSON**, jamais en dur. Un module peut ajouter ses entrées sous le groupe d'un autre.
- Pages admin via les **helpers** de thème, jamais d'`include` de chemin en dur.
- Modules cœur (`core: true`) : Auth, Configuration, System — non désactivables. **Analytics n'est pas cœur** : tout code qui en dépend doit rester fonctionnel quand il est désactivé.
- Une capacité **améliore**, elle ne conditionne pas : `function_exists()` avant usage.
- Un thème ne contient **aucun PHP**.
- **Ne journaliser que l'essentiel** (voir [Journalisation](#journalisation)).
- **Documentation** : à chaque modification/ajout, déposer un fichier dans `framework/changelog/` **et** mettre à jour `FRAMEWORK.md`.
