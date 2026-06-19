<?php
/**
 * AIModelService - Gestion des modèles IA
 * 
 * CRUD + filtres sur les modèles d'intelligence artificielle
 */

namespace Configuration\Services;

use Framework\Services\Database;

class AIModelService
{
    private Database $db;
    
    public function __construct(Database $db)
    {
        $this->db = $db;
    }
    
    /**
     * Récupérer tous les modèles
     */
    public function getAll(bool $activeOnly = false): array
    {
        try {
            $sql = "SELECT * FROM ai_models";
            
            if ($activeOnly) {
                $sql .= " WHERE is_active = 1";
            }
            
            $sql .= " ORDER BY provider, display_name";
            
            $stmt = $this->db->getPDO()->query($sql);
            $models = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Décoder les capabilities JSON
            foreach ($models as &$model) {
                $model['capabilities'] = json_decode($model['capabilities'], true);
            }
            
            return $models;
        } catch (\Exception $e) {
            error_log("AIModelService::getAll() - Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer modèles par provider
     */
    public function getByProvider(string $provider, bool $activeOnly = true): array
    {
        try {
            $sql = "SELECT * FROM ai_models WHERE provider = ?";
            
            if ($activeOnly) {
                $sql .= " AND is_active = 1";
            }
            
            $sql .= " ORDER BY display_name";
            
            $stmt = $this->db->getPDO()->prepare($sql);
            $stmt->execute([$provider]);
            $models = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($models as &$model) {
                $model['capabilities'] = json_decode($model['capabilities'], true);
            }
            
            return $models;
        } catch (\Exception $e) {
            error_log("AIModelService::getByProvider() - Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer modèles par capacité
     */
    public function getByCapability(string $capability, bool $activeOnly = true): array
    {
        try {
            // JSON_EXTRACT path built with a placeholder to prevent SQL injection
            $jsonPath = '$."' . addcslashes($capability, '"\\') . '"';
            $sql = "SELECT * FROM ai_models WHERE JSON_EXTRACT(capabilities, ?) = true";
            $params = [$jsonPath];

            if ($activeOnly) {
                $sql .= " AND is_active = 1";
            }

            $sql .= " ORDER BY provider, display_name";

            $stmt = $this->db->getPDO()->prepare($sql);
            $stmt->execute($params);
            $models = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($models as &$model) {
                $model['capabilities'] = json_decode($model['capabilities'], true);
            }
            
            return $models;
        } catch (\Exception $e) {
            error_log("AIModelService::getByCapability() - Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer un modèle par ID
     */
    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db->getPDO()->prepare("SELECT * FROM ai_models WHERE id = ?");
            $stmt->execute([$id]);
            $model = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($model) {
                $model['capabilities'] = json_decode($model['capabilities'], true);
                return $model;
            }
            
            return null;
        } catch (\Exception $e) {
            error_log("AIModelService::getById() - Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupérer le modèle par défaut
     */
    public function getDefault(): ?array
    {
        try {
            $stmt = $this->db->getPDO()->query("SELECT * FROM ai_models WHERE is_default = 1 LIMIT 1");
            $model = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($model) {
                $model['capabilities'] = json_decode($model['capabilities'], true);
                return $model;
            }
            
            return null;
        } catch (\Exception $e) {
            error_log("AIModelService::getDefault() - Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Créer un nouveau modèle
     */
    public function create(array $data): ?int
    {
        try {
            // Si défini comme défaut, retirer le défaut des autres
            if (!empty($data['is_default'])) {
                $this->db->getPDO()->exec("UPDATE ai_models SET is_default = 0");
            }
            
            $sql = "INSERT INTO ai_models
                    (provider, model_name, display_name, capabilities, is_active, is_default, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->getPDO()->prepare($sql);
            $stmt->execute([
                $data['provider'],
                $data['model_name'],
                $data['display_name'],
                json_encode($data['capabilities']),
                $data['is_active'] ?? 1,
                $data['is_default'] ?? 0,
                $data['notes'] ?? null
            ]);
            
            return (int)$this->db->getPDO()->lastInsertId();
        } catch (\Exception $e) {
            error_log("AIModelService::create() - Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Mettre à jour un modèle
     */
    public function update(int $id, array $data): bool
    {
        try {
            // Si défini comme défaut, retirer le défaut des autres
            if (!empty($data['is_default'])) {
                $stmt = $this->db->getPDO()->prepare("UPDATE ai_models SET is_default = 0 WHERE id != ?");
                $stmt->execute([$id]);
            }
            
            $sql = "UPDATE ai_models SET
                    provider = ?,
                    model_name = ?,
                    display_name = ?,
                    capabilities = ?,
                    is_active = ?,
                    is_default = ?,
                    notes = ?
                    WHERE id = ?";

            $stmt = $this->db->getPDO()->prepare($sql);
            return $stmt->execute([
                $data['provider'],
                $data['model_name'],
                $data['display_name'],
                json_encode($data['capabilities']),
                $data['is_active'] ?? 1,
                $data['is_default'] ?? 0,
                $data['notes'] ?? null,
                $id
            ]);
        } catch (\Exception $e) {
            error_log("AIModelService::update() - Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprimer un modèle
     */
    public function delete(int $id): bool
    {
        try {
            $stmt = $this->db->getPDO()->prepare("DELETE FROM ai_models WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (\Exception $e) {
            error_log("AIModelService::delete() - Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Activer/désactiver un modèle
     */
    public function toggleActive(int $id): bool
    {
        try {
            $sql = "UPDATE ai_models SET is_active = NOT is_active WHERE id = ?";
            $stmt = $this->db->getPDO()->prepare($sql);
            return $stmt->execute([$id]);
        } catch (\Exception $e) {
            error_log("AIModelService::toggleActive() - Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Définir comme modèle par défaut
     */
    public function setDefault(int $id): bool
    {
        try {
            $this->db->getPDO()->beginTransaction();
            
            // Retirer défaut de tous
            $this->db->getPDO()->exec("UPDATE ai_models SET is_default = 0");
            
            // Définir le nouveau défaut
            $stmt = $this->db->getPDO()->prepare("UPDATE ai_models SET is_default = 1 WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->db->getPDO()->commit();
            return true;
        } catch (\Exception $e) {
            if ($this->db->getPDO()->inTransaction()) {
                $this->db->getPDO()->rollBack();
            }
            error_log("AIModelService::setDefault() - Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtenir statistiques
     */
    public function getStats(): array
    {
        try {
            $stmt = $this->db->getPDO()->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(is_active) as active,
                    COUNT(DISTINCT provider) as providers
                FROM ai_models
            ");
            
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("AIModelService::getStats() - Error: " . $e->getMessage());
            return ['total' => 0, 'active' => 0, 'providers' => 0];
        }
    }
}
