<?php
declare(strict_types=1);

namespace Framework\Security;

/**
 * Service Session Manager
 * 
 * PHP 8.4+ Compatible
 * Gestion sécurisée des sessions utilisateur
 * 
 * Fonctionnalités:
 * - Configuration sécurisée (httponly, secure, samesite)
 * - Régénération ID périodique
 * - Protection fixation de session
 * - Validation fingerprint
 * - Flash messages
 * - Gestion login/logout
 */
class SessionManager
{
    private array $config;
    private bool $started = false;
    
    public function __construct(array $config)
    {
        $this->config = $config['session'];
    }
    
    /**
     * Démarrer la session avec configuration sécurisée
     */
    public function start(): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        
        // Configuration cookies de session
        session_set_cookie_params([
            'lifetime' => $this->config['lifetime'],
            'path' => $this->config['path'],
            'domain' => $this->config['domain'],
            'secure' => $this->config['secure'],
            'httponly' => $this->config['httponly'],
            'samesite' => $this->config['samesite']
        ]);
        
        // Nom de session custom
        session_name($this->config['name']);
        
        // Démarrer
        if (!session_start()) {
            throw new SessionException('Failed to start session');
        }
        
        $this->started = true;
        
        // Vérifier si régénération nécessaire
        $this->checkRegeneration();
        
