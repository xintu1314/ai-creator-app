<?php
/**
 * GrsAI — nano-banana-2
 * - 官方：POST /v1/chat/completions（model: nano-banana-2，可与 draw 并存）
 * - 绘画：POST /v1/draw/nano-banana
 * - 查结果：POST /v1/draw/result，body 为 {"id":"任务id"}（Authorization: Bearer）
 * 国内：https://grsai.dakka.com.cn  海外：https://grsaiapi.com
 */

function grsai_config(): array {
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }
    $all = require __DIR__ . '/../config/ai.php';
    $cfg = is_array($all['grsai'] ?? null) ? $all['grsai'] : [];
    return $cfg;
}

/** 优先 grsai.api_key，否则兼容旧版写在 openai_hk 里的同一枚 sk */
function grsai_resolve_api_key(): string {
    $all = require __DIR__ . '/../config/ai.php';
    $g = trim((string)($all['grsai']['api_key'] ?? ''));
    if ($g !== '') {
        return $g;
    }
    return trim((string)($all['openai_hk']['api_key'] ?? ''));
}

function grsai_log(string $event, array $context = []): void {
    $dir = __DIR__ . '/../logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $line = ['ts' => date('c'), 'event' => $event, 'context' => $context];
    @file_put_contents(
        $dir . '/grsai-' . date('Ymd') . '.log',
        json_encode($line, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
        FILE_APPEND
    );
}

function grsai_join_url(string $base, string $path): string {
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function grsai_http_post_json(string $url, string $apiKey, array $payload, int $timeout = 300): array {
    $startedAt = microtime(true);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 15,
    ]);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    @curl_close($ch);
    $elapsedMs = (int)round((microtime(true) - $startedAt) * 1000);

    if ($err !== '') {
        return ['ok' => false, 'message' => '请求失败：' . $err, 'http_code' => $httpCode, 'raw' => (string)$raw, 'elapsed_ms' => $elapsedMs];
    }

    $ok = $httpCode >= 200 && $httpCode < 300;
    $msg = '';
    if (!$ok) {
        $msg = 'HTTP ' . $httpCode;
        $try = json_decode((string)$raw, true);
        if (is_array($try)) {
            if (!empty($try['message']) && is_string($try['message'])) {
                $msg = $try['message'];
            } elseif (!empty($try['error'])) {
                $msg = is_string($try['error']) ? $try['error'] : json_encode($try['error'], JSON_UNESCAPED_UNICODE);
            }
        }
    }

    return [
        'ok' => $ok,
        'message' => $msg,
        'http_code' => $httpCode,
        'content_type' => $contentType,
        'raw' => (string)$raw,
        'elapsed_ms' => $elapsedMs,
    ];
}

function grsai_find_result_url(array $data): ?string {
    $walk = static function ($node) use (&$walk) {
        if (!is_array($node)) {
            return null;
        }
        $st = $node['status'] ?? '';
        if (in_array($st, ['succeeded', 'success', 'completed'], true)) {
            foreach (['results', 'result', 'outputs', 'output', 'data'] as $rk) {
                $block = $node[$rk] ?? null;
                if (is_array($block)) {
                    $first = isset($block[0]) ? $block[0] : $block;
                    if (is_array($first) && !empty($first['url'])) {
                        return (string)$first['url'];
                    }
                    if (is_string($first) && preg_match('#^https?://#', $first)) {
                        return $first;
                    }
                }
            }
            if (!empty($node['url']) && is_string($node['url']) && preg_match('#^https?://#', $node['url'])) {
                return (string)$node['url'];
            }
        }
        foreach ($node as $v) {
            if (is_array($v)) {
                $u = $walk($v);
                if ($u) {
                    return $u;
                }
            }
        }
        return null;
    };
    return $walk($data);
}

function grsai_find_task_id(array $data): ?string {
    $walk = static function ($node) use (&$walk) {
        if (!is_array($node)) {
            return null;
        }
        foreach (['id', 'task_id', 'taskId'] as $k) {
            if (!empty($node[$k]) && is_string($node[$k])) {
                return $node[$k];
            }
        }
        foreach ($node as $v) {
            if (is_array($v)) {
                $id = $walk($v);
                if ($id) {
                    return $id;
                }
            }
        }
        return null;
    };
    return $walk($data);
}

