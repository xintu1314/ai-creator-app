# 短信配置说明（手机号登录）

本项目使用**阿里云短信服务**发送登录验证码。未注册手机号验证通过后可自动创建账号。

## 1. 配置方式

### 方式一：本地配置文件（推荐）

复制 `api/config/sms.example.php` 为 `api/config/sms.local.php`，填写实际值：

```php
<?php
return [
    'aliyun' => [
        'enabled' => true,
        'debug_return_code' => false,   // 调试时设为 true，接口会返回验证码便于联调
        'access_key_id' => 'LTAIxxxxxxxxxxxx',
        'access_key_secret' => 'xxxxxxxxxxxxxxxxxxxxxxxx',
        'region' => 'cn-hangzhou',
        'endpoint' => 'dysmsapi.aliyuncs.com',
        'sign_name' => '你的短信签名',
        'template_code_login' => 'SMS_123456789',
        'code_ttl_seconds' => 300,      // 验证码有效期（秒）
        'send_cooldown_seconds' => 60,  // 发送间隔（秒）
        'daily_limit_per_phone' => 20,  // 每手机号每日发送上限
    ],
];
```

**注意**：`sms.local.php` 已在 .gitignore，不会被提交到版本库。

### 方式二：环境变量（生产环境）

```bash
export SMS_ENABLED=1
export ALIYUN_SMS_ACCESS_KEY_ID="your-access-key-id"
export ALIYUN_SMS_ACCESS_KEY_SECRET="your-access-key-secret"
export ALIYUN_SMS_REGION="cn-hangzhou"
export ALIYUN_SMS_SIGN_NAME="你的短信签名"
export ALIYUN_SMS_TEMPLATE_CODE_LOGIN="SMS_123456789"
# 可选
export ALIYUN_SMS_CODE_TTL_SECONDS=300
export ALIYUN_SMS_SEND_COOLDOWN_SECONDS=60
export ALIYUN_SMS_DAILY_LIMIT_PER_PHONE=20
# 调试模式：接口返回验证码（不真实发送）
export SMS_DEBUG_RETURN_CODE=1
```

## 2. 阿里云控制台准备

### 2.1 开通短信服务

1. 登录 [阿里云控制台](https://console.aliyun.com/)
2. 开通「短信服务」
3. 在 RAM 控制台创建 AccessKey，授予短信发送权限

### 2.2 短信签名

1. 进入「短信服务」→「国内消息」→「签名管理」
2. 添加签名（如：你的应用名、公司名）
3. 审核通过后获得 `sign_name`

### 2.3 短信模板

1. 进入「短信服务」→「国内消息」→「模板管理」
2. 添加「验证码」类型模板
3. 模板内容示例：`您的验证码是：${code}，5分钟内有效。`
4. 变量名必须为 `code`（与代码中 `templateParams` 一致）
5. 审核通过后获得 `template_code_login`（形如 `SMS_123456789`）

## 3. 配置项说明

| 配置项 | 说明 | 示例 |
|--------|------|------|
| `enabled` | 是否启用短信 | `true` |
| `access_key_id` | 阿里云 AccessKey ID | `LTAI5t...` |
| `access_key_secret` | 阿里云 AccessKey Secret | `xxxxxxxx` |
| `region` | 地域 | `cn-hangzhou` |
| `sign_name` | 短信签名 | `我的应用` |
| `template_code_login` | 登录验证码模板 CODE | `SMS_123456789` |
| `code_ttl_seconds` | 验证码有效期（秒） | `300` |
| `send_cooldown_seconds` | 同一手机号发送间隔（秒） | `60` |
| `daily_limit_per_phone` | 每手机号每日上限 | `20` |
| `debug_return_code` | 调试模式：不真实发送，接口返回验证码 | `false` |

## 4. 调试模式

本地开发时若未配置真实短信通道，可设置 `debug_return_code => true`：

- 接口不会真正调用阿里云发送短信
- 返回中会带 `debugCode`，前端会显示该验证码
- 用该验证码即可完成登录/注册

**生产环境务必设为 `false`。**

## 5. 测试

配置完成后：

1. 打开登录弹窗
2. 输入手机号（如 `13800138000`）
3. 点击「获取验证码」
4. 若为调试模式，页面会显示调试验证码；否则手机会收到短信
5. 输入验证码，点击「登录/注册」
