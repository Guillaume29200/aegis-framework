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
        // mysqlnd autorise plusieurs requêtes dans un seul exec()
        $this->db->getPDO()->exec($sql);
        $this->logger->info("Module SQL exécuté : {$moduleName}/database/{$file}");
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

    /**
     * Activer un module
     */
    public function activateModule(string $moduleName): bool
    {
        try {
            // Charger module temporairement
            $modulePath = $this->modulesPath . '/' . $moduleName;
            $config = json_decode(file_get_contents($modulePath . '/module.json'), true);
            $mainClass = $config['class'];
            $module = new $mainClass($config);

            // 1) Schéma : install.sql OU schema.sql + schema_*.sql (création des tables)
            $this->runModuleSchema($moduleName);

            // 2) Hook d'installation du module (logique PHP éventuelle)
            if (!$module->install()) {
                throw new \Exception("Module installation failed");
            }

            // 3) Activer en DB (priorité depuis le manifest si fournie)
            $priority = (int)($config['priority'] ?? 100);
            $version  = (string)($config['version'] ?? '1.0.0');
            $this->db->execute(
                "INSERT INTO modules (name, version, active, priority, installed_at) VALUES (?, ?, 1, ?, NOW())
                 ON DUPLICATE KEY UPDATE active = 1, version = VALUES(version), priority = VALUES(priority)",
                [$moduleName, $version, $priority]
            );

            $this->logger->info("Module activated: {$moduleName}");
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error("Module activation failed: {$moduleName}", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * DÃ©sactiver un module
     */
    public function deactivateModule(string $moduleName): bool
    {
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
            
        } catch (\Exception $e) {
            $this->logger->error("Module deactivation failed: {$moduleName}", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
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
        return '4.0.0'; // TODO: Charger depuis config
    }
}