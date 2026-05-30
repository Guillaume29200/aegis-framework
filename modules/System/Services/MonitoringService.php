<?php
declare(strict_types=1);

namespace System\Services;

/**
 * Service de monitoring général pour eSport-CMS V4.
 * Centralise les informations utiles pour superviser un hébergement web :
 * système/PHP, base de données, stockage, sécurité, logs et modules.
 */
class MonitoringService
{
    public static function getAllInfo($db = null): array
    {
        $runtime = self::getRuntimeInfo();
        $requirements = self::checkRequirements();
        $database = $db ? self::getDatabaseInfo($db) : null;
        $files = self::getFilesInfo();
        $performance = self::getPerformanceInfo();
        $security = self::getSecurityInfo();
        $logs = self::getLogsInfo($db);
        $modules = self::getModulesInfo();
        $health = self::buildHealthSummary($requirements, $database, $files, $performance, $security, $logs);

        return [
            'server_type' => self::getServerType(),
            'os' => self::detectOS(),
            'hardware' => self::getHardwareInfo(),
            'php_version' => PHP_VERSION,
            'cms_version' => self::getCMSVersion(),
            'requirements' => $requirements,
            'errors' => $health['critical'],
            'warnings' => $health['warnings'],
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
            'server_addr' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
            'runtime' => $runtime,
            'database' => $database,
            'files' => $files,
            'performance' => $performance,
            'security' => $security,
            'logs' => $logs,
            'modules' => $modules,
            'health' => $health,
        ];
    }

    public static function getServerType(): string
    {
        $serverString = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
        foreach (['apache' => 'Apache', 'nginx' => 'Nginx', 'litespeed' => 'LiteSpeed', 'lighttpd' => 'Lighttpd'] as $needle => $name) {
            if (stripos($serverString, $needle) !== false) {
                return $name;
            }
        }
        return $serverString !== '' ? $serverString : 'Autre';
    }

    public static function detectOS(): string
    {
        $name = php_uname('s');
        $release = php_uname('r');
        $version = php_uname('v');

        if (stripos($name, 'Windows') !== false) {
            return 'Windows ' . $version;
        }
        if (stripos($name, 'Linux') !== false) {
            $pretty = self::readOsReleasePrettyName();
            return $pretty ?: ('Linux ' . $release);
        }
        if (stripos($name, 'Darwin') !== false) {
            return 'macOS';
        }
        return trim($name . ' ' . $release);
    }

    public static function getHardwareInfo(): array
    {
        $root = self::getAppRoot();
        $diskTotal = function_exists('disk_total_space') ? @disk_total_space($root) : false;
        $diskFree = function_exists('disk_free_space') ? @disk_free_space($root) : false;

        return [
            'hostname' => php_uname('n'),
            'architecture' => php_uname('m'),
            'cpu' => php_uname('m') . ' (' . php_uname('n') . ')',
            'ram' => ini_get('memory_limit') ? 'Limite PHP: ' . ini_get('memory_limit') : 'Non disponible',
            'disk' => $diskTotal !== false ? self::formatBytes((int)$diskTotal) : 'Non disponible',
            'disk_free' => $diskFree !== false ? self::formatBytes((int)$diskFree) : 'Non disponible',
        ];
    }

