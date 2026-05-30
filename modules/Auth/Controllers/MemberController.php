<?php
declare(strict_types=1);

namespace Auth\Controllers;

use Auth\Services\AuthService;
use Framework\Services\Database;
use Framework\Security\CSRFProtection;

/**
 * Controller for the member/client area.
 */
class MemberController
{
    private AuthService $authService;
    private Database $db;
    private ?CSRFProtection $csrf;

    public function __construct(Database $db, ?CSRFProtection $csrf = null)
    {
        $this->db = $db;
        $this->csrf = $csrf;
        $this->authService = new AuthService($db);
        
        // Vérifier authentification et rôle membre
        if (!$this->authService->isLoggedIn()) {
            header('Location: ' . u('/auth/login'));
            exit;
        }
    }

    public function dashboard(): void
    {
        $user = $this->currentUser();
        $sessionInfo = $this->getSessionInfo((int)$user['id']);
        $pageTitle = 'Tableau de bord';
        $activePage = 'dashboard';

        require __DIR__ . '/../Views/member/dashboard.php';
    }

    public function sessions(): void
    {
        $user = $this->currentUser();
        $sessions = $this->getUserSessions((int)$user['id'], 100);
        $pageTitle = 'Sessions';
        $activePage = 'sessions';

        require __DIR__ . '/../Views/member/sessions.php';
    }


    public function gameServers(): void
    {
        $user = $this->currentUser();
        $gameServers = $this->getUserGameServers((int)$user['id']);
        $pageTitle = 'Mes serveurs de jeux';
        $activePage = 'game-servers';

        require __DIR__ . '/../Views/member/game-servers.php';
    }
    public function profile(): void
    {
        $user = $this->currentUser();
        $sessionInfo = $this->getSessionInfo((int)$user['id']);
        $pageTitle = 'Mes informations';
        $activePage = 'profile';

        require __DIR__ . '/../Views/member/profile.php';
    }

    public function updateProfile(): void
    {
        $userId = (int)$_SESSION['user_id'];
        $firstName = trim((string)($_POST['first_name'] ?? ''));
        $lastName = trim((string)($_POST['last_name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: ' . u('/member/profile?error=email_invalid'));
            exit;
        }

        $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            header('Location: ' . u('/member/profile?error=email_exists'));
            exit;
        }

        $stmt = $this->db->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ?, updated_at = NOW() WHERE id = ?');
        $success = $stmt->execute([$firstName ?: null, $lastName ?: null, $email, $userId]);

        if ($success) {
            $_SESSION['email'] = $email;
            header('Location: ' . u('/member/profile?updated=1'));
        } else {
            header('Location: ' . u('/member/profile?error=1'));
        }
        exit;
    }

    public function changePassword(): void
    {
        $userId = (int)$_SESSION['user_id'];
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if ($newPassword !== $confirmPassword) {
            header('Location: ' . u('/member/profile?error=password_mismatch'));
            exit;
        }
        if (strlen($newPassword) < 8) {
            header('Location: ' . u('/member/profile?error=password_too_short'));
            exit;
        }

        $user = $this->currentUser();
        if (!password_verify($currentPassword, (string)$user['password'])) {
            header('Location: ' . u('/member/profile?error=wrong_password'));
            exit;
        }

        $hash = password_hash($newPassword, PASSWORD_ARGON2ID);
        $stmt = $this->db->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?');
        $success = $stmt->execute([$hash, $userId]);

        header('Location: ' . u($success ? '/member/profile?password_updated=1' : '/member/profile?error=1'));
        exit;
    }

    private function currentUser(): array
    {
        $user = $this->authService->getUserById((int)$_SESSION['user_id']);
        if (!$user) {
            header('Location: ' . u('/auth/logout'));
            exit;
        }
        return $user;
    }

    private function getSessionInfo(int $userId): array
    {
        $sessions = $this->getUserSessions($userId, 20);
        $currentSessionId = session_id();
        $current = null;
        $previous = null;
        $registration = null;

        foreach ($sessions as $session) {
            if ($registration === null || strtotime((string)$session['created_at']) < strtotime((string)$registration['created_at'])) {
                $registration = $session;
            }
            if (($session['session_id'] ?? '') === $currentSessionId) {
                $current = $session;
                continue;
            }
            if ($previous === null) {
                $previous = $session;
            }
        }

        return [
            'current_ip' => $current['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? 'Inconnue'),
            'previous_ip' => $previous['ip_address'] ?? null,
            'registration_ip' => $registration['ip_address'] ?? null,
            'current_session_id' => $currentSessionId,
            'current' => $current,
            'previous' => $previous,
        ];
    }


    private function getUserGameServers(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                gs.id,
                gs.name,
                gs.status,
                gs.port,
                gs.query_port,
                gs.slots,
                gs.allocated_cores,
                gs.allocated_ram,
                gs.current_players,
                gs.current_map,
                gs.ping,
                gs.last_query_at,
                gs.last_online_at,
                gs.expires_at,
                gs.created_at,
                gs.updated_at,
                g.name AS game_name,
                g.category AS game_category,
                g.icon_url AS game_icon,
                g.banner_path AS game_banner,
                ds.name AS host_name,
                ds.ip_address AS host_ip,
                ds.status AS host_status
            FROM gsh_game_servers gs
            LEFT JOIN gsh_games g ON g.id = gs.game_id
            LEFT JOIN gsh_dedicated_servers ds ON ds.id = gs.dedicated_server_id
            WHERE gs.user_id = ?
            ORDER BY FIELD(gs.status, 'running', 'starting', 'stopped', 'installing', 'error', 'stopping', 'uninstalled'), gs.updated_at DESC, gs.id DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
    private function getUserSessions(int $userId, int $limit): array
    {
        $limit = max(1, min(200, (int)$limit));
        // LIMIT is an integer we control after clamping — safe to interpolate, but cast to int is explicit
        $stmt = $this->db->prepare("SELECT * FROM user_sessions WHERE user_id = ? ORDER BY last_activity DESC, id DESC LIMIT " . $limit);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
}