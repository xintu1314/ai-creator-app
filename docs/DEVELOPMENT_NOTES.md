# AI 创作平台 — 开发过程笔记（详细版）

> 本文记录整个项目的开发历程，包括技术栈选型、开发步骤、**部署详细流程**、**数据库迁移**、**代码发布流程**、常见问题与解决方案，供后续复盘与复用。

---

## 一、项目概述

**AI 创作平台** 是一个支持图片/视频 AI 生成的 Web 应用，核心功能包括：

- 图片生成（banana、banana pro 模型）
- 视频生成（seedance1.5 模型，原豆包视频）
- 参考图、首帧/尾帧上传
- 模板发布与管理
- 用户系统（手机号登录、积分、会员）
- 支付（易支付）

---

## 二、技术栈总览

| 层级 | 技术 | 说明 |
|------|------|------|
| **前端** | PHP + Tailwind CSS + Vanilla JS | 主站采用 PHP 渲染，与 React 版本界面一致 |
| **前端备选** | React 19 + TypeScript + Vite | 保留在 `src/`，可 `npm run dev` 启动 |
| **UI 组件** | Radix UI、shadcn/ui、Lucide Icons | React 版本使用 |
| **后端** | PHP 7.4+ | RESTful API，无框架，按功能模块组织 |
| **数据库** | PostgreSQL | 用户、积分、订单、任务、模板等 |
| **对象存储** | 阿里云 OSS | 图片/视频上传、模板预览图、参考图、首尾帧 |
| **AI 接口** | 无形科技 API | banana、banana pro 图片生成 |
| **短信** | 阿里云短信 | 登录验证码 |
| **支付** | 易支付 | 积分充值、会员开通 |
| **服务器** | CentOS 7.9 + 宝塔面板 | Nginx / PHP-FPM / PostgreSQL 14 |

---

## 三、开发步骤与对应技术

### 第 1 步：项目初始化

- **技术**：Vite + React + TypeScript
- **产出**：`package.json`、`vite.config.ts`、`src/` 目录
- **说明**：从 React 模板起步，后续为降低部署成本增加 PHP 版本

### 第 2 步：从 React 到 PHP 的迁移

- **技术**：PHP 内置服务器、Tailwind CDN、Lucide Icons CDN
- **产出**：`index.php`、`includes/`（sidebar、header、creation_area、template_cards、dialogs）、`assets/css/`、`assets/js/main.js`
- **说明**：界面与 React 版本 100% 一致，无需构建，修改即生效

### 第 3 步：API 接口设计

- **技术**：PHP 原生、`$_GET`/`$_POST`/`file_get_contents('php://input')`
- **产出**：`api/` 目录，统一 `{ success, message, data }` 返回格式
- **接口**：模型列表、模板列表、分类、教程、资产、生成任务、发布模板、上传、认证等

### 第 4 步：数据库设计

- **技术**：PostgreSQL、`api/db/schema.sql`
- **表**：`users`、`user_wallets`、`points_ledger`、`payment_orders`、`user_memberships`、`assets`、`publish_templates`、`tasks`、`sms_verification_codes`、`tutorials`、`admin_audit_logs`

### 第 5 步：用户认证

- **技术**：Session、阿里云短信
- **接口**：`send_code`、`register`、`login`、`me`、`logout`
- **说明**：支持验证码登录与密码登录，用户需先注册

### 第 6 步：积分与会员

- **技术**：`user_wallets`、`points_ledger`、`user_memberships`
- **接口**：`points/me`、`points/recharge`、`points/subscribe`、`points/checkin`
- **定价**：2K/4K 消耗不同，会员每日赠送 16 分

### 第 7 步：支付接入

- **技术**：易支付、`payment_orders`、回调 `notify`、`return`
- **说明**：支持积分包、会员套餐，开发版可直充/直开

### 第 8 步：图片/视频上传（OSS）

