<?php
declare(strict_types=1);

namespace Auth\Services;

use Framework\Services\Database;
use Framework\Services\Mailer;

/**
 * Double authentification par e-mail.
 *
 * Un mot de passe volé ne suffit plus : il faut aussi accéder à la boîte de
 * réception. C'est la seule protection qui tienne face à une fuite de mots de
 * passe venue d'ailleurs — la réutilisation d'un mot de passe entre deux sites
 * reste la première cause de compromission de compte.
 *
 * PAR E-MAIL, ET PAR E-MAIL SEULEMENT. Pas de SMS : c'est payant, cela demande
 * un prestataire, et le détournement de carte SIM en fait le facteur le plus
 * faible — le NIST l'a d'ailleurs déconseillé (SP 800-63B). L'e-mail ne
 * demande rien de plus que ce que le site sait déjà faire.
 *
 * LE CODE DE SECOURS, ET POURQUOI IL EST INDISPENSABLE.
 *
 * Si le 2FA est actif et que l'envoi d'e-mail tombe — serveur SMTP en panne,
 * boîte inaccessible, domaine expiré — plus personne n'entre. Y compris
 * l'administrateur, qui ne peut donc plus atteindre la page qui permettrait de
 * désactiver la protection. Le remède ne peut pas vivre derrière la porte
 * qu'il est censé ouvrir.
 *
 * UN SEUL code de secours est donc produit AU MOMENT DE L'ACTIVATION et
 * affiché une fois, à noter. Il s'utilise depuis la page de vérification
 * elle-même : il désactive le 2FA pour le site et laisse entrer. Il n'est
 * stocké que sous forme d'empreinte, se consomme à l'usage, et l'événement
 * part au journal d'audit — un code de secours employé est toujours un
 * incident, qu'il soit légitime ou non.
 *
 * Pas de lot de dix codes comme le prévoyait l'ancienne configuration : dix
 * secrets permanents à conserver valent moins qu'un seul qu'on note vraiment.
 * Pas de TOTP non plus, qui demanderait une application tierce.
 */
final class TwoFactorService
{
    /** Durée de vie par défaut, en minutes. */
    private const TTL_DEFAUT = 10;

    /** Au-delà, le code est brûlé : sinon 6 chiffres se devinent en 10^6 essais. */
    private const TENTATIVES_MAX = 5;

    /** Délai minimal entre deux envois, en secondes. */
    private const DELAI_RENVOI = 60;

    private Database $db;
    private ?Mailer $mailer;

    public function __construct(Database $db, ?Mailer $mailer = null)
    {
        $this->db     = $db;
        $this->mailer = $mailer;
    }

    // ── Réglages ─────────────────────────────────────────────────────────

    /** La double authentification est-elle active sur ce site ? */
    public function actif(): bool
    {
        return $this->reglage('twofa_enabled', '0') === '1';
    }

