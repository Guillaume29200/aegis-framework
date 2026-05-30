<?php
declare(strict_types=1);

namespace Framework\Services;

/**
 * SecurityFirewallService - Protection applicative anti-abus.
 *
 * Cette couche ne remplace pas une protection DDoS reseau type Cloudflare,
 * firewall hebergeur ou reverse proxy. Elle protege GSH contre les floods HTTP,
 * scans de panel, bruteforce et bots qui atteignent PHP.
 */
class SecurityFirewallService
{
    private Database $db;
    private array $config;
    private bool $tablesReady = false;

    public function __construct(Database $db, array $securityConfig)
    {
        $this->db = $db;
        $this->config = $securityConfig['firewall'] ?? [];
    }

    public function isEnabled(): bool
    {
        return (bool)($this->config['enabled'] ?? false);
    }

    public function ensureTables(): void
    {
        if ($this->tablesReady) {
            return;
        }

        $this->db->execute("
            CREATE TABLE IF NOT EXISTS security_ip_blocks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                reason VARCHAR(255) NOT NULL,
                blocked_until DATETIME NULL,
                permanent TINYINT(1) NOT NULL DEFAULT 0,
                created_by INT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_security_ip_blocks_ip (ip_address),
                KEY idx_security_ip_blocks_until (blocked_until),
                KEY idx_security_ip_blocks_permanent (permanent)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->execute("
            CREATE TABLE IF NOT EXISTS security_events (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                event_type VARCHAR(64) NOT NULL,
                severity VARCHAR(24) NOT NULL DEFAULT 'info',
                request_method VARCHAR(10) NULL,
                request_uri VARCHAR(512) NULL,
                user_agent VARCHAR(255) NULL,
                reason TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_security_events_ip_created (ip_address, created_at),
                KEY idx_security_events_type_created (event_type, created_at),
                KEY idx_security_events_severity (severity)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->execute("
            CREATE TABLE IF NOT EXISTS security_rate_counters (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                route_key VARCHAR(64) NOT NULL,
                window_start INT NOT NULL,
                hits INT NOT NULL DEFAULT 1,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_security_rate_window (ip_address, route_key, window_start),
                KEY idx_security_rate_updated (updated_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->tablesReady = true;
    }

    public function getClientIp(): string
    {
        if (!empty($this->config['trust_proxy_headers'])) {
            $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR'];
            foreach ($headers as $header) {
                if (!empty($_SERVER[$header])) {
                    $parts = explode(',', (string)$_SERVER[$header]);
                    $ip = trim($parts[0]);
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function isWhitelisted(string $ip): bool
    {
        $trusted = $this->config['trusted_ips'] ?? [];
        foreach ($trusted as $entry) {
            if ($this->ipMatches($ip, (string)$entry)) {
                return true;
            }
        }

        return false;
    }

    public function getActiveBlock(string $ip): ?array
    {
        $this->ensureTables();

        return $this->db->queryOne("
            SELECT *
            FROM security_ip_blocks
            WHERE ip_address = ?
              AND (permanent = 1 OR blocked_until IS NULL OR blocked_until > NOW())
            LIMIT 1
        ", [$ip]);
    }

    public function blockIp(string $ip, string $reason, int $seconds = 0, bool $permanent = false, ?int $createdBy = null): void
    {
        $this->ensureTables();

        $blockedUntilSql = $permanent || $seconds <= 0
            ? null
            : date('Y-m-d H:i:s', time() + $seconds);

        $this->db->execute("
            INSERT INTO security_ip_blocks (ip_address, reason, blocked_until, permanent, created_by)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                reason = VALUES(reason),
                blocked_until = VALUES(blocked_until),
                permanent = VALUES(permanent),
                created_by = VALUES(created_by),
                created_at = CURRENT_TIMESTAMP
        ", [$ip, $reason, $blockedUntilSql, $permanent ? 1 : 0, $createdBy]);

        $this->logEvent($ip, 'ip_blocked', 'high', $reason);
    }

    public function unblockIp(string $ip): int
    {
        $this->ensureTables();
        $deleted = $this->db->delete('security_ip_blocks', ['ip_address' => $ip]);
        $this->logEvent($ip, 'ip_unblocked', 'info', 'Deblocage manuel depuis le panel admin');
        return $deleted;
    }

    public function logEvent(string $ip, string $type, string $severity, string $reason): void
    {
        $this->ensureTables();

        $this->db->insert('security_events', [
            'ip_address' => $ip,
            'event_type' => $type,
            'severity' => $severity,
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'request_uri' => mb_substr($_SERVER['REQUEST_URI'] ?? '', 0, 512),
            'user_agent' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'reason' => $reason,
        ]);
    }

    public function inspectRequest(string $ip, string $method, string $uri): array
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $routeKey = $this->resolveRouteKey($path);

        if ($this->isExemptPath($path)) {
            return ['allowed' => true, 'route_key' => $routeKey];
        }

        $suspiciousReason = $this->detectSuspiciousRequest($path);
        if ($suspiciousReason !== null) {
            $this->logEvent($ip, 'suspicious_path', 'high', $suspiciousReason);
            $this->blockIp($ip, $suspiciousReason, (int)($this->config['suspicious_block_seconds'] ?? 3600));
            return ['allowed' => false, 'status' => 403, 'reason' => $suspiciousReason];
        }

        $rate = $this->incrementRate($ip, $routeKey);
        if ($rate['hits'] > $rate['limit']) {
            $reason = sprintf('Trop de requetes sur %s : %d/%d en %ds', $routeKey, $rate['hits'], $rate['limit'], $rate['window']);
            $this->logEvent($ip, 'rate_limit_exceeded', 'medium', $reason);
            $this->blockIp($ip, $reason, (int)($this->config['rate_block_seconds'] ?? 900));
            return ['allowed' => false, 'status' => 429, 'reason' => $reason];
        }

        return ['allowed' => true, 'route_key' => $routeKey, 'hits' => $rate['hits'], 'limit' => $rate['limit']];
    }

    public function getDashboardData(): array
    {
        $this->ensureTables();

        $stats = [
            'active_blocks' => (int)($this->db->queryOne("
                SELECT COUNT(*) as count FROM security_ip_blocks
                WHERE permanent = 1 OR blocked_until IS NULL OR blocked_until > NOW()
            ")['count'] ?? 0),
            'events_24h' => (int)($this->db->queryOne("
                SELECT COUNT(*) as count FROM security_events
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ")['count'] ?? 0),
            'rate_24h' => (int)($this->db->queryOne("
                SELECT COUNT(*) as count FROM security_events
                WHERE event_type = 'rate_limit_exceeded'
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ")['count'] ?? 0),
            'suspicious_24h' => (int)($this->db->queryOne("
                SELECT COUNT(*) as count FROM security_events
                WHERE event_type = 'suspicious_path'
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ")['count'] ?? 0),
        ];

        return [
            'stats' => $stats,
            'blocks' => $this->db->query("
                SELECT * FROM security_ip_blocks
                WHERE permanent = 1 OR blocked_until IS NULL OR blocked_until > NOW()
                ORDER BY created_at DESC
                LIMIT 100
            "),
            'events' => $this->db->query("
                SELECT * FROM security_events
                ORDER BY created_at DESC
                LIMIT 150
            "),
        ];
    }

    private function incrementRate(string $ip, string $routeKey): array
    {
        $this->ensureTables();

        $limits = $this->config['limits'][$routeKey] ?? $this->config['limits']['global'] ?? ['max_requests' => 120, 'window' => 60];
        $limit = (int)($limits['max_requests'] ?? 120);
        $window = (int)($limits['window'] ?? 60);
        $windowStart = intdiv(time(), $window) * $window;

        $this->db->execute("
            INSERT INTO security_rate_counters (ip_address, route_key, window_start, hits)
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE hits = hits + 1
        ", [$ip, $routeKey, $windowStart]);

        $row = $this->db->queryOne("
            SELECT hits
            FROM security_rate_counters
            WHERE ip_address = ? AND route_key = ? AND window_start = ?
        ", [$ip, $routeKey, $windowStart]);

        $this->cleanupCounters();

        return [
            'hits' => (int)($row['hits'] ?? 1),
            'limit' => $limit,
            'window' => $window,
        ];
    }

    private function resolveRouteKey(string $path): string
    {
        if (str_starts_with($path, '/auth')) {
            return 'auth';
        }
        if (str_starts_with($path, '/api')) {
            return 'api';
        }
        if (str_starts_with($path, '/admin')) {
            return 'admin';
        }

        return 'global';
    }

    private function isExemptPath(string $path): bool
    {
        $exemptPrefixes = $this->config['exempt_prefixes'] ?? [];
        foreach ($exemptPrefixes as $prefix) {
            if ($prefix !== '' && str_starts_with($path, (string)$prefix)) {
                return true;
            }
        }

        return false;
    }

    private function detectSuspiciousRequest(string $path): ?string
    {
        $lowerPath = strtolower($path);
        foreach (($this->config['suspicious_paths'] ?? []) as $needle) {
            if ($needle !== '' && str_contains($lowerPath, strtolower((string)$needle))) {
                return 'Chemin suspect detecte : ' . $needle;
            }
        }

        $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
        foreach (($this->config['blocked_user_agents'] ?? []) as $needle) {
            if ($needle !== '' && str_contains($ua, strtolower((string)$needle))) {
                return 'User-Agent bloque : ' . $needle;
            }
        }

        return null;
    }

    private function cleanupCounters(): void
    {
        if (random_int(1, 100) !== 1) {
            return;
        }

        $this->db->execute("
            DELETE FROM security_rate_counters
            WHERE updated_at < DATE_SUB(NOW(), INTERVAL 2 HOUR)
        ");
    }

    private function ipMatches(string $ip, string $rule): bool
    {
        if ($ip === $rule) {
            return true;
        }

        if (!str_contains($rule, '/')) {
            return false;
        }

        [$subnet, $bits] = explode('/', $rule, 2);
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || !filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        $mask = -1 << (32 - (int)$bits);
        return (ip2long($ip) & $mask) === (ip2long($subnet) & $mask);
    }
}
