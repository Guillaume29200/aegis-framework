<?php
declare(strict_types=1);

namespace Auth\Controllers;

use Auth\Services\AuthService;
use Framework\Services\Database;
use Framework\Services\Logger;
use Framework\Security\CSRFProtection;
use GameServerHub\Controllers\GamePanel\Docker\DockerRuntimeService;
use GameServerHub\Services\DedicatedServerService;
use GameServerHub\Services\FTPService;
use GameServerHub\Services\SSHService;

/**
 * Contrôleur Admin
 * 
 * Gère le dashboard administrateur et la gestion des utilisateurs
 */
class AdminController
{
    private AuthService $authService;
    private Database $db;
    private CSRFProtection $csrf;
    private Logger $logger;
    
    public function __construct(Database $db, CSRFProtection $csrf, ?Logger $logger = null)
    {
        $this->db = $db;
        $this->csrf = $csrf;
        $this->logger = $logger ?? new Logger($db, $GLOBALS['config'] ?? []);
        $this->authService = new AuthService($db);
        
        // Vérifier authentification et rôle admin
        if (!$this->authService->isLoggedIn() || !$this->authService->isAdmin()) {
            header('Location: ' . u('/auth/login'));
            exit;
        }
    }
    
    /**
     * Dashboard admin
     */
    public function dashboard(): void
    {
        $stats = $this->getStats();
        $currentUser = $this->authService->getUserById($_SESSION['user_id']);
        require __DIR__ . '/../Views/admin/dashboard.php';
    }
    
    /**
     * Liste des utilisateurs
     */
    public function users(): void
    {
        $users = $this->db->query("
            SELECT id, username, email, role, status, last_login, created_at, login_count 
            FROM users 
            ORDER BY created_at DESC
        ");
        $csrfToken = $this->csrf->generateToken();
        require __DIR__ . '/../Views/admin/users.php';
    }
    
    /**
     * Afficher le formulaire de création d'utilisateur
     */
    public function showCreateUser(): void
    {
        $csrfToken = $this->csrf->generateToken();
        require __DIR__ . '/../Views/admin/users-create.php';
    }
    
    /**
     * Traiter la création d'utilisateur
     */
    public function createUser(): void
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
            exit;
        }
        
