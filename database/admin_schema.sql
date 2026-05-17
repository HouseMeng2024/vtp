-- 通用后台基础表
-- 兼容目标：MySQL 5.7
-- 默认账号：admin
-- 默认密码：admin123

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `vtp_admin_user`;
CREATE TABLE `vtp_admin_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `username` varchar(50) NOT NULL COMMENT '登录账号',
  `password` varchar(255) NOT NULL COMMENT '登录密码哈希',
  `nickname` varchar(50) NOT NULL DEFAULT '' COMMENT '昵称',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
  `email` varchar(100) NOT NULL DEFAULT '' COMMENT '邮箱',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态：1正常 0禁用',
  `last_login_ip` varchar(45) NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  KEY `idx_status` (`status`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台管理员';

DROP TABLE IF EXISTS `vtp_admin_role`;
CREATE TABLE `vtp_admin_role` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(50) NOT NULL COMMENT '角色名称',
  `code` varchar(50) NOT NULL COMMENT '角色标识',
  `sort` int unsigned NOT NULL DEFAULT '100' COMMENT '排序',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态：1正常 0禁用',
  `data_scope` varchar(20) NOT NULL DEFAULT 'all' COMMENT '数据范围：all全部 self本人',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台角色';

DROP TABLE IF EXISTS `vtp_admin_menu`;
CREATE TABLE `vtp_admin_menu` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `parent_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '父级ID',
  `type` tinyint unsigned NOT NULL DEFAULT '2' COMMENT '类型：1目录 2菜单 3按钮/API权限',
  `title` varchar(50) NOT NULL COMMENT '名称',
  `permission` varchar(100) NOT NULL DEFAULT '' COMMENT '权限标识',
  `path` varchar(255) NOT NULL DEFAULT '' COMMENT '前端路由路径',
  `component` varchar(255) NOT NULL DEFAULT '' COMMENT '前端组件路径',
  `icon` varchar(50) NOT NULL DEFAULT '' COMMENT '图标',
  `sort` int unsigned NOT NULL DEFAULT '100' COMMENT '排序',
  `visible` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '是否显示：1显示 0隐藏',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态：1正常 0禁用',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_permission` (`permission`),
  KEY `idx_type` (`type`),
  KEY `idx_sort` (`sort`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台菜单权限';

DROP TABLE IF EXISTS `vtp_admin_user_role`;
CREATE TABLE `vtp_admin_user_role` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` bigint unsigned NOT NULL COMMENT '管理员ID',
  `role_id` bigint unsigned NOT NULL COMMENT '角色ID',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_role` (`user_id`, `role_id`),
  KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员角色关联';

DROP TABLE IF EXISTS `vtp_admin_role_menu`;
CREATE TABLE `vtp_admin_role_menu` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `role_id` bigint unsigned NOT NULL COMMENT '角色ID',
  `menu_id` bigint unsigned NOT NULL COMMENT '菜单权限ID',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_menu` (`role_id`, `menu_id`),
  KEY `idx_menu_id` (`menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色菜单权限关联';

DROP TABLE IF EXISTS `vtp_admin_login_log`;
CREATE TABLE `vtp_admin_login_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT '登录账号',
  `ip` varchar(45) NOT NULL DEFAULT '' COMMENT 'IP',
  `user_agent` varchar(500) NOT NULL DEFAULT '' COMMENT 'User-Agent',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态：1成功 0失败',
  `message` varchar(255) NOT NULL DEFAULT '' COMMENT '说明',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_username` (`username`),
  KEY `idx_status` (`status`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台登录日志';

DROP TABLE IF EXISTS `vtp_admin_operate_log`;
CREATE TABLE `vtp_admin_operate_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT '管理员账号',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '操作标题',
  `method` varchar(10) NOT NULL DEFAULT '' COMMENT '请求方法',
  `path` varchar(255) NOT NULL DEFAULT '' COMMENT '请求路径',
  `params` text COMMENT '请求参数',
  `response` text COMMENT '响应摘要',
  `ip` varchar(45) NOT NULL DEFAULT '' COMMENT 'IP',
  `user_agent` varchar(500) NOT NULL DEFAULT '' COMMENT 'User-Agent',
  `status_code` int unsigned NOT NULL DEFAULT '200' COMMENT 'HTTP状态码',
  `duration_ms` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '耗时毫秒',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_method` (`method`),
  KEY `idx_status_code` (`status_code`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台操作日志';

DROP TABLE IF EXISTS `vtp_admin_notice`;
CREATE TABLE `vtp_admin_notice` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(100) NOT NULL COMMENT '消息标题',
  `content` varchar(500) NOT NULL DEFAULT '' COMMENT '消息内容',
  `type` varchar(30) NOT NULL DEFAULT 'info' COMMENT '消息类型',
  `scope_type` varchar(20) NOT NULL DEFAULT 'all' COMMENT '接收范围：all全部 role角色 user管理员',
  `scope_ids` varchar(500) NOT NULL DEFAULT '' COMMENT '接收对象ID，逗号分隔',
  `popup` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否右下角弹出：1是 0否',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态：1正常 0禁用',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_scope_type` (`scope_type`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台消息通知';

DROP TABLE IF EXISTS `vtp_admin_notice_read`;
CREATE TABLE `vtp_admin_notice_read` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` bigint unsigned NOT NULL COMMENT '管理员ID',
  `notice_id` bigint unsigned NOT NULL COMMENT '消息ID',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_notice` (`user_id`, `notice_id`),
  KEY `idx_notice_id` (`notice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台消息已读记录';

DROP TABLE IF EXISTS `vtp_system_config`;
DROP TABLE IF EXISTS `vtp_system_config_tab`;
DROP TABLE IF EXISTS `vtp_system_config_group`;
CREATE TABLE `vtp_system_config_group` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `key` varchar(50) NOT NULL COMMENT '分组标识',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '分组名称',
  `sort` int NOT NULL DEFAULT '100' COMMENT '排序',
  `is_system` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '系统内置：1是 0否',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态：1启用 0禁用',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_group_key` (`group_id`, `key`),
  KEY `idx_sort` (`sort`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置分组';

CREATE TABLE `vtp_system_config_tab` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `group_id` bigint unsigned NOT NULL COMMENT '配置分组ID',
  `key` varchar(50) NOT NULL COMMENT '标签标识',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '标签名称',
  `sort` int NOT NULL DEFAULT '100' COMMENT '排序',
  `is_system` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '系统内置：1是 0否',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态：1启用 0禁用',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_group_key` (`group_id`, `key`),
  KEY `idx_group_id` (`group_id`),
  KEY `idx_sort` (`sort`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置标签页';

CREATE TABLE `vtp_system_config` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `group_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '配置分组ID',
  `tab_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '配置标签ID',
  `group` varchar(50) NOT NULL DEFAULT 'default' COMMENT '兼容配置分组',
  `key` varchar(100) NOT NULL COMMENT '配置键',
  `value` text COMMENT '配置值',
  `type` varchar(20) NOT NULL DEFAULT 'text' COMMENT '类型',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '配置名称',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `options` text COMMENT '选项配置',
  `sort` int NOT NULL DEFAULT '100' COMMENT '排序',
  `is_system` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '系统内置：1是 0否',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态：1启用 0禁用',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_group_key` (`group_id`, `key`),
  KEY `idx_group_id` (`group_id`),
  KEY `idx_tab_id` (`tab_id`),
  KEY `idx_group_key` (`group`, `key`),
  KEY `idx_sort` (`sort`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置';

DROP TABLE IF EXISTS `vtp_upload_file`;
DROP TABLE IF EXISTS `vtp_member`;
CREATE TABLE `vtp_member` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
  `nickname` varchar(50) NOT NULL DEFAULT '' COMMENT '昵称',
  `avatar` varchar(500) NOT NULL DEFAULT '' COMMENT '头像',
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
  `email` varchar(100) NOT NULL DEFAULT '' COMMENT '邮箱',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '密码',
  `gender` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '性别：0未知 1男 2女',
  `birthday` date DEFAULT NULL COMMENT '生日',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态：1正常 0禁用',
  `register_ip` varchar(45) NOT NULL DEFAULT '' COMMENT '注册 IP',
  `register_time` datetime DEFAULT NULL COMMENT '注册时间',
  `last_login_ip` varchar(45) NOT NULL DEFAULT '' COMMENT '最后登录 IP',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_mobile` (`mobile`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员';

CREATE TABLE `vtp_upload_file` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `disk` varchar(30) NOT NULL DEFAULT 'local' COMMENT '存储磁盘',
  `path` varchar(500) NOT NULL COMMENT '文件路径',
  `url` varchar(500) NOT NULL DEFAULT '' COMMENT '访问地址',
  `original_name` varchar(255) NOT NULL DEFAULT '' COMMENT '原始文件名',
  `mime_type` varchar(100) NOT NULL DEFAULT '' COMMENT 'MIME类型',
  `extension` varchar(30) NOT NULL DEFAULT '' COMMENT '扩展名',
  `category` varchar(30) NOT NULL DEFAULT 'other' COMMENT '文件分类：image document sheet archive other',
  `scene` varchar(50) NOT NULL DEFAULT 'default' COMMENT '上传场景',
  `size` bigint unsigned NOT NULL DEFAULT '0' COMMENT '文件大小',
  `sha1` char(40) NOT NULL DEFAULT '' COMMENT 'SHA1',
  `uploader_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '上传人ID',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_sha1` (`sha1`),
  KEY `idx_category` (`category`),
  KEY `idx_scene` (`scene`),
  KEY `idx_uploader_id` (`uploader_id`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='上传文件';

DROP TABLE IF EXISTS `vtp_dict_type`;
CREATE TABLE `vtp_dict_type` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(50) NOT NULL COMMENT '字典名称',
  `type` varchar(100) NOT NULL COMMENT '字典标识',
  `sort` int unsigned NOT NULL DEFAULT '100' COMMENT '排序',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态：1正常 0禁用',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='字典类型';

DROP TABLE IF EXISTS `vtp_dict_data`;
CREATE TABLE `vtp_dict_data` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `type_id` bigint unsigned NOT NULL COMMENT '字典类型ID',
  `label` varchar(100) NOT NULL COMMENT '字典标签',
  `value` varchar(100) NOT NULL COMMENT '字典值',
  `tag_type` varchar(30) NOT NULL DEFAULT '' COMMENT '标签样式',
  `sort` int unsigned NOT NULL DEFAULT '100' COMMENT '排序',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态：1正常 0禁用',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_type_value` (`type_id`, `value`),
  KEY `idx_type_id` (`type_id`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='字典数据';

INSERT INTO `vtp_admin_user` (`id`, `username`, `password`, `nickname`, `status`, `create_time`, `update_time`) VALUES
(1, 'admin', '$2y$12$FUyMIXrNAocLiLyVw1bWO.uTFnCan1dcOKyfNzFCsdlZ63tpFfSaK', 'Super Admin', 1, NOW(), NOW());

INSERT INTO `vtp_admin_role` (`id`, `name`, `code`, `sort`, `status`, `data_scope`, `remark`, `create_time`, `update_time`) VALUES
(1, 'Super Administrator', 'super_admin', 1, 1, 'all', 'Full access', NOW(), NOW());

INSERT INTO `vtp_admin_user_role` (`user_id`, `role_id`, `create_time`) VALUES
(1, 1, NOW());

INSERT INTO `vtp_system_config_group` (`id`, `key`, `title`, `sort`, `is_system`, `status`, `create_time`, `update_time`) VALUES
(1, 'system', 'System', 100, 1, 1, NOW(), NOW()),
(2, 'admin', 'Admin', 200, 1, 1, NOW(), NOW()),
(3, 'index', 'Index', 300, 1, 1, NOW(), NOW());

INSERT INTO `vtp_system_config_tab` (`id`, `group_id`, `key`, `title`, `sort`, `is_system`, `status`, `create_time`, `update_time`) VALUES
(1, 1, 'system_basic', 'Basic Rules', 100, 1, 1, NOW(), NOW()),
(2, 1, 'system_upload', 'Upload Rules', 200, 1, 1, NOW(), NOW()),
(3, 1, 'system_security', 'Security Rules', 300, 1, 1, NOW(), NOW()),
(4, 2, 'admin_basic', 'Admin Basic', 100, 1, 1, NOW(), NOW()),
(5, 2, 'admin_login', 'Login Security', 200, 1, 1, NOW(), NOW()),
(6, 3, 'index_site', 'Site Info', 100, 1, 1, NOW(), NOW()),
(7, 3, 'index_seo', 'SEO Settings', 200, 1, 1, NOW(), NOW());

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
(15, 3, 7, 'index_seo', 'seo_description', '', 'textarea', 'SEO Description', 'Default SEO description for the index site.', '', 102, 1, 1, NOW(), NOW());

INSERT INTO `vtp_dict_type` (`id`, `name`, `type`, `sort`, `status`, `remark`, `create_time`, `update_time`) VALUES
(1, 'Common Status', 'common_status', 1, 1, 'Common enabled and disabled status.', NOW(), NOW()),
(2, 'Switch Status', 'switch_status', 2, 1, 'Common switch status.', NOW(), NOW());

INSERT INTO `vtp_dict_data` (`type_id`, `label`, `value`, `tag_type`, `sort`, `status`, `remark`, `create_time`, `update_time`) VALUES
(1, 'Enabled', '1', 'success', 1, 1, '', NOW(), NOW()),
(1, 'Disabled', '0', 'info', 2, 1, '', NOW(), NOW()),
(2, 'On', '1', 'success', 1, 1, '', NOW(), NOW()),
(2, 'Off', '0', 'info', 2, 1, '', NOW(), NOW());

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
(56, 51, 3, 'Delete Member', 'admin:member:delete', '', '', '', 104, 0, 1, NOW(), NOW());

INSERT INTO `vtp_admin_notice` (`title`, `content`, `type`, `scope_type`, `scope_ids`, `popup`, `status`, `create_time`, `update_time`) VALUES
('System initialized', 'The general admin modules are enabled and ready for extension.', 'success', 'all', '', 0, 1, NOW(), NOW());

INSERT INTO `vtp_admin_role_menu` (`role_id`, `menu_id`, `create_time`)
SELECT 1, `id`, NOW() FROM `vtp_admin_menu`;

SET FOREIGN_KEY_CHECKS = 1;
