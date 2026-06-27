# 每日云彩收集 API

微信小程序「每日云彩收集」后端，基于 **Laravel 11 + MySQL + JWT**。

## 开发服务器部署（无需本地 PHP 环境）

将本项目上传到开发服务器后，依次执行：

```bash
# 1. 安装依赖
composer install --no-dev --optimize-autoloader

# 2. 复制环境配置
cp .env.example .env

# 3. 编辑 .env，填写数据库与微信配置
# DB_HOST / DB_DATABASE / DB_USERNAME / DB_PASSWORD
# WECHAT_MINI_APP_ID / WECHAT_MINI_APP_SECRET

# 4. 生成应用密钥与 JWT 密钥
php artisan key:generate
php artisan jwt:secret

# 5. 创建数据库（MySQL）
# CREATE DATABASE daily_cloud CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 6. 执行迁移与种子数据
php artisan migrate --seed

# 7. 创建图片存储软链接（云朵图片通过 /storage/clouds/... 访问）
php artisan storage:link
chmod -R 775 storage bootstrap/cache
mkdir -p storage/app/public/clouds

# 8. 启动开发服务（或配置 Nginx 指向 public/）
php artisan serve --host=0.0.0.0 --port=8000
```

Nginx 站点根目录**必须**指向 `public/`（不是项目根目录），API 前缀为 `/api/v1/*`。

### 接口 404 排查

1. **宝塔站点目录**：网站 → 设置 → 网站目录 → 运行目录选 `/public`
2. **验证接口**：浏览器访问 `https://你的域名/api/v1/health`，应返回 `{"status":"ok"}`
3. **清除路由缓存**（服务器上执行）：
   ```bash
   php artisan route:clear
   php artisan config:clear
   ```
4. **Nginx 配置参考**：见 `deploy/nginx.conf.example`

---

## 云朵 API（需 JWT 认证）

请求头：`Authorization: Bearer {token}`

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/api/v1/auth/login` | 微信登录 |
| POST | `/api/v1/clouds/upload` | 上传云朵（multipart/form-data） |
| GET | `/api/v1/clouds/today` | 查询今日是否已上传 |
| GET | `/api/v1/clouds` | 云朵列表（分页） |
| GET | `/api/v1/clouds/{id}` | 云朵详情 |
| GET | `/api/v1/health` | 健康检查 |
| GET | `/api/clouds` | 云朵列表（15 条/页，按日期倒序） |
| GET | `/api/clouds/{id}` | 云朵详情 |

**上传字段：** `image`（文件）、`mood`（1-5）、`collect_date`（Y-m-d）、`mood_label`、`location_city`、`location_lat`、`location_lng`、`note`、`cloud_type`

**图片存储：** `storage/app/public/clouds/年/月/日/`，需执行 `php artisan storage:link` 后通过 `APP_URL/storage/clouds/...` 访问。

---

## 项目目录结构

```
daily-cloud-collection/
├── app/                          # 应用核心代码
│   ├── Http/
│   │   └── Controllers/          # 控制器（待添加：AuthController、CloudController）
│   ├── Models/
│   │   ├── User.php              # 用户模型（JWTSubject，hasMany Clouds）
│   │   ├── Cloud.php             # 云朵记录（belongsTo User、CloudType）
│   │   └── CloudType.php         # 云类型字典（hasMany Clouds）
│   └── Providers/
│       └── AppServiceProvider.php
├── bootstrap/
│   ├── app.php                   # Laravel 11 应用启动与路由注册
│   └── providers.php
├── config/
│   ├── app.php                   # 应用基础配置
│   ├── auth.php                  # 认证守卫（api 使用 jwt 驱动）
│   ├── database.php              # MySQL 连接（读取 .env）
│   └── jwt.php                   # JWT 配置（tymon/jwt-auth）
├── database/
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php   # users + sessions
│   │   ├── 2024_01_01_000001_create_cloud_types_table.php
│   │   └── 2024_01_01_000002_create_clouds_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── CloudTypeSeeder.php   # 预置 8 种云类型
├── public/
│   └── index.php                 # Web 入口
├── routes/
│   ├── api.php                   # API 路由（/api/v1/*）
│   ├── web.php
│   └── console.php
├── storage/
│   ├── app/public/               # 云朵照片存储目录
│   └── logs/
├── .env.example                  # 环境变量模板
├── artisan                       # Artisan CLI
└── composer.json                 # PHP 8.2+ / Laravel 11 / jwt-auth
```

---

## 数据库表设计

### users（用户表）

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| openid | varchar(64) | 微信小程序 OpenID，唯一 |
| unionid | varchar(64) | 微信 UnionID，可空 |
| nickname | varchar(64) | 昵称 |
| avatar | varchar | 头像 URL |
| created_at / updated_at | timestamp | 时间戳 |

### cloud_types（云类型字典表）

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| name | varchar(32) | 类型名称（积云、层云等） |
| code | varchar(32) | 唯一编码 |
| description | varchar | 描述 |
| icon | varchar | 图标，可空 |
| sort | smallint | 排序 |
| is_active | boolean | 是否启用 |

### clouds（云朵记录表）

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| user_id | bigint | 外键 → users |
| cloud_type_id | bigint | 外键 → cloud_types，可空 |
| image_path | varchar | 照片存储路径 |
| mood | varchar(32) | 心情标签 |
| latitude / longitude | decimal(10,7) | 经纬度 |
| location_name | varchar | 位置描述 |
| record_date | date | 记录日期（每用户每天唯一） |
| note | text | 备注 |

**约束：** `(user_id, record_date)` 唯一索引，保证每天只能上传一张。

---

## Model 关联关系

```
User
 └── hasMany → Cloud

CloudType
 └── hasMany → Cloud

Cloud
 ├── belongsTo → User
 └── belongsTo → CloudType
```

---

## JWT 认证说明

- `User` 模型已实现 `Tymon\JWTAuth\Contracts\JWTSubject`
- `config/auth.php` 中 `api` 守卫驱动为 `jwt`
- 部署后执行 `php artisan jwt:secret` 写入 `JWT_SECRET`
- 小程序登录流程（待实现）：`wx.login` → 后端换 openid → 签发 JWT → 后续请求带 `Authorization: Bearer {token}`

---

## 环境变量要点

详见 `.env.example`，关键项：

| 变量 | 说明 |
|------|------|
| DB_* | MySQL 连接信息 |
| JWT_SECRET | JWT 签名密钥 |
| JWT_TTL | Token 有效期（分钟，默认 7 天） |
| WECHAT_MINI_APP_ID | 小程序 AppID |
| WECHAT_MINI_APP_SECRET | 小程序 AppSecret |
| CLOUD_IMAGE_DISK | 云朵图片存储磁盘（默认 public） |
