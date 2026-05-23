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
  UNIQUE KEY `uk_key` (`key`),
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

DROP TABLE IF EXISTS `vtp_content_category`;
CREATE TABLE `vtp_content_category` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `parent_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '父级ID',
  `type` varchar(50) NOT NULL DEFAULT 'article' COMMENT '分类类型',
  `name` varchar(100) NOT NULL COMMENT '分类名称',
  `slug` varchar(100) NOT NULL DEFAULT '' COMMENT '分类标识',
  `cover` varchar(500) NOT NULL DEFAULT '' COMMENT '封面图',
  `description` varchar(500) NOT NULL DEFAULT '' COMMENT '分类描述',
  `sort` int unsigned NOT NULL DEFAULT '100' COMMENT '排序',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态：1正常 0禁用',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容分类';

DROP TABLE IF EXISTS `vtp_article`;
CREATE TABLE `vtp_article` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `category_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  `title` varchar(200) NOT NULL COMMENT '标题',
  `subtitle` varchar(200) NOT NULL DEFAULT '' COMMENT '副标题',
  `cover` varchar(500) NOT NULL DEFAULT '' COMMENT '封面图',
  `summary` varchar(500) NOT NULL DEFAULT '' COMMENT '摘要',
  `content` longtext COMMENT '正文',
  `author` varchar(100) NOT NULL DEFAULT '' COMMENT '作者',
  `source` varchar(100) NOT NULL DEFAULT '' COMMENT '来源',
  `source_url` varchar(500) NOT NULL DEFAULT '' COMMENT '来源链接',
  `keywords` varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  `description` varchar(500) NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `views` int unsigned NOT NULL DEFAULT '0' COMMENT '浏览量',
  `sort` int unsigned NOT NULL DEFAULT '100' COMMENT '排序',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态：0下架 1发布',
  `publish_time` datetime DEFAULT NULL COMMENT '发布时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_status` (`status`),
  KEY `idx_publish_time` (`publish_time`),
  KEY `idx_sort` (`sort`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章';

DROP TABLE IF EXISTS `vtp_navigation`;
CREATE TABLE `vtp_navigation` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `parent_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '父级ID',
  `group` varchar(50) NOT NULL DEFAULT 'main' COMMENT '导航分组',
  `title` varchar(100) NOT NULL COMMENT '导航名称',
  `url` varchar(500) NOT NULL DEFAULT '' COMMENT '链接地址',
  `target` varchar(20) NOT NULL DEFAULT '_self' COMMENT '打开方式',
  `icon` varchar(100) NOT NULL DEFAULT '' COMMENT '图标',
  `sort` int unsigned NOT NULL DEFAULT '100' COMMENT '排序',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态：1正常 0禁用',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_group` (`group`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='导航';

DROP TABLE IF EXISTS `vtp_banner`;
CREATE TABLE `vtp_banner` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `position` varchar(50) NOT NULL DEFAULT 'home' COMMENT '展示位置',
  `title` varchar(150) NOT NULL COMMENT '标题',
  `subtitle` varchar(200) NOT NULL DEFAULT '' COMMENT '副标题',
  `image` varchar(500) NOT NULL COMMENT '图片',
  `link_url` varchar(500) NOT NULL DEFAULT '' COMMENT '链接地址',
  `target` varchar(20) NOT NULL DEFAULT '_self' COMMENT '打开方式',
  `start_time` datetime DEFAULT NULL COMMENT '开始时间',
  `end_time` datetime DEFAULT NULL COMMENT '结束时间',
  `sort` int unsigned NOT NULL DEFAULT '100' COMMENT '排序',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态：1正常 0禁用',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_position` (`position`),
  KEY `idx_status` (`status`),
  KEY `idx_time` (`start_time`, `end_time`),
  KEY `idx_sort` (`sort`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='幻灯';

INSERT INTO `vtp_admin_user` (`id`, `username`, `password`, `nickname`, `status`, `create_time`, `update_time`) VALUES
(1, 'admin', '$2y$12$FUyMIXrNAocLiLyVw1bWO.uTFnCan1dcOKyfNzFCsdlZ63tpFfSaK', '超级管理员', 1, NOW(), NOW());

INSERT INTO `vtp_admin_role` (`id`, `name`, `code`, `sort`, `status`, `data_scope`, `remark`, `create_time`, `update_time`) VALUES
(1, '超级管理员', 'super_admin', 1, 1, 'all', '拥有全部权限', NOW(), NOW());

INSERT INTO `vtp_admin_user_role` (`user_id`, `role_id`, `create_time`) VALUES
(1, 1, NOW());

INSERT INTO `vtp_system_config_group` (`id`, `key`, `title`, `sort`, `is_system`, `status`, `create_time`, `update_time`) VALUES
(1, 'system', '系统配置', 100, 1, 1, NOW(), NOW()),
(2, 'admin', '后台配置', 200, 1, 1, NOW(), NOW()),
(3, 'index', '前台配置', 300, 1, 1, NOW(), NOW());

INSERT INTO `vtp_system_config_tab` (`id`, `group_id`, `key`, `title`, `sort`, `is_system`, `status`, `create_time`, `update_time`) VALUES
(1, 1, 'system_basic', '基础规范', 100, 1, 1, NOW(), NOW()),
(2, 1, 'system_upload', '上传规范', 200, 1, 1, NOW(), NOW()),
(3, 1, 'system_security', '安全规范', 300, 1, 1, NOW(), NOW()),
(4, 2, 'admin_basic', '后台基础', 100, 1, 1, NOW(), NOW()),
(5, 2, 'admin_login', '登录安全', 200, 1, 1, NOW(), NOW()),
(6, 3, 'index_site', '网站信息', 100, 1, 1, NOW(), NOW()),
(7, 3, 'index_seo', 'SEO 配置', 200, 1, 1, NOW(), NOW());

INSERT INTO `vtp_system_config` (`id`, `group_id`, `tab_id`, `group`, `key`, `value`, `type`, `name`, `remark`, `options`, `sort`, `is_system`, `status`, `create_time`, `update_time`) VALUES
(1, 1, 2, 'system_upload', 'upload_max_size', '10', 'number', '上传大小限制', '单位 MB，所有模块上传默认遵守', '', 100, 1, 1, NOW(), NOW()),
(2, 1, 2, 'system_upload', 'upload_ext', 'jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip', 'text', '允许扩展名', '多个扩展名用英文逗号分隔', '', 101, 1, 1, NOW(), NOW()),
(3, 1, 3, 'system_security', 'password_min_length', '6', 'number', '密码最小长度', '系统账号类密码最小长度', '', 100, 1, 1, NOW(), NOW()),
(4, 1, 3, 'system_security', 'login_max_attempts', '5', 'number', '登录失败次数', '达到次数后临时锁定', '', 101, 1, 1, NOW(), NOW()),
(5, 1, 3, 'system_security', 'login_lock_seconds', '900', 'number', '登录锁定时长', '单位秒', '', 102, 1, 1, NOW(), NOW()),
(6, 2, 4, 'admin_basic', 'title', 'VTP Admin', 'text', '后台标题', '后台浏览器标题和顶部品牌名称', '', 100, 1, 1, NOW(), NOW()),
(7, 2, 4, 'admin_basic', 'logo', '', 'image', '后台 Logo', '后台登录页和顶部品牌 Logo', '', 101, 1, 1, NOW(), NOW()),
(8, 2, 4, 'admin_basic', 'description', '通用后台管理系统', 'textarea', '后台描述', '后台登录页展示说明', '', 102, 1, 1, NOW(), NOW()),
(9, 2, 5, 'admin_login', 'token_expire', '86400', 'number', '后台登录有效期', '单位秒，仅作用于 admin 模块', '', 100, 1, 1, NOW(), NOW()),
(10, 2, 5, 'admin_login', 'captcha_enabled', '0', 'switch', '后台登录验证码', '开启后后台登录需要输入验证码', '', 101, 1, 1, NOW(), NOW()),
(11, 3, 6, 'index_site', 'title', 'VTP', 'text', '网站标题', '前台 index 模块默认网站标题', '', 100, 1, 1, NOW(), NOW()),
(12, 3, 6, 'index_site', 'logo', '', 'image', '网站 Logo', '前台站点 Logo', '', 101, 1, 1, NOW(), NOW()),
(13, 3, 7, 'index_seo', 'seo_title', 'VTP', 'text', 'SEO 标题', '前台默认 SEO 标题', '', 100, 1, 1, NOW(), NOW()),
(14, 3, 7, 'index_seo', 'seo_keywords', '', 'text', 'SEO 关键词', '多个关键词用英文逗号分隔', '', 101, 1, 1, NOW(), NOW()),
(15, 3, 7, 'index_seo', 'seo_description', '', 'textarea', 'SEO 描述', '前台页面默认 SEO 描述', '', 102, 1, 1, NOW(), NOW());

INSERT INTO `vtp_dict_type` (`id`, `name`, `type`, `sort`, `status`, `remark`, `create_time`, `update_time`) VALUES
(1, '通用状态', 'common_status', 1, 1, '通用启用禁用状态', NOW(), NOW()),
(2, '开关状态', 'switch_status', 2, 1, '通用开关状态', NOW(), NOW()),
(3, '内容模型', 'content_model', 3, 1, '内容分类和内容模块使用的模型标识', NOW(), NOW());

INSERT INTO `vtp_dict_data` (`type_id`, `label`, `value`, `tag_type`, `sort`, `status`, `remark`, `create_time`, `update_time`) VALUES
(1, '正常', '1', 'success', 1, 1, '', NOW(), NOW()),
(1, '禁用', '0', 'info', 2, 1, '', NOW(), NOW()),
(2, '开启', '1', 'success', 1, 1, '', NOW(), NOW()),
(2, '关闭', '0', 'info', 2, 1, '', NOW(), NOW()),
(3, '文章', 'article', 'primary', 1, 1, '默认文章内容模型', NOW(), NOW());

INSERT INTO `vtp_admin_menu` (`id`, `parent_id`, `type`, `title`, `permission`, `path`, `component`, `icon`, `sort`, `visible`, `status`, `create_time`, `update_time`) VALUES
(1, 0, 1, '系统设置', '', '/system', '', 'Setting', 800, 1, 1, NOW(), NOW()),
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
(57, 1, 2, '配置管理', 'admin:config-manage:list', '/system/config-manage', 'system/config-manage/index', 'Operation', 930, 1, 1, NOW(), NOW()),
(58, 57, 3, '新增配置结构', 'admin:config-manage:create', '', '', '', 100, 0, 1, NOW(), NOW()),
(59, 57, 3, '编辑配置结构', 'admin:config-manage:update', '', '', '', 101, 0, 1, NOW(), NOW()),
(60, 57, 3, '删除配置结构', 'admin:config-manage:delete', '', '', '', 102, 0, 1, NOW(), NOW()),
(19, 1, 2, '文件管理', 'admin:file:list', '/system/files', 'system/file/index', 'FolderOpened', 110, 1, 1, NOW(), NOW()),
(20, 19, 3, '上传文件', 'admin:file:upload', '', '', '', 100, 0, 1, NOW(), NOW()),
(21, 19, 3, '删除文件', 'admin:file:delete', '', '', '', 101, 0, 1, NOW(), NOW()),
(22, 1, 2, '字典管理', 'admin:dict:list', '/system/dicts', 'system/dict/index', 'Tickets', 120, 1, 1, NOW(), NOW()),
(23, 22, 3, '新增字典', 'admin:dict:create', '', '', '', 100, 0, 1, NOW(), NOW()),
(24, 22, 3, '编辑字典', 'admin:dict:update', '', '', '', 101, 0, 1, NOW(), NOW()),
(25, 22, 3, '禁用启用字典', 'admin:dict:status', '', '', '', 102, 0, 1, NOW(), NOW()),
(26, 22, 3, '删除字典', 'admin:dict:delete', '', '', '', 103, 0, 1, NOW(), NOW()),
(27, 0, 1, '日志管理', '', '/logs', '', 'Document', 900, 1, 1, NOW(), NOW()),
(28, 27, 2, '登录日志', 'admin:login-log:list', '/logs/login', 'system/login-log/index', 'Key', 100, 1, 1, NOW(), NOW()),
(29, 27, 2, '操作日志', 'admin:operate-log:list', '/logs/operate', 'system/operate-log/index', 'DocumentChecked', 101, 1, 1, NOW(), NOW()),
(30, 2, 3, '强制管理员下线', 'admin:user:force-logout', '', '', '', 104, 0, 1, NOW(), NOW()),
(31, 19, 3, '编辑文件', 'admin:file:update', '', '', '', 102, 0, 1, NOW(), NOW()),
(32, 28, 3, '删除登录日志', 'admin:login-log:delete', '', '', '', 100, 0, 1, NOW(), NOW()),
(33, 28, 3, '清空登录日志', 'admin:login-log:clear', '', '', '', 101, 0, 1, NOW(), NOW()),
(34, 29, 3, '删除操作日志', 'admin:operate-log:delete', '', '', '', 100, 0, 1, NOW(), NOW()),
(35, 29, 3, '清空操作日志', 'admin:operate-log:clear', '', '', '', 101, 0, 1, NOW(), NOW()),
(36, 1, 2, '系统工具', 'admin:tool:list', '/system/tools', 'system/tool/index', 'Operation', 910, 1, 1, NOW(), NOW()),
(38, 36, 3, '查看数据库备份', 'admin:tool:backup-list', '', '', '', 101, 0, 1, NOW(), NOW()),
(39, 36, 3, '创建数据库备份', 'admin:tool:backup-create', '', '', '', 102, 0, 1, NOW(), NOW()),
(40, 36, 3, '恢复数据库备份', 'admin:tool:backup-restore', '', '', '', 103, 0, 1, NOW(), NOW()),
(41, 36, 3, '删除数据库备份', 'admin:tool:backup-delete', '', '', '', 104, 0, 1, NOW(), NOW()),
(42, 36, 3, '下载数据库备份', 'admin:tool:backup-download', '', '', '', 105, 0, 1, NOW(), NOW()),
(82, 1, 2, '缓存管理', 'admin:cache:list', '/system/cache', 'system/cache/index', 'Operation', 900, 1, 1, NOW(), NOW()),
(83, 82, 3, '清理缓存', 'admin:cache:clear', '', '', '', 100, 0, 1, NOW(), NOW()),
(49, 1, 2, '代码生成', 'admin:code-generator:list', '/system/code-generator', 'system/code-generator/index', 'Files', 920, 1, 1, NOW(), NOW()),
(50, 49, 3, '生成代码', 'admin:code-generator:generate', '', '', '', 100, 0, 1, NOW(), NOW()),
(43, 1, 2, '消息通知', 'admin:notice:list', '/system/notices', 'system/notice/index', 'Bell', 130, 1, 1, NOW(), NOW()),
(44, 43, 3, '新增消息', 'admin:notice:create', '', '', '', 100, 0, 1, NOW(), NOW()),
(45, 43, 3, '编辑消息', 'admin:notice:update', '', '', '', 101, 0, 1, NOW(), NOW()),
(46, 43, 3, '启用禁用消息', 'admin:notice:status', '', '', '', 102, 0, 1, NOW(), NOW()),
(47, 43, 3, '删除消息', 'admin:notice:delete', '', '', '', 103, 0, 1, NOW(), NOW()),
(48, 0, 1, '权限管理', '', '/permission', '', 'UserFilled', 300, 1, 1, NOW(), NOW()),
(51, 0, 2, '会员管理', 'admin:member:list', '/member', 'member/index', 'User', 200, 1, 1, NOW(), NOW()),
(52, 51, 3, '新增会员', 'admin:member:create', '', '', '', 100, 0, 1, NOW(), NOW()),
(53, 51, 3, '编辑会员', 'admin:member:update', '', '', '', 101, 0, 1, NOW(), NOW()),
(54, 51, 3, '启用禁用会员', 'admin:member:status', '', '', '', 102, 0, 1, NOW(), NOW()),
(55, 51, 3, '重置会员密码', 'admin:member:reset-password', '', '', '', 103, 0, 1, NOW(), NOW()),
(56, 51, 3, '删除会员', 'admin:member:delete', '', '', '', 104, 0, 1, NOW(), NOW()),
(61, 0, 1, '内容管理', '', '/content', '', 'Grid', 100, 1, 1, NOW(), NOW()),
(62, 61, 2, '内容分类', 'admin:content-category:list', '/content/categories', 'content/category/index', 'Menu', 100, 1, 1, NOW(), NOW()),
(63, 62, 3, '新增内容分类', 'admin:content-category:create', '', '', '', 100, 0, 1, NOW(), NOW()),
(64, 62, 3, '编辑内容分类', 'admin:content-category:update', '', '', '', 101, 0, 1, NOW(), NOW()),
(65, 62, 3, '启用禁用内容分类', 'admin:content-category:status', '', '', '', 102, 0, 1, NOW(), NOW()),
(66, 62, 3, '删除内容分类', 'admin:content-category:delete', '', '', '', 103, 0, 1, NOW(), NOW()),
(67, 61, 2, '文章管理', 'admin:article:list', '/content/articles', 'content/article/index', 'Document', 110, 1, 1, NOW(), NOW()),
(68, 67, 3, '新增文章', 'admin:article:create', '', '', '', 100, 0, 1, NOW(), NOW()),
(69, 67, 3, '编辑文章', 'admin:article:update', '', '', '', 101, 0, 1, NOW(), NOW()),
(70, 67, 3, '发布下架文章', 'admin:article:status', '', '', '', 102, 0, 1, NOW(), NOW()),
(71, 67, 3, '删除文章', 'admin:article:delete', '', '', '', 103, 0, 1, NOW(), NOW()),
(72, 61, 2, '导航管理', 'admin:navigation:list', '/content/navigation', 'content/navigation/index', 'Menu', 120, 1, 1, NOW(), NOW()),
(73, 72, 3, '新增导航', 'admin:navigation:create', '', '', '', 100, 0, 1, NOW(), NOW()),
(74, 72, 3, '编辑导航', 'admin:navigation:update', '', '', '', 101, 0, 1, NOW(), NOW()),
(75, 72, 3, '启用禁用导航', 'admin:navigation:status', '', '', '', 102, 0, 1, NOW(), NOW()),
(76, 72, 3, '删除导航', 'admin:navigation:delete', '', '', '', 103, 0, 1, NOW(), NOW()),
(77, 61, 2, '幻灯管理', 'admin:banner:list', '/content/banners', 'content/banner/index', 'Picture', 130, 1, 1, NOW(), NOW()),
(78, 77, 3, '新增幻灯', 'admin:banner:create', '', '', '', 100, 0, 1, NOW(), NOW()),
(79, 77, 3, '编辑幻灯', 'admin:banner:update', '', '', '', 101, 0, 1, NOW(), NOW()),
(80, 77, 3, '启用禁用幻灯', 'admin:banner:status', '', '', '', 102, 0, 1, NOW(), NOW()),
(81, 77, 3, '删除幻灯', 'admin:banner:delete', '', '', '', 103, 0, 1, NOW(), NOW());

INSERT INTO `vtp_admin_notice` (`title`, `content`, `type`, `scope_type`, `scope_ids`, `popup`, `status`, `create_time`, `update_time`) VALUES
('系统初始化完成', '通用后台基础模块已经启用，可以继续扩展业务功能。', 'success', 'all', '', 0, 1, NOW(), NOW());

INSERT INTO `vtp_admin_role_menu` (`role_id`, `menu_id`, `create_time`)
SELECT 1, `id`, NOW() FROM `vtp_admin_menu`;

SET FOREIGN_KEY_CHECKS = 1;
