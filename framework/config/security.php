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
        // Nombre de tokens conservés par action (pool). Toute l'app partage
        // l'action « default » : chaque page/onglet/AJAX ajoute un token. Une
        // valeur trop basse évince les tokens de formulaires encore ouverts et
        // provoque de faux « session expirée ». 50 = large marge multi-onglets.
        'pool_size' => 50,
        'secret' => getenv('CSRF_SECRET') ?: bin2hex(random_bytes(32)),

        // Routes exemptées (API publiques, webhooks)
        'except' => [
            '/api/webhook/*',
            '/api/public/*',
            '/api/odin/metrics',   // ingestion agent O.D.I.N — authentifiée par clé API (X-ODIN-KEY)
            '/marketplace/payment/paypal/webhook', // webhook PayPal — authentifié par signature (verifyWebhookSignature)
            '/gamenodeesport/donations/paypal/webhook', // webhook PayPal dons GameNodeEsport — authentifié par signature (verifyWebhook)
            '/gamenodeesport/boutique/paypal/webhook',  // webhook PayPal boutique GameNodeEsport — authentifié par signature (verifyWebhook)
            '/api/license/validate', // validation de licence — appelée par des sites tiers, authentifiée par clé + signature HMAC
            '/analytics/collect',    // collecte analytics — endpoint public sans état (beacon JS), aucune action sensible
            '/fpsmeter/api/result',  // ingestion des sondes FPSMeter — authentifiée par jeton (réglage ingest_token)
            '/fpsmeter/measure/sample', // sonde live FPSMeter — lecture seule (aucune écriture BDD), pilotée par le navigateur
        ],
    ],

    // ============================================
    // PROTECTION XSS
    // ============================================
    'xss' => [
        // filter_get/post/cookie DÉSACTIVÉS INTENTIONNELLEMENT (2026-07-19).
        // XSSProtection::cleanArray() appelle strip_tags() SANS $allowHtml sur
        // TOUTE valeur de $_GET/$_POST/$_COOKIE, sans liste blanche par champ :
        // ça mutile silencieusement toute donnée légitime contenant '<' ou '>'
        // (JSON, code, texte contenant un opérateur de comparaison, HTML d'un
        // éditeur riche...) AVANT même que le contrôleur ne la voie — aucune
        // erreur, aucune trace, juste une donnée corrompue en silence.
        // Ce n'est de toute façon pas la bonne protection contre le XSS : la
        // vraie protection est l'échappement CONTEXTUEL À L'AFFICHAGE (déjà
        // fait correctement par XSSProtection::escape()/escapeAttr()/escapeJs()
        // /escapeUrl(), utilisés dans les vues), pas la mutilation à l'entrée.
        // Ne pas réactiver sans remplacer cleanArray() par un filtrage opt-in,
        // par champ, avec liste blanche de tags — jamais un strip_tags global.
        'enabled' => true,
        'filter_get' => false,
        'filter_post' => false,
        'filter_cookie' => false,

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

        // Commentaires de blog (anti-flood)
        'comment' => [
            'max_attempts' => 8,
            'window' => 600,                  // 8 commentaires / 10 minutes par IP
        ],

        // Assistant IA public du Marketplace (anti-abus — appels IA payants)
        'marketplace_bot' => [
            'max_attempts' => 12,
            'window' => 300,                  // 12 messages / 5 minutes par IP
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

        // Vérifie l'empreinte auprès de Have I Been Pwned, par k-anonymity :
        // seuls 5 caractères du SHA-1 sortent du serveur. Sans effet si le
        // serveur n'a pas d'accès sortant — la vérification échoue alors en
        // silence, sans bloquer l'inscription.
        'check_compromised' => true,

        // NOTE — `max_age_days` et `prevent_reuse` ont été retirés : la
        // rotation périodique imposée est déconseillée par le NIST
        // (SP 800-63B §5.1.1.2), elle pousse à décliner un même mot de passe.
        // Voir Framework\Security\PasswordPolicy.
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
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com https://www.google.com https://www.gstatic.com https://www.paypal.com https://www.paypalobjects.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com",
            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "img-src 'self' data: https: blob:",
            "media-src 'self'",
            "object-src 'none'",
            "connect-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com https://*.basemaps.cartocdn.com https://*.tile.openstreetmap.org https://www.paypal.com https://www.paypalobjects.com",
            // Lecteurs vidéo intégrés. Seuls les hôtes réellement employés par
            // VideoProviderService sont listés : ouvrir frame-src plus largement
            // reviendrait à autoriser n'importe quel site à s'incruster dans une
            // page. youtube.com accompagne youtube-nocookie.com pour les thèmes
            // qui construisent encore l'URL classique.
            "frame-src https://www.google.com https://www.paypal.com "
                . "https://www.youtube-nocookie.com https://www.youtube.com "
                . "https://player.vimeo.com https://geo.dailymotion.com https://www.tiktok.com "
                // Lecteur et tchat Twitch. Le tchat est servi par www.twitch.tv,
                // le lecteur par player.twitch.tv : les deux sont nécessaires.
                . "https://player.twitch.tv https://www.twitch.tv",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]),

        // Permissions Policy
        'Permissions-Policy' => implode(', ', [
            'geolocation=()',
            'microphone=()',
            'camera=()',
            'payment=(self "https://www.paypal.com")',
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
    // ⚠️ NON IMPLÉMENTÉ — restreindre /admin à une liste d'adresses n'existe
    // pas encore. Mettre `enabled` à true ne protège RIEN.
    //
    // À ne pas confondre avec la liste blanche du Centre de sécurité (table
    // `security_ip_whitelist`), qui est bien active mais fait l'inverse : elle
    // désigne les adresses dont on ignore les signalements, 127.0.0.1 comprise.
    'ip_whitelist' => [
        'enabled' => false,
        'admin_only' => true,
        'ips' => [
            // '127.0.0.1',                   // Localhost
            // '192.168.1.0/24',              // Réseau local
        ],
        'bypass_for_dev' => true,
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
    // ✅ IMPLÉMENTÉ — mais piloté depuis l'administration, pas d'ici.
    //
    // Les réglages vivent dans la table `settings` (Configuration → 2FA) :
    //   twofa_enabled      0 | 1
    //   twofa_scope        'admins' | 'all'
    //   twofa_ttl_minutes  durée de validité du code
    //
    // C'est un réglage d'exploitation, pas de développement : l'administrateur
    // doit pouvoir l'activer ou le couper sans toucher à un fichier — et
    // remettre `twofa_enabled` à 0 en base est le recours prévu s'il perd
    // l'accès à sa boîte de réception.
    //
    // TOTP (Google Authenticator) et codes de secours ont été écartés : voir
    // Auth\Services\TwoFactorService pour le raisonnement.
    '2fa' => [
        'voir' => 'Configuration → 2FA (table settings, clés twofa_*)',
    ],

    // ============================================
    // UPLOAD SÉCURISÉ
    // ============================================
    // ⚠️ NON LU PAR LE CODE — conservé comme référence, à ne pas prendre pour
    // la règle appliquée.
    //
    // Les réceptions de fichiers passent par `Framework\Storage\ImageUploader`,
    // qui porte ses propres limites, PLUS STRICTES que celles écrites ici :
    // 5 Mo par défaut, images uniquement (jpeg, png, gif, webp, et svg sur
    // demande explicite), type lu dans le CONTENU et non dans le nom, puis
    // ré-encodage GD qui purge toute charge dissimulée. Ni `.doc`, ni `.zip`,
    // ni `.rar` ne sont acceptés, contrairement à ce que suggère la liste
    // ci-dessous. L'installation d'un thème par ZIP a son propre contrôle,
    // effectué avant extraction.
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
