<?php
declare(strict_types=1);

namespace Configuration\Services;

use Framework\Services\Database;
use Framework\Services\ImageOptimizer;

/**
 * ImageSettingsService — réglages d'optimisation des images uploadées et point
 * d'entrée unique pour optimiser un fichier. Service dédié extrait du
 * ConfigurationController : les uploads (SEO, Configuration, …) appellent
 * simplement optimize() sans dupliquer la lecture des réglages.
 */
class ImageSettingsService
{
    private SettingsService $settings;

    /** Extensions bitmap optimisables. */
    private const RASTER = ['png', 'jpg', 'jpeg', 'webp'];

    public function __construct(Database $db)
    {
        $this->settings = new SettingsService($db);
    }

    /** @return array{enabled:bool, max_width:int, quality:int} */
    public function getConfig(): array
    {
        return [
            'enabled'   => (string) $this->settings->get('image_optimize_enabled', '1') === '1',
            'max_width' => (int) $this->settings->get('image_max_width', 1920),
            'quality'   => (int) $this->settings->get('image_quality', 82),
        ];
    }

    /** @return array{success:bool, message:string} */
    public function save(array $post): array
    {
        $ok = $this->settings->setMultiple([
            'image_optimize_enabled' => ['value' => !empty($post['image_optimize_enabled']) ? 1 : 0, 'type' => 'bool'],
            'image_max_width'        => ['value' => max(320, min(5000, (int)($post['image_max_width'] ?? 1920))), 'type' => 'int'],
            'image_quality'          => ['value' => max(40, min(100, (int)($post['image_quality'] ?? 82))), 'type' => 'int'],
        ]);
        return $ok
            ? ['success' => true, 'message' => "Réglages d'optimisation des images enregistrés."]
            : ['success' => false, 'message' => 'Erreur lors de la sauvegarde.'];
    }

    public function isEnabled(): bool
    {
        return $this->getConfig()['enabled'];
    }

    /**
     * Optimise un fichier image uploadé si l'option est active et que le format
     * est un bitmap (jpg/png/webp). Sûr : ignore SVG/ICO/GIF. Ne lève jamais.
     */
    public function optimize(string $path, ?string $ext = null): void
    {
        $ext = strtolower($ext ?? pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, self::RASTER, true)) {
            return;
        }
        $cfg = $this->getConfig();
        if (!$cfg['enabled']) {
            return;
        }
        ImageOptimizer::optimize($path, $cfg['max_width'], $cfg['quality']);
    }
}
