<?php
declare(strict_types=1);

/**
 * Installer — moteur de l'installation Aegis Framework V4.
 * Autonome : ne dépend pas du bootstrap du CMS (la base n'existe pas encore).
 */
class Installer
{
    /** Version PHP minimale requise. */
    public const MIN_PHP = '8.5.0';

    /** Extensions PHP requises. */
    public const REQUIRED_EXTENSIONS = [
        'mbstring', 'pdo_mysql', 'curl', 'gd', 'fileinfo', 'openssl', 'zip', 'json', 'intl',
    ];

    private string $root;

    public function __construct(string $root)
    {
        $this->root = rtrim($root, '/\\');
    }

    // ───────────────────────────── Vérifications ─────────────────────────────

    /** @return array<int,array{label:string,ok:bool,value:string,help:string}> */
    public function checkRequirements(): array
    {
        $checks = [];

        $phpOk = version_compare(PHP_VERSION, self::MIN_PHP, '>=');
        $checks[] = [
            'label' => 'PHP ' . self::MIN_PHP . '+',
            'ok'    => $phpOk,
            'value' => PHP_VERSION,
            'help'  => $phpOk ? '' : "Votre hébergeur utilise PHP " . PHP_VERSION . ". Sélectionnez PHP " . self::MIN_PHP . " ou plus récent dans le panneau de votre hébergeur (souvent « Version PHP »).",
        ];

        foreach (self::REQUIRED_EXTENSIONS as $ext) {
            $ok = extension_loaded($ext);
            $checks[] = [
                'label' => $ext,
                'ok'    => $ok,
                'value' => $ok ? 'Chargée' : 'Absente',
                'help'  => $ok ? '' : "Activez l'extension « {$ext} » : dans php.ini, retirez le point-virgule devant « extension={$ext} » puis redémarrez le serveur (ou activez-la depuis le panneau de votre hébergeur).",
            ];
        }

        // Directives ini
        $checks[] = $this->iniCheck('file_uploads', (bool) ini_get('file_uploads'),
            "Activez « file_uploads = On » dans php.ini pour autoriser l'envoi de fichiers.");
        $checks[] = $this->iniCheck('log_errors', (bool) ini_get('log_errors'),
            "Activez « log_errors = On » dans php.ini pour journaliser les erreurs.");

        // mod_rewrite
        $rewrite = $this->hasModRewrite();
        $checks[] = [
            'label' => 'mod_rewrite',
            'ok'    => $rewrite,
            'value' => $rewrite ? 'Actif' : 'Inconnu / inactif',
            'help'  => $rewrite ? '' : "Activez le module Apache mod_rewrite (a2enmod rewrite) et autorisez AllowOverride All, sinon les URLs propres ne fonctionneront pas.",
        ];

        return $checks;
    }

    private function iniCheck(string $label, bool $ok, string $help): array
    {
        return ['label' => $label, 'ok' => $ok, 'value' => $ok ? 'On' : 'Off', 'help' => $ok ? '' : $help];
    }

    private function hasModRewrite(): bool
    {
        if (function_exists('apache_get_modules')) {
            return in_array('mod_rewrite', apache_get_modules(), true);
        }
        // Hors Apache (CGI/Nginx) : on ne peut pas détecter → on considère OK si .htaccess présent.
        return getenv('HTTP_MOD_REWRITE') === 'On' || file_exists($this->root . '/.htaccess');
    }

    /** @return array<int,array{label:string,path:string,ok:bool}> */
    public function checkWritable(): array
    {
        $paths = [
            'Racine (.env)'      => $this->root,
            'framework/logs'     => $this->root . '/framework/logs',
            'framework/cache'    => $this->root . '/framework/cache',
            'framework/uploads'  => $this->root . '/framework/uploads',
        ];
        $out = [];
        foreach ($paths as $label => $path) {
            if (!is_dir($path)) {
                @mkdir($path, 0755, true);
            }
            $out[] = ['label' => $label, 'path' => $path, 'ok' => is_writable($path)];
        }
        return $out;
    }

