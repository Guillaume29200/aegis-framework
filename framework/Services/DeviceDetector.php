<?php
declare(strict_types=1);

namespace Framework\Services;

/**
 * Service DeviceDetector - Parse User-Agent
 * 
 * Extrait depuis User-Agent:
 * - Navigateur (Chrome, Firefox, Safari...)
 * - Version navigateur
 * - OS (Windows, macOS, Linux, Android, iOS...)
 * - Type de device (desktop, mobile, tablet, bot)
 */
class DeviceDetector
{
    private string $userAgent;
    
    public function __construct(?string $userAgent = null)
    {
        $this->userAgent = $userAgent ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
    }
    
    /**
     * Obtenir toutes les infos
     */
    public function getAll(): array
    {
        return [
            'user_agent' => $this->userAgent,
            'browser' => $this->getBrowser(),
            'browser_version' => $this->getBrowserVersion(),
            'os' => $this->getOS(),
            'device_type' => $this->getDeviceType(),
        ];
    }
    
    /**
     * Détecter navigateur
     */
    public function getBrowser(): string
    {
        $browsers = [
            'Edg' => 'Edge',
            'Chrome' => 'Chrome',
            'Safari' => 'Safari',
            'Firefox' => 'Firefox',
            'Opera' => 'Opera',
            'MSIE' => 'Internet Explorer',
            'Trident' => 'Internet Explorer',
        ];
        
        foreach ($browsers as $key => $name) {
            if (stripos($this->userAgent, $key) !== false) {
                // Safari doit être testé après Chrome (car Chrome contient "Safari")
                if ($key === 'Safari' && stripos($this->userAgent, 'Chrome') !== false) {
                    continue;
                }
                return $name;
            }
        }
        
        return 'Unknown';
    }
    
    /**
     * Détecter version navigateur
     */
    public function getBrowserVersion(): string
    {
        $browser = $this->getBrowser();
        
        $patterns = [
            'Chrome' => '/Chrome\/([0-9.]+)/',
            'Firefox' => '/Firefox\/([0-9.]+)/',
            'Safari' => '/Version\/([0-9.]+)/',
            'Edge' => '/Edg\/([0-9.]+)/',
            'Opera' => '/Opera\/([0-9.]+)/',
            'Internet Explorer' => '/(?:MSIE |rv:)([0-9.]+)/',
        ];
        
        if (isset($patterns[$browser])) {
            if (preg_match($patterns[$browser], $this->userAgent, $matches)) {
                return $matches[1];
            }
        }
        
        return 'Unknown';
    }
    
    /**
     * Détecter OS
     */
    public function getOS(): string
    {
        $os = [
            'Windows NT 10.0' => 'Windows 10',
            'Windows NT 6.3' => 'Windows 8.1',
            'Windows NT 6.2' => 'Windows 8',
            'Windows NT 6.1' => 'Windows 7',
            'Windows NT 6.0' => 'Windows Vista',
            'Windows NT 5.1' => 'Windows XP',
            'Mac OS X' => 'macOS',
            'Android' => 'Android',
            'iPhone' => 'iOS',
            'iPad' => 'iOS',
            'Linux' => 'Linux',
            'Ubuntu' => 'Ubuntu',
            'CrOS' => 'Chrome OS',
        ];
        
        foreach ($os as $key => $name) {
            if (stripos($this->userAgent, $key) !== false) {
                // Pour macOS, extraire version
                if ($name === 'macOS') {
                    if (preg_match('/Mac OS X ([0-9_]+)/', $this->userAgent, $matches)) {
                        $version = str_replace('_', '.', $matches[1]);
                        return "macOS {$version}";
                    }
                }
                
                // Pour Android, extraire version
                if ($name === 'Android') {
                    if (preg_match('/Android ([0-9.]+)/', $this->userAgent, $matches)) {
                        return "Android {$matches[1]}";
                    }
                }
                
                return $name;
            }
        }
        
        return 'Unknown';
    }
    
    /**
     * Détecter type de device
     */
    public function getDeviceType(): string
    {
        // Bot
        if ($this->isBot()) {
            return 'bot';
        }
        
        // Mobile
        $mobileKeywords = ['Mobile', 'Android', 'iPhone', 'iPod', 'BlackBerry', 'Windows Phone'];
        foreach ($mobileKeywords as $keyword) {
            if (stripos($this->userAgent, $keyword) !== false) {
                return 'mobile';
            }
        }
        
        // Tablet
        $tabletKeywords = ['iPad', 'Tablet', 'Kindle'];
        foreach ($tabletKeywords as $keyword) {
            if (stripos($this->userAgent, $keyword) !== false) {
                return 'tablet';
            }
        }
        
        // Desktop par défaut
        return 'desktop';
    }
    
    /**
     * Vérifier si c'est un bot
     */
    public function isBot(): bool
    {
        $bots = [
            'Googlebot',
            'bingbot',
            'Yahoo! Slurp',
            'DuckDuckBot',
            'Baiduspider',
            'YandexBot',
            'facebookexternalhit',
            'WhatsApp',
            'Discordbot',
        ];
        
        foreach ($bots as $bot) {
            if (stripos($this->userAgent, $bot) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Vérifier si mobile
     */
    public function isMobile(): bool
    {
        return $this->getDeviceType() === 'mobile';
    }
    
    /**
     * Vérifier si tablet
     */
    public function isTablet(): bool
    {
        return $this->getDeviceType() === 'tablet';
    }
    
    /**
     * Vérifier si desktop
     */
    public function isDesktop(): bool
    {
        return $this->getDeviceType() === 'desktop';
    }
}