- **技术**：阿里云 OSS PHP SDK、`api/upload/image.php`、`api/upload/video.php`
- **路径**：`assets/images/templates`、`assets/images/references`、`assets/images/frames`
- **说明**：参考图、首帧、尾帧均走 `upload/image.php`，上传后返回 OSS URL

### 第 9 步：AI 生成任务

- **技术**：无形科技 API、异步任务、`tasks` 表
- **接口**：`generation/create.php`、`generation/status.php`
- **参数**：`prompt`、`model`、`aspectRatio`、`quality`、`referenceImageUrls`、`firstFrameUrl`、`lastFrameUrl`

### 第 10 步：管理后台

- **技术**：PHP、`api/admin/`、`admin_audit_logs`
- **功能**：用户管理、模板审核、积分调整、教程管理、仪表盘

---

## 四、部署详细流程（可复用）

### 4.1 前置条件

- 阿里云 ECS（CentOS 7.9）
- 服务器 IP、root 密码（或密钥对）
- 宝塔面板（可选，本机使用宝塔）

### 4.2 第一步：SSH 免密登录

1. **若用密钥对**：将私钥放在 `~/.ssh/`，执行：
   ```bash
   chmod 400 ~/.ssh/你的密钥.pem
   ssh -i ~/.ssh/你的密钥.pem root@服务器IP
   ```

2. **若用密码**：阿里云控制台 → 实例 → 更多 → 密码/密钥 → 重置实例密码 → 重启后：
   ```bash
   ssh root@服务器IP
   ```

3. **配置免密**（便于后续推送）：在服务器执行：
   ```bash
   mkdir -p /root/.ssh && chmod 700 /root/.ssh
   # 将本机公钥 ~/.ssh/id_rsa.pub 内容追加到：
   cat >> /root/.ssh/authorized_keys <<'EOF'
   ssh-rsa AAAAB3...你的公钥内容...
   EOF
   chmod 600 /root/.ssh/authorized_keys
   ```

### 4.3 第二步：安装依赖（宝塔环境）

服务器上执行：

```bash
# 安装 PostgreSQL 扩展（PHP 7.4 需 pdo_pgsql、pgsql）
yum -y install postgresql-devel gcc make autoconf automake libtool
# 若宝塔 PHP 7.4 缺 pgsql，在 BT 面板 → 软件商店 → PHP 7.4 → 设置 → 安装扩展 → pgsql

# 或手动编译（需在 PHP 源码目录）
cd /www/server/php/74/src/ext/pdo_pgsql
/www/server/php/74/bin/phpize
./configure --with-php-config=/www/server/php/74/bin/php-config
make -j$(nproc) && make install
# 在 php.ini 添加：extension=pdo_pgsql.so 和 extension=pgsql.so
```

### 4.4 第三步：PostgreSQL 安装与配置

```bash
# 安装 PostgreSQL 14（若宝塔自带）
yum -y install https://download.postgresql.org/pub/repos/yum/reporpms/EL-7-x86_64/pgdg-redhat-repo-latest.noarch.rpm
yum -y install postgresql14-server postgresql14
/usr/pgsql-14/bin/postgresql-14-setup initdb
systemctl enable postgresql-14; systemctl start postgresql-14

# 配置 pg_hba.conf 允许 md5 密码（127.0.0.1）
sed -i 's/^host.*127\.0\.0\.1\/32.*ident/host    all             all             127.0.0.1\/32            md5/' /var/lib/pgsql/14/data/pg_hba.conf
systemctl restart postgresql-14

# 创建数据库和用户（密码自行替换）
DB_USER=believer; DB_NAME=ai_creator; DB_PASS='你的强密码'
sudo -u postgres /usr/pgsql-14/bin/psql -c "CREATE ROLE ${DB_USER} LOGIN PASSWORD '${DB_PASS}';"
sudo -u postgres /usr/pgsql-14/bin/createdb -O ${DB_USER} ${DB_NAME}
sudo -u postgres /usr/pgsql-14/bin/psql -c "GRANT ALL PRIVILEGES ON DATABASE ${DB_NAME} TO ${DB_USER};"

# PG14 默认 scram-sha-256，若 PHP 连不上可切到 md5
sudo -u postgres /usr/pgsql-14/bin/psql -c "ALTER SYSTEM SET password_encryption = 'md5';"
sudo -u postgres /usr/pgsql-14/bin/psql -c "SELECT pg_reload_conf();"
sudo -u postgres /usr/pgsql-14/bin/psql -c "ALTER ROLE ${DB_USER} WITH PASSWORD '${DB_PASS}';"
```

