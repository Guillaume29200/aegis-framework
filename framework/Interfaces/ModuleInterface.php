<?php
declare(strict_types=1);

namespace Framework\Interfaces;

/**
 * Interface ModuleInterface
 * 
 * Contrat que TOUS les modules doivent implémenter
 * Permet au ModuleManager de charger et gérer les modules
 */
interface ModuleInterface
{
    /**
     * Obtenir nom du module
     */
    public function getName(): string;
    
    /**
     * Obtenir version du module
     */
    public function getVersion(): string;
    
    /**
     * Obtenir description du module
     */
    public function getDescription(): string;
    
    /**
     * Obtenir auteur du module
     */
    public function getAuthor(): string;
    
    /**
     * Obtenir dépendances (autres modules requis)
     * 
     * @return array Format: ['module_name' => 'min_version']
     */
    public function getDependencies(): array;
    
    /**
     * Initialiser le module
     * 
     * Appelé au chargement du module
     */
    public function init(): void;
    
    /**
     * Enregistrer les routes du module
     * 
     * @param \Framework\Services\Router $router
     */
    public function registerRoutes($router): void;
    
    /**
     * Enregistrer les hooks du module
     * 
     * @return array Format: ['hook_name' => [callable, priority]]
     */
    public function getHooks(): array;
    
    /**
     * Installation du module
     * 
     * Appelé lors de l'activation (créer tables, etc.)
     */
    public function install(): bool;
    
    /**
     * Désinstallation du module
     * 
     * Appelé lors de la désactivation (supprimer tables, etc.)
     */
    public function uninstall(): bool;
    
    /**
     * Vérifier si le module est compatible
     * 
     * @param string $cmsVersion Version du CMS
     * @return bool
     */
    public function isCompatible(string $cmsVersion): bool;
}
