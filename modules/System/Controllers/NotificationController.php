<?php
declare(strict_types=1);

namespace System\Controllers;

use Framework\Services\Database;
use Framework\Services\NotificationService;
use Framework\Security\CSRFProtection;

/**
 * Endpoints de la cloche de notifications (in-app), côté membre + admin.
 * Tout utilisateur connecté accède à SES propres notifications.
 */
class NotificationController
{
    private NotificationService $ns;

    public function __construct(Database $db, CSRFProtection $csrf)
    {
        $this->ns = new NotificationService($db);
    }

    private function userId(): int
    {
        return (int)($_SESSION['user_id'] ?? 0);
    }

    private function json(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    /**
     * Cloche : compteur non-lus + dernières notifications.
     * ?types=a,b,c (optionnel) restreint aux types listés — utilisé par les pages
     * membre d'un module pour ne pas remonter les notifications admin/système
     * d'autres modules (ex. activation/désactivation de module) dans la cloche
     * publique. Sans ce paramètre : aucune restriction (cloche admin).
     */
    public function poll(): void
    {
        $uid = $this->userId();
        if ($uid <= 0) { $this->json(['count' => 0, 'items' => []]); return; }

        $types = null;
        if (!empty($_GET['types'])) {
            $types = array_values(array_filter(array_map('trim', explode(',', (string)$_GET['types']))));
            $types = $types ?: null;
        }

        $items = array_map(function ($n) {
            return [
                'id'      => (int)$n['id'],
                'icon'    => $n['icon'] ?: '🔔',
                'title'   => $n['title'],
                'body'    => $n['body'],
                'url'     => self::linkUrl((string)($n['url'] ?? '')),
                'is_read' => (int)$n['is_read'],
                'ago'     => self::ago($n['created_at']),
            ];
        }, $this->ns->forUser($uid, 12, false, $types));

        $this->json(['count' => $this->ns->unreadCount($uid, $types), 'items' => $items]);
    }

    public function markRead(): void
    {
        $uid = $this->userId();
        if ($uid <= 0) { $this->json(['success' => false]); return; }
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) $this->ns->markRead($id, $uid);
        $this->json(['success' => true, 'count' => $this->ns->unreadCount($uid)]);
    }

    public function markAllRead(): void
    {
        $uid = $this->userId();
        if ($uid <= 0) { $this->json(['success' => false]); return; }
        $this->ns->markAllRead($uid);
        $this->json(['success' => true, 'count' => 0]);
    }

    /**
     * Rend cliquable l'URL stockée avec une notification.
     *
     * Les notifications enregistrent un chemin canonique (« /marketplace/purchases »).
     * Sans passer par u(), le lien perdait BASE_URL — donc cassé dès que le site
     * vit dans un sous-dossier — et ignorait le renommage éventuel du préfixe
     * public du module. Une URL absolue ou un mailto: est laissé intact.
     */
    private static function linkUrl(string $url): string
    {
        if ($url === '' || preg_match('#^(https?:)?//#i', $url) || str_starts_with($url, 'mailto:')) {
            return $url;
        }

        return function_exists('u') ? u('/' . ltrim($url, '/')) : $url;
    }

    /** Temps relatif court en français. */
    private static function ago(string $datetime): string
    {
        $ts = strtotime($datetime);
        if (!$ts) return '';
        $d = time() - $ts;
        if ($d < 60)     return "à l'instant";
        if ($d < 3600)   return 'il y a ' . intdiv($d, 60) . ' min';
        if ($d < 86400)  return 'il y a ' . intdiv($d, 3600) . ' h';
        if ($d < 604800) return 'il y a ' . intdiv($d, 86400) . ' j';
        return date('d/m/Y', $ts);
    }
}
