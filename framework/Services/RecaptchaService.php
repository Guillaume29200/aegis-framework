<?php
declare(strict_types=1);

namespace Framework\Services;

/**
 * Service reCAPTCHA v3 - STANDALONE
 * Lit directement dans la table settings (ne dépend pas de SettingsService)
 */
class RecaptchaService
{
    private array $config;
    private Database $db;
    private array $settings = [];
    
    public function __construct(array $config, $dbOrSettings)
    {
        $this->config = $config;
        
        // Si c'est une Database, charger les settings
        if ($dbOrSettings instanceof Database) {
            $this->db = $dbOrSettings;
            $this->loadSettings();
        }
        // Si c'est SettingsService (futur), utiliser directement
        else if (is_object($dbOrSettings) && method_exists($dbOrSettings, 'get')) {
            $this->settings = $this->loadFromSettingsService($dbOrSettings);
        }
    }
    
    /**
     * Charger settings depuis Database directement
     */
    private function loadSettings(): void
    {
        try {
            $pdo = $this->db->getPDO();
            $stmt = $pdo->query("SELECT param_key, param_value, param_type FROM settings");
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($results as $row) {
                $this->settings[$row['param_key']] = $this->castValue($row['param_value'], $row['param_type']);
            }
        } catch (\Exception $e) {
            error_log("RecaptchaService - Erreur: " . $e->getMessage());
            $this->settings = [];
        }
    }
    
    /**
     * Charger depuis SettingsService
     */
    private function loadFromSettingsService($settingsService): array
    {
        return [
            'recaptcha_enabled' => $settingsService->get('recaptcha_enabled', false),
            'recaptcha_site_key' => $settingsService->get('recaptcha_site_key', ''),
            'recaptcha_secret_key' => $settingsService->get('recaptcha_secret_key', ''),
            'recaptcha_login' => $settingsService->get('recaptcha_login', false),
            'recaptcha_register' => $settingsService->get('recaptcha_register', false),
            'recaptcha_forum_post' => $settingsService->get('recaptcha_forum_post', false),
            'recaptcha_forum_reply' => $settingsService->get('recaptcha_forum_reply', false),
            'recaptcha_comments' => $settingsService->get('recaptcha_comments', false),
        ];
    }
    
    /**
     * Caster valeur
     */
    private function castValue($value, string $type)
    {
        return match($type) {
            'int' => (int) $value,
            'bool' => (bool) $value,
            'json' => json_decode($value, true),
            default => (string) $value
        };
    }
    
    /**
     * Obtenir un setting
     */
    private function get(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }
    
    /**
     * Vérifier si activé
     */
    public function isEnabled(): bool
    {
        return (bool) $this->get('recaptcha_enabled', false);
    }
    
    /**
     * Vérifier si configuré
     */
    public function isConfigured(): bool
    {
        $siteKey = $this->get('recaptcha_site_key', '');
        $secretKey = $this->get('recaptcha_secret_key', '');
        return !empty($siteKey) && !empty($secretKey);
    }
    
    /**
     * Vérifier si actif pour une zone
     */
    public function isActiveForZone(string $zone): bool
    {
        if (!$this->isConfigured() || !$this->isEnabled()) {
            return false;
        }
        
        $zoneKey = 'recaptcha_' . $zone;
        return (bool) $this->get($zoneKey, false);
    }
    
    /**
     * Obtenir Site Key
     */
    public function getSiteKey(): string
    {
        return $this->get('recaptcha_site_key', '');
    }
    
    /**
     * Obtenir Secret Key
     */
    private function getSecretKey(): string
    {
        return $this->get('recaptcha_secret_key', '');
    }
    
    /**
     * Valider un token
     */
    public function verify(string $token, string $action = 'submit', float $minScore = 0.5): array
    {
        if (!$this->isConfigured() || !$this->isEnabled()) {
            return [
                'success' => true,
                'score' => 1.0,
                'bypass' => true,
                'message' => 'reCAPTCHA non activé'
            ];
        }
        
        if (empty($token)) {
            return [
                'success' => false,
                'score' => 0.0,
                'error' => 'Token manquant'
            ];
        }
        
        try {
            $data = [
                'secret' => $this->getSecretKey(),
                'response' => $token,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ];
            
            $response = $this->sendVerificationRequest($data);
            
            if (!$response || !$response['success']) {
                return [
                    'success' => false,
                    'score' => 0.0,
                    'error' => 'Validation échouée'
                ];
            }
            
            $score = $response['score'] ?? 0.0;
            
            if ($score < $minScore) {
                return [
                    'success' => false,
                    'score' => $score,
                    'error' => 'Score trop faible: ' . $score
                ];
            }
            
            return [
                'success' => true,
                'score' => $score,
                'hostname' => $response['hostname'] ?? '',
                'challenge_ts' => $response['challenge_ts'] ?? ''
            ];
            
        } catch (\Exception $e) {
            error_log('RecaptchaService::verify() - ' . $e->getMessage());
            return [
                'success' => false,
                'score' => 0.0,
                'error' => 'Erreur interne'
            ];
        }
    }
    
    /**
     * Envoyer requête à Google
     */
    private function sendVerificationRequest(array $data): ?array
    {
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200 || !$response) {
                return null;
            }
            
            return json_decode($response, true);
        }
        
        return null;
    }
    
    /**
     * Générer le script HTML
     */
    public function renderScript(): string
    {
        if (!$this->isConfigured() || !$this->isEnabled()) {
            return '<!-- reCAPTCHA désactivé -->';
        }
        
        $siteKey = $this->getSiteKey();
        return sprintf(
            '<script src="https://www.google.com/recaptcha/api.js?render=%s"></script>',
            htmlspecialchars($siteKey, ENT_QUOTES, 'UTF-8')
        );
    }
}