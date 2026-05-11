-- 通用后台基础表
-- 兼容目标：MySQL 5.7
-- 默认账号：admin
-- 默认密码：admin123

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `admin_user`;
CREATE TABLE `admin_user` (
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

DROP TABLE IF EXISTS `admin_role`;
CREATE TABLE `admin_role` (
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

DROP TABLE IF EXISTS `admin_menu`;
CREATE TABLE `admin_menu` (
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

DROP TABLE IF EXISTS `admin_user_role`;
CREATE TABLE `admin_user_role` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` bigint unsigned NOT NULL COMMENT '管理员ID',
  `role_id` bigint unsigned NOT NULL COMMENT '角色ID',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_role` (`user_id`, `role_id`),
  KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员角色关联';

DROP TABLE IF EXISTS `admin_role_menu`;
CREATE TABLE `admin_role_menu` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `role_id` bigint unsigned NOT NULL COMMENT '角色ID',
  `menu_id` bigint unsigned NOT NULL COMMENT '菜单权限ID',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_menu` (`role_id`, `menu_id`),
  KEY `idx_menu_id` (`menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色菜单权限关联';

DROP TABLE IF EXISTS `admin_login_log`;
CREATE TABLE `admin_login_log` (
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

DROP TABLE IF EXISTS `admin_operate_log`;
CREATE TABLE `admin_operate_log` (
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

DROP TABLE IF EXISTS `admin_notice`;
CREATE TABLE `admin_notice` (
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

DROP TABLE IF EXISTS `admin_notice_read`;
CREATE TABLE `admin_notice_read` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` bigint unsigned NOT NULL COMMENT '管理员ID',
  `notice_id` bigint unsigned NOT NULL COMMENT '消息ID',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_notice` (`user_id`, `notice_id`),
  KEY `idx_notice_id` (`notice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台消息已读记录';

DROP TABLE IF EXISTS `system_config`;
CREATE TABLE `system_config` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `group` varchar(50) NOT NULL DEFAULT 'default' COMMENT '配置分组',
  `key` varchar(100) NOT NULL COMMENT '配置键',
  `value` text COMMENT '配置值',
  `type` varchar(20) NOT NULL DEFAULT 'text' COMMENT '类型',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '配置名称',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_group_key` (`group`, `key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置';

DROP TABLE IF EXISTS `upload_file`;
DROP TABLE IF EXISTS `member`;
CREATE TABLE `member` (
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

CREATE TABLE `upload_file` (
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

DROP TABLE IF EXISTS `dict_type`;
CREATE TABLE `dict_type` (
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

DROP TABLE IF EXISTS `dict_data`;
CREATE TABLE `dict_data` (
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

INSERT INTO `admin_user` (`id`, `username`, `password`, `nickname`, `status`, `create_time`, `update_time`) VALUES
(1, 'admin', '$2y$12$b8EX2nGi7gpIKwRra/RJjOGi4nUBCQv1NVgEa4.SAsoXRtcBRddlm', '超级管理员', 1, NOW(), NOW());

INSERT INTO `admin_role` (`id`, `name`, `code`, `sort`, `status`, `data_scope`, `remark`, `create_time`, `update_time`) VALUES
(1, '超级管理员', 'super_admin', 1, 1, 'all', '拥有全部权限', NOW(), NOW());

INSERT INTO `admin_user_role` (`user_id`, `role_id`, `create_time`) VALUES
(1, 1, NOW());

INSERT INTO `system_config` (`group`, `key`, `value`, `type`, `name`, `remark`, `create_time`, `update_time`) VALUES
('basic', 'site_name', 'VTP Admin', 'text', '站点名称', '后台和项目默认显示名称', NOW(), NOW()),
('basic', 'admin_title', 'VTP Admin', 'text', '后台标题', '后台浏览器标题和顶部品牌名称', NOW(), NOW()),
('basic', 'site_logo', '', 'image', '站点 Logo', '填写图片 URL，用于后台品牌展示', NOW(), NOW()),
('basic', 'site_description', '通用后台管理系统', 'textarea', '站点描述', '用于项目说明、SEO 或接口展示', NOW(), NOW()),
('basic', 'site_keywords', '', 'text', '站点关键词', '多个关键词用英文逗号分隔', NOW(), NOW()),
('basic', 'site_icp', '', 'text', 'ICP备案号', '需要展示备案信息时填写', NOW(), NOW()),
('upload', 'upload_disk', 'local', 'text', '上传磁盘', '默认 local，后续可扩展 oss、cos 等', NOW(), NOW()),
('upload', 'upload_max_size', '10', 'number', '上传大小限制', '单位 MB', NOW(), NOW()),
('upload', 'upload_ext', 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx', 'text', '允许扩展名', '多个扩展名用英文逗号分隔', NOW(), NOW()),
('security', 'password_min_length', '6', 'number', '密码最小长度', '管理员密码最小长度', NOW(), NOW()),
('security', 'admin_token_expire', '86400', 'number', '后台登录有效期', '单位秒', NOW(), NOW()),
('security', 'login_captcha_enabled', '0', 'switch', '登录验证码', '开启后后台登录需要输入验证码', NOW(), NOW()),
('security', 'login_max_attempts', '5', 'number', '登录失败次数', '达到次数后临时锁定', NOW(), NOW()),
('security', 'login_lock_seconds', '900', 'number', '登录锁定时长', '单位秒', NOW(), NOW());

INSERT INTO `dict_type` (`id`, `name`, `type`, `sort`, `status`, `remark`, `create_time`, `update_time`) VALUES
(1, '通用状态', 'common_status', 1, 1, '通用启用禁用状态', NOW(), NOW()),
(2, '开关状态', 'switch_status', 2, 1, '通用开关状态', NOW(), NOW());

INSERT INTO `dict_data` (`type_id`, `label`, `value`, `tag_type`, `sort`, `status`, `remark`, `create_time`, `update_time`) VALUES
(1, '正常', '1', 'success', 1, 1, '', NOW(), NOW()),
(1, '禁用', '0', 'info', 2, 1, '', NOW(), NOW()),
(2, '开启', '1', 'success', 1, 1, '', NOW(), NOW()),
(2, '关闭', '0', 'info', 2, 1, '', NOW(), NOW());

INSERT INTO `admin_menu` (`id`, `parent_id`, `type`, `title`, `permission`, `path`, `component`, `icon`, `sort`, `visible`, `status`, `create_time`, `update_time`) VALUES
(1, 0, 1, '系统管理', '', '/system', '', 'Setting', 400, 1, 1, NOW(), NOW()),
(2, 48, 2, '管理员管理', 'admin:user:list', '/permission/users', 'system/user/index', 'User', 100, 1, 1, NOW(), NOW()),
(3, 2, 3, '新增管理员', 'admin:user:create', '', '', '', 100, 0, 1, NOW(), NOW()),
(4, 2, 3, '编辑管理员', 'admin:user:update', '', '', '', 101, 0, 1, NOW(), NOW()),
(5, 2, 3, '删除管理员', 'admin:user:delete', '', '', '', 102, 0, 1, NOW(), NOW()),
(6, 48, 2, '角色管理', 'admin:role:list', '/permission/roles', 'system/role/index', 'UserFilled', 101, 1, 1, NOW(), NOW()),
(7, 48, 2, '菜单管理', 'admin:menu:list', '/permission/menus', 'system/menu/index', 'Menu', 102, 1, 1, NOW(), NOW()),
(8, 2, 3, '禁用启用管理员', 'admin:user:status', '', '', '', 103, 0, 1, NOW(), NOW()),
(9, 6, 3, '新增角色', 'admin:role:create', '', '', '', 100, 0, 1, NOW(), NOW()),
(10, 6, 3, '编辑角色', 'admin:role:update', '', '', '', 101, 0, 1, NOW(), NOW()),
(11, 6, 3, '禁用启用角色', 'admin:role:status', '', '', '', 102, 0, 1, NOW(), NOW()),
(12, 6, 3, '删除角色', 'admin:role:delete', '', '', '', 103, 0, 1, NOW(), NOW()),
(13, 6, 3, '分配角色权限', 'admin:role:permission', '', '', '', 104, 0, 1, NOW(), NOW()),
(14, 7, 3, '新增菜单', 'admin:menu:create', '', '', '', 100, 0, 1, NOW(), NOW()),
(15, 7, 3, '编辑菜单', 'admin:menu:update', '', '', '', 101, 0, 1, NOW(), NOW()),
(16, 7, 3, '删除菜单', 'admin:menu:delete', '', '', '', 102, 0, 1, NOW(), NOW()),
(17, 1, 2, '项目配置', 'admin:config:list', '/system/config', 'system/config/index', 'Tools', 100, 1, 1, NOW(), NOW()),
(18, 17, 3, '保存项目配置', 'admin:config:update', '', '', '', 100, 0, 1, NOW(), NOW()),
(19, 1, 2, '文件管理', 'admin:file:list', '/system/files', 'system/file/index', 'FolderOpened', 103, 1, 1, NOW(), NOW()),
(20, 19, 3, '上传文件', 'admin:file:upload', '', '', '', 100, 0, 1, NOW(), NOW()),
(21, 19, 3, '删除文件', 'admin:file:delete', '', '', '', 101, 0, 1, NOW(), NOW()),
(22, 1, 2, '字典管理', 'admin:dict:list', '/system/dicts', 'system/dict/index', 'Tickets', 101, 1, 1, NOW(), NOW()),
(23, 22, 3, '新增字典', 'admin:dict:create', '', '', '', 100, 0, 1, NOW(), NOW()),
(24, 22, 3, '编辑字典', 'admin:dict:update', '', '', '', 101, 0, 1, NOW(), NOW()),
(25, 22, 3, '禁用启用字典', 'admin:dict:status', '', '', '', 102, 0, 1, NOW(), NOW()),
(26, 22, 3, '删除字典', 'admin:dict:delete', '', '', '', 103, 0, 1, NOW(), NOW()),
(27, 0, 1, '日志管理', '', '/logs', '', 'Document', 200, 1, 1, NOW(), NOW()),
(28, 27, 2, '登录日志', 'admin:login-log:list', '/logs/login', 'system/login-log/index', 'Key', 100, 1, 1, NOW(), NOW()),
(29, 27, 2, '操作日志', 'admin:operate-log:list', '/logs/operate', 'system/operate-log/index', 'DocumentChecked', 101, 1, 1, NOW(), NOW()),
(30, 2, 3, '强制管理员下线', 'admin:user:force-logout', '', '', '', 104, 0, 1, NOW(), NOW()),
(31, 19, 3, '编辑文件', 'admin:file:update', '', '', '', 102, 0, 1, NOW(), NOW()),
(32, 28, 3, '删除登录日志', 'admin:login-log:delete', '', '', '', 100, 0, 1, NOW(), NOW()),
(33, 28, 3, '清空登录日志', 'admin:login-log:clear', '', '', '', 101, 0, 1, NOW(), NOW()),
(34, 29, 3, '删除操作日志', 'admin:operate-log:delete', '', '', '', 100, 0, 1, NOW(), NOW()),
(35, 29, 3, '清空操作日志', 'admin:operate-log:clear', '', '', '', 101, 0, 1, NOW(), NOW()),
(36, 1, 2, '系统工具', 'admin:tool:list', '/system/tools', 'system/tool/index', 'Operation', 104, 1, 1, NOW(), NOW()),
(37, 36, 3, '清理缓存', 'admin:tool:cache-clear', '', '', '', 100, 0, 1, NOW(), NOW()),
(38, 36, 3, '查看数据库备份', 'admin:tool:backup-list', '', '', '', 101, 0, 1, NOW(), NOW()),
(39, 36, 3, '创建数据库备份', 'admin:tool:backup-create', '', '', '', 102, 0, 1, NOW(), NOW()),
(40, 36, 3, '恢复数据库备份', 'admin:tool:backup-restore', '', '', '', 103, 0, 1, NOW(), NOW()),
(41, 36, 3, '删除数据库备份', 'admin:tool:backup-delete', '', '', '', 104, 0, 1, NOW(), NOW()),
(42, 36, 3, '下载数据库备份', 'admin:tool:backup-download', '', '', '', 105, 0, 1, NOW(), NOW()),
(49, 1, 2, '代码生成', 'admin:code-generator:list', '/system/code-generator', 'system/code-generator/index', 'Files', 105, 1, 1, NOW(), NOW()),
(50, 49, 3, '生成代码', 'admin:code-generator:generate', '', '', '', 100, 0, 1, NOW(), NOW()),
(43, 1, 2, '消息通知', 'admin:notice:list', '/system/notices', 'system/notice/index', 'Bell', 102, 1, 1, NOW(), NOW()),
(44, 43, 3, '新增消息', 'admin:notice:create', '', '', '', 100, 0, 1, NOW(), NOW()),
(45, 43, 3, '编辑消息', 'admin:notice:update', '', '', '', 101, 0, 1, NOW(), NOW()),
(46, 43, 3, '启用禁用消息', 'admin:notice:status', '', '', '', 102, 0, 1, NOW(), NOW()),
(47, 43, 3, '删除消息', 'admin:notice:delete', '', '', '', 103, 0, 1, NOW(), NOW()),
(48, 0, 1, '权限管理', '', '/permission', '', 'UserFilled', 100, 1, 1, NOW(), NOW()),
(51, 0, 2, '会员管理', 'admin:member:list', '/member', 'member/index', 'User', 80, 1, 1, NOW(), NOW()),
(52, 51, 3, '新增会员', 'admin:member:create', '', '', '', 100, 0, 1, NOW(), NOW()),
(53, 51, 3, '编辑会员', 'admin:member:update', '', '', '', 101, 0, 1, NOW(), NOW()),
(54, 51, 3, '启用禁用会员', 'admin:member:status', '', '', '', 102, 0, 1, NOW(), NOW()),
(55, 51, 3, '重置会员密码', 'admin:member:reset-password', '', '', '', 103, 0, 1, NOW(), NOW()),
(56, 51, 3, '删除会员', 'admin:member:delete', '', '', '', 104, 0, 1, NOW(), NOW());

INSERT INTO `admin_notice` (`title`, `content`, `type`, `scope_type`, `scope_ids`, `popup`, `status`, `create_time`, `update_time`) VALUES
('系统初始化完成', '通用后台基础模块已经启用，可以继续扩展业务功能。', 'success', 'all', '', 0, 1, NOW(), NOW());

INSERT INTO `admin_role_menu` (`role_id`, `menu_id`, `create_time`)
SELECT 1, `id`, NOW() FROM `admin_menu`;

SET FOREIGN_KEY_CHECKS = 1;
