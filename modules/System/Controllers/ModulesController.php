<?php
declare(strict_types=1);

namespace System\Controllers;

use Framework\Services\Database;
use Framework\Security\CSRFProtection;
use Framework\ModuleManager\ModuleManager;
use Framework\Services\PublicPrefix;
use System\Services\ModuleInstallerService;

/**
 * ModulesController - Gestion des modules système
 */
class ModulesController
{
    private Database $db;
    private CSRFProtection $csrf;
    private ModuleManager $moduleManager;
    private const PROTECTED_MODULES = ['Auth', 'Configuration', 'System'];
    private const CATEGORY_INACTIVE = '⏸️ Non actif ou pas encore installé';

    public function __construct(Database $db, CSRFProtection $csrf, ModuleManager $moduleManager)
    {
        $this->db = $db;
        $this->csrf = $csrf;
        $this->moduleManager = $moduleManager;
    }

    /**
     * activate/deactivate exécutent respectivement install.sql/uninstall.sql
     * (DROP TABLE inclus) et delete() supprime dossier + tables : réservé à
     * admin/superadmin, jamais aux modérateurs (cf. upload(), déjà restreint).
     */
    private function requireAdmin(): void
    {
        if (empty($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'], true)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Accès réservé aux administrateurs.']);
            exit;
        }
    }

    /** Notifie tous les administrateurs (cloche in-app) d'un événement module. */
    private function notifyAdmins(string $title, string $body = '', string $icon = '🧩'): void
    {
        try {
            $admins = $this->db->query("SELECT id FROM users WHERE role IN ('admin','superadmin')");
            foreach ($admins as $a) {
                \Framework\Services\NotificationService::notify(
                    (int)$a['id'], 'module', $title, $body, '/admin/modules', $icon
                );
            }
        } catch (\Throwable $e) { /* non bloquant */ }
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
            $isProtected = $this->isProtectedModule($moduleName);
            $isInstalled = isset($modulesStatus[$moduleName]); // ligne présente en base

            // Catégorie : champ "category" du module.json ; sinon « Système » pour
            // les modules cœur, « Autres » par défaut.
            $category = trim((string)($config['category'] ?? ''));
            if ($category === '') {
                $category = $isProtected ? 'Système' : 'Autres';
            }

            // La catégorie réelle est CONSERVÉE même quand le module est
            // inactif : elle était auparavant écrasée par un libellé fourre-tout,
            // si bien qu'on ne savait plus de quoi il s'agissait. La séparation
            // se fait par un drapeau, pas en détruisant l'information.

            $modules[] = [
                'name' => $moduleName,
                'display_name' => $config['name'] ?? $moduleName,
                'version' => $config['version'] ?? '1.0.0',
                'description' => $config['description'] ?? 'Aucune description',
                'author' => $config['author'] ?? 'Inconnu',
                'enabled' => $config['enabled'] ?? true,
                'active' => $isActive,
                'loaded' => $isLoaded,
                'category' => $category,
                'installed' => $isInstalled,
                'dependencies' => $config['dependencies'] ?? [],
                'has_admin_menu' => isset($config['menu']) || isset($config['admin_menu']),
                'icon_path' => $this->getModuleIconPath($moduleName),
                'has_routes' => $config['routes'] ?? false,
                'is_protected' => $isProtected,
                'is_core'      => $isProtected,
                'dashboard_url' => $this->moduleDashboardUrl($config),
                // Un module qui expose un espace visiteur déclare son préfixe
                // dans son module.json : c'est ce qui fait apparaître le bouton.
                'public_canonical' => PublicPrefix::normalize((string) ($config['public']['prefix'] ?? '')),
                'public_prefix'    => PublicPrefix::effective($moduleName),
                'public_label'     => (string) ($config['public']['label'] ?? 'Site public'),
                'public_hint'      => (string) ($config['public']['hint'] ?? ''),
            ];
        }

        // Deux tableaux distincts, plutôt qu'un seul mêlant tout : ce qui
        // tourne et ce qui dort ne se lisent pas de la même façon, et on ne
        // cherche pas la même chose dans les deux.
        $activeModules   = array_values(array_filter($modules, fn($m) => $m['active']));
        $inactiveModules = array_values(array_filter($modules, fn($m) => !$m['active']));

        // Tri commun : les modules cœur d'abord — ce sont eux qui portent le
        // reste — puis par catégorie, puis par nom.
        $ordre = function (array $a, array $b): int {
            if ($a['is_core'] !== $b['is_core']) { return $a['is_core'] ? -1 : 1; }

            $ca = $a['category'];
            $cb = $b['category'];
            if ($ca !== $cb) {
                // « Système » ouvre la marche, « Autres » ferme.
                if ($ca === 'Système') { return -1; }
                if ($cb === 'Système') { return 1; }
                if ($ca === 'Autres')  { return 1; }
                if ($cb === 'Autres')  { return -1; }
                return strcasecmp($ca, $cb);
            }
            return strcasecmp($a['display_name'], $b['display_name']);
        };

        usort($activeModules, $ordre);
        usort($inactiveModules, $ordre);

        // La liste des catégories présentes alimente le filtre du tableau.
        $allCategories = array_values(array_unique(array_column($modules, 'category')));
        sort($allCategories, SORT_NATURAL | SORT_FLAG_CASE);

        $stats = [
            'total'    => count($modules),
            'active'   => count($activeModules),
            'inactive' => count($inactiveModules),
            'loaded'   => count(array_filter($modules, fn($m) => $m['loaded'])),
            'core'     => count(array_filter($modules, fn($m) => $m['is_core'])),
        ];

        $csrfToken = $this->csrf->generateToken();

        require __DIR__ . '/../Views/admin/modules/index.php';
    }

    /**
     * Supprimer définitivement un module (dossier + tables). Refuse les modules cœur.
     */
    public function delete(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            exit;
        }
        $this->requireAdmin();
        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
            exit;
        }

        $moduleName = (string)($_POST['module'] ?? '');
        if ($moduleName === '' || !preg_match('/^[A-Za-z0-9_]+$/', $moduleName)) {
            echo json_encode(['success' => false, 'message' => 'Module non spécifié.']);
            exit;
        }
        if ($this->isProtectedModule($moduleName)) {
            echo json_encode(['success' => false, 'message' => 'Ce module est un module cœur et ne peut pas être supprimé.']);
            exit;
        }

        $ok = $this->moduleManager->deleteModule($moduleName);
        $warning = $this->moduleManager->getLastWarning();
        if ($ok) {
            \Framework\Services\AuditService::record(
                'module.delete',
                'module',
                $moduleName,
                $warning ? "Module supprimé (tables NON supprimées : {$warning})" : 'Module supprimé (dossier + tables)'
            );
            $this->notifyAdmins(
                'Module supprimé',
                $warning
                    ? "Le module « {$moduleName} » a été supprimé, mais ses tables n'ont pas pu être nettoyées automatiquement (fichier uninstall.sql introuvable). Nettoyage manuel requis."
                    : "Le module « {$moduleName} » a été supprimé (dossier + tables).",
                $warning ? '⚠️' : '🗑️'
            );
        }
        echo json_encode([
            'success' => $ok,
            'warning' => $warning,
            'message' => !$ok
                ? 'Échec de la suppression : ' . ($this->moduleManager->getLastError() ?? 'inconnue')
                : ($warning
                    ? "Module « {$moduleName} » supprimé, mais ses tables n'ont PAS été supprimées : {$warning}"
                    : "Module « {$moduleName} » supprimé (dossier et tables)."),
        ]);
        exit;
    }

    /**
     * Installer un module depuis une archive ZIP uploadée.
     */
    public function upload(): void
    {
        if (empty($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'], true)) {
            redirect('/auth/login');
        }
        try {
            $this->csrf->validateToken($_POST['csrf_token'] ?? '');
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Token CSRF invalide.';
            redirect('/admin/modules');
        }

        $service = new ModuleInstallerService();
        $result = $service->installFromUpload($_FILES['module_zip'] ?? []);
        $_SESSION[$result['success'] ? 'success' : 'error'] = ($result['success'] ? '✅ ' : '❌ ') . $result['message'];
        redirect('/admin/modules');
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
        $this->requireAdmin();

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
                $err = $this->moduleManager->getLastError();
                $message = $result
                    ? 'Module activé avec succès'
                    : ("Installation bloquée : " . ($err ?: "erreur inconnue") . " — le module n'a pas été activé.");
            } else {
                $result = $this->moduleManager->deactivateModule($moduleName);
                $err = $this->moduleManager->getLastError();
                $warn = $this->moduleManager->getLastWarning();
                $message = !$result
                    ? ('Erreur lors de la désactivation : ' . ($err ?: 'inconnue'))
                    : ($warn ? "Module désactivé, mais ses tables n'ont PAS été supprimées : {$warn}" : 'Module désactivé avec succès');
            }

            if ($result) {
                $verb = $action === 'activate' ? 'activé' : 'désactivé';
                \Framework\Services\AuditService::record(
                    'module.' . ($action === 'activate' ? 'activate' : 'deactivate'),
                    'module', $moduleName, 'Module ' . $verb
                );
                $this->notifyAdmins('Module ' . $verb, "Le module « {$moduleName} » a été {$verb}.",
                    $action === 'activate' ? '✅' : '⏸️');
            }
            echo json_encode(['success' => $result, 'message' => $message]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
        }

        exit;
    }

    /**
     * Changer le préfixe public d'un module.
     *
     * Ne concerne que la partie visiteur : l'adresse d'administration reste
     * /admin/{module} en toutes circonstances.
     */
    public function savePrefix(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            exit;
        }
        $this->requireAdmin();

        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
            exit;
        }

        $moduleName = trim((string) ($_POST['module'] ?? ''));
        $candidate  = trim((string) ($_POST['prefix'] ?? ''));

        $discovered = $this->moduleManager->discoverModules();
        $canonical  = PublicPrefix::normalize(
            (string) ($discovered[$moduleName]['public']['prefix'] ?? '')
        );

        if ($canonical === '') {
            echo json_encode([
                'success' => false,
                'message' => 'Ce module n\'expose pas d\'espace visiteur.',
            ]);
            exit;
        }

        // Préfixes déjà pris : le canonique de chaque module, ou celui qu'on
        // lui a substitué. Un module ne peut pas marcher sur les plates-bandes
        // d'un autre, même inactif — il pourrait être réactivé demain.
        $taken = [];
        foreach ($discovered as $name => $config) {
            $other = PublicPrefix::normalize((string) ($config['public']['prefix'] ?? ''));
            if ($other === '') { continue; }
            $taken[$name] = PublicPrefix::effective($name) ?: $other;
        }

        if (($error = PublicPrefix::validate($candidate, $moduleName, $taken)) !== null) {
            echo json_encode(['success' => false, 'message' => $error]);
            exit;
        }

        // Revenir au préfixe canonique se stocke comme une absence de choix :
        // le module suivra alors son manifeste si celui-ci change.
        $normalized = PublicPrefix::normalize($candidate);
        $stored     = $normalized === $canonical ? '' : $normalized;

        try {
            $this->db->query(
                'UPDATE modules SET public_prefix = ? WHERE name = ?',
                [$stored, $moduleName]
            );
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Enregistrement impossible : ' . $e->getMessage()]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'prefix'  => $normalized,
            'url'     => rtrim(BASE_URL, '/') . '/' . $normalized,
            'message' => sprintf('Le site est désormais accessible sur /%s.', $normalized),
        ]);
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
            $raw = json_decode(file_get_contents($changelogFile), true) ?? [];
            $changelog = $this->normalizeChangelog($raw);
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
     * Normalise un changelog vers une liste [{version,date,changes[]}], quel que
     * soit le format source : liste, {"versions":[...]}, ou {"1.2.0":{...}}.
     */
    private function normalizeChangelog($raw): array
    {
        if (!is_array($raw) || empty($raw)) {
            return [];
        }
        // Format { "versions": [...] }
        if (isset($raw['versions']) && is_array($raw['versions'])) {
            return array_values($raw['versions']);
        }
        // Format liste [ {version, ...}, ... ]
        if (array_is_list($raw)) {
            return $raw;
        }
        // Format objet indexé par version { "1.2.0": {date,changes}, ... }
        $out = [];
        foreach ($raw as $version => $entry) {
            if (is_array($entry)) {
                $out[] = ['version' => (string)$version] + $entry;
            }
        }
        return $out;
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

    /**
     * URL du tableau de bord d'un module, déduite de son menu (module.json) :
     * 1re entrée de menu → son 1er enfant (dashboard), sinon son url/match.
     */
    private function moduleDashboardUrl(array $config): ?string
    {
        $menu = $config['menu'] ?? $config['admin_menu'] ?? null;
        if (!is_array($menu) || empty($menu)) { return null; }
        $first = $menu[0] ?? null;
        if (!is_array($first)) { return null; }
        if (!empty($first['children'][0]['url'])) { return (string) $first['children'][0]['url']; }
        if (!empty($first['url']))   { return (string) $first['url']; }
        if (!empty($first['match'])) { return (string) $first['match']; }
        return null;
    }
}
