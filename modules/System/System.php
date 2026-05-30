<?php
declare(strict_types=1);

namespace System;

use Framework\Interfaces\BaseModule;

/**
 * Module System — fonctionnalités cœur d'administration.
 *
 * Regroupe les pages système (gestion des modules, centre de sécurité,
 * monitoring) qui étaient auparavant éparpillées dans framework/Views/admin.
 *
 * Module CŒUR : non désactivable (flag "core" dans module.json).
 */
class System extends BaseModule
{
    public function getName(): string
    {
        return 'System';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDescription(): string
    {
        return 'Fonctionnalités système : modules, sécurité, monitoring';
    }

    public function getAuthor(): string
    {
        return 'eSport-CMS';
    }

    public function init(): void
    {
    }

    public function registerRoutes($router): void
    {
        $routesFile = __DIR__ . '/routes.php';

        if (file_exists($routesFile)) {
            $register = require $routesFile;
            if (is_callable($register)) {
                $register($router);
            }
        }
    }
}