### 4.5 第四步：上传项目代码

**本机执行**（rsync 推荐）：

```bash
rsync -avz --delete \
  --exclude ".git" \
  --exclude "node_modules" \
  /Users/believer/Downloads/app/ \
  root@39.106.59.118:/www/wwwroot/ai-creator/
```

### 4.6 第五步：配置数据库连接

在服务器创建或编辑 `/www/wwwroot/ai-creator/api/config/database.php`：

```php
<?php
return [
    'host' => '127.0.0.1',
    'port' => 5432,
    'dbname' => 'ai_creator',
    'user' => 'believer',
    'password' => '你的数据库密码',
    'charset' => 'UTF8',
];
```

### 4.7 第六步：安装 Composer 依赖

```bash
cd /www/wwwroot/ai-creator
composer install --no-dev -o
```

### 4.8 第七步：初始化数据库

```bash
cd /www/wwwroot/ai-creator
php api/db/init.php
```

### 4.9 第八步：配置 Nginx

**方式 A：宝塔面板**

- 网站 → 添加站点 → 域名填 IP 或域名
- 根目录：`/www/wwwroot/ai-creator`
- PHP 版本：7.4
- 伪静态：`try_files $uri $uri/ /index.php?$query_string;`
- 客户端最大上传：256M

**方式 B：手动配置**

创建 `/www/server/panel/vhost/nginx/ai-creator-8081.conf`（或对应域名）：

```nginx
server {
    listen 8081;
    server_name 39.106.59.118;
    root /www/wwwroot/ai-creator;
    index index.php index.html;
    client_max_body_size 256m;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/tmp/php-cgi-74.sock;  # 或 127.0.0.1:9000
        fastcgi_read_timeout 300;
    }

    location ~ /\.(git|env|ht) { deny all; }
}
```

执行：`nginx -t && nginx -s reload`

### 4.10 第九步：配置敏感信息（Key 不上 Git）

在服务器创建以下文件（不提交到 Git）：

| 文件 | 用途 |
|------|------|
| `api/config/oss.local.php` | OSS 存储 |
| `api/config/ai.local.php` | AI 接口 |
| `api/config/sms.local.php` | 短信 |
| `api/config/payment.local.php` | 支付 |

复制示例文件并填写：

```bash
cp api/config/oss.example.php api/config/oss.local.php
cp api/config/ai.example.php api/config/ai.local.php
cp api/config/sms.example.php api/config/sms.local.php
cp api/config/payment.example.php api/config/payment.local.php
# 编辑后填入实际 Key
```

### 4.11 第十步：PHP 上传限制

开发时 `start.php` 已设置：

```php
php -d upload_max_filesize=256M -d post_max_size=256M -S localhost:8000
```

生产环境需在 `php.ini` 或宝塔 PHP 设置中修改：

- `upload_max_filesize = 256M`
- `post_max_size = 256M`

---

## 五、域名绑定

### 5.1 创建 Nginx 配置

在 `deploy/sx-xyh.cn.nginx.conf` 中：

```nginx
server {
    listen 80;
    server_name sx-xyh.cn www.sx-xyh.cn;
    client_max_body_size 256m;

    location / {
        proxy_pass http://127.0.0.1:8081;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    access_log /www/wwwlogs/sx-xyh.cn.access.log;
    error_log  /www/wwwlogs/sx-xyh.cn.error.log;
}
```

