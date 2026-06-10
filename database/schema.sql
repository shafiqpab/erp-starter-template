-- ========================================
-- ERP STARTER TEMPLATE - DATABASE SCHEMA
-- ========================================

-- ===== USERS TABLE =====
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `username` VARCHAR(100) UNIQUE NOT NULL,
  `email` VARCHAR(150) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(200) NOT NULL,
  `phone` VARCHAR(20),
  `address` TEXT,
  `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
  `role_id` INT,
  `last_login` DATETIME,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===== ROLES TABLE =====
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `role_name` VARCHAR(100) UNIQUE NOT NULL,
  `role_code` VARCHAR(50) UNIQUE NOT NULL,
  `description` TEXT,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===== PERMISSIONS TABLE =====
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `permission_code` VARCHAR(100) UNIQUE NOT NULL,
  `permission_name` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `module` VARCHAR(100) NOT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===== ROLE PERMISSIONS (অনেক-অনেক সম্পর্ক) =====
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `role_id` INT NOT NULL,
  `permission_id` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_role_permission` (`role_id`, `permission_id`),
  FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===== USER PERMISSION OVERRIDES (ব্যবহারকারী স্তরে অনুমতি ওভাররাইড) =====
CREATE TABLE IF NOT EXISTS `user_permission_overrides` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `permission_id` INT NOT NULL,
  `override_type` ENUM('allow', 'deny') NOT NULL,
  `reason` TEXT,
  `created_by` INT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_user_permission_override` (`user_id`, `permission_id`, `override_type`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===== MENUS TABLE (Tree Structure) =====
CREATE TABLE IF NOT EXISTS `menus` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `menu_name` VARCHAR(150) NOT NULL,
  `menu_code` VARCHAR(100) UNIQUE NOT NULL,
  `parent_id` INT,
  `icon` VARCHAR(100),
  `route` VARCHAR(255),
  `permission_id` INT,
  `sort_order` INT DEFAULT 0,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`parent_id`) REFERENCES `menus` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===== ACTIVITY LOG TABLE =====
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `module` VARCHAR(100),
  `description` TEXT,
  `old_value` LONGTEXT,
  `new_value` LONGTEXT,
  `ip_address` VARCHAR(45),
  `user_agent` TEXT,
  `status` ENUM('success', 'failed', 'warning') DEFAULT 'success',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_module` (`module`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===== PERMISSION CACHE TABLE (পারফরম্যান্স অপটিমাইজেশন) =====
CREATE TABLE IF NOT EXISTS `permission_cache` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL UNIQUE,
  `permissions_json` LONGTEXT,
  `cached_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===== CREATE INDEXES FOR BETTER PERFORMANCE =====
CREATE INDEX `idx_users_status` ON `users` (`status`);
CREATE INDEX `idx_users_role_id` ON `users` (`role_id`);
CREATE INDEX `idx_permissions_module` ON `permissions` (`module`);
CREATE INDEX `idx_menus_parent_id` ON `menus` (`parent_id`);
CREATE INDEX `idx_menus_status` ON `menus` (`status`);