/** 提交/回调常见结构：优先 data.id，避免误取嵌套里的其它 id */
function grsai_extract_submit_task_id(array $data): ?string {
    if (!empty($data['data']) && is_array($data['data'])) {
        $d = $data['data'];
        foreach (['id', 'task_id', 'taskId'] as $k) {
            if (!empty($d[$k]) && is_string($d[$k])) {
                return $d[$k];
            }
        }
    }
    if (!empty($data['task']) && is_array($data['task'])) {
        $t = $data['task'];
        foreach (['id', 'task_id', 'taskId'] as $k) {
            if (!empty($t[$k]) && is_string($t[$k])) {
                return $t[$k];
            }
        }
    }
    $fallback = grsai_find_task_id($data);
    return $fallback !== null && $fallback !== '' ? $fallback : null;
}

function grsai_map_image_size(string $quality): string {
    $q = strtolower(trim($quality));
    if ($q === '4k') {
        return '4K';
    }
    if ($q === '1k') {
        return '1K';
    }
    return '2K';
}

/** 从助手文本里解析首张图片 HTTPS 链接（Markdown 或裸链） */
function grsai_extract_url_from_assistant_text(string $content): ?string {
    $content = trim($content);
    if ($content === '') {
        return null;
    }
    if (preg_match('/!\[[^\]]*\]\((https:\/\/[^)\s]+)\)/', $content, $m)) {
        return rtrim($m[1], '.,;)]\'"');
    }
    if (preg_match('#https://[^\s\)"\'<>]+#u', $content, $m)) {
        return rtrim($m[0], '.,;)]\'"');
    }
    return null;
}

/**
 * 从 chat/completions 响应取任务 id（排除 OpenAI 的 chatcmpl-* completion id）。
 */
function grsai_extract_task_id_from_chat_response(array $data): ?string {
    if (!empty($data['data']) && is_array($data['data'])) {
        $d = $data['data'];
        foreach (['id', 'task_id', 'taskId'] as $k) {
            if (!empty($d[$k]) && is_string($d[$k])) {
                $id = $d[$k];
                if (!preg_match('/^chatcmpl-/i', $id)) {
                    return $id;
                }
            }
        }
    }
    foreach (['task_id', 'taskId'] as $k) {
        if (!empty($data[$k]) && is_string($data[$k])) {
            return $data[$k];
        }
    }
    if (!empty($data['id']) && is_string($data['id'])) {
        $id = $data['id'];
        if (!preg_match('/^chatcmpl-/i', $id)) {
            return $id;
        }
    }
    return null;
}

function grsai_chat_get_choice_content(array $data): ?string {
    $ch = $data['choices'][0] ?? null;
    if ($ch === null && isset($data['data']['choices'][0])) {
        $ch = $data['data']['choices'][0];
    }
    if (!is_array($ch)) {
        return null;
    }
    $msg = $ch['message'] ?? [];
    if (!is_array($msg)) {
        return null;
    }
    $c = $msg['content'] ?? '';
    return is_string($c) ? $c : null;
}

/**
 * 官方文档：POST /v1/chat/completions（model 为 nano-banana-2，stream:false 便于解析）
 *
 * @return array{success:bool, message?:string, task_id?:string, image_url?:string, raw?:string}
 */
function grsai_submit_via_chat_completions(string $prompt, array $options, array $cfg, string $apiKey, string $base): array {
    $path = (string)($cfg['chat_path'] ?? '/v1/chat/completions');
    $url = grsai_join_url($base, $path);
    $model = (string)($cfg['nanobanana2_model'] ?? 'nano-banana-2');
    $body = [
        'model' => $model,
        'messages' => [
            ['role' => 'user', 'content' => $prompt],
        ],
        'stream' => false,
    ];
    $timeout = (int)($cfg['chat_timeout'] ?? 120);
    if ($timeout < 30) {
        $timeout = 120;
    }

    grsai_log('chat.submit', ['url' => $url, 'model' => $model]);

    $res = grsai_http_post_json($url, $apiKey, $body, $timeout);
    $raw = $res['raw'] ?? '';

    if (!$res['ok']) {
        $em = (string)($res['message'] ?? '');
        if ($em === '') {
            $em = '请求失败 HTTP ' . (int)($res['http_code'] ?? 0);
        }
        return ['success' => false, 'message' => $em, 'raw' => $raw];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = grsai_parse_sse_last_json($raw);
    }
    if (!is_array($data)) {
        grsai_log('chat.bad_response', ['preview' => substr($raw, 0, 400)]);
        return ['success' => false, 'message' => 'chat 接口返回格式异常', 'raw' => $raw];
    }

    if (!empty($data['error'])) {
        $em = is_array($data['error'])
            ? ($data['error']['message'] ?? json_encode($data['error'], JSON_UNESCAPED_UNICODE))
            : (string)$data['error'];
        return ['success' => false, 'message' => 'chat 接口错误：' . $em, 'raw' => $raw];
    }

    if (array_key_exists('code', $data) && (int)$data['code'] !== 0 && (int)$data['code'] !== 200) {
        $em = (string)($data['msg'] ?? $data['message'] ?? '接口返回错误');
        return ['success' => false, 'message' => $em, 'raw' => $raw];
    }

    $content = grsai_chat_get_choice_content($data);
    if ($content !== null && $content !== '') {
        $urlImg = grsai_extract_url_from_assistant_text($content);
        if ($urlImg !== null && $urlImg !== '') {
            grsai_log('chat.direct_image', []);
            return ['success' => true, 'image_url' => $urlImg, 'raw' => $raw];
        }
    }

    $tid = grsai_extract_task_id_from_chat_response($data);
    if ($tid !== null && $tid !== '') {
        grsai_log('chat.task_id', ['task_id' => $tid]);
        return ['success' => true, 'task_id' => $tid, 'raw' => $raw];
    }

    return ['success' => false, 'message' => 'chat 响应未包含任务 ID 或图片链接', 'raw' => $raw];
}

