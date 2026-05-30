<?php
declare(strict_types=1);

namespace Framework\Middleware;

use Framework\Services\SecurityFirewallService;

/**
 * SecurityFirewall - Middleware anti-abus HTTP.
 *
 * Execute tres tot dans le cycle web pour bloquer les IP deja bannies,
 * les scans evidents et les floods applicatifs avant les controllers.
 */
class SecurityFirewall
{
    private SecurityFirewallService $firewall;

    public function __construct(SecurityFirewallService $firewall)
    {
        $this->firewall = $firewall;
    }

    public function handle(): void
    {
        if (!$this->firewall->isEnabled()) {
            return;
        }

        try {
            $ip = $this->firewall->getClientIp();
            if ($this->firewall->isWhitelisted($ip)) {
                return;
            }

            $block = $this->firewall->getActiveBlock($ip);
            if ($block) {
                $this->renderBlocked(403, $block['reason'] ?? 'IP bloquee temporairement');
            }

            $decision = $this->firewall->inspectRequest(
                $ip,
                $_SERVER['REQUEST_METHOD'] ?? 'GET',
                $_SERVER['REQUEST_URI'] ?? '/'
            );

            if (($decision['allowed'] ?? true) === false) {
                $this->renderBlocked((int)($decision['status'] ?? 429), $decision['reason'] ?? 'Protection anti-abus active');
            }
        } catch (\Throwable $e) {
            error_log('[SecurityFirewall] bypass after internal error: ' . $e->getMessage());
        }
    }

    private function renderBlocked(int $status, string $reason): never
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
        header('Retry-After: 900');

        $title = $status === 429 ? 'Trop de requetes' : 'Acces temporairement bloque';
        $safeReason = htmlspecialchars($reason, ENT_QUOTES, 'UTF-8');

        echo <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Protection GSH - {$title}</title>
    <style>
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#111827;color:#e5e7eb;font-family:Arial,sans-serif}
        .box{width:min(520px,92vw);background:#1f2937;border:1px solid #374151;border-radius:12px;padding:32px;text-align:center}
        h1{font-size:24px;margin:0 0 12px}
        p{line-height:1.55;color:#cbd5e1}
        code{display:block;margin-top:18px;padding:12px;background:#0f172a;border-radius:8px;color:#fca5a5;word-break:break-word}
    </style>
</head>
<body>
    <div class="box">
        <h1>{$title}</h1>
        <p>La protection anti-abus a bloque cette requete. Reessayez plus tard ou contactez l'administrateur si vous pensez que c'est une erreur.</p>
        <code>{$safeReason}</code>
    </div>
</body>
</html>
HTML;
        exit;
    }
}
