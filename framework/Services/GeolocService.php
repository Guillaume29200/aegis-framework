<?php
declare(strict_types=1);

namespace Framework\Services;

/**
 * Service GeolocService - Géolocalisation IP
 *
 * Stratégie multi-provider avec fallback automatique :
 *   1. ipapi.co      — gratuit, 1 000 req/jour, fonctionne depuis VPS/datacenter
 *   2. ipinfo.io     — gratuit, 50 000 req/mois, fonctionne depuis VPS/datacenter
 *   3. ip-api.com    — gratuit mais bloque les IPs datacenter (dernier recours)
 *
 * Si tous les providers échouent, les champs geo restent NULL en BDD.
 */
class GeolocService
{
    private array $cache = [];

    /** Mettre à true uniquement pour diagnostiquer la géoloc (sinon logs parasites). */
    private const DEBUG = false;

    private static function dbg(string $message): void
    {
        if (self::DEBUG) { error_log($message); }
    }

    // ─── Providers déclarés dans l'ordre de priorité ──────────────────────────

    private const PROVIDERS = [
        'ipapi_co',
        'ipinfo_io',
        'ip_api_com',
    ];

    // ─── API publique ─────────────────────────────────────────────────────────

    /**
     * Géolocaliser une IP
     * Retourne null si aucun provider ne répond.
     */
    public function locate(string $ip): ?array
    {
        if (isset($this->cache[$ip])) {
            return $this->cache[$ip];
        }

        if ($this->isLocalIP($ip)) {
            return $this->getLocalData();
        }

        foreach (self::PROVIDERS as $provider) {
            $result = $this->tryProvider($provider, $ip);

            if ($result !== null) {
                $this->cache[$ip] = $result;
                return $result;
            }
        }

        // Tous les providers ont échoué — log unique pour ne pas spammer
        self::dbg("[GeolocService] Tous les providers ont échoué pour IP : {$ip}");
        return null;
    }

    /**
     * Géolocaliser l'IP du client courant
     */
    public function locateCurrent(): ?array
    {
        return $this->locate(self::getClientIP());
    }

