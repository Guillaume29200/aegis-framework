<?php
declare(strict_types=1);

namespace System\Controllers;

use Framework\Services\Database;
use Framework\Security\CSRFProtection;
use Framework\ModuleManager\ModuleManager;

/**
 * ModulesController - Gestion des modules système
 */
class ModulesController
{
    private Database $db;
    private CSRFProtection $csrf;
    private ModuleManager $moduleManager;
    private const PROTECTED_MODULES = ['Auth', 'Configuration', 'System'];

    public function __construct(Database $db, CSRFProtection $csrf, ModuleManager $moduleManager)
    {
        $this->db = $db;
        $this->csrf = $csrf;
        $this->moduleManager = $moduleManager;
    }

    /**
     * Liste des modules
     */
    public function index(): void
    {
        $availableModules = $this->moduleManager->discoverModules();
        $loadedModules = $this->moduleManager->getLoadedModules();
        $modulesStatus = $this->getModulesStatus();

        $modules = [];
        foreach ($availableModules as $moduleName => $config) {
            $isActive = isset($modulesStatus[$moduleName]) && $modulesStatus[$moduleName]['active'] === 1;
            $isLoaded = isset($loadedModules[$moduleName]);

            $modules[] = [
                'name' => $moduleName,
                'display_name' => $config['name'] ?? $moduleName,
                'version' => $config['version'] ?? '1.0.0',
                'description' => $config['description'] ?? 'Aucune description',
                'author' => $config['author'] ?? 'Inconnu',
                'enabled' => $config['enabled'] ?? true,
                'active' => $isActive,
                'loaded' => $isLoaded,
                'dependencies' => $config['dependencies'] ?? [],
                'has_admin_menu' => isset($config['menu']) || isset($config['admin_menu']),
                'icon_path' => $this->getModuleIconPath($moduleName),
                'has_routes' => $config['routes'] ?? false,
                'is_protected' => $this->isProtectedModule($moduleName),
            ];
        }

        $stats = [
            'total' => count($modules),
            'active' => count(array_filter($modules, fn($m) => $m['active'])),
            'inactive' => count(array_filter($modules, fn($m) => !$m['active'])),
            'loaded' => count(array_filter($modules, fn($m) => $m['loaded'])),
        ];

        $csrfToken = $this->csrf->generateToken();

        require __DIR__ . '/../Views/admin/modules/index.php';
    }

    /**
     * Activer/Désactiver un module
     */
    public function toggle(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            exit;
        }

        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
            exit;
        }

        $moduleName = $_POST['module'] ?? '';
        $action = $_POST['action'] ?? ''; // 'activate' ou 'deactivate'

        if (empty($moduleName)) {
            echo json_encode(['success' => false, 'message' => 'Module non spécifié']);
            exit;
        }

        if (!in_array($action, ['activate', 'deactivate'], true)) {
            echo json_encode(['success' => false, 'message' => 'Action invalide']);
            exit;
        }

        if ($action === 'deactivate' && $this->isProtectedModule((string)$moduleName)) {
            echo json_encode([
                'success' => false,
                'message' => 'Ce module est protégé et ne peut pas être désactivé.',
            ]);
            exit;
        }

        try {
            if ($action === 'activate') {
                $result = $this->moduleManager->activateModule($moduleName);
                $message = $result ? 'Module activé avec succès' : "Erreur lors de l'activation";
            } else {
                $result = $this->moduleManager->deactivateModule($moduleName);
                $message = $result ? 'Module désactivé avec succès' : 'Erreur lors de la désactivation';
            }

            echo json_encode(['success' => $result, 'message' => $message]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
        }

        exit;
    }

    /**
     * Récupérer les infos d'un module (modal)
     */
    public function info(): void
    {
        header('Content-Type: application/json');

        $moduleName = $_GET['module'] ?? '';

        if (empty($moduleName) || !preg_match('/^[A-Za-z0-9_-]+$/', (string)$moduleName)) {
            echo json_encode(['success' => false, 'message' => 'Module non spécifié']);
            exit;
        }

        $modulePath = ROOT_PATH . '/modules/' . $moduleName;

        $configFile = $modulePath . '/module.json';
        if (!file_exists($configFile)) {
            echo json_encode(['success' => false, 'message' => 'Module introuvable']);
            exit;
        }

        $config = json_decode(file_get_contents($configFile), true);
        if (!is_array($config)) {
            echo json_encode(['success' => false, 'message' => 'Configuration module invalide']);
            exit;
        }

        $changelogFile = $modulePath . '/changelog.json';
        $changelog = [];
        if (file_exists($changelogFile)) {
            $changelog = json_decode(file_get_contents($changelogFile), true) ?? [];
        }

        echo json_encode([
            'success' => true,
            'module' => [
                'name' => $config['name'] ?? $moduleName,
                'version' => $config['version'] ?? '1.0.0',
                'description' => $config['description'] ?? '',
                'author' => $config['author'] ?? 'Inconnu',
                'dependencies' => $config['dependencies'] ?? [],
                'protected' => $this->isProtectedModule((string)$moduleName),
                'changelog' => $changelog,
            ],
        ]);

        exit;
    }

    /**
     * Récupérer le statut des modules depuis la DB
     */
    private function getModulesStatus(): array
    {
        $result = [];
        try {
            $rows = $this->db->query("SELECT name, active, priority FROM modules");
            foreach ($rows as $row) {
                $result[$row['name']] = [
                    'active' => (int) $row['active'],
                    'priority' => (int) $row['priority'],
                ];
            }
        } catch (\Exception $e) {
            // Table peut ne pas exister encore
        }
        return $result;
    }

    /**
     * Obtenir le chemin de l'icône d'un module
     */
    private function getModuleIconPath(string $moduleName): string
    {
        $possiblePaths = [
            "/modules/{$moduleName}/assets/icon.png",
            "/modules/{$moduleName}/assets/icon.jpg",
            "/modules/{$moduleName}/assets/icon.svg",
        ];
        foreach ($possiblePaths as $path) {
            if (file_exists(ROOT_PATH . $path)) {
                return $path;
            }
        }
        return '/framework/assets/images/default-module-icon.png';
    }

    private function isProtectedModule(string $moduleName): bool
    {
        // Protégé si déclaré "core" dans son module.json (Auth, Configuration, System…)
        $configFile = __DIR__ . '/../../' . $moduleName . '/module.json';
        if (is_file($configFile)) {
            $cfg = json_decode((string)file_get_contents($configFile), true);
            if (is_array($cfg) && !empty($cfg['core'])) {
                return true;
            }
        }
        return in_array(strtolower($moduleName), array_map('strtolower', self::PROTECTED_MODULES), true);
    }
}
