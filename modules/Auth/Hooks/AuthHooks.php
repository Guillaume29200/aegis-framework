<?php
declare(strict_types=1);

namespace Auth\Hooks;

/**
 * Hooks d'authentification
 */
class AuthHooks
{
    /**
     * Vérifier authentification avant chaque requête
     */
    public static function checkAuthentication(): void
    {
        $uri = $_SERVER['REQUEST_URI'];
        $baseUrl = defined('BASE_URL') ? BASE_URL : '';

        if ($baseUrl !== '' && strpos($uri, $baseUrl) === 0) {
            $uri = substr($uri, strlen($baseUrl)) ?: '/';
        }
        
        // Routes publiques (ne nécessitent pas d'authentification)
        $publicRoutes = [
            '/auth/login',
            '/auth/register',
            '/auth/forgot-password',
            '/auth/reset-password',
            '/api/',
            '/'
        ];
        
        // Vérifier si la route est publique
        foreach ($publicRoutes as $route) {
            if (strpos($uri, $route) === 0) {
                return;
            }
        }
        
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: ' . u('/auth/login'));
            exit;
        }
    }
    
    /**
     * Logger connexion
     */
    public static function logLogin(array $user): void
    {
        error_log(sprintf(
            "[AUTH] User %s (ID: %d) logged in from %s",
            $user['username'],
            $user['id'],
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ));
    }
    
    /**
     * Logger déconnexion
     */
    public static function logLogout(): void
    {
        $username = $_SESSION['username'] ?? 'unknown';
        error_log(sprintf(
            "[AUTH] User %s logged out from %s",
            $username,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ));
    }
}