    public function requirementsPass(): bool
    {
        foreach ($this->checkRequirements() as $c) {
            if (!$c['ok']) return false;
        }
        foreach ($this->checkWritable() as $w) {
            if (!$w['ok']) return false;
        }
        return true;
    }

    // ───────────────────────────── Base de données ───────────────────────────

    private function pdo(array $db, bool $withDb = true): \PDO
    {
        $dsn = "mysql:host={$db['host']};port={$db['port']};charset=utf8mb4";
        if ($withDb) {
            $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4";
        }
        return new \PDO($dsn, $db['user'], $db['pass'], [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);
    }

    /** Teste la connexion (sans base sélectionnée). */
    public function testConnection(array $db): array
    {
        try {
            $this->pdo($db, false)->query('SELECT 1');
            return ['success' => true, 'message' => 'Connexion réussie au serveur MySQL.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Connexion impossible : ' . $e->getMessage()];
        }
    }

    /** Crée la base si nécessaire. */
    public function createDatabase(array $db): array
    {
        try {
            $pdo = $this->pdo($db, false);
            $name = str_replace('`', '', $db['name']);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            return ['success' => true, 'message' => "Base « {$name} » prête."];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Création de la base impossible : ' . $e->getMessage()];
        }
    }

    /** Exécute le schéma SQL. */
    public function runSchema(array $db): array
    {
        $file = __DIR__ . '/schema.sql';
        if (!is_file($file)) {
            return ['success' => false, 'message' => 'Fichier schema.sql introuvable.'];
        }
        try {
            $pdo = $this->pdo($db, true);
            $pdo->exec(file_get_contents($file)); // mysqlnd autorise les requêtes multiples
            $count = (int) $pdo->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()')->fetchColumn();
            return ['success' => true, 'message' => "Tables créées ({$count} au total)."];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Import du schéma impossible : ' . $e->getMessage()];
        }
    }

    /** Crée le compte super-administrateur. */
    public function createAdmin(array $db, array $admin): array
    {
        try {
            $pdo = $this->pdo($db, true);
            $hash = password_hash($admin['password'], PASSWORD_ARGON2ID);
            $stmt = $pdo->prepare(
                "INSERT INTO users (username, email, password, role, status, email_verified, created_at)
                 VALUES (?, ?, ?, 'superadmin', 'active', 1, NOW())
                 ON DUPLICATE KEY UPDATE email = VALUES(email), password = VALUES(password), role = 'superadmin', status = 'active'"
            );
            $stmt->execute([$admin['username'], $admin['email'], $hash]);
            return ['success' => true, 'message' => "Compte administrateur « {$admin['username']} » créé."];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Création de l\'administrateur impossible : ' . $e->getMessage()];
        }
    }

