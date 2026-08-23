<?php
declare(strict_types=1);

namespace Framework\Services;

/**
 * LicenseGuard — pont entre un module local et le système de licences.
 *
 * Chaque module appelle LicenseGuard::for('NomModule') pour savoir s'il a le
 * droit de tourner. Le garde lit la configuration de liaison stockée par le
 * module Licenses (mode open/licensed, clé, produit, URL API) et applique
 * EXACTEMENT la même logique résiliente que le client portable
 * (cache, grâce hors-ligne, essai, fail-open). Il ne bloque jamais brutalement.
 *
 * Si le module Licenses n'est pas installé, le garde laisse tout passer
 * (mode ouvert implicite) : le framework reste utilisable sans licences.
 */
final class LicenseGuard
{
    /** @var array<string,array> cache mémoire par module dans la requête */
    private static array $memo = [];

    public static function isAvailable(): bool
    {
        return is_file(ROOT_PATH . '/modules/Licenses/Services/LicenseService.php');
    }

    /**
     * Évalue la licence d'un module. Ne lève jamais d'exception.
     *
     * @return array{allowed:bool, status:string, mode:string, warning:?string, message:string}
     */
    public static function for(string $module): array
    {
        if (isset(self::$memo[$module])) return self::$memo[$module];

        // Pas de module Licenses → tout est ouvert.
        if (!self::isAvailable()) {
            return self::$memo[$module] = [
                'allowed' => true, 'status' => 'open', 'mode' => 'unlicensed',
                'warning' => null, 'message' => 'Système de licences absent : mode ouvert.',
            ];
        }

        try {
            $clientPath = ROOT_PATH . '/modules/Licenses/client/aegis-license-client.php';
            if (!class_exists('AegisLicenseClient') && is_file($clientPath)) {
                require_once $clientPath;
            }

            $svc      = self::licenseService();
            $binding  = $svc->getBinding($module);
            $settings = $svc->getSettings();

            // Mode ouvert (module offert) : court-circuit, zéro logique réseau.
            if (($binding['mode'] ?? 'open') === 'open') {
                return self::$memo[$module] = [
                    'allowed' => true, 'status' => 'open', 'mode' => 'unlicensed',
                    'warning' => null, 'message' => 'Module en accès libre (sans licence).',
                ];
            }

            $client = new \AegisLicenseClient([
                'api_url'            => $binding['api_url'] ?: self::defaultApiUrl(),
                'product'            => $binding['product'] ?: strtolower($module),
                'license_key'        => $binding['key'] ?? '',
                'secret'             => $svc->getSecret(),
                'cache_dir'          => ROOT_PATH . '/framework/cache/licenses',
                'trial_days'         => (int)$settings['trial_days'],
                'offline_grace_days' => (int)$settings['offline_grace_days'],
                'check_interval'     => (int)$settings['check_interval_hours'] * 3600,
                'fail_open'          => (bool)$settings['fail_open'],
            ]);
            $r = $client->check();

            return self::$memo[$module] = [
                'allowed' => $r['allowed'], 'status' => $r['status'], 'mode' => $r['mode'],
                'warning' => $r['warning'], 'message' => $r['message'],
            ];
        } catch (\Throwable $e) {
            // Toute erreur interne ⇒ fail-open (on ne bloque pas le client).
            return self::$memo[$module] = [
                'allowed' => true, 'status' => 'error', 'mode' => 'licensed',
                'warning' => null, 'message' => 'Vérification indisponible : ' . $e->getMessage(),
            ];
        }
    }

    /** Raccourci booléen. */
    public static function allowed(string $module): bool
    {
        return self::for($module)['allowed'];
    }

    private static function licenseService(): object
    {
        require_once ROOT_PATH . '/modules/Licenses/Services/LicenseService.php';
        /** @var Database $db */
        $db = $GLOBALS['db'] ?? null;
        if (!$db instanceof Database) {
            throw new \RuntimeException('Base de données indisponible.');
        }
        return new \Licenses\Services\LicenseService($db);
    }

    private static function defaultApiUrl(): string
    {
        // En local, le serveur de licence est le même hôte.
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base   = defined('BASE_URL') ? BASE_URL : '';
        return $scheme . '://' . $host . $base . '/api/license/validate';
    }
}
