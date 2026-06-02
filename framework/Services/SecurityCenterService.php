<?php
declare(strict_types=1);

namespace Framework\Services;

/**
 * SecurityCenterService — cerveau du Centre de sécurité (Aegis Framework).
 *
 * Responsabilités (Phase 1 — fondation) :
 *  - Catalogue de détecteurs catégorisés (règles : gravité, score, activation).
 *  - Configuration administrable (activation globale / par catégorie, seuils).
 *  - Score de menace par IP + calcul de niveau (Faible/Moyen/Élevé/Critique).
 *  - Blocage automatique selon le score (seuil → temporaire, seuil critique → permanent).
 *  - Listes blanche (jamais bloquée) et noire (blocage manuel permanent).
 *  - Journal d'événements + agrégats pour le tableau de bord.
 *
 * L'enforcement bas-niveau (écriture des blocages IP, déblocage) est délégué à
 * SecurityFirewallService, déjà branché dans le pipeline de requête.
 *
 * @see SecurityFirewallService
 */
class SecurityCenterService
{
    private Database $db;
    private SecurityFirewallService $firewall;
    private bool $schemaReady = false;
    private ?array $settingsCache = null;
    private ?array $rulesCache = null;

    /** Métadonnées des catégories d'événements. */
    public const CATEGORIES = [
        'web'    => ['label' => 'Attaques Web',        'icon' => '🌐'],
        'scan'   => ['label' => 'Scans & Reconnaissance', 'icon' => '🔍'],
        'auth'   => ['label' => 'Authentification & Sessions', 'icon' => '🔐'],
        'upload' => ['label' => 'Uploads & Fichiers',  'icon' => '📤'],
        'abuse'  => ['label' => 'Anti-Abus',           'icon' => '🚦'],
        'admin'  => ['label' => 'Administration',      'icon' => '🖥️'],
    ];

    /** Niveaux de gravité (ordre croissant). */
    public const SEVERITIES = ['info', 'faible', 'moyen', 'eleve', 'critique'];

