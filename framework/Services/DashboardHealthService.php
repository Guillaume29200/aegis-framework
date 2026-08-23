<?php
declare(strict_types=1);

namespace Framework\Services;

/**
 * L'état de santé de l'installation, pour le tableau de bord.
 *
 * Le tableau de bord répondait à une seule question — « qui sont mes
 * utilisateurs ? » — parce qu'il était rendu par le module Auth. Or la
 * première chose qu'un administrateur veut savoir en arrivant, c'est
 * si quelque chose ne va pas.
 *
 * Ce service rassemble ce qui doit sauter aux yeux :
 *   — les contrôles du diagnostic qui échouent ;
 *   — les modules dont les fichiers sont en avance sur la base ;
 *   — ce qui, en production, ne devrait pas être là.
 *
 * Le diagnostic complet coûte environ 170 ms : il est mémorisé quelques
 * minutes. Un tableau de bord qui met un cinquième de seconde de plus à
 * s'ouvrir à chaque visite finit par agacer, et l'information ne change
 * pas d'une minute à l'autre.
 */
final class DashboardHealthService
{
    private const TTL       = 180;
    private const CLE_CACHE = 'framework.dashboard_health';

    public function __construct(
        private Database $db,
        private \Framework\ModuleManager\ModuleManager $modules
    ) {
    }

    /**
     * @return array{
     *   status:string, ok:int, warn:int, error:int,
     *   issues:array<int,array{level:string,label:string,detail:string}>,
     *   updates:array<int,array<string,mixed>>,
     *   env:array<string,mixed>
     * }
     */
    public function snapshot(bool $frais = false): array
    {
        if (!$frais && function_exists('cache_get')) {
            $memo = cache_get(self::CLE_CACHE);
            if (is_array($memo)) { return $memo; }
        }

        $etat = $this->calcule();

        if (function_exists('cache_set')) {
            cache_set(self::CLE_CACHE, $etat, self::TTL);
        }

        return $etat;
    }

    /** À appeler après une réparation, pour ne pas afficher un état périmé. */
    public function forget(): void
    {
        if (function_exists('cache_delete')) {
            try { cache_delete(self::CLE_CACHE); } catch (\Throwable $e) {}
        }
    }

    private function calcule(): array
    {
        $ok = 0; $warn = 0; $error = 0;
        $issues = [];

        // ── Le diagnostic, s'il est disponible ────────────────────────────
        // Il vit dans le module System : le framework ne peut pas en dépendre,
        // il l'utilise s'il est là.
        if (class_exists('\System\Services\DiagnosticService')) {
            try {
                $diag    = new \System\Services\DiagnosticService($this->db, $this->modules);
                $checks  = $diag->run();
                $resume  = $diag->summary($checks);
                $ok      = (int) ($resume['ok'] ?? 0);
                $warn    = (int) ($resume['warn'] ?? 0);
                $error   = (int) ($resume['error'] ?? 0);

                foreach ($checks as $c) {
                    if (($c['status'] ?? 'ok') === 'ok') { continue; }
                    $issues[] = [
                        'level'  => (string) $c['status'],
                        'label'  => (string) ($c['label'] ?? ''),
                        'detail' => (string) ($c['detail'] ?? ''),
                    ];
                }
            } catch (\Throwable $e) {
                // Un diagnostic en panne ne doit pas emporter le tableau de bord.
            }
        }

        // ── Les mises à jour de modules ───────────────────────────────────
        $updates = [];
        try {
            $updates = (new ModuleUpdateService($this->db, $this->modules))->pending();
        } catch (\Throwable $e) {}

        // ── L'environnement ───────────────────────────────────────────────
        $env = $this->environnement();

        // Les erreurs commandent la couleur d'ensemble ; à défaut, les
        // avertissements ; sinon tout va bien.
        $status = $error > 0 ? 'error' : (($warn > 0 || $updates !== []) ? 'warn' : 'ok');

        return [
            'status'  => $status,
            'ok'      => $ok,
            'warn'    => $warn,
            'error'   => $error,
            'issues'  => array_slice($issues, 0, 6),
            'updates' => $updates,
            'env'     => $env,
        ];
    }

