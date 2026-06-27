# 云屿集 · Daily Cloud Collection

[![GitHub stars](https://img.shields.io/github/stars/qianbaidu0721/daily-cloud-collection?style=social)](https://github.com/qianbaidu0721/daily-cloud-collection/stargazers)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Laravel](https://img.shields.io/badge/Laravel-11-red.svg)](https://laravel.com)
[![Vue](https://img.shields.io/badge/Vue-3-green.svg)](https://vuejs.org)

「云屿集」是一套 **每日云朵收集** 全栈方案：微信小程序 + Laravel API + Vue 管理后台。

每天拍一朵云，记录心情与位置，生成分享卡片，在云朵广场与他人共享天空。

## 预览

> 截图请放入 [`docs/screenshots/`](docs/screenshots/README.md) 目录。添加文件后，将下方注释取消即可展示。

| 首页 | 日历 | 分享卡片 |
|:---:|:---:|:---:|
| <!-- ![首页](docs/screenshots/home.png) --> *home.png* | <!-- ![日历](docs/screenshots/calendar.png) --> *calendar.png* | <!-- ![分享卡片](docs/screenshots/share-card.png) --> *share-card.png* |

| 云朵广场 | 管理后台 |
|:---:|:---:|
| <!-- ![广场](docs/screenshots/plaza.png) --> *plaza.png* | <!-- ![管理后台](docs/screenshots/admin-dashboard.png) --> *admin-dashboard.png* |

## 功能概览

| 模块 | 能力 |
|------|------|
| 小程序 | 每日收集、心情/云型/位置、日历、云朵列表、分享卡片、云朵广场 |
| API | JWT 登录、图片上传、逆地理编码、分享卡片生成、公开广场 |
| 管理后台 | 用户/云朵/云类型管理、数据统计 |

## 项目结构

```
daily-cloud-collection/
├── app/                 # Laravel 后端
├── miniprogram/         # 微信小程序
├── admin-web/           # Vue 3 + Element Plus 管理端
├── public/admin/        # 管理端构建产物（npm run build 输出，默认 gitignore）
├── database/            # 迁移与种子
├── deploy/              # Nginx 配置示例
└── resources/fonts/     # 分享卡片中文字体（需自行放置）
```

## 快速开始

### 1. 后端 API

```bash
composer install
cp .env.example .env
# 编辑 .env：数据库、WECHAT_APPID/SECRET、AMAP_KEY（逆地理编码，可选）

php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

健康检查：`GET http://127.0.0.1:8000/api/v1/health`

### 2. 微信小程序

1. 用[微信开发者工具](https://developers.weixin.qq.com/miniprogram/dev/devtools/download.html)打开 `miniprogram/`
2. 复制 `project.config.example.json` 为 `project.config.json`，填入你的 **AppID**
3. 复制 `utils/config.example.js` 为 `utils/config.js`，填入 API 地址
4. 公众平台配置服务器域名（request / uploadFile）

详见 [miniprogram/README.md](miniprogram/README.md)

### 3. 管理后台

```bash
cd admin-web
npm install
cp .env.example .env.development   # 可选，默认代理到本地 8000 端口
npm run dev                          # http://localhost:5173
npm run build                        # 输出到 ../public/admin/
```

默认管理员（`php artisan db:seed` 后）：见 `.env.example` 中 `ADMIN_EMAIL` / `ADMIN_PASSWORD`

详见 [admin-web/README.md](admin-web/README.md)

## 主要 API

前缀：`/api/v1` · 认证：`Authorization: Bearer {token}`

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/auth/login` | 微信登录 |
| GET | `/clouds/today` | 今日收集状态 |
| POST | `/clouds/upload` | 上传云朵 |
| GET | `/clouds` | 我的云朵列表 |
| GET | `/clouds/public` | 云朵广场 |
| POST | `/clouds/card` | 生成分享卡片 |
| GET | `/cloud-types` | 云类型列表 |
| GET | `/location/reverse` | 经纬度逆解析 |

## 生产部署要点

- Web 根目录指向 `public/`
- `php artisan config:cache` / `route:cache`
- `storage/`、`bootstrap/cache/` 可写
- 分享卡片需安装 `intervention/image`，并在 `resources/fonts/` 放置中文字体
- 管理后台：`cd admin-web && npm run build`，访问 `https://域名/admin/`

Nginx 参考：[deploy/nginx.conf.example](deploy/nginx.conf.example)

## 环境变量

见 [.env.example](.env.example)，关键项：

| 变量 | 说明 |
|------|------|
| `WECHAT_APPID` / `WECHAT_SECRET` | 小程序凭证 |
| `JWT_SECRET` | `php artisan jwt:secret` 生成 |
| `AMAP_KEY` | 高德 Web 服务 Key（逆地理编码） |
| `ADMIN_*` | 管理后台初始账号 |

## 参与贡献

详见 [CONTRIBUTING.md](CONTRIBUTING.md)。

- 报告 Bug：[Issue 模板](https://github.com/qianbaidu0721/daily-cloud-collection/issues/new/choose)
- 协议：[MIT](LICENSE)
- **请勿**将 `.env`、JWT 密钥、微信 Secret 提交到仓库

## 发布版本

| 版本 | 说明 |
|------|------|
| [v1.0.0](https://github.com/qianbaidu0721/daily-cloud-collection/releases/tag/v1.0.0) | 首个开源版本：小程序 + API + 管理后台 |

---

如果这个项目对你有帮助，欢迎 [Star ⭐](https://github.com/qianbaidu0721/daily-cloud-collection/stargazers)
