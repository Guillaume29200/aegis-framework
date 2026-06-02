<?php
declare(strict_types=1);

/**
 * Aegis Framework V4 - Point d'entrée principal
 * 
 * Architecture modulaire avec système de routing automatique.
 * Toute l'initialisation est dans bootstrap.php
 * 
 * @author Guillaume
 * @version 4.0.0
 */

// ============================================
// CHARGEMENT DU BOOTSTRAP
// ============================================
require_once __DIR__ . '/framework/bootstrap.php';

// ============================================
// ROUTING (WEB UNIQUEMENT)
// ============================================
if (IS_CLI) {
    die("Ce fichier est reserve aux requetes HTTP.\n");
}

use Framework\Services\Router;

try {
    // ─────────────────────────────────────────
    // Router
    // ─────────────────────────────────────────
    $router = new Router();
    
    // Injecter les dépendances pour les contrôleurs
    $router->setDependencies([
        'Database' => $db,
        'CSRFProtection' => $csrfProtection,
        'XSSProtection' => $xssProtection,
        'RateLimiter' => $rateLimiter,
        'SessionManager' => $sessionManager,
        'Logger' => $logger,
        'DebugBar' => $debugBar,
        'ModuleManager' => $moduleManager,
        'SecurityFirewallService' => $securityFirewallService,
        'SecurityCenterService' => $securityCenterService,
        // Avec namespaces complets
        'Framework\Services\Database' => $db,
        'Framework\Security\CSRFProtection' => $csrfProtection,
        'Framework\Security\XSSProtection' => $xssProtection,
        'Framework\Security\RateLimiter' => $rateLimiter,
        'Framework\Security\SessionManager' => $sessionManager,
        'Framework\Services\Logger' => $logger,
        'Framework\Services\DebugBar' => $debugBar,
        'Framework\ModuleManager\ModuleManager' => $moduleManager,
        'Framework\Services\SecurityFirewallService' => $securityFirewallService,
        'Framework\Services\SecurityCenterService' => $securityCenterService,
    ]);

    // ─────────────────────────────────────────
    // Garde CSRF centralisée (toutes requêtes POST/PUT/PATCH/DELETE)
    // ─────────────────────────────────────────
    $router->enableCsrfGuard(
        $csrfProtection,
        $securityConfig['csrf']['except'] ?? []
    );

    // ============================================
    // ENREGISTREMENT DES ROUTES
    // ============================================
    
    // 1. Routes des modules (chargées automatiquement)
    foreach ($moduleManager->getLoadedModules() as $module) {
        $module->registerRoutes($router);
    }
    
    // 2. Routes système (racine)
    require ROOT_PATH . '/routes.php';
    
    // ============================================
    // DEBUG BAR
    // ============================================
    // ============================================
    // DISPATCH DE LA REQUÊTE
    // ============================================
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = $_SERVER['REQUEST_URI'];
    
    // Retirer le base path si sous-dossier
    $scriptName = dirname($_SERVER['SCRIPT_NAME']);
    if ($scriptName !== '/' && strpos($uri, $scriptName) === 0) {
        $uri = substr($uri, strlen($scriptName));
    }
    
    // S'assurer que l'URI commence par /
    if (empty($uri) || $uri[0] !== '/') {
        $uri = '/' . $uri;
    }

    // ============================================
    // GARDE D'AUTHENTIFICATION — ZONE /admin
    // ============================================
    // Toute la zone d'administration exige une session "staff" (admin / superadmin
    // / moderator). Bloque les anonymes ET les membres simples avant le dispatch,
    // donc avant toute logique de contrôleur (ban IP, SSH, etc.).
    $gatePath = strtok($uri, '?');
    if (preg_match('#^/admin(?:/|$)#', $gatePath)) {
        $isXhr = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
              && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $loggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
        $role = $_SESSION['role'] ?? '';
        $isStaff = in_array($role, ['admin', 'superadmin', 'moderator'], true);

        if (!$loggedIn || !$isStaff) {
            if ($isXhr) {
                http_response_code(!$loggedIn ? 401 : 403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => !$loggedIn ? 'Non authentifié' : 'Accès refusé']);
            } elseif (!$loggedIn) {
                header('Location: ' . u('/auth/login'));
            } else {
                http_response_code(403);
                echo '<!doctype html><meta charset="utf-8"><title>403</title>'
                   . '<div style="font-family:system-ui;max-width:480px;margin:80px auto;text-align:center">'
                   . '<h1 style="font-size:48px;margin:0">403</h1><p>Accès réservé à l\'administration.</p>'
                   . '<p><a href="' . u('/') . '">← Retour à l\'accueil</a></p></div>';
            }
            exit;
        }
    }

    // Buffer de sortie
    ob_start();
    $debugBar->mark('router.dispatch.start');
    $router->dispatch($method, $uri);
    $debugBar->mark('router.dispatch.end');
    $output = ob_get_clean();
    
    // ============================================
    // INJECTION DEBUG BAR
    // ============================================
    if ($config['debug_bar']) {
        $debugBar->importQueries($db->getQueryLog());

        $contentType = '';
        foreach (headers_list() as $header) {
            if (stripos($header, 'Content-Type') !== false) {
                $contentType = $header;
                break;
            }
        }
        
        $trimmedOutput = ltrim($output);
        $looksLikeJson = $trimmedOutput !== '' && in_array($trimmedOutput[0], ['{', '['], true);
        $isApiRoute = str_starts_with($uri, '/api/');

        // Ne pas injecter pour JSON/API, meme si le header n'est pas encore visible.
        if (stripos($contentType, 'application/json') === false && !$looksLikeJson && !$isApiRoute) {
            $debugBarHtml = $debugBar->render();
            
            if (strpos($output, '</body>') !== false) {
                $output = str_replace('</body>', $debugBarHtml . '</body>', $output);
            } else {
                $output .= $debugBarHtml;
            }
        }
    }
    
    // ============================================
    // SORTIE FINALE
    // ============================================
    echo $output;
    
} catch (\Exception $e) {
    $logger->critical('Unhandled exception', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    throw $e;
}