    /**
     * Catalogue des détecteurs : clé => [catégorie, libellé, gravité, score, description].
     * Les scores reprennent la spécification ; les autres sont des défauts raisonnables,
     * modifiables depuis l'administration (table security_rules).
     */
    public const RULES = [
        // 🌐 Attaques Web
        'clickjacking'        => ['web', 'Clickjacking', 'eleve', 25, "Tentative d'intégration du site dans une iframe externe."],
        'csrf_attack'         => ['web', 'CSRF Attack', 'eleve', 30, "Formulaire soumis avec un token CSRF invalide ou absent."],
        'xss_attempt'         => ['web', 'XSS Attempt', 'eleve', 30, "Tentative d'injection JavaScript ou HTML malveillant."],
        'sql_injection'       => ['web', 'SQL Injection Probe', 'eleve', 25, "Tentative d'injection SQL dans GET, POST ou URL."],
        'path_traversal'      => ['web', 'Path Traversal', 'eleve', 25, "Tentative d'accès hors des répertoires autorisés (../, %2e%2e/)."],
        'lfi'                 => ['web', 'Local File Inclusion (LFI)', 'critique', 35, "Tentative d'inclusion de fichiers locaux."],
        'rfi'                 => ['web', 'Remote File Inclusion (RFI)', 'critique', 40, "Tentative d'inclusion de fichiers distants."],
        // 🔍 Scans & Reconnaissance
        'directory_scan'      => ['scan', 'Directory Scan', 'faible', 10, "Scan de répertoires automatisé."],
        'git_probe'           => ['scan', 'Git Repository Probe', 'moyen', 30, "Accès à /.git/, /.git/config, /.git/HEAD."],
        'env_probe'           => ['scan', 'Environment Disclosure Probe', 'eleve', 30, "Accès à /.env, /.env.local, /.env.production."],
        'backup_probe'        => ['scan', 'Backup File Probe', 'moyen', 20, "Accès à backup.zip, database.sql, dump.sql, site.zip."],
        'sensitive_file_probe'=> ['scan', 'Sensitive File Probe', 'moyen', 20, "Tentative d'accès à des fichiers sensibles."],
        'cms_scan'            => ['scan', 'CMS Scan', 'faible', 15, "Scan ciblant WordPress, Joomla, Drupal, phpMyAdmin, Adminer."],
        'scanner_detected'    => ['scan', 'Security Scanner Detected', 'eleve', 40, "Outil de scan connu : sqlmap, nikto, acunetix, masscan, nmap, zgrab."],
        // 🔐 Authentification & Sessions
        'account_enumeration' => ['auth', 'Account Enumeration', 'moyen', 15, "Tentative de découverte de comptes utilisateurs."],
        'brute_force'         => ['auth', 'Brute Force Attempt', 'eleve', 25, "Tentatives répétées de connexion."],
        'auth_flood'          => ['auth', 'Authentication Flood', 'eleve', 30, "Abus sur les formulaires d'authentification."],
        'invalid_session'     => ['auth', 'Invalid Session Token', 'faible', 10, "Token de session invalide."],
        'session_hijacking'   => ['auth', 'Session Hijacking', 'critique', 50, "Changement anormal d'IP, User-Agent ou fingerprint."],
        // 📤 Uploads & Fichiers
        'malicious_upload'    => ['upload', 'Malicious Upload Attempt', 'critique', 40, "Fichier potentiellement dangereux (shell.php, backdoor.php…)."],
        'double_extension'    => ['upload', 'Double Extension Upload', 'eleve', 30, "Fichier à double extension (image.jpg.php…)."],
        'executable_upload'   => ['upload', 'Executable Upload Attempt', 'eleve', 30, "Fichier exécutable non autorisé."],
        // 🚦 Anti-Abus
        'rate_limit_exceeded' => ['abuse', 'Rate Limit Exceeded', 'moyen', 15, "Limite de requêtes dépassée."],
        'request_flood'       => ['abuse', 'Request Flood', 'eleve', 25, "Volume anormal de requêtes."],
        'suspicious_ua'       => ['abuse', 'Suspicious User-Agent', 'faible', 10, "User-Agent suspect ou automatisé."],
        'suspicious_pattern'  => ['abuse', 'Suspicious Request Pattern', 'moyen', 15, "Comportement de requête anormal."],
        // 🖥️ Administration
        'admin_panel_probe'   => ['admin', 'Admin Panel Probe', 'moyen', 20, "Accès à des panneaux d'admin connus (/admin, /cpanel…)."],
    ];

    /** Réglages par défaut (table security_settings). */
    public const DEFAULT_SETTINGS = [
        'enabled'              => '1',
        'auto_block'           => '1',
        'block_threshold'      => '100',  // score → blocage temporaire
        'block_duration_hours' => '24',
        'ban_threshold'        => '300',  // score → blocage permanent
        'score_decay_days'     => '7',    // remise à zéro après inactivité (info)
        'log_retention_days'   => '30',
    ];

    public function __construct(Database $db, SecurityFirewallService $firewall)
    {
        $this->db = $db;
        $this->firewall = $firewall;
    }

    /** IP cliente (délègue au firewall : prise en charge des en-têtes proxy). */
    public function clientIp(): string
    {
        return $this->firewall->getClientIp();
    }

    // ── Schéma ────────────────────────────────────────────────────────────────

