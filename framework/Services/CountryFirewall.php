<?php
declare(strict_types=1);

namespace Framework\Services;

/**
 * Filtrage géographique des visiteurs.
 *
 * Deux modes, parce qu'ils répondent à deux intentions opposées :
 *
 *   • LISTE NOIRE — tout le monde entre, sauf les pays désignés. Le réflexe
 *     habituel : on ferme la porte aux origines d'où viennent les ennuis.
 *
 *   • LISTE BLANCHE — personne n'entre, sauf les pays désignés. Bien plus
 *     étanche pour un site dont le public est connu d'avance (une association
 *     française, une ligue européenne) : ce qui n'est pas prévu est refusé,
 *     sans avoir à énumérer le reste du monde.
 *
 * CE QUI NE DOIT JAMAIS ARRIVER — et les trois garde-fous correspondants.
 *
 *   1. Un pays inconnu n'est JAMAIS un motif de refus. Base absente, adresse
 *      IPv6, plage non attribuée : on laisse passer. Sans cela, une base
 *      manquante fermerait le site à la terre entière, en liste blanche.
 *
 *   2. Les adresses de confiance passent toujours — 127.0.0.1 en tête. On
 *      s'appuie sur la liste blanche du pare-feu, déjà en place.
 *
 *   3. Un administrateur connecté n'est jamais refusé. C'est ce qui permet de
 *      corriger une erreur de réglage depuis l'endroit où on l'a commise.
 *
 * S'y ajoute, côté enregistrement, le refus d'une configuration qui bloquerait
 * le pays de l'administrateur lui-même : voir `seTirerUneBalle()`.
 */
final class CountryFirewall
{
    public const MODE_INACTIF = 'off';
    public const MODE_NOIRE   = 'block';
    public const MODE_BLANCHE = 'allow';

    private const CLE_CACHE = 'framework.geo.reglages';
    private const TTL_CACHE = 600;

    private Database $db;
    private CountryDatabase $base;

    /** @var array{actif:bool, mode:string, pays:string[]}|null */
    private ?array $reglages = null;

    public function __construct(Database $db, ?CountryDatabase $base = null)
    {
        $this->db   = $db;
        $this->base = $base ?? new CountryDatabase();
    }

    public function base(): CountryDatabase
    {
        return $this->base;
    }

    // ── Réglages ─────────────────────────────────────────────────────────

    /** @return array{actif:bool, mode:string, pays:string[]} */
    public function reglages(): array
    {
        if ($this->reglages !== null) {
            return $this->reglages;
        }

        // Ces trois réglages sont lus sur CHAQUE requête, y compris quand le
        // filtre est à l'arrêt. La requête SQL coûtait alors 1 ms par page
        // pour répondre « désactivé » : on la mémorise, et l'enregistrement
        // vide le cache pour que le réglage prenne effet aussitôt.
        if (function_exists('cache_get')) {
            $memo = cache_get(self::CLE_CACHE);
            if (is_array($memo) && isset($memo['actif'], $memo['mode'], $memo['pays'])) {
                return $this->reglages = $memo;
            }
        }

        // L'interrupteur est séparé du mode à dessein : couper la protection ne
        // doit pas faire perdre la liste de pays patiemment constituée, et la
        // remettre en service doit être l'affaire d'un seul geste.
        $actif = false;
        $mode  = self::MODE_NOIRE;
        $pays  = [];

        try {
            // `security_settings`, et non `settings` : ce réglage appartient au
            // Centre de sécurité, qui l'écrit par `SecurityCenterService`.
            // Lire l'autre table faisait que l'enregistrement n'avait aucun
            // effet — le filtre restait éteint quoi qu'on coche.
            $lignes = $this->db->query(
                "SELECT param_key, param_value FROM security_settings
                 WHERE param_key IN ('geo_enabled','geo_mode','geo_countries')"
            );

            foreach ($lignes as $l) {
                switch ($l['param_key']) {
                    case 'geo_enabled':   $actif = (string) $l['param_value'] === '1'; break;
                    case 'geo_mode':      $mode  = (string) $l['param_value']; break;
                    case 'geo_countries': $pays  = self::analyser((string) $l['param_value']); break;
                }
            }
        } catch (\Throwable $e) {
            // Réglages illisibles : on n'active rien.
        }

        // `off` a existé comme mode avant l'interrupteur : on le lit encore
        // pour qu'une installation antérieure ne se réveille pas activée.
        if ($mode === self::MODE_INACTIF) {
            $actif = false;
            $mode  = self::MODE_NOIRE;
        }

        if (!in_array($mode, [self::MODE_NOIRE, self::MODE_BLANCHE], true)) {
            $mode = self::MODE_NOIRE;
        }

        $reglages = ['actif' => $actif, 'mode' => $mode, 'pays' => $pays];

        if (function_exists('cache_set')) {
            cache_set(self::CLE_CACHE, $reglages, self::TTL_CACHE);
        }

        return $this->reglages = $reglages;
    }

