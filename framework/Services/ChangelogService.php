<?php
declare(strict_types=1);

namespace Framework\Services;

/**
 * Le journal des versions du framework, un fichier par version.
 *
 * POURQUOI DÉCOUPER — et ce n'est pas pour la vitesse.
 *
 * Le fichier unique pesait 102 Ko pour 16 versions et se lisait en 2,2 ms :
 * il aurait tenu longtemps. Le vrai coût était ailleurs. Publier une version
 * demandait de modifier un gros fichier partagé — un tableau JSON où chaque
 * ajout se fait en tête, donc où deux personnes qui publient en parallèle se
 * marchent dessus, et où une relecture de diff montre tout le fichier.
 *
 * Un fichier par version supprime cela : publier, c'est DÉPOSER UN FICHIER.
 * Rien à modifier, rien à fusionner, rien à renuméroter.
 *
 * Le prix — lire N fichiers au lieu d'un — est payé une fois puis mémorisé,
 * la liste ne changeant qu'à la publication.
 *
 *     framework/changelog/
 *         4.0.0-alpha.1.json
 *         …
 *         4.0.0-beta.1.json
 *
 * Le nom du fichier fait foi pour le numéro : il ne peut pas diverger du
 * contenu, et la version courante est simplement la plus haute.
 *
 * L'ancien `changelog.json` reste lu si le dossier n'existe pas, pour qu'une
 * installation non migrée continue de fonctionner.
 */
final class ChangelogService
{
    private const CLE_CACHE = 'framework.changelog';
    private const TTL       = 600;

    /**
     * Étiquettes déduites du contenu quand la version n'en déclare pas.
     *
     * Cinq versions sur seize n'avaient aucune étiquette : le champ avait
     * simplement été oublié en cours de route. Plutôt que de les saisir à la
     * main — ce qui se réoubliera — on les lit dans les titres de groupes,
     * qui portent déjà l'emoji du type de changement.
     *
     * @var array<string,string[]>
     */
    private const INDICES = [
        'fix'      => ['🐛', 'correctif', 'correction', 'corrections'],
        'security' => ['🔒', '🔐', '🛡️', 'sécurit', 'durcissement', 'rgpd'],
        'module'   => ['📦', 'module '],
        'feature'  => [],   // le défaut : tout groupe qui n'est rien d'autre
    ];

    private ?string $racine;

    public function __construct(?string $racine = null)
    {
        $this->racine = $racine ?? (defined('ROOT_PATH') ? rtrim(ROOT_PATH, "/\\") : dirname(__DIR__, 2));
    }

    /** Le dossier des versions. */
    public function dir(): string
    {
        return $this->racine . '/framework/changelog';
    }

    /**
     * Toutes les versions, de la plus récente à la plus ancienne.
     *
     * @return array<int,array<string,mixed>>
     */
    public function releases(bool $frais = false): array
    {
        if (!$frais && function_exists('cache_get')) {
            $memo = cache_get(self::CLE_CACHE);
            if (is_array($memo)) { return $memo; }
        }

        $liste = $this->charge();

        if (function_exists('cache_set')) {
            cache_set(self::CLE_CACHE, $liste, self::TTL);
        }

        return $liste;
    }

    /** La version en cours : la plus haute présente. */
    public function current(): string
    {
        $r = $this->releases();
        return (string) ($r[0]['version'] ?? '');
    }

    /** Le journal complet, dans la forme attendue par la page d'administration. */
    public function all(): array
    {
        return [
            'product'  => 'Aegis Framework',
            'version'  => $this->current(),
            'releases' => $this->releases(),
        ];
    }

    /** À appeler après avoir déposé une version. */
    public function forget(): void
    {
        if (function_exists('cache_delete')) {
            try { cache_delete(self::CLE_CACHE); } catch (\Throwable $e) {}
        }
    }

    // ── Lecture ──────────────────────────────────────────────────────────

    /** @return array<int,array<string,mixed>> */
    private function charge(): array
    {
        $dossier = $this->dir();

        $releases = is_dir($dossier)
            ? $this->depuisDossier($dossier)
            : $this->depuisFichierUnique();

        // De la plus récente à la plus ancienne. version_compare connaît
        // l'ordre alpha < beta < rc < stable, il n'y a rien à réinventer.
        usort($releases, static fn(array $a, array $b): int
            => version_compare((string) $b['version'], (string) $a['version']));

        foreach ($releases as &$r) {
            if (empty($r['types'])) {
                $r['types'] = $this->deduitTypes($r);
            }
        }
        unset($r);

        return $releases;
    }

    /** @return array<int,array<string,mixed>> */
    private function depuisDossier(string $dossier): array
    {
        $out = [];

        foreach (glob($dossier . '/*.json') ?: [] as $fichier) {
            $brut = json_decode((string) file_get_contents($fichier), true);
            if (!is_array($brut)) { continue; }

            // Le NOM DU FICHIER fait foi : un numéro écrit dans le contenu
            // pourrait le contredire, et il faudrait alors choisir.
            $brut['version'] = basename($fichier, '.json');

            $out[] = $brut;
        }

        return $out;
    }

    /** Repli : l'ancien fichier unique, pour une installation non migrée. */
    private function depuisFichierUnique(): array
    {
        $fichier = $this->racine . '/framework/changelog.json';
        if (!is_file($fichier)) { return []; }

        $j = json_decode((string) file_get_contents($fichier), true);
        return is_array($j['releases'] ?? null) ? $j['releases'] : [];
    }

    /**
     * Déduit les étiquettes d'une version à partir de ses groupes.
     *
     * @return string[]
     */
    private function deduitTypes(array $release): array
    {
        $trouves = [];

        foreach ($release['groups'] ?? [] as $g) {
            $titre = mb_strtolower((string) ($g['title'] ?? ''));
            if ($titre === '') { continue; }

            $type = 'feature';
            foreach (self::INDICES as $candidat => $indices) {
                foreach ($indices as $indice) {
                    if (str_contains($titre, mb_strtolower($indice))) {
                        $type = $candidat;
                        break 2;
                    }
                }
            }
            $trouves[$type] = true;
        }

        // Une version sans groupe reconnaissable reste une version.
        if ($trouves === []) { return ['release']; }

        // Ordre stable et lisible, quel que soit l'ordre des groupes.
        $ordre = ['release', 'feature', 'module', 'security', 'fix'];
        return array_values(array_filter($ordre, static fn(string $t): bool => isset($trouves[$t])));
    }
}
