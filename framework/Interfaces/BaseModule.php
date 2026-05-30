<?php
declare(strict_types=1);

namespace Framework\Interfaces;

use Framework\Services\Router;

/**
 * Classe abstraite BaseModule
 * 
 * Implémentation de base pour simplifier création de modules
 * Les modules peuvent étendre cette classe au lieu d'implémenter l'interface
 */
abstract class BaseModule implements ModuleInterface
{
    protected array $config = [];
    
    /**
     * Constructeur
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }
    
    /**
     * {@inheritdoc}
     */
    abstract public function getName(): string;
    
    /**
     * {@inheritdoc}
     */
    abstract public function getVersion(): string;
    
    /**
     * {@inheritdoc}
     */
    abstract public function getDescription(): string;
    
    /**
     * {@inheritdoc}
     */
    public function getAuthor(): string
    {
        return 'Unknown';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        return [];
    }
    
    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        // Par défaut: rien à faire
    }
    
    /**
     * {@inheritdoc}
     */
    public function registerRoutes($router): void
    {
        // Par défaut: pas de routes
    }
    
    /**
     * {@inheritdoc}
     */
    public function getHooks(): array
    {
        return [];
    }
    
    /**
     * {@inheritdoc}
     */
    public function install(): bool
    {
        // Par défaut: installation réussie
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function uninstall(): bool
    {
        // Par défaut: désinstallation réussie
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isCompatible(string $cmsVersion): bool
    {
        // Par défaut: compatible avec toutes versions
        return true;
    }
    
    /**
     * Déclarer les entrées de menu admin du module.
     *
     * Source unique de vérité : la clé "menu" du module.json (ou l'ancien
     * alias "admin_menu"). Le menu admin est donc 100 % déclaratif : aucun
     * code n'est nécessaire dans la classe du module.
     *
     * Un module peut tout de même surcharger cette méthode s'il a besoin d'un
     * menu dynamique (badges/compteurs, conditions de permission).
     *
     * @return array<int, array<string, mixed>> Liste d'items de menu.
     */
    public function getAdminMenu(): array
    {
        $menu = $this->config['menu'] ?? $this->config['admin_menu'] ?? null;

        if (empty($menu) || !is_array($menu)) {
            return [];
        }

        // Autoriser un item unique OU une liste d'items.
        if (isset($menu['label'])) {
            return [$menu];
        }

        return array_values($menu);
    }

    /**
     * Obtenir chemin du module
     */
    protected function getModulePath(): string
    {
        $reflection = new \ReflectionClass($this);
        return dirname($reflection->getFileName());
    }
    
    /**
     * Charger vue du module
     */
    protected function loadView(string $viewName, array $data = []): string
    {
        $viewPath = $this->getModulePath() . '/Views/' . $viewName . '.php';
        
        if (!file_exists($viewPath)) {
            throw new \Exception("View not found: {$viewName}");
        }
        
        $output = (static function (string $__path, array $__data): string {
            extract($__data, EXTR_SKIP);
            ob_start();
            include $__path;
            return (string)ob_get_clean();
        })($viewPath, $data);
        return $output;
    }
    
    /**
     * Obtenir configuration du module
     */
    protected function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
}
