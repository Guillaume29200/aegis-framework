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
        $router->post('/modules/delete', 'System\\Controllers\\ModulesController@delete');
        $router->get('/modules/info',    'System\\Controllers\\ModulesController@info');
        $router->post('/modules/upload', 'System\\Controllers\\ModulesController@upload');
        $router->get('/modules/generate',  'System\\Controllers\\ModuleGeneratorController@index');
        $router->post('/modules/generate', 'System\\Controllers\\ModuleGeneratorController@generate');

        // ── Centre de sécurité ────────────────────────────────────────────
        $router->get('/security',                 'System\\Controllers\\SecurityController@index');
        $router->post('/security/settings',       'System\\Controllers\\SecurityController@saveSettings');
        $router->post('/security/rules',          'System\\Controllers\\SecurityController@saveRules');
        $router->post('/security/whitelist/add',  'System\\Controllers\\SecurityController@whitelistAdd');
        $router->post('/security/whitelist/remove','System\\Controllers\\SecurityController@whitelistRemove');
        $router->post('/security/blacklist/add',  'System\\Controllers\\SecurityController@blacklistAdd');
        $router->post('/security/block',          'System\\Controllers\\SecurityController@block');
        $router->post('/security/unblock',        'System\\Controllers\\SecurityController@unblock');
        $router->post('/security/purge',          'System\\Controllers\\SecurityController@purgeEvents');

        // ── Changelog / versions du framework ─────────────────────────────
        $router->get('/changelog',          'System\\Controllers\\ChangelogController@index');

        // ── Diagnostic / santé ────────────────────────────────────────────
        $router->get('/diagnostic',         'System\\Controllers\\DiagnosticController@index');
        $router->post('/diagnostic/repair', 'System\\Controllers\\DiagnosticController@repair');

        // ── Monitoring ────────────────────────────────────────────────────
        $router->get('/monitoring',                 'System\\Controllers\\MonitoringController@index');
        $router->get('/monitoring/view-log',        'System\\Controllers\\MonitoringController@viewLog');
        $router->post('/monitoring/delete-log',     'System\\Controllers\\MonitoringController@deleteLog');
        $router->post('/monitoring/delete-db-log',  'System\\Controllers\\MonitoringController@deleteDatabaseLog');
        $router->post('/monitoring/purge-db-logs',  'System\\Controllers\\MonitoringController@purgeDatabaseLogs');
    });
};