function grsai_find_failed_message(array $data): ?string {
    $walk = static function ($node) use (&$walk) {
        if (!is_array($node)) {
            return null;
        }
        if (($node['status'] ?? '') === 'failed') {
            $r = $node['failure_reason'] ?? '';
            $e = $node['error'] ?? '';
            if (is_string($r) && $r !== '') {
                return $r;
            }
            if (is_string($e) && $e !== '') {
                return $e;
            }
            return '生成失败';
        }
        foreach ($node as $v) {
            if (is_array($v)) {
                $m = $walk($v);
                if ($m !== null) {
                    return $m;
                }
            }
        }
        return null;
    };
    return $walk($data);
}

/**
 * 解析流式 SSE：取最后一个有效 data JSON。
 */
function grsai_parse_sse_last_json(string $raw): ?array {
    $last = null;
    $lines = preg_split("/\r\n|\n|\r/", $raw) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, 'data:') !== 0) {
            continue;
        }
        $json = trim(substr($line, 5));
        if ($json === '' || strcasecmp($json, '[DONE]') === 0) {
            continue;
        }
        $d = json_decode($json, true);
        if (is_array($d)) {
            $last = $d;
        }
    }
    return $last;
}

/**
 * 单次查询任务结果（供 status.php 轮询调用，不在此 sleep）。
 *
 * @return array{success:bool, phase:string, image_url?:string, message?:string}
 * phase: completed | failed | running
 */
function grsai_query_draw_status(string $taskId): array {
    $taskId = trim($taskId);
    if ($taskId === '') {
        return ['success' => false, 'phase' => 'failed', 'message' => '缺少任务 ID'];
    }
    if (function_exists('set_time_limit')) {
        @set_time_limit(20);
    }
    $cfg = grsai_config();
    $apiKey = grsai_resolve_api_key();
    if ($apiKey === '') {
        return ['success' => false, 'phase' => 'failed', 'message' => '未配置 GrsAI API Key'];
    }
    $base = rtrim((string)($cfg['base_url'] ?? 'https://grsai.dakka.com.cn'), '/');
    $path = (string)($cfg['result_endpoint'] ?? '/v1/draw/result');
    $url = $base . $path;
    // 官方文档：请求体为 { "id": "xxxxx" }，优先使用 id
    $payloadVariants = [
        ['id' => $taskId],
        ['task_id' => $taskId],
        ['taskId' => $taskId],
    ];
    $lastErr = '查询失败';
    foreach ($payloadVariants as $payload) {
        // status 轮询必须短超时，否则会把本地 PHP 开发服务器拖到 Failed to fetch
        $res = grsai_http_post_json($url, $apiKey, $payload, 10);
        if (!$res['ok']) {
            if (($res['message'] ?? '') !== '') {
                $lastErr = (string)$res['message'];
            } elseif (!empty($res['http_code'])) {
                $lastErr = 'HTTP ' . (int)$res['http_code'];
            }
            continue;
        }
        $raw = $res['raw'];
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            continue;
        }
        if (isset($data['code']) && (int)$data['code'] === -22) {
            return ['success' => true, 'phase' => 'failed', 'message' => '任务不存在或已过期'];
        }
        $fail = grsai_find_failed_message($data);
        if ($fail !== null) {
            return ['success' => true, 'phase' => 'failed', 'message' => $fail];
        }
        $imageUrl = grsai_find_result_url($data);
        if ($imageUrl !== null && $imageUrl !== '') {
            return ['success' => true, 'phase' => 'completed', 'image_url' => $imageUrl];
        }
        return ['success' => true, 'phase' => 'running'];
    }
    return ['success' => false, 'phase' => 'failed', 'message' => $lastErr];
}

