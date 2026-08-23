<?php
declare(strict_types=1);

namespace Auth\Controllers;

use Framework\Services\Database;
use Framework\Security\CSRFProtection;

/**
 * SessionController — endpoints liés à la session active de l'utilisateur :
 *  - ping        : « keep-alive » (réinitialise l'inactivité quand l'utilisateur
 *                  clique « Rester connecté » dans la modale d'avertissement) ;
 *  - logoutOthers: déconnecte toutes les AUTRES sessions de l'utilisateur.
 */
class SessionController
{
    private Database $db;
    private ?CSRFProtection $csrf;

    public function __construct(Database $db, ?CSRFProtection $csrf = null)
    {
        $this->db = $db;
        $this->csrf = $csrf;
    }

    /** GET /auth/session/ping — réinitialise l'activité, renvoie le temps restant. */
    public function ping(): void
    {
        header('Content-Type: application/json');

        if (empty($_SESSION['logged_in']) && empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'expired' => true]);
            exit;
        }

        // Le simple fait d'arriver ici (requête authentifiée) a déjà réinitialisé
        // _last_activity via SessionManager::validate(). On renvoie le restant.
        $sm = $GLOBALS['sessionManager'] ?? null;
        $remaining = ($sm && method_exists($sm, 'getIdleRemaining')) ? $sm->getIdleRemaining() : null;

        echo json_encode(['ok' => true, 'remaining' => $remaining]);
        exit;
    }

    /** POST /auth/session/logout-others — ferme les autres sessions du compte. */
    public function logoutOthers(): void
    {
        header('Content-Type: application/json');

        if ($this->csrf && !$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
            exit;
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Non authentifié']);
            exit;
        }

        $currentSid  = session_id();
        $currentRem  = $_COOKIE['remember_token'] ?? '';
        $closed = 0;

        try {
            $closed = $this->db->execute(
                "DELETE FROM user_sessions WHERE user_id = ? AND session_id <> ?",
                [$userId, $currentSid]
            );
            // Révoque aussi les « se souvenir de moi » des autres appareils.
            if ($currentRem !== '') {
                $this->db->execute("DELETE FROM remember_tokens WHERE user_id = ? AND token <> ?", [$userId, $currentRem]);
            } else {
                $this->db->execute("DELETE FROM remember_tokens WHERE user_id = ?", [$userId]);
            }
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }

        echo json_encode(['success' => true, 'closed' => max(0, (int)$closed)]);
        exit;
    }
}
