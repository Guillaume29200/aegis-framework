<?php
declare(strict_types=1);

namespace Auth\Middleware;

use Auth\Services\AuthService;

/**
 * Middleware d'authentification
 * 
 * Vérifie si l'utilisateur est connecté et autorisé
 */
class AuthMiddleware
{
    private AuthService $authService;
    
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    
    /**
     * Vérifier authentification
     */
    public function handle(callable $next): void
    {
        if (!$this->authService->isLoggedIn()) {
            header('Location: ' . u('/auth/login'));
            exit;
        }
        
        $next();
    }
    
    /**
     * Vérifier rôle admin
     */
    public function requireAdmin(callable $next): void
    {
        if (!$this->authService->isLoggedIn() || !$this->authService->isAdmin()) {
            http_response_code(403);
            echo "Accès interdit";
            exit;
        }
        
        $next();
    }
    
    /**
     * Vérifier rôle spécifique
     */
    public function requireRole(string $role, callable $next): void
    {
        if (!$this->authService->isLoggedIn() || !$this->authService->hasRole($role)) {
            http_response_code(403);
            echo "Accès interdit";
            exit;
        }
        
        $next();
    }
}
