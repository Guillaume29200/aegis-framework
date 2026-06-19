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

    /** Empêche la récursion lors du traitement d'une expiration de session. */
    private static bool $handlingExpiry = false;

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

        // Durcissement anti-fixation / anti-vol de session (avant session_start) :
        //  - use_strict_mode : refuse un ID de session non généré par le serveur ;
        //  - use_only_cookies : interdit l'ID de session passé dans l'URL ;
        //  - use_trans_sid : ne jamais propager l'ID via l'URL.
        @ini_set('session.use_strict_mode', '1');
        @ini_set('session.use_only_cookies', '1');
        @ini_set('session.use_trans_sid', '0');
        @ini_set('session.cookie_httponly', '1');

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
        // Ne JAMAIS régénérer pendant une requête AJAX : avec le panel qui fait
        // beaucoup de requêtes concurrentes (polling console/stats/stream), une
        // requête en vol arriverait avec l'ancien ID juste régénéré → session
        // perdue → déconnexion aléatoire. On régénère uniquement sur les pages.
        if ($this->isAjaxRequest()) {
            return;
        }

        $now = time();

        if (!isset($_SESSION['_last_regeneration'])) {
            $_SESSION['_last_regeneration'] = $now;
            return;
        }

        // Régénérer si interval dépassé (sans supprimer l'ancien ID par défaut,
        // cf. regenerate_delete_old — évite les courses résiduelles).
        $elapsed = $now - $_SESSION['_last_regeneration'];
        if ($elapsed > (int)$this->config['regenerate_interval']) {
            $this->regenerate((bool)($this->config['regenerate_delete_old'] ?? false));
        }
    }

    private function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
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
            // Session hijacking détecté ! Le Centre de sécurité (créé après le
            // démarrage de session) relèvera ce drapeau dans son middleware.
            $GLOBALS['_aegis_session_hijack'] = true;
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
        // Garde anti-récursion : destroy() puis un éventuel re-start()/validate()
        // pouvait relancer cette méthode en boucle (Xdebug « infinite loop »).
        // On ne traite l'expiration qu'une seule fois par requête.
        if (self::$handlingExpiry) {
            return;
        }
        self::$handlingExpiry = true;

        // Logger la raison (si logger disponible)
        error_log("Session expired: {$reason}");

        // Détruire la session expirée. On NE recrée PAS de session ici :
        // le message d'expiration est porté par le paramètre ?session_expired=1
        // (lu par la page de login), donc inutile de réamorcer une session
        // (ce qui relançait validate() → boucle).
        $this->destroy();

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
            $this->fingerprintIPPart(),
            $this->config['name']
        ];

        return hash('sha256', implode('|', $components));
    }

    /**
     * Composante IP de l'empreinte, selon le mode de liaison configuré.
     * Normalise le localhost (::1 ↔ 127.0.0.1) — sinon le navigateur alterne
     * IPv4/IPv6 en local et déconnecte aléatoirement (faux « hijacking »).
     */
    private function fingerprintIPPart(): string
    {
        $mode = $this->config['ip_binding'] ?? 'subnet';
        if ($mode === 'off') {
            return '';
        }

        $ip = $this->getClientIP();

        // Localhost : toutes les variantes comptent comme la même origine.
        if (in_array($ip, ['::1', '127.0.0.1', '0.0.0.0', ''], true)) {
            return 'local';
        }

        if ($mode === 'strict') {
            return $ip;
        }

        // 'subnet' : on ne lie qu'au sous-réseau (tolère les changements d'IP
        // mineurs : équilibrage opérateur, bascule IPv4/IPv6 partielle…).
        if (strpos($ip, ':') !== false) {            // IPv6 → ~/64 (4 premiers blocs)
            return implode(':', array_slice(explode(':', $ip), 0, 4));
        }
        $parts = explode('.', $ip);                  // IPv4 → /24
        if (count($parts) === 4) {
            $parts[3] = '0';
            return implode('.', $parts);
        }
        return $ip;
    }

    /** Secondes d'inactivité restantes avant expiration (pour le front). */
    public function getIdleRemaining(): int
    {
        if (!$this->isLoggedIn() || !isset($_SESSION['_last_activity'])) {
            return (int)($this->config['gc_maxlifetime'] ?? 7200);
        }
        $max = (int)($this->config['gc_maxlifetime'] ?? 7200);
        return max(0, $max - (time() - (int)$_SESSION['_last_activity']));
    }

    /** Durée maximale d'inactivité configurée (secondes). */
    public function getIdleMax(): int
    {
        return (int)($this->config['gc_maxlifetime'] ?? 7200);
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