### 5.2 上传并生效

```bash
scp deploy/sx-xyh.cn.nginx.conf root@39.106.59.118:/www/server/panel/vhost/nginx/sx-xyh.cn.conf
ssh root@39.106.59.118 "nginx -t && nginx -s reload"
```

### 5.3 DNS 解析

在域名服务商添加 A 记录：

| 类型 | 主机记录 | 记录值 |
|------|----------|--------|
| A | @ | 39.106.59.118 |
| A | www | 39.106.59.118 |

---

## 六、代码发布流程（日常更新）

### 6.1 方式一：Git + rsync（推荐）

```bash
# 1. 本地提交
git add .
git commit -m "fix: 描述"
git push origin main

# 2. 同步到服务器（按需修改路径）
rsync -avz -e ssh \
  api/ includes/ assets/ index.php \
  root@39.106.59.118:/www/wwwroot/ai-creator/
```

### 6.2 方式二：仅同步修改文件

```bash
rsync -avz -e ssh \
  api/common/auth.php api/upload/image.php assets/js/main.js \
  root@39.106.59.118:/www/wwwroot/ai-creator/
```

### 6.3 方式三：服务器 git pull（若服务器是 git 仓库）

```bash
ssh root@39.106.59.118 "cd /www/wwwroot/ai-creator && git pull origin main"
```

**注意**：本机部署时通常用 rsync 直接传，服务器目录可能不是 git 仓库。

---

## 七、数据库迁移（本地 → 线上）

### 7.1 导出本地数据

```bash
# 按表导出（避免敏感 URL 参数时用 --column-inserts）
pg_dump -h 127.0.0.1 -p 5432 -U believer -d ai_creator \
  --data-only --column-inserts \
  --table=users --table=user_wallets --table=user_memberships \
  --table=points_ledger --table=assets --table=tasks \
  --table=payment_orders --table=publish_templates \
  --table=sms_verification_codes --table=tutorials --table=admin_audit_logs \
  > ai_creator.data.local.sql
```

### 7.2 脱敏处理（若含 Access Key）

- 删除 SQL 中的 `X-Tos-Credential`、`X-Tos-Signature` 等签名 URL 参数
- 或替换为 `REDACTED`，避免 GitHub Push Protection 拦截

### 7.3 上传并导入

```bash
scp ai_creator.data.local.sql root@39.106.59.118:/root/
ssh root@39.106.59.118 "source /root/ai-creator.secrets; /usr/pgsql-14/bin/psql postgresql://${DB_USER}:${DB_PASS}@127.0.0.1:5432/${DB_NAME} -f /root/ai_creator.data.local.sql"
```

### 7.4 仅迁移模板表

```bash
pg_dump -h 127.0.0.1 -p 5432 -U believer -d ai_creator \
  --data-only --column-inserts --table=publish_templates \
  > publish_templates.local.sql
scp publish_templates.local.sql root@39.106.59.118:/root/
# 服务器上先 TRUNCATE publish_templates CASCADE，再导入
```

---

## 八、常见问题与解决方案

### 8.1 参考图 / 首帧 / 尾帧上传失败

**现象**：线上环境上传失败，本地正常。

**排查**：
- 检查 `api/upload/image.php`、`api/common/oss.php`
- 确认 OSS 配置（`oss.local.php` 或环境变量）
- 检查 `upload_max_filesize`、`post_max_size`、`client_max_body_size`
- 确认 CORS、错误信息是否被吞掉
- 确认服务器执行过 `composer install --no-dev`（OSS SDK 需 vendor）

**解决**：统一错误返回格式，增加日志，确保 OSS 凭证与 Bucket 权限正确。

### 8.2 登录状态一直掉（Session 401）

**现象**：登录后接口仍返回 401。

