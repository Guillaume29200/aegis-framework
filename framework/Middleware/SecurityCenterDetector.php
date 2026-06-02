<?php
declare(strict_types=1);

namespace Framework\Middleware;

use Framework\Services\SecurityCenterService;
use Framework\Services\SecurityFirewallService;

/**
 * SecurityCenterDetector — middleware d'analyse catégorisée (Aegis Framework).
 *
 * S'exécute juste après SecurityFirewall (qui a déjà bloqué les IP bannies et
 * appliqué le rate-limit). Il fait tourner les détecteurs du Centre de sécurité
 * sur la surface URL de la requête et, si le score franchit un seuil, refuse
 * immédiatement la requête.
 */
class SecurityCenterDetector
{
    private SecurityCenterService $center;
    private SecurityFirewallService $firewall;

    public function __construct(SecurityCenterService $center, SecurityFirewallService $firewall)
    {
        $this->center = $center;
        $this->firewall = $firewall;
    }

    public function handle(): void
    {
        try {
            if (!$this->center->isEnabled()) {
                return;
            }

            $ip = $this->firewall->getClientIp();

            // 1) Détournement de session (drapeau posé par SessionManager).
            $this->center->reportSessionHijackIfFlagged($ip);

            // 2) Analyse de la surface URL (chemin + query + User-Agent).
            $result = $this->center->inspectHttpRequest(
                $ip,
                $_SERVER['REQUEST_METHOD'] ?? 'GET',
                $_SERVER['REQUEST_URI'] ?? '/'
            );

            // 3) Inspection des fichiers uploadés (le cas échéant).
            if (!empty($_FILES)) {
                $this->center->inspectUploadedFiles($ip);
            }

            // Blocage immédiat si un seuil vient d'être franchi, ou si l'IP est
            // désormais sous blocage actif.
            if (($result['blocked'] ?? false) || $this->firewall->getActiveBlock($ip)) {
                $this->renderBlocked($result['reason'] ?? 'Activité malveillante détectée');
            }
        } catch (\Throwable $e) {
            error_log('[SecurityCenterDetector] bypass after internal error: ' . $e->getMessage());
        }
    }

    private function renderBlocked(string $reason): never
    {
        http_response_code(403);
        header('Content-Type: text/html; charset=UTF-8');

        $safeReason = htmlspecialchars($reason, ENT_QUOTES, 'UTF-8');
        echo <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accès bloqué — Aegis Framework</title>
    <style>
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#0b1120;color:#e6ebf5;font-family:Inter,Arial,sans-serif}
        .box{width:min(520px,92vw);background:#151c2c;border:1px solid #2a3349;border-radius:14px;padding:34px;text-align:center}
        h1{font-size:22px;margin:0 0 12px}
        p{line-height:1.6;color:#9aa7c2}
        code{display:block;margin-top:18px;padding:12px;background:#0b1120;border-radius:8px;color:#f87171;word-break:break-word}
    </style>
</head>
<body>
    <div class="box">
        <h1>🛡️ Accès bloqué</h1>
        <p>Le Centre de sécurité a détecté une activité malveillante et a bloqué cette requête. Si vous pensez qu'il s'agit d'une erreur, contactez l'administrateur du site.</p>
        <code>{$safeReason}</code>
    </div>
</body>
</html>
HTML;
        exit;
    }
}
