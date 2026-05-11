# VTP 后台系统 Agent 说明

## 基本要求

- 始终使用简体中文回复。
- 这是一个通用后台管理系统，优先保持代码稳定、清晰、可复用。
- 不要随意引入重型依赖，不要做无关重构。
- 修改前先看现有代码风格，按当前项目结构继续写。
- 删除、重命名、迁移文件前必须确认影响范围。

## 技术栈

- 后端：ThinkPHP 8，多应用模式。
- 前端：Vue 3、TypeScript、Vite、Element Plus、Pinia、Vue Router。
- 实时服务：Workerman 5、GatewayWorker。
- 数据库：MySQL，SQL 尽量兼容 MySQL 5.7。
- Redis：按 Redis 7.2 兼容思路使用。

## 目录约定

- 后台接口控制器放在 `app/admin/controller`。
- 前台接口控制器放在 `app/index/controller`。
- 公共基类放在 `app/common/base`。
- 公共模型放在 `app/common/model`。
- 后台业务服务放在 `app/common/service/admin`。
- 后台前端放在 `admin-web`。
- Workerman 入口和配置放在 `worker`。
- 数据库结构维护在 `database/admin_schema.sql`。

## 后端开发规则

- 控制器只负责接收参数、调用 Service、返回响应。
- 业务逻辑写到 Service，不要堆在 Controller。
- 数据库操作优先使用 ThinkPHP Model，不要直接手写 `Db` 查询，除非确实更合适。
- 分页使用 ThinkPHP 自带 `paginate()`，不要自己组装分页结构。
- `create_time`、`update_time` 使用 ThinkPHP 默认自动时间戳能力。
- 软删除模型需要显式使用 SoftDelete trait。
- 自己编写的 PHP 文件、类和方法都要加必要注释。
- 接口返回统一使用项目已有响应结构。
- 后台接口权限走菜单权限码，不要在前端写死权限。
- 自动路由已启用，`app/admin/route/app.php` 保持极简，不要手动堆后台路由。

## 前端开发规则

- 后台页面统一放在 `admin-web/src/views`。
- 接口按业务模块拆到 `admin-web/src/api`，不要把所有接口塞进一个文件。
- UI 以 Element Plus 默认样式为主，除主题色、布局变量外尽量少写自定义颜色。
- 列表页面以表格为主，页面不整体乱滚，优先让表格区域内部滚动。
- 新增、编辑、详情、删除、状态切换等通用交互保持一致。
- 文件、图片选择统一使用已有文件选择器，不要直接在表单里散落上传按钮。
- 菜单和权限来自后端数据，前端不要维护重复权限表。

## 已有核心模块

- 登录认证
- 控制台
- 管理员管理
- 角色管理
- 菜单管理
- 会员管理
- 项目配置
- 文件管理和文件选择器
- 字典管理
- 通知管理
- 登录日志
- 操作日志
- 系统工具
- 代码生成器

## 文件上传约定

- 数据库记录保留每次上传行为。
- 文件内容相同且后缀相同时，物理文件可复用同一个地址。
- 删除上传记录时，如果还有其他记录引用同一物理文件，只删除记录；只有最后一条引用被删时，才删除物理文件。
- 上传场景使用 `scene` 标记，例如 `avatar`、`setting`、`goods`，测试阶段可直接使用英文值。
- 图片选择器要展示图片缩略图，不要只展示资源路径。

## 代码生成器约定

- 代码生成器在后台页面操作，生成结果应尽量直接落地到项目。
- 默认生成后端、前端、菜单、数据表。
- 生成后台接口时使用自动路由，不要生成独立路由文件。
- 生成前端 API 时按业务模块新建或覆盖模块文件。
- 字段类型、字典、列表显示、搜索、必填等配置由生成器配置决定。
- 生成测试模块后，如果不再需要，要清理对应后端、前端、菜单、权限和数据库表。

## 数据库约定

- 默认字符集使用 `utf8mb4`。
- 排序规则优先使用 `utf8mb4_unicode_ci`，通用性和多语言排序更稳。
- SQL 兼容 MySQL 5.7，避免使用只在 MySQL 8+ 才支持的语法。
- 通用后台表当前包括：
  - `admin_user`
  - `admin_role`
  - `admin_menu`
  - `admin_user_role`
  - `admin_role_menu`
  - `admin_login_log`
  - `admin_operate_log`
  - `admin_notice`
  - `admin_notice_read`
  - `member`
  - `system_config`
  - `upload_file`
  - `dict_type`
  - `dict_data`

## 本地运行

- 后端开发服务：

```bash
php think run
```

- 后台前端开发服务：

```bash
cd admin-web
yarn dev
```

- 后台前端构建检查：

```bash
cd admin-web
yarn build
```

- Workerman 启动：

```bash
php worker/start.php start
```

- Workerman 后台启动：

```bash
php worker/start.php start -d
```

## 验证要求

- 修改 PHP 后至少运行相关文件的 `php -l`。
- 修改前端后运行 `cd admin-web && yarn build`。
- 涉及页面布局和交互时，需要实际打开页面检查。
- 不要提交 vendor、node_modules、runtime 日志等生成内容。


