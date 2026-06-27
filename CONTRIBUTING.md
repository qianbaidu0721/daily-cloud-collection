# 参与贡献

感谢你对「云屿集 / Daily Cloud Collection」的关注！欢迎通过 Issue 或 Pull Request 参与项目。

## 开始之前

1. 先搜索 [Issues](https://github.com/qianbaidu0721/daily-cloud-collection/issues)，避免重复提交
2. 较大改动请先开 Issue 讨论方案
3. **切勿**提交 `.env`、微信 Secret、JWT 密钥、真实 AppID、生产域名

## 本地开发

### 后端

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
php artisan serve
```

### 小程序

1. 复制 `miniprogram/project.config.example.json` → `project.config.json`，填入你的 AppID
2. 复制 `miniprogram/utils/config.example.js` → `utils/config.js`，填入 API 地址
3. 用微信开发者工具打开 `miniprogram/`

### 管理后台

```bash
cd admin-web
npm install
cp .env.example .env.development
npm run dev
```

## 提交 Pull Request

1. Fork 本仓库，从 `main` 创建分支（如 `fix/upload-error`、`feat/calendar-filter`）
2. 保持 commit 信息清晰，例如：
   - `fix: 修复分享卡片缓存未失效`
   - `feat: 日历页增加月份筛选`
   - `docs: 补充部署说明`
3. 确保变更可运行：
   - 后端：`php artisan migrate` 无报错
   - 管理端：`npm run build` 通过
4. 发起 PR，说明改动动机、测试方式、关联 Issue（如有）

## 代码规范

- **PHP**：遵循 Laravel 惯例，运行 `./vendor/bin/pint`（如已安装）
- **Vue/TS**：与 `admin-web` 现有风格一致
- **小程序**：与现有页面结构、命名保持一致
- 注释仅用于非显而易见的业务逻辑

## 报告 Bug

请使用 [Bug 反馈模板](https://github.com/qianbaidu0721/daily-cloud-collection/issues/new?template=bug_report.yml)，附上复现步骤与环境信息。

## 协议

贡献代码即表示你同意以 [MIT License](LICENSE) 授权你的贡献。
