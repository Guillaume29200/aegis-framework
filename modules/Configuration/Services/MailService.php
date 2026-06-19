<?php
declare(strict_types=1);

namespace Configuration\Services;

use Framework\Services\Database;

/**
 * MailService — réglages d'e-mail (expéditeur + gabarit de réinitialisation
 * de mot de passe). Service dédié, extrait du ConfigurationController.
 */
class MailService
{
    private SettingsService $settings;

    public function __construct(Database $db)
    {
        $this->settings = new SettingsService($db);
    }

    /** @return array<string,string> */
    public function getConfig(): array
    {
        return [
            'from_email' => (string) $this->settings->get('password_reset_from_email', ''),
            'from_name'  => (string) $this->settings->get('password_reset_from_name', ''),
            'subject'    => (string) $this->settings->get('password_reset_email_subject', ''),
            'body'       => (string) $this->settings->get('password_reset_email_body', ''),
        ];
    }

    /**
     * @return array{success:bool, message:string}
     */
    public function save(array $post): array
    {
        $email = trim((string)($post['password_reset_from_email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => "L'adresse e-mail expéditeur est invalide."];
        }

        $ok = $this->settings->setMultiple([
            'password_reset_from_email'    => ['value' => $email, 'type' => 'string'],
            'password_reset_from_name'     => ['value' => trim((string)($post['password_reset_from_name'] ?? '')), 'type' => 'string'],
            'password_reset_email_subject' => ['value' => trim((string)($post['password_reset_email_subject'] ?? '')), 'type' => 'string'],
            'password_reset_email_body'    => ['value' => trim((string)($post['password_reset_email_body'] ?? '')), 'type' => 'string'],
        ]);

        return $ok
            ? ['success' => true, 'message' => 'Paramètres e-mail enregistrés avec succès.']
            : ['success' => false, 'message' => 'Erreur lors de la sauvegarde.'];
    }
}
