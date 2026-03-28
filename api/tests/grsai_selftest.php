<?php
/**
 * 本地自检：php api/tests/grsai_selftest.php
 * 不输出密钥；可选第二参数 network 时探测上游（需网络）。
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/common/grsai.php';

$fail = 0;
$ok = static function (string $name, bool $pass) use (&$fail): void {
    if ($pass) {
        echo "[OK] {$name}\n";
    } else {
        echo "[FAIL] {$name}\n";
        $fail++;
    }
};

// --- 纯逻辑 ---
$ok('extract data.id', grsai_extract_task_id_from_chat_response([
    'code' => 0,
    'data' => ['id' => 'draw_task_1'],
]) === 'draw_task_1');

$ok('skip root chatcmpl, use data.id', grsai_extract_task_id_from_chat_response([
    'id' => 'chatcmpl-abc',
    'data' => ['id' => 'real_task'],
]) === 'real_task');

$ok('markdown url', grsai_extract_url_from_assistant_text('![x](https://cdn.example.com/a.png)') === 'https://cdn.example.com/a.png');

$ok('map_image_size 4k', grsai_map_image_size('4k') === '4K');

$ok('extract_submit_task_id prefers data', grsai_extract_submit_task_id([
    'code' => 0,
    'data' => ['id' => 't2'],
]) === 't2');

$cfg = grsai_config();
$m = (string)($cfg['nanobanana2_model'] ?? '');
$ok('nanobanana2_model 含 nano-banana', $m !== '' && strpos($m, 'nano-banana') !== false);

$src = file_get_contents($root . '/common/grsai.php');
$p1 = strpos($src, "['id' => \$taskId]");
$p2 = strpos($src, "['task_id' => \$taskId]");
$ok('draw/result 请求体 id 优先于 task_id', $p1 !== false && $p2 !== false && $p1 < $p2);

echo $fail === 0 ? "\n单元断言全部通过。\n" : "\n单元断言失败数: {$fail}\n";

// --- 可选网络探测 ---
if (($argv[1] ?? '') !== 'network') {
    exit($fail > 0 ? 1 : 0);
}

echo "\n--- 网络探测（不打印 key）---\n";
$all = require $root . '/config/ai.php';
$key = trim((string)($all['grsai']['api_key'] ?? ''));
if ($key === '') {
    $key = trim((string)($all['openai_hk']['api_key'] ?? ''));
}
if ($key === '') {
    echo "[SKIP] 未配置 api_key\n";
    exit($fail > 0 ? 1 : 0);
}

$base = rtrim((string)($all['grsai']['base_url'] ?? 'https://grsai.dakka.com.cn'), '/');
$model = (string)($all['grsai']['nanobanana2_model'] ?? 'nano-banana-2');

$chatUrl = $base . '/v1/chat/completions';
$chatBody = json_encode([
    'model' => $model,
    'messages' => [['role' => 'user', 'content' => '自检：只回复 OK 两个字母']],
    'stream' => false,
], JSON_UNESCAPED_UNICODE);

$ch = curl_init($chatUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $chatBody,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $key,
        'Content-Type: application/json',
    ],
    CURLOPT_TIMEOUT => 45,
    CURLOPT_CONNECTTIMEOUT => 10,
]);
$raw = curl_exec($ch);
$err = curl_error($ch);
$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo '[chat/completions] HTTP ' . $code . ($err ? ' curl_err=' . $err : '') . "\n";
if ($raw !== false && $raw !== '') {
    $j = json_decode((string)$raw, true);
    if (is_array($j)) {
        if (isset($j['error'])) {
            echo '[chat/completions] error: ' . json_encode($j['error'], JSON_UNESCAPED_UNICODE) . "\n";
        } else {
            $snippet = substr(preg_replace('/\s+/', ' ', (string)$raw), 0, 280);
            echo '[chat/completions] body_snippet: ' . $snippet . "...\n";
        }
    } else {
        echo "[chat/completions] 非 JSON，长度 " . strlen((string)$raw) . "\n";
    }
}

$retChat = grsai_submit_via_chat_completions('自检简笔画一只猫', [], $all['grsai'], $key, $base);
echo '[grsai_submit_via_chat_completions] success=' . ($retChat['success'] ? '1' : '0') . ' msg=' . ($retChat['message'] ?? '') . "\n";

$retFull = grsai_submit_nanobanana2('自检简笔画一只猫', ['aspectRatio' => '1:1', 'quality' => '2k']);
echo '[grsai_submit_nanobanana2] success=' . ($retFull['success'] ? '1' : '0') . ' msg=' . substr((string)($retFull['message'] ?? ''), 0, 120) . "\n";
if (!empty($retFull['task_id'])) {
    echo '[grsai_submit_nanobanana2] task_id_len=' . strlen((string)$retFull['task_id']) . "\n";
    $st = grsai_query_draw_status((string)$retFull['task_id']);
    echo '[grsai_query_draw_status] success=' . ($st['success'] ? '1' : '0') . ' phase=' . ($st['phase'] ?? '') . ' msg=' . substr((string)($st['message'] ?? ''), 0, 80) . "\n";
}

exit($fail > 0 ? 1 : 0);