    /** À appeler après tout enregistrement, sans quoi le réglage traînerait. */
    public function oublier(): void
    {
        $this->reglages = null;

        if (function_exists('cache_delete')) {
            try { cache_delete(self::CLE_CACHE); } catch (\Throwable $e) {}
        }
    }

    /** L'interrupteur, indépendamment du reste. */
    public function interrupteur(): bool
    {
        return $this->reglages()['actif'];
    }

    public function mode(): string
    {
        return $this->reglages()['mode'];
    }

    /** @return string[] */
    public function paysChoisis(): array
    {
        return $this->reglages()['pays'];
    }

    /**
     * Le filtre est-il réellement en service ?
     *
     * Une liste vide n'active rien : en liste blanche elle bloquerait tout le
     * monde, en liste noire elle ne bloquerait personne. Dans les deux cas,
     * mieux vaut le dire clairement que laisser croire à une protection.
     */
    public function actif(): bool
    {
        $r = $this->reglages();

        return $r['actif']
            && $r['pays'] !== []
            && $this->base->existe();
    }

    /**
     * Pourquoi le filtre ne s'applique pas, alors que l'interrupteur est mis.
     *
     * L'interface doit pouvoir dire « activé mais sans effet » plutôt que de
     * laisser croire à une protection qui n'opère pas.
     */
    public function empechement(): ?string
    {
        $r = $this->reglages();

        if (!$r['actif'])            { return null; }
        if (!$this->base->existe())  { return 'La base de données des pays est absente : construisez-la.'; }
        if ($r['pays'] === [])       { return 'Aucun pays sélectionné : le filtre n\'a rien à appliquer.'; }

        return null;
    }

    /** Découpe une saisie libre en codes ISO à deux lettres. @return string[] */
    public static function analyser(string $brut): array
    {
        $out = [];

        foreach (preg_split('/[\s,;|]+/', strtoupper(trim($brut))) ?: [] as $m) {
            $m = trim($m);
            if (strlen($m) === 2 && ctype_alpha($m)) {
                $out[$m] = true;
            }
        }

        return array_keys($out);
    }

    // ── Décision ─────────────────────────────────────────────────────────

    /**
     * Ce visiteur doit-il être refusé ?
     *
     * @return string|null Le code pays motivant le refus, ou null pour laisser
     *                     passer. Le null couvre aussi bien « pays autorisé »
     *                     que « pays inconnu » : dans le doute, on n'interdit
     *                     jamais.
     */
    public function refus(string $ip): ?string
    {
        if (!$this->actif()) {
            return null;
        }

        $pays = $this->base->pays($ip);

        // Garde-fou n°1 : l'ignorance n'est pas un motif de refus.
        if ($pays === null) {
            return null;
        }

        $liste = $this->reglages()['pays'];

        if ($this->mode() === self::MODE_NOIRE) {
            return in_array($pays, $liste, true) ? $pays : null;
        }

        return in_array($pays, $liste, true) ? null : $pays;
    }

    /**
     * La configuration proposée enfermerait-elle l'administrateur dehors ?
     *
     * Appelé AVANT d'enregistrer. Se bloquer soi-même est l'erreur la plus
     * facile à commettre ici, et la plus pénible à défaire : il faudrait
     * modifier la base à la main, précisément ce qu'on cherche à éviter.
     *
     * @param  string[] $pays
     * @return string|null Le pays de l'administrateur s'il serait refusé.
     */
    public function seTirerUneBalle(string $mode, array $pays, string $ipAdmin, bool $actif = true): ?string
    {
        if (!$actif || $mode === self::MODE_INACTIF || $pays === []) {
            return null;
        }

        $sien = $this->base->pays($ipAdmin);

        // Depuis le réseau local, on ne sait pas : rien à signaler.
        if ($sien === null) {
            return null;
        }

        if ($mode === self::MODE_NOIRE) {
            return in_array($sien, $pays, true) ? $sien : null;
        }

        return in_array($sien, $pays, true) ? null : $sien;
    }

    // ── Confort d'affichage ──────────────────────────────────────────────

    /**
     * Le nom du pays en français.
     *
     * `intl` fournit la traduction et la tient à jour ; sans lui on retombe
     * sur le code, ce qui reste lisible. Embarquer une table de 250 noms
     * aurait ajouté un fichier à maintenir pour un résultat inférieur.
     */
    public static function nomPays(string $code, string $langue = 'fr'): string
    {
        $code = strtoupper($code);

        if (class_exists(\Locale::class)) {
            $nom = \Locale::getDisplayRegion('-' . $code, $langue);
            if ($nom !== '' && $nom !== $code) {
                return $nom;
            }
        }

        return $code;
    }

    /**
     * Tous les pays présents dans la base, triés par nom.
     *
     * @return array<string,string> code => nom
     */
    public function paysConnus(): array
    {
        $codes = $this->base->codesPresents();
        $out   = [];

        foreach ($codes as $c) {
            $out[$c] = self::nomPays($c);
        }

        asort($out, SORT_LOCALE_STRING);

        return $out;
    }
}
