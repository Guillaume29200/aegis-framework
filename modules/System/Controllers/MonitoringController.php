<?php
/**
 * Contrôleur Monitoring V2 - Aegis Framework V4
 * VERSION ULTRA COMPLÃˆTE
 */

namespace System\Controllers;

use System\Services\MonitoringService;
use Framework\Services\Database;
use Framework\Security\CSRFProtection;

class MonitoringController
{
    private $db;
    private ?CSRFProtection $csrf;
    
    public function __construct(?Database $db = null, ?CSRFProtection $csrf = null)
    {
        $this->db = $db;
        $this->csrf = $csrf;
        $this->requireAdmin();
    }

    /**
     * Le monitoring expose des infos serveur/BDD sensibles et permet de
     * supprimer/purger des logs (traces d'audit) : réservé à admin/superadmin,
     * comme SecurityController/DiagnosticController — pas les modérateurs.
     */
    /**
     * Le dossier des journaux du framework.
     *
     * Écrit `__DIR__ . '/../logs'` à l'origine, quand le contrôleur vivait dans
     * `framework/Controllers`. Il a déménagé dans `modules/System/Controllers`
     * sans que le chemin suive : il désignait `modules/System/logs`, dossier
     * inexistant. « Voir » et « Supprimer » répondaient donc « Fichier
     * introuvable » sur tous les journaux, et le résumé affichait 0 fichier.
     */
    private function cheminDesLogs(): string
    {
        $racine = defined('ROOT_PATH') ? rtrim(ROOT_PATH, "/\\") : dirname(__DIR__, 3);

        return $racine . '/framework/logs';
    }

    private function requireAdmin(): void
    {
        if (empty($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'], true)) {
            redirect('/auth/login');
        }
    }

    protected function render(string $view, array $data = []): void
    {
        $viewPath = __DIR__ . '/../Views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            throw new \Exception("Vue introuvable : $viewPath");
        }

        (static function (string $__path, array $__data): void {
            extract($__data, EXTR_SKIP);
            require $__path;
        })($viewPath, $data);
    }

    /**
     * Page principale de monitoring avec TOUS les onglets
     */
    public function index(): void
    {
        // Récupérer TOUTES les infos (V2)
        $data = MonitoringService::getAllInfo($this->db);
        
        // Lister le contenu FTP
        $rootDir = $_SERVER['DOCUMENT_ROOT'];
        $dirContents = MonitoringService::listDirectory($rootDir);
        
        // Préparer les données pour la vue
        $viewData = [
            'csrfToken' => $this->csrf ? $this->csrf->generateToken() : '',
            'pageTitle' => 'Monitoring',

            // Systeme / runtime
            'serverType' => $data['server_type'] ?? 'Unknown',
            'os' => $data['os'] ?? 'Unknown',
            'phpVersion' => $data['php_version'] ?? PHP_VERSION,
            'cmsVersion' => $data['cms_version'] ?? 'N/A',
            'hardware' => $data['hardware'] ?? [],
            'requirements' => $data['requirements'] ?? [],
            'runtime' => $data['runtime'] ?? [],
            'serverName' => $data['server_name'] ?? ($_SERVER['SERVER_NAME'] ?? 'Unknown'),
            'serverAddr' => $data['server_addr'] ?? ($_SERVER['SERVER_ADDR'] ?? 'Unknown'),

            // Sante globale
            'health' => $data['health'] ?? ['score' => 0, 'level' => 'warning', 'critical' => [], 'warnings' => []],
            'errors' => $data['errors'] ?? [],
            'warnings' => $data['warnings'] ?? [],

            // Sections detaillees
            'database' => $data['database'] ?? null,
            'filesInfo' => $data['files'] ?? [],
            'performance' => $data['performance'] ?? [],
            'security' => $data['security'] ?? [],
            'logs' => $data['logs'] ?? [],
            'modules' => $data['modules'] ?? ['total_modules' => 0, 'modules' => []],

            // Explorateur racine (réservé usage futur)
            'directories' => $dirContents['directories'],
            'files' => $dirContents['files'],
        ];

        $this->render('admin/monitoring/index', $viewData);
    }

