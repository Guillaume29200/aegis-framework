<?php
declare(strict_types=1);

namespace Configuration\Controllers;

use Configuration\Services\RgpdService;
use Framework\Services\Database;
use Framework\Security\CSRFProtection;

/**
 * RgpdController — administration de la bannière RGPD / cookies.
 * Contrôleur dédié à cette fonctionnalité (séparé de Configuration).
 */
class RgpdController
{
    private RgpdService $rgpd;
    private CSRFProtection $csrf;

    public function __construct(Database $db, CSRFProtection $csrf)
    {
        $this->rgpd = new RgpdService($db);
        $this->csrf = $csrf;
    }

    /** Page d'administration RGPD. */
    public function index(): void
    {
        $pageTitle = 'RGPD / Cookies';
        $rgpd      = $this->rgpd->getConfig();
        $csrfToken = $this->csrf->generateToken();
        require __DIR__ . '/../Views/admin/rgpd/index.php';
    }

    /** Sauvegarde (AJAX JSON). */
    public function save(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
            exit;
        }

        $ok = $this->rgpd->save($_POST);
        echo json_encode($ok
            ? ['success' => true,  'message' => 'Configuration enregistrée. Le consentement sera redemandé aux visiteurs.']
            : ['success' => false, 'message' => 'Erreur lors de la sauvegarde.']);
        exit;
    }

    /** Réinitialiser les consentements (force le réaffichage du bandeau). */
    public function reset(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
            exit;
        }
        $ok = $this->rgpd->resetConsents();
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Consentements réinitialisés : le bandeau réapparaîtra pour tous les visiteurs.' : 'Erreur.']);
        exit;
    }
}
