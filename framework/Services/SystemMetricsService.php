<?php
declare(strict_types=1);

namespace Framework\Services;

/**
 * Charge processeur et mémoire de la machine, en direct.
 *
 * Deux contraintes ont dicté la conception :
 *
 *   — RIEN N'EST ÉCRIT EN BASE. Un relevé toutes les cinq secondes ferait
 *     grossir une table pour une donnée qui n'a d'intérêt qu'à l'instant où
 *     on la regarde. L'échantillon précédent — nécessaire au calcul de la
 *     charge — vit dans le cache applicatif, pas dans une table.
 *
 *   — WINDOWS ET LINUX. Les deux systèmes n'exposent pas du tout la même
 *     chose, et la manière évidente est lente sur les deux :
 *
 *       Windows : `Win32_Processor.LoadPercentage` met 1 200 ms, et
 *                 `PerfFormattedData` plus de 11 secondes — inutilisable
 *                 pour un rafraîchissement court. Les compteurs BRUTS
 *                 (`PerfRawData`) répondent en 55 ms : on les lit deux fois
 *                 et on calcule la différence nous-mêmes.
 *
 *       Linux   : /proc/stat et /proc/meminfo, deux lectures de fichier.
 *
 * Quand aucune source n'est exploitable — COM absent, /proc inaccessible,
 * hébergement mutualisé — le service le dit (`source: unavailable`) plutôt
 * que de renvoyer des zéros qu'on prendrait pour une machine au repos.
 */
final class SystemMetricsService
{
    /** Où l'échantillon précédent est gardé entre deux appels. */
    private const CLE_CPU = 'framework.metrics.cpu_prev';

    /** Au-delà, l'échantillon précédent est trop vieux pour être comparé. */
    private const AGE_MAX = 60;

    /**
     * @return array{
     *   source:string, cpu:?float, cores:?int,
     *   ram:array{used_pct:?float,used:?float,total:?float},
     *   php:array{usage:float,peak:float,limit:?float},
     *   uptime:?int
     * }
     */
    public function snapshot(): array
    {
        $windows = stripos(PHP_OS_FAMILY, 'Windows') !== false;

        $base = [
            'source' => 'unavailable',
            'cpu'    => null,
            'cores'  => null,
            'ram'    => ['used_pct' => null, 'used' => null, 'total' => null],
            'php'    => [
                'usage' => (float) memory_get_usage(true),
                'peak'  => (float) memory_get_peak_usage(true),
                'limit' => $this->limiteMemoirePhp(),
            ],
            'uptime' => null,
        ];

        try {
            $mesure = $windows ? $this->windows() : $this->linux();
        } catch (\Throwable $e) {
            return $base;
        }

        return array_replace_recursive($base, $mesure);
    }

    // ── Linux ────────────────────────────────────────────────────────────

    private function linux(): array
    {
        $out = ['source' => 'linux'];

        // CPU : /proc/stat donne des compteurs cumulés. La charge est la part
        // de temps NON inactif entre deux relevés.
        $ligne = $this->premiereLigne('/proc/stat');
        if ($ligne !== null && str_starts_with($ligne, 'cpu ')) {
            $champs = preg_split('/\s+/', trim($ligne)) ?: [];
            array_shift($champs);
            $valeurs = array_map('floatval', $champs);

            $total = array_sum($valeurs);
            // idle + iowait : la machine n'a rien fait d'utile pendant ce temps.
            $idle  = ($valeurs[3] ?? 0.0) + ($valeurs[4] ?? 0.0);

            $out['cpu'] = $this->chargeDepuisCompteurs($idle, $total);
        }

        // Cœurs : une ligne « processor » par cœur logique.
        $cpuinfo = @file_get_contents('/proc/cpuinfo');
        if (is_string($cpuinfo)) {
            $n = preg_match_all('/^processor\s*:/mi', $cpuinfo);
            if ($n > 0) { $out['cores'] = $n; }
        }

        // Mémoire : MemAvailable est la bonne mesure — MemFree ignore le cache,
        // qui est récupérable et ne doit pas compter comme occupé.
        $meminfo = @file_get_contents('/proc/meminfo');
        if (is_string($meminfo)) {
            $lire = function (string $cle) use ($meminfo): ?float {
                return preg_match('/^' . $cle . ':\s+(\d+)\s*kB/mi', $meminfo, $m)
                    ? (float) $m[1] * 1024 : null;
            };
            $total = $lire('MemTotal');
            $dispo = $lire('MemAvailable') ?? $lire('MemFree');

            if ($total !== null && $dispo !== null && $total > 0) {
                $utilise = $total - $dispo;
                $out['ram'] = [
                    'used_pct' => round($utilise / $total * 100, 1),
                    'used'     => $utilise,
                    'total'    => $total,
                ];
            }
        }

        $uptime = $this->premiereLigne('/proc/uptime');
        if ($uptime !== null) { $out['uptime'] = (int) (float) strtok($uptime, ' '); }

        return $out;
    }

