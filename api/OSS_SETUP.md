# 阿里云 OSS 配置说明

图片、视频等文件存储至阿里云 OSS，模板预览图、参考图、生成结果等均使用 OSS 链接。

参考文档：https://help.aliyun.com/zh/oss/developer-reference/simple-upload-using-oss-sdk-for-php-v2

## 1. 安装依赖

```bash
composer install
```

## 2. 配置 OSS

### 方式一：环境变量（推荐生产环境）

```bash
export OSS_ACCESS_KEY_ID="your-access-key-id"
export OSS_ACCESS_KEY_SECRET="your-access-key-secret"
export OSS_BUCKET="your-bucket-name"
export OSS_REGION="cn-hangzhou"
export OSS_ENDPOINT="oss-cn-hangzhou.aliyuncs.com"
# 可选：自定义域名（CDN）
export OSS_CUSTOM_DOMAIN="https://cdn.example.com"
```

### 方式二：本地配置文件

复制 `api/config/oss.example.php` 为 `api/config/oss.local.php`，填写实际值：

```php
return [
    'access_key_id'     => 'your-access-key-id',
    'access_key_secret' => 'your-access-key-secret',
    'region'            => 'cn-hangzhou',
    'endpoint'          => 'oss-cn-hangzhou.aliyuncs.com',
    'bucket'            => 'your-bucket-name',
    'custom_domain'     => '',  // 可选
];
```

**注意**：`oss.local.php` 已在 .gitignore，不会被提交。

## 3. 阿里云控制台准备

1. 创建 Bucket，选择地域（如华东1-杭州）
2. 设置 Bucket 访问权限为「公共读」或配置读写策略
3. 在 RAM 控制台创建 AccessKey，授予 `oss:PutObject` 等权限

## 4. 地域与 Endpoint 对照

| 地域       | 地域ID      | 外网 Endpoint                |
|------------|-------------|------------------------------|
| 华东1(杭州) | cn-hangzhou | oss-cn-hangzhou.aliyuncs.com  |
| 华东2(上海) | cn-shanghai | oss-cn-shanghai.aliyuncs.com |
| 华北2(北京) | cn-beijing  | oss-cn-beijing.aliyuncs.com  |
| 华南1(深圳) | cn-shenzhen | oss-cn-shenzhen.aliyuncs.com |

完整列表见：https://help.aliyun.com/zh/oss/user-guide/regions-and-endpoints

## 5. 使用场景

| 场景           | 存储路径                        | 说明                    |
|----------------|---------------------------------|-------------------------|
| 发布模板预览图 | assets/images/templates/yyyy/mm/dd/ | 模板的半屏展示图        |
| 图片参考图     | assets/images/references/       | 生成时的多张参考图      |
| 视频首帧/尾帧  | assets/images/frames/           | 视频生成的首尾帧图片    |
