<?php
/**
 * Routes du module Auth
 */

return function($router) {
    // Routes d'authentification (publiques)
    $router->group('/auth', function($router) {
        $router->get('/', function() {
            header('Location: ' . u('/auth/login'));
            exit;
        });

        $router->get('/login',    'Auth\\Controllers\\AuthController@showLogin');
        $router->post('/login',   'Auth\\Controllers\\AuthController@login');
        
        $router->get('/register',  'Auth\\Controllers\\AuthController@showRegister');
        $router->post('/register', 'Auth\\Controllers\\AuthController@register');
        
        $router->post('/logout', 'Auth\\Controllers\\AuthController@logout');
        $router->get('/logout',  'Auth\\Controllers\\AuthController@logout');
        
        $router->get('/forgot-password',          'Auth\\Controllers\\AuthController@showForgotPassword');
        $router->post('/forgot-password',         'Auth\\Controllers\\AuthController@forgotPassword');
        $router->get('/reset-password/{token}',   'Auth\\Controllers\\AuthController@showResetPassword');
        $router->post('/reset-password',          'Auth\\Controllers\\AuthController@resetPassword');
    });
    
    // Routes admin (protegees)
    $router->group('/admin', function($router) {
        $router->get('/',          'Auth\\Controllers\\AdminController@dashboard');
        $router->get('/dashboard', 'Auth\\Controllers\\AdminController@dashboard');
        
        // Gestion utilisateurs
        $router->get('/users',                    'Auth\\Controllers\\AdminController@users');
        $router->get('/users/create',             'Auth\\Controllers\\AdminController@showCreateUser');
        $router->post('/users/create',            'Auth\\Controllers\\AdminController@createUser');
        $router->get('/users/{id}',               'Auth\\Controllers\\AdminController@showUser');
        $router->get('/users/{id}/edit',          'Auth\\Controllers\\AdminController@showEditUser');
        $router->post('/users/{id}/update',       'Auth\\Controllers\\AdminController@updateUser');
        $router->post('/users/{id}/delete',       'Auth\\Controllers\\AdminController@deleteUser');
        
        $router->get('/stats', 'Auth\\Controllers\\AdminController@stats');
    });
    
    // Routes membre (protegees)
    $router->group('/member', function($router) {
        $router->get('/',          'Auth\\Controllers\\MemberController@dashboard');
        $router->get('/dashboard', 'Auth\\Controllers\\MemberController@dashboard');
        
        $router->get('/profile',                    'Auth\\Controllers\\MemberController@profile');
        $router->post('/profile/update',            'Auth\\Controllers\\MemberController@updateProfile');
        $router->post('/profile/change-password',   'Auth\\Controllers\\MemberController@changePassword');
        
        $router->get('/settings',         'Auth\\Controllers\\MemberController@settings');
        $router->post('/settings/update', 'Auth\\Controllers\\MemberController@updateSettings');
    });
};