<?php
declare(strict_types=1);

namespace Configuration\Controllers;

use Configuration\Services\SeoService;
use Framework\Services\Database;
use Framework\Security\CSRFProtection;

/**
 * SeoController — SEO, médias (logo/favicon/OG) et analytics.
 * Contrôleur dédié (séparé de Configuration).
 */
class SeoController
{
    private SeoService $seo;
    private CSRFProtection $csrf;

    public function __construct(Database $db, CSRFProtection $csrf)
    {
        $this->seo = new SeoService($db);
        $this->csrf = $csrf;
    }

    public function index(): void
    {
        $pageTitle = 'SEO & médias';
        $seo       = $this->seo->getConfig();
        $csrfToken = $this->csrf->generateToken();
        require __DIR__ . '/../Views/admin/seo/index.php';
    }

    public function save(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
            exit;
        }
        echo json_encode($this->seo->save($_POST, $_FILES));
        exit;
    }
}
