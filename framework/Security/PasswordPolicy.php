<?php
declare(strict_types=1);

namespace Framework\Security;

/**
 * La politique de mot de passe, appliquée pour de bon.
 *
 * POURQUOI CETTE CLASSE EXISTE.
 *
 * `config/security.php` annonçait douze caractères, une majuscule, un chiffre
 * et un caractère spécial. Aucune de ces clés n'était lue nulle part : la
 * seule règle réellement appliquée était un `strlen($password) < 8` écrit deux
 * fois dans `AuthService`, à l'inscription et à la réinitialisation. `motdepas`
 * passait. Une configuration décorative est pire que pas de configuration —
 * elle donne une assurance que le code ne tient pas.
 *
 * La règle vit désormais ici, et nulle part ailleurs. Les deux points d'entrée
 * appellent la même méthode, donc ils ne peuvent plus diverger.
 *
 * CE QUI A ÉTÉ ÉCARTÉ, ET POURQUOI.
 *
 * `max_age_days` (changement forcé tous les 90 jours) et `prevent_reuse` (les
 * 5 derniers interdits) figuraient aussi dans la configuration. Ils ont été
 * retirés plutôt qu'implémentés : la rotation périodique imposée est
 * explicitement déconseillée par le NIST (SP 800-63B, §5.1.1.2), parce qu'elle
 * pousse à décliner un même mot de passe — `Ete2026!`, puis `Ete2026!!` — ce
 * qui affaiblit au lieu de renforcer. Les implémenter aurait demandé une table
 * d'historique et un parcours de changement forcé, pour un résultat négatif.
 */
final class PasswordPolicy
{
    private int  $longueurMin;
    private bool $majuscule;
    private bool $minuscule;
    private bool $chiffre;
    private bool $special;
    private bool $verifierFuites;

    /** Plafond : au-delà, Argon2id travaille pour rien et c'est un levier de déni de service. */
    private const LONGUEUR_MAX = 200;

    public function __construct(array $regles = [])
    {
        $this->longueurMin    = max(8, (int) ($regles['min_length'] ?? 12));
        $this->majuscule      = (bool) ($regles['require_uppercase'] ?? true);
        $this->minuscule      = (bool) ($regles['require_lowercase'] ?? true);
        $this->chiffre        = (bool) ($regles['require_numbers']   ?? true);
        $this->special        = (bool) ($regles['require_special']   ?? true);
        $this->verifierFuites = (bool) ($regles['check_compromised'] ?? false);
    }

    /**
     * Construit la politique depuis `config/security.php`.
     *
     * Le fichier est lu directement plutôt que pris dans une variable globale :
     * la classe reste utilisable en ligne de commande et dans un test, sans
     * amorcer tout le framework.
     */
    public static function depuisConfig(?array $config = null): self
    {
        if ($config === null) {
            $chemin = (defined('ROOT_PATH') ? rtrim(ROOT_PATH, "/\\") : dirname(__DIR__, 2))
                    . '/framework/config/security.php';
            $config = is_file($chemin) ? (array) require $chemin : [];
        }

        return new self((array) ($config['password'] ?? []));
    }

    /**
     * Contrôle un mot de passe.
     *
     * @return string[] Les manquements, dans l'ordre de lecture. Tableau vide
     *                  si le mot de passe convient.
     */
    public function verifier(string $motDePasse): array
    {
        $fautes = [];

        // mb_strlen : « é » compte pour un caractère, pas pour deux. Sans quoi
        // une phrase de passe accentuée passerait la longueur sans la remplir.
        $longueur = mb_strlen($motDePasse);

        if ($longueur < $this->longueurMin) {
            $fautes[] = sprintf('Le mot de passe doit contenir au moins %d caractères.', $this->longueurMin);
        }
        if ($longueur > self::LONGUEUR_MAX) {
            $fautes[] = sprintf('Le mot de passe ne peut pas dépasser %d caractères.', self::LONGUEUR_MAX);
        }

        if ($this->majuscule && !preg_match('/\p{Lu}/u', $motDePasse)) {
            $fautes[] = 'Il doit contenir au moins une majuscule.';
        }
        if ($this->minuscule && !preg_match('/\p{Ll}/u', $motDePasse)) {
            $fautes[] = 'Il doit contenir au moins une minuscule.';
        }
        if ($this->chiffre && !preg_match('/\d/', $motDePasse)) {
            $fautes[] = 'Il doit contenir au moins un chiffre.';
        }
        // Tout ce qui n'est ni lettre ni chiffre ni espace : la liste blanche de
        // symboles est un piège, elle refuse « £ » ou « € » sans raison.
        if ($this->special && !preg_match('/[^\p{L}\p{N}\s]/u', $motDePasse)) {
            $fautes[] = 'Il doit contenir au moins un caractère spécial.';
        }

        // Inutile d'interroger un service distant pour un mot de passe déjà
        // refusé sur sa forme.
        if ($fautes === [] && $this->verifierFuites && $this->estCompromis($motDePasse)) {
            $fautes[] = 'Ce mot de passe figure dans une fuite de données connue. Choisissez-en un autre.';
        }

        return $fautes;
    }

    /** La règle, en une phrase, pour l'afficher sous le champ. */
    public function enonce(): string
    {
        $exigences = [];
        if ($this->majuscule) { $exigences[] = 'une majuscule'; }
        if ($this->minuscule) { $exigences[] = 'une minuscule'; }
        if ($this->chiffre)   { $exigences[] = 'un chiffre'; }
        if ($this->special)   { $exigences[] = 'un caractère spécial'; }

        $phrase = sprintf('%d caractères minimum', $this->longueurMin);

        if ($exigences !== []) {
            $dernier = array_pop($exigences);
            $phrase .= ', dont ' . ($exigences ? implode(', ', $exigences) . ' et ' . $dernier : $dernier);
        }

        return $phrase . '.';
    }

    public function longueurMinimale(): int
    {
        return $this->longueurMin;
    }

    /**
     * Le mot de passe figure-t-il dans les fuites connues ?
     *
     * Protocole k-anonymity de Have I Been Pwned : on n'envoie que les CINQ
     * PREMIERS caractères de l'empreinte SHA-1. Le service renvoie tous les
     * suffixes commençant par ce préfixe — plusieurs centaines — et la
     * comparaison se fait ici. Le mot de passe, son empreinte complète et
     * l'identité de l'utilisateur ne quittent jamais le serveur.
     *
     * En cas d'échec réseau on répond « non compromis » : un service distant
     * indisponible ne doit pas empêcher quelqu'un de créer son compte.
     */
    private function estCompromis(string $motDePasse): bool
    {
        $empreinte = strtoupper(sha1($motDePasse));
        $prefixe   = substr($empreinte, 0, 5);
        $suffixe   = substr($empreinte, 5);

        $flux = @file_get_contents(
            'https://api.pwnedpasswords.com/range/' . $prefixe,
            false,
            stream_context_create(['http' => [
                'timeout'       => 3,
                'ignore_errors' => true,
                'header'        => "User-Agent: Aegis-Framework\r\n",
            ]])
        );

        if (!is_string($flux) || $flux === '') {
            return false;
        }

        foreach (explode("\n", $flux) as $ligne) {
            if (strncmp(trim($ligne), $suffixe, 35) === 0) {
                return true;
            }
        }

        return false;
    }
}
