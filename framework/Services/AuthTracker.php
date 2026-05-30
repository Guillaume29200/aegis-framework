<?php
declare(strict_types=1);

namespace Framework\Services;

use Framework\Services\Database;
use Framework\Services\DeviceDetector;
use Framework\Services\GeolocService;

/**
 * Service AuthTracker - Tracking connexions/inscriptions
 * 
 * Enregistre pour chaque connexion/inscription:
 * - IP + géolocalisation
 * - User-Agent complet
 * - Navigateur, OS, device
 * - Résolution écran (via JS côté client)
 * - Timezone
 */
class AuthTracker
{
    private Database $db;
    private DeviceDetector $deviceDetector;
    private GeolocService $geolocService;
    
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->deviceDetector = new DeviceDetector();
        $this->geolocService = new GeolocService();
    }
    
    /**
     * Tracker une inscription
     */
    public function trackRegistration(int $userId, ?string $screenResolution = null): void
    {
        $ip = GeolocService::getClientIP();
        $deviceInfo = $this->deviceDetector->getAll();
        $geoData = $this->geolocService->locate($ip);
        
        $data = [
            'user_id' => $userId,
            'registration_ip' => $ip,
            'registration_user_agent' => $deviceInfo['user_agent'],
            'registration_browser' => $deviceInfo['browser'],
            'registration_browser_version' => $deviceInfo['browser_version'],
            'registration_os' => $deviceInfo['os'],
            'registration_device' => $deviceInfo['device_type'],
            'registration_screen_resolution' => $screenResolution,
            'registration_country_code' => $geoData['country_code'] ?? null,
            'registration_country' => $geoData['country_name'] ?? null,
            'registration_city' => $geoData['city'] ?? null,
            'registration_latitude' => $geoData['latitude'] ?? null,
            'registration_longitude' => $geoData['longitude'] ?? null,
            'registration_isp' => $geoData['isp'] ?? null,
            'registration_timezone' => $geoData['timezone'] ?? date_default_timezone_get(),
        ];
        
        try {
            $this->db->insert('user_registration_data', $data);
        } catch (\Exception $e) {
            error_log("AuthTracker registration error: " . $e->getMessage());
        }
    }
    
    /**
     * Tracker une connexion
     */
    public function trackLogin(int $userId, bool $success = true, ?string $screenResolution = null): int
    {
        $ip = GeolocService::getClientIP();
        $deviceInfo = $this->deviceDetector->getAll();
        $geoData = $this->geolocService->locate($ip);
        
        $data = [
            'user_id' => $userId,
            'ip_address' => $ip,
            'user_agent' => $deviceInfo['user_agent'],
            'browser' => $deviceInfo['browser'],
            'browser_version' => $deviceInfo['browser_version'],
            'os' => $deviceInfo['os'],
            'device_type' => $deviceInfo['device_type'],
            'screen_resolution' => $screenResolution,
            'country_code' => $geoData['country_code'] ?? null,
            'country_name' => $geoData['country_name'] ?? null,
            'city' => $geoData['city'] ?? null,
            'latitude' => $geoData['latitude'] ?? null,
            'longitude' => $geoData['longitude'] ?? null,
            'isp' => $geoData['isp'] ?? null,
            'timezone' => $geoData['timezone'] ?? date_default_timezone_get(),
            'login_success' => $success ? 1 : 0,
        ];
        
        try {
            return $this->db->insert('user_logins', $data);
        } catch (\Exception $e) {
            error_log("AuthTracker login error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtenir historique connexions utilisateur
     */
    public function getUserLogins(int $userId, int $limit = 50): array
    {
        return $this->db->query(
            "SELECT * FROM user_logins 
             WHERE user_id = ? 
             ORDER BY created_at DESC 
             LIMIT ?",
            [$userId, $limit]
        );
    }
    
    /**
     * Obtenir dernière connexion
     */
    public function getLastLogin(int $userId): ?array
    {
        return $this->db->queryOne(
            "SELECT * FROM user_logins 
             WHERE user_id = ? AND login_success = 1 
             ORDER BY created_at DESC 
             LIMIT 1",
            [$userId]
        );
    }
    
    /**
     * Obtenir données d'inscription
     */
    public function getRegistrationData(int $userId): ?array
    {
        return $this->db->queryOne(
            "SELECT * FROM user_registration_data WHERE user_id = ?",
            [$userId]
        );
    }
    
    /**
     * Détecter connexion suspecte
     */
    public function detectSuspiciousLogin(int $userId): array
    {
        $suspiciousReasons = [];
        
        // Récupérer dernière connexion réussie
        $lastLogin = $this->getLastLogin($userId);
        
        if (!$lastLogin) {
            return $suspiciousReasons; // Première connexion = OK
        }
        
        // Connexion actuelle
        $currentIp = GeolocService::getClientIP();
        $currentDevice = $this->deviceDetector->getAll();
        $currentGeo = $this->geolocService->locate($currentIp);
        
        // Vérifier changement pays
        if (isset($currentGeo['country_code'], $lastLogin['country_code'])) {
            if ($currentGeo['country_code'] !== $lastLogin['country_code']) {
                $suspiciousReasons[] = [
                    'type' => 'country_change',
                    'message' => "Connexion depuis un nouveau pays: {$currentGeo['country_name']}",
                    'severity' => 'high'
                ];
            }
        }
        
        // Vérifier changement device type
        if ($currentDevice['device_type'] !== $lastLogin['device_type']) {
            $suspiciousReasons[] = [
                'type' => 'device_change',
                'message' => "Connexion depuis un nouveau type d'appareil: {$currentDevice['device_type']}",
                'severity' => 'medium'
            ];
        }
        
        // Vérifier changement OS
        if ($currentDevice['os'] !== $lastLogin['os']) {
            $suspiciousReasons[] = [
                'type' => 'os_change',
                'message' => "Connexion depuis un nouveau système: {$currentDevice['os']}",
                'severity' => 'medium'
            ];
        }
        
        // Vérifier connexion trop rapide depuis IPs différentes
        $lastLoginTime = strtotime($lastLogin['created_at']);
        $timeDiff = time() - $lastLoginTime;
        
        if ($timeDiff < 60 && $currentIp !== $lastLogin['ip_address']) {
            $suspiciousReasons[] = [
                'type' => 'rapid_ip_change',
                'message' => "Connexion depuis une IP différente en moins d'1 minute",
                'severity' => 'high'
            ];
        }
        
        return $suspiciousReasons;
    }
    
    /**
     * Obtenir statistiques de connexion (pour dashboard admin)
     */
    public function getLoginStats(int $days = 30): array
    {
        $stats = [];
        
        // Connexions par jour
        $stats['daily_logins'] = $this->db->query(
            "SELECT DATE(created_at) as date, COUNT(*) as count
             FROM user_logins
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             AND login_success = 1
             GROUP BY DATE(created_at)
             ORDER BY date DESC",
            [$days]
        );
        
        // Connexions par pays
        $stats['by_country'] = $this->db->query(
            "SELECT country_name, COUNT(*) as count
             FROM user_logins
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             AND login_success = 1
             AND country_name IS NOT NULL
             GROUP BY country_name
             ORDER BY count DESC
             LIMIT 10",
            [$days]
        );
        
        // Connexions par device
        $stats['by_device'] = $this->db->query(
            "SELECT device_type, COUNT(*) as count
             FROM user_logins
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             AND login_success = 1
             GROUP BY device_type
             ORDER BY count DESC",
            [$days]
        );
        
        // Connexions par navigateur
        $stats['by_browser'] = $this->db->query(
            "SELECT browser, COUNT(*) as count
             FROM user_logins
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             AND login_success = 1
             GROUP BY browser
             ORDER BY count DESC
             LIMIT 10",
            [$days]
        );
        
        // Tentatives échouées
        $stats['failed_attempts'] = $this->db->queryOne(
            "SELECT COUNT(*) as count
             FROM user_logins
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             AND login_success = 0",
            [$days]
        )['count'] ?? 0;
        
        return $stats;
    }
    
    /**
     * Obtenir données pour carte géographique
     */
    public function getMapData(?int $userId = null, int $days = 30): array
    {
        $params = [$days];
        $whereClause = '';
        
        if ($userId) {
            $whereClause = 'AND user_id = ?';
            $params[] = $userId;
        }
        
        return $this->db->query(
            "SELECT latitude, longitude, city, country_name, COUNT(*) as count
             FROM user_logins
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             AND login_success = 1
             AND latitude IS NOT NULL
             AND longitude IS NOT NULL
             {$whereClause}
             GROUP BY latitude, longitude, city, country_name
             ORDER BY count DESC",
            $params
        );
    }
}
