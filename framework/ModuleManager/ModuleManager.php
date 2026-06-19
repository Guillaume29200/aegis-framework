<?php
declare(strict_types=1);

namespace Framework\ModuleManager;

use Framework\Interfaces\ModuleInterface;
use Framework\Services\Database;
use Framework\Services\Logger;

/**
 * ModuleManager - Gestion des modules
 * 
 * ResponsabilitÃ©s:
 * - DÃ©couvrir modules disponibles
 * - Charger modules actifs
 * - GÃ©rer hooks
 * - Installer/dÃ©sinstaller modules
 */
class ModuleManager
{
    private Database $db;
    private Logger $logger;
    private string $modulesPath;
    private array $loadedModules = [];
    private array $hooks = [];
    private ?string $lastError = null;
    
    public function __construct(Database $db, Logger $logger, string $modulesPath)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->modulesPath = rtrim($modulesPath, '/');
    }
    
    /**
     * DÃ©couvrir tous les modules disponibles
     */
    public function discoverModules(): array
    {
        $modules = [];
        
        if (!is_dir($this->modulesPath)) {
            return $modules;
        }
        
        $dirs = scandir($this->modulesPath);
        
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }
            
            $modulePath = $this->modulesPath . '/' . $dir;
            
            if (!is_dir($modulePath)) {
                continue;
            }
            
            // Chercher module.json
            $configFile = $modulePath . '/module.json';
            
            if (!file_exists($configFile)) {
                continue;
            }
            
            $config = json_decode(file_get_contents($configFile), true);
            
            if (!$config) {
                $this->logger->warning("Invalid module.json in: {$dir}");
                continue;
            }
            
            $modules[$dir] = $config;
        }
        
        return $modules;
    }
    
    /**
     * Charger tous les modules actifs
     */
    public function loadModules(): void
    {
        try {
            // RÃ©cupÃ©rer modules actifs depuis DB
            $activeModules = $this->db->query(
                "SELECT * FROM modules WHERE active = 1 ORDER BY priority ASC"
            );
            
            foreach ($activeModules as $moduleData) {
                try {
                    $this->loadModule($moduleData['name']);
                } catch (\Exception $e) {
                    $this->logger->error("Failed to load module: {$moduleData['name']}", [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Si table modules n'existe pas, charger tous les modules disponibles
            $this->logger->warning("Table 'modules' not found, loading all available modules");
            
            $availableModules = $this->discoverModules();
            
            foreach ($availableModules as $moduleName => $config) {
                try {
                    $this->loadModule($moduleName);
                } catch (\Exception $ex) {
                    $this->logger->error("Failed to load module: {$moduleName}", [
                        'error' => $ex->getMessage()
                    ]);
                }
            }
        }
    }
    
    /**
     * Charger un module spÃ©cifique
     */
    private function loadModule(string $moduleName): void
    {
        $modulePath = $this->modulesPath . '/' . $moduleName;
        
        if (!is_dir($modulePath)) {
            throw new \Exception("Module directory not found: {$moduleName}");
        }
        
        // Charger module.json
        $configFile = $modulePath . '/module.json';
        
        if (!file_exists($configFile)) {
            throw new \Exception("module.json not found in: {$moduleName}");
        }
        
        $config = json_decode(file_get_contents($configFile), true);
        
        // VÃ©rifier classe principale
        $mainClass = $config['class'] ?? null;
        
        if (!$mainClass) {
            throw new \Exception("No main class defined in module.json: {$moduleName}");
        }
        
        // Charger classe (autoloader devrait le gÃ©rer)
        if (!class_exists($mainClass)) {
            throw new \Exception("Module class not found: {$mainClass}");
        }
        
        // Instancier module
        $module = new $mainClass($config);
        
        if (!$module instanceof ModuleInterface) {
            throw new \Exception("Module must implement ModuleInterface: {$mainClass}");
        }
        
        // VÃ©rifier compatibilitÃ©
        $cmsVersion = $this->getCMSVersion();
        if (!$module->isCompatible($cmsVersion)) {
            throw new \Exception("Module not compatible with CMS version {$cmsVersion}: {$moduleName}");
        }
        
        // VÃ©rifier dÃ©pendances
        $this->checkDependencies($module);
        
        // Initialiser module
        $module->init();
        
        // Enregistrer hooks
        $this->registerModuleHooks($module);
        
        // Stocker module chargÃ©
        $this->loadedModules[$moduleName] = $module;
    }
    
    /**
     * VÃ©rifier dÃ©pendances d'un module
     */
    private function checkDependencies(ModuleInterface $module): void
    {
        $dependencies = $module->getDependencies();
        
        foreach ($dependencies as $depName => $minVersion) {
            // VÃ©rifier si dÃ©pendance chargÃ©e
            if (!isset($this->loadedModules[$depName])) {
                throw new \Exception(
                    "Missing dependency: {$depName} for module {$module->getName()}"
                );
            }
            
            // VÃ©rifier version
            $depModule = $this->loadedModules[$depName];
            if (version_compare($depModule->getVersion(), $minVersion, '<')) {
                throw new \Exception(
                    "Dependency version mismatch: {$depName} >= {$minVersion} required"
                );
            }
        }
    }
    
    /**
     * Enregistrer les hooks d'un module
     */
    private function registerModuleHooks(ModuleInterface $module): void
    {
        $hooks = $module->getHooks();
        
        foreach ($hooks as $hookName => $hookData) {
            if (!is_array($hookData)) {
                $hookData = [$hookData, 10]; // [callable, priority]
            }
            
            [$callable, $priority] = $hookData;
            
            if (!isset($this->hooks[$hookName])) {
                $this->hooks[$hookName] = [];
            }
            
            $this->hooks[$hookName][] = [
                'callable' => $callable,
                'priority' => $priority ?? 10,
                'module' => $module->getName()
            ];
        }
        
        // Trier par prioritÃ©
        foreach ($this->hooks as $hookName => &$hooks) {
            usort($hooks, fn($a, $b) => $a['priority'] <=> $b['priority']);
        }
    }
    
    /**
     * ExÃ©cuter un hook
     */
    public function executeHook(string $hookName, ...$args)
    {
        if (!isset($this->hooks[$hookName])) {
            return null;
        }
        
        $result = null;
        
        foreach ($this->hooks[$hookName] as $hook) {
            try {
                $result = call_user_func($hook['callable'], ...$args);
                
                // Si hook retourne false, arrÃªter propagation
                if ($result === false) {
                    break;
                }
            } catch (\Exception $e) {
                $this->logger->error("Hook execution failed: {$hookName}", [
                    'module' => $hook['module'],
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $result;
    }
    
    /**
     * Exécuter un fichier SQL d'un module (database/<file>) s'il existe.
     * Convention : modules/<Name>/database/install.sql, uninstall.sql, update_x.y.z.sql
     */
    private function runModuleSqlFile(string $moduleName, string $file): void
    {
        $path = $this->modulesPath . '/' . $moduleName . '/database/' . $file;
        if (!is_file($path)) {
            return;
        }
        $sql = trim((string) file_get_contents($path));
        if ($sql === '') {
            return;
        }
        $this->execSqlScript($sql);
        $this->logger->info("Module SQL exécuté : {$moduleName}/database/{$file}");
    }

    /**
     * Exécute un script SQL complet (dumps phpMyAdmin/mysqldump inclus) en le
     * découpant statement par statement. Gère la directive client `DELIMITER`
     * (nécessaire pour les triggers/procédures dont le corps contient des `;`).
     * Chaque statement est exécuté séparément → erreurs précises et support des
     * blocs BEGIN…END.
     */
    private function execSqlScript(string $sql): void
    {
        $pdo = $this->db->getPDO();
        $delimiter = ';';
        $buffer = '';
        // Normalise les fins de ligne.
        $lines = preg_split('/\r\n|\r|\n/', $sql);

        // Désactive les contrôles de clés étrangères le temps du script :
        // permet les CREATE TABLE avec FK « en avant » (table référencée créée
        // plus loin), comme dans les dumps phpMyAdmin/mysqldump.
        try { $pdo->exec('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) {}

        try {
        foreach ($lines as $line) {
            $trim = ltrim($line);

            // Changement de délimiteur (directive client, à ne pas envoyer au serveur).
            if (stripos($trim, 'DELIMITER ') === 0) {
                $delimiter = trim(substr($trim, 10));
                continue;
            }
            // Ignore les lignes de commentaire pleines (mais garde les /*! ... */ exécutables).
            if ($trim === '' || str_starts_with($trim, '-- ') || $trim === '--' || str_starts_with($trim, '#')) {
                continue;
            }

            $buffer .= $line . "\n";

            // Fin de statement quand la ligne se termine par le délimiteur courant.
            $rtrim = rtrim($line);
            if ($delimiter !== '' && str_ends_with($rtrim, $delimiter)) {
                $stmt = substr(rtrim($buffer), 0, -strlen($delimiter));
                $stmt = trim($stmt);
                if ($stmt !== '') {
                    $pdo->exec($stmt);
                }
                $buffer = '';
            }
        }

        // Dernier statement éventuel sans délimiteur final.
        $tail = trim($buffer);
        if ($tail !== '') {
            $pdo->exec($tail);
        }
        } finally {
            try { $pdo->exec('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) {}
        }
    }

    /**
     * Installer le schéma d'un module. Supporte deux conventions :
     *  1) modules/<Name>/database/install.sql (un seul fichier)
     *  2) modules/<Name>/schema.sql + schema_*.sql (fichiers multiples, racine du module)
     * Chaque fichier est exécuté dans son propre try/catch pour qu'une migration
     * facultative qui échoue n'interrompe pas l'installation.
     */
    private function runModuleSchema(string $moduleName): void
    {
        $base = $this->modulesPath . '/' . $moduleName;

        // Convention 1 : database/install.sql
        if (is_file($base . '/database/install.sql')) {
            $this->runModuleSqlFile($moduleName, 'install.sql');
            return;
        }

        // Convention 2 : schema.sql (base) puis schema_*.sql (ajouts), ordre alphabétique
        $files = [];
        if (is_file($base . '/schema.sql')) {
            $files[] = $base . '/schema.sql';
        }
        $extra = glob($base . '/schema_*.sql') ?: [];
        sort($extra, SORT_STRING);
        $files = array_merge($files, $extra);

        $pdo = $this->db->getPDO();
        foreach ($files as $f) {
            $sql = trim((string) file_get_contents($f));
            if ($sql === '') {
                continue;
            }
            try {
                $pdo->exec($sql);
            } catch (\Throwable $e) {
                // Migration facultative / déjà appliquée : on log et on continue.
                $this->logger->warning("Module {$moduleName} : SQL ignoré (" . basename($f) . ") : " . $e->getMessage());
            }
        }
        $this->logger->info("Module {$moduleName} : schéma installé (" . count($files) . " fichier(s)).");
    }

    // ── Migrations versionnées (par module) ─────────────────────────────────

    public function ensureMigrationsTable(): void
    {
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS module_migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                module VARCHAR(64) NOT NULL,
                migration VARCHAR(191) NOT NULL,
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_module_migration (module, migration)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /** Fichiers de migration d'un module (database/migrations/*.sql), triés. */
    private function migrationFiles(string $moduleName): array
    {
        $dir = $this->modulesPath . '/' . $moduleName . '/database/migrations';
        if (!is_dir($dir)) {
            return [];
        }
        $files = glob($dir . '/*.sql') ?: [];
        sort($files, SORT_STRING);
        return $files;
    }

    /** Migrations déjà appliquées (noms de fichiers). */
    private function appliedMigrations(string $moduleName): array
    {
        $this->ensureMigrationsTable();
        $rows = $this->db->query("SELECT migration FROM module_migrations WHERE module = ?", [$moduleName]);
        return array_column($rows, 'migration');
    }

    private function markMigrationApplied(string $moduleName, string $migration): void
    {
        $this->db->execute(
            "INSERT INTO module_migrations (module, migration) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE applied_at = applied_at",
            [$moduleName, $migration]
        );
    }

    /**
     * Marque TOUTES les migrations actuelles comme appliquées sans les exécuter.
     * Utilisé après un install.sql « baseline » (qui contient déjà l'état à jour).
     */
    private function baselineMigrations(string $moduleName): void
    {
        $this->ensureMigrationsTable();
        foreach ($this->migrationFiles($moduleName) as $f) {
            $this->markMigrationApplied($moduleName, basename($f));
        }
    }

    /**
     * Compte les migrations en attente (présentes mais pas encore appliquées).
     */
    public function pendingMigrationCount(string $moduleName): int
    {
        $applied = $this->appliedMigrations($moduleName);
        $pending = 0;
        foreach ($this->migrationFiles($moduleName) as $f) {
            if (!in_array(basename($f), $applied, true)) {
                $pending++;
            }
        }
        return $pending;
    }

    /**
     * Exécute les migrations en attente d'un module, dans l'ordre. Chaque
     * migration appliquée est enregistrée. Lève une exception au premier échec
     * (la migration fautive n'est pas marquée appliquée).
     *
     * @return int Nombre de migrations appliquées.
     */
    public function runPendingMigrations(string $moduleName): int
    {
        $applied = $this->appliedMigrations($moduleName);
        $pdo = $this->db->getPDO();
        $count = 0;

        foreach ($this->migrationFiles($moduleName) as $file) {
            $name = basename($file);
            if (in_array($name, $applied, true)) {
                continue;
            }
            $sql = trim((string) file_get_contents($file));
            if ($sql === '') {
                $this->markMigrationApplied($moduleName, $name);
                continue;
            }
            try {
                $pdo->exec($sql);
            } catch (\Throwable $e) {
                throw new \RuntimeException("Migration « {$name} » a échoué : " . $e->getMessage(), 0, $e);
            }
            $this->markMigrationApplied($moduleName, $name);
            $count++;
            $this->logger->info("Module {$moduleName} : migration appliquée ({$name}).");
        }
        return $count;
    }

    /**
     * Met à jour un module déjà installé : exécute les migrations en attente et
     * synchronise la version en base sur celle du module.json.
     *
     * @return array{success:bool, message:string, applied:int}
     */
    public function updateModule(string $moduleName): array
    {
        $this->lastError = null;
        $configFile = $this->modulesPath . '/' . $moduleName . '/module.json';
        if (!is_file($configFile)) {
            $this->lastError = "Module introuvable : {$moduleName}.";
            return ['success' => false, 'message' => $this->lastError, 'applied' => 0];
        }
        $config = json_decode((string) file_get_contents($configFile), true) ?: [];
        $version = (string)($config['version'] ?? '1.0.0');

        try {
            $applied = $this->runPendingMigrations($moduleName);
            $this->db->execute("UPDATE modules SET version = ? WHERE name = ?", [$version, $moduleName]);
            $msg = $applied > 0
                ? "{$applied} migration(s) appliquée(s). Module mis à jour en v{$version}."
                : "Aucune migration en attente. Version synchronisée (v{$version}).";
            $this->logger->info("Module updated: {$moduleName} (v{$version}, {$applied} migrations)");
            return ['success' => true, 'message' => $msg, 'applied' => $applied];
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            $this->logger->error("Module update failed: {$moduleName}", ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage(), 'applied' => 0];
        }
    }

    /** Dernier message d'erreur d'activation/désactivation (pour l'UI). */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Activer un module — installation atomique et sécurisée.
     *
     * Étapes : schéma SQL → vérification que les tables attendues existent
     * réellement → hook install() PHP → activation en base. À la moindre
     * erreur, le module N'EST PAS activé et les tables partiellement créées
     * sont nettoyées (uninstall.sql) : on n'a jamais un module « actif » dans
     * le menu sans ses tables. Le message d'erreur est exposé via getLastError().
     */
    public function activateModule(string $moduleName): bool
    {
        $this->lastError = null;

        $modulePath = $this->modulesPath . '/' . $moduleName;
        $configFile = $modulePath . '/module.json';
        if (!is_file($configFile)) {
            $this->lastError = "Module introuvable : {$moduleName} (module.json manquant).";
            return false;
        }
        $config = json_decode((string) file_get_contents($configFile), true);
        if (!is_array($config) || empty($config['class'])) {
            $this->lastError = "module.json invalide pour {$moduleName}.";
            return false;
        }

        try {
            $mainClass = $config['class'];
            if (!class_exists($mainClass)) {
                throw new \RuntimeException("Classe principale introuvable : {$mainClass}.");
            }
            $module = new $mainClass($config);

            // 1) Schéma : création des tables.
            $this->runModuleSchema($moduleName);

            // 2) VÉRIFICATION : toutes les tables déclarées dans le SQL existent-elles ?
            $missing = $this->findMissingTables($moduleName);
            if (!empty($missing)) {
                throw new \RuntimeException(
                    "Le schéma SQL n'a pas créé toutes les tables attendues. Manquantes : "
                    . implode(', ', $missing) . '.'
                );
            }

            // 3) Hook d'installation PHP éventuel.
            if (!$module->install()) {
                throw new \RuntimeException("Le hook install() du module a échoué.");
            }

            // 4) Activation en base (seulement si tout a réussi).
            $priority = (int)($config['priority'] ?? 100);
            $version  = (string)($config['version'] ?? '1.0.0');
            $this->db->execute(
                "INSERT INTO modules (name, version, active, priority, installed_at) VALUES (?, ?, 1, ?, NOW())
                 ON DUPLICATE KEY UPDATE active = 1, version = VALUES(version), priority = VALUES(priority)",
                [$moduleName, $version, $priority]
            );

            // install.sql est une « baseline » à jour : on marque les migrations
            // existantes comme appliquées (elles ne se rejoueront pas).
            $this->baselineMigrations($moduleName);

            $this->logger->info("Module activated: {$moduleName}");
            return true;

        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            $this->logger->error("Module activation failed: {$moduleName}", ['error' => $e->getMessage()]);

            // Nettoyage : on ne laisse JAMAIS un module à moitié installé.
            try {
                $this->runModuleSqlFile($moduleName, 'uninstall.sql'); // supprime les tables partielles si fourni
            } catch (\Throwable $cleanupError) {
                $this->logger->warning("Nettoyage après échec d'activation impossible : {$moduleName}", ['error' => $cleanupError->getMessage()]);
            }
            // On s'assure que le module n'est pas marqué actif.
            try {
                $this->db->execute("UPDATE modules SET active = 0 WHERE name = ?", [$moduleName]);
            } catch (\Throwable $ignore) {}

            return false;
        }
    }

    /** Wrapper public : tables déclarées par le module mais absentes en base. */
    public function missingTables(string $moduleName): array
    {
        return $this->findMissingTables($moduleName);
    }

    /**
     * Détecte les tables déclarées (CREATE TABLE) dans le SQL d'installation du
     * module mais absentes de la base après exécution.
     *
     * @return string[] Liste des tables manquantes (vide = OK).
     */
    private function findMissingTables(string $moduleName): array
    {
        $base = $this->modulesPath . '/' . $moduleName;

        // Rassemble le SQL d'installation selon la convention présente.
        $sql = '';
        if (is_file($base . '/database/install.sql')) {
            $sql = (string) file_get_contents($base . '/database/install.sql');
        } else {
            if (is_file($base . '/schema.sql')) {
                $sql .= (string) file_get_contents($base . '/schema.sql') . "\n";
            }
            foreach (glob($base . '/schema_*.sql') ?: [] as $f) {
                $sql .= (string) file_get_contents($f) . "\n";
            }
        }
        if (trim($sql) === '') {
            return []; // pas de schéma déclaré : rien à vérifier
        }

        // Extrait les noms de tables des CREATE TABLE [IF NOT EXISTS] `name`.
        if (!preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`"]?([a-zA-Z0-9_]+)[`"]?/i', $sql, $m)) {
            return [];
        }
        $expected = array_unique($m[1]);

        $missing = [];
        foreach ($expected as $table) {
            if (!$this->tableExists($table)) {
                $missing[] = $table;
            }
        }
        return $missing;
    }

    private function tableExists(string $table): bool
    {
        try {
            $row = $this->db->queryOne(
                "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?",
                [$table]
            );
            return (bool) $row;
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * DÃ©sactiver un module
     */
    public function deactivateModule(string $moduleName): bool
    {
        $this->lastError = null;
        try {
            // Hook de désinstallation du module (logique PHP éventuelle)
            if (isset($this->loadedModules[$moduleName])) {
                $module = $this->loadedModules[$moduleName];
                $module->uninstall();
                unset($this->loadedModules[$moduleName]);
            }

            // Schéma : exécuter database/uninstall.sql si présent (suppression des tables)
            $this->runModuleSqlFile($moduleName, 'uninstall.sql');

            // Désactiver en DB
            $this->db->execute(
                "UPDATE modules SET active = 0 WHERE name = ?",
                [$moduleName]
            );

            $this->logger->info("Module deactivated: {$moduleName}");

            return true;

        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            $this->logger->error("Module deactivation failed: {$moduleName}", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Supprimer DÉFINITIVEMENT un module : exécute uninstall.sql (suppression des
     * tables), retire les lignes en base (modules + module_migrations) puis
     * supprime le dossier du module sur le disque.
     *
     * ⚠️ Destructif. Le garde « module cœur » est géré par l'appelant
     * (ModulesController) qui refuse les modules protégés avant d'appeler ici.
     */
    public function deleteModule(string $moduleName): bool
    {
        $this->lastError = null;

        // Sécurité : nom de module sain (évite tout chemin malveillant).
        if (!preg_match('/^[A-Za-z0-9_]+$/', $moduleName)) {
            $this->lastError = "Nom de module invalide.";
            return false;
        }

        try {
            // 1) Hook PHP de désinstallation si le module est chargé.
            if (isset($this->loadedModules[$moduleName])) {
                try { $this->loadedModules[$moduleName]->uninstall(); } catch (\Throwable $e) {}
                unset($this->loadedModules[$moduleName]);
            }

            // 2) Suppression des tables (uninstall.sql si fourni).
            $this->runModuleSqlFile($moduleName, 'uninstall.sql');

            // 3) Nettoyage base (état + migrations).
            $this->db->execute("DELETE FROM modules WHERE name = ?", [$moduleName]);
            try {
                $this->ensureMigrationsTable();
                $this->db->execute("DELETE FROM module_migrations WHERE module = ?", [$moduleName]);
            } catch (\Throwable $e) { /* table absente : ignore */ }

            // 4) Suppression du dossier sur le disque.
            $dir = $this->modulesPath . '/' . $moduleName;
            $real = realpath($dir);
            $realModules = realpath($this->modulesPath);
            if ($real !== false && $realModules !== false && str_starts_with($real, $realModules)) {
                $this->rrmdirModule($real);
            }

            $this->logger->info("Module deleted: {$moduleName}");
            return true;

        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            $this->logger->error("Module deletion failed: {$moduleName}", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /** Suppression récursive d'un dossier de module. */
    private function rrmdirModule(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->rrmdirModule($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    /**
     * Obtenir module chargÃ©
     */
    public function getModule(string $moduleName): ?ModuleInterface
    {
        return $this->loadedModules[$moduleName] ?? null;
    }
    
    /**
     * Obtenir tous les modules chargÃ©s
     */
    public function getLoadedModules(): array
    {
        return $this->loadedModules;
    }
    
    /**
     * Obtenir version du CMS
     */
    private function getCMSVersion(): string
    {
        // Source unique de vérité : la version courante du changelog du framework.
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $cached = '4.0.0';
        $file = (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2)) . '/framework/changelog.json';
        if (is_file($file)) {
            $data = json_decode((string)@file_get_contents($file), true);
            if (is_array($data) && !empty($data['version'])) {
                // On compare sur le CŒUR numérique uniquement (ex. « 4.0.0-alpha.10 »
                // → « 4.0.0 ») : sinon version_compare jugerait une pré-version
                // INFÉRIEURE à 4.0.0 et les modules exigeant « >= 4.0.0 » ne
                // pourraient plus s'activer pendant la phase alpha/beta.
                $cached = preg_replace('/[-+].*$/', '', (string)$data['version']) ?: '4.0.0';
            }
        }
        return $cached;
    }
}