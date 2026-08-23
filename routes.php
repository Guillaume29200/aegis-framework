<?php
/**
 * Aegis Framework V4 - Routes SystÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¨me
 * @version 4.0.0
 */

// PAGE D'ACCUEIL
$router->get('/', function() use ($container) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Page d'accueil par défaut configurable (admin → Configuration).
    // Si un module public est choisi, TOUT visiteur arrivant sur « / » y est redirigé.
    $landing = '';
    try {
        $db = $container->get('Framework\\Services\\Database');
        $landing = (string) (new \Configuration\Services\SettingsService($db))->get('default_landing', '');
    } catch (\Throwable $e) {
        $landing = '';
    }
    if ($landing !== '' && $landing !== 'auth') {
        redirect($landing);
        return;
    }

    // Repli : aucun module public choisi. Les administrateurs rejoignent
    // l'administration ; les membres n'ont plus d'espace dédié (chaque module
    // public gère le sien), on les renvoie donc sur la page de connexion.
    $isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    $role = $_SESSION['role'] ?? '';
    if ($isLoggedIn && in_array($role, ['admin', 'superadmin', 'moderator'], true)) {
        redirect('/admin/dashboard');
    } else {
        redirect('/auth/login');
    }
});

// AUTHENTIFICATION
$router->group('/auth', function($router) use ($container) {
    $router->get('/login', function() use ($container) {
        $db = $container->get('Framework\\Services\\Database');
        $csrf = $container->get('Framework\\Security\\CSRFProtection');
        $controller = new Auth\Controllers\AuthController($db, $csrf);
        $controller->showLogin();
    });
    
    $router->post('/login', function() use ($container) {
        $db = $container->get('Framework\\Services\\Database');
        $csrf = $container->get('Framework\\Security\\CSRFProtection');
        $controller = new Auth\Controllers\AuthController($db, $csrf);
        $controller->login();
    });
    
    $router->get('/logout', function() use ($container) {
        $db = $container->get('Framework\\Services\\Database');
        $csrf = $container->get('Framework\\Security\\CSRFProtection');
        $controller = new Auth\Controllers\AuthController($db, $csrf);
        $controller->logout();
    });
    
    $router->get('/register', function() use ($container) {
        $db = $container->get('Framework\\Services\\Database');
        $csrf = $container->get('Framework\\Security\\CSRFProtection');
        $controller = new Auth\Controllers\AuthController($db, $csrf);
        $controller->showRegister();
    });
    
    $router->post('/register', function() use ($container) {
        $db = $container->get('Framework\\Services\\Database');
        $csrf = $container->get('Framework\\Security\\CSRFProtection');
        $controller = new Auth\Controllers\AuthController($db, $csrf);
        $controller->register();
    });
});

