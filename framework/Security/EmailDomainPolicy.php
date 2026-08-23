<?php
declare(strict_types=1);

namespace Framework\Security;

use Framework\Services\Database;

/**
 * Filtrage des domaines d'adresse e-mail à l'inscription.
 *
 * POURQUOI UNE LISTE BLANCHE ET NON UNE LISTE NOIRE.
 *
 * Interdire les fournisseurs jetables demande de tous les connaître. Il en
 * naît chaque semaine, la liste grossit sans fin, et elle est périmée le jour
 * où on la fige — c'est l'impasse qu'a rencontrée eSport-CMS V3.
 *
 * Une liste blanche renverse la charge : une trentaine de domaines couvrent la
 * quasi-totalité des inscrits réels, et tout ce qui naîtra demain est refusé
 * sans qu'on ait à le savoir. La liste ne grossit plus toute seule.
 *
 * SON DÉFAUT, QU'IL FAUT ASSUMER. Une adresse sur un domaine propre —
 * `contact@lavelia-studio.com` — est refusée elle aussi. C'est pourquoi la
 * liste est modifiable depuis l'administration : on y ajoute son domaine, ceux
 * de ses partenaires, ceux d'une école ou d'une entreprise. Et un
 * administrateur peut toujours créer un compte à la main, ce filtre ne
 * s'appliquant qu'à l'inscription publique.
 *
 * Désactivé par défaut : un site qui vient d'être installé ne doit pas refuser
 * des inscriptions à cause d'un réglage que personne n'a demandé.
 */
final class EmailDomainPolicy
{
    /**
     * La liste proposée à la première activation.
     *
     * Les messageries respectueuses de la vie privée (Proton, Tuta) y figurent :
     * ce sont de vrais fournisseurs, pas des adresses jetables. Les confondre
     * reviendrait à pénaliser des utilisateurs légitimes.
     */
    public const DEFAUTS = [
        // Généralistes
        'gmail.com', 'googlemail.com',
        'outlook.com', 'outlook.fr', 'hotmail.com', 'hotmail.fr',
        'live.com', 'live.fr', 'msn.com',
        'yahoo.com', 'yahoo.fr', 'ymail.com',
        'icloud.com', 'me.com', 'mac.com',
        'aol.com', 'gmx.com', 'gmx.fr', 'mail.com', 'zoho.com',
        // Fournisseurs d'accès français
        'orange.fr', 'wanadoo.fr', 'free.fr', 'sfr.fr', 'neuf.fr',
        'laposte.net', 'bbox.fr', 'numericable.fr', 'aliceadsl.fr',
        // Messageries chiffrées
        'protonmail.com', 'proton.me', 'pm.me', 'tutanota.com', 'tuta.com',
    ];

    private bool $actif;

    /** @var string[] */
    private array $autorises;

    /** @param string[] $autorises */
    public function __construct(bool $actif, array $autorises)
    {
        $this->actif     = $actif;
        $this->autorises = $autorises;
    }

    /** Construit la politique depuis les réglages enregistrés. */
    public static function depuisBase(?Database $db = null): self
    {
        $actif  = false;
        $liste  = [];

        try {
            $db  = $db ?? ($GLOBALS['db'] ?? null);
            $pdo = $db?->getPDO();

            if ($pdo !== null) {
                $r = $pdo->query(
                    "SELECT param_key, param_value FROM settings
                     WHERE param_key IN ('email_domains_enabled','email_domains_allowed')"
                )->fetchAll(\PDO::FETCH_KEY_PAIR) ?: [];

                $actif = ($r['email_domains_enabled'] ?? '0') === '1';
                $liste = self::analyser((string) ($r['email_domains_allowed'] ?? ''));
            }
        } catch (\Throwable $e) {
            // Réglage illisible : on n'active rien. Un filtre qu'on ne sait pas
            // lire ne doit jamais bloquer les inscriptions par accident.
        }

        return new self($actif, $liste);
    }

    /**
     * Découpe une saisie libre en domaines.
     *
     * On accepte les retours à la ligne, les virgules, les points-virgules et
     * les espaces : personne ne doit avoir à deviner le séparateur attendu.
     * Une adresse complète collée par mégarde est réduite à son domaine.
     *
     * @return string[]
     */
    public static function analyser(string $brut): array
    {
        $morceaux = preg_split('/[\s,;]+/', mb_strtolower(trim($brut))) ?: [];
        $out = [];

        foreach ($morceaux as $m) {
            $m = trim($m);
            if ($m === '') { continue; }

            // « jean@gmail.com » → « gmail.com » ; « @gmail.com » aussi.
            if (str_contains($m, '@')) {
                $m = substr($m, strrpos($m, '@') + 1);
            }

            $m = trim($m, ".\t\n\r ");
            if ($m === '' || !str_contains($m, '.')) { continue; }

            $out[$m] = true;
        }

        return array_keys($out);
    }

    /** Le filtre est-il en service ? */
    public function actif(): bool
    {
        // Une liste vide bloquerait toutes les inscriptions : on considère
        // alors le filtre comme éteint plutôt que de fermer le site.
        return $this->actif && $this->autorises !== [];
    }

    /** @return string[] */
    public function autorises(): array
    {
        return $this->autorises;
    }

    /**
     * L'adresse est-elle acceptable ?
     *
     * @return string|null Le motif du refus, ou null si l'adresse convient.
     */
    public function refus(string $email): ?string
    {
        if (!$this->actif()) {
            return null;
        }

        $domaine = $this->domaine($email);
        if ($domaine === null) {
            return null; // Adresse malformée : c'est la validation d'e-mail qui le dira.
        }

        if (in_array($domaine, $this->autorises, true)) {
            return null;
        }

        // Le sous-domaine d'un domaine autorisé passe aussi : une école qui
        // ouvre `etudiants.exemple.fr` n'a pas à être ajoutée deux fois.
        foreach ($this->autorises as $permis) {
            if (str_ends_with($domaine, '.' . $permis)) {
                return null;
            }
        }

        return sprintf(
            'Les adresses en « %s » ne sont pas acceptées. Utilisez une adresse personnelle courante (Gmail, Outlook, Orange, Free…).',
            $domaine
        );
    }

    private function domaine(string $email): ?string
    {
        $email = mb_strtolower(trim($email));
        $pos   = strrpos($email, '@');

        if ($pos === false) {
            return null;
        }

        $domaine = substr($email, $pos + 1);

        return $domaine !== '' ? $domaine : null;
    }
}
