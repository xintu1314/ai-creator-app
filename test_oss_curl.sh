#!/bin/bash
# OSS 上传接口 curl 测试
# 用法: ./test_oss_curl.sh 或 bash test_oss_curl.sh
# 需要: 1. 运行 php start.php 2. 已执行 composer install

cd "$(dirname "$0")"

# 创建 1x1 测试图片
TEST_IMG="tmp_curl_test.png"
echo "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==" | base64 -d > "$TEST_IMG" 2>/dev/null || echo "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==" | base64 -D > "$TEST_IMG" 2>/dev/null

echo "=== 测试 OSS 上传接口 (http://localhost:8000) ==="
echo ""

RESP=$(curl -s -X POST -F "file=@$TEST_IMG" -F "prefix=assets/images/references" "http://localhost:8000/api/upload/image.php" 2>/dev/null)
rm -f "$TEST_IMG"

echo "响应: $RESP"
echo ""

if echo "$RESP" | grep -q '"success":true'; then
    echo "✅ 上传成功!"
    echo "$RESP" | grep -o '"url":"[^"]*"' | head -1
else
    echo "❌ 上传失败，请检查:"
    echo "  1. 是否已运行 php start.php"
    echo "  2. 是否已执行 composer install"
    echo "  3. 是否已配置 api/config/oss.local.php"
fi
