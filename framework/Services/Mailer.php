<?php
declare(strict_types=1);

namespace Framework\Services;

/**
 * L'envoi de courriel, en un seul endroit.
 *
 * POURQUOI.
 *
 * Le framework n'avait aucune couche d'envoi. `AuthController` appelait
 * `mail()` en direct avec sa propre construction d'en-têtes ; la vérification
 * d'adresse et le 2FA, tous deux prévus dans `config/security.php`, n'ont
 * jamais été écrits — faute, précisément, de ce point d'envoi.
 *
 * Cette classe ne fait pas plus que le strict nécessaire : résoudre
 * l'expéditeur depuis les réglages, encoder correctement, appeler `mail()`.
 * Elle existe surtout pour qu'il n'y ait qu'UN seul endroit à modifier le jour
 * où SMTP authentifié sera nécessaire — ce que `mail()` ne sait pas faire, et
 * qui sera indispensable en hébergement mutualisé.
 *
 * SUR L'ÉCHEC. `mail()` renvoie `false` quand aucun agent de transport n'est
 * configuré — le cas courant sous WAMP. L'appelant DOIT tenir compte du
 * retour : dans le cas du 2FA, un code qu'on ne peut pas envoyer doit interdire
 * la connexion en le disant, jamais l'autoriser en silence.
 */
final class Mailer
{
    /** @var array<string,string> Réglages du site (site_name, expéditeur…). */
    private array $reglages;

    /** @param array<string,mixed> $reglages */
    public function __construct(array $reglages = [])
    {
        $this->reglages = array_map(
            static fn($v): string => is_scalar($v) ? (string) $v : '',
            $reglages
        );
    }

    /** Construit le service en lisant la table `settings`. */
    public static function depuisBase(?Database $db = null): self
    {
        $reglages = [];

        try {
            $db  = $db ?? ($GLOBALS['db'] ?? null);
            $pdo = $db?->getPDO();

            if ($pdo !== null) {
                $reglages = $pdo->query(
                    "SELECT param_key, param_value FROM settings WHERE param_key IN (
                        'site_name','webmaster_email',
                        'password_reset_from_email','password_reset_from_name'
                    )"
                )->fetchAll(\PDO::FETCH_KEY_PAIR) ?: [];
            }
        } catch (\Throwable $e) {
            // Base injoignable : on retombe sur les valeurs par défaut.
        }

        return new self($reglages);
    }

    /**
     * Envoie un message en texte brut.
     *
     * @return bool false si l'agent de transport a refusé le message. Le motif
     *              n'est pas disponible : `mail()` ne le donne pas.
     */
    public function envoyer(string $destinataire, string $sujet, string $corps): bool
    {
        if (!filter_var($destinataire, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Un saut de ligne dans le sujet permettrait d'injecter des en-têtes —
        // et donc d'ajouter des destinataires cachés à notre insu.
        $sujet = str_replace(["\r", "\n"], ' ', $sujet);

        [$adresse, $nom] = $this->expediteur();

        $entetes = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'From: ' . mb_encode_mimeheader($nom, 'UTF-8') . ' <' . $adresse . '>',
            'Reply-To: ' . $adresse,
            'X-Mailer: Aegis-Framework',
        ];

        return @mail(
            $destinataire,
            mb_encode_mimeheader($sujet, 'UTF-8'),
            $corps,
            implode("\r\n", $entetes)
        );
    }

    /**
     * L'expéditeur effectif : réglage dédié, puis adresse du webmestre, puis
     * une adresse construite sur le domaine. Une adresse invalide ferait
     * refuser le message par la plupart des serveurs receveurs.
     *
     * @return array{0:string,1:string} [adresse, nom]
     */
    public function expediteur(): array
    {
        $nomSite = trim($this->reglages['site_name'] ?? '') ?: 'Aegis Framework';

        $adresse = trim($this->reglages['password_reset_from_email'] ?? '');
        if ($adresse === '' || !filter_var($adresse, FILTER_VALIDATE_EMAIL)) {
            $adresse = trim($this->reglages['webmaster_email'] ?? '');
        }
        if ($adresse === '' || !filter_var($adresse, FILTER_VALIDATE_EMAIL)) {
            $hote = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
            $adresse = 'no-reply@' . ($hote ?: 'localhost');
        }

        $nom = trim($this->reglages['password_reset_from_name'] ?? '') ?: $nomSite;

        return [$adresse, $nom];
    }

    public function nomDuSite(): string
    {
        return trim($this->reglages['site_name'] ?? '') ?: 'Aegis Framework';
    }

    /**
     * L'envoi est-il seulement possible sur cette machine ?
     *
     * Sert à prévenir l'administrateur AVANT qu'il n'active le 2FA : sans
     * agent de transport, activer la double authentification revient à se
     * verrouiller dehors.
     */
    public static function transportDisponible(): bool
    {
        // Le contrôle ouvre une connexion réseau : sur une machine sans serveur
        // SMTP il coûte une seconde pleine, le temps que le délai d'attente
        // expire. La page de configuration le demande à chaque affichage, donc
        // on mémorise — l'état d'un agent de transport ne change pas d'une
        // minute à l'autre.
        if (function_exists('cache_get')) {
            $memo = cache_get('framework.mail.transport');
            if (is_bool($memo)) {
                return $memo;
            }
        }

        $verdict = self::sonderTransport();

        if (function_exists('cache_set')) {
            cache_set('framework.mail.transport', $verdict, 300);
        }

        return $verdict;
    }

    private static function sonderTransport(): bool
    {
        if (!function_exists('mail')) {
            return false;
        }

        if (stripos(PHP_OS_FAMILY, 'Windows') !== false) {
            $hote = trim((string) ini_get('SMTP'));
            if ($hote === '') {
                return false;
            }

            // Lire la directive ne suffit pas : WAMP livre `SMTP = localhost`
            // par défaut, sans que rien n'écoute. La page annonçait donc « envoi
            // configuré » sur une machine incapable d'expédier un message.
            // On ouvre vraiment la connexion — c'est le seul contrôle honnête.
            $port   = (int) (ini_get('smtp_port') ?: 25);
            $prise  = @fsockopen($hote, $port, $errno, $errstr, 1.0);

            if ($prise === false) {
                return false;
            }

            fclose($prise);
            return true;
        }

        // Ailleurs, `mail()` passe par un binaire sendmail : on vérifie que le
        // chemin est renseigné et que le programme existe réellement.
        $chemin = trim((string) ini_get('sendmail_path'));
        if ($chemin === '') {
            return false;
        }

        $binaire = strtok($chemin, ' ');

        return $binaire !== false && (is_executable($binaire) || is_file($binaire));
    }
}
