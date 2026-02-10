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

### 7. 发布模板（占位）

```
POST /api/publish/create.php
Content-Type: application/json

{
  "contentType": "image|video",
  "modelId": "banana-pro",
  "category": "室内",
  "title": "模板标题",
  "content": "模板内容"
}
```

## 测试

使用 PHP 内置服务器：

```bash
php -S localhost:8000
```

访问示例：
- http://localhost:8000/api/models/index.php?type=image
- http://localhost:8000/api/templates/index.php?type=video
- http://localhost:8000/api/categories/index.php