    /**
     * Obtenir l'IP réelle du client (Cloudflare, proxies, load balancers)
     */
    public static function getClientIP(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',   // Cloudflare
            'HTTP_X_FORWARDED_FOR',    // Standard proxy
            'HTTP_X_REAL_IP',          // Nginx
            'HTTP_CLIENT_IP',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip  = trim($ips[0]);

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    // ─── Providers ────────────────────────────────────────────────────────────

    /**
     * Dispatcher vers le bon provider
     */
    private function tryProvider(string $provider, string $ip): ?array
    {
        try {
            return match ($provider) {
                'ipapi_co'   => $this->fetchIpapiCo($ip),
                'ipinfo_io'  => $this->fetchIpinfoIo($ip),
                'ip_api_com' => $this->fetchIpApiCom($ip),
                default      => null,
            };
        } catch (\Throwable $e) {
            self::dbg("[GeolocService] Provider {$provider} exception pour {$ip} : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Provider 1 : ipapi.co
     * Endpoint : https://ipapi.co/{ip}/json/
     * Limite   : 1 000 req/jour — fonctionne depuis VPS
     */
    private function fetchIpapiCo(string $ip): ?array
    {
        $url  = "https://ipapi.co/{$ip}/json/";
        $body = $this->curl($url);

        if ($body === null) {
            return null;
        }

        $data = json_decode($body, true);

        if (!is_array($data) || isset($data['error'])) {
            self::dbg("[GeolocService] ipapi.co erreur pour {$ip} : " . ($data['reason'] ?? $body));
            return null;
        }

        return [
            'country_code' => $data['country_code']  ?? null,
            'country_name' => $data['country_name']   ?? null,
            'city'         => $data['city']           ?? null,
            'latitude'     => isset($data['latitude'])  ? (float)$data['latitude']  : null,
            'longitude'    => isset($data['longitude']) ? (float)$data['longitude'] : null,
            'isp'          => $data['org']            ?? null,
            'timezone'     => $data['timezone']       ?? null,
        ];
    }

    /**
     * Provider 2 : ipinfo.io
     * Endpoint : https://ipinfo.io/{ip}/json
     * Limite   : 50 000 req/mois — fonctionne depuis VPS
     */
    private function fetchIpinfoIo(string $ip): ?array
    {
        $url  = "https://ipinfo.io/{$ip}/json";
        $body = $this->curl($url);

        if ($body === null) {
            return null;
        }

        $data = json_decode($body, true);

        if (!is_array($data) || isset($data['error'])) {
            self::dbg("[GeolocService] ipinfo.io erreur pour {$ip} : " . json_encode($data['error'] ?? $body));
            return null;
        }

        // ipinfo.io retourne "lat,lng" dans le champ "loc"
        $lat = null;
        $lon = null;
        if (!empty($data['loc'])) {
            [$lat, $lon] = explode(',', $data['loc']);
            $lat = (float)$lat;
            $lon = (float)$lon;
        }

        return [
            'country_code' => $data['country']  ?? null,
            'country_name' => $this->countryCodeToName($data['country'] ?? ''),
            'city'         => $data['city']     ?? null,
            'latitude'     => $lat,
            'longitude'    => $lon,
            'isp'          => $data['org']      ?? null,
            'timezone'     => $data['timezone'] ?? null,
        ];
    }

    /**
     * Provider 3 : ip-api.com
     * Endpoint : http://ip-api.com/json/{ip}
     * Limite   : 45 req/min — BLOQUE les IPs datacenter
     * Conservé comme dernier recours (IPs résidentielles)
     */
    private function fetchIpApiCom(string $ip): ?array
    {
        $url  = "http://ip-api.com/json/{$ip}?fields=status,country,countryCode,city,lat,lon,isp,timezone";
        $body = $this->curl($url);

        if ($body === null) {
            return null;
        }

        // ip-api.com peut retourner "Host not allowed" en texte brut
        if (!str_starts_with(trim($body), '{')) {
            self::dbg("[GeolocService] ip-api.com réponse non-JSON pour {$ip} : {$body}");
            return null;
        }

        $data = json_decode($body, true);

        if (!is_array($data) || ($data['status'] ?? '') !== 'success') {
            self::dbg("[GeolocService] ip-api.com status échec pour {$ip} : " . ($data['message'] ?? $body));
            return null;
        }

        return [
            'country_code' => $data['countryCode'] ?? null,
            'country_name' => $data['country']     ?? null,
            'city'         => $data['city']        ?? null,
            'latitude'     => isset($data['lat'])   ? (float)$data['lat'] : null,
            'longitude'    => isset($data['lon'])   ? (float)$data['lon'] : null,
            'isp'          => $data['isp']          ?? null,
            'timezone'     => $data['timezone']     ?? null,
        ];
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Exécuter une requête cURL GET avec timeout
     * Retourne null en cas d'erreur réseau ou HTTP != 200
     */
    private function curl(string $url, int $timeout = 5): ?string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 2,
            CURLOPT_USERAGENT      => 'eSport-CMS/4.0 GeolocService',
            CURLOPT_SSL_VERIFYPEER => false, // PHP 8.5 + certains VPS ont des soucis SSL
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $body     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        $curlErrNo= curl_errno($ch);

        // PHP 8.5 : curl_close() est deprecated — on utilise unset() à la place
        unset($ch);

        if ($curlErr) {
            self::dbg("[GeolocService] cURL erreur sur {$url} : {$curlErr} (errno {$curlErrNo})");
            return null;
        }

        if ($httpCode !== 200) {
            self::dbg("[GeolocService] HTTP {$httpCode} sur {$url}");
            return null;
        }

        return $body ?: null;
    }

    /**
     * Déterminer si l'IP est locale / privée
     */
    private function isLocalIP(string $ip): bool
    {
        // IPv6 loopback / link-local
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return str_starts_with($ip, '::1') || str_starts_with($ip, 'fe80:');
        }

        $localRanges = [
            '127.0.0.0/8',
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            '169.254.0.0/16',
        ];

        foreach ($localRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    private function ipInRange(string $ip, string $range): bool
    {
        [$subnet, $mask] = explode('/', $range);
        $ipLong     = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong   = -1 << (32 - (int)$mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }

    /**
     * Données renvoyées pour les IPs locales
     */
    private function getLocalData(): array
    {
        return [
            'country_code' => 'XX',
            'country_name' => 'Local',
            'city'         => 'Localhost',
            'latitude'     => null,
            'longitude'    => null,
            'isp'          => 'Local Network',
            'timezone'     => date_default_timezone_get(),
        ];
    }

    /**
     * Conversion code pays ISO → nom lisible
     * Utilisé quand le provider ne renvoie que le code (ipinfo.io)
     */
    private function countryCodeToName(string $code): ?string
    {
        if (empty($code)) {
            return null;
        }

        $countries = [
            'FR' => 'France', 'DE' => 'Germany', 'GB' => 'United Kingdom',
            'US' => 'United States', 'CA' => 'Canada', 'BE' => 'Belgium',
            'CH' => 'Switzerland', 'LU' => 'Luxembourg', 'NL' => 'Netherlands',
            'ES' => 'Spain', 'IT' => 'Italy', 'PT' => 'Portugal',
            'PL' => 'Poland', 'RO' => 'Romania', 'CZ' => 'Czech Republic',
            'SE' => 'Sweden', 'NO' => 'Norway', 'DK' => 'Denmark',
            'FI' => 'Finland', 'AT' => 'Austria', 'RU' => 'Russia',
            'CN' => 'China', 'JP' => 'Japan', 'KR' => 'South Korea',
            'IN' => 'India', 'AU' => 'Australia', 'BR' => 'Brazil',
            'MX' => 'Mexico', 'AR' => 'Argentina', 'ZA' => 'South Africa',
            'NG' => 'Nigeria', 'EG' => 'Egypt', 'TR' => 'Turkey',
            'SA' => 'Saudi Arabia', 'AE' => 'United Arab Emirates',
            'SG' => 'Singapore', 'MY' => 'Malaysia', 'TH' => 'Thailand',
            'VN' => 'Vietnam', 'ID' => 'Indonesia', 'PH' => 'Philippines',
        ];

        return $countries[$code] ?? $code;
    }
}