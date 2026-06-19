<?php
declare(strict_types=1);

namespace Auth\Hooks;

/**
 * Hooks Admin
 */
class AdminHooks
{
    /**
     * Ajouter menu admin
     */
    public static function addAdminMenu(array $menu): array
    {
        $menu['auth'] = [
            'title' => 'Utilisateurs',
            'icon' => '👥',
            'url' => '/admin/users',
            'order' => 10,
            'submenu' => [
                [
                    'title' => 'Liste des utilisateurs',
                    'url' => '/admin/users'
                ],
                [
                    'title' => 'Ajouter un utilisateur',
                    'url' => '/admin/users/create'
                ],
                [
                    'title' => 'Statistiques',
                    'url' => '/admin/stats'
                ]
            ]
        ];
        
        return $menu;
    }
}
