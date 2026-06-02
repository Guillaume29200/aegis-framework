<?php
declare(strict_types=1);

namespace Auth\Controllers;

use Auth\Services\AuthService;
use Configuration\Services\SettingsService;
use Framework\Services\Database;
use Framework\Security\CSRFProtection;
use Framework\Security\RateLimiter;

/**
 * Controleur d'authentification.
 */
class AuthController
{
    private AuthService $authService;
    private SettingsService $settingsService;
    private CSRFProtection $csrf;

    public function __construct(Database $db, CSRFProtection $csrf, RateLimiter $rateLimiter)
    {
        $this->authService = new AuthService($db, $rateLimiter);
        $this->settingsService = new SettingsService($db);
        $this->csrf = $csrf;
    }

    /**
     * Signale un événement de sécurité au Centre de sécurité (si disponible).
     * Découplé : passe par le conteneur global, sans dépendance dure.
     */
    private function reportSecurity(string $ruleKey, string $detail): void
    {
        $center = $GLOBALS['securityCenterService'] ?? null;
        if ($center instanceof \Framework\Services\SecurityCenterService) {
            try {
                $center->recordEvent($center->clientIp(), $ruleKey, $detail);
            } catch (\Throwable $e) {
                // La sécurité ne doit jamais casser l'authentification.
                error_log('[SecurityCenter] reportSecurity: ' . $e->getMessage());
            }
        }
    }

    public function showLogin(): void
    {
        if ($this->authService->isLoggedIn()) {
            $role = $_SESSION['role'] ?? 'member';
            header('Location: ' . u($role === 'admin' || $role === 'superadmin' ? '/admin/dashboard' : '/member/dashboard'));
            exit;
        }

        $csrfToken = $this->csrf->generateToken();
        $settings = $this->settingsService->getAllSettings();
        require __DIR__ . '/../Views/auth/login.php';
    }

    public function login(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Methode non autorisee']);
            exit;
        }

