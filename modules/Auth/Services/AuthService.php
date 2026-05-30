<?php
declare(strict_types=1);

namespace Auth\Services;

use Framework\Services\Database;
use Framework\Services\AuthTracker;

/**
 * Service d'authentification
 * 
 * Gère toutes les opérations liées à l'authentification :
 * - Login / Logout
 * - Inscription
 * - Remember Me
 * - Gestion des sessions
 * - Vérification des permissions
 */
class AuthService
{
    private Database $db;
    private ?AuthTracker $authTracker = null;
    private ?\Framework\Security\RateLimiter $rateLimiter = null;
    private int $maxLoginAttempts = 5;
    private int $lockoutDuration = 900; // 15 minutes

    public function __construct(Database $db, ?\Framework\Security\RateLimiter $rateLimiter = null)
    {
        $this->db = $db;
        $this->rateLimiter = $rateLimiter;

        // Initialiser le tracker de connexions
        $this->authTracker = new AuthTracker($db);
    }
    
    /**
     * Connexion utilisateur
     */
    public function login(string $identifier, string $password, bool $rememberMe = false, ?string $screenResolution = null): array
    {
        // ========================================
        // RATE LIMITING (par compte ET par IP)
        // ========================================
        // Deux dimensions complementaires :
        //  - par compte  : protege un compte cible d'une attaque distribuee
        //  - par IP      : empeche une IP de balayer plusieurs comptes (enumeration)
        $rateError = $this->checkLoginRateLimit($identifier);
        if ($rateError !== null) {
            return ['success' => false, 'error' => $rateError];
        }

        // Récupérer l'utilisateur
        $user = $this->getUserByIdentifier($identifier);

        if (!$user) {
            $this->recordLoginAttempt($identifier, false);
            $this->registerLoginFailure($identifier);
            return [
                'success' => false,
                'error' => 'Identifiants incorrects.'
            ];
        }

        // Vérifier le mot de passe
        if (!password_verify($password, $user['password'])) {
            $this->recordLoginAttempt($identifier, false);
            $this->registerLoginFailure($identifier);

            // ========================================
            // TRACKER LA TENTATIVE ÉCHOUÉE
            // ========================================
            if ($this->authTracker) {
                $this->authTracker->trackLogin($user['id'], false, $screenResolution);
            }

            return [
                'success' => false,
                'error' => 'Identifiants incorrects.'
            ];
        }

        // Vérifier le statut du compte
        if ($user['status'] !== 'active') {
            return [
                'success' => false,
                'error' => 'Votre compte est ' . $user['status'] . '.'
            ];
        }

        // Connexion réussie — réinitialiser les compteurs de rate limiting
        $this->recordLoginAttempt($identifier, true);
        $this->resetLoginRateLimit($identifier);
        $this->createUserSession($user);
        
        // ========================================
        // TRACKER LA CONNEXION RÉUSSIE
        // ========================================
        if ($this->authTracker) {
            $this->authTracker->trackLogin($user['id'], true, $screenResolution);
        }
        
        // Remember Me
        if ($rememberMe) {
            $this->createRememberToken($user['id']);
        }
        
        // Mettre à jour dernière connexion
        $this->updateLastLogin($user['id']);
        
        return [
            'success' => true,
            'user' => $user,
            'redirect' => $this->getRedirectUrl($user['role'])
        ];
    }
    
    /**
     * Déconnexion utilisateur
     */
    public function logout(): void
    {
        // Supprimer token Remember Me
        if (isset($_COOKIE['remember_token'])) {
            $this->deleteRememberToken($_COOKIE['remember_token']);
            $this->clearRememberCookie();
        }
        
        // Supprimer la session
        if (isset($_SESSION['session_id'])) {
            $this->deleteUserSession($_SESSION['session_id']);
        }
        
        // Détruire la session PHP
        session_destroy();
    }
    
