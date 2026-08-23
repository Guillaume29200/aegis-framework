-- Double authentification par e-mail.
--
-- `config/security.php` déclarait un bloc `2fa` depuis l'origine, avec le
-- commentaire « À activer quand implémenté ». Voici l'implémentation.
--
-- Le code n'est PAS stocké en clair : une lecture de la base — sauvegarde
-- égarée, injection SQL, accès d'un prestataire — donnerait sinon le second
-- facteur de tous ceux qui sont en train de se connecter. On garde une
-- empreinte SHA-256, suffisante ici : le secret est court, aléatoire et vit
-- dix minutes, ce qui rend l'attaque par dictionnaire sans objet.
--
-- Rejouable : la migration a pu être appliquée à la main avant d'être
-- enregistrée.

CREATE TABLE IF NOT EXISTS `auth_2fa_codes` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED NOT NULL,
    `code_hash`   CHAR(64)     NOT NULL COMMENT 'SHA-256 du code à 6 chiffres',
    `tentatives`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `expire_le`   DATETIME     NOT NULL,
    `utilise_le`  DATETIME     NULL DEFAULT NULL,
    `ip`          VARCHAR(45)  NULL DEFAULT NULL,
    `cree_le`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    -- Le parcours de vérification cherche toujours le dernier code vivant
    -- d'un utilisateur : cet index couvre exactement cette requête.
    KEY `idx_user_expire` (`user_id`, `expire_le`),
    KEY `idx_purge` (`expire_le`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Réglages, pilotés depuis Configuration → 2FA.
--
-- `twofa_enabled` à 0 par défaut : activer la double authentification sur une
-- installation dont l'envoi d'e-mail n'est pas configuré verrouillerait
-- l'administrateur dehors. L'interface impose un envoi de test avant de
-- permettre l'activation.
INSERT IGNORE INTO `settings` (`param_key`, `param_value`, `param_type`) VALUES
    ('twofa_enabled',      '0',      'bool'),
    ('twofa_scope',        'admins', 'string'),
    ('twofa_ttl_minutes',  '10',     'int');
