<?php
declare(strict_types=1);

namespace Framework\Capabilities;

/**
 * Câble au runtime les capacités déclarées par les modules actifs.
 * Appelé une fois après le chargement des modules (ModuleManager::loadModules()).
 *
 * Pour chaque capacité présente dans au moins un module actif, on charge le
 * fichier de helpers correspondant (fonctions globales `md_render()`, etc.).
 * Idempotent : les helpers sont guardés par `function_exists`.
 */
final class CapabilityManager
{
    /** capacité → fichier de helpers (relatif à framework/). */
    private const HELPERS = [
        'markdown'  => 'Helpers/MarkdownHelper.php',
        'cache'     => 'Helpers/CacheHelper.php',     // déjà chargé par bootstrap ; require_once = no-op
        'ai'        => 'Helpers/AIModelsHelper.php',  // idem
        'rgpd'      => 'Helpers/RgpdHelper.php',      // cookie_banner() → composant central
        'recaptcha' => 'Helpers/RecaptchaHelper.php', // déjà chargé par bootstrap ; require_once = no-op
        'analytics' => 'Helpers/AnalyticsHelper.php', // analytics_head() → module Analytics
        'seo'       => 'Helpers/SeoHelper.php',        // seo_head() → méta par page + défauts site
        'turbonav'  => 'Helpers/TurboNavHelper.php',  // turbonav_script() → navigation AJAX
    ];

    private static bool $booted = false;

    /**
     * @param string        $modulesPath  Dossier /modules.
     * @param string[]|null $activeNames  Modules actifs ; null = scanne tout /modules.
     */
    public static function boot(string $modulesPath, ?array $activeNames = null): void
    {
        if (self::$booted) { return; }
        self::$booted = true;

        if ($activeNames === null) {
            $activeNames = [];
            foreach (glob(rtrim($modulesPath, '/\\') . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
                $activeNames[] = basename($dir);
            }
        }

        $frameworkDir = dirname(__DIR__); // .../framework
        foreach (self::collect($modulesPath, $activeNames) as $cap) {
            $rel = self::HELPERS[$cap] ?? null;
            if ($rel !== null) {
                $file = $frameworkDir . '/' . $rel;
                if (is_file($file)) { require_once $file; }
            }
        }
    }

    /** Capacités déclarées (et disponibles) par un module donné. @return string[] */
    public static function moduleCapabilities(string $modulesPath, string $moduleName): array
    {
        $file = rtrim($modulesPath, '/\\') . '/' . $moduleName . '/module.json';
        if (!is_file($file)) { return []; }
        $cfg = json_decode((string) file_get_contents($file), true);
        if (!is_array($cfg)) { return []; }
        return CapabilityRegistry::sanitize((array) ($cfg['capabilities'] ?? []));
    }

    /** Union des capacités de tous les modules actifs. @return string[] */
    private static function collect(string $modulesPath, array $names): array
    {
        $set = [];
        foreach ($names as $n) {
            foreach (self::moduleCapabilities($modulesPath, (string) $n) as $c) {
                $set[$c] = true;
            }
        }
        return array_keys($set);
    }
}