    /**
     * Ce compte doit-il passer par un second facteur ?
     *
     * @param array<string,mixed> $utilisateur
     */
    public function requisPour(array $utilisateur): bool
    {
        if (!$this->actif()) {
            return false;
        }

        // Sans adresse valide, il n'y a nulle part où envoyer le code. Exiger
        // un second facteur qu'on ne peut pas délivrer reviendrait à interdire
        // la connexion.
        $email = trim((string) ($utilisateur['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if ($this->reglage('twofa_scope', 'admins') === 'all') {
            return true;
        }

        return in_array((string) ($utilisateur['role'] ?? ''), ['admin', 'superadmin'], true);
    }

    public function dureeMinutes(): int
    {
        return max(2, min(60, (int) $this->reglage('twofa_ttl_minutes', (string) self::TTL_DEFAUT)));
    }

    // ── Émission ─────────────────────────────────────────────────────────

    /**
     * Produit un code, l'enregistre et l'envoie.
     *
     * @param array<string,mixed> $utilisateur
     * @return array{success:bool, message:string}
     */
    public function emettre(array $utilisateur): array
    {
        $id = (int) ($utilisateur['id'] ?? 0);
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Compte introuvable.'];
        }

        if (($reste = $this->secondesAvantRenvoi($id)) > 0) {
            return [
                'success' => false,
                'message' => sprintf('Un code vient d\'être envoyé. Patientez %d secondes avant d\'en demander un autre.', $reste),
            ];
        }

        // random_int, jamais rand() : le second facteur doit être imprévisible
        // même en connaissant les codes précédents.
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Les codes encore vivants de ce compte sont périmés d'office : un seul
        // code valide à la fois, sinon chaque renvoi élargit la cible.
        $this->db->execute(
            "UPDATE auth_2fa_codes SET expire_le = NOW() WHERE user_id = ? AND utilise_le IS NULL AND expire_le > NOW()",
            [$id]
        );

        $this->db->execute(
            "INSERT INTO auth_2fa_codes (user_id, code_hash, expire_le, ip)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), ?)",
            [$id, hash('sha256', $code), $this->dureeMinutes(), $_SERVER['REMOTE_ADDR'] ?? null]
        );

        if (!$this->envoyer($utilisateur, $code)) {
            // L'envoi a échoué : on le dit franchement. Laisser entrer sans le
            // second facteur annulerait la protection ; se taire laisserait
            // l'utilisateur attendre un message qui n'arrivera jamais.
            return [
                'success' => false,
                'message' => "Le code n'a pas pu être envoyé. L'envoi d'e-mail n'est pas configuré sur ce serveur — prévenez l'administrateur.",
            ];
        }

        return [
            'success' => true,
            'message' => sprintf('Un code à 6 chiffres vient d\'être envoyé à %s.', $this->emailMasque((string) $utilisateur['email'])),
        ];
    }

    // ── Vérification ─────────────────────────────────────────────────────

    /**
     * @return array{success:bool, message:string, brule?:bool}
     */
    public function verifier(int $userId, string $saisie): array
    {
        $saisie = preg_replace('/\D/', '', $saisie) ?? '';

        if (strlen($saisie) !== 6) {
            return ['success' => false, 'message' => 'Le code comporte 6 chiffres.'];
        }

        $ligne = $this->db->queryOne(
            "SELECT id, code_hash, tentatives FROM auth_2fa_codes
             WHERE user_id = ? AND utilise_le IS NULL AND expire_le > NOW()
             ORDER BY id DESC LIMIT 1",
            [$userId]
        );

        if (!$ligne) {
            return ['success' => false, 'message' => 'Ce code a expiré. Demandez-en un nouveau.', 'brule' => true];
        }

        if ((int) $ligne['tentatives'] >= self::TENTATIVES_MAX) {
            $this->db->execute("UPDATE auth_2fa_codes SET expire_le = NOW() WHERE id = ?", [$ligne['id']]);
            return ['success' => false, 'message' => 'Trop de tentatives. Demandez un nouveau code.', 'brule' => true];
        }

        // hash_equals : la comparaison dure le même temps quel que soit le
        // nombre de caractères justes, ce qui interdit de retrouver le code
        // chiffre par chiffre en mesurant les temps de réponse.
        if (!hash_equals((string) $ligne['code_hash'], hash('sha256', $saisie))) {
            $this->db->execute("UPDATE auth_2fa_codes SET tentatives = tentatives + 1 WHERE id = ?", [$ligne['id']]);
            $restantes = self::TENTATIVES_MAX - ((int) $ligne['tentatives'] + 1);

            return [
                'success' => false,
                'message' => $restantes > 0
                    ? sprintf('Code incorrect. Il vous reste %d tentative%s.', $restantes, $restantes > 1 ? 's' : '')
                    : 'Code incorrect. Demandez un nouveau code.',
                'brule'   => $restantes <= 0,
            ];
        }

        // Consommé : un code ne sert qu'une fois, même dans sa fenêtre de vie.
        $this->db->execute("UPDATE auth_2fa_codes SET utilise_le = NOW() WHERE id = ?", [$ligne['id']]);
        $this->purger();

        return ['success' => true, 'message' => 'Code vérifié.'];
    }

    // ── Code de secours ──────────────────────────────────────────────────

    /**
     * Produit un nouveau code de secours et retourne sa forme lisible.
     *
     * La valeur en clair n'est retournée QU'ICI : seule son empreinte est
     * conservée. Si l'administrateur ne la note pas, il faudra en régénérer un.
     */
    public function genererCodeDeSecours(): string
    {
        // Alphabet sans 0/O ni 1/I/L : ce code se recopie à la main depuis un
        // bout de papier, souvent des mois plus tard.
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $groupes  = [];

        for ($g = 0; $g < 4; $g++) {
            $bloc = '';
            for ($i = 0; $i < 4; $i++) {
                $bloc .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $groupes[] = $bloc;
        }

        $code = implode('-', $groupes);

        $this->ecrireReglage('twofa_recovery_hash', password_hash($code, PASSWORD_DEFAULT));
        $this->ecrireReglage('twofa_recovery_at', (string) time());

        return $code;
    }

    /** Un code de secours est-il en place ? */
    public function codeDeSecoursExiste(): bool
    {
        return $this->reglage('twofa_recovery_hash', '') !== '';
    }

    /**
     * Consomme un code de secours : désactive le 2FA et laisse entrer.
     *
     * @return array{success:bool, message:string}
     */
    public function utiliserCodeDeSecours(string $saisie): array
    {
        $empreinte = $this->reglage('twofa_recovery_hash', '');
        if ($empreinte === '') {
            return ['success' => false, 'message' => 'Aucun code de secours n\'a été généré sur ce site.'];
        }

        // Normalisation : on accepte les minuscules et l'absence de tirets,
        // parce que le code est recopié à la main.
        $propre = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $saisie) ?? '');
        if (strlen($propre) !== 16) {
            return ['success' => false, 'message' => 'Le code de secours comporte 16 caractères.'];
        }

        $canonique = implode('-', str_split($propre, 4));

        if (!password_verify($canonique, $empreinte)) {
            return ['success' => false, 'message' => 'Code de secours invalide.'];
        }

        // Consommé, et la protection retombe : l'administrateur doit repasser
        // par la configuration, ce qui l'oblige à constater le problème d'envoi
        // avant de réactiver.
        $this->ecrireReglage('twofa_recovery_hash', '');
        $this->ecrireReglage('twofa_enabled', '0');

        try {
            \Framework\Services\AuditService::record(
                'auth.2fa_recovery_used',
                'settings',
                'twofa_enabled',
                'Code de secours 2FA utilisé — la double authentification a été désactivée.'
            );
        } catch (\Throwable $e) {
            // L'audit ne doit pas empêcher de reprendre la main.
        }

        return [
            'success' => true,
            'message' => 'Code accepté. La double authentification a été désactivée : reconfigurez-la depuis Configuration → 2FA.',
        ];
    }