// ADMIN
$router->group('/admin', function($router) use ($container) {
    
    // Dashboard
    $router->get('/dashboard', function() use ($container) {
        $db = $container->get('Framework\\Services\\Database');
        $csrf = $container->get('Framework\\Security\\CSRFProtection');
        $controller = new Auth\Controllers\AdminController($db, $csrf);
        $controller->dashboard();
    });
    
    // [Modules / Sécurité → déplacés dans le module System (modules/System/routes.php)]

    // Utilisateurs
    $router->get('/users', function() use ($container) {
        $db = $container->get('Framework\\Services\\Database');
        $csrf = $container->get('Framework\\Security\\CSRFProtection');
        $controller = new Auth\Controllers\AdminController($db, $csrf);
        $controller->users();
    });
    
    $router->get('/users/create', function() use ($container) {
        $db = $container->get('Framework\\Services\\Database');
        $csrf = $container->get('Framework\\Security\\CSRFProtection');
        $controller = new Auth\Controllers\AdminController($db, $csrf);
        $controller->showCreateUser();
    });
    
    $router->post('/users/store', function() use ($container) {
        $db = $container->get('Framework\\Services\\Database');
        $csrf = $container->get('Framework\\Security\\CSRFProtection');
        $controller = new Auth\Controllers\AdminController($db, $csrf);
        $controller->createUser();
    });
    
    $router->get('/users/{id}/edit', function($id) use ($container) {
        $db = $container->get('Framework\\Services\\Database');
        $csrf = $container->get('Framework\\Security\\CSRFProtection');
        $controller = new Auth\Controllers\AdminController($db, $csrf);
        $controller->showEditUser((int)$id);
    });
    
    $router->post('/users/{id}/update', function($id) use ($container) {
        $db = $container->get('Framework\\Services\\Database');
        $csrf = $container->get('Framework\\Security\\CSRFProtection');
        $controller = new Auth\Controllers\AdminController($db, $csrf);
        $controller->updateUser((int)$id);
    });
    
    $router->post('/users/{id}/delete', function($id) use ($container) {
        $db = $container->get('Framework\\Services\\Database');
        $csrf = $container->get('Framework\\Security\\CSRFProtection');
        $controller = new Auth\Controllers\AdminController($db, $csrf);
        $controller->deleteUser((int)$id);
    });
    
    // ParamÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¨tres
    $router->get('/settings', function() use ($container) {
        redirect('/admin/configuration');
    });
    
    $router->post('/settings/update', function() use ($container) {
        redirect('/admin/configuration');
    });
    
    // [Monitoring → déplacé dans le module System (modules/System/routes.php)]

    // Assistant IA (icône 🧠 du header) — pré-intégré au core, indépendant de tout module.
    $router->get('/chat', function() use ($container) {
        $db = $container->get('Framework\\Services\\Database');
        $csrf = $container->get('Framework\\Security\\CSRFProtection');
        $controller = new Framework\Controllers\ChatController($db, $csrf);
        $controller->index();
    });
    $router->get('/chat/{id}', function($id) use ($container) {
        $db = $container->get('Framework\\Services\\Database');
        $csrf = $container->get('Framework\\Security\\CSRFProtection');
        $controller = new Framework\Controllers\ChatController($db, $csrf);
        $controller->show((int)$id);
    });
    $router->post('/chat/send', function() use ($container) {
        $db = $container->get('Framework\\Services\\Database');
        $csrf = $container->get('Framework\\Security\\CSRFProtection');
        $controller = new Framework\Controllers\ChatController($db, $csrf);
        $controller->send();
    });
    $router->post('/chat/{id}/rename', function($id) use ($container) {
        $db = $container->get('Framework\\Services\\Database');
        $csrf = $container->get('Framework\\Security\\CSRFProtection');
        $controller = new Framework\Controllers\ChatController($db, $csrf);
        $controller->rename((int)$id);
    });
    $router->post('/chat/{id}/delete', function($id) use ($container) {
        $db = $container->get('Framework\\Services\\Database');
        $csrf = $container->get('Framework\\Security\\CSRFProtection');
        $controller = new Framework\Controllers\ChatController($db, $csrf);
        $controller->delete((int)$id);
    });
});

// INSTALLATION
$router->group('/install', function($router) {
    $installedFile = ROOT_PATH . '/.installed';
    
    if (file_exists($installedFile)) {
        $router->get('/', function() {
            header('Location: /');
            exit;
        });
        return;
    }
    
    $router->get('/', function() {
        require ROOT_PATH . '/install/views/index.php';
    });
});

// API SYSTÃƒÆ’Ã†â€™Ãƒâ€¹Ã¢â‚¬Â ME
$router->group('/api/system', function($router) {
    $router->get('/status', function() {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'ok',
            'version' => '4.0.0',
            'timestamp' => time(),
        ]);
    });
});

// UTILITAIRES
$router->get('/favicon.ico', function() {
    $favicon = ROOT_PATH . '/public/favicon.ico';
    if (file_exists($favicon)) {
        header('Content-Type: image/x-icon');
        readfile($favicon);
    } else {
        http_response_code(404);
    }
    exit;
});

$router->get('/robots.txt', function() {
    header('Content-Type: text/plain');
    echo "User-agent: *\n";
    echo "Disallow: /admin/\n";
    echo "Disallow: /api/\n";
    echo "Disallow: /install/\n";
    echo "Allow: /\n";
    exit;
});
