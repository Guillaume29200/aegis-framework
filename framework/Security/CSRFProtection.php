<?php
declare(strict_types=1);

namespace Framework\Security;

class CSRFProtection
{
    private array $config;
    private string $tokenName;
    
    public function __construct(array $config)
    {
        $this->config = $config['csrf'];
        $this->tokenName = $this->config['token_name'];
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }
    }
    
    // Taille max du pool de tokens par action (1 token par onglet ouvert)
    private int $poolSize = 10;

    public function generateToken(string $action = 'default'): string
    {
        $this->cleanExpiredTokens();

        $token = bin2hex(random_bytes($this->config['token_length']));
        $expire = time() + $this->config['expire'];

        if (!isset($_SESSION['csrf_tokens'][$action])) {
            $_SESSION['csrf_tokens'][$action] = [];
        }

        // Ajouter le nouveau token au pool
        $_SESSION['csrf_tokens'][$action][] = [
            'token' => $token,
            'expire' => $expire
        ];

        // Limiter la taille du pool — garder les plus récents
        if (count($_SESSION['csrf_tokens'][$action]) > $this->poolSize) {
            $_SESSION['csrf_tokens'][$action] = array_slice(
                $_SESSION['csrf_tokens'][$action], 
                -$this->poolSize
            );
        }

        return $token;
    }

    public function getToken(string $action = 'default'): string
    {
        $this->cleanExpiredTokens();

        // Retourner le dernier token valide du pool
        if (!empty($_SESSION['csrf_tokens'][$action])) {
            $last = end($_SESSION['csrf_tokens'][$action]);
            if ($last['expire'] > time()) {
                return $last['token'];
            }
        }

        return $this->generateToken($action);
    }

    public function validateToken(string $token, string $action = 'default'): bool
    {
        $this->cleanExpiredTokens();

        if (empty($_SESSION['csrf_tokens'][$action])) {
            throw new CSRFException('CSRF token not found');
        }

        $now = time();

        // Valider contre n'importe quel token valide du pool
        foreach ($_SESSION['csrf_tokens'][$action] as $idx => $data) {
            if ($data['expire'] < $now) {
                continue;
            }
            if (hash_equals($data['token'], $token)) {
                return true;
            }
        }

        throw new CSRFException('CSRF token mismatch');
    }
    
    public function validateRequest(string $action = 'default'): bool
    {
        $token = $_POST[$this->tokenName] ?? null;
        
        if (!$token && isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
        
        if (!$token) {
            throw new CSRFException('CSRF token not provided');
        }
        
        return $this->validateToken($token, $action);
    }
    
    public function getTokenInput(string $action = 'default'): string
    {
        $token = $this->getToken($action);
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            htmlspecialchars($this->tokenName, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
        );
    }
    
    public function getTokenMeta(string $action = 'default'): string
    {
        $token = $this->getToken($action);
        return sprintf(
            '<meta name="csrf-token" content="%s">',
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
        );
    }
    
    private function cleanExpiredTokens(): void
    {
        $now = time();

        foreach ($_SESSION['csrf_tokens'] as $action => $pool) {
            // Migration ancien format : token unique (string ou array plat)
            if (!is_array($pool)) {
                unset($_SESSION['csrf_tokens'][$action]);
                continue;
            }

            // Migration ancien format : array plat {token, expire} → pool de tableaux
            if (isset($pool['token'])) {
                if (!empty($pool['expire']) && $pool['expire'] >= $now) {
                    $_SESSION['csrf_tokens'][$action] = [$pool];
                } else {
                    unset($_SESSION['csrf_tokens'][$action]);
                }
                continue;
            }

            // Format pool correct — nettoyer les expirés
            $_SESSION['csrf_tokens'][$action] = array_values(
                array_filter($pool, fn($data) => is_array($data) && isset($data['expire']) && $data['expire'] >= $now)
            );

            if (empty($_SESSION['csrf_tokens'][$action])) {
                unset($_SESSION['csrf_tokens'][$action]);
            }
        }
    }
    
    public function regenerateTokens(): void
    {
        $_SESSION['csrf_tokens'] = [];
    }
}

class CSRFException extends \Exception
{
    public function __construct(string $message = 'CSRF validation failed')
    {
        parent::__construct($message, 403);
    }
}