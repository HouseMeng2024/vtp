# VTP Admin

VTP Admin 是一个基于 ThinkPHP 8 和 Vue 3 的通用后台管理系统，适合接单项目时作为后台基础模板继续扩展。

## 技术栈

- 后端：ThinkPHP 8、多应用模式、ThinkORM
- 前端：Vue 3、TypeScript、Vite、Element Plus、Pinia、Vue Router
- 实时服务：Workerman、GatewayWorker
- 数据库：MySQL，SQL 兼容目标为 MySQL 5.7
- 缓存：Redis，按 Redis 7.2 兼容思路使用

## 功能模块

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

## 目录结构

```text
app/admin                后台接口应用
app/index                前台应用
app/common/base          公共基类
app/common/model         公共模型
app/common/service/admin 后台业务服务
admin-web                后台 Vue 前端
database                 数据库初始化脚本
public                   Web 入口和静态资源
worker                   Workerman/GatewayWorker 服务
runtime                  运行时目录
```

## 环境要求

- PHP >= 8.0
- Composer
- MySQL 5.7+ 或兼容版本
- Node.js
- Yarn 1.x

## 后端启动

安装 PHP 依赖：

```bash
composer install
```

配置数据库。默认读取根目录 `.env`：

```ini
APP_DEBUG = true

DB_TYPE = mysql
DB_HOST = 127.0.0.1
DB_NAME = vtp
DB_USER = root
DB_PASS =
DB_PORT = 3306
DB_CHARSET = utf8mb4
DB_PREFIX = vtp_

DEFAULT_LANG = zh-cn
```

创建数据库并导入初始化 SQL：

```bash
mysql -uroot -p -e "CREATE DATABASE IF NOT EXISTS vtp DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -uroot -p vtp < database/admin_schema.sql
```

启动 ThinkPHP 开发服务：

```bash
php think run
```

默认后端地址：

```text
http://127.0.0.1:8000
```

## 后台前端启动

进入后台前端目录：

```bash
cd admin-web
yarn install
yarn dev
```

默认前端地址以 Vite 输出为准，通常是：

```text
http://localhost:5173
```

开发环境下，`admin-web/vite.config.ts` 已代理：

- `/admin` 到 `http://127.0.0.1:8000`
- `/storage` 到 `http://127.0.0.1:8000`

## 默认账号

初始化 SQL 默认后台账号：

```text
账号：admin
密码：admin123
```

如果本地测试库手动改过密码，以当前数据库为准。

## Workerman

前台简单聊天和实时服务相关代码放在 `worker`。

前台启动：

```bash
php worker/start.php start
```

后台守护进程启动：

```bash
php worker/start.php start -d
```

停止：

```bash
php worker/start.php stop
```

重启：

```bash
php worker/start.php restart
```

运行日志和 PID 在 `runtime` 目录。

## 前端构建

```bash
cd admin-web
yarn build
```

构建产物在：

```text
admin-web/dist
```

部署时可由 Nginx 指向该目录，也可以按项目部署方案复制到后端静态目录。

## 数据库约定

- 默认字符集使用 `utf8mb4`。
- 默认排序规则使用 `utf8mb4_unicode_ci`。
- 数据库表统一使用 `vtp_` 前缀，模型和代码生成器配置里仍使用不带前缀的逻辑表名。
- SQL 尽量兼容 MySQL 5.7。
- 表结构维护在 `database/admin_schema.sql`。
- 业务开发优先使用 ThinkPHP Model。
- 分页使用 ThinkPHP 自带 `paginate()`。
- `create_time`、`update_time` 使用框架自动时间戳。
- 需要软删除的模型显式使用 SoftDelete trait。

## 开发约定

- 控制器只负责参数接收、调用 Service、返回响应。
- 业务逻辑写在 `app/common/service/admin`。
- 后台接口权限走数据库菜单权限码。
- 前端菜单和权限来自后端，不在前端维护重复权限表。
- 后台路由使用自动路由，`app/admin/route/app.php` 保持极简。
- 前端接口按业务模块拆到 `admin-web/src/api`。
- UI 以 Element Plus 默认样式为主，少写自定义颜色。
- 文件和图片选择统一使用已有文件选择器。

更多 Agent 接手规则见：

```text
AGENTS.md
```

## 验证命令

PHP 文件语法检查：

```bash
php -l app/admin/controller/User.php
```

前端构建检查：

```bash
cd admin-web
yarn build
```

## 常见目录权限

本地或服务器部署时，需要确保以下目录可写：

```text
runtime
public/storage
```

## 说明

这是通用后台模板项目。后续业务模块建议优先通过现有代码生成器生成基础 CRUD，再按业务需要补充 Service 逻辑和页面交互。
