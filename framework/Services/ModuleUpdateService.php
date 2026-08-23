<?php
declare(strict_types=1);

namespace Framework\Services;

/**
 * Les modules dont les fichiers sont en avance sur la base.
 *
 * L'information existait déjà, mais seulement dans le diagnostic : il fallait
 * ouvrir cette page pour apprendre qu'une mise à jour attendait. Autant dire
 * qu'on ne l'apprenait pas. Ce service isole le même calcul pour que
 * l'en-tête d'administration puisse le signaler en permanence.
 *
 * Deux motifs de mise à jour, et le premier suffit :
 *   — la version de `module.json` diffère de celle enregistrée en base ;
 *   — des migrations SQL n'ont pas encore été appliquées.
 *
 * Le calcul lit un fichier par module actif. C'est peu, mais c'est à chaque
 * page d'administration : le résultat est donc mémorisé quelques minutes.
 */
final class ModuleUpdateService
{
    /** Durée de mémorisation. Assez courte pour ne pas mentir après une mise à jour. */
    private const TTL = 120;

    private const CLE_CACHE = 'framework.module_updates';

    public function __construct(
        private Database $db,
        private \Framework\ModuleManager\ModuleManager $modules
    ) {
    }

    /**
     * @return array<int,array{name:string,from:string,to:string,migrations:int}>
     */
    public function pending(bool $frais = false): array
    {
        if (!$frais && function_exists('cache_get')) {
            $memo = cache_get(self::CLE_CACHE);
            if (is_array($memo)) { return $memo; }
        }

        $liste = $this->calcule();

        if (function_exists('cache_set')) {
            cache_set(self::CLE_CACHE, $liste, self::TTL);
        }

        return $liste;
    }

    public function count(bool $frais = false): int
    {
        return count($this->pending($frais));
    }

    /** À appeler après une mise à jour, pour que le compteur ne traîne pas. */
    public function forget(): void
    {
        if (function_exists('cache_delete')) {
            cache_delete(self::CLE_CACHE);
        }
    }

    /**
     * @return array<int,array{name:string,from:string,to:string,migrations:int}>
     */
    private function calcule(): array
    {
        $racine = defined('ROOT_PATH') ? rtrim(ROOT_PATH, "/\\") : dirname(__DIR__, 2);
        $out    = [];

        try {
            $lignes = $this->db->query(
                "SELECT name, version FROM modules WHERE active = 1"
            ) ?: [];
        } catch (\Throwable $e) {
            // Table absente ou base indisponible : on ne signale rien plutôt
            // que de faire échouer l'en-tête de toutes les pages.
            return [];
        }

        foreach ($lignes as $ligne) {
            $nom     = (string) $ligne['name'];
            $enBase  = (string) ($ligne['version'] ?? '');
            $fichier = $racine . '/modules/' . $nom . '/module.json';

            if (!is_file($fichier)) { continue; }

            $manifeste = json_decode((string) file_get_contents($fichier), true);
            $surDisque = is_array($manifeste) ? (string) ($manifeste['version'] ?? '') : '';

            try {
                $migrations = $this->modules->pendingMigrationCount($nom);
            } catch (\Throwable $e) {
                $migrations = 0;
            }

            $versionDiffere = $surDisque !== '' && $surDisque !== $enBase;

            if ($migrations > 0 || $versionDiffere) {
                $out[] = [
                    'name'       => $nom,
                    'from'       => $enBase !== '' ? $enBase : '?',
                    'to'         => $surDisque !== '' ? $surDisque : $enBase,
                    'migrations' => $migrations,
                ];
            }
        }

        // Les mises à jour les plus parlantes d'abord : celles qui changent
        // de version, puis celles qui n'ont que des migrations à passer.
        usort($out, function (array $a, array $b): int {
            $sautA = $a['from'] !== $a['to'] ? 1 : 0;
            $sautB = $b['from'] !== $b['to'] ? 1 : 0;
            return $sautB <=> $sautA ?: strcmp($a['name'], $b['name']);
        });

        return $out;
    }
}
