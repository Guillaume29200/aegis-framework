# Aegis Framework — Base de connaissances de l'assistant IA

Ce document est injecté automatiquement dans le contexte de l'assistant IA à chaque message. Il décrit l'architecture d'Aegis Framework pour que l'assistant réponde avec des informations exactes plutôt que des suppositions génériques.

## Documentation officielle

Aegis Framework a une documentation officielle en ligne, à privilégier chaque fois qu'une réponse précise et à jour est nécessaire, ou quand une information ne se trouve pas dans ce primer :

- Portail général : https://gamenodepanel.com/documentation/documentation.php
- Vue d'ensemble du framework : https://gamenodepanel.com/documentation/framework/doc-aegis.php
- Démarrage rapide : https://gamenodepanel.com/documentation/framework/doc-quickstart.php
- Configuration : https://gamenodepanel.com/documentation/framework/doc-config.php
- Routeur : https://gamenodepanel.com/documentation/framework/doc-router.php
- Services : https://gamenodepanel.com/documentation/framework/doc-services.php
- Security Center : https://gamenodepanel.com/documentation/framework/doc-security-center.php
- Migrations : https://gamenodepanel.com/documentation/framework/doc-migrations.php
- TurboNav : https://gamenodepanel.com/documentation/framework/doc-turbonav.php

**Consigne pour l'assistant** : si une question porte sur un comportement précis du framework (routeur, migrations, sécurité, TurboNav, configuration) et que ce primer ne suffit pas à répondre avec certitude, propose explicitement le lien de documentation officielle correspondant plutôt que d'inventer une réponse. Ne jamais présenter une supposition comme un fait vérifié sur le framework.

---

## 1. Vue d'ensemble

Aegis Framework est un CMS/panel d'administration modulaire en PHP 8.5, sans dépendance front-end externe pour l'admin (pas de CDN, design system CSS maison). Architecture : un **core** (`framework/`) qui fournit les services partagés (base de données, sécurité, sessions, notifications, IA, etc.) et un système de **modules** (`modules/`) qui apportent chacun une fonctionnalité verticale (tournois, forum, marketplace, sauvegardes...). Le routage, le thème admin et l'assistant IA (cette conversation) sont pré-intégrés au core — ils fonctionnent même si aucun module tiers n'est installé.

Racine du projet : `C:\wamp64\www\v4_beta`. Point d'entrée web : `index.php`, qui charge `framework/bootstrap.php` puis enregistre les routes (modules actifs, puis `routes.php` racine pour les routes core).

## 2. framework/ — le core

### 2.1 framework/Services/ (logique métier partagée)

