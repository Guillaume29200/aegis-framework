<?php
declare(strict_types=1);

namespace Framework\Security;

use Framework\Services\Database;

/**
 * Service Rate Limiter
 * 
 * PHP 8.4+ Compatible
 * Protection contre attaques par force brute
 * 
 * Fonctionnalités:
 * - Limite requêtes par IP/User
 * - Blocage temporaire automatique
 * - Différents niveaux (global, auth, API, sensitive)
 * - Nettoyage automatique des entrées expirées
 */
class RateLimiter
{
    private Database $db;
    private array $config;
    
    public function __construct(Database $db, array $config)
    {
        $this->db = $db;
        $this->config = $config['rate_limit'];
    }
    
    /**
     * Vérifier si action autorisée
     * 
     * @param string $identifier IP ou user ID
     * @param string $action Type d'action (auth, api, sensitive, global)
     * @return bool True si autorisé
     * @throws RateLimitException si limite dépassée
     */
    public function check(string $identifier, string $action = 'global'): bool
    {
        if (!$this->config['enabled']) {
            return true;
        }
        
        // Récupérer limites pour cette action
        $limits = $this->getLimits($action);
        
        // Vérifier si bloqué
        if ($this->isBlocked($identifier, $action)) {
            throw new RateLimitException(
                "Rate limit exceeded for action: {$action}. You are temporarily blocked.",
                $this->getResetTime($identifier, $action)
            );
        }
        
        // Récupérer compteur actuel
        $current = $this->getCurrentCount($identifier, $action);
        
        // Vérifier limite
        $maxRequests = $limits['max_requests'] ?? $limits['max_attempts'] ?? 60;
        if ($current >= $maxRequests) {
            // Bloquer si action sensible avec lockout_duration
            if (isset($limits['lockout_duration'])) {
                $this->block($identifier, $action, $limits['lockout_duration']);
            }
            
            throw new RateLimitException(
                "Rate limit exceeded for action: {$action}",
                $this->getResetTime($identifier, $action)
            );
        }
        
        return true;
    }
    
