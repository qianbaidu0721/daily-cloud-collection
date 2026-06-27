# 云屿集 - 微信小程序

「每日云彩收集」小程序前端，对接 Laravel 后端 API。

## 快速开始

1. 用[微信开发者工具](https://developers.weixin.qq.com/miniprogram/dev/devtools/download.html)打开本目录 `miniprogram/`
2. 修改 `project.config.json` 中的 `appid` 为你的小程序 AppID
3. 修改 `utils/config.js` 中的 `baseURL`（参考 `config.example.js`）
4. 在微信公众平台 → 开发管理 → 开发设置 → 服务器域名，添加：
   - request 合法域名：`https://你的API域名`
   - uploadFile 合法域名：同上

## 目录结构

```
miniprogram/
├── pages/
│   ├── index/          # 首页 - 今日收集与上传
│   ├── list/           # 云朵列表（瀑布流）
│   └── calendar/       # 日历视图
├── components/
│   ├── cloud-card/     # 云朵卡片
│   └── mood-picker/    # 心情选择器
├── utils/
│   ├── config.js       # baseURL 等配置
│   ├── request.js      # 请求封装（JWT、Loading、错误处理）
│   ├── auth.js         # 登录
│   ├── api.js          # 业务 API
│   ├── constants.js    # 心情、云类型常量
│   └── util.js         # 工具函数
├── assets/icons/       # TabBar 图标（可替换为设计稿）
├── app.js
├── app.json
└── app.wxss
```

## 功能说明

### 首页
- 展示日期与时段天气文案
- 显示今日是否已收集
- 上传流程：选图 → 心情 → 云类型 → 自动定位 → 提交

### 列表页
- 双列瀑布流展示
- 下拉刷新、上拉加载更多（15 条/页）

### 日历页
- 月度日历，有云日期显示 ☁️
- 点击日期查看当天云朵

## API 对接

| 接口 | 说明 |
|------|------|
| POST `/api/v1/auth/login` | wx.login 换 JWT |
| GET `/api/v1/clouds/today` | 今日状态 |
| POST `/api/v1/clouds/upload` | 上传云朵 |
| GET `/api/v1/clouds?page=1` | 分页列表 |
| GET `/api/v1/health` | 健康检查（部署验证用） |

## 注意事项

- TabBar 图标为占位图，建议替换 `assets/icons/` 下 6 个 PNG
- 定位需在小程序后台配置用户隐私保护指引
- 开发阶段可在开发者工具中勾选「不校验合法域名」