        try {
            if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Token CSRF invalide']);
                exit;
            }
        } catch (\Exception $e) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Erreur CSRF : ' . $e->getMessage()]);
            exit;
        }
        
        $username        = trim($_POST['username']         ?? '');
        $email           = trim($_POST['email']            ?? '');
        $password        = $_POST['password']              ?? '';
        $passwordConfirm = $_POST['password_confirm']      ?? '';
        $firstName       = trim($_POST['first_name']       ?? '');
        $lastName        = trim($_POST['last_name']        ?? '');
        $role            = $_POST['role']                  ?? 'member';
        $status          = $_POST['status']                ?? 'active';
        $sendEmail       = isset($_POST['send_email']);
        
        $errors = [];
        
        if (empty($username)) {
            $errors['username'] = 'Le nom d\'utilisateur est requis';
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $errors['username'] = 'Le nom d\'utilisateur doit contenir entre 3 et 50 caractères';
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            $errors['username'] = 'Le nom d\'utilisateur ne peut contenir que des lettres, chiffres, - et _';
        } elseif ($this->authService->usernameExists($username)) {
            $errors['username'] = 'Ce nom d\'utilisateur est déjà utilisé';
        }
        
        if (empty($email)) {
            $errors['email'] = 'L\'email est requis';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'L\'email n\'est pas valide';
        } elseif ($this->authService->emailExists($email)) {
            $errors['email'] = 'Cet email est déjà utilisé';
        }
        
        if (empty($password)) {
            $errors['password'] = 'Le mot de passe est requis';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères';
        }
        
        if ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Les mots de passe ne correspondent pas';
        }
        
        if (!in_array($role, ['member', 'moderator', 'admin', 'superadmin'])) {
            $errors['role'] = 'Rôle invalide';
        }
        
        if (!in_array($status, ['active', 'inactive', 'banned'])) {
            $errors['status'] = 'Statut invalide';
        }
        
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }
        
        $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);
        
        try {
            $userId = $this->db->insert('users', [
                'username'   => $username,
                'email'      => $email,
                'password'   => $hashedPassword,
                'first_name' => $firstName ?: null,
                'last_name'  => $lastName  ?: null,
                'role'       => $role,
                'status'     => $status,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            
            if (!$userId) {
                throw new \Exception('Erreur lors de la création de l\'utilisateur');
            }
            
            $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
            $basePath   = ($scriptName === '/' || $scriptName === '') ? '' : $scriptName;
            
            echo json_encode([
                'success' => true,
                'message' => 'Utilisateur créé avec succès !',
                'redirect' => $basePath . '/admin/users',
                'user_id' => $userId,
            ]);
            exit;
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la création : ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Afficher détails utilisateur
     */
    public function showUser(int $id): void
    {
        $user = $this->authService->getUserById($id);
        
        if (!$user) {
            http_response_code(404);
            echo "Utilisateur non trouvé";
            return;
        }
        
        $authTracker = new \Framework\Services\AuthTracker($this->db);
        $logins           = $authTracker->getUserLogins($id, 100);
        $registrationData = $authTracker->getRegistrationData($id);
        $lastLogin        = $authTracker->getLastLogin($id);
        
        $mapData = [];
        if ($lastLogin && isset($lastLogin['latitude']) && isset($lastLogin['longitude']) && $lastLogin['latitude'] && $lastLogin['longitude']) {
            $mapData = [
                'lat'     => (float)$lastLogin['latitude'],
                'lng'     => (float)$lastLogin['longitude'],
                'city'    => $lastLogin['city']         ?? 'Inconnu',
                'country' => $lastLogin['country_name'] ?? 'Inconnu',
                'ip'      => $lastLogin['ip_address']   ?? 'N/A',
                'isp'     => $lastLogin['isp']          ?? 'Inconnu',
            ];
        }
        
        $totalLogins = count($logins);
        $uniqueIPs   = count(array_unique(array_filter(array_column($logins, 'ip_address'))));
        $devices     = array_count_values(array_filter(array_column($logins, 'device_type')));
        $browsers    = array_count_values(array_filter(array_column($logins, 'browser')));
        
        $csrfToken = $this->csrf->generateToken();
        
        require __DIR__ . '/../Views/admin/users-details.php';
    }

    /**
     * Afficher le formulaire d'édition d'un utilisateur
     * GET /admin/users/{id}/edit
     */
    public function showEditUser(int $id): void
    {
        $user = $this->authService->getUserById($id);

        if (!$user) {
            http_response_code(404);
            echo "Utilisateur non trouvé";
            return;
        }

        $csrfToken = $this->csrf->generateToken();

        require __DIR__ . '/../Views/admin/users-edit.php';
    }
    
    /**
     * Mettre à jour utilisateur
     * POST /admin/users/{id}/update
     */
    public function updateUser(int $id): void
    {
        // CSRF
        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo "Token CSRF invalide";
            exit;
        }

        $user = $this->authService->getUserById($id);
        if (!$user) {
            http_response_code(404);
            echo "Utilisateur non trouvé";
            exit;
        }

        $username    = trim($_POST['username']   ?? '');
        $email       = trim($_POST['email']      ?? '');
        $firstName   = trim($_POST['first_name'] ?? '');
        $lastName    = trim($_POST['last_name']  ?? '');
        $role        = $_POST['role']            ?? 'member';
        $status      = $_POST['status']          ?? 'active';
        $newPassword = $_POST['new_password']    ?? '';

        $errors = [];

        if (empty($username)) {
            $errors[] = 'Le nom d\'utilisateur est requis';
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = 'Le nom d\'utilisateur doit contenir entre 3 et 50 caractères';
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            $errors[] = 'Nom d\'utilisateur invalide';
        } elseif (strtolower($username) !== strtolower($user['username']) && $this->authService->usernameExists($username)) {
            $errors[] = 'Ce nom d\'utilisateur est déjà utilisé';
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email invalide';
        } elseif (strtolower($email) !== strtolower($user['email']) && $this->authService->emailExists($email)) {
            $errors[] = 'Cet email est déjà utilisé';
        }

        if (!in_array($role, ['member', 'moderator', 'admin', 'superadmin'])) {
            $errors[] = 'Rôle invalide';
        }

        if (!in_array($status, ['active', 'inactive', 'banned'])) {
            $errors[] = 'Statut invalide';
        }

        if (!empty($newPassword) && strlen($newPassword) < 8) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caractères';
        }

        $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $basePath   = ($scriptName === '/' || $scriptName === '') ? '' : $scriptName;

        if (!empty($errors)) {
            header("Location: {$basePath}/admin/users/{$id}/edit?error=1");
            exit;
        }

        $fields = [
            'username'   => $username,
            'email'      => $email,
            'first_name' => $firstName ?: null,
            'last_name'  => $lastName  ?: null,
            'role'       => $role,
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (!empty($newPassword)) {
            $fields['password'] = password_hash($newPassword, PASSWORD_ARGON2ID);
        }

        $setClauses = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($fields)));
        $values     = array_values($fields);
        $values[]   = $id;

        $this->db->execute("UPDATE users SET {$setClauses} WHERE id = ?", $values);

        header("Location: {$basePath}/admin/users/{$id}?updated=1");
        exit;
    }
    
    /**
     * Supprimer utilisateur
     */
    public function deleteUser(int $id): void
    {
        $basePath = $this->basePath();

        if ($id === (int)($_SESSION['user_id'] ?? 0)) {
            header("Location: {$basePath}/admin/users?error=self_delete");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            header("Location: {$basePath}/admin/users?error=delete_failed&reason=" . urlencode('Token CSRF invalide'));
            exit;
        }
        
        try {
            $user = $this->authService->getUserById($id);
            
            if (!$user) {
                header("Location: {$basePath}/admin/users?error=not_found");
                exit;
            }

            $deletedGameServers = $this->deleteUserGameServers($id);
            
            $this->db->getPDO()->beginTransaction();
            
            $this->cleanupUserDatabaseLinks($id);
            
            $success = $this->db->execute("DELETE FROM users WHERE id = ?", [$id]);
            
            if (!$success) {
                throw new \Exception("Erreur lors de la suppression");
            }
            
            $this->db->getPDO()->commit();
            
            header("Location: {$basePath}/admin/users?deleted=1&username=" . urlencode($user['username']) . "&servers_deleted={$deletedGameServers}");
            exit;
            
        } catch (\Exception $e) {
            if ($this->db->getPDO()->inTransaction()) {
                $this->db->getPDO()->rollBack();
            }
            
            header("Location: {$basePath}/admin/users?error=delete_failed&reason=" . urlencode($e->getMessage()));
            exit;
        }
    }

    private function deleteUserGameServers(int $userId): int
    {
        if (!$this->tableExists('gsh_game_servers')) {
            return 0;
        }

        $gameServers = $this->db->query(
            "SELECT gs.*, g.name AS game_name, ds.ip_address AS server_ip
             FROM gsh_game_servers gs
             LEFT JOIN gsh_games g ON g.id = gs.game_id
             LEFT JOIN gsh_dedicated_servers ds ON ds.id = gs.dedicated_server_id
             WHERE gs.user_id = ?
             ORDER BY gs.id ASC",
            [$userId]
        );

        foreach ($gameServers as $gameServer) {
            $this->deleteGameServerCascade($gameServer);
        }

        return count($gameServers);
    }

    private function cleanupUserDatabaseLinks(int $userId): void
    {
        foreach ([
            'user_sessions',
            'remember_tokens',
            'password_reset_tokens',
            'user_logins',
            'user_registration_data',
        ] as $table) {
            $this->deleteRowsByUserIdIfTableExists($table, $userId);
        }

        if ($this->tableExists('logs')) {
            $this->db->execute('UPDATE logs SET user_id = NULL WHERE user_id = ?', [$userId]);
        }
    }

    private function deleteRowsByUserIdIfTableExists(string $table, int $userId): void
    {
        if (!preg_match('/^[a-z0-9_]+$/', $table) || !$this->tableExists($table)) {
            return;
        }

        $this->db->execute("DELETE FROM `{$table}` WHERE user_id = ?", [$userId]);
    }

    private function deleteGameServerCascade(array $gameServer): void
    {
        $gameServerId = (int)($gameServer['id'] ?? 0);
        if ($gameServerId <= 0) {
            return;
        }

        $isDockerServer = $this->isDockerRuntimeServer($gameServer);

        if ($isDockerServer) {
            try {
                $dockerRuntime = new DockerRuntimeService($this->db, $this->logger);
                $purged = $dockerRuntime->purge($gameServerId);
                $this->logger->info('Ressources Docker supprimees avec utilisateur', [
                    'game_server_id' => $gameServerId,
                    'container' => $purged['container'] ?? null,
                    'volume' => $purged['volume'] ?? null,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Suppression Docker impossible pendant suppression utilisateur', [
                    'game_server_id' => $gameServerId,
                    'user_id' => $gameServer['user_id'] ?? null,
                    'error' => $e->getMessage(),
                ]);
                throw new \RuntimeException('Suppression Docker impossible pour le serveur #' . $gameServerId . ' : ' . $e->getMessage());
            }
        } else {
            $this->stopClassicGameServerIfNeeded($gameServer);
            $this->deleteClassicFtpAccount($gameServer);
        }

        $this->cleanupGameServerDatabaseLinks($gameServerId);
        $this->db->delete('gsh_game_servers', ['id' => $gameServerId]);

        $this->logger->info('Serveur de jeu supprime avec utilisateur', [
            'game_server_id' => $gameServerId,
            'user_id' => $gameServer['user_id'] ?? null,
            'runtime' => $isDockerServer ? 'docker' : 'classic',
        ]);
    }

    private function stopClassicGameServerIfNeeded(array $gameServer): void
    {
        $gameServerId = (int)($gameServer['id'] ?? 0);
        $status = (string)($gameServer['status'] ?? '');

        if (!in_array($status, ['running', 'starting', 'stopping'], true)) {
            return;
        }

        try {
            $serverService = new DedicatedServerService($this->db);
            $dedicated = $serverService->getById((int)$gameServer['dedicated_server_id']);
            if (!$dedicated) {
                return;
            }

            $sshService = new SSHService($this->db, $this->logger);
            $password = !empty($dedicated['ssh_password'])
                ? $serverService->decryptPassword($dedicated['ssh_password'])
                : null;

            if (!$sshService->connect(
                $dedicated['ip_address'],
                (int)$dedicated['ssh_port'],
                $dedicated['ssh_user'],
                $password,
                $dedicated['ssh_key'] ?? null
            )) {
                return;
            }

            // gameServerId is an int from DB — cast again to guarantee no injection
            $safeId = (int)$gameServerId;
            $sessionName = escapeshellarg('srv' . $safeId);
            $pids = trim((string)$sshService->exec("pgrep -f {$sessionName} 2>/dev/null | tr '\n' ' '"));
            if ($pids !== '') {
                // $pids comes from pgrep — only digits and spaces; strip anything else for safety
                $pids = preg_replace('/[^0-9 ]/', '', $pids);
                if ($pids !== '') {
                    $sshService->exec("kill -15 {$pids} 2>/dev/null; true");
                    sleep(2);
                    $sshService->exec("kill -9 {$pids} 2>/dev/null; true");
                }
            }

            $sshService->exec("tmux kill-session -t {$sessionName} 2>/dev/null; true");

            $port = (int)($gameServer['port'] ?? 0);
            if ($port > 1024 && $port <= 65535) {
                $sshService->exec("fuser -k {$port}/udp 2>/dev/null; fuser -k {$port}/tcp 2>/dev/null; true");
            }

            $sshService->disconnect();
        } catch (\Throwable $e) {
            $this->logger->warning('Impossible d arreter le serveur classique pendant suppression utilisateur', [
                'game_server_id' => $gameServerId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function deleteClassicFtpAccount(array $gameServer): void
    {
        $gameServerId = (int)($gameServer['id'] ?? 0);

        try {
            $ftpService = new FTPService($this->db, $this->logger);
            $ftpService->deleteFTPAccount($gameServerId, (int)$gameServer['dedicated_server_id']);
        } catch (\Throwable $e) {
            $this->logger->warning('Suppression FTP ignoree pendant suppression utilisateur', [
                'game_server_id' => $gameServerId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function cleanupGameServerDatabaseLinks(int $gameServerId): void
    {
        if ($this->tableExists('gsh_ftp_accounts') && $this->tableExists('gsh_ftp_logs')) {
            $ftpAccounts = $this->db->query('SELECT id FROM gsh_ftp_accounts WHERE game_server_id = ?', [$gameServerId]);
            foreach ($ftpAccounts as $ftpAccount) {
                $this->db->delete('gsh_ftp_logs', ['ftp_account_id' => (int)$ftpAccount['id']]);
            }
        }

        foreach ($this->gameServerLinkedTables() as $table) {
            if ($table === 'gsh_game_servers') {
                continue;
            }

            $this->db->execute("DELETE FROM `{$table}` WHERE game_server_id = ?", [$gameServerId]);
        }
    }

    private function gameServerLinkedTables(): array
    {
        try {
            $rows = $this->db->query(
                "SELECT table_name
                 FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                   AND column_name = 'game_server_id'
                   AND table_name LIKE 'gsh\\_%'"
            );
        } catch (\Throwable) {
            return [];
        }

        $tables = [];
        foreach ($rows as $row) {
            $table = (string)($row['table_name'] ?? '');
            if (preg_match('/^gsh_[a-z0-9_]+$/', $table)) {
                $tables[] = $table;
            }
        }

        return array_values(array_unique($tables));
    }

    private function isDockerRuntimeServer(array $gameServer): bool
    {
        if (!$this->tableExists('gsh_game_versions')) {
            return false;
        }

        foreach (['game_version_id', 'version_id', 'server_version_id'] as $key) {
            if (!empty($gameServer[$key])) {
                $version = $this->db->queryOne(
                    'SELECT game_runtime_type, docker_image FROM gsh_game_versions WHERE id = ? LIMIT 1',
                    [(int)$gameServer[$key]]
                );

                if (($version['game_runtime_type'] ?? '') === 'docker' && !empty($version['docker_image'])) {
                    return true;
                }
            }
        }

        if ($this->tableExists('gsh_installations')) {
            $installation = $this->db->queryOne(
                "SELECT i.id
                 FROM gsh_installations i
                 LEFT JOIN gsh_game_versions gv ON gv.id = i.version_id
                 WHERE i.game_server_id = ?
                   AND (i.install_type = 'docker' OR gv.game_runtime_type = 'docker')
                 ORDER BY i.created_at DESC, i.id DESC
                 LIMIT 1",
                [(int)$gameServer['id']]
            );

            if (!empty($installation)) {
                return true;
            }
        }

        $version = $this->db->queryOne(
            "SELECT game_runtime_type, docker_image
             FROM gsh_game_versions
             WHERE game_id = ? AND game_runtime_type = 'docker'
             ORDER BY is_default DESC, id DESC
             LIMIT 1",
            [(int)($gameServer['game_id'] ?? 0)]
        );

        return (($version['game_runtime_type'] ?? '') === 'docker') && !empty($version['docker_image']);
    }

    private function basePath(): string
    {
        $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        return ($scriptName === '/' || $scriptName === '') ? '' : $scriptName;
    }
    
    /**
     * Statistiques admin
     */
    public function stats(): void
    {
        $stats = $this->getStats();
        header('Content-Type: application/json');
        echo json_encode($stats);
    }
    
    /**
     * Récupérer statistiques
     */
    private function getStats(): array
    {
        $result = $this->db->query("SELECT COUNT(*) as total FROM users");
        $totalUsers = $result[0]['total'] ?? 0;

        $result = $this->db->query("
            SELECT COUNT(*) as total FROM users
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $newUsers = $result[0]['total'] ?? 0;

        $result = $this->db->query("
            SELECT COUNT(*) as total FROM users
            WHERE last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $activeUsers = $result[0]['total'] ?? 0;

        $usersByRole        = $this->db->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
        $usersByStatus      = $this->db->query("SELECT status, COUNT(*) as count FROM users GROUP BY status");
        $registrationsChart = $this->db->query("
            SELECT DATE(created_at) AS day, COUNT(*) AS count
            FROM users
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ");

        $gshEnabled = $this->tableExists('gsh_dedicated_servers');

        $gsh = [
            'dedicated_online'     => 0,
            'dedicated_total'      => 0,
            'game_servers_running' => 0,
            'game_servers_total'   => 0,
            'game_servers_error'   => 0,
            'installs_running'     => 0,
            'odin_alerts'          => 0,
            'games_active'         => 0,
        ];

        if ($gshEnabled) {
            $r = $this->db->queryOne(
                "SELECT COUNT(*) as total, SUM(status = 'online') as online
                 FROM gsh_dedicated_servers"
            );
            $gsh['dedicated_total']  = (int)($r['total']  ?? 0);
            $gsh['dedicated_online'] = (int)($r['online'] ?? 0);

            if ($this->tableExists('gsh_game_servers')) {
                $r = $this->db->queryOne(
                    "SELECT COUNT(*)                     AS total,
                            SUM(status = 'running')      AS `running`,
                            SUM(status = 'stopped')      AS `stopped`,
                            SUM(status = 'error')        AS `error`,
                            SUM(status = 'installing')   AS `installing`,
                            SUM(status = 'uninstalled')  AS `uninstalled`,
                            SUM(status = 'starting')     AS `starting`,
                            SUM(status = 'stopping')     AS `stopping`
                     FROM gsh_game_servers"
                );
                $gsh['game_servers_total']       = (int)($r['total']       ?? 0);
                $gsh['game_servers_running']     = (int)($r['running']     ?? 0);
                $gsh['game_servers_stopped']     = (int)($r['stopped']     ?? 0);
                $gsh['game_servers_error']       = (int)($r['error']       ?? 0);
                $gsh['game_servers_installing']  = (int)($r['installing']  ?? 0);
                $gsh['game_servers_uninstalled'] = (int)($r['uninstalled'] ?? 0);
            }

            if ($this->tableExists('gsh_installations')) {
                $r = $this->db->queryOne(
                    "SELECT COUNT(*) as c FROM gsh_installations
                     WHERE status IN ('running', 'pending')"
                );
                $gsh['installs_running'] = (int)($r['c'] ?? 0);
            }

            if ($this->tableExists('gsh_odin_alerts')) {
                $r = $this->db->queryOne(
                    "SELECT COUNT(*) as c FROM gsh_odin_alerts WHERE is_resolved = 0"
                );
                $gsh['odin_alerts'] = (int)($r['c'] ?? 0);
            }

            if ($this->tableExists('gsh_games')) {
                $r = $this->db->queryOne(
                    "SELECT COUNT(*) as c FROM gsh_games WHERE status = 'active'"
                );
                $gsh['games_active'] = (int)($r['c'] ?? 0);
            }

            $servers = $this->db->query(
                "SELECT id, name, ip_address, status, nbr_core, nbr_ram FROM gsh_dedicated_servers ORDER BY name ASC"
            );
            foreach ($servers as &$srv) {
                $sid = (int)$srv['id'];
                $srv['odin'] = $this->db->queryOne(
                    "SELECT cpu_percent, memory_percent, disk_percent,
                            memory_used_mb, memory_total_mb,
                            disk_used_gb, disk_total_gb, collected_at
                     FROM gsh_odin_metrics
                     WHERE dedicated_server_id = ?
                     ORDER BY collected_at DESC LIMIT 1",
                    [$sid]
                );
                $srv['running_game_servers'] = (int)($this->db->queryOne(
                    "SELECT COUNT(*) as c FROM gsh_game_servers
                     WHERE dedicated_server_id = ? AND status = 'running'", [$sid]
                )['c'] ?? 0);
                $srv['total_game_servers'] = (int)($this->db->queryOne(
                    "SELECT COUNT(*) as c FROM gsh_game_servers
                     WHERE dedicated_server_id = ? AND status != 'uninstalled'", [$sid]
                )['c'] ?? 0);
            }
            unset($srv);
            $gsh['servers'] = $servers;

            if ($this->tableExists('gsh_game_servers')) {
                $gsh['active_game_servers'] = $this->db->query(
                    "SELECT gs.id, gs.name, gs.port, gs.status, gs.updated_at,
                            g.name    AS game_name,
                            g.icon_url,
                            ds.name   AS ds_name
                     FROM gsh_game_servers gs
                     JOIN gsh_games             g  ON gs.game_id            = g.id
                     JOIN gsh_dedicated_servers ds ON gs.dedicated_server_id = ds.id
                     WHERE gs.status IN ('running','stopped','error','starting','stopping')
                     ORDER BY FIELD(gs.status,'running','starting','stopping','error','stopped') ASC,
                              gs.updated_at DESC
                     LIMIT 10"
                );
            }

            if ($this->tableExists('gsh_installations')) {
                $gsh['recent_installs'] = $this->db->query(
                    "SELECT i.id, i.status, i.progress, i.current_step,
                            i.install_type, i.updated_at, i.error_message,
                            g.name  AS game_name,
                            g.icon_url,
                            ds.name AS server_name
                     FROM gsh_installations i
                     JOIN gsh_games             g  ON i.game_id   = g.id
                     JOIN gsh_dedicated_servers ds ON i.server_id = ds.id
                     ORDER BY i.updated_at DESC
                     LIMIT 6"
                );
            }

            $gsh['chart_data'] = [];
            foreach ($servers as $srv) {
                if ($srv['status'] !== 'online') continue;
                $pts = $this->db->query(
                    "SELECT cpu_percent, memory_percent,
                            DATE_FORMAT(collected_at, '%H:%i') AS label
                     FROM gsh_odin_metrics
                     WHERE dedicated_server_id = ?
                       AND collected_at >= NOW() - INTERVAL 24 HOUR
                     ORDER BY collected_at ASC LIMIT 48",
                    [(int)$srv['id']]
                );
                if (!empty($pts)) {
                    $gsh['chart_data'][$srv['id']] = ['name' => $srv['name'], 'points' => $pts];
                }
            }
        }

        return [
            'total_users'         => $totalUsers,
            'new_users'           => $newUsers,
            'active_users'        => $activeUsers,
            'users_by_role'       => $usersByRole,
            'users_by_status'     => $usersByStatus,
            'registrations_chart' => $registrationsChart,
            'gsh_enabled'         => $gshEnabled,
            'gsh'                 => $gsh,
        ];
    }

    /**
     * Vérifie si une table existe dans la base de données courante.
     */
    private function tableExists(string $table): bool
    {
        try {
            $r = $this->db->queryOne(
                "SELECT COUNT(*) AS c
                 FROM information_schema.tables
                 WHERE table_schema = DATABASE()
                   AND table_name = ?",
                [$table]
            );
            return (int)($r['c'] ?? 0) > 0;
        } catch (\Throwable) {
            return false;
        }
    }
}
