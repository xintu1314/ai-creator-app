# PHP安装指南

## 检测到系统未安装PHP

要运行PHP版本的项目，需要先安装PHP。

## 安装方法（macOS）

### 方法1：使用Homebrew（推荐）

```bash
# 安装Homebrew（如果还没有）
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# 安装PHP
brew install php

# 验证安装
php -v
```

### 方法2：使用MAMP（图形界面）

1. 下载MAMP：https://www.mamp.info/
2. 安装并启动MAMP
3. 将项目目录配置为MAMP的文档根目录

### 方法3：使用XAMPP

1. 下载XAMPP：https://www.apachefriends.org/
2. 安装XAMPP
3. 将项目文件复制到XAMPP的htdocs目录

## 安装后启动项目

```bash
cd /Users/believer/Downloads/app
php -S localhost:8000
```

然后访问：http://localhost:8000

## 临时方案：使用React版本

如果暂时无法安装PHP，可以继续使用React版本：

```bash
npm run dev
```

访问：http://localhost:5173
