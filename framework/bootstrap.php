<?php
declare(strict_types=1);

/**
 * Aegis Framework V4 - Bootstrap
 * 
 * Fichier d'initialisation de l'application.
 * Chargé par index.php (web) et les scripts CRON (CLI).
 * 
 * @author Guillaume
 * @version 4.0.0-alpha.10
 */

// ============================================
// PROTECTION CONTRE DOUBLE CHARGEMENT
// ============================================
if (defined('AEGIS_FRAMEWORK')) {
    return;
}

// ============================================
// CONSTANTES FONDAMENTALES
// ============================================
define('AEGIS_FRAMEWORK', true);
define('ROOT_PATH', dirname(__DIR__)); // /framework -> racine

// Détecter le mode CLI
define('IS_CLI', php_sapi_name() === 'cli');

// ============================================
// CONFIGURATION ERREURS (temporaire)
// ============================================
error_reporting(E_ALL);
ini_set('display_errors', IS_CLI ? '1' : '0');

// ============================================
// URL HELPERS (uniquement en mode web)
// ============================================
if (!IS_CLI) {
    $scriptName = dirname($_SERVER['SCRIPT_NAME']);
    $basePath = ($scriptName !== '/' && $scriptName !== '\\') ? $scriptName : '';
    define('BASE_URL', $basePath);
} else {
    define('BASE_URL', '');
}

/**
 * Générer une URL relative au base path
 */
function url(string $path): string {
    if (empty($path) || $path[0] !== '/') {
        $path = '/' . $path;
    }
    return BASE_URL . $path;
}

/**
 * Afficher une URL (echo)
 */
function e_url(string $path): void {
    echo url($path);
}

/**
 * Helper court - Générer URL
 */
function u(string $path): string {
    return url($path);
}

/**
 * Helper court - Echo URL
 */
function e(string $path): void {
    echo url($path);
}

/**
 * Base path uniquement
 */
function base_uri(): string {
    return BASE_URL;
}

/**
 * Rediriger vers une URL
 */
function redirect(string $path): never {
    header('Location: ' . url($path));
    exit;
}

/**
 * Un module est-il actif (chargé) ? Sert à masquer les liens vers des modules
 * non installés/désactivés (ex. menu membre). Insensible à la casse.
 */
function module_active(string $name): bool {
    $mm = $GLOBALS['moduleManager'] ?? null;
    if ($mm === null || !method_exists($mm, 'getLoadedModules')) {
        return false;
    }
    foreach (array_keys($mm->getLoadedModules()) as $loaded) {
        if (strcasecmp((string)$loaded, $name) === 0) {
            return true;
        }
    }
    return false;
}

// ============================================
// THÈME ADMIN — shell + helpers
// ============================================
// Emplacement unique du shell admin. Les pages ne référencent jamais le chemin :
// elles appellent admin_header()/admin_footer(). Déplacer le thème = changer
// uniquement cette constante.
define('ADMIN_THEME_PATH', ROOT_PATH . '/framework/Views/theme/admin');

/**
 * Ouvrir le shell d'administration (en-tête, sidebar, header).
 *
 * @param string $pageTitle Titre de la page (onglet + header).
 * @param array  $context   Variables additionnelles exposées au shell (ex: currentUser).
 */
function admin_header(string $pageTitle = 'Administration', array $context = []): void {
    extract($context, EXTR_SKIP);
    require ADMIN_THEME_PATH . '/header.php';
}

/**
 * Fermer le shell d'administration (panneau de contrôle, scripts).
 */
function admin_footer(): void {
    require ADMIN_THEME_PATH . '/footer.php';
}

// ============================================
// AUTOLOADER COMPOSER
// ============================================
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require ROOT_PATH . '/vendor/autoload.php';
}

// ============================================
// VARIABLES D'ENVIRONNEMENT (.env)
// ============================================
if (file_exists(ROOT_PATH . '/.env')) {
    $env = parse_ini_file(ROOT_PATH . '/.env');
    foreach ($env as $key => $value) {
        putenv("{$key}={$value}");
    }
}

// ============================================
// CONFIGURATIONS
// ============================================
$envConfig = require ROOT_PATH . '/framework/config/environment.php';
$dbConfig = require ROOT_PATH . '/framework/config/database.php';
$securityConfig = require ROOT_PATH . '/framework/config/security.php';

// Helpers
require_once ROOT_PATH . '/framework/Helpers/RecaptchaHelper.php';
require_once ROOT_PATH . '/framework/Helpers/CacheHelper.php';
require_once ROOT_PATH . '/framework/Helpers/AIModelsHelper.php';

// Configuration environnement actuel
$currentEnv = $envConfig['environment'];
$config = $envConfig[$currentEnv];

// Appliquer configuration PHP
ini_set('display_errors', $config['display_errors'] ? '1' : '0');
error_reporting($config['error_reporting']);
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Europe/Paris');