    /** Active les modules cœur + réglages par défaut. */
    public function seedDefaults(array $db, array $admin): array
    {
        try {
            $pdo = $this->pdo($db, true);

            foreach ([['Configuration', 5], ['Auth', 10], ['System', 50]] as [$name, $prio]) {
                $pdo->prepare("INSERT INTO modules (name, version, active, priority, installed_at)
                    VALUES (?, '1.0.0', 1, ?, NOW())
                    ON DUPLICATE KEY UPDATE active = 1, priority = VALUES(priority)")
                    ->execute([$name, $prio]);
            }

            $settings = [
                ['site_name', $admin['site_name'] ?? 'Aegis Framework', 'string'],
                ['site_description', '', 'string'],
                ['webmaster_email', $admin['email'] ?? '', 'string'],
                ['cms_version', '4.0.0', 'string'],
                // Système
                ['debug_mode', '0', 'bool'],
                ['cache_enabled', '1', 'bool'],
                ['cache_ttl', '3600', 'int'],
                ['maintenance_mode', '0', 'bool'],
                ['maintenance_theme', 'moderne', 'string'],
                ['turbonav_enabled', '1', 'bool'],
                // Sécurité
                ['registration_enabled', '1', 'bool'],
                ['cookies_banner_enabled', '0', 'bool'],
                ['recaptcha_enabled', '0', 'bool'],
                ['recaptcha_site_key', '', 'string'],
                ['recaptcha_secret_key', '', 'string'],
                ['recaptcha_login', '0', 'bool'],
                ['recaptcha_register', '0', 'bool'],
                // SEO
                ['meta_title_template', '{page_title} - {site_name}', 'string'],
                ['meta_description_default', '', 'string'],
                ['meta_keywords_default', '', 'string'],
                // IA
                ['openai_api_key', '', 'string'],
                ['claude_api_key', '', 'string'],
                ['mistral_api_key', '', 'string'],
                ['default_ai_provider', 'openai', 'string'],
                // E-mails (réinitialisation mot de passe)
                ['password_reset_from_email', 'noreply@' . ($_SERVER['SERVER_NAME'] ?? 'exemple.com'), 'string'],
                ['password_reset_from_name', $admin['site_name'] ?? 'Aegis Framework', 'string'],
                ['password_reset_email_subject', 'Réinitialisation de votre mot de passe - {site_name}', 'string'],
                ['password_reset_email_body', "Bonjour {username},\r\n\r\nUne demande de réinitialisation de mot de passe a été effectuée pour votre compte {site_name}.\r\n\r\nCliquez sur le lien suivant pour choisir un nouveau mot de passe :\r\n{reset_link}\r\n\r\nCe lien expire dans {expires_minutes} minutes. Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.\r\n\r\n{site_name}", 'string'],
            ];
            foreach ($settings as [$k, $v, $t]) {
                $pdo->prepare("INSERT INTO settings (param_key, param_value, param_type)
                    VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE param_value = VALUES(param_value)")
                    ->execute([$k, $v, $t]);
            }
            return ['success' => true, 'message' => 'Modules cœur activés et réglages initialisés.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Initialisation impossible : ' . $e->getMessage()];
        }
    }

    /** Insère les données par défaut (seed.sql) — modèles IA, etc. */
    public function runSeed(array $db): array
    {
        $file = __DIR__ . '/seed.sql';
        if (!is_file($file)) {
            return ['success' => true, 'message' => 'Aucune donnée par défaut à insérer.'];
        }
        try {
            $this->pdo($db, true)->exec(file_get_contents($file));
            return ['success' => true, 'message' => 'Données par défaut insérées (modèles IA…).'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Insertion des données par défaut impossible : ' . $e->getMessage()];
        }
    }

    /** Écrit le fichier .env. */
    public function writeEnv(array $db, array $admin): array
    {
        $content = "# Généré par l'installeur le " . date('Y-m-d H:i:s') . "\n"
            . "APP_ENV=production\n"
            . "APP_TIMEZONE=Europe/Paris\n"
            . "DB_TYPE=mysql\n"
            . "DB_HOST={$db['host']}\n"
            . "DB_PORT={$db['port']}\n"
            . "DB_NAME={$db['name']}\n"
            . "DB_USER={$db['user']}\n"
            . "DB_PASS=\"{$db['pass']}\"\n";
        if (@file_put_contents($this->root . '/.env', $content) === false) {
            return ['success' => false, 'message' => "Écriture du .env impossible. Vérifiez les droits d'écriture à la racine."];
        }
        return ['success' => true, 'message' => 'Configuration (.env) enregistrée.'];
    }

    public function lockFile(): string
    {
        return __DIR__ . '/installed.lock';
    }

    public function isInstalled(): bool
    {
        return is_file($this->lockFile());
    }

    public function finalize(): array
    {
        @file_put_contents($this->lockFile(), 'Installé le ' . date('Y-m-d H:i:s') . "\n");
        return ['success' => true, 'message' => 'Installation finalisée.'];
    }
}
