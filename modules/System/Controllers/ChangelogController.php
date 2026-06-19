<?php
declare(strict_types=1);

namespace System\Controllers;

use Framework\Security\CSRFProtection;
use Framework\Services\Database;

/**
 * ChangelogController — journal des versions d'Aegis Framework.
 * Source : framework/changelog.json (remplace l'ancien changelog.md racine).
 */
class ChangelogController
{
    public function __construct(Database $db, CSRFProtection $csrf) {}

    public function index(): void
    {
        if (empty($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', ['admin', 'superadmin', 'moderator'], true)) {
            redirect('/auth/login');
        }

        $file = ROOT_PATH . '/framework/changelog.json';
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
        $changelog = is_array($data) ? $data : ['product' => 'Aegis Framework', 'version' => '', 'releases' => []];

        require __DIR__ . '/../Views/admin/changelog/index.php';
    }
}
