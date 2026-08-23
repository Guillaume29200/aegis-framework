<?php
declare(strict_types=1);

namespace System\Controllers;

use Framework\Security\CSRFProtection;
use Framework\Services\Database;

/**
 * ChangelogController — journal des versions d'Aegis Framework.
 * Source : framework/changelog/*.json — un fichier par version, assemblés
 * par ChangelogService.
 */
class ChangelogController
{
    public function __construct(Database $db, CSRFProtection $csrf) {}

    public function index(): void
    {
        if (empty($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', ['admin', 'superadmin', 'moderator'], true)) {
            redirect('/auth/login');
        }

        // Un fichier par version, assemblés et mémorisés par le service.
        $changelog = (new \Framework\Services\ChangelogService())->all();

        require __DIR__ . '/../Views/admin/changelog/index.php';
    }
}
