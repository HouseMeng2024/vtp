-- Upgrade default admin data to English i18n defaults.
-- Compatible with MySQL 5.7.
-- This script only normalizes built-in records. Custom records are preserved.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Keep default admin password as admin123 and normalize built-in names.
UPDATE `vtp_admin_user`
SET `password` = '$2y$12$FUyMIXrNAocLiLyVw1bWO.uTFnCan1dcOKyfNzFCsdlZ63tpFfSaK',
    `nickname` = 'Super Admin',
    `update_time` = NOW()
WHERE `id` = 1 AND `username` = 'admin';
INSERT INTO `vtp_admin_role` (`id`, `name`, `code`, `sort`, `status`, `data_scope`, `remark`, `create_time`, `update_time`) VALUES
(1, 'Super Administrator', 'super_admin', 1, 1, 'all', 'Full access', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `code` = VALUES(`code`),
  `sort` = VALUES(`sort`),
  `status` = VALUES(`status`),
  `data_scope` = VALUES(`data_scope`),
  `remark` = VALUES(`remark`),
  `update_time` = VALUES(`update_time`);

INSERT INTO `vtp_admin_user_role` (`user_id`, `role_id`, `create_time`) VALUES
(1, 1, NOW())
ON DUPLICATE KEY UPDATE
  `user_id` = VALUES(`user_id`),
  `role_id` = VALUES(`role_id`),
  `create_time` = VALUES(`create_time`);

INSERT INTO `vtp_system_config_group` (`id`, `key`, `title`, `sort`, `is_system`, `status`, `create_time`, `update_time`) VALUES
(1, 'system', 'System', 100, 1, 1, NOW(), NOW()),
(2, 'admin', 'Admin', 200, 1, 1, NOW(), NOW()),
(3, 'index', 'Index', 300, 1, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `key` = VALUES(`key`),
  `title` = VALUES(`title`),
  `sort` = VALUES(`sort`),
  `is_system` = VALUES(`is_system`),
  `status` = VALUES(`status`),
  `update_time` = VALUES(`update_time`);

INSERT INTO `vtp_system_config_tab` (`id`, `group_id`, `key`, `title`, `sort`, `is_system`, `status`, `create_time`, `update_time`) VALUES
(1, 1, 'system_basic', 'Basic Rules', 100, 1, 1, NOW(), NOW()),
(2, 1, 'system_upload', 'Upload Rules', 200, 1, 1, NOW(), NOW()),
(3, 1, 'system_security', 'Security Rules', 300, 1, 1, NOW(), NOW()),
(4, 2, 'admin_basic', 'Admin Basic', 100, 1, 1, NOW(), NOW()),
(5, 2, 'admin_login', 'Login Security', 200, 1, 1, NOW(), NOW()),
(6, 3, 'index_site', 'Site Info', 100, 1, 1, NOW(), NOW()),
(7, 3, 'index_seo', 'SEO Settings', 200, 1, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `group_id` = VALUES(`group_id`),
  `key` = VALUES(`key`),
  `title` = VALUES(`title`),
  `sort` = VALUES(`sort`),
  `is_system` = VALUES(`is_system`),
  `status` = VALUES(`status`),
  `update_time` = VALUES(`update_time`);

INSERT INTO `vtp_system_config` (`id`, `group_id`, `tab_id`, `group`, `key`, `value`, `type`, `name`, `remark`, `options`, `sort`, `is_system`, `status`, `create_time`, `update_time`) VALUES
(1, 1, 2, 'system_upload', 'upload_max_size', '10', 'number', 'Upload Size Limit', 'Unit: MB. Applies to uploads in all modules by default.', '', 100, 1, 1, NOW(), NOW()),
(2, 1, 2, 'system_upload', 'upload_ext', 'jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip', 'text', 'Allowed Extensions', 'Use commas to separate multiple extensions.', '', 101, 1, 1, NOW(), NOW()),
(3, 1, 3, 'system_security', 'password_min_length', '6', 'number', 'Password Min Length', 'Minimum password length for system accounts.', '', 100, 1, 1, NOW(), NOW()),
(4, 1, 3, 'system_security', 'login_max_attempts', '5', 'number', 'Max Login Attempts', 'Temporarily lock the account after this number of failed attempts.', '', 101, 1, 1, NOW(), NOW()),
(5, 1, 3, 'system_security', 'login_lock_seconds', '900', 'number', 'Login Lock Duration', 'Unit: seconds.', '', 102, 1, 1, NOW(), NOW()),
(6, 2, 4, 'admin_basic', 'title', 'VTP Admin', 'text', 'Admin Title', 'Browser title and top brand name for the admin panel.', '', 100, 1, 1, NOW(), NOW()),
(7, 2, 4, 'admin_basic', 'logo', '', 'image', 'Admin Logo', 'Logo used on the admin login page and header.', '', 101, 1, 1, NOW(), NOW()),
(8, 2, 4, 'admin_basic', 'description', 'General admin system', 'textarea', 'Admin Description', 'Description shown on the admin login page.', '', 102, 1, 1, NOW(), NOW()),
(9, 2, 5, 'admin_login', 'token_expire', '86400', 'number', 'Admin Session TTL', 'Unit: seconds. Applies only to the admin module.', '', 100, 1, 1, NOW(), NOW()),
(10, 2, 5, 'admin_login', 'captcha_enabled', '0', 'switch', 'Login Captcha', 'Require captcha when signing in to the admin panel.', '', 101, 1, 1, NOW(), NOW()),
(11, 3, 6, 'index_site', 'title', 'VTP', 'text', 'Site Title', 'Default site title for the index module.', '', 100, 1, 1, NOW(), NOW()),
(12, 3, 6, 'index_site', 'logo', '', 'image', 'Site Logo', 'Logo used by the index site.', '', 101, 1, 1, NOW(), NOW()),
(13, 3, 7, 'index_seo', 'seo_title', 'VTP', 'text', 'SEO Title', 'Default SEO title for the index site.', '', 100, 1, 1, NOW(), NOW()),
(14, 3, 7, 'index_seo', 'seo_keywords', '', 'text', 'SEO Keywords', 'Use commas to separate multiple keywords.', '', 101, 1, 1, NOW(), NOW()),
(15, 3, 7, 'index_seo', 'seo_description', '', 'textarea', 'SEO Description', 'Default SEO description for the index site.', '', 102, 1, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `group_id` = VALUES(`group_id`),
  `tab_id` = VALUES(`tab_id`),
  `group` = VALUES(`group`),
  `key` = VALUES(`key`),
  `value` = VALUES(`value`),
  `type` = VALUES(`type`),
  `name` = VALUES(`name`),
  `remark` = VALUES(`remark`),
  `options` = VALUES(`options`),
  `sort` = VALUES(`sort`),
  `is_system` = VALUES(`is_system`),
  `status` = VALUES(`status`),
  `update_time` = VALUES(`update_time`);

INSERT INTO `vtp_dict_type` (`id`, `name`, `type`, `sort`, `status`, `remark`, `create_time`, `update_time`) VALUES
(1, 'Common Status', 'common_status', 1, 1, 'Common enabled and disabled status.', NOW(), NOW()),
(2, 'Switch Status', 'switch_status', 2, 1, 'Common switch status.', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `type` = VALUES(`type`),
  `sort` = VALUES(`sort`),
  `status` = VALUES(`status`),
  `remark` = VALUES(`remark`),
  `update_time` = VALUES(`update_time`);

INSERT INTO `vtp_dict_data` (`type_id`, `label`, `value`, `tag_type`, `sort`, `status`, `remark`, `create_time`, `update_time`) VALUES
(1, 'Enabled', '1', 'success', 1, 1, '', NOW(), NOW()),
(1, 'Disabled', '0', 'info', 2, 1, '', NOW(), NOW()),
(2, 'On', '1', 'success', 1, 1, '', NOW(), NOW()),
(2, 'Off', '0', 'info', 2, 1, '', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `label` = VALUES(`label`),
  `tag_type` = VALUES(`tag_type`),
  `sort` = VALUES(`sort`),
  `status` = VALUES(`status`),
  `remark` = VALUES(`remark`),
  `update_time` = VALUES(`update_time`);

-- Remove known duplicate test menus created during earlier local generation tests.
DELETE FROM `vtp_admin_role_menu` WHERE `menu_id` BETWEEN 74 AND 86;
DELETE FROM `vtp_admin_menu` WHERE `id` BETWEEN 74 AND 86;
INSERT INTO `vtp_admin_menu` (`id`, `parent_id`, `type`, `title`, `permission`, `path`, `component`, `icon`, `sort`, `visible`, `status`, `create_time`, `update_time`) VALUES
(1, 0, 1, 'System Settings', '', '/system', '', 'Setting', 400, 1, 1, NOW(), NOW()),
(2, 48, 2, 'Admin Users', 'admin:user:list', '/permission/users', 'system/user/index', 'User', 100, 1, 1, NOW(), NOW()),
(3, 2, 3, 'Create Admin', 'admin:user:create', '', '', '', 100, 0, 1, NOW(), NOW()),
(4, 2, 3, 'Edit Admin', 'admin:user:update', '', '', '', 101, 0, 1, NOW(), NOW()),
(5, 2, 3, 'Delete Admin', 'admin:user:delete', '', '', '', 102, 0, 1, NOW(), NOW()),
(6, 48, 2, 'Roles', 'admin:role:list', '/permission/roles', 'system/role/index', 'UserFilled', 101, 1, 1, NOW(), NOW()),
(7, 48, 2, 'Menus', 'admin:menu:list', '/permission/menus', 'system/menu/index', 'Menu', 102, 1, 1, NOW(), NOW()),
(8, 2, 3, 'Toggle Admin Status', 'admin:user:status', '', '', '', 103, 0, 1, NOW(), NOW()),
(9, 6, 3, 'Create Role', 'admin:role:create', '', '', '', 100, 0, 1, NOW(), NOW()),
(10, 6, 3, 'Edit Role', 'admin:role:update', '', '', '', 101, 0, 1, NOW(), NOW()),
(11, 6, 3, 'Toggle Role Status', 'admin:role:status', '', '', '', 102, 0, 1, NOW(), NOW()),
(12, 6, 3, 'Delete Role', 'admin:role:delete', '', '', '', 103, 0, 1, NOW(), NOW()),
(13, 6, 3, 'Assign Role Permissions', 'admin:role:permission', '', '', '', 104, 0, 1, NOW(), NOW()),
(14, 7, 3, 'Create Menu', 'admin:menu:create', '', '', '', 100, 0, 1, NOW(), NOW()),
(15, 7, 3, 'Edit Menu', 'admin:menu:update', '', '', '', 101, 0, 1, NOW(), NOW()),
(16, 7, 3, 'Delete Menu', 'admin:menu:delete', '', '', '', 102, 0, 1, NOW(), NOW()),
(17, 1, 2, 'Project Config', 'admin:config:list', '/system/config', 'system/config/index', 'Tools', 100, 1, 1, NOW(), NOW()),
(18, 17, 3, 'Save Project Config', 'admin:config:update', '', '', '', 100, 0, 1, NOW(), NOW()),
(57, 1, 2, 'Config Management', 'admin:config-manage:list', '/system/config-manage', 'system/config-manage/index', 'Operation', 106, 1, 1, NOW(), NOW()),
(58, 57, 3, 'Create Config Structure', 'admin:config-manage:create', '', '', '', 100, 0, 1, NOW(), NOW()),
(59, 57, 3, 'Edit Config Structure', 'admin:config-manage:update', '', '', '', 101, 0, 1, NOW(), NOW()),
(60, 57, 3, 'Delete Config Structure', 'admin:config-manage:delete', '', '', '', 102, 0, 1, NOW(), NOW()),
(19, 1, 2, 'File Management', 'admin:file:list', '/system/files', 'system/file/index', 'FolderOpened', 103, 1, 1, NOW(), NOW()),
(20, 19, 3, 'Upload File', 'admin:file:upload', '', '', '', 100, 0, 1, NOW(), NOW()),
(21, 19, 3, 'Delete File', 'admin:file:delete', '', '', '', 101, 0, 1, NOW(), NOW()),
(22, 1, 2, 'Dictionaries', 'admin:dict:list', '/system/dicts', 'system/dict/index', 'Tickets', 101, 1, 1, NOW(), NOW()),
(23, 22, 3, 'Create Dictionary', 'admin:dict:create', '', '', '', 100, 0, 1, NOW(), NOW()),
(24, 22, 3, 'Edit Dictionary', 'admin:dict:update', '', '', '', 101, 0, 1, NOW(), NOW()),
(25, 22, 3, 'Toggle Dictionary Status', 'admin:dict:status', '', '', '', 102, 0, 1, NOW(), NOW()),
(26, 22, 3, 'Delete Dictionary', 'admin:dict:delete', '', '', '', 103, 0, 1, NOW(), NOW()),
(27, 0, 1, 'Logs', '', '/logs', '', 'Document', 200, 1, 1, NOW(), NOW()),
(28, 27, 2, 'Login Logs', 'admin:login-log:list', '/logs/login', 'system/login-log/index', 'Key', 100, 1, 1, NOW(), NOW()),
(29, 27, 2, 'Operation Logs', 'admin:operate-log:list', '/logs/operate', 'system/operate-log/index', 'DocumentChecked', 101, 1, 1, NOW(), NOW()),
(30, 2, 3, 'Force Admin Offline', 'admin:user:force-logout', '', '', '', 104, 0, 1, NOW(), NOW()),
(31, 19, 3, 'Edit File', 'admin:file:update', '', '', '', 102, 0, 1, NOW(), NOW()),
(32, 28, 3, 'Delete Login Log', 'admin:login-log:delete', '', '', '', 100, 0, 1, NOW(), NOW()),
(33, 28, 3, 'Clear Login Logs', 'admin:login-log:clear', '', '', '', 101, 0, 1, NOW(), NOW()),
(34, 29, 3, 'Delete Operation Log', 'admin:operate-log:delete', '', '', '', 100, 0, 1, NOW(), NOW()),
(35, 29, 3, 'Clear Operation Logs', 'admin:operate-log:clear', '', '', '', 101, 0, 1, NOW(), NOW()),
(36, 1, 2, 'System Tools', 'admin:tool:list', '/system/tools', 'system/tool/index', 'Operation', 104, 1, 1, NOW(), NOW()),
(37, 36, 3, 'Clear Cache', 'admin:tool:cache-clear', '', '', '', 100, 0, 1, NOW(), NOW()),
(38, 36, 3, 'View Database Backups', 'admin:tool:backup-list', '', '', '', 101, 0, 1, NOW(), NOW()),
(39, 36, 3, 'Create Database Backup', 'admin:tool:backup-create', '', '', '', 102, 0, 1, NOW(), NOW()),
(40, 36, 3, 'Restore Database Backup', 'admin:tool:backup-restore', '', '', '', 103, 0, 1, NOW(), NOW()),
(41, 36, 3, 'Delete Database Backup', 'admin:tool:backup-delete', '', '', '', 104, 0, 1, NOW(), NOW()),
(42, 36, 3, 'Download Database Backup', 'admin:tool:backup-download', '', '', '', 105, 0, 1, NOW(), NOW()),
(49, 1, 2, 'Code Generator', 'admin:code-generator:list', '/system/code-generator', 'system/code-generator/index', 'Files', 105, 1, 1, NOW(), NOW()),
(50, 49, 3, 'Generate Code', 'admin:code-generator:generate', '', '', '', 100, 0, 1, NOW(), NOW()),
(43, 1, 2, 'Notices', 'admin:notice:list', '/system/notices', 'system/notice/index', 'Bell', 102, 1, 1, NOW(), NOW()),
(44, 43, 3, 'Create Notice', 'admin:notice:create', '', '', '', 100, 0, 1, NOW(), NOW()),
(45, 43, 3, 'Edit Notice', 'admin:notice:update', '', '', '', 101, 0, 1, NOW(), NOW()),
(46, 43, 3, 'Toggle Notice Status', 'admin:notice:status', '', '', '', 102, 0, 1, NOW(), NOW()),
(47, 43, 3, 'Delete Notice', 'admin:notice:delete', '', '', '', 103, 0, 1, NOW(), NOW()),
(48, 0, 1, 'Permissions', '', '/permission', '', 'UserFilled', 100, 1, 1, NOW(), NOW()),
(51, 0, 2, 'Members', 'admin:member:list', '/member', 'member/index', 'User', 80, 1, 1, NOW(), NOW()),
(52, 51, 3, 'Create Member', 'admin:member:create', '', '', '', 100, 0, 1, NOW(), NOW()),
(53, 51, 3, 'Edit Member', 'admin:member:update', '', '', '', 101, 0, 1, NOW(), NOW()),
(54, 51, 3, 'Toggle Member Status', 'admin:member:status', '', '', '', 102, 0, 1, NOW(), NOW()),
(55, 51, 3, 'Reset Member Password', 'admin:member:reset-password', '', '', '', 103, 0, 1, NOW(), NOW()),
(56, 51, 3, 'Delete Member', 'admin:member:delete', '', '', '', 104, 0, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `parent_id` = VALUES(`parent_id`),
  `type` = VALUES(`type`),
  `title` = VALUES(`title`),
  `permission` = VALUES(`permission`),
  `path` = VALUES(`path`),
  `component` = VALUES(`component`),
  `icon` = VALUES(`icon`),
  `sort` = VALUES(`sort`),
  `visible` = VALUES(`visible`),
  `status` = VALUES(`status`),
  `update_time` = VALUES(`update_time`);

-- Ensure the default super admin role owns all built-in permissions.
INSERT INTO `vtp_admin_role_menu` (`role_id`, `menu_id`, `create_time`)
SELECT 1, m.`id`, NOW()
FROM `vtp_admin_menu` m
WHERE m.`id` BETWEEN 1 AND 60
  AND NOT EXISTS (
    SELECT 1 FROM `vtp_admin_role_menu` rm
    WHERE rm.`role_id` = 1 AND rm.`menu_id` = m.`id`
  );

-- Seed the default notice once.
INSERT INTO `vtp_admin_notice` (`title`, `content`, `type`, `scope_type`, `scope_ids`, `popup`, `status`, `create_time`, `update_time`)
SELECT 'System initialized', 'The general admin modules are enabled and ready for extension.', 'success', 'all', '', 0, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `vtp_admin_notice` WHERE `title` = 'System initialized'
);

ALTER TABLE `vtp_admin_menu` AUTO_INCREMENT = 61;
ALTER TABLE `vtp_system_config_group` AUTO_INCREMENT = 4;
ALTER TABLE `vtp_system_config_tab` AUTO_INCREMENT = 8;
ALTER TABLE `vtp_system_config` AUTO_INCREMENT = 16;
ALTER TABLE `vtp_dict_type` AUTO_INCREMENT = 3;

SET FOREIGN_KEY_CHECKS = 1;