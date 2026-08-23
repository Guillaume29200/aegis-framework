<?php
declare(strict_types=1);

namespace Framework\Services;

/**
 * Renommage du préfixe public d'un module.
 *
 * Un module qui expose un espace visiteur déclare son préfixe canonique dans
 * son module.json ("public": {"prefix": "gamenodeesport"}). L'administrateur
 * peut lui en substituer un autre — /site, /web, /monsite — sans qu'aucune
 * ligne du module ne change.
 *
 * La substitution se fait à deux endroits, et deux seulement :
 *   — Router::addRoute()  pour les URL qui entrent  (déclaration des routes) ;
 *   — url()               pour les URL qui sortent  (liens, redirections).
 *
 * Le préfixe d'administration (/admin/...) n'est jamais concerné.
 */
final class PublicPrefix
{
    /** canonique => préfixe effectif. Seules les entrées renommées y figurent. */
    private static array $map = [];

    /** Tous les canoniques connus, renommés ou non : sert aux collisions. */
    private static array $canonical = [];

    private static bool $loaded = false;

    /**
     * Segments de premier niveau qu'un préfixe ne peut pas revendiquer : ils
     * appartiennent au cœur, au serveur web, ou serviraient une page blanche.
     */
    public const RESERVED = [
        'admin', 'api', 'assets', 'auth', 'cache', 'config', 'console', 'cron',
        'data', 'framework', 'install', 'logs', 'login', 'logout', 'modules',
        'public', 'register', 'storage', 'system', 'themes', 'uploads', 'vendor',
    ];

    /**
     * Charge la carte depuis la base.
     *
     * Appelé une fois au démarrage, avant que les modules n'enregistrent leurs
     * routes. Une base injoignable ne doit pas empêcher le site de répondre :
     * sans carte, chacun garde son préfixe canonique.
     */
    public static function load(Database $db, array $declared): void
    {
        self::$canonical = $declared;   // module => préfixe canonique
        self::$map       = [];
        self::$loaded    = true;

        if ($declared === []) { return; }

        try {
            $rows = $db->query('SELECT name, public_prefix FROM modules WHERE public_prefix <> \'\'') ?: [];
        } catch (\Throwable) {
            return;
        }

        foreach ($rows as $row) {
            $module = (string) ($row['name'] ?? '');
            $custom = self::normalize((string) ($row['public_prefix'] ?? ''));
            $canon  = self::normalize((string) ($declared[$module] ?? ''));

            if ($canon === '' || $custom === '' || $custom === $canon) { continue; }
            self::$map[$canon] = $custom;
        }
    }

    /**
     * Traduit un chemin interne vers le préfixe effectif.
     *
     * Ne touche qu'au premier segment, et seulement s'il correspond exactement
     * à un canonique renommé : /gamenodeesportique reste intact.
     */
    public static function translate(string $path): string
    {
        if (self::$map === [] || $path === '') { return $path; }

        $lead = $path[0] === '/' ? '/' : '';
        $rest = $lead === '/' ? substr($path, 1) : $path;

        $cut     = strcspn($rest, '/?#');
        $segment = substr($rest, 0, $cut);

        $custom = self::$map[strtolower($segment)] ?? null;
        if ($custom === null) { return $path; }

        return $lead . $custom . substr($rest, $cut);
    }

    /** Préfixe effectif d'un module, canonique compris s'il n'a pas été renommé. */
    public static function effective(string $module): string
    {
        $canon = self::$canonical[$module] ?? '';
        return $canon === '' ? '' : (self::$map[$canon] ?? $canon);
    }

    public static function canonical(string $module): string
    {
        return self::$canonical[$module] ?? '';
    }

    /** Les canoniques effectivement renommés : sert à la redirection 301. */
    public static function renamed(): array
    {
        return self::$map;
    }

    public static function isLoaded(): bool
    {
        return self::$loaded;
    }

    /** Remet le registre à zéro. Réservé aux tests. */
    public static function reset(): void
    {
        self::$map = [];
        self::$canonical = [];
        self::$loaded = false;
    }

    public static function normalize(string $value): string
    {
        return strtolower(trim(trim($value), '/'));
    }

    /**
     * Valide un préfixe candidat.
     *
     * Renvoie un message d'erreur, ou null si le préfixe est acceptable. Le
     * module concerné est exclu de la recherche de collision : se réattribuer
     * son propre préfixe n'est pas un conflit.
     *
     * @param array $taken module => préfixe déjà en vigueur (canonique ou choisi)
     */
    public static function validate(string $candidate, string $forModule, array $taken): ?string
    {
        $p = self::normalize($candidate);

        if ($p === '') {
            return 'Le préfixe ne peut pas être vide.';
        }
        if (!preg_match('/^[a-z0-9][a-z0-9-]{1,39}$/', $p)) {
            return 'Le préfixe doit faire 2 à 40 caractères, en minuscules, chiffres ou tirets, et commencer par une lettre ou un chiffre.';
        }
        if (str_contains($p, '--')) {
            return 'Le préfixe ne peut pas contenir deux tirets de suite.';
        }
        if (str_ends_with($p, '-')) {
            return 'Le préfixe ne peut pas se terminer par un tiret.';
        }
        if (in_array($p, self::RESERVED, true)) {
            return sprintf('« %s » est réservé par le cœur du système.', $p);
        }
        if (preg_match('/\.(php|html?|js|css)$/', $p)) {
            return 'Le préfixe ne peut pas ressembler à un nom de fichier.';
        }

        foreach ($taken as $module => $prefix) {
            if ($module === $forModule) { continue; }
            if (self::normalize((string) $prefix) === $p) {
                return sprintf('« %s » est déjà utilisé par le module %s.', $p, $module);
            }
        }

        return null;
    }
}
