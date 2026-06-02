<?php
/**
 * Helpers Cache - Fonctions globales pour le cache
 * 
 * Usage :
 * - cache()->get('key')
 * - cache()->set('key', 'value')
 * - cache()->remember('key', fn() => expensiveOperation())
 * - cache_get('key')
 * - cache_set('key', 'value')
 */

if (!function_exists('cache')) {
    /**
     * Obtenir l'instance CacheService
     */
    function cache(): ?\Framework\Services\CacheService
    {
        return $GLOBALS['cache'] ?? null;
    }
}

if (!function_exists('cache_get')) {
    /**
     * Récupérer depuis le cache
     */
    function cache_get(string $key, $default = null)
    {
        $cache = cache();
        return $cache ? $cache->get($key, $default) : $default;
    }
}

if (!function_exists('cache_set')) {
    /**
     * Enregistrer dans le cache
     */
    function cache_set(string $key, $value, ?int $ttl = null): bool
    {
        $cache = cache();
        return $cache ? $cache->set($key, $value, $ttl) : false;
    }
}

if (!function_exists('cache_has')) {
    /**
     * Vérifier si une clé existe
     */
    function cache_has(string $key): bool
    {
        $cache = cache();
        return $cache ? $cache->has($key) : false;
    }
}

if (!function_exists('cache_delete')) {
    /**
     * Supprimer du cache
     */
    function cache_delete(string $key): bool
    {
        $cache = cache();
        return $cache ? $cache->delete($key) : false;
    }
}

if (!function_exists('cache_clear')) {
    /**
     * Vider tout le cache
     */
    function cache_clear(): bool
    {
        $cache = cache();
        return $cache ? $cache->clear() : false;
    }
}

if (!function_exists('cache_remember')) {
    /**
     * Remember : récupère depuis cache OU exécute et met en cache
     */
    function cache_remember(string $key, callable $callback, ?int $ttl = null)
    {
        $cache = cache();
        return $cache ? $cache->remember($key, $callback, $ttl) : $callback();
    }
}
