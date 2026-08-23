<?php
declare(strict_types=1);

namespace Framework\Templating;

/**
 * Le peu que le gestionnaire de thèmes attend d'un magasin de réglages.
 *
 * ThemeSettings en est l'implémentation par défaut, adossée à la table d'un
 * module. Mais un module qui possède déjà son propre service de réglages n'a
 * aucune raison d'en instancier un second sur la même table : il lui suffit
 * d'implémenter ces quatre méthodes et de se passer lui-même au gestionnaire.
 *
 * C'est ce que fait GameNodeEsport avec son SettingsService historique — une
 * table, un service, aucun doublon.
 */
interface SettingsStore
{
    public function get(string $key, string $default = ''): string;

    /** @return array<string,string> Toutes les clés, en un seul aller-retour. */
    public function all(): array;

    public function set(string $key, string $value): void;

    /** Supprime toutes les clés commençant par un préfixe. */
    public function forget(string $prefix): void;
}
