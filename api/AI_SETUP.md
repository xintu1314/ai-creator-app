# AI 接口配置说明

支持 banana、banana pro 图片生成（无形科技 API），豆包视频待接入。

## 1. 无形科技配置

### 方式一：环境变量（推荐生产环境）

```bash
export WUYIN_API_KEY="你的无形科技密钥"
```

### 方式二：本地配置文件

复制 `api/config/ai.example.php` 为 `api/config/ai.local.php`，填写实际值：

```php
return [
    'wuyinkeji' => [
        'api_key' => '你的无形科技密钥',
        'base_url' => 'https://api.wuyinkeji.com',
        'endpoints' => [
            'banana' => '/api/async/image_nanoBanana',
            'banana_pro' => '/api/async/image_nanoBananaPro',
        ],
        'draw_detail' => '/api/img/drawDetail',
    ],
];
```

**注意**：`ai.local.php` 已在 .gitignore，不会被提交。

## 2. 参数对应关系

| 前端参数 | API 参数 | 说明 |
|----------|----------|------|
| 2k / 4k | imageSize: 2K / 4K | 图像质量 |
| 3:4, 16:9 等 | aspectRatio | 比例（9:21 映射为 9:16） |
| referenceImageUrls | urls | 参考图 URL 数组（最高 14 张） |

## 3. 已接入模型

| 模型 | 类型 | 状态 |
|------|------|------|
| banana | 图片 | ✅ 已接入 |
| banana pro | 图片 | ✅ 已接入 |
| 豆包视频 | 视频 | ⏳ 待接入 |

## 4. banana pro 接口说明

若 banana pro 接口路径与示例不同，请在 `ai.local.php` 中覆盖 `endpoints.banana_pro`。
