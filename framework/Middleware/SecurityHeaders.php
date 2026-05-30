<?php
declare(strict_types=1);

namespace Framework\Middleware;

/**
 * SecurityHeaders - ajoute les protections HTTP globales.
 *
 * Cette couche est executee tres tot afin que les pages normales, les erreurs
 * et les blocages firewall heritent tous des memes en-tetes de securite.
 */
class SecurityHeaders
{
    public function __construct(private array $headers)
    {
    }

    public function handle(): void
    {
        if (headers_sent()) {
            return;
        }

        foreach ($this->headers as $name => $value) {
            if (!is_string($name) || trim($name) === '' || $value === null || $value === '') {
                continue;
            }

            header($name . ': ' . (string) $value, true);
        }
    }
}
