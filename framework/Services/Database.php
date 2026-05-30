<?php
declare(strict_types=1);

namespace Framework\Services;

use PDO;
use PDOException;

/**
 * Service Database - Gestion connexion et requêtes
 * 
 * CRITIQUE: Utilise TOUJOURS des prepared statements
 * Support MySQL, PostgreSQL, SQLite
 */
class Database
{
    private ?PDO $pdo = null;
    private array $config;
    private array $queryLog = [];
    private bool $logQueries;
    
    public function __construct(array $dbConfig, array $envConfig)
    {
        $this->config = $dbConfig;
        $this->logQueries = $envConfig['log_queries'] ?? false;
    }
    
    /**
     * Obtenir connexion PDO (lazy loading)
     */
    public function getPDO(): PDO
    {
        if ($this->pdo === null) {
            $this->connect();
        }
        
        return $this->pdo;
    }
    
    /**
     * Établir connexion
     */
    private function connect(): void
    {
        try {
            $dsn = $this->buildDSN();
            
            $this->pdo = new PDO(
                $dsn,
                $this->config['username'] ?? null,
                $this->config['password'] ?? null,
                $this->config['options']
            );
            
            // Charset + session variables pour MySQL
            if ($this->config['type'] === 'mysql') {
                $this->pdo->exec("SET NAMES '{$this->config['charset']}'");
                // Réinitialise sql_select_limit au cas où une conf WAMP le limiterait
                $this->pdo->exec("SET SESSION sql_select_limit = DEFAULT");
            }
            
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Database connection failed: " . $e->getMessage()
            );
        }
    }
    
    /**
     * Construire DSN selon le type de DB
     */
    private function buildDSN(): string
    {
        switch ($this->config['type']) {
            case 'mysql':
                return sprintf(
                    "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                    $this->config['host'],
                    $this->config['port'],
                    $this->config['database'],
                    $this->config['charset']
                );
            
            case 'postgresql':
                return sprintf(
                    "pgsql:host=%s;port=%d;dbname=%s",
                    $this->config['host'],
                    $this->config['port'],
                    $this->config['database']
                );
            
            case 'sqlite':
                return 'sqlite:' . $this->config['sqlite']['path'];
            
            default:
                throw new DatabaseException("Unsupported database type: {$this->config['type']}");
        }
    }
    
    /**
     * Préparer une requête (retourne PDOStatement)
     */
    public function prepare(string $sql): \PDOStatement
    {
        return $this->getPDO()->prepare($sql);
    }
    
    /**
     * Exécuter requête SELECT
     */
    public function query(string $sql, array $params = []): array
    {
        $startTime = microtime(true);
        
        try {
            $stmt = $this->getPDO()->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll();
            
            // Logger si activé
            if ($this->logQueries) {
                $this->logQuery($sql, $params, microtime(true) - $startTime);
            }
            
            return $result;
            
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Query failed: " . $e->getMessage() . " | SQL: {$sql}"
            );
        }
    }
    
    /**
     * Exécuter requête SELECT (une seule ligne)
     */
    public function queryOne(string $sql, array $params = []): ?array
    {
        $startTime = microtime(true);
        
        try {
            $stmt = $this->getPDO()->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            if ($this->logQueries) {
                $this->logQuery($sql, $params, microtime(true) - $startTime);
            }
            
            return $result ?: null;
            
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Query failed: " . $e->getMessage() . " | SQL: {$sql}"
            );
        }
    }
    
    /**
     * Exécuter requête INSERT/UPDATE/DELETE
     */
    public function execute(string $sql, array $params = []): int
    {
        $startTime = microtime(true);
        
        try {
            $stmt = $this->getPDO()->prepare($sql);
            $stmt->execute($params);
            $affectedRows = $stmt->rowCount();
            
            if ($this->logQueries) {
                $this->logQuery($sql, $params, microtime(true) - $startTime);
            }
            
            return $affectedRows;
            
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Execute failed: " . $e->getMessage() . " | SQL: {$sql}"
            );
        }
    }
    
    /**
     * Insérer et retourner ID
     */
    public function insert(string $table, array $data): int
    {
        // Construire requête
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
        
        $this->execute($sql, array_values($data));
        
        return (int) $this->getPDO()->lastInsertId();
    }
    
    /**
     * Mettre à jour
     */
    public function update(string $table, array $data, array $where): int
    {
        // Construire SET
        $sets = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $sets[] = "{$key} = ?";
            $values[] = $value;
        }
        
        // Construire WHERE
        $conditions = [];
        foreach ($where as $key => $value) {
            $conditions[] = "{$key} = ?";
            $values[] = $value;
        }
        
        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', $sets),
            implode(' AND ', $conditions)
        );
        
        return $this->execute($sql, $values);
    }
    
    /**
     * Supprimer
     */
    public function delete(string $table, array $where): int
    {
        $conditions = [];
        $values = [];
        
        foreach ($where as $key => $value) {
            $conditions[] = "{$key} = ?";
            $values[] = $value;
        }
        
        $sql = sprintf(
            "DELETE FROM %s WHERE %s",
            $table,
            implode(' AND ', $conditions)
        );
        
        return $this->execute($sql, $values);
    }
    
    /**
     * Démarrer transaction
     */
    public function beginTransaction(): bool
    {
        return $this->getPDO()->beginTransaction();
    }
    
    /**
     * Valider transaction
     */
    public function commit(): bool
    {
        return $this->getPDO()->commit();
    }
    
    /**
     * Annuler transaction
     */
    public function rollback(): bool
    {
        return $this->getPDO()->rollBack();
    }
    
    /**
     * Logger une requête
     */
    private function logQuery(string $sql, array $params, float $time): void
    {
        $this->queryLog[] = [
            'sql' => $sql,
            'params' => $params,
            'time' => $time,
            'slow' => $time > 0.1 // > 100ms
        ];
    }
    
    /**
     * Obtenir log des requêtes
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }
    
    /**
     * Tester connexion
     */
    public function testConnection(): bool
    {
        try {
            $this->getPDO()->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}

/**
 * Exception Database
 */
class DatabaseException extends \Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message, 500);
    }
}