/**
 * 仅提交 nanobanana2 任务（快速返回），由前端轮询 status.php 再查 GrsAI 结果。
 *
 * @return array{success:bool, message?:string, task_id?:string, image_url?:string, raw?:string}
 */
function grsai_submit_nanobanana2(string $prompt, array $options = []): array {
    $cfg = grsai_config();
    $apiKey = grsai_resolve_api_key();
    if ($apiKey === '') {
        return ['success' => false, 'message' => '未配置 GrsAI：请在 api/config/ai.local.php 中填写 grsai.api_key（或沿用 openai_hk.api_key），或设置环境变量 GRSAI_API_KEY'];
    }

    $base = rtrim((string)($cfg['base_url'] ?? 'https://grsai.dakka.com.cn'), '/');
    $mode = (string)($cfg['submit_mode'] ?? 'chat_then_draw');

    if ($mode === 'chat' || $mode === 'chat_then_draw') {
        $chatRet = grsai_submit_via_chat_completions($prompt, $options, $cfg, $apiKey, $base);
        if ($chatRet['success']) {
            return $chatRet;
        }
        grsai_log('chat.fallback_to_draw', ['message' => $chatRet['message'] ?? '']);
        if ($mode === 'chat') {
            return $chatRet;
        }
    }

    $drawPath = (string)($cfg['draw_endpoint'] ?? '/v1/draw/nano-banana');
    $model = (string)($cfg['nanobanana2_model'] ?? 'nano-banana-2');
    $url = grsai_join_url($base, $drawPath);

    $aspect = (string)($options['aspectRatio'] ?? '3:4');
    $imageSize = grsai_map_image_size((string)($options['quality'] ?? '2k'));
    $refs = isset($options['referenceImageUrls']) && is_array($options['referenceImageUrls'])
        ? array_values(array_filter($options['referenceImageUrls'], 'is_string'))
        : [];

    $body = [
        'model' => $model,
        'prompt' => $prompt,
        'aspectRatio' => $aspect,
        'shutProgress' => true,
        'webHook' => '-1',
    ];
    if (empty($cfg['omit_image_size'])) {
        $body['imageSize'] = $imageSize;
    }
    if ($refs !== []) {
        $body['urls'] = array_slice($refs, 0, 8);
    }

    $submitTimeout = (int)($cfg['submit_timeout'] ?? 90);
    if ($submitTimeout < 30) {
        $submitTimeout = 90;
    }

    grsai_log('draw.submit', ['url' => $url, 'model' => $model, 'aspect' => $aspect]);

    $res = grsai_http_post_json($url, $apiKey, $body, $submitTimeout);
    $raw = $res['raw'] ?? '';

    if (!$res['ok']) {
        $em = (string)($res['message'] ?? '');
        if ($em === '') {
            $em = '请求失败 HTTP ' . (int)($res['http_code'] ?? 0);
        }
        return ['success' => false, 'message' => $em, 'raw' => $raw];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = grsai_parse_sse_last_json($raw);
    }
    if (!is_array($data)) {
        grsai_log('draw.bad_response', ['preview' => substr($raw, 0, 400)]);
        return ['success' => false, 'message' => '接口返回格式异常', 'raw' => $raw];
    }

    if (array_key_exists('code', $data) && (int)$data['code'] !== 0 && (int)$data['code'] !== 200) {
        $em = (string)($data['msg'] ?? $data['message'] ?? '接口返回错误');
        grsai_log('draw.business_code', ['code' => $data['code'], 'msg' => $em]);
        return ['success' => false, 'message' => $em, 'raw' => $raw];
    }

    $fail = grsai_find_failed_message($data);
    if ($fail !== null) {
        return ['success' => false, 'message' => $fail, 'raw' => $raw];
    }

    $imageUrl = grsai_find_result_url($data);
    if ($imageUrl) {
        return ['success' => true, 'image_url' => $imageUrl, 'raw' => $raw];
    }

    $taskId = grsai_extract_submit_task_id($data);
    if ($taskId) {
        grsai_log('draw.task_id', ['task_id' => $taskId]);
        return ['success' => true, 'task_id' => $taskId, 'raw' => $raw];
    }

    return ['success' => false, 'message' => '未能从接口解析出任务 ID 或图片地址', 'raw' => $raw];
}