    // ── Windows ──────────────────────────────────────────────────────────

    private function windows(): array
    {
        if (!class_exists('COM')) {
            return ['source' => 'unavailable'];
        }

        $wmi = new \COM('winmgmts://./root/cimv2');
        $out = ['source' => 'windows'];

        // Compteurs bruts : 55 ms, contre 1 200 ms pour LoadPercentage.
        // Sur « _Total », PercentProcessorTime compte le temps INACTIF.
        foreach ($wmi->ExecQuery(
            "SELECT PercentProcessorTime, TimeStamp_Sys100NS FROM Win32_PerfRawData_PerfOS_Processor WHERE Name='_Total'"
        ) as $p) {
            $out['cpu'] = $this->chargeDepuisCompteurs(
                (float) $p->PercentProcessorTime,
                (float) $p->TimeStamp_Sys100NS
            );
            break;
        }

        foreach ($wmi->ExecQuery(
            'SELECT FreePhysicalMemory, TotalVisibleMemorySize, NumberOfProcesses, LastBootUpTime FROM Win32_OperatingSystem'
        ) as $o) {
            // WMI donne des kilo-octets.
            $libre = (float) $o->FreePhysicalMemory * 1024;
            $total = (float) $o->TotalVisibleMemorySize * 1024;

            if ($total > 0) {
                $out['ram'] = [
                    'used_pct' => round(($total - $libre) / $total * 100, 1),
                    'used'     => $total - $libre,
                    'total'    => $total,
                ];
            }
            break;
        }

        $coeurs = (int) (getenv('NUMBER_OF_PROCESSORS') ?: 0);
        if ($coeurs > 0) { $out['cores'] = $coeurs; }

        return $out;
    }

    // ── Commun ───────────────────────────────────────────────────────────

    /**
     * Charge en pourcentage, à partir de compteurs cumulés d'inactivité.
     *
     * Le premier appel ne peut rien calculer — il n'y a pas encore de point
     * de comparaison. Il renvoie null, et la jauge affiche « mesure en
     * cours » plutôt qu'un zéro trompeur.
     */
    private function chargeDepuisCompteurs(float $idle, float $total): ?float
    {
        $avant = null;
        if (function_exists('cache_get')) {
            $memo = cache_get(self::CLE_CPU);
            if (is_array($memo) && isset($memo['idle'], $memo['total'], $memo['at'])) {
                $avant = $memo;
            }
        }

        if (function_exists('cache_set')) {
            cache_set(self::CLE_CPU, ['idle' => $idle, 'total' => $total, 'at' => time()], self::AGE_MAX);
        }

        if ($avant === null || (time() - (int) $avant['at']) > self::AGE_MAX) {
            return null;
        }

        $dIdle  = $idle  - (float) $avant['idle'];
        $dTotal = $total - (float) $avant['total'];

        // Compteurs remis à zéro (redémarrage) ou deux appels dans la même
        // seconde : aucune mesure fiable à en tirer.
        if ($dTotal <= 0 || $dIdle < 0) { return null; }

        return round(max(0.0, min(100.0, 100.0 - ($dIdle / $dTotal * 100.0))), 1);
    }

    private function premiereLigne(string $fichier): ?string
    {
        if (!@is_readable($fichier)) { return null; }
        $f = @fopen($fichier, 'r');
        if ($f === false) { return null; }
        $l = fgets($f);
        fclose($f);
        return $l === false ? null : $l;
    }

    /** La limite mémoire de PHP en octets, ou null si elle est illimitée. */
    private function limiteMemoirePhp(): ?float
    {
        $brut = trim((string) ini_get('memory_limit'));
        if ($brut === '' || $brut === '-1') { return null; }

        $unite = strtolower(substr($brut, -1));
        $n     = (float) $brut;

        return match ($unite) {
            'g' => $n * 1073741824,
            'm' => $n * 1048576,
            'k' => $n * 1024,
            default => $n,
        };
    }
}
