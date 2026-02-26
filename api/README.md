# API 接口说明

## 基础

- 所有接口返回 JSON，格式：`{ success, message, data }`
- 开发环境 CORS 已开启，支持跨域

## 接口列表

### 1. 模型列表

```
GET /api/models/index.php?type=image|video
```

### 2. 模板列表

```
GET /api/templates/index.php?type=image|video
```

### 3. 分类列表

```
GET /api/categories/index.php
```

### 4. 教程列表

```
GET /api/tutorials/index.php
```

### 5. 资产列表

```
GET /api/assets/index.php?filter=all|image|video&page=1&limit=20
```

### 6. 生成任务（占位）

```
POST /api/generation/create.php
Content-Type: application/json

{
  "prompt": "提示词",
  "model": "banana pro",
  "type": "image|video",
  "aspectRatio": "3:4",
  "mode": "single|multiple",
  "quality": "2k|4k"
}
```

### 7. 发布模板

```
POST /api/publish/create.php
Content-Type: application/json

{
  "contentType": "image|video",
  "modelId": "banana-pro",
  "category": "室内",
  "title": "模板标题",
  "content": "模板内容",
  "image": "https://xxx.oss-cn-xxx.aliyuncs.com/..."  // 可选，OSS 预览图 URL
}
```

### 8. 图片上传（OSS）

```
POST /api/upload/image.php
Content-Type: multipart/form-data

file: 图片文件（必填）
prefix: 可选，assets/images/templates | assets/images/references | assets/images/frames

返回: { success: true, data: { url: "https://bucket.oss-cn-xxx.aliyuncs.com/..." } }
```

### 9. 生成任务参数（含参考图）

```
POST /api/generation/create.php

{
  "prompt": "提示词",
  "model": "banana pro",
  "type": "image|video",
  "referenceImageUrls": ["https://xxx.oss-cn-xxx.aliyuncs.com/..."],  // 可选，多张参考图 OSS URL
  "firstFrameUrl": "https://...",  // 视频首帧 OSS URL
  "lastFrameUrl": "https://..."    // 视频尾帧 OSS URL
}
```

### 10. 发送验证码（手机号）

```
POST /api/auth/send_code.php
Content-Type: application/json

{
  "phone": "13800138000",
  "purpose": "login"   // 或 "register"，默认 login
}
```

### 11. 注册（手机号 + 验证码 + 密码）

```
POST /api/auth/register.php
Content-Type: application/json

{
  "phone": "13800138000",
  "code": "123456",
  "password": "12345678",
  "nickname": "可选昵称"
}
```

### 12. 登录（手机号 + 验证码 或 手机号 + 密码）

```
POST /api/auth/login.php
Content-Type: application/json

验证码登录（未注册自动创建）：
{
  "phone": "13800138000",
  "code": "123456"
}

密码登录（需已注册）：
{
  "phone": "13800138000",
  "password": "12345678"
}
```

### 13. 当前登录态

```
GET /api/auth/me.php
```

### 14. 退出登录

```
POST /api/auth/logout.php
```

## OSS 配置

详见 [OSS 配置说明](OSS_SETUP.md)

## AI 配置

详见 [AI 接口配置说明](AI_SETUP.md)

## 短信配置（手机号登录）

详见 [短信配置说明](SMS_SETUP.md)

快速配置：复制 `api/config/sms.example.php` 为 `api/config/sms.local.php`，填写：
- `access_key_id` / `access_key_secret`（阿里云 AccessKey）
- `sign_name`（短信签名）
- `template_code_login`（验证码模板 CODE）
- `enabled` 设为 `true`

调试期可设 `debug_return_code => true`，接口会在返回中带 `debugCode`，便于本地联调。

## 测试

使用 PHP 内置服务器：

```bash
php -S localhost:8000
```

访问示例：
- http://localhost:8000/api/models/index.php?type=image
- http://localhost:8000/api/templates/index.php?type=video
- http://localhost:8000/api/categories/index.php

### 15. 我的积分与定价配置

```
GET /api/points/me.php
```

### 16. 积分充值（开发版直充）

```
POST /api/points/recharge.php
Content-Type: application/json

{
  "packageId": "pkg_9_9|pkg_19_9|pkg_29_9"
}
```

### 17. 开通会员（开发版直开）

```
POST /api/points/subscribe.php
Content-Type: application/json

{
  "planId": "member_first_month|member_renew_month|member_single_month|member_year"
}
```

## 当前积分定价

- 单图消耗（2K）：banana=5 分，banana pro=10 分
- 单图消耗（4K）：在 2K 基础上 +80%（banana=9 分，banana pro=18 分）
- 单独购买积分包：
  - 9.9 元 = 165 分
  - 19.9 元 = 335 分
  - 29.9 元 = 505 分
- 会员：每天赠送 16 分，每天 12:00 重置，不叠加
  - 首月会员 29.9 元
  - 连续续费 39.9 元 / 月
  - 单月购买 49.9 元 / 月
  - 年会员 299 元 / 年
