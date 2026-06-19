<?php
declare(strict_types=1);

require_once __DIR__ . '/Installer.php';

/**
 * InstallController — point d'entrée sécurisé unique des actions de l'installeur.
 *
 * Centralise :
 *  - le blocage si le CMS est déjà installé (installed.lock) ;
 *  - la vérification CSRF de TOUTES les actions mutantes ;
 *  - la validation des entrées et le hachage du mot de passe admin
 *    (jamais stocké en clair en session) ;
 *  - le dispatch des tâches d'installation.
 */
final class InstallController
{
    private Installer $installer;

    public function __construct(Installer $installer)
    {
        $this->installer = $installer;
    }

    /** Jeton CSRF de session (généré une fois). */
    public function csrfToken(): string
    {
        if (empty($_SESSION['install_csrf']) || !is_string($_SESSION['install_csrf'])) {
            $_SESSION['install_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['install_csrf'];
    }

    private function csrfValid(): bool
    {
        $token = $_POST['csrf']
            ?? $_GET['csrf']
            ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        return is_string($token)
            && $token !== ''
            && !empty($_SESSION['install_csrf'])
            && hash_equals((string) $_SESSION['install_csrf'], $token);
    }

    private function json(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Routeur d'actions sécurisé. */
    public function handle(string $action): void
    {
        // 1) Verrou : aucune action si déjà installé.
        if ($this->installer->isInstalled()) {
            $this->json(['success' => false, 'message' => 'Le CMS est déjà installé. Supprimez install/installed.lock pour réinstaller.']);
        }

        // 2) CSRF obligatoire sur toutes les actions.
        if (!$this->csrfValid()) {
            $this->json(['success' => false, 'message' => 'Jeton de sécurité invalide ou expiré. Rechargez la page.']);
        }

        switch ($action) {
            case 'test-db':    $this->testDb();    break;
            case 'save-admin': $this->saveAdmin(); break;
            case 'run':        $this->run();       break;
            default:           $this->json(['success' => false, 'message' => 'Action inconnue.']);
        }
    }

    private function testDb(): void
    {
        $db = [
            'host' => trim((string) ($_POST['host'] ?? 'localhost')),
            'port' => (int) ($_POST['port'] ?? 3306),
            'name' => trim((string) ($_POST['name'] ?? '')),
            'user' => trim((string) ($_POST['user'] ?? 'root')),
            'pass' => (string) ($_POST['pass'] ?? ''),
        ];
        $res = $this->installer->testConnection($db);
        if ($res['success']) {
            $_SESSION['install_db'] = $db;
        }
        $this->json($res);
    }

    private function saveAdmin(): void
    {
        $username = trim((string) ($_POST['username'] ?? ''));
        $email    = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirm  = (string) ($_POST['confirm'] ?? '');
        $siteName = trim((string) ($_POST['site_name'] ?? 'Aegis Framework'));

        if (strlen($username) < 3)                      { $this->json(['success' => false, 'message' => "Le nom d'utilisateur doit faire au moins 3 caractères."]); }
        if (!preg_match('/^[A-Za-z0-9_.-]{3,32}$/', $username)) { $this->json(['success' => false, 'message' => "Nom d'utilisateur invalide (3-32 caractères : lettres, chiffres, _ . -)."]); }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $this->json(['success' => false, 'message' => 'Adresse e-mail invalide.']); }
        if (strlen($password) < 8)                      { $this->json(['success' => false, 'message' => 'Le mot de passe doit faire au moins 8 caractères.']); }
        if ($password !== $confirm)                     { $this->json(['success' => false, 'message' => 'Les mots de passe ne correspondent pas.']); }
        if ($siteName === '')                           { $siteName = 'Aegis Framework'; }

        // SÉCURITÉ : on ne stocke JAMAIS le mot de passe en clair en session.
        // Il est haché immédiatement (Argon2id) ; seul le hash transite jusqu'à
        // la création du compte.
        $_SESSION['install_admin'] = [
            'username'      => $username,
            'email'         => $email,
            'site_name'     => $siteName,
            'password_hash' => password_hash($password, PASSWORD_ARGON2ID),
        ];
        $this->json(['success' => true, 'message' => 'Compte administrateur validé.']);
    }

    private function run(): void
    {
        $db    = $_SESSION['install_db'] ?? null;
        $admin = $_SESSION['install_admin'] ?? null;
        if (!is_array($db) || !is_array($admin)) {
            $this->json(['success' => false, 'message' => 'Données manquantes : reprenez les étapes Base de données et Administrateur.']);
        }

        $task = (string) ($_GET['task'] ?? '');
        $res = match ($task) {
            'database' => $this->installer->createDatabase($db),
            'schema'   => $this->installer->runSchema($db),
            'admin'    => $this->installer->createAdmin($db, $admin),
            'modules'  => $this->installer->seedDefaults($db, $admin),
            'seed'     => $this->installer->runSeed($db),
            'env'      => $this->installer->writeEnv($db, $admin),
            'finalize' => $this->finalizeAndCleanup(),
            default    => ['success' => false, 'message' => 'Tâche inconnue.'],
        };
        $this->json($res);
    }

    /** Finalise puis purge les secrets de session. */
    private function finalizeAndCleanup(): array
    {
        $res = $this->installer->finalize();
        if ($res['success']) {
            unset($_SESSION['install_db'], $_SESSION['install_admin'], $_SESSION['install_csrf']);
        }
        return $res;
    }
}
