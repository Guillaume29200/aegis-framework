<?php
declare(strict_types=1);

namespace Auth;

use Framework\Interfaces\BaseModule;
use Framework\Services\Router;

/**
 * Module Auth
 * 
 * Système complet d'authentification avec :
 * - Connexion / Inscription
 * - Dashboard admin et membre
 * - Redirection automatique selon le rôle
 * - Remember me (se souvenir de moi)
 * - Gestion des sessions
 * - Protection CSRF
 * 
 * @author Guillaume
 * @version 1.0.0
 */
class Auth extends BaseModule
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Auth';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getVersion(): string
    {
        return '1.0.0';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Système d\'authentification complet avec gestion des rôles et dashboards';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getAuthor(): string
    {
        return 'eSport-CMS';
    }

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        // Charger configuration
        $this->loadConfig();
        
        // Vérifier et restaurer session "Remember Me"
        $this->checkRememberMe();
    }
    
    /**
     * {@inheritdoc}
     */
    public function registerRoutes($router): void
    {
        // Charger le fichier routes.php du module
        $routesFile = __DIR__ . '/routes.php';
        
        if (file_exists($routesFile)) {
            $registerRoutes = require $routesFile;
            $registerRoutes($router);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function getHooks(): array
    {
        return [
            // Ajouter menu admin
            'admin.menu' => [
                ['\Auth\Hooks\AdminHooks', 'addAdminMenu'],
                10
            ],
            
            // Vérifier authentification avant chaque requête
            'request.before' => [
                ['\Auth\Hooks\AuthHooks', 'checkAuthentication'],
                5
            ],
            
            // Logger les connexions
            'user.login' => [
                ['\Auth\Hooks\AuthHooks', 'logLogin'],
                10
            ],
            
            // Logger les déconnexions
            'user.logout' => [
                ['\Auth\Hooks\AuthHooks', 'logLogout'],
                10
            ],
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function install(): bool
    {
        try {
            // Créer tables
            $this->createTables();
            
            // Créer utilisateur admin par défaut
            $this->createDefaultAdmin();
            
            return true;
            
        } catch (\Exception $e) {
            error_log("Auth install error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function uninstall(): bool
    {
        try {
            // Note: Ne pas supprimer la table users car elle peut être utilisée par d'autres modules
            return true;
            
        } catch (\Exception $e) {
            error_log("Auth uninstall error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Charger configuration du module
     */
    private function loadConfig(): void
    {
        // Configuration chargée depuis module.json
    }
    
    /**
     * Vérifier et restaurer session Remember Me
     */
    private function checkRememberMe(): void
    {
        if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
            $authService = new Services\AuthService($this->db);
            $authService->loginFromRememberToken($_COOKIE['remember_token']);
        }
    }
    
    /**
     * Créer tables SQL
     */
    private function createTables(): void
    {
        $sql = file_get_contents(__DIR__ . '/schema.sql');
        
        // Les tables sont créées via le fichier schema.sql
        // Exécution déléguée au système d'installation
    }
    
    /**
     * Créer administrateur par défaut
     */
    private function createDefaultAdmin(): void
    {
        // Création d'un compte admin par défaut
        // Login: admin
        // Password: admin123 (à changer immédiatement)
        
        $hashedPassword = password_hash('admin123', PASSWORD_ARGON2ID);
        
        $adminData = [
            'username' => 'admin',
            'email' => 'admin@esport-cms.local',
            'password' => $hashedPassword,
            'role' => 'admin',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Insérer via Database service
        // $this->db->insert('users', $adminData);
    }
}
