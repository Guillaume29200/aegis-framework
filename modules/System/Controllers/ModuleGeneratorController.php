<?php
declare(strict_types=1);

namespace System\Controllers;

use Framework\Security\CSRFProtection;
use Framework\Services\Database;
use System\Services\ModuleGeneratorService;

/**
 * ModuleGeneratorController — assistant de création de module (scaffolding).
 */
class ModuleGeneratorController
{
    private CSRFProtection $csrf;

    public function __construct(Database $db, CSRFProtection $csrf)
    {
        $this->csrf = $csrf;
    }

    public function index(): void
    {
        $this->requireAdmin();
        $pageTitle = 'Générateur de module';
        $csrfToken = $this->csrf->generateToken();
        // L'option « système de licence » n'apparaît que si le module Licenses existe.
        $licenseAvailable = is_dir(ROOT_PATH . '/modules/Licenses');
        require __DIR__ . '/../Views/admin/modules/generate.php';
    }

    public function generate(): void
    {
        $this->requireAdmin();
        try {
            $this->csrf->validateToken($_POST['csrf_token'] ?? '');
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Token CSRF invalide.';
            redirect('/admin/modules/generate');
        }

        $service = new ModuleGeneratorService();
        $result = $service->generate([
            'name'         => $_POST['name'] ?? '',
            'display_name' => $_POST['display_name'] ?? '',
            'description'  => $_POST['description'] ?? '',
            'author'       => $_POST['author'] ?? '',
            'category'     => $_POST['category'] ?? '',
            'icon'         => $_POST['icon'] ?? '',
            'sections'     => $_POST['sections'] ?? '',
            'mega'         => isset($_POST['mega']),
            // Option licence : ignorée en silence si le module Licenses est absent.
            'license'         => isset($_POST['license']) && is_dir(ROOT_PATH . '/modules/Licenses'),
            'license_product' => $_POST['license_product'] ?? '',
        ]);

        if ($result['success']) {
            $_SESSION['success'] = '✅ ' . $result['message'];
            redirect('/admin/modules');
        }
        $_SESSION['error'] = '❌ ' . $result['message'];
        redirect('/admin/modules/generate');
    }

    private function requireAdmin(): void
    {
        if (empty($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'], true)) {
            redirect('/auth/login');
        }
    }
}
