<?php
/**
 * Controller Configuration - Module Configuration V4
 * Gestion de l'interface d'administration des paramètres
 */

namespace Configuration\Controllers;

use Configuration\Services\SettingsService;
use Configuration\Services\MailService;
use Configuration\Services\ImageSettingsService;
use Framework\Services\Database;
use Framework\Security\CSRFProtection;

class ConfigurationController
{
    private $settingsService;
    private MailService $mailService;
    private ImageSettingsService $imageSettings;
    private $csrf;

    public function __construct(Database $db, CSRFProtection $csrf)
    {
        $this->settingsService = new SettingsService($db);
        $this->mailService = new MailService($db);
        $this->imageSettings = new ImageSettingsService($db);
        $this->csrf = $csrf;
    }
    
    /**
     * Page principale des settings
     */
    public function index(): void
    {
        // Récupérer tous les settings
        $settings = $this->settingsService->getAllSettings();
        
        // Vérifier si reCAPTCHA est configuré
        $recaptchaConfigured = $this->settingsService->isRecaptchaConfigured();
        
        // Générer token CSRF
        $csrfToken = $this->csrf->generateToken();
        
        // Préparer les données pour la vue
        $viewData = [
            'pageTitle' => 'Configuration Générale',
            'settings' => $settings,
            'recaptchaConfigured' => $recaptchaConfigured,
            'csrfToken' => $csrfToken
        ];
        
        $this->render('admin/settings/index', $viewData);
    }
    
    /**
     * Sauvegarder les paramètres généraux (AJAX)
     */
    public function saveGeneral(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        // Vérifier CSRF
        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
            exit;
        }
        
        $updates = [
            'site_name' => ['value' => $_POST['site_name'] ?? '', 'type' => 'string'],
            'site_description' => ['value' => $_POST['site_description'] ?? '', 'type' => 'string'],
            'webmaster_email' => ['value' => $_POST['webmaster_email'] ?? '', 'type' => 'string'],
            'login_visual_badge' => ['value' => trim($_POST['login_visual_badge'] ?? ''), 'type' => 'string'],
            'login_visual_title' => ['value' => trim($_POST['login_visual_title'] ?? ''), 'type' => 'string'],
            'login_visual_text' => ['value' => trim($_POST['login_visual_text'] ?? ''), 'type' => 'string']
        ];