    /** @return array<string,mixed> */
    private function environnement(): array
    {
        $racine = defined('ROOT_PATH') ? rtrim(ROOT_PATH, "/\\") : dirname(__DIR__, 2);

        $mysql = '?';
        try {
            $r = $this->db->query('SELECT VERSION() AS v');
            $mysql = (string) ($r[0]['v'] ?? '?');
        } catch (\Throwable $e) {}

        $version = '?';
        try {
            $version = (new ChangelogService($racine))->current() ?: '?';
        } catch (\Throwable $e) {}

        $appEnv = getenv('APP_ENV') ?: 'production';

        return [
            'php'         => PHP_VERSION,
            'mysql'       => $mysql,
            'framework'   => $version,
            'app_env'     => $appEnv,
            'is_prod'     => $appEnv === 'production',
            // Deux points qu'on ne veut PAS voir en production, et qui ne se
            // remarquent nulle part ailleurs dans l'interface.
            'install_dir' => is_dir($racine . '/install'),
            'debug'       => (bool) (defined('DEBUG_MODE') ? DEBUG_MODE : false),
        ];
    }

    /**
     * L'état de la porte d'entrée : tentatives de connexion et blocages.
     *
     * Ces chiffres existaient — la table `login_attempts` les enregistre à
     * chaque essai — mais ne se voyaient nulle part. Une série d'échecs sur
     * un même compte est le premier signe d'une attaque, et c'est exactement
     * le genre de chose qu'on découvre trop tard.
     *
     * @return array<string,mixed>
     */
    public function security(): array
    {
        $un = function (string $sql, array $p = []): int {
            try { $r = $this->db->query($sql, $p); return (int) ($r[0]['n'] ?? 0); }
            catch (\Throwable $e) { return 0; }
        };

        $echecs24 = $un("SELECT COUNT(*) n FROM login_attempts WHERE success = 0 AND attempted_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $reussi24 = $un("SELECT COUNT(*) n FROM login_attempts WHERE success = 1 AND attempted_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $bloques  = $un("SELECT COUNT(*) n FROM rate_limit_blocks WHERE blocked_until > NOW()");

        // Les comptes les plus visés sur la journée : c'est le renseignement
        // utile, pas le total brut.
        $cibles = [];
        try {
            $cibles = $this->db->query(
                "SELECT identifier, COUNT(*) AS n
                   FROM login_attempts
                  WHERE success = 0 AND attempted_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  GROUP BY identifier
                  ORDER BY n DESC
                  LIMIT 3"
            ) ?: [];
        } catch (\Throwable $e) {}

        return [
            'failed_24h'  => $echecs24,
            'success_24h' => $reussi24,
            'blocked'     => $bloques,
            'targets'     => $cibles,
            // Un ratio d'échec élevé sur un volume significatif mérite un
            // regard ; sur trois tentatives, il ne veut rien dire.
            'suspect'     => $echecs24 >= 10 && $echecs24 > $reussi24 * 3,
        ];
    }

    /**
     * L'espace disque et les dossiers qui doivent rester inscriptibles.
     *
     * Un disque plein ne se manifeste que par des téléversements qui échouent
     * en silence, et un dossier passé en lecture seule après une restauration
     * ne se remarque qu'au moment d'écrire.
     *
     * @return array<string,mixed>
     */
    public function storage(): array
    {
        $racine = defined('ROOT_PATH') ? rtrim(ROOT_PATH, "/\\") : dirname(__DIR__, 2);

        $libre = @disk_free_space($racine);
        $total = @disk_total_space($racine);
        $pct   = ($libre !== false && $total !== false && $total > 0)
            ? (int) round(($total - $libre) / $total * 100)
            : null;

        $dossiers = [];
        foreach ([
            'framework/cache'   => 'Cache',
            'framework/logs'    => 'Journaux',
            'framework/uploads' => 'Téléversements',
            'framework/storage' => 'Stockage',
        ] as $rel => $nom) {
            $chemin = $racine . '/' . $rel;
            if (!is_dir($chemin)) { continue; }
            $dossiers[] = ['label' => $nom, 'path' => $rel, 'writable' => is_writable($chemin)];
        }

        return [
            'free'       => $libre !== false ? (float) $libre : null,
            'total'      => $total !== false ? (float) $total : null,
            'used_pct'   => $pct,
            'dirs'       => $dossiers,
            'unwritable' => array_values(array_filter($dossiers, fn(array $d) => !$d['writable'])),
        ];
    }

    /**
     * L'audience des sept derniers jours, si le module Analytics tourne.
     *
     * Trois états, et il faut les distinguer — c'est là que se cache le piège :
     *
     *   'absent'    le module n'est pas là ;
     *   'inactive'  il est installé mais DÉSACTIVÉ. Ses tables subsistent
     *               (uninstall.sql est facultatif, les données sont
     *               préservées), si bien qu'un simple test d'existence de
     *               table ferait afficher des chiffres périmés comme s'il
     *               tournait encore. On ne montre donc AUCUN chiffre ;
     *   'idle'      actif, mais aucune visite sur la période.
     *
     * @return array<string,mixed>  toujours un tableau, jamais null : le
     *                              gabarit lit `state` et sait quoi dire.
     */
    public function audience(): array
    {
        $absent = ['state' => 'absent', 'pageviews' => 0, 'visitors' => 0, 'days' => []];

        try {
            // 1. Le module est-il présent ET actif ? C'est la question qui
            //    compte, pas l'existence de la table.
            $mod = $this->db->query(
                'SELECT active FROM modules WHERE name = ? LIMIT 1',
                ['Analytics']
            );

            if (!$mod) { return $absent; }

            if ((int) ($mod[0]['active'] ?? 0) !== 1) {
                return ['state' => 'inactive', 'pageviews' => 0, 'visitors' => 0, 'days' => []];
            }

            // 2. On lit les ÉVÉNEMENTS, pas la table d'agrégation.
            //
            //    `analytics_daily` est un résumé journalier alimenté par une
            //    tâche planifiée. Si celle-ci ne tourne pas — c'est le cas ici,
            //    la table s'arrêtait dix jours en arrière — le tableau de bord
            //    annonçait « aucune visite » alors que le site en recevait tous
            //    les jours. On interroge donc la source, qui est toujours à jour.
            $existe = $this->db->query("SHOW TABLES LIKE 'analytics_events'");
            if (!$existe) { return $absent; }

            $lignes = $this->db->query(
                "SELECT DATE(created_at) AS day,
                        COUNT(*)                    AS pageviews,
                        COUNT(DISTINCT visitor_hash) AS visitors
                   FROM analytics_events
                  WHERE type = 'pageview'
                    AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                  GROUP BY DATE(created_at)
                  ORDER BY day ASC"
            ) ?: [];
        } catch (\Throwable $e) {
            return $absent;
        }

        // Table présente mais aucune ligne récente : ce n'est PAS la même chose
        // qu'Analytics absent. Renvoyer null ici ferait afficher « module non
        // installé » à quelqu'un qui l'a installé et n'a simplement pas eu de
        // visite cette semaine.
        if ($lignes === []) {
            return ['state' => 'idle', 'pageviews' => 0, 'visitors' => 0, 'days' => []];
        }

        $vues = array_sum(array_column($lignes, 'pageviews'));
        $vis  = array_sum(array_column($lignes, 'visitors'));
        $max  = max(1, max(array_column($lignes, 'pageviews')));

        return [
            'pageviews' => (int) $vues,
            'visitors'  => (int) $vis,
            'state'     => 'live',
            'days'      => array_map(fn(array $l) => [
                'day' => (string) $l['day'],
                'n'   => (int) $l['pageviews'],
                // Hauteur déjà calculée : le gabarit n'a qu'à la poser.
                'pct' => (int) round(((int) $l['pageviews']) / $max * 100),
            ], $lignes),
        ];
    }

    /**
     * Les dernières écritures du journal d'audit, prêtes à afficher.
     *
     * @return array<int,array<string,mixed>>
     */
    public function recentActivity(int $limite = 8): array
    {
        try {
            // La table porte déjà le nom d'utilisateur : pas de jointure, et
            // la ligne reste lisible même si le compte a été supprimé depuis.
            return $this->db->query(
                'SELECT id, user_id, username, action, target_type, target_id, summary, ip, created_at
                   FROM cms_audit_log
                  ORDER BY id DESC
                  LIMIT ?',
                [max(1, $limite)]
            ) ?: [];
        } catch (\Throwable $e) {
            // Table absente : le journal n'est pas une dépendance dure.
            return [];
        }
    }
}