        try {
            if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
                $this->reportSecurity('csrf_attack', 'Token CSRF invalide sur /auth/login');
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Token CSRF invalide. Veuillez recharger la page.']);
                exit;
            }
        } catch (\Throwable $e) {
            $this->reportSecurity('csrf_attack', 'Token CSRF absent/invalide sur /auth/login');
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Erreur de validation CSRF : ' . $e->getMessage()]);
            exit;
        }

        $identifier = trim((string)($_POST['identifier'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $rememberMe = isset($_POST['remember_me']);
        $screenResolution = $_POST['screen_resolution'] ?? null;

        if ($identifier === '' || $password === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Tous les champs sont requis.']);
            exit;
        }

        try {
            $result = $this->authService->login($identifier, $password, $rememberMe, $screenResolution);

            if (!$result['success']) {
                $err = (string)($result['error'] ?? '');
                if (str_contains($err, 'Trop de tentatives')) {
                    $this->reportSecurity('auth_flood', 'Limite de tentatives de connexion atteinte');
                } else {
                    $this->reportSecurity('brute_force', 'Échec de connexion pour « ' . $identifier . ' »');
                }
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Identifiants incorrects.']);
                exit;
            }

            if (!empty($_POST['maintenance_bypass'])) {
                $role = $_SESSION['role'] ?? '';
                if (!in_array($role, ['admin', 'superadmin'], true)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'error' => 'Acces reserve aux administrateurs']);
                    exit;
                }

                echo json_encode([
                    'success' => true,
                    'message' => 'Connexion reussie !',
                    'redirect' => u('/admin/dashboard'),
                ]);
                exit;
            }

            echo json_encode([
                'success' => true,
                'message' => 'Connexion reussie !',
                'redirect' => $result['redirect'],
            ]);
            exit;
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Une erreur est survenue : ' . $e->getMessage()]);
            exit;
        }
    }

    public function showRegister(): void
    {
        if ($this->authService->isLoggedIn()) {
            $role = $_SESSION['role'] ?? 'member';
            header('Location: ' . u($role === 'admin' || $role === 'superadmin' ? '/admin/dashboard' : '/member/dashboard'));
            exit;
        }

        if (!$this->authService->isRegistrationEnabled()) {
            $csrfToken = $this->csrf->generateToken();
            $settings = $this->settingsService->getAllSettings();
            require __DIR__ . '/../Views/auth/registration-closed.php';
            exit;
        }

        $csrfToken = $this->csrf->generateToken();
        $settings = $this->settingsService->getAllSettings();
        require __DIR__ . '/../Views/auth/register.php';
    }

    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Methode non autorisee'], 405);
        }

        if (!$this->authService->isRegistrationEnabled()) {
            $this->jsonResponse([
                'success' => false,
                'errors' => ['general' => 'Les inscriptions sont actuellement fermees.'],
            ], 403);
        }

        try {
            if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
                $this->jsonResponse([
                    'success' => false,
                    'errors' => ['general' => 'Token CSRF invalide.'],
                ], 403);
            }
        } catch (\Throwable $e) {
            $this->jsonResponse([
                'success' => false,
                'errors' => ['general' => 'Erreur de validation CSRF.'],
            ], 403);
        }

        $data = [
            'username' => trim((string)($_POST['username'] ?? '')),
            'email' => trim((string)($_POST['email'] ?? '')),
            'password' => (string)($_POST['password'] ?? ''),
            'password_confirm' => (string)($_POST['password_confirm'] ?? ''),
            'first_name' => trim((string)($_POST['first_name'] ?? '')),
            'last_name' => trim((string)($_POST['last_name'] ?? '')),
        ];
        $screenResolution = $_POST['screen_resolution'] ?? null;

        try {
            $result = $this->authService->register($data, $screenResolution);
        } catch (\Throwable $e) {
            error_log('Registration failed: ' . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'errors' => ['general' => 'Une erreur est survenue pendant l\'inscription.'],
            ], 500);
        }

        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Inscription reussie !',
                'redirect' => $result['redirect'] ?? u('/member/dashboard'),
            ]);
        }

        $this->jsonResponse([
            'success' => false,
            'errors' => $result['errors'] ?? ['general' => 'Inscription impossible.'],
        ], 400);
    }
    public function logout(): void
    {
        $this->authService->logout();
        header('Location: ' . u('/auth/login?logout=1'));
        exit;
    }

    public function showForgotPassword(): void
    {
        $csrfToken = $this->csrf->generateToken();
        $settings = $this->settingsService->getAllSettings();
        require __DIR__ . '/../Views/auth/forgot-password.php';
    }

    public function forgotPassword(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Methode non autorisee']);
            exit;
        }

        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Token CSRF invalide. Veuillez recharger la page.']);
            exit;
        }

        $email = trim((string)($_POST['email'] ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            try {
                $resetRequest = $this->authService->createPasswordResetRequest($email);
                if ($resetRequest !== null) {
                    $settings = $this->settingsService->getAllSettings();
                    $resetUrl = $this->absoluteUrl('/auth/reset-password/' . $resetRequest['token']);
                    $sent = $this->sendPasswordResetEmail($resetRequest, $resetUrl, $settings);
                    if (!$sent) {
                        error_log('Password reset mail failed for user #' . $resetRequest['user_id']);
                    }
                }
            } catch (\Throwable $e) {
                error_log('Password reset request failed: ' . $e->getMessage());
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Si cet email existe, vous recevrez un lien de reinitialisation.'
        ]);
        exit;
    }

    public function showResetPassword(string $token): void
    {
        $csrfToken = $this->csrf->generateToken();
        $settings = $this->settingsService->getAllSettings();
        require __DIR__ . '/../Views/auth/reset-password.php';
    }

    public function resetPassword(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Methode non autorisee']);
            exit;
        }

        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Token CSRF invalide. Veuillez recharger la page.']);
            exit;
        }

        try {
            $result = $this->authService->resetPasswordWithToken(
                trim((string)($_POST['token'] ?? '')),
                (string)($_POST['password'] ?? ''),
                (string)($_POST['password_confirm'] ?? '')
            );

            if (!$result['success']) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'errors' => $result['errors'] ?? ['general' => 'Reinitialisation impossible.'],
                    'error' => $result['errors']['general'] ?? 'Veuillez verifier les champs du formulaire.'
                ]);
                exit;
            }

            echo json_encode([
                'success' => true,
                'message' => 'Votre mot de passe a ete reinitialise. Vous pouvez maintenant vous connecter.'
            ]);
            exit;
        } catch (\Throwable $e) {
            error_log('Password reset failed: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Une erreur est survenue pendant la reinitialisation.']);
            exit;
        }
    }

    private function sendPasswordResetEmail(array $resetRequest, string $resetUrl, array $settings): bool
    {
        $siteName = trim((string)($settings['site_name'] ?? 'Aegis Framework'));
        $fromEmail = trim((string)($settings['password_reset_from_email'] ?? ''));
        if ($fromEmail === '') {
            $fromEmail = trim((string)($settings['webmaster_email'] ?? ''));
        }
        if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            $host = preg_replace('/:\\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
            $fromEmail = 'no-reply@' . ($host ?: 'localhost');
        }

        $fromName = trim((string)($settings['password_reset_from_name'] ?? '')) ?: $siteName;
        $subject = trim((string)($settings['password_reset_email_subject'] ?? '')) ?: 'Reinitialisation de votre mot de passe - {site_name}';
        $body = trim((string)($settings['password_reset_email_body'] ?? ''));
        if ($body === '') {
            $body = "Bonjour {username},\n\nUne demande de reinitialisation de mot de passe a ete effectuee pour votre compte {site_name}.\n\nCliquez sur le lien suivant pour choisir un nouveau mot de passe :\n{reset_link}\n\nCe lien expire dans {expires_minutes} minutes. Si vous n'etes pas a l'origine de cette demande, ignorez cet email.\n\n{site_name}";
        }

        $replacements = [
            '{site_name}' => $siteName,
            '{username}' => (string)($resetRequest['username'] ?? ''),
            '{email}' => (string)($resetRequest['email'] ?? ''),
            '{reset_link}' => $resetUrl,
            '{expires_minutes}' => (string)($resetRequest['expires_minutes'] ?? 60),
            '{ip}' => $_SERVER['REMOTE_ADDR'] ?? '',
            '{date}' => date('d/m/Y H:i'),
        ];

        $subject = strtr($subject, $replacements);
        $body = strtr($body, $replacements);

        $encodedFromName = mb_encode_mimeheader($fromName, 'UTF-8');
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $encodedFromName . ' <' . $fromEmail . '>',
            'Reply-To: ' . $fromEmail,
            'X-Mailer: PHP/' . PHP_VERSION,
        ];

        return mail((string)$resetRequest['email'], mb_encode_mimeheader($subject, 'UTF-8'), $body, implode("\r\n", $headers));
    }

    private function jsonResponse(array $payload, int $status = 200): void
    {
        http_response_code($status);

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        if (ob_get_level() > 0) {
            $currentOutput = ob_get_contents();
            if (is_string($currentOutput) && trim($currentOutput) !== '') {
                error_log('JSON response buffer cleaned before AuthController response: ' . substr(trim($currentOutput), 0, 500));
            }
            ob_clean();
        }

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    private function absoluteUrl(string $path): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . u($path);
    }
}