    /**
     * Incrémenter le compteur
     * 
     * @param string $identifier IP ou user ID
     * @param string $action Type d'action
     */
    public function increment(string $identifier, string $action = 'global'): void
    {
        if (!$this->config['enabled']) {
            return;
        }
        
        $limits = $this->getLimits($action);
        $window = $limits['window'];
        
        $pdo = $this->db->getPDO();
        
        // Vérifier si entrée existe
        $stmt = $pdo->prepare("
            SELECT id, attempts, reset_at
            FROM rate_limits
            WHERE identifier = ? AND action = ?
        ");
        $stmt->execute([$identifier, $action]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Vérifier si fenêtre expirée
            if (strtotime($existing['reset_at']) < time()) {
                // Reset compteur
                $stmt = $pdo->prepare("
                    UPDATE rate_limits
                    SET attempts = 1,
                        reset_at = DATE_ADD(NOW(), INTERVAL ? SECOND)
                    WHERE id = ?
                ");
                $stmt->execute([$window, $existing['id']]);
            } else {
                // Incrémenter
                $stmt = $pdo->prepare("
                    UPDATE rate_limits
                    SET attempts = attempts + 1
                    WHERE id = ?
                ");
                $stmt->execute([$existing['id']]);
            }
        } else {
            // Créer nouvelle entrée
            $stmt = $pdo->prepare("
                INSERT INTO rate_limits (identifier, action, attempts, reset_at)
                VALUES (?, ?, 1, DATE_ADD(NOW(), INTERVAL ? SECOND))
            ");
            $stmt->execute([$identifier, $action, $window]);
        }
    }
    
    /**
     * Obtenir limites pour une action (PHP 8.4 match)
     * 
     * @param string $action Type d'action
     * @return array Limites
     */
    private function getLimits(string $action): array
    {
        return match($action) {
            'auth' => $this->config['auth'],
            'api' => $this->config['api'],
            'sensitive' => $this->config['sensitive'],
            'registration' => $this->config['registration'],
            'password_reset' => $this->config['password_reset'],
            default => $this->config['global']
        };
    }
    
    /**
     * Obtenir compteur actuel
     * 
     * @param string $identifier IP ou user ID
     * @param string $action Type d'action
     * @return int Nombre de tentatives
     */
    private function getCurrentCount(string $identifier, string $action): int
    {
        $pdo = $this->db->getPDO();
        
        $stmt = $pdo->prepare("
            SELECT attempts
            FROM rate_limits
            WHERE identifier = ? 
              AND action = ?
              AND reset_at > NOW()
        ");
        $stmt->execute([$identifier, $action]);
        
        $result = $stmt->fetch();
        return $result ? (int)$result['attempts'] : 0;
    }
    
    /**
     * Vérifier si identifiant bloqué
     * 
     * @param string $identifier IP ou user ID
     * @param string $action Type d'action
     * @return bool True si bloqué
     */
    private function isBlocked(string $identifier, string $action): bool
    {
        $pdo = $this->db->getPDO();
        
        $stmt = $pdo->prepare("
            SELECT id
            FROM rate_limit_blocks
            WHERE identifier = ?
              AND action = ?
              AND blocked_until > NOW()
        ");
        $stmt->execute([$identifier, $action]);
        
        return $stmt->fetch() !== false;
    }
    
    /**
     * Bloquer un identifiant
     * 
     * @param string $identifier IP ou user ID
     * @param string $action Type d'action
     * @param int $duration Durée du ban en secondes
     */
    private function block(string $identifier, string $action, int $duration): void
    {
        $pdo = $this->db->getPDO();
        
        $stmt = $pdo->prepare("
            INSERT INTO rate_limit_blocks (identifier, action, blocked_until)
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))
            ON DUPLICATE KEY UPDATE blocked_until = DATE_ADD(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$identifier, $action, $duration, $duration]);
        
        // Logger le blocage
        $this->logBlock($identifier, $action, $duration);
    }
    
    /**
     * Obtenir timestamp de reset
     * 
     * @param string $identifier IP ou user ID
     * @param string $action Type d'action
     * @return int Timestamp Unix
     */
    private function getResetTime(string $identifier, string $action): int
    {
        $pdo = $this->db->getPDO();
        
        // Vérifier d'abord si bloqué
        $stmt = $pdo->prepare("
            SELECT UNIX_TIMESTAMP(blocked_until) as reset_time
            FROM rate_limit_blocks
            WHERE identifier = ? AND action = ?
        ");
        $stmt->execute([$identifier, $action]);
        
        $result = $stmt->fetch();
        if ($result) {
            return (int)$result['reset_time'];
        }
        
        // Sinon obtenir reset_at de rate_limits
        $stmt = $pdo->prepare("
            SELECT UNIX_TIMESTAMP(reset_at) as reset_time
            FROM rate_limits
            WHERE identifier = ? AND action = ?
        ");
        $stmt->execute([$identifier, $action]);
        
        $result = $stmt->fetch();
        return $result ? (int)$result['reset_time'] : time();
    }
    
    /**
     * Reset compteur (après succès auth par exemple)
     * 
     * @param string $identifier IP ou user ID
     * @param string $action Type d'action
     */
    public function reset(string $identifier, string $action = 'global'): void
    {
        $pdo = $this->db->getPDO();
        
        $stmt = $pdo->prepare("
            DELETE FROM rate_limits
            WHERE identifier = ? AND action = ?
        ");
        $stmt->execute([$identifier, $action]);
    }
    
    /**
     * Débloquer un identifiant
     * 
     * @param string $identifier IP ou user ID
     * @param string $action Type d'action
     */
    public function unblock(string $identifier, string $action = 'global'): void
    {
        $pdo = $this->db->getPDO();
        
        $stmt = $pdo->prepare("
            DELETE FROM rate_limit_blocks
            WHERE identifier = ? AND action = ?
        ");
        $stmt->execute([$identifier, $action]);
    }
    
    /**
     * Obtenir temps restant avant reset
     * 
     * @param string $identifier IP ou user ID
     * @param string $action Type d'action
     * @return int Secondes restantes
     */
    public function getTimeRemaining(string $identifier, string $action = 'global'): int
    {
        $resetTime = $this->getResetTime($identifier, $action);
        return max(0, $resetTime - time());
    }
    
    /**
     * Nettoyer anciennes entrées (CRON)
     * 
     * @return int Nombre d'entrées supprimées
     */
    public function cleanup(): int
    {
        $pdo = $this->db->getPDO();
        
        // Supprimer rate_limits expirés
        $stmt = $pdo->prepare("DELETE FROM rate_limits WHERE reset_at < NOW()");
        $stmt->execute();
        $deleted = $stmt->rowCount();
        
        // Supprimer blocks expirés
        $stmt = $pdo->prepare("DELETE FROM rate_limit_blocks WHERE blocked_until < NOW()");
        $stmt->execute();
        $deleted += $stmt->rowCount();
        
        return $deleted;
    }
    
    /**
     * Obtenir statistiques rate limiting
     * 
     * @param string|null $action Filtrer par action
     * @return array Statistiques
     */
    public function getStats(?string $action = null): array
    {
        $pdo = $this->db->getPDO();
        
        $whereClause = $action ? 'WHERE action = ? AND' : 'WHERE';
        $params = $action ? [$action] : [];
        
        // Compteurs actifs
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as active_limits
            FROM rate_limits
            {$whereClause} reset_at > NOW()
        ");
        $stmt->execute($params);
        $activeLimits = $stmt->fetch()['active_limits'];
        
        // Blocks actifs
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as active_blocks
            FROM rate_limit_blocks
            {$whereClause} blocked_until > NOW()
        ");
        $stmt->execute($params);
        $activeBlocks = $stmt->fetch()['active_blocks'];
        
        return [
            'active_limits' => $activeLimits,
            'active_blocks' => $activeBlocks,
        ];
    }
    
    /**
     * Logger un blocage
     * 
     * @param string $identifier IP ou user ID
     * @param string $action Type d'action
     * @param int $duration Durée du ban
     */
    private function logBlock(string $identifier, string $action, int $duration): void
    {
        error_log(sprintf(
            "[RATE_LIMIT] Blocked: identifier=%s, action=%s, duration=%ds",
            $identifier,
            $action,
            $duration
        ));
    }
}

/**
 * Exception Rate Limit
 */
class RateLimitException extends \Exception
{
    private int $resetTime;
    
    public function __construct(string $message, int $resetTime)
    {
        parent::__construct($message, 429);
        $this->resetTime = $resetTime;
    }
    
    /**
     * Obtenir timestamp de reset
     */
    public function getResetTime(): int
    {
        return $this->resetTime;
    }
    
    /**
     * Obtenir secondes avant retry
     */
    public function getRetryAfter(): int
    {
        return max(0, $this->resetTime - time());
    }
    
    /**
     * Obtenir header HTTP Retry-After
     */
    public function getRetryAfterHeader(): string
    {
        return 'Retry-After: ' . $this->getRetryAfter();
    }
}
