<?php
declare(strict_types=1);

namespace System\Controllers;

use Framework\Services\Database;
use Framework\Services\SystemMetricsService;

/**
 * Le point d'appel des jauges CPU / mémoire.
 *
 * Répond en JSON, ne touche à aucune table, et n'est accessible qu'aux
 * administrateurs — la charge d'une machine renseigne sur son
 * dimensionnement, ce n'est pas une donnée publique.
 */
class MetricsController
{
    public function __construct(private Database $db) {}

    public function live(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        // Une mesure instantanée ne se met jamais en cache côté navigateur.
        header('Cache-Control: no-store, max-age=0');

        if (empty($_SESSION['logged_in'])
            || !in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'], true)) {
            http_response_code(403);
            echo json_encode(['error' => 'Accès refusé']);
            return;
        }

        try {
            $m = (new SystemMetricsService())->snapshot();
        } catch (\Throwable $e) {
            http_response_code(200);
            echo json_encode(['source' => 'unavailable', 'cpu' => null, 'ram' => null]);
            return;
        }

        echo json_encode([
            'source' => $m['source'],
            'cpu'    => $m['cpu'],
            'cores'  => $m['cores'],
            'ram'    => $m['ram'],
            'php'    => $m['php'],
            'uptime' => $m['uptime'],
            'at'     => date('H:i:s'),
        ], JSON_UNESCAPED_UNICODE);
    }
}
