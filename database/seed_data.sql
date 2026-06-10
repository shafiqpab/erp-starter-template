-- ========================================
-- DEFAULT DATA (SEED)
-- ========================================

-- ===== DEFAULT ROLES =====
INSERT INTO `roles` (`role_name`, `role_code`, `description`, `status`) VALUES
('Admin', 'admin', 'সিস্টেম প্রশাসক - সব অ্যাক্সেস', 'active'),
('Manager', 'manager', 'ম্যানেজার - সীমিত অ্যাক্সেস', 'active'),
('User', 'user', 'সাধারণ ব্যবহারকারী', 'active'),
('Viewer', 'viewer', 'শুধুমাত্র দেখার অ্যাক্সেস', 'active');

-- ===== DEFAULT PERMISSIONS =====
INSERT INTO `permissions` (`permission_code`, `permission_name`, `description`, `module`, `status`) VALUES
-- User Management
('user.view', 'View Users', 'ব্যবহারকারী দেখতে পারবেন', 'user', 'active'),
('user.create', 'Create User', 'নতুন ব্যবহারকারী তৈরি করতে পারবেন', 'user', 'active'),
('user.edit', 'Edit User', 'ব্যবহারকারী সম্পাদনা করতে পারবেন', 'user', 'active'),
('user.delete', 'Delete User', 'ব্যবহারকারী মুছতে পারবেন', 'user', 'active'),
('user.status', 'User Status Control', 'ব্যবহারকারী স্ট্যাটাস পরিবর্তন করতে পারবেন', 'user', 'active'),

-- Role Management
('role.view', 'View Roles', 'ভূমিকা দেখতে পারবেন', 'role', 'active'),
('role.create', 'Create Role', 'নতুন ভূমিকা তৈরি করতে পারবেন', 'role', 'active'),
('role.edit', 'Edit Role', 'ভূমিকা সম্পাদনা করতে পারবেন', 'role', 'active'),
('role.delete', 'Delete Role', 'ভূমিকা মুছতে পারবেন', 'role', 'active'),

-- Permission Management
('permission.view', 'View Permissions', 'অনুমতি দেখতে পারবেন', 'permission', 'active'),
('permission.create', 'Create Permission', 'নতুন অনুমতি তৈরি করতে পারবেন', 'permission', 'active'),
('permission.edit', 'Edit Permission', 'অনুমতি সম্পাদনা করতে পারবেন', 'permission', 'active'),
('permission.delete', 'Delete Permission', 'অনুমতি মুছতে পারবেন', 'permission', 'active'),

-- Menu Management
('menu.view', 'View Menus', 'মেনু দেখতে পারবেন', 'menu', 'active'),
('menu.create', 'Create Menu', 'নতুন মেনু তৈরি করতে পারবেন', 'menu', 'active'),
('menu.edit', 'Edit Menu', 'মেনু সম্পাদনা করতে পারবেন', 'menu', 'active'),
('menu.delete', 'Delete Menu', 'মেনু মুছতে পারবেন', 'menu', 'active'),

-- Dashboard
('dashboard.view', 'View Dashboard', 'ড্যাশবোর্ড দেখতে পারবেন', 'dashboard', 'active'),

-- Reports
('report.view', 'View Reports', 'রিপোর্ট দেখতে পারবেন', 'report', 'active'),
('report.export', 'Export Reports', 'রিপোর্ট এক্সপোর্ট করতে পারবেন', 'report', 'active'),

-- Settings
('settings.view', 'View Settings', 'সেটিংস দেখতে পারবেন', 'settings', 'active'),
('settings.edit', 'Edit Settings', 'সেটিংস পরিবর্তন করতে পারবেন', 'settings', 'active');

-- ===== ADMIN ROLE PERMISSIONS =====
INSERT INTO `role_permissions` (`role_id`, `permission_id`) 
SELECT 
  (SELECT `id` FROM `roles` WHERE `role_code` = 'admin'),
  `id` FROM `permissions`;

-- ===== MANAGER ROLE PERMISSIONS =====
INSERT INTO `role_permissions` (`role_id`, `permission_id`) 
SELECT 
  (SELECT `id` FROM `roles` WHERE `role_code` = 'manager'),
  `id` FROM `permissions` 
WHERE `permission_code` IN (
  'user.view', 'user.edit', 'user.create',
  'dashboard.view', 'report.view', 'report.export',
  'settings.view'
);

-- ===== USER ROLE PERMISSIONS =====
INSERT INTO `role_permissions` (`role_id`, `permission_id`) 
SELECT 
  (SELECT `id` FROM `roles` WHERE `role_code` = 'user'),
  `id` FROM `permissions` 
