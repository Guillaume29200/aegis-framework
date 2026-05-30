<?php
/**
 * Routes du module System (pages cœur d'administration).
 * Utilise le DI du Router (Controller@method) — voir setDependencies() dans index.php.
 */

return function ($router) {
    $router->group('/admin', function ($router) {

        // ── Gestion des modules ───────────────────────────────────────────
        $router->get('/modules',         'System\\Controllers\\ModulesController@index');
        $router->post('/modules/toggle', 'System\\Controllers\\ModulesController@toggle');
        $router->get('/modules/info',    'System\\Controllers\\ModulesController@info');

        // ── Centre de sécurité ────────────────────────────────────────────
        $router->get('/security',          'System\\Controllers\\SecurityController@index');
        $router->post('/security/unblock', 'System\\Controllers\\SecurityController@unblock');
        $router->post('/security/block',   'System\\Controllers\\SecurityController@block');

        // ── Monitoring ────────────────────────────────────────────────────
        $router->get('/monitoring',                 'System\\Controllers\\MonitoringController@index');
        $router->get('/monitoring/view-log',        'System\\Controllers\\MonitoringController@viewLog');
        $router->post('/monitoring/delete-log',     'System\\Controllers\\MonitoringController@deleteLog');
        $router->post('/monitoring/delete-db-log',  'System\\Controllers\\MonitoringController@deleteDatabaseLog');
        $router->post('/monitoring/purge-db-logs',  'System\\Controllers\\MonitoringController@purgeDatabaseLogs');
    });
};
