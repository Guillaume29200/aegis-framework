<?php
/**
 * ROUTES MODULE CONFIGURATION
 */

return function($router) {
    $router->group('/admin/configuration', function($router) {
        
        // Page principale Configuration
        $router->get('/', 'Configuration\\Controllers\\ConfigurationController@index');
        
        // Sauvegardes par section
        $router->post('/save-general',  'Configuration\\Controllers\\ConfigurationController@saveGeneral');
        $router->post('/save-system',   'Configuration\\Controllers\\ConfigurationController@saveSystem');
        $router->post('/save-security', 'Configuration\\Controllers\\ConfigurationController@saveSecurity');
        $router->post('/save-sessions', 'Configuration\\Controllers\\ConfigurationController@saveSessions');
        $router->post('/save-email',    'Configuration\\Controllers\\ConfigurationController@saveEmail');
        $router->post('/save-seo',      'Configuration\\Controllers\\ConfigurationController@saveSeo');
        $router->post('/save-ai',       'Configuration\\Controllers\\ConfigurationController@saveAi');
        $router->post('/save-turbonav', 'Configuration\\Controllers\\ConfigurationController@saveTurboNav');

        // RGPD / Cookies (contrôleur dédié)
        $router->get('/rgpd',        'Configuration\\Controllers\\RgpdController@index');
        $router->post('/save-rgpd',  'Configuration\\Controllers\\RgpdController@save');
        $router->post('/rgpd/reset', 'Configuration\\Controllers\\RgpdController@reset');

        // SEO & médias (contrôleur dédié)
        $router->get('/seo',       'Configuration\\Controllers\\SeoController@index');
        $router->post('/seo/save', 'Configuration\\Controllers\\SeoController@save');

        // Sitemap & robots.txt (contrôleur dédié)
        $router->post('/sitemap/generate', 'Configuration\\Controllers\\SitemapController@generate');

        // ======================================
        // GESTION MODÈLES IA
        // ======================================
        
        $router->get('/ai-models',              'Configuration\\Controllers\\AIModelController@index');
        $router->get('/ai-models/create',       'Configuration\\Controllers\\AIModelController@create');
        $router->get('/ai-models/{id}/edit',    'Configuration\\Controllers\\AIModelController@edit');
        $router->post('/ai-models',             'Configuration\\Controllers\\AIModelController@store');
        $router->post('/ai-models/{id}/update', 'Configuration\\Controllers\\AIModelController@update');
        $router->post('/ai-models/{id}/delete', 'Configuration\\Controllers\\AIModelController@delete');
        $router->post('/ai-models/{id}/toggle', 'Configuration\\Controllers\\AIModelController@toggle');
        $router->post('/ai-models/{id}/default','Configuration\\Controllers\\AIModelController@setDefault');
    });
};