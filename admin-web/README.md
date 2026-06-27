# 每日云彩收集 - 管理后台

Vue 3 + Vite + Element Plus 管理端，打包后部署到 Laravel `public/admin/`，通过 `https://域名/admin/` 访问。

## 技术栈

- Vue 3 + TypeScript
- Vite 6
- Element Plus
- Pinia + Vue Router
- Axios（对接 Laravel `{ code, msg, data }` 响应格式）

## 开发

```bash
cd admin-web
npm install
npm run dev
```

开发地址：http://localhost:5173/admin/

**默认连本地 API**：`.env.development` 中 `VITE_API_PROXY_TARGET=http://127.0.0.1:8000`，需先启动 Laravel。

若要连本地 Laravel，修改 `.env.development`：

```env
VITE_API_PROXY_TARGET=http://127.0.0.1:8000
```

并启动 `php artisan serve`。

## 构建部署

```bash
npm run build
```

产物输出到 `../public/admin/`，访问路径：`https://你的域名/admin/`

## 环境变量

| 变量 | 说明 |
|------|------|
| `VITE_API_BASE` | 管理端 API 前缀，默认 `/api/admin/v1` |
| `VITE_API_PROXY_TARGET` | 开发代理目标，默认 `http://127.0.0.1:8000`（仅 `npm run dev` 生效） |

## Nginx（宝塔）

在 `location /` 之前添加：

```nginx
location = /admin {
    return 301 /admin/;
}

location ^~ /admin/ {
    root /www/wwwroot/你的站点/public;
    try_files $uri $uri/ /admin/index.html;
}
```

## 目录结构

```
src/
├── api/          # 接口封装
├── layouts/      # 布局组件
├── router/       # 路由（base: /admin/）
├── stores/       # Pinia 状态
├── views/        # 页面
└── styles/       # 全局样式
```

## 待对接后端接口

- `POST /api/admin/v1/auth/login`
- `GET  /api/admin/v1/auth/me`
- `POST /api/admin/v1/auth/logout`
- `GET  /api/admin/v1/dashboard/overview`