    public static function checkRequirements(): array
    {
        $requiredExtensions = ['mbstring', 'pdo_mysql', 'curl', 'gd', 'fileinfo', 'openssl', 'zip', 'json', 'intl'];
        $extensions = [];
        foreach ($requiredExtensions as $extension) {
            $extensions[$extension] = extension_loaded($extension);
        }

        return [
            'file_uploads' => filter_var(ini_get('file_uploads'), FILTER_VALIDATE_BOOLEAN),
            'upload_max_filesize' => ini_get('upload_max_filesize') ?: '0M',
            'post_max_size' => ini_get('post_max_size') ?: '0M',
            'max_execution_time' => ini_get('max_execution_time'),
            'max_input_vars' => ini_get('max_input_vars'),
            'memory_limit' => ini_get('memory_limit') ?: '0M',
            'allow_url_fopen' => filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN),
            'display_errors' => filter_var(ini_get('display_errors'), FILTER_VALIDATE_BOOLEAN),
            'log_errors' => filter_var(ini_get('log_errors'), FILTER_VALIDATE_BOOLEAN),
            'extensions' => $extensions,
            'ext_mbstring' => $extensions['mbstring'],
            'ext_pdo_mysql' => $extensions['pdo_mysql'],
            'ext_curl' => $extensions['curl'],
            'ext_gd' => $extensions['gd'],
            'ext_fileinfo' => $extensions['fileinfo'],
            'ext_openssl' => $extensions['openssl'],
            'ext_zip' => $extensions['zip'],
            'ext_json' => $extensions['json'],
            'func_exec' => function_exists('exec'),
            'func_shell_exec' => function_exists('shell_exec'),
            'mod_rewrite' => self::checkModRewrite(),
        ];
    }

    public static function checkModRewrite(): bool
    {
        if (function_exists('apache_get_modules')) {
            return in_array('mod_rewrite', apache_get_modules(), true);
        }
        $htaccessPath = self::getAppRoot() . '/.htaccess';
        return is_file($htaccessPath) && strpos((string)@file_get_contents($htaccessPath), 'RewriteEngine') !== false;
    }

    public static function listDirectory(string $path): array
    {
        if (!is_dir($path)) {
            return ['directories' => [], 'files' => []];
        }

        $directories = [];
        $files = [];
        foreach ((array)@scandir($path) as $item) {
            if ($item === '.' || $item === '..' || $item === '') {
                continue;
            }
            $fullPath = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . $item;
            if (is_dir($fullPath)) {
                $directories[] = $item;
            } elseif (is_file($fullPath)) {
                $files[] = $item;
            }
        }

        sort($directories);
        sort($files);
        return ['directories' => $directories, 'files' => $files];
    }

    public static function getCMSVersion(): string
    {
        $jsonPath = __DIR__ . '/../Version/current_version_cms.json';
        if (is_file($jsonPath)) {
            $data = json_decode((string)file_get_contents($jsonPath), true);
            if (!empty($data['version'])) {
                return 'v' . $data['version'];
            }
        }
        return 'v4.0.0';
    }

    public static function getRuntimeInfo(): array
    {
        return [
            'app_root' => self::getAppRoot(),
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? '',
            'script_name' => $_SERVER['SCRIPT_NAME'] ?? '',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? '',
            'php_sapi' => PHP_SAPI,
            'loaded_ini' => php_ini_loaded_file() ?: 'Non disponible',
            'timezone' => date_default_timezone_get(),
            'server_time' => date('d/m/Y H:i:s'),
            'session_name' => session_name(),
            'session_save_path' => session_save_path() ?: sys_get_temp_dir(),
            'temp_dir' => sys_get_temp_dir(),
        ];
    }

    public static function getDatabaseInfo($db): array
    {
        try {
            $pdo = self::resolvePdo($db);
            if (!$pdo) {
                return ['error' => 'Connexion PDO introuvable.'];
            }

            $dbName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();

            $stmtSize = $pdo->prepare("SELECT COALESCE(ROUND(SUM(data_length + index_length) / 1024 / 1024, 2), 0) FROM information_schema.TABLES WHERE table_schema = ?");
            $stmtSize->execute([$dbName]);
            $dbSize = (float)$stmtSize->fetchColumn();

            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE table_schema = ?");
            $stmtCount->execute([$dbName]);
            $tableCount = (int)$stmtCount->fetchColumn();

            $mysqlVersion = (string)$pdo->query('SELECT VERSION()')->fetchColumn();
            $maxConnections = (string)$pdo->query("SHOW VARIABLES LIKE 'max_connections'")->fetch(\PDO::FETCH_ASSOC)['Value'] ?? 'N/A';
            $threadsConnected = (string)$pdo->query("SHOW STATUS LIKE 'Threads_connected'")->fetch(\PDO::FETCH_ASSOC)['Value'] ?? 'N/A';
            $uptime = (int)($pdo->query("SHOW STATUS LIKE 'Uptime'")->fetch(\PDO::FETCH_ASSOC)['Value'] ?? 0);

            $stmtTables = $pdo->prepare("SELECT TABLE_NAME AS table_name, COALESCE(TABLE_ROWS, 0) AS rows_count, ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS size_mb FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC LIMIT 12");
            $stmtTables->execute([$dbName]);
            $tables = $stmtTables->fetchAll(\PDO::FETCH_ASSOC);
            $totalRows = 0;
            foreach ($tables as $table) {
                $totalRows += (int)($table['rows_count'] ?? 0);
            }

            return [
                'db_name' => $dbName,
                'db_size' => number_format($dbSize, 2, ',', ' ') . ' Mo',
                'db_size_mb' => $dbSize,
                'table_count' => $tableCount,
                'total_rows' => number_format($totalRows, 0, ',', ' '),
                'mysql_version' => $mysqlVersion,
                'max_connections' => $maxConnections,
                'threads_connected' => $threadsConnected,
                'uptime' => self::formatDuration($uptime),
                'tables' => $tables,
            ];
        } catch (\Throwable $e) {
            return ['error' => 'Erreur BDD : ' . $e->getMessage()];
        }
    }

    public static function getFilesInfo(): array
    {
        $rootPath = self::getAppRoot();
        $logsPath = __DIR__ . '/../logs';
        $uploadsPath = $rootPath . '/framework/uploads';
        $modulesPath = $rootPath . '/modules';

        $diskFree = function_exists('disk_free_space') ? @disk_free_space($rootPath) : false;
        $diskTotal = function_exists('disk_total_space') ? @disk_total_space($rootPath) : false;
        $diskUsedPercent = ($diskFree !== false && $diskTotal !== false && $diskTotal > 0)
            ? round((($diskTotal - $diskFree) / $diskTotal) * 100, 2)
            : 0.0;

        $directories = [
            'Application' => $rootPath,
            'Modules' => $modulesPath,
            'Uploads framework' => $uploadsPath,
            'Logs framework' => $logsPath,
        ];

        $breakdown = [];
        foreach ($directories as $label => $path) {
            $size = is_dir($path) ? self::getDirSize($path, 25000) : 0;
            $breakdown[] = ['label' => $label, 'path' => $path, 'size' => self::formatBytes($size), 'size_bytes' => $size];
        }

        return [
            'root_path' => $rootPath,
            'total_size' => self::formatBytes(self::getDirSize($rootPath, 50000)),
            'file_count' => number_format(self::countFiles($rootPath, 50000), 0, ',', ' '),
            'logs_size' => self::formatBytes(is_dir($logsPath) ? self::getDirSize($logsPath, 10000) : 0),
            'uploads_size' => self::formatBytes(is_dir($uploadsPath) ? self::getDirSize($uploadsPath, 10000) : 0),
            'disk_free' => $diskFree !== false ? self::formatBytes((int)$diskFree) : 'N/A',
            'disk_total' => $diskTotal !== false ? self::formatBytes((int)$diskTotal) : 'N/A',
            'disk_used_percent' => $diskUsedPercent,
            'breakdown' => $breakdown,
            'writable' => [
                'framework/logs' => is_writable($logsPath),
                'framework/uploads' => is_dir($uploadsPath) && is_writable($uploadsPath),
                'temp_dir' => is_writable(sys_get_temp_dir()),
            ],
        ];
    }

    public static function getPerformanceInfo(): array
    {
        $info = [
            'memory_current' => self::formatBytes(memory_get_usage(true)),
            'memory_peak' => self::formatBytes(memory_get_peak_usage(true)),
            'memory_limit' => ini_get('memory_limit'),
            'realpath_cache_size' => function_exists('realpath_cache_size') ? self::formatBytes(realpath_cache_size()) : 'N/A',
            'realpath_cache_ttl' => ini_get('realpath_cache_ttl'),
            'opcache_enabled' => false,
        ];

        if (function_exists('opcache_get_status')) {
            $opcache = @opcache_get_status(false);
            $info['opcache_enabled'] = is_array($opcache) && !empty($opcache['opcache_enabled']);
            if ($info['opcache_enabled']) {
                $memory = $opcache['memory_usage'] ?? [];
                $stats = $opcache['opcache_statistics'] ?? [];
                $info['opcache_hit_rate'] = isset($stats['opcache_hit_rate']) ? round((float)$stats['opcache_hit_rate'], 2) . '%' : 'N/A';
                $info['opcache_cached_scripts'] = $stats['num_cached_scripts'] ?? 'N/A';
                $info['opcache_memory_used'] = self::formatBytes(max(0, (int)($memory['used_memory'] ?? 0)));
                $info['opcache_memory_free'] = self::formatBytes(max(0, (int)($memory['free_memory'] ?? 0)));
                $info['opcache_restarts'] = (int)($stats['oom_restarts'] ?? 0) + (int)($stats['hash_restarts'] ?? 0) + (int)($stats['manual_restarts'] ?? 0);
            }
        }

        return $info;
    }

    public static function getSecurityInfo(): array
    {
        $headers = self::getSentHeadersMap();
        $dangerousFunctions = ['exec', 'shell_exec', 'system', 'passthru', 'proc_open', 'popen'];
        $disabledFunctions = array_filter(array_map('trim', explode(',', (string)ini_get('disable_functions'))));
        $enabledDangerous = array_values(array_diff($dangerousFunctions, $disabledFunctions));
        $appRoot = self::getAppRoot();

        $criticalFiles = [
            'index.php',
            '.htaccess',
            'framework/bootstrap.php',
            'framework/config/database.php',
            'framework/config/security.php',
            '.env',
        ];
        $permissions = [];
        foreach ($criticalFiles as $file) {
            $fullPath = $appRoot . '/' . $file;
            if (is_file($fullPath)) {
                $permissions[$file] = [
                    'perms'    => substr(sprintf('%o', (int)fileperms($fullPath)), -4),
                    'writable' => is_writable($fullPath),
                ];
            }
        }

        return [
            'https_enabled' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'security_headers' => [
                'Content-Security-Policy' => isset($headers['content-security-policy']),
                'X-Frame-Options' => isset($headers['x-frame-options']),
                'X-Content-Type-Options' => isset($headers['x-content-type-options']),
                'Referrer-Policy' => isset($headers['referrer-policy']),
                'Permissions-Policy' => isset($headers['permissions-policy']),
            ],
            'file_permissions' => $permissions,
            'display_errors' => filter_var(ini_get('display_errors'), FILTER_VALIDATE_BOOLEAN),
            'expose_php' => filter_var(ini_get('expose_php'), FILTER_VALIDATE_BOOLEAN),
            'session_cookie_secure' => filter_var(ini_get('session.cookie_secure'), FILTER_VALIDATE_BOOLEAN),
            'session_cookie_httponly' => filter_var(ini_get('session.cookie_httponly'), FILTER_VALIDATE_BOOLEAN),
            'session_cookie_samesite' => ini_get('session.cookie_samesite') ?: 'Non defini',
            'enabled_dangerous_functions' => $enabledDangerous,
            'disabled_dangerous_functions' => array_values(array_intersect($dangerousFunctions, $disabledFunctions)),
        ];
    }

    public static function getLogsInfo($db = null): array
    {
        $logsPath = __DIR__ . '/../logs';
        $logs = [];
        $totalSize = 0;
        $criticalCount = 0;

        if (is_dir($logsPath)) {
            foreach ((array)@scandir($logsPath) as $file) {
                $fullPath = $logsPath . '/' . $file;
                if ($file === '.' || $file === '..' || !is_file($fullPath)) {
                    continue;
                }
                $size = (int)@filesize($fullPath);
                $totalSize += $size;
                if (stripos($file, 'critical') !== false || stripos($file, 'error') !== false) {
                    $criticalCount++;
                }
                $logs[] = [
                    'name' => $file,
                    'size' => self::formatBytes($size),
                    'size_bytes' => $size,
                    'modified' => date('d/m/Y H:i:s', (int)filemtime($fullPath)),
                ];
            }
        }

        usort($logs, static fn(array $a, array $b): int => ($b['size_bytes'] <=> $a['size_bytes']));

        $lastErrors = [];
        $errorLog = ini_get('error_log');
        if ($errorLog && is_file($errorLog)) {
            $lines = @file($errorLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (is_array($lines)) {
                $lastErrors = array_slice(array_reverse($lines), 0, 8);
            }
        }

        return [
            'log_files' => $logs,
            'log_count' => count($logs),
            'total_size' => self::formatBytes($totalSize),
            'critical_files' => $criticalCount,
            'last_errors' => $lastErrors,
            'logs_path' => realpath($logsPath) ?: $logsPath,
            'php_error_log' => $errorLog ?: 'Non defini',
            'database' => self::getDatabaseLogsInfo($db),
        ];
    }

    public static function getDatabaseLogsInfo($db = null): array
    {
        $empty = [
            'available' => false,
            'total' => 0,
            'by_level' => [],
            'recent' => [],
            'error' => null,
        ];

        $pdo = self::resolvePdo($db);
        if (!$pdo) {
            $empty['error'] = 'Base de donnees non disponible';
            return $empty;
        }

        try {
            if (!in_array('logs', self::getExistingTables($pdo, ['logs']), true)) {
                $empty['error'] = 'Table logs introuvable';
                return $empty;
            }

            $total = (int)($pdo->query('SELECT COUNT(*) FROM logs')->fetchColumn() ?: 0);
            $byLevel = self::queryAllSafe($pdo, "SELECT level, COUNT(*) total FROM logs GROUP BY level ORDER BY total DESC");
            $recent = self::queryAllSafe($pdo, "
                SELECT id, level, message, context, ip_address, user_id, url, method, created_at
                FROM logs
                ORDER BY id DESC
                LIMIT 100
            ");

            foreach ($recent as &$row) {
                $row['message'] = self::truncateText((string)($row['message'] ?? ''), 180);
                $row['context'] = self::truncateText((string)($row['context'] ?? ''), 220);
            }
            unset($row);

            return [
                'available' => true,
                'total' => $total,
                'by_level' => $byLevel,
                'recent' => $recent,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            $empty['error'] = $e->getMessage();
            return $empty;
        }
    }

    public static function deleteDatabaseLog($db, int $logId): array
    {
        $pdo = self::resolvePdo($db);
        if (!$pdo) {
            return ['success' => false, 'message' => 'Connexion PDO introuvable.'];
        }
        if ($logId <= 0) {
            return ['success' => false, 'message' => 'Log invalide.'];
        }

        try {
            if (!in_array('logs', self::getExistingTables($pdo, ['logs']), true)) {
                return ['success' => false, 'message' => 'Table logs introuvable.'];
            }
            $stmt = $pdo->prepare('DELETE FROM logs WHERE id = ?');
            $stmt->execute([$logId]);
            if ($stmt->rowCount() < 1) {
                return ['success' => false, 'message' => 'Log introuvable ou deja supprime.'];
            }
            return ['success' => true, 'message' => 'Log SQL supprime avec succes.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Suppression impossible : ' . $e->getMessage()];
        }
    }

    public static function purgeDatabaseLogs($db): array
    {
        $pdo = self::resolvePdo($db);
        if (!$pdo) {
            return ['success' => false, 'message' => 'Connexion PDO introuvable.'];
        }

        try {
            if (!in_array('logs', self::getExistingTables($pdo, ['logs']), true)) {
                return ['success' => false, 'message' => 'Table logs introuvable.'];
            }
            $count = (int)($pdo->query('SELECT COUNT(*) FROM logs')->fetchColumn() ?: 0);
            $pdo->exec('DELETE FROM logs');
            return ['success' => true, 'message' => $count . ' logs SQL supprimes avec succes.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Purge impossible : ' . $e->getMessage()];
        }
    }

    private static function truncateText(string $value, int $limit): string
    {
        if (strlen($value) <= $limit) {
            return $value;
        }
        return substr($value, 0, max(0, $limit - 3)) . '...';
    }
    public static function getModulesInfo(): array
    {
        $modulesPath = self::getAppRoot() . '/modules';
        $modules = [];
        if (is_dir($modulesPath)) {
            foreach ((array)@scandir($modulesPath) as $dir) {
                if ($dir === '.' || $dir === '..' || !is_dir($modulesPath . '/' . $dir)) {
                    continue;
                }
                $moduleJsonPath = $modulesPath . '/' . $dir . '/module.json';
                $moduleData = is_file($moduleJsonPath) ? json_decode((string)file_get_contents($moduleJsonPath), true) : [];
                $modules[] = [
                    'name' => $dir,
                    'version' => $moduleData['version'] ?? 'N/A',
                    'enabled' => $moduleData['enabled'] ?? true,
                    'path' => $modulesPath . '/' . $dir,
                ];
            }
        }

        return ['total_modules' => count($modules), 'modules' => $modules, 'modules_path' => $modulesPath];
    }

    private static function buildHealthSummary(array $requirements, ?array $database, array $files, array $performance, array $security, array $logs): array
    {
        $critical = [];
        $warnings = [];
        $ok = [];
        $score = 100;

        foreach ($requirements['extensions'] as $extension => $loaded) {
            if (!$loaded && in_array($extension, ['mbstring', 'pdo_mysql', 'curl', 'openssl', 'fileinfo'], true)) {
                $critical[] = 'Extension PHP manquante : ' . $extension;
                $score -= 10;
            }
        }

        if (self::toBytes($requirements['upload_max_filesize']) < 128 * 1024 * 1024) {
            $warnings[] = 'upload_max_filesize actif (' . $requirements['upload_max_filesize'] . ') inferieur a 128 Mo';
            $score -= 5;
        }
        if (self::toBytes($requirements['memory_limit']) > 0 && self::toBytes($requirements['memory_limit']) < 256 * 1024 * 1024) {
            $warnings[] = 'memory_limit inferieur a 256 Mo';
            $score -= 5;
        }
        if (!empty($database['error'])) {
            $critical[] = $database['error'];
            $score -= 15;
        }
        if (($files['disk_used_percent'] ?? 0) >= 90) {
            $critical[] = 'Disque utilise a plus de 90%';
            $score -= 15;
        } elseif (($files['disk_used_percent'] ?? 0) >= 80) {
            $warnings[] = 'Disque utilise a plus de 80%';
            $score -= 8;
        }
        if (empty($performance['opcache_enabled'])) {
            $warnings[] = 'OPcache desactive';
            $score -= 5;
        }
        if (!empty($security['display_errors'])) {
            $warnings[] = 'display_errors actif';
            $score -= 6;
        }
        if (!empty($security['expose_php'])) {
            $warnings[] = 'expose_php actif';
            $score -= 3;
        }
        foreach ($security['security_headers'] as $header => $present) {
            if (!$present) {
                $warnings[] = 'Header securite absent : ' . $header;
                $score -= 2;
            }
        }
        if (!$critical && !$warnings) {
            $ok[] = 'Aucune anomalie majeure detectee';
        }

        $score = max(0, min(100, $score));
        $level = $score >= 85 ? 'good' : ($score >= 65 ? 'warning' : 'danger');

        return [
            'score' => $score,
            'level' => $level,
            'critical' => $critical,
            'warnings' => $warnings,
            'ok' => $ok,
            'generated_at' => date('d/m/Y H:i:s'),
        ];
    }


    private static function resolvePdo($db): ?\PDO
    {
        if ($db instanceof \PDO) {
            return $db;
        }
        foreach (['getPDO', 'getPdo', 'getConnection', 'getDb'] as $method) {
            if (is_object($db) && method_exists($db, $method)) {
                $pdo = $db->{$method}();
                return $pdo instanceof \PDO ? $pdo : null;
            }
        }
        return null;
    }

    private static function getExistingTables(\PDO $pdo, array $tables): array
    {
        if ($tables === []) {
            return [];
        }

        try {
            $dbName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
            $placeholders = implode(',', array_fill(0, count($tables), '?'));
            $stmt = $pdo->prepare(
                "SELECT TABLE_NAME
                 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = ?
                   AND TABLE_NAME IN ({$placeholders})"
            );
            $stmt->execute(array_merge([$dbName], $tables));
            $existing = $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
            return array_values(array_intersect($tables, $existing));
        } catch (\Throwable) {
            return [];
        }
    }

    private static function getTablesWithColumn(\PDO $pdo, string $column): array
    {
        try {
            $dbName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
            $stmt = $pdo->prepare(
                "SELECT c.TABLE_NAME
                 FROM information_schema.COLUMNS c
                 INNER JOIN information_schema.TABLES t
                    ON t.TABLE_SCHEMA = c.TABLE_SCHEMA
                   AND t.TABLE_NAME = c.TABLE_NAME
                 WHERE c.TABLE_SCHEMA = ?
                   AND c.COLUMN_NAME = ?
                   AND c.TABLE_NAME LIKE 'gsh\\_%'
                   AND t.TABLE_TYPE = 'BASE TABLE'
                 ORDER BY c.TABLE_NAME"
            );
            $stmt->execute([$dbName, $column]);
            return $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    private static function fetchColumnSafe(\PDO $pdo, string $sql, array $params = []): array
    {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    private static function executeSafe(\PDO $pdo, string $sql, array $params = []): int
    {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
    private static function queryOneSafe(\PDO $pdo, string $sql, array $params = []): array
    {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    private static function queryAllSafe(\PDO $pdo, string $sql, array $params = []): array
    {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    private static function getSentHeadersMap(): array
    {
        $headers = [];
        foreach (headers_list() as $header) {
            $parts = explode(':', $header, 2);
            if (count($parts) === 2) {
                $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
        }
        return $headers;
    }

    private static function readOsReleasePrettyName(): ?string
    {
        $path = '/etc/os-release';
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }
        foreach ((array)@file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with($line, 'PRETTY_NAME=')) {
                return trim(substr($line, 12), '"\'');
            }
        }
        return null;
    }

    private static function getAppRoot(): string
    {
        if (defined('ROOT_PATH')) {
            return ROOT_PATH;
        }
        return realpath(__DIR__ . '/../../..') ?: dirname(__DIR__, 3);
    }

    private static function getDirSize(string $path, int $limit = 30000): int
    {
        if (!is_dir($path)) {
            return 0;
        }
        $size = 0;
        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            if (++$count > $limit) {
                break;
            }
            if ($item->isFile()) {
                $size += (int)$item->getSize();
            }
        }
        return $size;
    }

    private static function countFiles(string $path, int $limit = 30000): int
    {
        if (!is_dir($path)) {
            return 0;
        }
        $count = 0;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $count++;
                if ($count >= $limit) {
                    break;
                }
            }
        }
        return $count;
    }

    private static function formatBytes(int|float $bytes): string
    {
        $bytes = max(0, (float)$bytes);
        $units = ['o', 'Ko', 'Mo', 'Go', 'To'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return number_format($bytes, $i === 0 ? 0 : 2, ',', ' ') . ' ' . $units[$i];
    }

    private static function toBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return $value === '-1' ? -1 : 0;
        }
        $unit = strtolower(substr($value, -1));
        $number = (float)$value;
        return match ($unit) {
            'g' => (int)($number * 1024 * 1024 * 1024),
            'm' => (int)($number * 1024 * 1024),
            'k' => (int)($number * 1024),
            default => (int)$number,
        };
    }

    private static function formatDuration(int $seconds): string
    {
        if ($seconds <= 0) {
            return 'N/A';
        }
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        if ($days > 0) {
            return $days . 'j ' . $hours . 'h';
        }
        return $hours . 'h ' . $minutes . 'm';
    }
}