// ============================================
// AUTOLOADER FRAMEWORK & MODULES
// ============================================
spl_autoload_register(function ($class) {
    $class = str_replace('\\', '/', $class);
    
    // Framework
    if (strpos($class, 'Framework/') === 0) {
        $class = str_replace('Framework/', 'framework/', $class);
        $file = ROOT_PATH . '/' . $class . '.php';
    }
    // Modules avec namespace Modules\
    elseif (strpos($class, 'Modules/') === 0) {
        $class = substr($class, strlen('Modules/'));
        $file = ROOT_PATH . '/modules/' . $class . '.php';
    }
    // Modules sans namespace Modules\
    else {
        $file = ROOT_PATH . '/modules/' . $class . '.php';
    }
    
    if (file_exists($file)) {
        require $file;
    }
});

// ============================================
// GESTION D'ERREURS
// ============================================
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function ($exception) use ($config) {
    error_log(sprintf(
        "[ERROR] %s in %s:%d",
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
    ));

    if (IS_CLI) {
        fwrite(STDERR, "ERROR: " . $exception->getMessage() . "\n");
        fwrite(STDERR, "File: " . $exception->getFile() . ":" . $exception->getLine() . "\n");
        exit(1);
    }

    $isCsrf = $exception instanceof \Framework\Security\CSRFException;

    if ($isCsrf) {
        http_response_code(403);
        $icon    = '🔄';
        $color   = '#f59e0b';
        $heading = 'Session expirée';
        $message = 'Votre session a expiré ou vous avez soumis un formulaire depuis un onglet trop ancien. Rechargez la page et réessayez.';
        $btnText = 'Recharger la page';
        $btnHref = 'javascript:history.back()';
    } elseif ($exception->getCode() === 404) {
        http_response_code(404);
        $icon    = '🔍';
        $color   = '#6366f1';
        $heading = 'Page introuvable';
        $message = 'La page que vous cherchez n\'existe pas ou a été déplacée.';
        $btnText = 'Retour au dashboard';
        $btnHref = BASE_URL . '/admin/dashboard';
    } else {
        http_response_code(500);
        $icon    = '⚙️';
        $color   = '#ef4444';
        $heading = 'Une erreur est survenue';
        $message = $config['display_errors']
            ? htmlspecialchars($exception->getMessage())
            : 'Une erreur inattendue s\'est produite. Veuillez réessayer.';
        $btnText = 'Retour au dashboard';
        $btnHref = BASE_URL . '/admin/dashboard';
    }

    $trace = '';
    if ($config['display_errors'] && !$isCsrf) {
        $trace = '<div class="debug">'
            . htmlspecialchars($exception->getFile() . ':' . $exception->getLine() . "\n" . $exception->getTraceAsString())
            . '</div>';
    }

    echo <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aegis Framework — {$heading}</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#0f172a;color:#e2e8f0;min-height:100vh;display:flex;align-items:center;justify-content:center}
        .card{background:#1e293b;border:1px solid #334155;border-radius:16px;padding:3rem;max-width:500px;width:90%;text-align:center;box-shadow:0 25px 50px rgba(0,0,0,.5)}
        .icon{font-size:4rem;margin-bottom:1.5rem}
        .bar{display:inline-block;width:48px;height:4px;background:{$color};border-radius:2px;margin-bottom:1.5rem}
        h1{font-size:1.5rem;font-weight:700;margin-bottom:1rem;color:#f1f5f9}
        p{color:#94a3b8;line-height:1.6;margin-bottom:2rem}
        .btn{display:inline-block;background:{$color};color:#fff;padding:.75rem 2rem;border-radius:8px;text-decoration:none;font-weight:600;font-size:1rem;transition:opacity .2s}
        .btn:hover{opacity:.85}
        .debug{margin-top:2rem;text-align:left;background:#0f172a;border-radius:8px;padding:1rem;font-size:.72rem;color:#64748b;overflow-x:auto;white-space:pre-wrap;word-break:break-all}
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">{$icon}</div>
        <div class="bar"></div>
        <h1>{$heading}</h1>
        <p>{$message}</p>
        <a href="{$btnHref}" class="btn">{$btnText}</a>
        {$trace}
    </div>
</body>
</html>
HTML;
    exit;
});

// ============================================
// INITIALISATION DES SERVICES
// ============================================
use Framework\Services\Database;
use Framework\Services\DebugBar;
use Framework\Services\Logger;
use Framework\Services\CacheService;
use Framework\Services\SecurityFirewallService;
use Framework\Services\SecurityCenterService;
use Framework\Security\SessionManager;
use Framework\Security\CSRFProtection;
use Framework\Security\XSSProtection;
use Framework\Security\RateLimiter;
use Framework\ModuleManager\ModuleManager;
use Framework\Services\RecaptchaService;
use Framework\Middleware\MaintenanceMode;
use Framework\Middleware\SecurityHeaders;
use Framework\Middleware\SecurityFirewall;
use Framework\Middleware\SecurityCenterDetector;

// ─────────────────────────────────────────
// Database
// ─────────────────────────────────────────
$db = new Database($dbConfig, $config);

// ─────────────────────────────────────────
// Logger
// ─────────────────────────────────────────
$logger = new Logger($db, $config);

// ─────────────────────────────────────────
// Debug Bar (web uniquement)
// ─────────────────────────────────────────
$debugEnabled = false;
if (!IS_CLI) {
    try {
        $stmt = $db->getPDO()->query("SELECT param_value FROM settings WHERE param_key = 'debug_mode' LIMIT 1");
        $debugEnabled = $stmt ? (bool)$stmt->fetchColumn() : false;
    } catch (Exception $e) {
        $debugEnabled = false;
    }
}
$debugBar = new DebugBar($debugEnabled);

// Le réglage « Mode debug » (admin) est l'interrupteur maître : quand il est actif,
// il force l'affichage des erreurs et l'injection de la debug bar, quel que soit l'environnement.
if ($debugEnabled) {
    $config['debug_bar'] = true;
    $config['display_errors'] = true;
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

// ─────────────────────────────────────────
// TurboNav (web uniquement)
// ─────────────────────────────────────────
if (!IS_CLI) {
    try {
        $stmt = $db->getPDO()->query("SELECT param_value FROM settings WHERE param_key = 'turbonav_enabled' LIMIT 1");
        $turboNavEnabled = $stmt ? (bool)$stmt->fetchColumn() : false;
    } catch (Exception $e) {
        $turboNavEnabled = false;
    }
    define('TURBONAV_ENABLED', $turboNavEnabled);
} else {
    define('TURBONAV_ENABLED', false);
}

// ─────────────────────────────────────────
// Réglages de session pilotés depuis l'admin (Configuration → Sessions).
// Surcharge la config par défaut de security.php si présent en base.
// ─────────────────────────────────────────
if (!IS_CLI) {
    try {
        $rows = $db->getPDO()->query(
            "SELECT param_key, param_value FROM settings WHERE param_key IN (
                'session_idle_minutes','session_regenerate_minutes','session_ip_binding',
                'session_idle_logout','session_warn_seconds','session_remember_enabled','session_remember_days'
            )"
        )->fetchAll(\PDO::FETCH_KEY_PAIR);

        if (!empty($rows['session_idle_minutes'])) {
            $securityConfig['session']['gc_maxlifetime'] = max(1, (int)$rows['session_idle_minutes']) * 60;
        }
        if (!empty($rows['session_regenerate_minutes'])) {
            $securityConfig['session']['regenerate_interval'] = max(1, (int)$rows['session_regenerate_minutes']) * 60;
        }
        if (!empty($rows['session_ip_binding']) && in_array($rows['session_ip_binding'], ['off','subnet','strict'], true)) {
            $securityConfig['session']['ip_binding'] = $rows['session_ip_binding'];
        }
        // Exposé aux vues (front : modal d'inactivité) — valeurs effectives.
        $GLOBALS['session_ui'] = [
            'idle_logout'      => isset($rows['session_idle_logout']) ? (bool)(int)$rows['session_idle_logout'] : true,
            'idle_seconds'     => (int)($securityConfig['session']['gc_maxlifetime']),
            'warn_seconds'     => isset($rows['session_warn_seconds']) ? max(10, (int)$rows['session_warn_seconds']) : 60,
            'remember_enabled' => isset($rows['session_remember_enabled']) ? (bool)(int)$rows['session_remember_enabled'] : true,
            'remember_days'    => isset($rows['session_remember_days']) ? max(1, (int)$rows['session_remember_days']) : 30,
        ];
    } catch (\Throwable $e) {
        // Table/colonnes absentes → on garde les défauts de security.php.
    }
}

// ─────────────────────────────────────────
// Security Services
// ─────────────────────────────────────────
$sessionManager = new SessionManager($securityConfig);
if (!IS_CLI) {
    $sessionManager->start();
}
$GLOBALS['sessionManager'] = $sessionManager;

$csrfProtection = new CSRFProtection($securityConfig);
$xssProtection = new XSSProtection($securityConfig);
$rateLimiter = new RateLimiter($db, $securityConfig);
$securityFirewallService = new SecurityFirewallService($db, $securityConfig);
$securityCenterService = new SecurityCenterService($db, $securityFirewallService);
$GLOBALS['securityCenterService'] = $securityCenterService; // accès aux modules (hooks de détection)
// Pont : les détections de rate-limit/flood du firewall alimentent le score du Centre.
$securityFirewallService->setDetectionSink(static function (string $ip, string $ruleKey, string $reason) use ($securityCenterService): void {
    $securityCenterService->recordEvent($ip, $ruleKey, $reason);
});

// ─────────────────────────────────────────
// Maintenance Mode (web uniquement)
// ─────────────────────────────────────────
if (!IS_CLI) {
    $securityHeaders = new SecurityHeaders($securityConfig['headers'] ?? []);
    $securityHeaders->handle();

    $securityFirewall = new SecurityFirewall($securityFirewallService);
    $securityFirewall->handle();

    // Détection catégorisée + scoring (Centre de sécurité) — après le firewall.
    $securityCenterDetector = new SecurityCenterDetector($securityCenterService, $securityFirewallService);
    $securityCenterDetector->handle();

    $maintenanceMode = new MaintenanceMode($db, $sessionManager, $csrfProtection);
    $maintenanceMode->handle();
    
    // Filtrer XSS sur superglobales
    $xssProtection->filterGlobals();
}

// ─────────────────────────────────────────
// reCAPTCHA Service
// ─────────────────────────────────────────
$recaptchaService = new RecaptchaService($securityConfig, $db);

// ─────────────────────────────────────────
// Cache Service
// ─────────────────────────────────────────
try {
    $cacheStmt = $db->getPDO()->query("
        SELECT param_key, param_value 
        FROM settings 
        WHERE param_key IN ('cache_enabled', 'cache_ttl')
    ");
    $cacheSettings = [];
    while ($row = $cacheStmt->fetch(\PDO::FETCH_ASSOC)) {
        $cacheSettings[$row['param_key']] = $row['param_value'];
    }
    
    $cacheConfig = [
        'cache_enabled' => (bool)($cacheSettings['cache_enabled'] ?? false),
        'cache_ttl' => (int)($cacheSettings['cache_ttl'] ?? 3600),
        'cache_dir' => ROOT_PATH . '/framework/cache'
    ];
} catch (Exception $e) {
    $cacheConfig = [
        'cache_enabled' => false,
        'cache_ttl' => 3600,
        'cache_dir' => ROOT_PATH . '/framework/cache'
    ];
}

$cache = new CacheService($cacheConfig);
$GLOBALS['cache'] = $cache;

// ─────────────────────────────────────────
// Module Manager
// ─────────────────────────────────────────
$moduleManager = new ModuleManager(
    $db,
    $logger,
    ROOT_PATH . '/modules'
);

// ─────────────────────────────────────────
// Container Global
// ─────────────────────────────────────────
$GLOBALS['container'] = new class(
    $db, 
    $csrfProtection, 
    $xssProtection, 
    $rateLimiter, 
    $sessionManager, 
    $logger, 
    $debugBar,
    $recaptchaService,
    $moduleManager,
    $securityFirewallService
) {
    private array $services = [];
    
    public function __construct(...$services) {
        $this->services = [
            'db' => $services[0],
            'csrf' => $services[1],
            'xss' => $services[2],
            'rateLimiter' => $services[3],
            'session' => $services[4],
            'logger' => $services[5],
            'debugBar' => $services[6],
            'recaptcha' => $services[7],
            'moduleManager' => $services[8],
            'securityFirewall' => $services[9],
            // Avec namespaces complets
            'Framework\Services\Database' => $services[0],
            'Framework\Security\CSRFProtection' => $services[1],
            'Framework\Security\XSSProtection' => $services[2],
            'Framework\Security\RateLimiter' => $services[3],
            'Framework\Security\SessionManager' => $services[4],
            'Framework\Services\Logger' => $services[5],
            'Framework\Services\DebugBar' => $services[6],
            'Framework\Services\RecaptchaService' => $services[7],
            'Framework\ModuleManager\ModuleManager' => $services[8],
            'Framework\Services\SecurityFirewallService' => $services[9],
        ];
    }
    
    public function get(string $name) {
        return $this->services[$name] ?? null;
    }
};

// ─────────────────────────────────────────
// Headers de sécurité (web uniquement)
// ─────────────────────────────────────────
// ─────────────────────────────────────────
// Charger les modules
// ─────────────────────────────────────────
$moduleManager->loadModules();

// ============================================
// VARIABLES GLOBALES POUR RÉTROCOMPATIBILITÉ
// ============================================
// Ces variables sont disponibles pour les scripts qui incluent bootstrap.php
$GLOBALS['db'] = $db;
$GLOBALS['logger'] = $logger;
$GLOBALS['config'] = $config;
$GLOBALS['securityConfig'] = $securityConfig;
$GLOBALS['csrfProtection'] = $csrfProtection;
$GLOBALS['sessionManager'] = $sessionManager;
$GLOBALS['moduleManager'] = $moduleManager;
$GLOBALS['debugBar'] = $debugBar;