    public function ensureSchema(): void
    {
        if ($this->schemaReady) {
            return;
        }
        // Le firewall garantit security_events / security_ip_blocks / security_rate_counters.
        $this->firewall->ensureTables();

        $this->db->execute("
            CREATE TABLE IF NOT EXISTS security_settings (
                param_key   VARCHAR(64) PRIMARY KEY,
                param_value VARCHAR(255) NOT NULL,
                updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->execute("
            CREATE TABLE IF NOT EXISTS security_rules (
                rule_key    VARCHAR(48) PRIMARY KEY,
                category    VARCHAR(16) NOT NULL,
                label       VARCHAR(96) NOT NULL,
                description VARCHAR(255) NULL,
                severity    VARCHAR(12) NOT NULL DEFAULT 'moyen',
                score       INT NOT NULL DEFAULT 10,
                enabled     TINYINT(1) NOT NULL DEFAULT 1,
                KEY idx_security_rules_category (category)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->execute("
            CREATE TABLE IF NOT EXISTS security_threat_scores (
                ip_address   VARCHAR(45) PRIMARY KEY,
                score        INT NOT NULL DEFAULT 0,
                events_count INT NOT NULL DEFAULT 0,
                last_rule    VARCHAR(48) NULL,
                first_seen   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                last_seen    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_threat_score (score)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->execute("
            CREATE TABLE IF NOT EXISTS security_ip_whitelist (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                note       VARCHAR(255) NULL,
                created_by INT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_whitelist_ip (ip_address)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Catégorie/score sur les événements (idempotent).
        foreach (['category VARCHAR(16) NULL', 'score INT NOT NULL DEFAULT 0', 'rule_key VARCHAR(48) NULL'] as $col) {
            $name = explode(' ', $col)[0];
            try {
                $this->db->execute("ALTER TABLE security_events ADD COLUMN IF NOT EXISTS {$col}");
            } catch (\Throwable $e) {
                // MySQL < 8 / MariaDB ancienne : tester manuellement.
                $exists = $this->db->queryOne(
                    "SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'security_events' AND column_name = ?",
                    [$name]
                );
                if (!$exists) {
                    $this->db->execute("ALTER TABLE security_events ADD COLUMN {$col}");
                }
            }
        }

        $this->seedSettings();
        $this->seedRules();
        $this->schemaReady = true;
    }

    private function seedSettings(): void
    {
        foreach (self::DEFAULT_SETTINGS as $k => $v) {
            $this->db->execute(
                "INSERT INTO security_settings (param_key, param_value) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE param_key = param_key",
                [$k, $v]
            );
        }
        // Activation par catégorie (défaut : activée).
        foreach (array_keys(self::CATEGORIES) as $cat) {
            $this->db->execute(
                "INSERT INTO security_settings (param_key, param_value) VALUES (?, '1')
                 ON DUPLICATE KEY UPDATE param_key = param_key",
                ['cat_' . $cat]
            );
        }
    }

    private function seedRules(): void
    {
        foreach (self::RULES as $key => [$cat, $label, $sev, $score, $desc]) {
            $this->db->execute(
                "INSERT INTO security_rules (rule_key, category, label, description, severity, score, enabled)
                 VALUES (?, ?, ?, ?, ?, ?, 1)
                 ON DUPLICATE KEY UPDATE category = VALUES(category), label = VALUES(label), description = VALUES(description)",
                [$key, $cat, $label, $desc, $sev, $score]
            );
        }
    }

    // ── Configuration ───────────────────────────────────────────────────────

    public function getSettings(): array
    {
        if ($this->settingsCache !== null) {
            return $this->settingsCache;
        }
        $this->ensureSchema();
        $rows = $this->db->query("SELECT param_key, param_value FROM security_settings");
        $out = [];
        foreach ($rows as $r) {
            $out[$r['param_key']] = $r['param_value'];
        }
        return $this->settingsCache = $out + self::DEFAULT_SETTINGS;
    }

    public function setSetting(string $key, string $value): void
    {
        $this->ensureSchema();
        $this->db->execute(
            "INSERT INTO security_settings (param_key, param_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE param_value = VALUES(param_value)",
            [$key, $value]
        );
        $this->settingsCache = null;
    }

    public function isEnabled(): bool
    {
        return (string)($this->getSettings()['enabled'] ?? '1') === '1';
    }

    public function isCategoryEnabled(string $category): bool
    {
        return (string)($this->getSettings()['cat_' . $category] ?? '1') === '1';
    }

    /** @return array<string,array> Règles fusionnées DB + catalogue, indexées par clé. */
    public function getRules(): array
    {
        if ($this->rulesCache !== null) {
            return $this->rulesCache;
        }
        $this->ensureSchema();
        $rows = $this->db->query("SELECT * FROM security_rules");
        $out = [];
        foreach ($rows as $r) {
            $out[$r['rule_key']] = $r;
        }
        return $this->rulesCache = $out;
    }

    public function getRule(string $key): ?array
    {
        return $this->getRules()[$key] ?? null;
    }

    public function isRuleEnabled(string $key): bool
    {
        $rule = $this->getRule($key);
        return $rule !== null && (int)$rule['enabled'] === 1;
    }

    public function updateRule(string $key, int $score, string $severity, bool $enabled): void
    {
        $this->ensureSchema();
        $severity = in_array($severity, self::SEVERITIES, true) ? $severity : 'moyen';
        $this->db->execute(
            "UPDATE security_rules SET score = ?, severity = ?, enabled = ? WHERE rule_key = ?",
            [max(0, min(100, $score)), $severity, $enabled ? 1 : 0, $key]
        );
        $this->rulesCache = null;
    }

    public function setCategoryEnabled(string $category, bool $enabled): void
    {
        $this->setSetting('cat_' . $category, $enabled ? '1' : '0');
    }

    // ── Niveau de menace ──────────────────────────────────────────────────────

    public static function levelFromScore(int $score): string
    {
        return match (true) {
            $score <= 0  => 'aucun',
            $score <= 25 => 'faible',
            $score <= 50 => 'moyen',
            $score <= 75 => 'eleve',
            default      => 'critique',
        };
    }

    // ── Cœur : enregistrer un événement et scorer ──────────────────────────────

    /**
     * Enregistre un événement de sécurité et met à jour le score de l'IP.
     * Déclenche le blocage automatique si les seuils sont franchis.
     *
     * @return array{recorded:bool, score:int, level:string, blocked:bool}
     */
    public function recordEvent(string $ip, string $ruleKey, string $details = '', ?array $meta = null): array
    {
        $this->ensureSchema();
        $result = ['recorded' => false, 'score' => 0, 'level' => 'aucun', 'blocked' => false];

        if (!$this->isEnabled()) {
            return $result;
        }
        $rule = $this->getRule($ruleKey);
        if ($rule === null || (int)$rule['enabled'] !== 1 || !$this->isCategoryEnabled($rule['category'])) {
            return $result;
        }
        // Liste blanche : on n'agit jamais sur ces IP.
        if ($this->isWhitelisted($ip)) {
            return $result;
        }

        $points = (int)$rule['score'];
        $severity = (string)$rule['severity'];

        // Journal d'événement (réutilise security_events, enrichi catégorie/score).
        $this->db->insert('security_events', [
            'ip_address'     => $ip,
            'event_type'     => $ruleKey,
            'category'       => $rule['category'],
            'score'          => $points,
            'rule_key'       => $ruleKey,
            'severity'       => $severity,
            'request_method' => $meta['method'] ?? ($_SERVER['REQUEST_METHOD'] ?? null),
            'request_uri'    => mb_substr((string)($meta['uri'] ?? ($_SERVER['REQUEST_URI'] ?? '')), 0, 512),
            'user_agent'     => mb_substr((string)($meta['ua'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 255),
            'reason'         => $details !== '' ? $details : (string)$rule['label'],
        ]);

        // Cumul du score IP.
        $this->db->execute(
            "INSERT INTO security_threat_scores (ip_address, score, events_count, last_rule)
             VALUES (?, ?, 1, ?)
             ON DUPLICATE KEY UPDATE score = score + VALUES(score), events_count = events_count + 1, last_rule = VALUES(last_rule)",
            [$ip, $points, $ruleKey]
        );

        $total = (int)($this->db->queryOne(
            "SELECT score FROM security_threat_scores WHERE ip_address = ?",
            [$ip]
        )['score'] ?? $points);

        $result['recorded'] = true;
        $result['score'] = $total;
        $result['level'] = self::levelFromScore($total);

        // Blocage automatique selon seuils.
        $settings = $this->getSettings();
        if ((string)($settings['auto_block'] ?? '1') === '1') {
            $banThreshold = (int)($settings['ban_threshold'] ?? 300);
            $blockThreshold = (int)($settings['block_threshold'] ?? 100);
            $createdBy = $_SESSION['user_id'] ?? null;

            if ($total >= $banThreshold) {
                $this->firewall->blockIp($ip, "Score de menace critique ({$total}) — blocage permanent", 0, true, $createdBy);
                $result['blocked'] = true;
            } elseif ($total >= $blockThreshold) {
                $hours = (int)($settings['block_duration_hours'] ?? 24);
                $this->firewall->blockIp($ip, "Score de menace élevé ({$total}) — blocage {$hours} h", $hours * 3600, false, $createdBy);
                $result['blocked'] = true;
            }
        }

        return $result;
    }

    // ── Détection HTTP (Phase 2 — surface URL : chemin + query + User-Agent) ──

    /** Préfixes jamais inspectés (assets statiques). */
    private const EXEMPT_PREFIXES = ['/framework/assets/', '/favicon.ico', '/robots.txt'];

    /**
     * Inspecte une requête HTTP et enregistre les détecteurs déclenchés.
     * N'analyse que la surface URL (chemin, query string, User-Agent) afin
     * d'éviter les faux positifs sur les contenus légitimes postés (POST).
     *
     * @return array{blocked:bool, reason:?string, hits:array<int,string>}
     */
    public function inspectHttpRequest(string $ip, string $method, string $uri): array
    {
        $out = ['blocked' => false, 'reason' => null, 'hits' => []];
        if (!$this->isEnabled() || $this->isWhitelisted($ip)) {
            return $out;
        }

        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        foreach (self::EXEMPT_PREFIXES as $p) {
            if (str_starts_with($path, $p)) {
                return $out;
            }
        }

        $lowerPath = strtolower(rawurldecode($path));
        $rawQuery  = (string)($_SERVER['QUERY_STRING'] ?? (parse_url($uri, PHP_URL_QUERY) ?? ''));
        $query     = strtolower(rawurldecode($rawQuery));
        $ua        = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
        $lowerUa   = strtolower($ua);
        $surface   = $lowerPath . ' ' . $query; // chemin + paramètres GET

        $hits = $this->detectFromRequest($lowerPath, $query, $surface, $lowerUa);

        $meta = ['method' => $method, 'uri' => $uri, 'ua' => $ua];
        foreach ($hits as $ruleKey => $detail) {
            $res = $this->recordEvent($ip, $ruleKey, $detail, $meta);
            if ($res['recorded']) {
                $out['hits'][] = $ruleKey;
            }
            if ($res['blocked']) {
                $out['blocked'] = true;
                $out['reason']  = $detail;
            }
        }

        return $out;
    }

    /**
     * Renvoie les détecteurs déclenchés : [rule_key => détail]. Au plus un par règle.
     * La désactivation par règle/catégorie est appliquée plus loin par recordEvent().
     */
    private function detectFromRequest(string $lowerPath, string $query, string $surface, string $lowerUa): array
    {
        $hits = [];
        $first = function (array $needles, string $hay): ?string {
            foreach ($needles as $n) {
                if ($n !== '' && str_contains($hay, $n)) {
                    return $n;
                }
            }
            return null;
        };

        // 🌐 Web — Path Traversal
        if ($m = $first(['../', '..\\', '%2e%2e', '....//', '..%2f', '..%5c'], $surface)) {
            $hits['path_traversal'] = "Séquence de traversée détectée : {$m}";
        }
        // 🌐 Web — LFI
        if ($m = $first(['/etc/passwd', '/proc/self/', 'php://filter', 'php://input', 'file://', 'win.ini', 'boot.ini', '/etc/shadow'], $surface)) {
            $hits['lfi'] = "Inclusion de fichier local : {$m}";
        }
        // 🌐 Web — RFI (param = http(s)/ftp ://)
        if (preg_match('~=\s*(https?|ftp)://~i', $query)) {
            $hits['rfi'] = "Paramètre pointant vers une ressource distante";
        }
        // 🌐 Web — SQL Injection
        if ($m = $first([
            'union select', 'union all select', "or 1=1", "' or '", '" or "', ' or 1=1', 'sleep(', 'benchmark(',
            'information_schema', 'concat(', 'group_concat', '/*!', '0x3c', "'--", '";--', 'waitfor delay',
        ], $surface)) {
            $hits['sql_injection'] = "Motif d'injection SQL : {$m}";
        }
        // 🌐 Web — XSS
        if ($m = $first(['<script', '%3cscript', 'onerror=', 'onload=', 'javascript:', '<svg', '<img', 'document.cookie', 'alert(', 'onmouseover='], $surface)) {
            $hits['xss_attempt'] = "Motif XSS : {$m}";
        }

        // 🔍 Scans — fichiers/dossiers sensibles
        if (str_contains($lowerPath, '/.git')) {
            $hits['git_probe'] = "Accès au dépôt Git ({$lowerPath})";
        }
        if (str_contains($lowerPath, '/.env')) {
            $hits['env_probe'] = "Accès à un fichier d'environnement ({$lowerPath})";
        }
        if ($m = $first(['backup.zip', 'database.sql', 'dump.sql', 'site.zip', '.sql.gz', 'backup.tar', '.bak', 'www.zip', 'db.sql'], $lowerPath)) {
            $hits['backup_probe'] = "Recherche de sauvegarde : {$m}";
        }
        if ($m = $first(['wp-config', '.htpasswd', 'id_rsa', '.ssh/', 'web.config', '.aws/', 'config.php.bak', '.npmrc', '.svn/'], $lowerPath)) {
            $hits['sensitive_file_probe'] = "Accès fichier sensible : {$m}";
        }
        if ($m = $first(['wp-admin', 'wp-login', '/wordpress', '/joomla', '/drupal', 'phpmyadmin', '/adminer', 'xmlrpc.php', '/typo3'], $lowerPath)) {
            $hits['cms_scan'] = "Scan CMS : {$m}";
        }
        if ($m = $first(['sqlmap', 'nikto', 'acunetix', 'masscan', 'nmap', 'zgrab', 'nessus', 'dirbuster', 'gobuster', 'wpscan', 'fuzz', 'nuclei', 'httpx'], $lowerUa)) {
            $hits['scanner_detected'] = "Outil de scan détecté (UA) : {$m}";
        }
        // 🔍 Directory scan (extensions/sondes courantes hors contexte)
        if ($m = $first(['/cgi-bin/', '.action', '.do~', '/vendor/', '/.well-known/security', '/server-status', '/actuator'], $lowerPath)) {
            $hits['directory_scan'] = "Sonde de répertoire : {$m}";
        }

        // 🖥️ Admin — panneaux tiers (on exclut notre propre /admin)
        if ($m = $first(['/cpanel', '/whm', '/plesk', '/webmail', '/administrator', '/admin.php', '/manager/html', '/phpmyadmin', '/panel', '/_admin'], $lowerPath)) {
            $hits['admin_panel_probe'] = "Sonde de panneau d'administration : {$m}";
        }

        // 🚦 Anti-abus — User-Agent automatisé / suspect
        if ($lowerUa === '') {
            $hits['suspicious_ua'] = "User-Agent absent";
        } elseif ($m = $first(['curl/', 'wget/', 'python-requests', 'go-http-client', 'libwww', 'scrapy', 'httpclient', 'java/', 'okhttp', 'winhttp'], $lowerUa)) {
            $hits['suspicious_ua'] = "User-Agent automatisé : {$m}";
        }
        // 🚦 Anti-abus — motif de requête anormal
        if ($m = $first(['%00', '<?php', 'eval(', 'base64_decode', 'phpinfo(', '${', '/bin/sh', 'cmd.exe'], $surface)) {
            $hits['suspicious_pattern'] = "Motif de requête anormal : {$m}";
        }

        return $hits;
    }

    /** Extensions exécutables/scripts interdites en upload. */
    private const EXEC_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'pht', 'phps', 'phar',
        'exe', 'sh', 'bat', 'cmd', 'com', 'cgi', 'pl', 'py', 'jsp', 'asp', 'aspx', 'htaccess',
    ];
    /** Extensions « bénignes » utilisées dans les doubles extensions. */
    private const BENIGN_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'pdf', 'doc', 'docx', 'zip', 'txt', 'csv'];
    /** Noms de webshells connus. */
    private const KNOWN_SHELLS = ['shell.php', 'backdoor', 'c99', 'r57', 'webshell', 'cmd.php', 'wso.php', 'b374k', 'eval.php'];

    /**
     * Inspecte les fichiers uploadés ($_FILES) et enregistre les détecteurs.
     * Basé sur les noms/extensions (faible taux de faux positifs : les uploads
     * légitimes sont des images/documents/archives).
     */
    public function inspectUploadedFiles(string $ip, ?array $files = null): array
    {
        $out = ['blocked' => false, 'hits' => []];
        if (!$this->isEnabled() || $this->isWhitelisted($ip)) {
            return $out;
        }
        $files = $files ?? ($_FILES ?? []);
        $names = $this->collectFileNames($files);
        $meta = ['method' => $_SERVER['REQUEST_METHOD'] ?? 'POST', 'uri' => $_SERVER['REQUEST_URI'] ?? '', 'ua' => $_SERVER['HTTP_USER_AGENT'] ?? ''];

        foreach ($names as $name) {
            $lower = strtolower(trim($name));
            if ($lower === '') {
                continue;
            }
            $parts = explode('.', $lower);
            $ext = end($parts);
            $rule = null; $detail = '';

            // Webshell connu.
            foreach (self::KNOWN_SHELLS as $shell) {
                if (str_contains($lower, $shell)) {
                    $rule = 'malicious_upload';
                    $detail = "Nom de webshell détecté : {$name}";
                    break;
                }
            }
            // Double extension (bénigne suivie d'exécutable), ex. image.jpg.php
            if ($rule === null && count($parts) >= 3) {
                $prev = $parts[count($parts) - 2];
                if (in_array($ext, self::EXEC_EXTENSIONS, true) && in_array($prev, self::BENIGN_EXTENSIONS, true)) {
                    $rule = 'double_extension';
                    $detail = "Double extension : {$name}";
                }
            }
            // Extension exécutable simple.
            if ($rule === null && in_array($ext, self::EXEC_EXTENSIONS, true)) {
                $rule = 'executable_upload';
                $detail = "Extension exécutable interdite : .{$ext} ({$name})";
            }

            if ($rule !== null) {
                $res = $this->recordEvent($ip, $rule, $detail, $meta);
                if ($res['recorded']) {
                    $out['hits'][] = $rule;
                }
                if ($res['blocked']) {
                    $out['blocked'] = true;
                }
            }
        }
        return $out;
    }

    /** Aplati les noms de fichiers de $_FILES (gère les champs multiples). */
    private function collectFileNames(array $files): array
    {
        $names = [];
        foreach ($files as $field) {
            if (!isset($field['name'])) {
                continue;
            }
            if (is_array($field['name'])) {
                array_walk_recursive($field['name'], function ($n) use (&$names) {
                    if (is_string($n) && $n !== '') {
                        $names[] = $n;
                    }
                });
            } elseif (is_string($field['name']) && $field['name'] !== '') {
                $names[] = $field['name'];
            }
        }
        return $names;
    }

    /** Signale une tentative de détournement de session (drapeau posé par SessionManager). */
    public function reportSessionHijackIfFlagged(string $ip): array
    {
        if (empty($GLOBALS['_aegis_session_hijack'])) {
            return ['blocked' => false];
        }
        unset($GLOBALS['_aegis_session_hijack']);
        $res = $this->recordEvent($ip, 'session_hijacking', 'Empreinte de session divergente (IP/User-Agent)');
        return ['blocked' => $res['blocked']];
    }

    // ── Listes blanche / noire ──────────────────────────────────────────────

    public function isWhitelisted(string $ip): bool
    {
        // Config statique (trusted_ips) + table administrable.
        if ($this->firewall->isWhitelisted($ip)) {
            return true;
        }
        $this->ensureSchema();
        return (bool)$this->db->queryOne(
            "SELECT 1 FROM security_ip_whitelist WHERE ip_address = ?",
            [$ip]
        );
    }

    public function addToWhitelist(string $ip, string $note = '', ?int $createdBy = null): void
    {
        $this->ensureSchema();
        $this->db->execute(
            "INSERT INTO security_ip_whitelist (ip_address, note, created_by) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE note = VALUES(note)",
            [$ip, $note, $createdBy]
        );
        // Une IP en liste blanche ne doit pas rester bloquée.
        $this->firewall->unblockIp($ip);
    }

    public function removeFromWhitelist(string $ip): void
    {
        $this->ensureSchema();
        $this->db->delete('security_ip_whitelist', ['ip_address' => $ip]);
    }

    public function getWhitelist(): array
    {
        $this->ensureSchema();
        return $this->db->query("SELECT * FROM security_ip_whitelist ORDER BY created_at DESC");
    }

    /** Liste noire = blocages permanents (manuels ou auto critiques). */
    public function addToBlacklist(string $ip, string $reason = 'Liste noire (manuel)', ?int $createdBy = null): void
    {
        $this->firewall->blockIp($ip, $reason, 0, true, $createdBy);
    }

    public function removeFromBlacklist(string $ip): int
    {
        return $this->firewall->unblockIp($ip);
    }

    // ── Tableau de bord ───────────────────────────────────────────────────────

    public function getDashboard(): array
    {
        $this->ensureSchema();

        $stats = [
            'enabled'        => $this->isEnabled(),
            'active_blocks'  => (int)($this->db->queryOne("SELECT COUNT(*) c FROM security_ip_blocks WHERE permanent = 1 OR blocked_until IS NULL OR blocked_until > NOW()")['c'] ?? 0),
            'events_24h'     => (int)($this->db->queryOne("SELECT COUNT(*) c FROM security_events WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")['c'] ?? 0),
            'events_total'   => (int)($this->db->queryOne("SELECT COUNT(*) c FROM security_events")['c'] ?? 0),
            'tracked_ips'    => (int)($this->db->queryOne("SELECT COUNT(*) c FROM security_threat_scores")['c'] ?? 0),
            'whitelist'      => (int)($this->db->queryOne("SELECT COUNT(*) c FROM security_ip_whitelist")['c'] ?? 0),
        ];

        $byCategory = $this->db->query("
            SELECT COALESCE(category,'?') cat, COUNT(*) c
            FROM security_events
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY category ORDER BY c DESC
        ");
        $bySeverity = $this->db->query("
            SELECT severity, COUNT(*) c FROM security_events
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY severity
        ");

        return [
            'stats'       => $stats,
            'by_category' => $byCategory,
            'by_severity' => $bySeverity,
            'top_ips'     => $this->db->query("SELECT * FROM security_threat_scores ORDER BY score DESC LIMIT 20"),
            'blocks'      => $this->db->query("SELECT * FROM security_ip_blocks WHERE permanent = 1 OR blocked_until IS NULL OR blocked_until > NOW() ORDER BY created_at DESC LIMIT 100"),
            'events'      => $this->db->query("SELECT * FROM security_events ORDER BY created_at DESC LIMIT 200"),
        ];
    }

    /** Règles regroupées par catégorie pour l'affichage admin. */
    public function getRulesByCategory(): array
    {
        $out = [];
        foreach (self::CATEGORIES as $catKey => $meta) {
            $out[$catKey] = ['meta' => $meta, 'enabled' => $this->isCategoryEnabled($catKey), 'rules' => []];
        }
        foreach ($this->getRules() as $key => $rule) {
            $cat = $rule['category'];
            if (isset($out[$cat])) {
                $out[$cat]['rules'][$key] = $rule;
            }
        }
        return $out;
    }

    public function purgeEvents(?int $olderThanDays = null): int
    {
        $this->ensureSchema();
        if ($olderThanDays !== null && $olderThanDays > 0) {
            return $this->db->execute("DELETE FROM security_events WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)", [$olderThanDays]);
        }
        return $this->db->execute("DELETE FROM security_events");
    }
}
