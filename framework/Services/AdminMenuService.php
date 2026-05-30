<?php
declare(strict_types=1);

namespace Framework\Services;

use Framework\ModuleManager\ModuleManager;

/**
 * AdminMenuService — Agrégation du menu d'administration.
 *
 * Le menu est construit dynamiquement à partir de :
 *   1. Items « cœur » fournis par le framework (Dashboard, groupe Système).
 *   2. Items déclarés par chaque module ACTIF via getAdminMenu()
 *      (ou la clé admin_menu de son module.json, gérée par BaseModule).
 *
 * Ainsi, activer/désactiver un module ajoute/retire automatiquement ses
 * entrées de menu, sans toucher au layout.
 *
 * Format d'un item :
 *   [
 *     'label'    => 'Utilisateurs',
 *     'icon'     => '👥',                 // emoji
 *     'url'      => '/admin/users',       // null si simple groupe
 *     'position' => 20,                   // ordre croissant
 *     'match'    => '/admin/users',       // préfixe pour l'état actif (def: url)
 *     'badge'    => null,                 // ex: '3' (compteur)
 *     'children' => [ ...items... ],      // sous-menu optionnel
 *   ]
 */
class AdminMenuService
{
    private ?ModuleManager $moduleManager;

    public function __construct(?ModuleManager $moduleManager = null)
    {
        $this->moduleManager = $moduleManager;
    }

    /**
     * Construire le menu complet, trié par position.
     *
     * @return array<int, array<string, mixed>>
     */
    public function build(): array
    {
        $items = $this->coreItems();

        if ($this->moduleManager !== null) {
            foreach ($this->moduleManager->getLoadedModules() as $module) {
                if (!method_exists($module, 'getAdminMenu')) {
                    continue;
                }

                try {
                    $moduleItems = (array) $module->getAdminMenu();
                } catch (\Throwable) {
                    continue;
                }

                foreach ($moduleItems as $item) {
                    if (is_array($item) && !empty($item['label'])) {
                        $items[] = $this->normalize($item);
                    }
                }
            }
        }

        usort($items, fn($a, $b) => ($a['position'] <=> $b['position']));

        return $items;
    }

    /**
     * Items cœur du framework.
     *
     * Volontairement vide : le menu admin est 100 % piloté par les modules
     * (chaque module.json déclare ses entrées via la clé "menu"). Les pages
     * système (Modules/Sécurité/Monitoring) sont fournies par le module System,
     * le tableau de bord et les utilisateurs par le module Auth, etc.
     *
     * Ce point reste disponible si le framework devait un jour injecter une
     * entrée non rattachée à un module.
     *
     * @return array<int, array<string, mixed>>
     */
    private function coreItems(): array
    {
        return [];
    }

    /**
     * Normaliser un item (valeurs par défaut + enfants récursifs).
     *
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function normalize(array $item): array
    {
        $url = $item['url'] ?? null;

        $children = [];
        if (!empty($item['children']) && is_array($item['children'])) {
            foreach ($item['children'] as $child) {
                if (is_array($child) && !empty($child['label'])) {
                    $children[] = $this->normalize($child);
                }
            }
        }

        return [
            'label'    => (string) $item['label'],
            'icon'     => (string) ($item['icon'] ?? '•'),
            'url'      => $url,
            'position' => (int) ($item['position'] ?? 500),
            'match'    => (string) ($item['match'] ?? $url ?? ''),
            'badge'    => $item['badge'] ?? null,
            'children' => $children,
        ];
    }

    /**
     * Déterminer si un item correspond à l'URI courante (état actif).
     */
    public static function isActive(array $item, string $currentPath): bool
    {
        $match = $item['match'] ?? '';
        if ($match !== '' && ($currentPath === $match || str_starts_with($currentPath, $match . '/'))) {
            return true;
        }

        foreach ($item['children'] ?? [] as $child) {
            if (self::isActive($child, $currentPath)) {
                return true;
            }
        }

        return false;
    }
}