        // Valider la session
        $this->validate();
    }
    
    /**
     * Vérifier et régénérer ID si nécessaire
     */
    private function checkRegeneration(): void
    {
        $now = time();
        
        if (!isset($_SESSION['_last_regeneration'])) {
            $_SESSION['_last_regeneration'] = $now;
            return;
        }
        
        // Régénérer si interval dépassé
        $elapsed = $now - $_SESSION['_last_regeneration'];
        if ($elapsed > $this->config['regenerate_interval']) {
            $this->regenerate();
        }
    }
    
    /**
     * Régénérer l'ID de session (protection fixation)
     * 
     * @param bool $deleteOld Supprimer ancienne session
     */
    public function regenerate(bool $deleteOld = true): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id($deleteOld);
            $_SESSION['_last_regeneration'] = time();
        }
    }
    
    /**
     * Valider la session (protection hijacking)
     */
    private function validate(): void
    {
        // Vérifier fingerprint
        $fingerprint = $this->generateFingerprint();
        
        if (!isset($_SESSION['_fingerprint'])) {
            $_SESSION['_fingerprint'] = $fingerprint;
        } elseif ($_SESSION['_fingerprint'] !== $fingerprint) {
            // Session hijacking détecté !
            $this->handleSessionExpired('Session validation failed: possible hijacking attempt');
            return;
        }
        
        // Vérifier timeout (seulement si utilisateur connecté)
        if ($this->isLoggedIn() && isset($_SESSION['_last_activity'])) {
            $elapsed = time() - $_SESSION['_last_activity'];
            
            if ($elapsed > $this->config['gc_maxlifetime']) {
                $this->handleSessionExpired('Session expired due to inactivity');
                return;
            }
        }
        
        $_SESSION['_last_activity'] = time();
    }
    
    /**
     * Gérer l'expiration de session proprement
     * 
     * @param string $reason Raison de l'expiration
     */
    private function handleSessionExpired(string $reason): void
    {
        // Logger la raison (si logger disponible)
        error_log("Session expired: {$reason}");
        
        // Sauvegarder un message flash avant destruction
        if (!isset($_SESSION['_flash'])) {
            $_SESSION['_flash'] = [];
        }
        $_SESSION['_flash']['session_expired'] = true;
        $_SESSION['_flash']['session_expired_reason'] = $reason;
        
        // Nettoyer les données utilisateur mais garder le flash
        $flashData = $_SESSION['_flash'] ?? [];
        
        // Détruire la session
        $this->destroy();
        
        // Redémarrer avec le message flash
        $this->start();
        $_SESSION['_flash'] = $flashData;
        
        // Rediriger vers login en respectant un sous-dossier d'installation (ex: /v4).
        $loginUrl = $this->resolveAppUrl($this->config['login_url'] ?? '/auth/login');
        
        // Si on est en AJAX, retourner JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'session_expired' => true,
                'message' => 'Votre session a expiré. Veuillez vous reconnecter.',
                'redirect' => $loginUrl
            ]);
            exit;
        }
        
        // Redirection HTTP standard
        header("Location: {$loginUrl}?session_expired=1");
        exit;
    }

    /**
     * Convertir une URL applicative en URL compatible avec BASE_URL.
     */
    private function resolveAppUrl(string $path): string
    {
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        if (function_exists('url')) {
            return url($path);
        }

        if ($path === '' || $path[0] !== '/') {
            $path = '/' . $path;
        }

        $scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = ($scriptName !== '/' && $scriptName !== '\\' && $scriptName !== '.') ? $scriptName : '';

        return $basePath . $path;
    }
    
    /**
     * Générer fingerprint de session (PHP 8.4 optimisé)
     * 
     * Combine User-Agent + IP + Session name
     * 
     * @return string Hash SHA256
     */
    private function generateFingerprint(): string
    {
        $components = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $this->getClientIP(),
            $this->config['name']
        ];
        
        return hash('sha256', implode('|', $components));
    }
    
    /**
     * Obtenir IP client (gère proxies)
     * 
     * @return string Adresse IP
     */
    private function getClientIP(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',    // Cloudflare
            'HTTP_X_FORWARDED_FOR',     // Proxy standard
            'HTTP_X_REAL_IP',           // Nginx
        ];
        
        foreach ($headers as $header) {
            if (isset($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                return trim($ips[0]);
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Obtenir valeur de session
     * 
     * @param string $key Clé
     * @param mixed $default Valeur par défaut
     * @return mixed Valeur
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Définir valeur de session
     * 
     * @param string $key Clé
     * @param mixed $value Valeur
     */
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }
    
    /**
     * Vérifier si clé existe
     * 
     * @param string $key Clé
     * @return bool True si existe
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }
    
    /**
     * Supprimer une clé
     * 
     * @param string $key Clé
     */
    public function delete(string $key): void
    {
        unset($_SESSION[$key]);
    }
    
    /**
     * Vider toutes les données de session (sauf meta)
     */
    public function clear(): void
    {
        $preserve = ['_fingerprint', '_last_regeneration', '_last_activity'];
        
        foreach ($_SESSION as $key => $value) {
            if (!in_array($key, $preserve, true)) {
                unset($_SESSION[$key]);
            }
        }
    }
    
    /**
     * Détruire complètement la session
     */
    public function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            
            // Supprimer cookie de session
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
            
            session_destroy();
            $this->started = false;
        }
    }
    
    /**
     * Flash message (message unique, supprimée après lecture)
     * 
     * @param string $key Clé du message
     * @param mixed $value Valeur (null pour récupérer)
     * @return mixed Valeur ou null
     */
    public function flash(string $key, mixed $value = null): mixed
    {
        if ($value === null) {
            // Récupérer et supprimer
            $val = $_SESSION['_flash'][$key] ?? null;
            unset($_SESSION['_flash'][$key]);
            return $val;
        }
        
        // Stocker
        $_SESSION['_flash'][$key] = $value;
        return null;
    }
    
    /**
     * Garder un flash message pour la prochaine requête
     * 
     * @param string $key Clé du message
     */
    public function reflash(string $key): void
    {
        if (isset($_SESSION['_flash'][$key])) {
            $this->flash($key, $_SESSION['_flash'][$key]);
        }
    }
    
    /**
     * Vérifier si utilisateur connecté
     * 
     * @return bool True si connecté
     */
    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
    }
    
    /**
     * Obtenir ID utilisateur
     * 
     * @return int|null ID utilisateur ou null
     */
    public function getUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Obtenir données utilisateur
     * 
     * @param string|null $key Clé spécifique ou null pour tout
     * @return mixed Données utilisateur
     */
    public function getUserData(?string $key = null): mixed
    {
        if ($key === null) {
            return $_SESSION['user_data'] ?? [];
        }
        
        return $_SESSION['user_data'][$key] ?? null;
    }
    
    /**
     * Connecter un utilisateur
     * 
     * @param int $userId ID utilisateur
     * @param array $userData Données utilisateur additionnelles
     * @param bool $remember Remember me (non implémenté ici)
     */
    public function login(int $userId, array $userData = [], bool $remember = false): void
    {
        // Régénérer ID pour prévenir fixation
        $this->regenerate();
        
        // Stocker infos user
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_data'] = $userData;
        $_SESSION['login_time'] = time();
        $_SESSION['login_ip'] = $this->getClientIP();
        
        // Régénérer fingerprint
        $_SESSION['_fingerprint'] = $this->generateFingerprint();
        
        // TODO: Implémenter "Remember Me" avec token persistent
        if ($remember) {
            // Créer token remember_me en DB
            // Stocker cookie sécurisé
        }
    }
    
    /**
     * Déconnecter utilisateur
     */
    public function logout(): void
    {
        // Nettoyer données user
        unset(
            $_SESSION['user_id'],
            $_SESSION['user_data'],
            $_SESSION['login_time'],
            $_SESSION['login_ip']
        );
        
        // Détruire session complètement
        $this->destroy();
    }
    
    /**
     * Obtenir temps de connexion
     * 
     * @return int|null Timestamp Unix ou null
     */
    public function getLoginTime(): ?int
    {
        return $_SESSION['login_time'] ?? null;
    }
    
    /**
     * Obtenir durée de la session
     * 
     * @return int Durée en secondes
     */
    public function getSessionDuration(): int
    {
        if (!isset($_SESSION['login_time'])) {
            return 0;
        }
        
        return time() - $_SESSION['login_time'];
    }
    
    /**
     * Vérifier si session active
     * 
     * @return bool True si active
     */
    public function isActive(): bool
    {
        return $this->started && session_status() === PHP_SESSION_ACTIVE;
    }
    
    /**
     * Obtenir ID de session
     * 
     * @return string Session ID
     */
    public function getId(): string
    {
        return session_id();
    }
    
    /**
     * Obtenir toutes les données de session (debug)
     * 
     * @return array Données session
     */
    public function all(): array
    {
        return $_SESSION;
    }
}

/**
 * Exception Session
 */
class SessionException extends \Exception
{
    public function __construct(string $message = 'Session error', int $code = 500)
    {
        parent::__construct($message, $code);
    }
}