**原因**：`session.save_path` 不可写或权限问题。

**解决**：`api/common/auth.php` 中已实现 fallback：当默认 session 目录不可写时，自动切换到 `sys_get_temp_dir()/php_sessions`。

### 8.3 普通用户能看到后台

**原因**：后台权限判断依赖 `users.role = 'admin'`，需正确设置。

**解决**：使用 `deploy/set_admin_by_phone.php` 设置管理员：

```bash
php deploy/set_admin_by_phone.php 13485353864
```

### 8.4 视频模板预览是占位图

**原因**：未上传完视频就发布，或封面未更新。

**解决**：视频未上传完前禁用发布按钮；或上传完成后自动用视频首帧作为封面。

### 8.5 资产无法点击查看/下载

**解决**：在 `includes/assets.php` 中为资产项添加「查看」弹窗预览，避免被当作下载。

### 8.6 首帧、尾帧图片框样式与拖拽

**需求**：首帧/尾帧改为方形，参考图、首帧、尾帧支持拖拽上传。

**技术**：
- 样式：`aspect-square`、`overflow-hidden`，确保 `w`、`h` 一致
- 拖拽：`dragover`、`dragenter`、`dragleave`、`drop` 事件，`e.dataTransfer.files`
- 兼容：`handleFrameUpload` 支持 `(input, previewId, frameType)` 或 `(file, previewId, frameType)`

### 8.7 PHP 未安装

**解决**：参考 `INSTALL_PHP.md`，macOS 推荐 `brew install php`，或使用 MAMP/XAMPP。临时可用 React 版本 `npm run dev`。

### 8.8 短信验证码调试

**解决**：`sms.local.php` 中设置 `debug_return_code => true`，接口返回 `debugCode`，前端显示，便于本地联调。生产务必设为 `false`。

### 8.9 OSS 配置

**解决**：复制 `oss.example.php` 为 `oss.local.php`，填写 `access_key_id`、`access_key_secret`、`bucket`、`region`、`endpoint`。详见 `api/OSS_SETUP.md`。

### 8.10 AI 接口配置

**解决**：复制 `ai.example.php` 为 `ai.local.php`，填写无形科技 `api_key`。详见 `api/AI_SETUP.md`。

### 8.11 GitHub Push 被拦截（含 Key）

**现象**：SQL dump 或代码中含 `AKLT...` 等 Access Key，GitHub 拒绝 push。

**解决**：
- 方案 A：脱敏后提交（删除 URL 中 `X-Tos-Credential`、`X-Tos-Signature` 等参数）
- 方案 B：敏感文件不进 git，用 `.gitignore` 排除，部署时单独传服务器

---

## 九、Key 与敏感信息管理

**原则**：Key 永远不进 Git。

**方式**：
- 服务器环境变量：`getenv('OSS_ACCESS_KEY_ID')` 等
- 服务器本地配置：`api/config/*.local.php`（已在 `.gitignore`）

**部署时**：`git pull` 或 `rsync` 后，`*.local.php` 在服务器上单独创建/保留，无需从 git 传。

---

## 十、启动方式

```bash
# PHP 版本（主站）
php start.php
# 或
php -S localhost:8000

# React 版本（备选）
npm run dev
```

---

## 十一、总结

项目采用「PHP 主站 + React 备选」的混合架构，后端以 PHP 原生实现，数据库用 PostgreSQL，存储用阿里云 OSS，AI 用无形科技，支付用易支付。部署时使用宝塔 + Nginx + PHP-FPM + PostgreSQL 14，代码通过 rsync 或 git pull 同步到服务器，敏感配置通过 `*.local.php` 或环境变量注入。开发过程中多次遇到上传、Session、OSS、数据库迁移等问题，通过统一错误返回、Session fallback、脱敏提交等方式逐步解决。建议将开发笔记持续更新，便于团队协作与知识沉淀。

---

*记录于 2026 年 2 月*
