<?php
declare(strict_types=1);

namespace Configuration;

use Framework\Interfaces\BaseModule;
use Framework\Services\Router;

class Configuration extends BaseModule
{
    public function getName(): string
    {
        return 'Configuration';
    }

    public function getDescription(): string
    {
        return 'Module de configuration globale du framework - Système, Sécurité, SEO, IA';
    }
    
    public function getAuthor(): string
    {
        return 'Aegis Framework';
    }

    public function init(): void
    {
    }
    
    public function registerRoutes($router): void
    {
        $routesFile = __DIR__ . '/routes.php';
        
        if (file_exists($routesFile)) {
            $registerRoutes = require $routesFile;
            if (is_callable($registerRoutes)) {
                $registerRoutes($router);
            }
        }
    }
    
    public function getHooks(): array
    {
        return [];
    }
}