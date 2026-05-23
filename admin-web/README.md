# VTP Admin Web

VTP 后台前端，基于 Vue 3、TypeScript、Vite、Element Plus、Pinia 和 Vue Router。

## 启动

```bash
yarn install
yarn dev
```

默认开发地址以 Vite 输出为准，通常是：

```text
http://localhost:5173
```

开发代理在 `vite.config.ts` 中维护：

- `/admin` 代理到后端后台接口
- `/index` 代理到后端前台接口
- `/storage` 代理到后端静态上传资源

## 构建

```bash
yarn build
```

构建产物输出到 `dist`。

## 目录

```text
src/api          后台接口模块，按业务拆分
src/components   通用组件，包括文件选择器和富文本组件
src/router       路由守卫和动态页面加载
src/stores       Pinia 状态
src/views        后台页面
src/utils        请求、资源地址等工具
```

## 约定

- 菜单、路由和权限来自后端菜单数据。
- 接口文件按业务模块放在 `src/api`，不要堆到单个文件。
- 页面优先使用 Element Plus 组件和项目已有交互模式。
- 图片、文件、富文本插图统一复用文件选择器。
- 修改前端后运行 `yarn build` 检查。
