<?php
/**
 * Service Settings - Module Configuration V4
 * Gestion des paramètres du CMS en base de données
 */

namespace Configuration\Services;

use Framework\Services\Database;

class SettingsService
{
    private $db;
    
    public function __construct(Database $db)
    {
        $this->db = $db;
    }
    
    /**
     * Récupérer TOUS les settings
     */
    public function getAllSettings(): array
    {
        try {
            $pdo = $this->db->getPDO();
            
            $stmt = $pdo->query("SELECT param_key, param_value, param_type FROM settings");
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $settings = [];
            foreach ($results as $row) {
                $settings[$row['param_key']] = $this->castValue($row['param_value'], $row['param_type']);
            }
            
            return $settings;
        } catch (\Exception $e) {
            error_log("SettingsService::getAllSettings() - Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer un setting par sa clé
     */
    public function get(string $key, $default = null)
    {
        try {
            $pdo = $this->db->getPDO();
            
            $stmt = $pdo->prepare("SELECT param_value, param_type FROM settings WHERE param_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($result) {
                return $this->castValue($result['param_value'], $result['param_type']);
            }
            
            return $default;
        } catch (\Exception $e) {
            error_log("SettingsService::get($key) - Error: " . $e->getMessage());
            return $default;
        }
    }
    
    /**
     * Définir un setting
     */
    public function set(string $key, $value, string $type = 'string'): bool
    {
        try {
            $pdo = $this->db->getPDO();
            
            $stringValue = $this->convertToString($value, $type);
            
            $stmt = $pdo->prepare("
                INSERT INTO settings (param_key, param_value, param_type) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE param_value = ?, param_type = ?
            ");
            
            return $stmt->execute([$key, $stringValue, $type, $stringValue, $type]);
        } catch (\Exception $e) {
            error_log("SettingsService::set($key) - Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mettre à jour plusieurs settings en une seule transaction
     */
    public function setMultiple(array $settings): bool
    {
        try {
            $pdo = $this->db->getPDO();
            $pdo->beginTransaction();
            
            foreach ($settings as $key => $data) {
                $value = $data['value'];
                $type = $data['type'] ?? 'string';
                
                $stringValue = $this->convertToString($value, $type);
                
                $stmt = $pdo->prepare("
                    INSERT INTO settings (param_key, param_value, param_type) 
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE param_value = ?, param_type = ?
                ");
                
                $stmt->execute([$key, $stringValue, $type, $stringValue, $type]);
            }
            
            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            error_log("SettingsService::setMultiple() - Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Caster une valeur selon son type
     */
    private function castValue($value, string $type)
    {
        switch ($type) {
            case 'int':
                return (int) $value;
            case 'bool':
                return (bool) $value;
            case 'json':
                return json_decode($value, true);
            case 'string':
            default:
                return (string) $value;
        }
    }
    
    /**
     * Convertir une valeur en string pour la BDD
     */
    private function convertToString($value, string $type): string
    {
        switch ($type) {
            case 'int':
                return (string) (int) $value;
            case 'bool':
                return $value ? '1' : '0';
            case 'json':
                return json_encode($value);
            case 'string':
            default:
                return (string) $value;
        }
    }
    
    /**
     * Vérifier si reCAPTCHA est correctement configuré
     */
    public function isRecaptchaConfigured(): bool
    {
        $siteKey = $this->get('recaptcha_site_key', '');
        $secretKey = $this->get('recaptcha_secret_key', '');
        
        return !empty($siteKey) && !empty($secretKey);
    }
    
    /**
     * Obtenir les settings groupés par section
     */
    public function getSettingsBySection(string $section): array
    {
        $allSettings = $this->getAllSettings();
        
        $sections = [
            'general'  => ['site_name', 'site_description', 'webmaster_email', 'cms_version', 'login_cover_image', 'login_logo_image', 'login_visual_badge', 'login_visual_title', 'login_visual_text'],
            'system'   => ['debug_mode', 'cache_enabled', 'cache_ttl', 'maintenance_mode', 'maintenance_theme', 'turbonav_enabled'],
            'security' => ['registration_enabled', 'cookies_banner_enabled', 'recaptcha_enabled', 'recaptcha_site_key', 'recaptcha_secret_key', 'recaptcha_login', 'recaptcha_register', 'password_reset_from_email', 'password_reset_from_name', 'password_reset_email_subject', 'password_reset_email_body'],
            'seo'      => ['meta_title_template', 'meta_description_default', 'meta_keywords_default'],
            'ai'       => ['openai_api_key', 'claude_api_key', 'mistral_api_key', 'default_ai_provider']
        ];
        
        $result = [];
        if (isset($sections[$section])) {
            foreach ($sections[$section] as $key) {
                $result[$key] = $allSettings[$key] ?? null;
            }
        }
        
        return $result;
    }
}
