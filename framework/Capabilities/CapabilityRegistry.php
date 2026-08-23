<?php
declare(strict_types=1);

namespace Framework\Capabilities;

/**
 * Registre des « capacités » de module — briques transverses réutilisables
 * (Markdown, Cache, IA, SEO, RGPD, Analytics, reCAPTCHA) qu'un module peut activer
 * via son module.json (`"capabilities": ["markdown", ...]`) au lieu de recoder
 * le même code à chaque fois.
 *
 * Source UNIQUE de vérité pour :
 *   - l'UI du générateur de module (cases à cocher + descriptions) ;
 *   - le runtime (CapabilityManager) qui câble helpers/assets/routes.
 *
 * `available` = capacité réellement implémentée (activable) ; les autres sont
 * affichées « bientôt » dans le générateur pour montrer la feuille de route.
 */
final class CapabilityRegistry
{
    /** @var array<string,array{name:string,icon:string,description:string,needs_db:bool,available:bool}> */
    private const CAPS = [
        'markdown' => [
            'name'        => 'Markdown',
            'icon'        => '✍️',
            'description' => "Éditeur Markdown automatique sur les zones de texte (gras, listes, liens, code, aperçu) et rendu HTML sécurisé partagé. Un seul moteur pour tous les modules.",
            'needs_db'    => false,
            'available'   => true,
        ],
        'cache' => [
            'name'        => 'Cache',
            'icon'        => '⚡',
            'description' => "Mise en cache clé/valeur (get, set, remember) via le CacheService du framework, pour accélérer les requêtes coûteuses. Le service généré met ses statistiques en cache d'exemple.",
            'needs_db'    => false,
            'available'   => true,
        ],
        'ai' => [
            'name'        => 'Modèles IA',
            'icon'        => '🤖',
            'description' => "Accès aux modèles IA configurés (Configuration → Modèles IA) : modèle par défaut, provider, capacités. Idéal pour brancher de la génération de contenu.",
            'needs_db'    => false,
            'available'   => true,
        ],
        'seo' => [
            'name'        => 'SEO',
            'icon'        => '🔎',
            'description' => "Balises meta par page (titre, description, canonical, Open Graph, Twitter Card) via seo_head(), réutilisant les défauts site (nom, image OG) de Configuration → SEO & médias.",
            'needs_db'    => false,
            'available'   => true,
        ],
        'rgpd' => [
            'name'        => 'RGPD / Cookies',
            'icon'        => '🍪',
            'description' => "Bandeau de consentement cookies réutilisant le composant central du framework, piloté depuis Configuration → RGPD. Un appel à cookie_banner() sur vos pages publiques suffit.",
            'needs_db'    => false,
            'available'   => true,
        ],
        'analytics' => [
            'name'        => 'Analytics',
            'icon'        => '📊',
            'description' => "Injection du tracker d'audience sur vos pages publiques via analytics_head(), si le module Analytics est installé et activé. Statistiques dans Administration → Analytics.",
            'needs_db'    => false,
            'available'   => true,
        ],
        'turbonav' => [
            'name'        => 'TurboNav',
            'icon'        => '⚡',
            'description' => "Navigation AJAX sur les pages publiques du module : préchargement au survol, remplacement du contenu sans rechargement complet. Nécessite que TurboNav soit activé dans Configuration — sinon le script se met en veille de lui-même.",
            'needs_db'    => false,
            'available'   => true,
        ],
        'recaptcha' => [
            'name'        => 'reCAPTCHA',
            'icon'        => '🛡️',
            'description' => "Protection anti-bot des formulaires (script + vérification par zone) via le service reCAPTCHA du framework, configuré dans Configuration → reCAPTCHA.",
            'needs_db'    => false,
            'available'   => true,
        ],
    ];

    /** @return array<string,array{name:string,icon:string,description:string,needs_db:bool,available:bool}> */
    public static function all(): array
    {
        return self::CAPS;
    }

    /** @return array{name:string,icon:string,description:string,needs_db:bool,available:bool}|null */
    public static function get(string $key): ?array
    {
        return self::CAPS[$key] ?? null;
    }

    /** Toutes les clés de capacités connues. @return string[] */
    public static function keys(): array
    {
        return array_keys(self::CAPS);
    }

    /** Clés des capacités réellement implémentées (activables). @return string[] */
    public static function availableKeys(): array
    {
        return array_keys(array_filter(self::CAPS, fn($c) => $c['available']));
    }

    /** Ne garde que les capacités connues ET disponibles parmi une liste. */
    public static function sanitize(array $requested): array
    {
        $avail = self::availableKeys();
        $out = [];
        foreach ($requested as $k) {
            $k = strtolower(trim((string) $k));
            if (in_array($k, $avail, true) && !in_array($k, $out, true)) { $out[] = $k; }
        }
        return $out;
    }
}
