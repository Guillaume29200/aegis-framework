<?php
declare(strict_types=1);

namespace Framework\Middleware;

use Framework\Services\Database;
use Framework\Security\SessionManager;
use Framework\Security\CSRFProtection;

/**
 * Middleware MaintenanceMode
 * 
 * Vérifie si le site est en maintenance
 * Si OUI et que l'utilisateur n'est pas admin → affiche page de maintenance
 * Si OUI et que l'utilisateur est admin → laisse passer
 */
class MaintenanceMode
{
    private Database $db;
    private SessionManager $sessionManager;
    private ?CSRFProtection $csrf;
    private bool $maintenanceEnabled = false;
    
    public function __construct(Database $db, SessionManager $sessionManager, ?CSRFProtection $csrf = null)
    {
        $this->db = $db;
        $this->sessionManager = $sessionManager;
        $this->csrf = $csrf;
        $this->checkMaintenanceMode();
    }
    
    /**
     * Vérifier si le mode maintenance est activé
     */
    private function checkMaintenanceMode(): void
    {
        try {
            $stmt = $this->db->getPDO()->query("
                SELECT param_value 
                FROM settings 
                WHERE param_key = 'maintenance_mode' 
                LIMIT 1
            ");
            
            $this->maintenanceEnabled = $stmt ? (bool)$stmt->fetchColumn() : false;
        } catch (\Exception $e) {
            // Si erreur, désactiver
            $this->maintenanceEnabled = false;
        }
    }
    
    /**
     * Vérifier si l'utilisateur est admin
     */
    private function isAdmin(): bool
    {
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
            return false;
        }
        
        $role = $_SESSION['role'] ?? '';
        return in_array($role, ['admin', 'superadmin']);
    }
    
    /**
     * Vérifier et afficher la page de maintenance si nécessaire
     */
    public function handle(): void
    {
        // Si pas en maintenance, laisser passer
        if (!$this->maintenanceEnabled) {
            return;
        }
        
        // Si admin, laisser passer
        if ($this->isAdmin()) {
            return;
        }
        
        // Si requête avec maintenance_bypass (formulaire de la page maintenance)
        if (isset($_POST['maintenance_bypass']) && $_POST['maintenance_bypass'] == '1') {
            $currentRoute = $this->getCurrentRoute();
            if ($currentRoute === '/auth/login' || $currentRoute === '/api/auth/login') {
                return;
            }
        }
        
        // Routes autorisées même en maintenance
        $allowedRoutes = [
            '/auth/login',
            '/api/auth/login',
        ];
        
        $currentRoute = $this->getCurrentRoute();
        
        foreach ($allowedRoutes as $route) {
            if ($currentRoute === $route) {
                return; // Autoriser l'accès (GET ET POST)
            }
        }
        
        // Afficher la page de maintenance
        $this->showMaintenancePage();
        exit;
    }
    
    /**
     * Afficher la page de maintenance
     */
    private function showMaintenancePage(): void
    {
        http_response_code(503);
        header('Retry-After: 3600'); // Réessayer dans 1h
        
        // Générer token CSRF pour le formulaire de connexion
        if ($this->csrf) {
            $csrfToken = $this->csrf->generateToken('default'); // 'default' car AuthController valide avec 'default'
        } else {
            // Fallback si pas de CSRF (ne devrait jamais arriver)
            if (!isset($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            $csrfToken = $_SESSION['csrf_token'];
        }

        $loginAction = function_exists('u') ? u('/auth/login') : '/auth/login';
        
        // Récupérer le thème sélectionné
        try {
            $stmt = $this->db->getPDO()->query("
                SELECT param_value 
                FROM settings 
                WHERE param_key = 'maintenance_theme' 
                LIMIT 1
            ");
            $theme = $stmt ? $stmt->fetchColumn() : 'moderne';
        } catch (\Exception $e) {
            $theme = 'moderne'; // Thème par défaut
        }
        
        // Mapper les thèmes vers les fichiers
        $themeFiles = [
            'moderne' => 'theme1_moderne.php',
            'minimaliste' => 'theme2_minimaliste.php',
            'gaming' => 'theme3_gaming.php',
            'noel' => 'theme4_noel.php',
            'halloween' => 'theme5_halloween.php'
        ];
        
        // Fichier du thème
        $themeFile = $themeFiles[$theme] ?? $themeFiles['moderne'];
        $themePath = __DIR__ . '/../Views/theme/public/maintenance/' . $themeFile;
        
        // Si le fichier n'existe pas, utiliser le moderne
        if (!file_exists($themePath)) {
            $themePath = __DIR__ . '/../Views/theme/public/maintenance/theme1_moderne.php';
        }
        
        require $themePath;
    }

    /**
     * Retourne la route sans le sous-dossier d'installation (ex: /v4).
     */
    private function getCurrentRoute(): string
    {
        $currentRoute = $_SERVER['REQUEST_URI'] ?? '/';
        $currentRoute = parse_url($currentRoute, PHP_URL_PATH) ?: '/';
        $baseUrl = defined('BASE_URL') ? BASE_URL : '';

        if ($baseUrl !== '' && strpos($currentRoute, $baseUrl) === 0) {
            $currentRoute = substr($currentRoute, strlen($baseUrl)) ?: '/';
        }

        return '/' . ltrim($currentRoute, '/');
    }
    
    /**
     * Vérifier si le mode maintenance est activé (statique)
     */
    public function isEnabled(): bool
    {
        return $this->maintenanceEnabled;
    }
}
