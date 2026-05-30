-- ============================================
-- Module Auth - Schéma de base de données
-- ============================================

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) DEFAULT NULL,
  `last_name` VARCHAR(100) DEFAULT NULL,
  `role` ENUM('member', 'moderator', 'admin', 'superadmin') NOT NULL DEFAULT 'member',
  `status` ENUM('active', 'inactive', 'banned', 'pending') NOT NULL DEFAULT 'active',
  `avatar` VARCHAR(255) DEFAULT NULL,
  `last_login` DATETIME DEFAULT NULL,
  `login_count` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `email_verification_token` VARCHAR(64) DEFAULT NULL,
  `password_reset_token` VARCHAR(64) DEFAULT NULL,
  `password_reset_expires` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des tokens "Remember Me"
CREATE TABLE IF NOT EXISTS `remember_tokens` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `token` VARCHAR(64) NOT NULL UNIQUE,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_token` (`token`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `fk_remember_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des tentatives de connexion (sécurité)
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `identifier` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `success` TINYINT(1) NOT NULL DEFAULT 0,
  `attempted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_identifier` (`identifier`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_attempted_at` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des sessions utilisateur (tracking)
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `session_id` VARCHAR(128) NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `device_type` VARCHAR(50) DEFAULT NULL,
  `browser` VARCHAR(50) DEFAULT NULL,
  `os` VARCHAR(50) DEFAULT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `last_activity` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_last_activity` (`last_activity`),
  CONSTRAINT `fk_user_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des permissions utilisateurs (optionnel)
CREATE TABLE IF NOT EXISTS `user_permissions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `permission` VARCHAR(100) NOT NULL,
  `granted` TINYINT(1) NOT NULL DEFAULT 1,
  `granted_by` INT(11) UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_permission` (`user_id`, `permission`),
  KEY `idx_permission` (`permission`),
  CONSTRAINT `fk_user_permissions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion de l'utilisateur admin par défaut
-- Mot de passe: admin123 (à changer immédiatement en production)
INSERT IGNORE INTO `users` (`username`, `email`, `password`, `role`, `status`, `email_verified`, `created_at`) 
VALUES (
  'admin',
  'admin@esport-cms.local',
  '$argon2id$v=19$m=65536,t=4,p=1$cGRVWUhyVEJGYUdqU1Ew$hxQzHfJhYCRvdGR6fVVRPuBLKCJzLHGwOKJxJKRLQ',
  'admin',
  'active',
  1,
  NOW()
);
