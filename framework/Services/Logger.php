<?php
declare(strict_types=1);

namespace Framework\Services;

use Framework\Services\Database;

/**
 * Central application logger.
 * Stores regular logs in the SQL logs table and mirrors important events to files.
 */
class Logger
{
    private Database $db;
    private string $logPath;
    private array $config;

    public const DEBUG = 'DEBUG';
    public const INFO = 'INFO';
    public const WARNING = 'WARNING';
    public const ERROR = 'ERROR';
    public const CRITICAL = 'CRITICAL';
    public const SECURITY = 'SECURITY';

    /** Taille max d'un fichier de log avant rotation (octets) */
    private int $maxFileSize;
    /** Nombre d'archives conservees par fichier de log rotate */
    private int $maxRotatedFiles;
    /** Duree de retention des fichiers de logs (jours) */
    private int $retentionDays;

    public function __construct(Database $db, array $config)
    {
        $this->db = $db;
        $this->config = $config;
        $this->logPath = __DIR__ . '/../logs/';

        // Parametres de rotation (surchargables via la config d'environnement)
        $this->maxFileSize     = (int)($config['log_max_size'] ?? 10 * 1024 * 1024); // 10 Mo
        $this->maxRotatedFiles = (int)($config['log_max_files'] ?? 5);
        $this->retentionDays   = (int)($config['log_retention_days'] ?? 30);

        if (!is_dir($this->logPath)) {
            @mkdir($this->logPath, 0755, true);
        }
    }

    public function log(string $level, string $message, array $context = []): void
    {
        if ($this->isNoisyFrameworkLog($message)) {
            return;
        }

        if (!$this->shouldLog($level)) {
            return;
        }

        $logData = [
            'level' => $level,
            'message' => $message,
            'context' => json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'ip_address' => $this->clientIp(),
            'user_id' => $_SESSION['user_id'] ?? null,
            'url' => $_SERVER['REQUEST_URI'] ?? null,
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
        ];

        try {
            $this->db->insert('logs', $logData);
        } catch (\Throwable) {
            $this->logToFile($level, $message, $context);
        }

        if (in_array($level, [self::ERROR, self::CRITICAL, self::SECURITY], true)) {
            $this->logToFile($level, $message, $context);
        }
    }

    private function isNoisyFrameworkLog(string $message): bool
    {
        return preg_match('/^Loaded \d+ modules$/', $message) === 1
            || str_starts_with($message, 'Module loaded: ');
    }

    private function shouldLog(string $level): bool
    {
        $levels = [
            self::DEBUG => 0,
            self::INFO => 1,
            self::WARNING => 2,
            self::ERROR => 3,
            self::CRITICAL => 4,
            self::SECURITY => 5,
        ];

        $configLevel = $this->config['log_level'] ?? self::INFO;
        $currentLevelValue = $levels[$level] ?? 1;
        $configLevelValue = $levels[$configLevel] ?? 1;

        return $currentLevelValue >= $configLevelValue;
    }

    private function logToFile(string $level, string $message, array $context = []): void
    {
        if (!is_writable($this->logPath)) {
            return;
        }

        $filename = $this->logPath . strtolower($level) . '_' . date('Y-m-d') . '.log';

        // Rotation par taille : si le fichier du jour depasse la limite,
        // on le renomme en .1, .2 ... (les plus anciens etant ecrases).
        $this->rotateIfNeeded($filename);

        $logLine = sprintf(
            "[%s] [%s] %s %s\n",
            date('Y-m-d H:i:s'),
            $level,
            $message,
            $context ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : ''
        );

        @file_put_contents($filename, $logLine, FILE_APPEND | LOCK_EX);

        // Purge des vieux fichiers, de maniere occasionnelle pour ne pas
        // scanner le dossier a chaque ecriture (~1 chance sur 50).
        if (random_int(1, 50) === 1) {
            $this->pruneOldLogs();
        }
    }

    /**
     * Rotation d'un fichier de log s'il depasse la taille maximale.
     * file.log -> file.log.1 -> file.log.2 ... jusqu'a maxRotatedFiles.
     */
    private function rotateIfNeeded(string $filename): void
    {
        if (!is_file($filename) || @filesize($filename) < $this->maxFileSize) {
            return;
        }

        // Supprimer l'archive la plus ancienne si elle existe
        $oldest = $filename . '.' . $this->maxRotatedFiles;
        if (is_file($oldest)) {
            @unlink($oldest);
        }

        // Decaler les archives : .N-1 -> .N
        for ($i = $this->maxRotatedFiles - 1; $i >= 1; $i--) {
            $src = $filename . '.' . $i;
            if (is_file($src)) {
                @rename($src, $filename . '.' . ($i + 1));
            }
        }

        // Fichier courant -> .1
        @rename($filename, $filename . '.1');
    }

    /**
     * Supprime les fichiers de logs plus vieux que la duree de retention.
     */
    private function pruneOldLogs(): void
    {
        $threshold = time() - ($this->retentionDays * 86400);
        $files = @glob($this->logPath . '*.log*') ?: [];

        foreach ($files as $file) {
            if (is_file($file) && @filemtime($file) < $threshold) {
                @unlink($file);
            }
        }
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log(self::DEBUG, $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log(self::INFO, $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log(self::WARNING, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log(self::ERROR, $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    public function security(string $message, array $context = []): void
    {
        $this->log(self::SECURITY, $message, $context);
    }

    public function getRecentLogs(int $limit = 100, ?string $level = null): array
    {
        $limit = max(1, min(500, $limit));
        $params = [];
        $where = '';

        if ($level !== null && $level !== '') {
            $where = 'WHERE level = ?';
            $params[] = $level;
        }

        return $this->db->query(
            "SELECT * FROM logs {$where} ORDER BY created_at DESC, id DESC LIMIT {$limit}",
            $params
        );
    }

    public function clearLogs(?string $level = null): int
    {
        if ($level !== null && $level !== '') {
            return $this->db->delete('logs', ['level' => $level]);
        }

        try {
            $rows = $this->db->query('SELECT COUNT(*) AS total FROM logs');
            $count = (int)($rows[0]['total'] ?? 0);
            $this->db->query('DELETE FROM logs');
            return $count;
        } catch (\Throwable) {
            return 0;
        }
    }

    private function clientIp(): string
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return trim(explode(',', (string)$ip)[0]);
    }
}