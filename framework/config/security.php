<?php
declare(strict_types=1);

/**
 * Aegis Framework V4 - Configuration Sécurité
 *
 * - CSRF Protection
 * - XSS Filtering
 * - Rate Limiting
 * - Sessions sécurisées
 * - Password policy
 * - Security headers
 */

return [
    // ============================================
    // SESSIONS SÉCURISÉES
    // ============================================
    'session' => [
        'name' => 'AEGIS_FRAMEWORK_SESSION',
        'lifetime' => 0,                      // Expire à fermeture navigateur
        'path' => '/',
        'domain' => '',                       // Laisser vide pour auto-detect
        'secure' => (getenv('APP_ENV') !== 'local' && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,                   // Pas accessible via JavaScript
        'samesite' => 'Strict',               // Protection CSRF niveau cookie
        'regenerate_interval' => 300,         // Régénérer ID toutes les 5 minutes
        'gc_maxlifetime' => 7200,             // 2 heures de durée max (cohérent avec lifetime=0)
        'login_url' => '/auth/login',         // URL de redirection après expiration
        // Liaison de la session à l'IP cliente dans l'empreinte anti-vol :
        //   'off'    = ne lie pas l'IP (le plus tolérant — recommandé en local/derrière proxy variable)
        //   'subnet' = lie au sous-réseau (/24 IPv4, /64 IPv6) — bon compromis (défaut)
        //   'strict' = lie à l'IP exacte (le plus strict, peut déconnecter si l'IP change)
        'ip_binding' => 'subnet',
        // Régénération sans supprimer immédiatement l'ancien ID (évite les courses
        // avec les requêtes AJAX concurrentes qui causent des déconnexions aléatoires).
        'regenerate_delete_old' => false,
    ],

    // ============================================
    // PROTECTION CSRF
    // ============================================
    'csrf' => [
        'enabled' => true,
        'token_name' => 'csrf_token',         // Nom du champ dans formulaires
        'header_name' => 'X-CSRF-Token',      // Nom du header pour AJAX
        'token_length' => 32,                 // Longueur du token en bytes
        'expire' => 3600,                     // Token expire après 1h
        'secret' => getenv('CSRF_SECRET') ?: bin2hex(random_bytes(32)),

        // Routes exemptées (API publiques, webhooks)
        'except' => [
            '/api/webhook/*',
            '/api/public/*',
            '/api/odin/metrics',   // ingestion agent O.D.I.N — authentifiée par clé API (X-ODIN-KEY)
            '/marketplace/payment/paypal/webhook', // webhook PayPal — authentifié par signature (verifyWebhookSignature)
            '/api/license/validate', // validation de licence — appelée par des sites tiers, authentifiée par clé + signature HMAC
        ],
    ],

    // ============================================
    // PROTECTION XSS
    // ============================================
    'xss' => [
        'enabled' => true,
        'filter_get' => true,                 // Filtrer $_GET
        'filter_post' => true,                // Filtrer $_POST
        'filter_cookie' => true,              // Filtrer $_COOKIE

        // Tags HTML autorisés (pour éditeur WYSIWYG)
        'allowed_tags' => '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><code><pre><img><video><iframe>',

        // Attributs autorisés par tag
        'allowed_attributes' => [
            'a' => ['href', 'title', 'target', 'rel'],
            'img' => ['src', 'alt', 'title', 'width', 'height'],
            'video' => ['src', 'controls', 'width', 'height'],
            'iframe' => ['src', 'width', 'height', 'frameborder'],
        ],

        // Protocoles autorisés pour liens
        'allowed_protocols' => ['http', 'https', 'mailto', 'tel'],
    ],

    // ============================================
    // RATE LIMITING
    // ============================================
    'rate_limit' => [
        'enabled' => true,

        // Limite globale (par IP)
        'global' => [
            'max_requests' => 100,
            'window' => 60,                   // 100 requêtes / 60 secondes
        ],

        // Limite authentification (tentatives login)
        'auth' => [
            'max_attempts' => 5,              // 5 tentatives max
            'window' => 300,                  // Dans une fenêtre de 5 minutes
            'lockout_duration' => 900,        // Ban 15 minutes
        ],

        // Limite API
        'api' => [
            'max_requests' => 1000,
            'window' => 3600,                 // 1000 requêtes / heure
        ],

        // Limite actions sensibles (changement password, suppression compte)
        'sensitive' => [
            'max_attempts' => 3,
            'window' => 600,                  // 3 tentatives / 10 minutes
            'lockout_duration' => 1800,       // Ban 30 minutes
        ],

        // Limite inscription (anti-spam)
        'registration' => [
            'max_attempts' => 3,
            'window' => 3600,                 // 3 inscriptions max / heure par IP
        ],

        // Limite reset password
        'password_reset' => [
            'max_attempts' => 3,
            'window' => 3600,                 // 3 demandes / heure
        ],
    ],

    // ============================================
    // FIREWALL APPLICATIF / ANTI-ABUS HTTP
    // ============================================
    'firewall' => [
        'enabled' => true,
        'trust_proxy_headers' => false,
        'rate_block_seconds' => 900,
        'suspicious_block_seconds' => 3600,

        // IP autorisees sans limitation applicative. Garder localhost pour WAMP.
        'trusted_ips' => [
            '127.0.0.1',
            '::1',
        ],

        // Prefixes jamais limites par cette couche.
        'exempt_prefixes' => [
            '/framework/assets/',
            '/favicon.ico',
            '/robots.txt',
        ],

        'limits' => [
            'global' => ['max_requests' => 180, 'window' => 60],
            'auth' => ['max_requests' => 30, 'window' => 60],
            'admin' => ['max_requests' => 240, 'window' => 60],
            'api' => ['max_requests' => 120, 'window' => 60],
        ],

        'suspicious_paths' => [
            '/.env',
            '/wp-login.php',
            '/xmlrpc.php',
            '/phpmyadmin',
            '/pma',
            '/adminer',
            '/vendor/phpunit',
            '/config.php',
            '/backup',
            '/shell',
            '/cmd.php',
        ],

        'blocked_user_agents' => [
            'sqlmap',
            'nikto',
            'acunetix',
            'dirbuster',
            'masscan',
            'zgrab',
        ],
    ],

    // ============================================
    // POLITIQUE MOTS DE PASSE
    // ============================================
    'password' => [
        'algorithm' => PASSWORD_ARGON2ID,     // Algo le plus sécurisé

        // Options Argon2ID
        'options' => [
            'memory_cost' => 65536,           // 64 MB
            'time_cost' => 4,                 // 4 itérations
            'threads' => 2,                   // 2 threads
        ],

        // Règles de complexité
        'min_length' => 12,                   // 12 caractères minimum
        'require_uppercase' => true,          // Au moins 1 majuscule
        'require_lowercase' => true,          // Au moins 1 minuscule
        'require_numbers' => true,            // Au moins 1 chiffre
        'require_special' => true,            // Au moins 1 caractère spécial

        // Sécurité avancée
        'max_age_days' => 90,                 // Forcer changement tous les 90j
        'prevent_reuse' => 5,                 // Empêcher réutilisation des 5 derniers
        'check_compromised' => true,          // Vérifier si mot de passe compromis (haveibeenpwned API)
    ],

    // ============================================
    // HEADERS DE SÉCURITÉ HTTP
    // ============================================
    'headers' => [
        // XSS Protection
        'X-XSS-Protection' => '1; mode=block',

        // MIME Type Sniffing Protection
        'X-Content-Type-Options' => 'nosniff',

        // Clickjacking Protection
        'X-Frame-Options' => 'DENY',

        // Referrer Policy
        'Referrer-Policy' => 'strict-origin-when-cross-origin',

        // Content Security Policy
        'Content-Security-Policy' => implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com https://www.google.com https://www.gstatic.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com",
            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "img-src 'self' data: https: blob:",
            "media-src 'self'",
            "object-src 'none'",
            "connect-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com https://*.basemaps.cartocdn.com https://*.tile.openstreetmap.org",
            "frame-src https://www.google.com",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]),

        // Permissions Policy
        'Permissions-Policy' => implode(', ', [
            'geolocation=()',
            'microphone=()',
            'camera=()',
            'payment=()',
            'usb=()',
            'magnetometer=()',
            'gyroscope=()',
            'accelerometer=()',
        ]),

        // HSTS (HTTPS uniquement - décommenter en prod)
        // 'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
    ],

    // ============================================
    // WHITELIST IP (accès admin)
    // ============================================
    'ip_whitelist' => [
        'enabled' => false,                   // Activer en production
        'admin_only' => true,                 // Seulement pour /admin
        'ips' => [
            // '127.0.0.1',                   // Localhost
            // '192.168.1.0/24',              // Réseau local
        ],
        'bypass_for_dev' => true,             // Bypass en mode dev
    ],

    // ============================================
    // DÉTECTION ATTAQUES
    // ============================================
    'attack_detection' => [
        'enabled' => true,

        // SQL Injection patterns
        'sql_injection_patterns' => [
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bDROP\b.*\bTABLE\b)/i',
            '/(\bINSERT\b.*\bINTO\b)/i',
            '/(--|#|\/\*|\*\/|;)/',
        ],

        // XSS patterns
        'xss_patterns' => [
            '/<script[^>]*>.*?<\/script>/is',
            '/javascript:/i',
            '/on\w+\s*=/i',                   // onclick, onerror, etc.
        ],

        // Path traversal
        'path_traversal_patterns' => [
            '/\.\.(\/|\\\\)/',
            '/\/etc\/passwd/',
            '/\/windows\/system32/i',
        ],

        // Action si détection
        'action' => 'block',                  // 'block' ou 'log'
        'log_attempts' => true,
        'ban_duration' => 3600,               // Ban 1h
    ],

    // ============================================
    // LOGGING SÉCURITÉ
    // ============================================
    'logging' => [
        'enabled' => true,
        'log_failed_auth' => true,            // Logger tentatives login échouées
        'log_rate_limit' => true,             // Logger dépassements rate limit
        'log_suspicious_activity' => true,    // Logger activité suspecte
        'log_ip_changes' => true,             // Logger changements IP durant session
        'log_csrf_failures' => true,          // Logger échecs validation CSRF
        'log_file' => __DIR__ . '/../../logs/security.log',
    ],

    // ============================================
    // TWO-FACTOR AUTHENTICATION (2FA)
    // ============================================
    '2fa' => [
        'enabled' => false,                   // À activer quand implémenté
        'required_for_admin' => true,         // Obligatoire pour admins
        'methods' => ['totp', 'email'],       // TOTP (Google Authenticator) ou Email
        'backup_codes' => 10,                 // Nombre de codes de backup
    ],

    // ============================================
    // UPLOAD SÉCURISÉ
    // ============================================
    'upload' => [
        'max_size' => 10485760,               // 10 MB max
        'allowed_extensions' => [
            'jpg', 'jpeg', 'png', 'gif', 'webp',
            'pdf', 'doc', 'docx', 'xls', 'xlsx',
            'zip', 'rar',
        ],
        'allowed_mime_types' => [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
        ],
        'scan_virus' => false,                // ClamAV si disponible
        'quarantine_suspicious' => true,
    ],

];
