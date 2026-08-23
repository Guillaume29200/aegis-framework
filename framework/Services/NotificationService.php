<?php
declare(strict_types=1);

namespace Framework\Services;

/**
 * Notifications par utilisateur (cloche in-app), au niveau framework donc
 * réutilisable par tous les modules (Tournois, amis, tickets, forum…).
 *
 * Table : cms_notifications (user_id, type, icon, title, body, url, is_read).
 *
 * Usage simple depuis n'importe où (sans injection) :
 *   \Framework\Services\NotificationService::notify($userId, 'friend', 'Titre', 'Corps', '/url', '🤝');
 */
class NotificationService
{
    public function __construct(private Database $db) {}

    public function push(int $userId, string $type, string $title, string $body = '', string $url = '', string $icon = '🔔'): int
    {
        if ($userId <= 0 || $title === '') {
            return 0;
        }
        $this->db->execute(
            "INSERT INTO cms_notifications (user_id, type, icon, title, body, url)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $userId,
                mb_substr($type, 0, 50),
                mb_substr($icon, 0, 16) ?: '🔔',
                mb_substr($title, 0, 180),
                mb_substr($body, 0, 500),
                mb_substr($url, 0, 255),
            ]
        );
        return (int)$this->db->getPDO()->lastInsertId();
    }

    /**
     * @param string[]|null $types Restreint aux types listés (ex. contexte membre d'un
     *                              module : n'affiche pas les notifications admin/système
     *                              d'autres modules). null = aucune restriction (défaut,
     *                              comportement historique — utilisé notamment par la
     *                              cloche admin qui doit tout voir).
     */
    public function forUser(int $userId, int $limit = 20, bool $unreadOnly = false, ?array $types = null): array
    {
        $limit = max(1, min(100, $limit));
        $where  = $unreadOnly ? 'AND is_read = 0' : '';
        $params = [$userId];
        if ($types) {
            $placeholders = implode(',', array_fill(0, count($types), '?'));
            $where .= " AND type IN ($placeholders)";
            $params = array_merge($params, $types);
        }
        return $this->db->query(
            "SELECT * FROM cms_notifications WHERE user_id = ? $where ORDER BY created_at DESC LIMIT $limit",
            $params
        );
    }

    /** @param string[]|null $types Voir forUser(). */
    public function unreadCount(int $userId, ?array $types = null): int
    {
        $where  = '';
        $params = [$userId];
        if ($types) {
            $placeholders = implode(',', array_fill(0, count($types), '?'));
            $where = " AND type IN ($placeholders)";
            $params = array_merge($params, $types);
        }
        $row = $this->db->query(
            "SELECT COUNT(*) AS n FROM cms_notifications WHERE user_id = ? AND is_read = 0 $where",
            $params
        );
        return (int)($row[0]['n'] ?? 0);
    }

    public function markRead(int $id, int $userId): void
    {
        $this->db->execute(
            "UPDATE cms_notifications SET is_read = 1 WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }

    public function markAllRead(int $userId): void
    {
        $this->db->execute(
            "UPDATE cms_notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
    }

    public function purgeOld(int $days = 60): void
    {
        $this->db->execute(
            "DELETE FROM cms_notifications WHERE is_read = 1 AND created_at < (NOW() - INTERVAL ? DAY)",
            [max(1, $days)]
        );
    }

    // =========================================================================
    // Raccourci statique — pour appeler depuis n'importe quel service/contrôleur
    // sans passer par l'injection de dépendances.
    // =========================================================================

    public static function notify(int $userId, string $type, string $title, string $body = '', string $url = '', string $icon = '🔔'): void
    {
        try {
            $db = $GLOBALS['db'] ?? null;
            if (!$db instanceof Database) return;
            (new self($db))->push($userId, $type, $title, $body, $url, $icon);
        } catch (\Throwable $e) {
            // Une notification ne doit jamais casser l'action métier appelante.
        }
    }
}
