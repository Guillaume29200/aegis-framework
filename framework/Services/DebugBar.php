<?php
declare(strict_types=1);

namespace Framework\Services;

/**
 * DebugBar - Mini profiler applicatif inspire des toolbars de framework.
 *
 * Le service centralise les informations utiles en developpement :
 * requete HTTP, session, SQL, memoire, fichiers inclus, headers et config.
 * Les donnees sensibles sont masquees avant affichage.
 */
class DebugBar
{
    private array $queries = [];
    private array $logs = [];
    private array $securityChecks = [];
    private array $marks = [];
    private float $startTime;
    private bool $enabled;

    public function __construct(bool $enabled = true)
    {
        $this->enabled = $enabled;
        $this->startTime = defined('APP_START') ? (float)APP_START : microtime(true);
        $this->mark('debugbar.boot');
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function mark(string $name): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->marks[] = [
            'name' => $name,
            'time' => microtime(true) - $this->startTime,
            'memory' => memory_get_usage(true),
        ];
    }

    public function logQuery(string $query, float $executionTime, array $params = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->queries[] = $this->normalizeQuery([
            'sql' => $query,
            'time' => $executionTime,
            'params' => $params,
        ]);
    }

    public function log(string $level, string $message, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->logs[] = [
            'level' => strtoupper($level),
            'message' => $message,
            'context' => $this->redact($context),
            'time' => microtime(true) - $this->startTime,
        ];
    }

    public function securityCheck(string $check, bool $passed, ?string $message = null): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->securityChecks[] = [
            'check' => $check,
            'passed' => $passed,
            'message' => $message,
        ];
    }

    public function importQueries(array $queries): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->queries = [];
        foreach ($queries as $query) {
            $this->queries[] = $this->normalizeQuery($query);
        }
    }

    public function render(): string
    {
        if (!$this->enabled) {
            return '';
        }

        $this->mark('debugbar.render');
        $debugData = $this->collectData();

        ob_start();
        include __DIR__ . '/../Views/debug-bar.php';
        return (string)ob_get_clean();
    }

    public function getStats(): array
    {
        $data = $this->collectData();

        return [
            'load_time' => $data['summary']['load_time'],
            'memory_usage' => $data['summary']['memory_usage_bytes'],
            'memory_peak' => $data['summary']['memory_peak_bytes'],
            'queries_count' => $data['queries']['count'],
            'slow_queries' => $data['queries']['slow_count'],
            'logs_count' => count($this->logs),
            'files_count' => count($data['files']),
        ];
    }

    private function collectData(): array
    {
        $loadTime = microtime(true) - $this->startTime;
        $includedFiles = get_included_files();
        $headers = function_exists('headers_list') ? headers_list() : [];
        $responseCode = http_response_code();
        $queryTimes = array_column($this->queries, 'time');
        $slowQueries = array_values(array_filter($this->queries, static fn(array $q): bool => !empty($q['slow'])));

        $checks = $this->securityChecks;
        $checks[] = [
            'check' => 'Session active',
            'passed' => session_status() === PHP_SESSION_ACTIVE,
            'message' => session_status() === PHP_SESSION_ACTIVE ? 'PHP session is active' : 'No active session',
        ];
        $checks[] = [
            'check' => 'HTTPS',
            'passed' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? null) === '443',
            'message' => 'Local HTTP can be normal on WAMP',
        ];
        $checks[] = [
            'check' => 'Debug mode',
            'passed' => $this->enabled,
            'message' => 'Debug toolbar is enabled',
        ];

        return [
            'summary' => [
                'load_time' => $loadTime,
                'memory_usage_bytes' => memory_get_usage(true),
                'memory_peak_bytes' => memory_get_peak_usage(true),
                'memory_limit' => ini_get('memory_limit'),
                'php_version' => PHP_VERSION,
                'sapi' => PHP_SAPI,
                'response_code' => $responseCode,
                'included_files_count' => count($includedFiles),
            ],
            'request' => [
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
                'uri' => $_SERVER['REQUEST_URI'] ?? '',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'get' => $this->redact($_GET),
                'post' => $this->redact($_POST),
                'cookies' => $this->redact($_COOKIE),
                'server' => $this->redact($this->serverSnapshot()),
                'headers' => $headers,
            ],
            'session' => [
                'id' => session_status() === PHP_SESSION_ACTIVE ? session_id() : null,
                'name' => session_name(),
                'data' => $this->redact($_SESSION ?? []),
            ],
            'queries' => [
                'count' => count($this->queries),
                'slow_count' => count($slowQueries),
                'total_time' => array_sum($queryTimes),
                'slow' => $slowQueries,
                'items' => $this->queries,
            ],
            'logs' => $this->logs,
            'security' => $checks,
            'files' => $this->formatFiles($includedFiles),
            'marks' => $this->marks,
            'config' => [
                'environment' => getenv('APP_ENV') ?: 'development',
                'base_url' => defined('BASE_URL') ? BASE_URL : '',
                'turbonav' => defined('TURBONAV_ENABLED') ? TURBONAV_ENABLED : null,
                'root_path' => defined('ROOT_PATH') ? ROOT_PATH : '',
                'timezone' => date_default_timezone_get(),
                'opcache' => function_exists('opcache_get_status') ? (bool)@opcache_get_status(false) : false,
            ],
        ];
    }

    private function normalizeQuery(array $query): array
    {
        $sql = (string)($query['sql'] ?? $query['query'] ?? 'N/A');
        $time = (float)($query['time'] ?? 0);

        return [
            'sql' => $sql,
            'time' => $time,
            'params' => $this->redact($query['params'] ?? []),
            'slow' => (bool)($query['slow'] ?? $time > 0.1),
        ];
    }

    private function serverSnapshot(): array
    {
        $keys = [
            'REQUEST_METHOD',
            'REQUEST_URI',
            'SCRIPT_NAME',
            'SERVER_NAME',
            'SERVER_PORT',
            'REMOTE_ADDR',
            'HTTP_HOST',
            'HTTP_REFERER',
            'HTTP_USER_AGENT',
            'CONTENT_TYPE',
            'CONTENT_LENGTH',
        ];

        $snapshot = [];
        foreach ($keys as $key) {
            if (isset($_SERVER[$key])) {
                $snapshot[$key] = $_SERVER[$key];
            }
        }

        return $snapshot;
    }

    private function formatFiles(array $files): array
    {
        $root = defined('ROOT_PATH') ? str_replace('\\', '/', ROOT_PATH) : '';
        $items = [];

        foreach ($files as $file) {
            $normalized = str_replace('\\', '/', $file);
            $relative = $root !== '' ? str_replace($root . '/', '', $normalized) : $normalized;
            $items[] = [
                'path' => $relative,
                'size' => is_file($file) ? filesize($file) : 0,
            ];
        }

        return $items;
    }

    private function redact(mixed $value): mixed
    {
        if (is_array($value)) {
            $clean = [];
            foreach ($value as $key => $item) {
                $keyString = strtolower((string)$key);
                if ($this->isSensitiveKey($keyString)) {
                    $clean[$key] = '[redacted]';
                    continue;
                }
                $clean[$key] = $this->redact($item);
            }
            return $clean;
        }

        if (is_object($value)) {
            return '[object ' . $value::class . ']';
        }

        return $value;
    }

    private function isSensitiveKey(string $key): bool
    {
        foreach (['password', 'passwd', 'token', 'secret', 'key', 'cookie', 'authorization', 'csrf'] as $needle) {
            if (str_contains($key, $needle)) {
                return true;
            }
        }

        return false;
    }
}