    /**
     * Inscription utilisateur
     */
    public function register(array $data, ?string $screenResolution = null): array
    {
        // Validation
        $validation = $this->validateRegistration($data);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => $validation['errors']
            ];
        }
        
        // Vérifier si username/email existe déjà
        if ($this->usernameExists($data['username'])) {
            return [
                'success' => false,
                'errors' => ['username' => 'Ce nom d\'utilisateur existe déjà.']
            ];
        }
        
        if ($this->emailExists($data['email'])) {
            return [
                'success' => false,
                'errors' => ['email' => 'Cette adresse email est déjà utilisée.']
            ];
        }
        
        // Hasher le mot de passe
        $hashedPassword = password_hash($data['password'], PASSWORD_ARGON2ID);
        
        // Créer l'utilisateur
        $userId = $this->createUser([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $hashedPassword,
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'role' => 'member',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        if (!$userId) {
            return [
                'success' => false,
                'errors' => ['general' => 'Erreur lors de la création du compte.']
            ];
        }
        
        // ========================================
        // TRACKER L'INSCRIPTION
        // ========================================
        if ($this->authTracker) {
            $this->authTracker->trackRegistration($userId, $screenResolution);
        }
        
        // Récupérer l'utilisateur créé
        $user = $this->getUserById($userId);
        
        // Créer la session
        $this->createUserSession($user);
        
        // ========================================
        // TRACKER LA PREMIÈRE CONNEXION (inscription = connexion)
        // ========================================
        if ($this->authTracker) {
            $this->authTracker->trackLogin($userId, true, $screenResolution);
        }
        
        return [
            'success' => true,
            'user' => $user,
            'redirect' => $this->getRedirectUrl($user['role'])
        ];
    }
    
    /**
     * Cree une demande de reinitialisation de mot de passe.
     * Retourne null si l'email n'existe pas afin de ne pas reveler les comptes.
     */
    public function createPasswordResetRequest(string $email): ?array
    {
        $this->ensurePasswordResetTable();

        $email = trim(mb_strtolower($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT id, username, email, status FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user || ($user['status'] ?? '') !== 'active') {
            return null;
        }

        $this->db->execute(
            "UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL",
            [(int)$user['id']]
        );

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);

        $this->db->execute(
            "INSERT INTO password_reset_tokens (user_id, token_hash, ip_address, user_agent, expires_at, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())",
            [
                (int)$user['id'],
                $tokenHash,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $expiresAt,
            ]
        );

        return [
            'user_id' => (int)$user['id'],
            'username' => (string)$user['username'],
            'email' => (string)$user['email'],
            'token' => $token,
            'expires_at' => $expiresAt,
            'expires_minutes' => 60,
        ];
    }

    /**
     * Reinitialise le mot de passe a partir d'un token valide et non utilise.
     */
    public function resetPasswordWithToken(string $token, string $password, string $passwordConfirm): array
    {
        $this->ensurePasswordResetTable();

        $errors = [];
        if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            $errors['general'] = 'Lien de reinitialisation invalide.';
        }
        if ($password === '') {
            $errors['password'] = 'Le mot de passe est requis.';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 8 caracteres.';
        }
        if ($passwordConfirm === '' || $password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Les mots de passe ne correspondent pas.';
        }
        if ($errors) {
            return ['success' => false, 'errors' => $errors];
        }

        $tokenHash = hash('sha256', $token);
        $stmt = $this->db->prepare(
            "SELECT prt.id, prt.user_id, u.status
             FROM password_reset_tokens prt
             INNER JOIN users u ON u.id = prt.user_id
             WHERE prt.token_hash = ?
               AND prt.used_at IS NULL
               AND prt.expires_at > NOW()
             LIMIT 1"
        );
        $stmt->execute([$tokenHash]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row || ($row['status'] ?? '') !== 'active') {
            return [
                'success' => false,
                'errors' => ['general' => 'Ce lien est invalide, expire ou deja utilise.'],
            ];
        }

        $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?",
                [$hashedPassword, (int)$row['user_id']]
            );
            $this->db->execute(
                "UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?",
                [(int)$row['id']]
            );
            $this->db->execute("DELETE FROM remember_tokens WHERE user_id = ?", [(int)$row['user_id']]);
            $this->db->execute("DELETE FROM user_sessions WHERE user_id = ?", [(int)$row['user_id']]);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        return ['success' => true];
    }

    /**
     * Cree la table de reset si elle n'existe pas encore.
     */
    private function ensurePasswordResetTable(): void
    {
        $this->db->execute(
            "CREATE TABLE IF NOT EXISTS password_reset_tokens (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token_hash CHAR(64) NOT NULL,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(255) NULL,
                expires_at DATETIME NOT NULL,
                used_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_password_reset_token_hash (token_hash),
                INDEX idx_password_reset_user_id (user_id),
                INDEX idx_password_reset_expires_at (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }
    /**
     * Connexion depuis Remember Token
     */
    public function loginFromRememberToken(string $token): bool
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            $this->clearRememberCookie();
            return false;
        }

        $stmt = $this->db->prepare("
            SELECT rt.*, u.*
            FROM remember_tokens rt
            INNER JOIN users u ON rt.user_id = u.id
            WHERE rt.token = ? AND rt.expires_at > NOW() AND u.status = 'active'
            LIMIT 1
        ");
        
        $stmt->execute([$token]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$result) {
            $this->clearRememberCookie();
            return false;
        }

        $this->createUserSession($result);
        $this->rotateRememberToken((int)$result['id']);
        $this->updateLastLogin((int)$result['user_id']);
        
        // ========================================
        // TRACKER LA CONNEXION VIA REMEMBER TOKEN
        // ========================================
        if ($this->authTracker) {
            $this->authTracker->trackLogin((int)$result['user_id'], true);
        }
        
        return true;
    }
    
    /**
     * Créer session utilisateur
     */
    private function createUserSession(array $user): void
    {
        // Regénérer ID de session (sécurité)
        session_regenerate_id(true);
        
        // Stocker infos utilisateur en session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['session_id'] = session_id();
        
        // Enregistrer en BDD pour tracking
        $this->recordUserSession($user['id']);
    }
    
    /**
     * Créer token Remember Me
     */
    private function createRememberToken(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 2592000); // 30 jours
        
        $stmt = $this->db->prepare("
            INSERT INTO remember_tokens (user_id, token, ip_address, user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $token,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $expiresAt
        ]);
        
        // Définir le cookie
        $this->setRememberCookie($token, time() + 2592000);
    }
    
    private function rotateRememberToken(int $rememberTokenId): void
    {
        $newToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 2592000);

        $stmt = $this->db->prepare("UPDATE remember_tokens SET token = ?, expires_at = ?, created_at = NOW() WHERE id = ?");
        $stmt->execute([$newToken, $expiresAt, $rememberTokenId]);

        $this->setRememberCookie($newToken, time() + 2592000);
    }

    private function setRememberCookie(string $token, int $expiresAt): void
    {
        setcookie('remember_token', $token, [
            'expires' => $expiresAt,
            'path' => '/',
            'domain' => '',
            'secure' => $this->isHttpsRequest(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function clearRememberCookie(): void
    {
        setcookie('remember_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => $this->isHttpsRequest(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function isHttpsRequest(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    }
    /**
     * Supprimer token Remember Me
     */
    private function deleteRememberToken(string $token): void
    {
        $stmt = $this->db->prepare("DELETE FROM remember_tokens WHERE token = ?");
        $stmt->execute([$token]);
    }
    
    /**
     * Enregistrer session utilisateur en BDD
     */
    private function recordUserSession(int $userId): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, last_activity)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $userId,
            session_id(),
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    /**
     * Supprimer session utilisateur
     */
    private function deleteUserSession(string $sessionId): void
    {
        $stmt = $this->db->prepare("DELETE FROM user_sessions WHERE session_id = ?");
        $stmt->execute([$sessionId]);
    }
    
    /**
     * Enregistrer tentative de connexion
     */
    private function recordLoginAttempt(string $identifier, bool $success): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO login_attempts (identifier, ip_address, user_agent, success)
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $identifier,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $success ? 1 : 0
        ]);
    }
    
    /**
     * Construire les clés de rate limiting (compte + IP) pour le login.
     *
     * @return array{account: string, ip: string}
     */
    private function loginRateKeys(string $identifier): array
    {
        return [
            'account' => 'login:account:' . mb_strtolower(trim($identifier)),
            'ip'      => 'login:ip:' . $this->clientIp(),
        ];
    }

    /**
     * Vérifier les limites de tentatives de connexion (compte ET IP).
     *
     * @return string|null Message d'erreur si bloqué, null si autorisé.
     */
    private function checkLoginRateLimit(string $identifier): ?string
    {
        // Sans RateLimiter injecté, repli sur l'ancien lockout par compte.
        if ($this->rateLimiter === null) {
            return $this->isLockedOut($identifier)
                ? 'Trop de tentatives échouées. Réessayez dans 15 minutes.'
                : null;
        }

        $keys = $this->loginRateKeys($identifier);

        try {
            $this->rateLimiter->check($keys['ip'], 'auth');
            $this->rateLimiter->check($keys['account'], 'auth');
        } catch (\Framework\Security\RateLimitException $e) {
            $minutes = (int)ceil(max(1, $e->getRetryAfter()) / 60);
            return "Trop de tentatives échouées. Réessayez dans {$minutes} minute(s).";
        }

        return null;
    }

    /**
     * Incrémenter les compteurs de rate limiting après un échec de connexion.
     */
    private function registerLoginFailure(string $identifier): void
    {
        if ($this->rateLimiter === null) {
            return;
        }

        $keys = $this->loginRateKeys($identifier);
        $this->rateLimiter->increment($keys['ip'], 'auth');
        $this->rateLimiter->increment($keys['account'], 'auth');
    }

    /**
     * Réinitialiser les compteurs de rate limiting après une connexion réussie.
     */
    private function resetLoginRateLimit(string $identifier): void
    {
        if ($this->rateLimiter === null) {
            return;
        }

        $keys = $this->loginRateKeys($identifier);
        $this->rateLimiter->reset($keys['account'], 'auth');
        $this->rateLimiter->unblock($keys['account'], 'auth');
        // L'IP n'est volontairement PAS reset : une connexion reussie ne doit pas
        // effacer les echecs accumules par d'autres comptes depuis la meme IP.
    }

    /**
     * IP cliente (REMOTE_ADDR uniquement — pas de confiance aux en-têtes proxy
     * pour éviter qu'un attaquant ne falsifie sa clé de rate limiting).
     */
    private function clientIp(): string
    {
        return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    /**
     * Vérifier si l'utilisateur est lockout
     */
    private function isLockedOut(string $identifier): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as attempts
            FROM login_attempts
            WHERE identifier = ? 
            AND success = 0 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        
        $stmt->execute([$identifier, $this->lockoutDuration]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result['attempts'] >= $this->maxLoginAttempts;
    }
    
    /**
     * Mettre à jour dernière connexion
     */
    private function updateLastLogin(int $userId): void
    {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET last_login = NOW(), login_count = login_count + 1
            WHERE id = ?
        ");
        
        $stmt->execute([$userId]);
    }
    
    /**
     * Récupérer utilisateur par identifiant (username ou email)
     */
    private function getUserByIdentifier(string $identifier): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM users 
            WHERE username = ? OR email = ?
            LIMIT 1
        ");
        
        $stmt->execute([$identifier, $identifier]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }
    
    /**
     * Récupérer utilisateur par ID
     */
    public function getUserById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }
    
    /**
     * Créer utilisateur
     */
    private function createUser(array $data): ?int
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (username, email, password, first_name, last_name, role, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $success = $stmt->execute([
            $data['username'],
            $data['email'],
            $data['password'],
            $data['first_name'] ?? null,
            $data['last_name'] ?? null,
            $data['role'],
            $data['status'],
            $data['created_at']
        ]);
        
        return $success ? (int)$this->db->getPDO()->lastInsertId() : null;
    }
    
    /**
     * Vérifier si username existe
     */
    public function usernameExists(string $username): bool
    {
        $result = $this->db->query(
            "SELECT COUNT(*) as count FROM users WHERE username = ?",
            [$username]
        );
        return ($result[0]['count'] ?? 0) > 0;
    }
    
    /**
     * Vérifier si email existe
     */
    public function emailExists(string $email): bool
    {
        $result = $this->db->query(
            "SELECT COUNT(*) as count FROM users WHERE email = ?",
            [$email]
        );
        return ($result[0]['count'] ?? 0) > 0;
    }
    
    /**
     * Validation inscription
     */
    private function validateRegistration(array $data): array
    {
        $errors = [];
        
        // Username
        if (empty($data['username'])) {
            $errors['username'] = 'Le nom d\'utilisateur est requis.';
        } elseif (strlen($data['username']) < 3) {
            $errors['username'] = 'Le nom d\'utilisateur doit contenir au moins 3 caractères.';
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $data['username'])) {
            $errors['username'] = 'Le nom d\'utilisateur ne peut contenir que des lettres, chiffres, _ et -.';
        }
        
        // Email
        if (empty($data['email'])) {
            $errors['email'] = 'L\'adresse email est requise.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'L\'adresse email n\'est pas valide.';
        }
        
        // Password
        if (empty($data['password'])) {
            $errors['password'] = 'Le mot de passe est requis.';
        } elseif (strlen($data['password']) < 8) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères.';
        }
        
        // Confirmation password
        if (empty($data['password_confirm']) || $data['password'] !== $data['password_confirm']) {
            $errors['password_confirm'] = 'Les mots de passe ne correspondent pas.';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Obtenir URL de redirection selon le rôle
     */
    private function getRedirectUrl(string $role): string
    {
        $path = match($role) {
            'admin', 'superadmin' => '/admin/dashboard',
            'moderator' => '/admin/dashboard',
            'member' => '/member/dashboard',
            default => '/member/dashboard'
        };
        
        // Calculer le base path (gère Windows et Linux)
        $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $basePath = ($scriptName === '/' || $scriptName === '') ? '' : $scriptName;
        
        return $basePath . $path;
    }

    
    /**
     * Vérifier si l'utilisateur est connecté
     */
    public function isLoggedIn(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Vérifier si l'utilisateur a un rôle
     */
    public function hasRole(string $role): bool
    {
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }
    
    /**
     * Vérifier si l'utilisateur est admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->hasRole('superadmin');
    }
    
    /**
     * Vérifier si les inscriptions sont ouvertes
     */
    public function isRegistrationEnabled(): bool
    {
        try {
            $stmt = $this->db->getPDO()->query("
                SELECT param_value 
                FROM settings 
                WHERE param_key = 'registration_enabled' 
                LIMIT 1
            ");
            
            return $stmt ? (bool)$stmt->fetchColumn() : true; // Par défaut ouvert si param pas trouvé
        } catch (\Exception $e) {
            return true; // Par défaut ouvert en cas d'erreur
        }
    }
	
	
}