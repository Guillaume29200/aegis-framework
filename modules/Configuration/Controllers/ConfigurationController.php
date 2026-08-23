<?php
/**
 * Controller Configuration - Module Configuration V4
 * Gestion de l'interface d'administration des paramètres
 */

namespace Configuration\Controllers;

use Configuration\Services\SettingsService;
use Configuration\Services\MailService;
use Configuration\Services\ImageSettingsService;
use Configuration\Services\AIModelService;
use Framework\Services\Database;
use Framework\Services\SecretBox;
use Framework\Security\CSRFProtection;

class ConfigurationController
{
    private $settingsService;
    private MailService $mailService;
    private ImageSettingsService $imageSettings;
    private $csrf;
    private Database $db;

    public function __construct(Database $db, CSRFProtection $csrf)
    {
        $this->db = $db;
        $this->settingsService = new SettingsService($db);
        $this->mailService = new MailService($db);
        $this->imageSettings = new ImageSettingsService($db);
        $this->csrf = $csrf;
        $this->requireAdmin();
    }

    /**
     * La Configuration touche des réglages sensibles (identifiants SMTP, clés API IA,
     * reCAPTCHA, mode maintenance...) : réservé à admin/superadmin, contrairement au
     * portail /admin générique qui laisse aussi entrer les modérateurs.
     */
    private function requireAdmin(): void
    {
        if (empty($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'], true)) {
            redirect('/auth/login');
        }
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
        
        // Page d'accueil par défaut : options publiques disponibles + valeur courante
        $landingOptions = (new \Configuration\Services\LandingService($this->db))->options();
        $defaultLanding = (string) $this->settingsService->get('default_landing', '');

        // Diagnostic IA : le provider choisi par défaut a-t-il un modèle actif / un modèle par défaut ?
        $aiModelService = new AIModelService($this->db);
        $aiDefaultProvider = (string) $this->settingsService->get('default_ai_provider', 'openai');
        $aiProviderHasActiveModels = !empty($aiModelService->getByProvider($aiDefaultProvider, true));
        $aiProviderHasDefaultModel = $aiModelService->getDefaultForProvider($aiDefaultProvider) !== null;

        // Préparer les données pour la vue
        $viewData = [
            'pageTitle' => 'Configuration Générale',
            'settings' => $settings,
            'recaptchaConfigured' => $recaptchaConfigured,
            'csrfToken' => $csrfToken,
            'landingOptions' => $landingOptions,
            'defaultLanding' => $defaultLanding,
            'aiDefaultProvider' => $aiDefaultProvider,
            'aiProviderHasActiveModels' => $aiProviderHasActiveModels,
            'aiProviderHasDefaultModel' => $aiProviderHasDefaultModel,
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
            'login_visual_text' => ['value' => trim($_POST['login_visual_text'] ?? ''), 'type' => 'string'],
            // Visuels propres à la page d'inscription (repli sur ceux du login côté vue).
            'register_visual_badge' => ['value' => trim($_POST['register_visual_badge'] ?? ''), 'type' => 'string'],
            'register_visual_title' => ['value' => trim($_POST['register_visual_title'] ?? ''), 'type' => 'string'],
            'register_visual_text' => ['value' => trim($_POST['register_visual_text'] ?? ''), 'type' => 'string'],
        ];

        // Page d'accueil par défaut : on n'accepte qu'une valeur autorisée (URL publique d'un module actif, 'auth' ou vide).
        $landing = trim((string) ($_POST['default_landing'] ?? ''));
        $allowed = (new \Configuration\Services\LandingService($this->db))->allowedValues();
        if (!in_array($landing, $allowed, true)) { $landing = ''; }
        $updates['default_landing'] = ['value' => $landing, 'type' => 'string'];

        try {
            $loginCoverImage = $this->handleLoginAssetUpload('login_cover_image', 'login-cover');
            if ($loginCoverImage !== null) {
                $updates['login_cover_image'] = ['value' => $loginCoverImage, 'type' => 'string'];
            }

            $loginLogoImage = $this->handleLoginAssetUpload('login_logo_image', 'login-logo');
            if ($loginLogoImage !== null) {
                $updates['login_logo_image'] = ['value' => $loginLogoImage, 'type' => 'string'];
            }

            $registerCoverImage = $this->handleLoginAssetUpload('register_cover_image', 'register-cover');
            if ($registerCoverImage !== null) {
                $updates['register_cover_image'] = ['value' => $registerCoverImage, 'type' => 'string'];
            }

            $registerLogoImage = $this->handleLoginAssetUpload('register_logo_image', 'register-logo');
            if ($registerLogoImage !== null) {
                $updates['register_logo_image'] = ['value' => $registerLogoImage, 'type' => 'string'];
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
     * Sauvegarder les paramètres de session (AJAX).
     * Pris en compte au prochain chargement (bootstrap lit ces clés).
     */
    public function saveSessions(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
            exit;
        }

        $ipBinding = in_array($_POST['session_ip_binding'] ?? '', ['off', 'subnet', 'strict'], true)
            ? $_POST['session_ip_binding'] : 'subnet';

        $clamp = fn($v, $min, $max, $def) => max($min, min($max, (int)($v !== '' ? $v : $def)));

        $updates = [
            'session_idle_logout'        => ['value' => isset($_POST['session_idle_logout']) ? 1 : 0, 'type' => 'bool'],
            'session_idle_minutes'       => ['value' => $clamp($_POST['session_idle_minutes'] ?? '', 1, 1440, 120), 'type' => 'int'],
            'session_warn_seconds'       => ['value' => $clamp($_POST['session_warn_seconds'] ?? '', 10, 600, 60), 'type' => 'int'],
            'session_ip_binding'         => ['value' => $ipBinding, 'type' => 'string'],
            'session_regenerate_minutes' => ['value' => $clamp($_POST['session_regenerate_minutes'] ?? '', 1, 240, 5), 'type' => 'int'],
            'session_remember_enabled'   => ['value' => isset($_POST['session_remember_enabled']) ? 1 : 0, 'type' => 'bool'],
            'session_remember_days'      => ['value' => $clamp($_POST['session_remember_days'] ?? '', 1, 365, 30), 'type' => 'int'],
        ];

        if ($this->settingsService->setMultiple($updates)) {
            echo json_encode(['success' => true, 'message' => 'Réglages de session enregistrés (effectifs au prochain chargement de page).']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la sauvegarde']);
        }
        exit;
    }

    /**
     * Sauvegarder les réglages de double authentification (AJAX).
     */
    public function saveTwoFactor(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
            exit;
        }

        $actif = isset($_POST['twofa_enabled']) ? 1 : 0;
        $scope = in_array($_POST['twofa_scope'] ?? '', ['admins', 'all'], true) ? $_POST['twofa_scope'] : 'admins';
        $ttl   = max(2, min(60, (int) ($_POST['twofa_ttl_minutes'] ?? 10)));

        $ok = $this->settingsService->setMultiple([
            'twofa_enabled'     => ['value' => $actif, 'type' => 'bool'],
            'twofa_scope'       => ['value' => $scope, 'type' => 'string'],
            'twofa_ttl_minutes' => ['value' => $ttl,   'type' => 'int'],
        ]);

        if (!$ok) {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la sauvegarde']);
            exit;
        }

        $service = new \Auth\Services\TwoFactorService($this->db);

        // À l'activation, on produit un code de secours s'il n'y en a pas.
        // C'est le seul moment où il peut être montré, et le seul recours si
        // l'envoi d'e-mail tombe : la page qui permettrait de désactiver la
        // protection est elle-même derrière la protection.
        $codeDeSecours = null;
        if ($actif === 1 && !$service->codeDeSecoursExiste()) {
            $codeDeSecours = $service->genererCodeDeSecours();
        }

        // On avertit sans refuser : l'administrateur peut vouloir préparer le
        // réglage avant de configurer l'envoi. Mais il doit le savoir.
        $message = $actif === 1
            ? (\Framework\Services\Mailer::transportDisponible()
                ? 'Double authentification activée. Elle s\'appliquera à la prochaine connexion.'
                : '⚠️ Activée, mais AUCUN agent d\'envoi n\'est configuré : les codes ne partiront pas et la connexion sera impossible. Faites un envoi de test.')
            : 'Double authentification désactivée.';

        echo json_encode([
            'success'  => true,
            'message'  => $message,
            'recovery' => $codeDeSecours,
        ]);
        exit;
    }

    /**
     * Régénérer le code de secours (AJAX).
     *
     * L'ancien cesse aussitôt de fonctionner : c'est le geste à faire quand on
     * a égaré le papier, ou qu'on soupçonne que quelqu'un l'a vu.
     */
    public function regenerateTwoFactorRecovery(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
            exit;
        }

        $code = (new \Auth\Services\TwoFactorService($this->db))->genererCodeDeSecours();

        echo json_encode([
            'success'  => true,
            'message'  => 'Nouveau code de secours généré. L\'ancien ne fonctionne plus.',
            'recovery' => $code,
        ]);
        exit;
    }

    /**
     * Envoi d'un message de test (AJAX).
     *
     * Indispensable avant d'activer le 2FA : `mail()` échoue silencieusement
     * quand aucun agent de transport n'est configuré, ce qui est le cas par
     * défaut sous WAMP.
     */
    public function testTwoFactorMail(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
            exit;
        }

        $service = new \Auth\Services\TwoFactorService($this->db);
        echo json_encode($service->envoiDeTest(trim((string) ($_POST['email'] ?? ''))));
        exit;
    }

    /**
     * Sauvegarder le filtrage des domaines d'e-mail (AJAX).
     */
    public function saveDomains(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
            exit;
        }

        // On normalise à l'enregistrement plutôt qu'à chaque lecture : la liste
        // est écrite une fois et relue à chaque inscription.
        $domaines = \Framework\Security\EmailDomainPolicy::analyser((string) ($_POST['email_domains_allowed'] ?? ''));
        $actif    = isset($_POST['email_domains_enabled']) ? 1 : 0;

        // Activer avec une liste vide fermerait le site aux inscriptions.
        if ($actif === 1 && $domaines === []) {
            echo json_encode([
                'success' => false,
                'message' => 'Impossible d\'activer le filtre avec une liste vide : plus aucune inscription ne serait possible.',
            ]);
            exit;
        }

        $ok = $this->settingsService->setMultiple([
            'email_domains_enabled' => ['value' => $actif, 'type' => 'bool'],
            'email_domains_allowed' => ['value' => implode("\n", $domaines), 'type' => 'string'],
        ]);

        if (!$ok) {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la sauvegarde']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'message' => $actif === 1
                ? sprintf('Filtre actif sur %d domaine(s). Vérifiez que le vôtre y figure.', count($domaines))
                : sprintf('Filtre désactivé. La liste de %d domaine(s) est conservée.', count($domaines)),
        ]);
        exit;
    }

    /**
     * Éprouver une adresse contre les réglages enregistrés (AJAX).
     *
     * Se tromper de liste blanche ferme le site aux inscriptions sans que
     * personne s'en aperçoive : mieux vaut pouvoir vérifier sur-le-champ.
     */
    public function testDomain(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
            exit;
        }

        $email = trim((string) ($_POST['email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Adresse invalide.']);
            exit;
        }

        $politique = \Framework\Security\EmailDomainPolicy::depuisBase($this->db);

        if (!$politique->actif()) {
            echo json_encode(['success' => true, 'message' => 'Le filtre est inactif : toutes les adresses passent.']);
            exit;
        }

        $refus = $politique->refus($email);

        echo json_encode([
            'success' => $refus === null,
            'message' => $refus ?? 'Adresse acceptée.',
        ]);
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
            'default_ai_provider' => ['value' => $_POST['default_ai_provider'] ?? 'openai', 'type' => 'string']
        ];

        // Champ vide = laisser inchangé (on ne réécrit pas la clé déjà enregistrée) ;
        // les clés non vides sont chiffrées avant stockage (jamais en clair en base).
        foreach (['openai_api_key', 'claude_api_key', 'mistral_api_key', 'gemini_api_key', 'groq_api_key'] as $key) {
            $value = trim((string) ($_POST[$key] ?? ''));
            if ($value !== '') {
                $updates[$key] = ['value' => SecretBox::encrypt($value), 'type' => 'string'];
            }
        }

        // Ollama : une URL de serveur local, pas un secret — stockée en clair,
        // toujours réécrite (contrairement aux clés API, un champ vide signifie
        // ici "revenir à la valeur par défaut", pas "ne pas toucher").
        $updates['ollama_base_url'] = ['value' => trim((string) ($_POST['ollama_base_url'] ?? '')), 'type' => 'string'];

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