    /**
     * Afficher le contenu d'un fichier log
     */
    public function viewLog(): void
    {
        $filename = $_GET['file'] ?? '';
        
        if (empty($filename)) {
            echo 'Aucun fichier spécifié';
            exit; // IMPORTANT : Empêcher le chargement du layout
        }
        
        // Sécurité : empêcher directory traversal
        $filename = basename($filename);
        
        $logsPath = $this->cheminDesLogs();
        $fullPath = $logsPath . '/' . $filename;

        if (!file_exists($fullPath) || !is_file($fullPath)) {
            echo 'Fichier introuvable: ' . realpath($logsPath);
            exit; // IMPORTANT : Empêcher le chargement du layout
        }
        
        $logContent = file_get_contents($fullPath);
        
        if (empty($logContent)) {
            echo 'Fichier vide';
        } else {
            // Limiter à 50 000 caractères pour éviter de saturer le navigateur
            if (strlen($logContent) > 50000) {
                echo "=== Affichage des 50 000 derniers caractères ===\n\n";
                echo substr($logContent, -50000);
            } else {
                echo $logContent;
            }
        }
        exit; // IMPORTANT : Empêcher le chargement du layout
    }
    
    /**
     * Supprimer un fichier log
     */
    public function deleteLog(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Methode non autorisee']);
            exit;
        }

        if ($this->csrf) {
            try {
                $this->csrf->validateToken($_POST['csrf_token'] ?? '');
            } catch (\Throwable $e) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
                exit;
            }
        }
        
        $filename = $_POST['file'] ?? '';
        
        if (empty($filename)) {
            echo json_encode(['success' => false, 'message' => 'Aucun fichier spécifié']);
            exit; // IMPORTANT : Empêcher le chargement du layout
        }
        
        // Sécurité : empêcher directory traversal
        $filename = basename($filename);
        
        $logsPath = $this->cheminDesLogs();
        $fullPath = $logsPath . '/' . $filename;
        
        if (!file_exists($fullPath) || !is_file($fullPath)) {
            echo json_encode(['success' => false, 'message' => 'Fichier introuvable']);
            exit; // IMPORTANT : Empêcher le chargement du layout
        }
        
        if (@unlink($fullPath)) {
            echo json_encode(['success' => true, 'message' => 'Log supprimé avec succès']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Impossible de supprimer le log']);
        }
        exit; // IMPORTANT : Empêcher le chargement du layout
    }
    /**
     * Supprimer une ligne de la table logs.
     */
    public function deleteDatabaseLog(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Methode non autorisee']);
            exit;
        }

        if ($this->csrf) {
            try {
                $this->csrf->validateToken($_POST['csrf_token'] ?? '');
            } catch (\Throwable $e) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
                exit;
            }
        }

        $logId = (int)($_POST['log_id'] ?? 0);
        if ($logId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Log SQL invalide']);
            exit;
        }

        $result = MonitoringService::deleteDatabaseLog($this->db, $logId);
        if (!$result['success']) {
            http_response_code(400);
        }

        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Vider entierement la table logs.
     */
    public function purgeDatabaseLogs(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Methode non autorisee']);
            exit;
        }

        if ($this->csrf) {
            try {
                $this->csrf->validateToken($_POST['csrf_token'] ?? '');
            } catch (\Throwable $e) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
                exit;
            }
        }

        $confirmation = (string)($_POST['confirmation'] ?? '');
        if ($confirmation !== 'PURGE_SQL_LOGS') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Confirmation de purge invalide']);
            exit;
        }

        $result = MonitoringService::purgeDatabaseLogs($this->db);
        if (!$result['success']) {
            http_response_code(400);
        }

        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Vider le fichier d'erreurs de PHP.
     *
     * Le bloc « Dernières erreurs PHP » était en lecture seule : une fois le
     * défaut corrigé, la trace restait affichée sans moyen de la retirer.
     *
     * Le fichier est tronqué, jamais supprimé : PHP y écrit via un descripteur
     * ouvert au démarrage, et effacer l'entrée du répertoire lui ferait perdre
     * sa destination jusqu'au redémarrage du serveur.
     */
    public function purgePhpErrorLog(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Methode non autorisee']);
            exit;
        }

        if ($this->csrf) {
            try {
                $this->csrf->validateToken($_POST['csrf_token'] ?? '');
            } catch (\Throwable $e) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
                exit;
            }
        }

        if ((string)($_POST['confirmation'] ?? '') !== 'PURGE_PHP_LOG') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Confirmation de purge invalide']);
            exit;
        }

        $result = MonitoringService::purgePhpErrorLog();
        if (!$result['success']) {
            http_response_code(400);
        }

        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
