<?php
/**
 * 易支付同步回跳页面
 */
require_once __DIR__ . '/../common/payment.php';

$result = payment_handle_callback($_GET);
$ok = !empty($result['success']);
$msg = $ok ? '支付成功，正在返回首页...' : ('支付处理失败：' . ($result['message'] ?? '未知错误'));
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>支付结果</title>
    <style>
        body { font-family: -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,PingFang SC,Microsoft YaHei,sans-serif; background:#f7f8fa; margin:0; }
        .wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
        .card { width:100%; max-width:420px; background:#fff; border-radius:14px; padding:22px; box-shadow:0 8px 30px rgba(0,0,0,.06); }
        .title { font-size:18px; font-weight:600; margin:0 0 8px; color:#1f2937; }
        .desc { font-size:14px; color:#4b5563; margin:0 0 16px; line-height:1.7; }
        .btn { display:inline-block; padding:9px 14px; border-radius:9px; background:#2563eb; color:#fff; text-decoration:none; font-size:14px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1 class="title"><?php echo $ok ? '支付成功' : '支付未完成'; ?></h1>
        <p class="desc"><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></p>
        <a class="btn" href="/">返回首页</a>
    </div>
</div>
<script>
setTimeout(function () { window.location.href = '/'; }, 1800);
</script>
</body>
</html>