        try {
            $loginCoverImage = $this->handleLoginAssetUpload('login_cover_image', 'login-cover');
            if ($loginCoverImage !== null) {
                $updates['login_cover_image'] = ['value' => $loginCoverImage, 'type' => 'string'];
            }

            $loginLogoImage = $this->handleLoginAssetUpload('login_logo_image', 'login-logo');
            if ($loginLogoImage !== null) {
                $updates['login_logo_image'] = ['value' => $loginLogoImage, 'type' => 'string'];
            }
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
        
        if ($this->settingsService->setMultiple($updates)) {
            echo json_encode(['success' => true, 'message' => 'Paramètres généraux sauvegardés avec succès']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la sauvegarde']);
        }
        exit;
    }
    
    /**
     * Sauvegarder les paramètres système (AJAX)
     */
    public function saveSystem(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        // Vérifier CSRF
        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
            exit;
        }
        
        // Validation maintenance_theme
        $maintenanceTheme = $_POST['maintenance_theme'] ?? 'moderne';
        $allowedThemes = ['moderne', 'minimaliste', 'gaming', 'noel', 'halloween'];
        if (!in_array($maintenanceTheme, $allowedThemes)) {
            $maintenanceTheme = 'moderne';
        }
        
        $updates = [
            'debug_mode' => ['value' => isset($_POST['debug_mode']) ? 1 : 0, 'type' => 'bool'],
            'cache_enabled' => ['value' => isset($_POST['cache_enabled']) ? 1 : 0, 'type' => 'bool'],
            'cache_ttl' => ['value' => (int)($_POST['cache_ttl'] ?? 3600), 'type' => 'int'],
            'maintenance_mode' => ['value' => isset($_POST['maintenance_mode']) ? 1 : 0, 'type' => 'bool'],
            'maintenance_theme' => ['value' => $maintenanceTheme, 'type' => 'string'],
        ];

        // Réglages d'optimisation des images (service dédié).
        $this->imageSettings->save($_POST);

        if ($this->settingsService->setMultiple($updates)) {
            echo json_encode(['success' => true, 'message' => 'Paramètres système sauvegardés avec succès']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la sauvegarde']);
        }
        exit;
    }
    
    /**
     * Sauvegarder les paramètres de sécurité (AJAX)
     */
    public function saveSecurity(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        // Vérifier CSRF
        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
            exit;
        }
        
        $updates = [
            'registration_enabled' => ['value' => isset($_POST['registration_enabled']) ? 1 : 0, 'type' => 'bool'],
            // cookies_banner_enabled : géré exclusivement par la page RGPD (RgpdController)
            'recaptcha_enabled' => ['value' => isset($_POST['recaptcha_enabled']) ? 1 : 0, 'type' => 'bool'],
            'recaptcha_site_key' => ['value' => $_POST['recaptcha_site_key'] ?? '', 'type' => 'string'],
            'recaptcha_secret_key' => ['value' => $_POST['recaptcha_secret_key'] ?? '', 'type' => 'string'],
            'recaptcha_login' => ['value' => isset($_POST['recaptcha_login']) ? 1 : 0, 'type' => 'bool'],
            'recaptcha_register' => ['value' => isset($_POST['recaptcha_register']) ? 1 : 0, 'type' => 'bool'],
        ];
        
        if ($this->settingsService->setMultiple($updates)) {
            echo json_encode(['success' => true, 'message' => 'Paramètres de sécurité sauvegardés avec succès']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la sauvegarde']);
        }
        exit;
    }
    
    /**
     * Sauvegarder les paramètres emails (AJAX)
     */
    public function saveEmail(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
            exit;
        }

        echo json_encode($this->mailService->save($_POST));
        exit;
    }
    /**
     * Sauvegarder les paramètres SEO (AJAX)
     */
    public function saveSeo(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        // Vérifier CSRF
        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
            exit;
        }
        
        $updates = [
            'meta_title_template' => ['value' => $_POST['meta_title_template'] ?? '{page_title} - {site_name}', 'type' => 'string'],
            'meta_description_default' => ['value' => $_POST['meta_description_default'] ?? '', 'type' => 'string'],
            'meta_keywords_default' => ['value' => $_POST['meta_keywords_default'] ?? '', 'type' => 'string']
        ];
        
        if ($this->settingsService->setMultiple($updates)) {
            echo json_encode(['success' => true, 'message' => 'Paramètres SEO sauvegardés avec succès']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la sauvegarde']);
        }
        exit;
    }
    
    /**
     * Sauvegarder les paramètres IA (AJAX)
     */
    public function saveAi(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        // Vérifier CSRF
        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
            exit;
        }
        
        $updates = [
            'openai_api_key' => ['value' => $_POST['openai_api_key'] ?? '', 'type' => 'string'],
            'claude_api_key' => ['value' => $_POST['claude_api_key'] ?? '', 'type' => 'string'],
            'mistral_api_key' => ['value' => $_POST['mistral_api_key'] ?? '', 'type' => 'string'],
            'default_ai_provider' => ['value' => $_POST['default_ai_provider'] ?? 'openai', 'type' => 'string']
        ];
        
        if ($this->settingsService->setMultiple($updates)) {
            echo json_encode(['success' => true, 'message' => 'Paramètres IA sauvegardés avec succès']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la sauvegarde']);
        }
        exit;
    }
    
    /**
     * Sauvegarder les paramètres TurboNav (AJAX)
     */
    public function saveTurboNav(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // Vérifier CSRF
        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
            exit;
        }

        $updates = [
            'turbonav_enabled' => ['value' => isset($_POST['turbonav_enabled']) ? 1 : 0, 'type' => 'bool'],
        ];

        if ($this->settingsService->setMultiple($updates)) {
            $state = isset($_POST['turbonav_enabled']) ? 'activé' : 'désactivé';
            echo json_encode(['success' => true, 'message' => "TurboNav $state avec succès"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la sauvegarde']);
        }
        exit;
    }

    /**
     * Enregistre une image utilisee par la page de connexion.
     */
    private function handleLoginAssetUpload(string $fieldName, string $prefix): ?string
    {
        if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
            return null;
        }

        $file = $_FILES[$fieldName];
        $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException($this->getUploadErrorMessage($error));
        }