    // ── Envoi de test, pour l'administration ─────────────────────────────

    /** @return array{success:bool, message:string} */
    public function envoiDeTest(string $destinataire): array
    {
        $mailer = $this->mailer ?? Mailer::depuisBase($this->db);

        if (!filter_var($destinataire, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Adresse de destination invalide.'];
        }

        $site = $mailer->nomDuSite();
        $ok = $mailer->envoyer(
            $destinataire,
            sprintf('[%s] Test d\'envoi', $site),
            "Ceci est un message de test envoyé depuis {$site}.\n\n"
          . "Si vous le recevez, l'envoi d'e-mail fonctionne et la double\n"
          . "authentification peut être activée sans risque.\n\n"
          . 'Émis le ' . date('d/m/Y à H:i') . ".\n"
        );

        if (!$ok) {
            [$de] = $mailer->expediteur();
            return [
                'success' => false,
                'message' => "L'envoi a échoué (expéditeur : {$de}). "
                           . (Mailer::transportDisponible()
                              ? 'Le serveur a refusé le message — vérifiez la configuration de l\'agent de transport.'
                              : 'Aucun agent de transport n\'est configuré dans php.ini (directive SMTP sous Windows, sendmail_path sinon).'),
            ];
        }

        return ['success' => true, 'message' => "Message envoyé à {$destinataire}. Vérifiez la réception, y compris les indésirables."];
    }

    // ── Interne ──────────────────────────────────────────────────────────

    /** @param array<string,mixed> $utilisateur */
    private function envoyer(array $utilisateur, string $code): bool
    {
        $mailer  = $this->mailer ?? Mailer::depuisBase($this->db);
        $site    = $mailer->nomDuSite();
        $minutes = $this->dureeMinutes();
        $pseudo  = trim((string) ($utilisateur['username'] ?? ''));

        $corps = ($pseudo !== '' ? "Bonjour {$pseudo}," : 'Bonjour,') . "\n\n"
               . "Voici votre code de connexion à {$site} :\n\n"
               . "    {$code}\n\n"
               . "Il expire dans {$minutes} minutes et ne peut servir qu'une fois.\n\n"
               . "Si vous n'êtes pas à l'origine de cette connexion, quelqu'un connaît\n"
               . "votre mot de passe : changez-le sans attendre.\n\n"
               . 'Demandé depuis l\'adresse ' . ($_SERVER['REMOTE_ADDR'] ?? 'inconnue')
               . ' le ' . date('d/m/Y à H:i') . ".\n\n"
               . $site;

        return $mailer->envoyer(
            (string) $utilisateur['email'],
            sprintf('%s — code de connexion : %s', $site, $code),
            $corps
        );
    }

    /** Secondes restantes avant qu'un nouvel envoi soit permis. */
    private function secondesAvantRenvoi(int $userId): int
    {
        $ligne = $this->db->queryOne(
            "SELECT TIMESTAMPDIFF(SECOND, cree_le, NOW()) AS ecoule
             FROM auth_2fa_codes WHERE user_id = ? ORDER BY id DESC LIMIT 1",
            [$userId]
        );

        if (!$ligne) {
            return 0;
        }

        return max(0, self::DELAI_RENVOI - (int) $ligne['ecoule']);
    }

    /**
     * Les codes périmés depuis plus d'un jour ne servent plus à rien.
     * Appelé après une vérification réussie : pas de tâche planifiée à prévoir.
     */
    private function purger(): void
    {
        try {
            $this->db->execute("DELETE FROM auth_2fa_codes WHERE expire_le < DATE_SUB(NOW(), INTERVAL 1 DAY)");
        } catch (\Throwable $e) {
            // Le ménage n'est pas critique.
        }
    }

    /** Écrit un réglage, en créant la ligne si elle n'existe pas encore. */
    private function ecrireReglage(string $cle, string $valeur): void
    {
        $this->db->execute(
            "INSERT INTO settings (param_key, param_value, param_type) VALUES (?, ?, 'string')
             ON DUPLICATE KEY UPDATE param_value = VALUES(param_value)",
            [$cle, $valeur]
        );
    }

    private function reglage(string $cle, string $defaut): string
    {
        try {
            $ligne = $this->db->queryOne("SELECT param_value FROM settings WHERE param_key = ?", [$cle]);
            if ($ligne && $ligne['param_value'] !== null && $ligne['param_value'] !== '') {
                return (string) $ligne['param_value'];
            }
        } catch (\Throwable $e) {
            // Réglage absent : on garde le défaut, qui désactive le 2FA.
        }

        return $defaut;
    }

    /** `jean.dupont@example.com` → `j••••••••t@example.com`. */
    private function emailMasque(string $email): string
    {
        [$local, $domaine] = array_pad(explode('@', $email, 2), 2, '');

        if ($domaine === '' || mb_strlen($local) < 2) {
            return $email;
        }

        return mb_substr($local, 0, 1)
             . str_repeat('•', max(1, mb_strlen($local) - 2))
             . mb_substr($local, -1) . '@' . $domaine;
    }
}