- **Database.php** — wrapper PDO (MySQL/PostgreSQL/SQLite), `query()`/`execute()`/`queryOne()`, connexion paresseuse, log des requêtes.
- **AIClientService.php** — client IA multi-provider unifié (OpenAI/Claude/Mistral), `chat(messages, options)` et `complete(prompt, options)`, ne lève jamais d'exception (retourne `['success'=>false,'error'=>...]`).
- **AuditService.php** — journal d'audit des actions admin sensibles (table `cms_audit_log`), helper statique `AuditService::record($action, $targetType, $targetId, $summary)`.
- **ModerationService.php** — file de signalements de contenu générique (table `cms_reports`), réutilisable par tout module (livre d'or, commentaires, forum, avis...). Types actuellement enregistrés : guestbook, blog_comment, blog_post, forum_post, forum_topic, profile, marketplace_review.
- **NotificationService.php** — cloche de notifications in-app par utilisateur.
- **SecretBox.php** — chiffrement AES-256-CBC pour les secrets (clés API...), clé stockée hors webroot (`framework/storage/secrets/.enckey`).
- **Router.php** — enregistrement de routes (GET/POST/PUT/DELETE, `{param}`, groupes, middleware, garde CSRF intégrée).
- **SessionManager.php** (dans framework/Security/) et les autres services de sécurité — voir 2.2.
- **AdminMenuService.php** — agrège le menu latéral admin (core + `menu` de chaque module.json actif).
- **CacheService.php** — cache fichier clé/valeur avec TTL.
- **GeolocService.php** — géolocalisation IP avec bascule multi-fournisseur automatique.
- **Logger.php** — logs applicatifs (DEBUG/INFO/WARNING/ERROR/CRITICAL/SECURITY).
- **SecurityCenterService.php** / **SecurityFirewallService.php** — scoring de menace par IP, blocage automatique, centre de sécurité.
- **RecaptchaService.php**, **ImageOptimizer.php**, **DeviceDetector.php**, **AuthTracker.php**, **DebugBar.php**, **LicenseGuard.php** (pont vers un futur module Licences, permissif par défaut si absent).

### 2.2 framework/Security/

- **CSRFProtection.php** — protection CSRF par jetons, pool de jetons valides par session.
- **XSSProtection.php** — échappement contextuel (`escape()/escapeAttr()/escapeJs()/escapeUrl()`).
- **RateLimiter.php** — limitation de débit par IP/utilisateur, blocage temporaire.
- **SessionManager.php** — cycle de vie sécurisé des sessions (mode strict, régénération périodique, anti-fixation, empreinte navigateur).

### 2.3 framework/ModuleManager/

**ModuleManager.php** — gestion du cycle de vie des modules : découverte (`modules/*/module.json`), chargement, vérification des dépendances, activation/désactivation, migrations. Convention de schéma par module : `database/install.sql` (baseline, exécutée une fois à l'activation), `database/uninstall.sql` (DROP, à la désactivation), `database/migrations/*.sql` (incrémental, suivi dans la table `module_migrations`). **Important** : `install.sql` doit toujours représenter l'état actuel et complet du module (schéma + données de seed) — une réinstallation fraîche marque les migrations existantes comme déjà appliquées sans les rejouer.

### 2.4 framework/Interfaces/

- **ModuleInterface.php** — contrat qu'une classe de module doit implémenter (`getName()`, `getVersion()`, `getDescription()`, `registerRoutes()`, `install()`, `uninstall()`...).
- **BaseModule.php** — classe abstraite avec valeurs par défaut sensées, fournit aussi `getAdminMenu()` (déclaratif via module.json), `loadView()`, `getConfig()`.

### 2.5 framework/Helpers/ (fonctions globales)

- **AIClientHelper.php** — `ai_chat()`, `ai_complete()`.
- **AIModelsHelper.php** — `ai_get_models()`, `ai_get_default_model()`, `ai_model_display_name()`, `ai_provider_icon()`.
- **CacheHelper.php** — `cache()`, `cache_get()`, `cache_set()`, `cache_remember()`.
- **RecaptchaHelper.php**, **MarkdownHelper.php**, **RgpdHelper.php**, **SeoHelper.php**, **AnalyticsHelper.php** — helpers de capacité optionnelle par module (voir 2.7).

### 2.6 framework/bootstrap.php et fonctions globales

Chargé une fois par requête, séquence : constantes → autoload → config (environment/database/security) → gestion d'erreurs → services core (Database, Logger, SessionManager, CSRF, XSS, RateLimiter, SecurityCenter) → middlewares (headers sécurité, firewall, maintenance) → conteneur DI (`$GLOBALS['container']`) → `ModuleManager::loadModules()` (charge les modules actifs et enregistre leurs routes).

Fonctions globales définies ici :
- `u($path)` / `url($path)` — préfixe une URL avec `BASE_URL`.
- `redirect($path)` — redirection HTTP puis `exit`.
- `module_active($name)` — vérifie si un module est actuellement actif (insensible à la casse).
- `admin_header($title, $context)` / `admin_footer()` — ouvre/ferme le thème admin partagé (`framework/Views/theme/admin/`).

### 2.7 framework/Capabilities/

Système de « capacités » optionnelles qu'un module peut déclarer dans son `module.json` (markdown, cache, ai, seo, rgpd, analytics, recaptcha) — le `CapabilityManager` charge automatiquement le helper correspondant. `Markdown/MarkdownService.php` est le moteur de rendu Markdown partagé (avec extensions type BBCode : spoiler, quote, youtube, couleur, emoji).

### 2.8 framework/Middleware/

`SecurityHeaders.php`, `SecurityFirewall.php`, `SecurityCenterDetector.php`, `MaintenanceMode.php` — exécutés tôt dans `bootstrap.php`, avant les contrôleurs.

### 2.9 Autres répertoires

- **framework/Views/theme/admin/** — thème admin partagé (header.php/footer.php), design system CSS (`.ui-*`, `.form-*`, `.alert-*`, `.adm-*`) dans `framework/assets/css/admin/ui.css` + `compat.css` (classes Bootstrap-like `.form-control`/`.form-select`/`.alert-*` pour les formulaires — **pas** `.ui-input`/`.ui-notice`, qui n'existent pas globalement).
- **framework/config/** — `environment.php` (dev/staging/prod), `database.php`, `security.php` (session, CSRF, etc.), toutes les valeurs viennent de `.env` via `getenv()`.
- **framework/storage/** — stockage persistant hors webroot (`secrets/.enckey`, `backups/`).
- **framework/data/** — données de référence du core (ce fichier).
- **install/schema.sql** — schéma des tables core (pas dans un module) : `users`, `settings`, `modules`, `module_migrations`, `logs`, `cms_audit_log`, `cms_notifications`, `cms_chat_conversations`/`cms_chat_messages` (cette conversation).

### 2.9 Préfixe public d'un module

Un module qui expose un espace visiteur le déclare dans son `module.json` :

```json
"public": {
  "prefix": "gamenodeesport",
  "label":  "Site public",
  "hint":   "Adresse de la partie visiteur : accueil, équipes, actualités…"
}
```

Ce préfixe est **canonique** : le module écrit toujours `/gamenodeesport/equipes`,
dans `routes.php` comme dans ses appels à `u()`. L'administrateur peut lui
substituer une autre adresse — `/site`, `/web`, `/communaute` — depuis
`/admin/modules`, sans qu'une seule ligne du module ne change.

La substitution a lieu à deux endroits, et deux seulement :

- **`Router::addRoute()`** — les chemins déclarés à l'enregistrement des routes ;
- **`url()`** — les chemins produits par `u()`, `e()` et `redirect()`.

Conséquences pour qui écrit un module :

- Toute URL publique doit passer par `u()`. Concaténer un chemin après
  `u('/')` contourne la traduction et produit une adresse morte.
- Le préfixe **d'administration ne bouge jamais** : `/admin/{module}` reste
  `/admin/{module}`.
- Les anciennes adresses restent valides : une requête GET sur le préfixe
  canonique est redirigée en 301 vers l'adresse en vigueur.
- Les URL saisies à la main par l'administrateur (bouton de slider, contenu
  d'un widget) ne sont pas réécrites — c'est du contenu, pas du code. La
  redirection 301 les rattrape.

`Framework\Services\PublicPrefix` porte le registre : `translate()`,
`effective()`, `canonical()`, `renamed()` et `validate()`. Le préfixe choisi est
stocké dans `modules.public_prefix` ; une valeur vide signifie « suivre le
manifeste ».

## 3. Système de modules

Un module = un dossier sous `modules/` avec `module.json` (nom, version, description, catégorie, dépendances, menu admin) + une classe `NomDuModule.php` implémentant `ModuleInterface` + `Controllers/`, `Services/`, `Views/admin/`, `routes.php`, `database/`. Convention établie dans ce projet : **un contrôleur + un service par fonctionnalité** (pas de contrôleur fourre-tout), chaque contrôleur duplique sa propre petite méthode privée `requireAdmin()` (pas de classe de base partagée), flash messages via query string (`?flash_type=ok|err&flash_msg=...`, pas de session flash).

## 4. Modules installés

- **Auth** — authentification, inscription, rôles. Module fondamental (aucune dépendance), requis par presque tous les autres.
- **Configuration** — réglages globaux (système, sécurité, SEO, IA — clés API et modèles disponibles se configurent ici : `/admin/configuration`).
- **System** — colonne vertébrale admin : gestion des modules, centre de sécurité, diagnostic, monitoring, modération, journal d'audit, changelog.
- **Analytics** — analytics self-hosted sans cookies (visiteurs, pages, referrers).
- **Sauvegarde** — sauvegardes planifiées (locales + cloud S3), restauration.
- **Marketplace** — place de marché (thèmes/modules/jeux), paiement PayPal, anti-fraude, avis, promotions.
- **Forum** — forum complet (catégories, sondages, réputation/XP, shoutbox).
- **Tournaments** — gestion de tournois esport (brackets, équipes, gathers, véto de maps, tchat, classements, profils joueurs, blog).
- **FPSmeter** — monitoring FPS/perf de serveurs de jeu HLDS/SRCDS, détection de fournisseurs d'hébergement (GSP), anti-fraude.
- **GameNodeViewer** — affichage temps réel de l'état de serveurs de jeu (joueurs, carte, ping), console RCON.
- **Tickets** — support ticket lié aux serveurs de jeu (Game Node Panel).
- **AegisCoreIA** — module tiers (vendu séparément) : agents IA automatisés qui auditent d'autres modules (base de données, sauvegardes, tournois, modération) et produisent des rapports. **Distinct de cet assistant conversationnel**, qui est pré-intégré au core et ne dépend d'aucun module.

## 5. Sécurité

CSRF systématique sur toute action qui modifie l'état (jeton par formulaire/requête AJAX). Clés API et secrets chiffrés en base via `SecretBox` (AES-256-CBC), jamais stockés en clair. Chaque page admin vérifie `$_SESSION['logged_in']` et `$_SESSION['role'] ∈ {admin, superadmin}`. Le centre de sécurité (`SecurityCenterService`) score et bloque automatiquement les IP menaçantes.

## 6. IA intégrée

`AIClientService` (core) gère les appels vers OpenAI, Claude et Mistral de façon unifiée. Les clés API et modèles se configurent dans `/admin/configuration` (onglet IA) et `/admin/configuration/ai-models`. Un provider n'est utilisable que s'il a une clé API renseignée **et** au moins un modèle actif — sinon il est simplement absent des sélecteurs, jamais proposé grisé.