WHERE `permission_code` IN (
  'dashboard.view', 'report.view', 'user.view'
);

-- ===== VIEWER ROLE PERMISSIONS =====
INSERT INTO `role_permissions` (`role_id`, `permission_id`) 
SELECT 
  (SELECT `id` FROM `roles` WHERE `role_code` = 'viewer'),
  `id` FROM `permissions` 
WHERE `permission_code` IN (
  'dashboard.view', 'report.view'
);

-- ===== DEFAULT MENUS =====
INSERT INTO `menus` (`menu_name`, `menu_code`, `parent_id`, `icon`, `route`, `permission_id`, `sort_order`, `status`) VALUES
('Dashboard', 'dashboard', NULL, 'fas fa-chart-line', '/dashboard', (SELECT `id` FROM `permissions` WHERE `permission_code` = 'dashboard.view'), 1, 'active'),
('User Management', 'user_management', NULL, 'fas fa-users', NULL, (SELECT `id` FROM `permissions` WHERE `permission_code` = 'user.view'), 2, 'active'),
('Users List', 'users_list', (SELECT `id` FROM `menus` WHERE `menu_code` = 'user_management'), 'fas fa-list', '/users', (SELECT `id` FROM `permissions` WHERE `permission_code` = 'user.view'), 1, 'active'),
('Add User', 'add_user', (SELECT `id` FROM `menus` WHERE `menu_code` = 'user_management'), 'fas fa-user-plus', '/users/create', (SELECT `id` FROM `permissions` WHERE `permission_code` = 'user.create'), 2, 'active'),
('Role Management', 'role_management', NULL, 'fas fa-lock', NULL, (SELECT `id` FROM `permissions` WHERE `permission_code` = 'role.view'), 3, 'active'),
('Roles List', 'roles_list', (SELECT `id` FROM `menus` WHERE `menu_code` = 'role_management'), 'fas fa-list', '/roles', (SELECT `id` FROM `permissions` WHERE `permission_code` = 'role.view'), 1, 'active'),
('Add Role', 'add_role', (SELECT `id` FROM `menus` WHERE `menu_code` = 'role_management'), 'fas fa-plus', '/roles/create', (SELECT `id` FROM `permissions` WHERE `permission_code` = 'role.create'), 2, 'active'),
('Permission Management', 'permission_management', NULL, 'fas fa-shield-alt', NULL, (SELECT `id` FROM `permissions` WHERE `permission_code` = 'permission.view'), 4, 'active'),
('Permissions List', 'permissions_list', (SELECT `id` FROM `menus` WHERE `menu_code` = 'permission_management'), 'fas fa-list', '/permissions', (SELECT `id` FROM `permissions` WHERE `permission_code` = 'permission.view'), 1, 'active'),
('Menu Management', 'menu_management', NULL, 'fas fa-bars', NULL, (SELECT `id` FROM `permissions` WHERE `permission_code` = 'menu.view'), 5, 'active'),
('Menus List', 'menus_list', (SELECT `id` FROM `menus` WHERE `menu_code` = 'menu_management'), 'fas fa-list', '/menus', (SELECT `id` FROM `permissions` WHERE `permission_code` = 'menu.view'), 1, 'active'),
('Reports', 'reports', NULL, 'fas fa-file-alt', '/reports', (SELECT `id` FROM `permissions` WHERE `permission_code` = 'report.view'), 6, 'active'),
('Settings', 'settings', NULL, 'fas fa-cog', '/settings', (SELECT `id` FROM `permissions` WHERE `permission_code` = 'settings.view'), 7, 'active'),
('Debug Permission Checker', 'debug_permission', NULL, 'fas fa-bug', '/debug/permissions', (SELECT `id` FROM `permissions` WHERE `permission_code` = 'permission.view'), 8, 'active');

-- ===== DEFAULT ADMIN USER (পাসওয়ার্ড: admin@123) =====
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `phone`, `address`, `status`, `role_id`) VALUES
('admin', 'admin@erp.local', '$2y$10$KIX5fHO0dI/9qRfZ0x.LOe8GH.Z9n3Z9Z9Z9Z9Z9Z9Z9Z9Z9Z9Z9Z', 'Admin User', '+880-1XXX-XXXXXX', 'Dhaka, Bangladesh', 'active', (SELECT `id` FROM `roles` WHERE `role_code` = 'admin'));

-- Note: Password hash is for 'admin@123' using bcrypt