        $tmpName = (string)($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new \RuntimeException('Upload refuse : fichier temporaire invalide. Rechargez la page puis reessayez.');
        }

        $maxSize = 5 * 1024 * 1024;
        if ((int)($file['size'] ?? 0) > $maxSize) {
            throw new \RuntimeException('Upload refuse : l\'image ne doit pas depasser 5 Mo.');
        }

        $originalName = (string)($file['name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($extension, $allowedExtensions, true)) {
            throw new \RuntimeException('Upload refuse : seuls les fichiers .jpg, .jpeg, .png et .webp sont autorises.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($tmpName) ?: '';
        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/pjpeg' => 'jpg',
            'image/png' => 'png',
            'image/x-png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($allowedTypes[$mimeType])) {
            throw new \RuntimeException('Upload refuse : le contenu du fichier n\'est pas une image JPG, JPEG, PNG ou WebP valide.');
        }

        $extensionByMime = $allowedTypes[$mimeType];
        if (($extension === 'jpg' || $extension === 'jpeg') && $extensionByMime === 'jpg') {
            $extensionByMime = 'jpg';
        } elseif ($extension !== $extensionByMime) {
            throw new \RuntimeException('Upload refuse : l\'extension du fichier ne correspond pas au type reel de l\'image.');
        }

        $uploadDir = ROOT_PATH . '/framework/uploads';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            throw new \RuntimeException('Upload impossible : le dossier framework/uploads est inaccessible.');
        }

        if (!is_writable($uploadDir)) {
            throw new \RuntimeException('Upload impossible : le dossier framework/uploads n\'est pas accessible en ecriture.');
        }

        $filename = sprintf(
            '%s-%s-%s.%s',
            preg_replace('/[^a-z0-9-]/i', '-', $prefix),
            date('Ymd-His'),
            bin2hex(random_bytes(8)),
            $extensionByMime
        );

        $targetPath = $uploadDir . '/' . $filename;
        if (!move_uploaded_file($tmpName, $targetPath)) {
            throw new \RuntimeException('Upload impossible : impossible de deplacer le fichier envoye vers framework/uploads.');
        }

        // Optimisation automatique (service dédié, jpg/png/webp uniquement).
        $this->imageSettings->optimize($targetPath, $extensionByMime);

        return '/framework/uploads/' . $filename;
    }

    private function getUploadErrorMessage(int $error): string
    {
        $uploadMax = ini_get('upload_max_filesize') ?: 'inconnue';
        $postMax = ini_get('post_max_size') ?: 'inconnue';

        return match ($error) {
            UPLOAD_ERR_INI_SIZE => "Upload refuse : l'image depasse la limite PHP upload_max_filesize ($uploadMax). Limite GSH : 5 Mo.",
            UPLOAD_ERR_FORM_SIZE => "Upload refuse : l'image depasse la taille maximale autorisee par le formulaire. Limite GSH : 5 Mo.",
            UPLOAD_ERR_PARTIAL => 'Upload impossible : le fichier a ete recu partiellement. Reessayez avec une connexion stable.',
            UPLOAD_ERR_NO_TMP_DIR => 'Upload impossible : le dossier temporaire PHP est manquant sur le serveur.',
            UPLOAD_ERR_CANT_WRITE => 'Upload impossible : PHP ne peut pas ecrire le fichier sur le disque.',
            UPLOAD_ERR_EXTENSION => 'Upload impossible : une extension PHP a bloque l\'upload.',
            default => "Upload impossible : erreur PHP $error pendant l'envoi. Verifiez aussi post_max_size ($postMax).",
        };
    }
    /**
     * Render une vue
     */
    protected function render(string $view, array $data = []): void
    {
        $viewPath = __DIR__ . '/../Views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            throw new \Exception("Vue introuvable : $viewPath");
        }

        (static function (string $__path, array $__data): void {
            extract($__data, EXTR_SKIP);
            require $__path;
        })($viewPath, $data);
    }
}
