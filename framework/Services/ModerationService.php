<?php
declare(strict_types=1);

namespace Framework\Services;

/**
 * Signalements de contenu (modération), au niveau framework donc réutilisable
 * par tous les modules (livre d'or, commentaires blog, forum…).
 *
 * Table : cms_reports.
 */
class ModerationService
{
    /** Types de contenu signalables (whitelist). */
    public const TYPES = ['guestbook', 'blog_comment', 'blog_post', 'forum_post', 'forum_topic', 'profile', 'marketplace_review'];

    /** Motifs proposés. */
    public const REASONS = [
        'spam'        => 'Spam / publicité',
        'insult'      => 'Insulte / harcèlement',
        'nsfw'        => 'Contenu choquant',
        'offtopic'    => 'Hors-sujet',
        'other'       => 'Autre',
    ];

    public function __construct(private Database $db) {}

    /**
     * Enregistre un signalement (idempotent par utilisateur+contenu) et notifie
     * les administrateurs. @return array{ok:bool,error?:string}
     */
    public function report(string $type, int $contentId, int $reporterId, string $reporterName, string $reason, string $note = ''): array
    {
        if (!in_array($type, self::TYPES, true)) return ['ok' => false, 'error' => 'Type invalide'];
        if ($contentId <= 0 || $reporterId <= 0) return ['ok' => false, 'error' => 'Signalement invalide'];
        if (!array_key_exists($reason, self::REASONS)) $reason = 'other';

        // Déjà signalé par cet utilisateur ?
        $exists = $this->db->query(
            "SELECT id FROM cms_reports WHERE content_type = ? AND content_id = ? AND reporter_user_id = ? LIMIT 1",
            [$type, $contentId, $reporterId]
        );
        if (!empty($exists)) return ['ok' => false, 'error' => 'Vous avez déjà signalé ce contenu'];

        $this->db->execute(
            "INSERT INTO cms_reports (content_type, content_id, reporter_user_id, reporter_name, reason, note)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$type, $contentId, $reporterId, mb_substr($reporterName, 0, 100), $reason, mb_substr($note, 0, 500)]
        );

        $this->notifyAdmins($type, $reporterName, self::REASONS[$reason]);
        return ['ok' => true];
    }

    private function notifyAdmins(string $type, string $reporter, string $reasonLabel): void
    {
        try {
            $admins = $this->db->query("SELECT id FROM users WHERE role IN ('admin','superadmin')");
            foreach ($admins as $a) {
                NotificationService::notify(
                    (int)$a['id'], 'report',
                    'Nouveau signalement (' . $type . ')',
                    $reporter . ' a signalé un contenu — ' . $reasonLabel,
                    '/admin/moderation', '🚩'
                );
            }
        } catch (\Throwable $e) { /* non bloquant */ }
    }

    public function pendingCount(): int
    {
        $r = $this->db->query("SELECT COUNT(*) AS n FROM cms_reports WHERE status = 'pending'");
        return (int)($r[0]['n'] ?? 0);
    }

    /** Liste paginée, groupée par contenu (nombre de signalements). */
    public function listGrouped(string $status = 'pending', int $limit = 100): array
    {
        $status = in_array($status, ['pending', 'resolved', 'rejected', 'all'], true) ? $status : 'pending';
        $where  = $status === 'all' ? '' : 'WHERE status = ?';
        $params = $status === 'all' ? [] : [$status];
        $limit  = max(1, min(300, $limit));

        return $this->db->query(
            "SELECT content_type, content_id,
                    COUNT(*) AS reports,
                    MAX(created_at) AS last_at,
                    MIN(status) AS status,
                    GROUP_CONCAT(DISTINCT reason SEPARATOR ',') AS reasons,
                    GROUP_CONCAT(DISTINCT reporter_name SEPARATOR ', ') AS reporters,
                    MAX(note) AS sample_note
             FROM cms_reports
             $where
             GROUP BY content_type, content_id
             ORDER BY reports DESC, last_at DESC
             LIMIT $limit",
            $params
        );
    }

    /** Traite tous les signalements d'un contenu (resolve/reject). */
    public function setStatus(string $type, int $contentId, string $status, int $adminId): void
    {
        if (!in_array($status, ['resolved', 'rejected', 'pending'], true)) return;
        $this->db->execute(
            "UPDATE cms_reports SET status = ?, resolved_by = ?, resolved_at = NOW()
             WHERE content_type = ? AND content_id = ?",
            [$status, $adminId, $type, $contentId]
        );
    }
}
