-- Remove the i18n module and restore default Chinese admin labels.
-- Compatible with MySQL 5.7.

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM `vtp_admin_role_menu`
WHERE `menu_id` IN (
  SELECT `id` FROM `vtp_admin_menu`
  WHERE `permission` IN ('admin:translation:list', 'admin:translation:update')
     OR `path` = '/system/translations'
     OR `component` = 'system/translation/index'
);

DELETE FROM `vtp_admin_menu`
WHERE `permission` IN ('admin:translation:list', 'admin:translation:update')
   OR `path` = '/system/translations'
   OR `component` = 'system/translation/index';

UPDATE `vtp_admin_menu` SET `title` = CASE `id`
  WHEN 1 THEN '系统设置'
  WHEN 2 THEN '管理员管理'
  WHEN 3 THEN '新增管理员'
  WHEN 4 THEN '编辑管理员'
  WHEN 5 THEN '删除管理员'
  WHEN 6 THEN '角色管理'
  WHEN 7 THEN '菜单管理'
  WHEN 8 THEN '禁用启用管理员'
  WHEN 9 THEN '新增角色'
  WHEN 10 THEN '编辑角色'
  WHEN 11 THEN '禁用启用角色'
  WHEN 12 THEN '删除角色'
  WHEN 13 THEN '分配角色权限'
  WHEN 14 THEN '新增菜单'
  WHEN 15 THEN '编辑菜单'
  WHEN 16 THEN '删除菜单'
  WHEN 17 THEN '项目配置'
  WHEN 18 THEN '保存项目配置'
  WHEN 19 THEN '文件管理'
  WHEN 20 THEN '上传文件'
  WHEN 21 THEN '删除文件'
  WHEN 22 THEN '字典管理'
  WHEN 23 THEN '新增字典'
  WHEN 24 THEN '编辑字典'
  WHEN 25 THEN '禁用启用字典'
  WHEN 26 THEN '删除字典'
  WHEN 27 THEN '日志管理'
  WHEN 28 THEN '登录日志'
  WHEN 29 THEN '操作日志'
  WHEN 30 THEN '强制管理员下线'
  WHEN 31 THEN '编辑文件'
  WHEN 32 THEN '删除登录日志'
  WHEN 33 THEN '清空登录日志'
  WHEN 34 THEN '删除操作日志'
  WHEN 35 THEN '清空操作日志'
  WHEN 36 THEN '系统工具'
  WHEN 37 THEN '清理缓存'
  WHEN 38 THEN '查看数据库备份'
  WHEN 39 THEN '创建数据库备份'
  WHEN 40 THEN '恢复数据库备份'
  WHEN 41 THEN '删除数据库备份'
  WHEN 42 THEN '下载数据库备份'
  WHEN 43 THEN '消息通知'
  WHEN 44 THEN '新增消息'
  WHEN 45 THEN '编辑消息'
  WHEN 46 THEN '启用禁用消息'
  WHEN 47 THEN '删除消息'
  WHEN 48 THEN '权限管理'
  WHEN 49 THEN '代码生成'
  WHEN 50 THEN '生成代码'
  WHEN 51 THEN '会员管理'
  WHEN 52 THEN '新增会员'
  WHEN 53 THEN '编辑会员'
  WHEN 54 THEN '启用禁用会员'
  WHEN 55 THEN '重置会员密码'
  WHEN 56 THEN '删除会员'
  WHEN 57 THEN '配置管理'
  WHEN 58 THEN '新增配置结构'
  WHEN 59 THEN '编辑配置结构'
  WHEN 60 THEN '删除配置结构'
  ELSE `title`
END
WHERE `id` BETWEEN 1 AND 60;

UPDATE `vtp_system_config_group` SET `title` = CASE `key`
  WHEN 'system' THEN '系统配置'
  WHEN 'admin' THEN '后台配置'
  WHEN 'index' THEN '前台配置'
  ELSE `title`
END
WHERE `key` IN ('system', 'admin', 'index');

UPDATE `vtp_system_config_tab` SET `title` = CASE `key`
  WHEN 'system_basic' THEN '基础规范'
  WHEN 'system_upload' THEN '上传规范'
  WHEN 'system_security' THEN '安全规范'
  WHEN 'admin_basic' THEN '后台基础'
  WHEN 'admin_login' THEN '登录安全'
  WHEN 'index_site' THEN '网站信息'
  WHEN 'index_seo' THEN 'SEO 配置'
  ELSE `title`
END
WHERE `key` IN ('system_basic', 'system_upload', 'system_security', 'admin_basic', 'admin_login', 'index_site', 'index_seo');

UPDATE `vtp_system_config` SET `name` = CASE `id`
  WHEN 1 THEN '上传大小限制'
  WHEN 2 THEN '允许扩展名'
  WHEN 3 THEN '密码最小长度'
  WHEN 4 THEN '登录失败次数'
  WHEN 5 THEN '登录锁定时长'
  WHEN 6 THEN '后台标题'
  WHEN 7 THEN '后台 Logo'
  WHEN 8 THEN '后台描述'
  WHEN 9 THEN '后台登录有效期'
  WHEN 10 THEN '后台登录验证码'
  WHEN 11 THEN '网站标题'
  WHEN 12 THEN '网站 Logo'
  WHEN 13 THEN 'SEO 标题'
  WHEN 14 THEN 'SEO 关键词'
  WHEN 15 THEN 'SEO 描述'
  ELSE `name`
END,
`remark` = CASE `id`
  WHEN 1 THEN '单位 MB，所有模块上传默认遵守'
  WHEN 2 THEN '多个扩展名用英文逗号分隔'
  WHEN 3 THEN '系统账号类密码最小长度'
  WHEN 4 THEN '达到次数后临时锁定'
  WHEN 5 THEN '单位秒'
  WHEN 6 THEN '后台浏览器标题和顶部品牌名称'
  WHEN 7 THEN '后台登录页和顶部品牌 Logo'
  WHEN 8 THEN '后台登录页展示说明'
  WHEN 9 THEN '单位秒，仅作用于 admin 模块'
  WHEN 10 THEN '开启后后台登录需要输入验证码'
  WHEN 11 THEN '前台 index 模块默认网站标题'
  WHEN 12 THEN '前台站点 Logo'
  WHEN 13 THEN '前台默认 SEO 标题'
  WHEN 14 THEN '多个关键词用英文逗号分隔'
  WHEN 15 THEN '前台页面默认 SEO 描述'
  ELSE `remark`
END
WHERE `id` BETWEEN 1 AND 15;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vtp_admin_menu' AND COLUMN_NAME = 'i18n_key') > 0, 'ALTER TABLE `vtp_admin_menu` DROP COLUMN `i18n_key`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vtp_system_config_group' AND COLUMN_NAME = 'title_i18n_key') > 0, 'ALTER TABLE `vtp_system_config_group` DROP COLUMN `title_i18n_key`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vtp_system_config_tab' AND COLUMN_NAME = 'title_i18n_key') > 0, 'ALTER TABLE `vtp_system_config_tab` DROP COLUMN `title_i18n_key`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vtp_system_config' AND COLUMN_NAME = 'name_i18n_key') > 0, 'ALTER TABLE `vtp_system_config` DROP COLUMN `name_i18n_key`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vtp_system_config' AND COLUMN_NAME = 'remark_i18n_key') > 0, 'ALTER TABLE `vtp_system_config` DROP COLUMN `remark_i18n_key`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

DROP TABLE IF EXISTS `vtp_i18n_message`;
DROP TABLE IF EXISTS `vtp_i18n_locale`;

SET FOREIGN_KEY_CHECKS = 1;
