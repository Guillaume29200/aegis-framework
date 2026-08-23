<?php
declare(strict_types=1);

namespace Framework\Services;

/**
 * Journal d'audit des actions sensibles (framework, transverse à tous les
 * modules) : qui a fait quoi, quand, sur quelle cible, depuis quelle IP.
 *
 * Table : cms_audit_log.
 *
 * Usage simple depuis n'importe où (auteur = session courante) :
 *   \Framework\Services\AuditService::record('module.toggle', 'module', 'Blog', 'Désactivé');
 */
class AuditService
{
    public function __construct(private Database $db) {}

    public function log(
        ?int $userId, string $username, string $action,
        ?string $targetType = null, ?string $targetId = null,
        string $summary = '', string $ip = ''
    ): int {
        if ($action === '') return 0;
        $this->db->execute(
            "INSERT INTO cms_audit_log (user_id, username, action, target_type, target_id, summary, ip)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $userId ?: null,
                mb_substr($username, 0, 100),
                mb_substr($action, 0, 80),
                $targetType !== null ? mb_substr($targetType, 0, 60) : null,
                $targetId !== null ? mb_substr((string)$targetId, 0, 100) : null,
                mb_substr($summary, 0, 500),
                mb_substr($ip, 0, 45),
            ]
        );
        return (int)$this->db->getPDO()->lastInsertId();
    }

    /**
     * Consultation paginée avec filtres (action, recherche texte).
     * @return array{items:array, total:int, pages:int, page:int}
     */
    public function query(array $filters = [], int $page = 1, int $perPage = 40): array
    {
        $page    = max(1, $page);
        $perPage = max(1, min(200, $perPage));
        $where   = [];
        $params  = [];

        if (!empty($filters['action'])) {
            $where[] = 'action = ?';
            $params[] = $filters['action'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(username LIKE ? OR summary LIKE ? OR target_id LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            array_push($params, $like, $like, $like);
        }
        $sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countRow = $this->db->query("SELECT COUNT(*) AS n FROM cms_audit_log $sql", $params);
        $total    = (int)($countRow[0]['n'] ?? 0);
        $offset   = ($page - 1) * $perPage;

        $items = $this->db->query(
            "SELECT * FROM cms_audit_log $sql ORDER BY created_at DESC, id DESC LIMIT $perPage OFFSET $offset",
            $params
        );

        return [
            'items' => $items,
            'total' => $total,
            'pages' => (int)max(1, ceil($total / $perPage)),
            'page'  => $page,
        ];
    }

    /** Liste des actions distinctes présentes (pour le filtre). */
    public function distinctActions(): array
    {
        return array_column(
            $this->db->query("SELECT DISTINCT action FROM cms_audit_log ORDER BY action ASC"),
            'action'
        );
    }

    /** Supprime une entrée précise. @return bool succès (false si id inconnu) */
    public function deleteOne(int $id): bool
    {
        if ($id <= 0) return false;
        return $this->db->execute("DELETE FROM cms_audit_log WHERE id = ?", [$id]) > 0;
    }

    /**
     * Purge le journal. $days = null purge tout l'historique, sinon uniquement
     * les entrées plus vieilles que $days jours.
     * @return int nombre d'entrées supprimées
     */
    public function purge(?int $days = null): int
    {
        $countRow = $this->db->query(
            $days !== null
                ? "SELECT COUNT(*) AS n FROM cms_audit_log WHERE created_at < (NOW() - INTERVAL ? DAY)"
                : "SELECT COUNT(*) AS n FROM cms_audit_log",
            $days !== null ? [max(1, $days)] : []
        );
        $n = (int)($countRow[0]['n'] ?? 0);

        if ($days !== null) {
            $this->db->execute("DELETE FROM cms_audit_log WHERE created_at < (NOW() - INTERVAL ? DAY)", [max(1, $days)]);
        } else {
            $this->db->execute("DELETE FROM cms_audit_log", []);
        }
        return $n;
    }

    // =========================================================================
    // Raccourci statique — auteur déduit de la session, IP du serveur.
    // =========================================================================

    public static function record(string $action, ?string $targetType = null, ?string $targetId = null, string $summary = ''): void
    {
        try {
            $db = $GLOBALS['db'] ?? null;
            if (!$db instanceof Database) return;
            $uid  = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
            $name = (string)($_SESSION['username'] ?? '');
            $ip   = (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '');
            if (str_contains($ip, ',')) $ip = trim(explode(',', $ip)[0]);
            (new self($db))->log($uid, $name, $action, $targetType, $targetId, $summary, $ip);
        } catch (\Throwable $e) {
            // L'audit ne doit jamais casser l'action métier.
        }
    }
}
