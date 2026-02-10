# 调试指南

## 如果页面"动不了"，请检查以下几点：

### 1. 打开浏览器开发者工具
- 按 `F12` 或 `Cmd+Option+I` (Mac)
- 查看 Console 标签页，看是否有JavaScript错误

### 2. 检查JavaScript是否加载
在Console中输入：
```javascript
typeof changeTab
```
如果返回 `"function"`，说明JavaScript已加载。

### 3. 检查图标是否显示
- 如果图标显示为空白或方框，说明Lucide图标库未加载
- 检查网络标签页，看是否有资源加载失败

### 4. 测试对话框功能
在Console中输入：
```javascript
openModelDialog()
```
如果对话框出现，说明功能正常。

### 5. 常见问题

#### 问题1：点击按钮没反应
**原因**：JavaScript未加载或函数未定义
**解决**：刷新页面，检查 `assets/js/main.js` 是否正确加载

#### 问题2：图标不显示
**原因**：Lucide图标库未加载
**解决**：检查网络连接，确保能访问 `unpkg.com`

#### 问题3：对话框打不开
**原因**：CSS样式冲突或JavaScript错误
**解决**：我已经修复了这个问题，请刷新页面

#### 问题4：页面样式错乱
**原因**：Tailwind CSS未加载
**解决**：检查网络连接，确保能访问 `cdn.tailwindcss.com`

### 6. 快速修复

如果所有功能都不工作，尝试：

1. **硬刷新页面**：`Cmd+Shift+R` (Mac) 或 `Ctrl+Shift+R` (Windows)
2. **清除浏览器缓存**
3. **检查网络连接**：确保能访问CDN资源
4. **查看服务器日志**：检查PHP服务器是否有错误

### 7. 测试页面

访问 `http://localhost:8000/test.html` 来测试JavaScript是否正常工作。
