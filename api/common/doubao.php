<?php
/**
 * 豆包视频（火山方舟 Ark）API 封装
 */

function doubao_config(): array {
    static $cfg = null;
    if ($cfg !== null) return $cfg;
    $all = require __DIR__ . '/../config/ai.php';
    $cfg = is_array($all['doubao'] ?? null) ? $all['doubao'] : [];
    return $cfg;
}

function doubao_mask_secret(string $secret): string {
    $len = strlen($secret);
    if ($len <= 10) return str_repeat('*', $len);
    return substr($secret, 0, 6) . str_repeat('*', $len - 10) . substr($secret, -4);
}

function doubao_log_file(): string {
    $dir = __DIR__ . '/../logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir . '/doubao-' . date('Ymd') . '.log';
}

function doubao_log(string $event, array $context = []): void {
    $line = [
        'ts' => date('c'),
        'event' => $event,
        'context' => $context,
    ];
    @file_put_contents(
        doubao_log_file(),
        json_encode($line, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
        FILE_APPEND
    );
}

function doubao_http_request(string $method, string $url, string $apiKey, ?array $payload = null): array {
    $startedAt = microtime(true);
    $ch = curl_init($url);
    $headers = [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ];

    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 45,
    ];

    if (strtoupper($method) === 'POST') {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = json_encode($payload ?? [], JSON_UNESCAPED_UNICODE);
    } else {
        $opts[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
    }

    curl_setopt_array($ch, $opts);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    @curl_close($ch);
    $elapsedMs = (int)round((microtime(true) - $startedAt) * 1000);

    if ($err) {
        return [
            'ok' => false,
            'message' => '请求失败：' . $err,
            'http_code' => $httpCode,
            'elapsed_ms' => $elapsedMs,
            'raw' => $raw ?: '',
        ];
    }

    $data = json_decode((string)$raw, true);
    if (!is_array($data)) {
        return [
            'ok' => false,
            'message' => '接口返回非 JSON',
            'http_code' => $httpCode,
            'elapsed_ms' => $elapsedMs,
            'raw' => $raw ?: '',
        ];
    }

    return [
        'ok' => $httpCode >= 200 && $httpCode < 300,
        'data' => $data,
        'http_code' => $httpCode,
        'elapsed_ms' => $elapsedMs,
        'raw' => $raw ?: '',
    ];
}

function doubao_join_url(string $base, string $path): string {
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function doubao_get_nested(array $data, array $path, $default = null) {
    $cur = $data;
    foreach ($path as $key) {
        if (!is_array($cur) || !array_key_exists($key, $cur)) {
            return $default;
        }
        $cur = $cur[$key];
    }
    return $cur;
}

function doubao_extract_first_http_url($value): ?string {
    if (is_string($value)) {
        $trimmed = trim($value);
        if (preg_match('#^https?://#i', $trimmed)) {
            return $trimmed;
        }
        return null;
    }
    if (!is_array($value)) return null;

    $priorityKeys = ['url', 'video_url', 'file_url', 'download_url'];
    foreach ($priorityKeys as $key) {
        if (array_key_exists($key, $value)) {
            $found = doubao_extract_first_http_url($value[$key]);
            if ($found) return $found;
        }
    }
    foreach ($value as $item) {
        $found = doubao_extract_first_http_url($item);
        if ($found) return $found;
    }
    return null;
}

function doubao_normalize_ratio(string $ratio): string {
    $map = [
        '9:21' => '9:16',
    ];
    return $map[$ratio] ?? $ratio;
}

function doubao_submit_video(string $prompt, array $options = []): array {
    $cfg = doubao_config();
    $apiKey = trim((string)($cfg['api_key'] ?? ''));
    $baseUrl = trim((string)($cfg['base_url'] ?? ''));
    $model = trim((string)($options['model'] ?? ($cfg['model'] ?? '')));
    $endpoint = trim((string)($cfg['create_endpoint'] ?? '/contents/generations/tasks'));

    if ($apiKey === '') return ['success' => false, 'message' => 'ARK_API_KEY 未配置'];
    if ($baseUrl === '' || $model === '') return ['success' => false, 'message' => '豆包配置不完整'];

    $duration = (int)($options['duration'] ?? 5);
    if ($duration <= 0) $duration = 5;
    $cameraFixed = (bool)($options['camera_fixed'] ?? false);
    $watermark = array_key_exists('watermark', $options) ? (bool)$options['watermark'] : true;
    $ratio = trim((string)($options['aspect_ratio'] ?? ''));
    $ratio = $ratio !== '' ? doubao_normalize_ratio($ratio) : '';

    $promptWithFlags = trim($prompt);
    $promptWithFlags .= ' --duration ' . $duration;
    $promptWithFlags .= ' --camerafixed ' . ($cameraFixed ? 'true' : 'false');
    $promptWithFlags .= ' --watermark ' . ($watermark ? 'true' : 'false');
    if ($ratio !== '') {
        $promptWithFlags .= ' --ratio ' . $ratio;
    }

    $content = [[
        'type' => 'text',
        'text' => $promptWithFlags,
    ]];

    $firstFrameUrl = trim((string)($options['first_frame_url'] ?? ''));
    if ($firstFrameUrl !== '') {
        $content[] = [
            'type' => 'image_url',
            'image_url' => [
                'url' => $firstFrameUrl,
            ],
        ];
    }

    $payload = [
        'model' => $model,
        'content' => $content,
    ];

    $url = doubao_join_url($baseUrl, $endpoint);
    doubao_log('submit.begin', [
        'url' => $url,
        'model' => $model,
        'has_first_frame' => $firstFrameUrl !== '',
        'api_key' => doubao_mask_secret($apiKey),
    ]);
    $ret = doubao_http_request('POST', $url, $apiKey, $payload);

    if (!$ret['ok']) {
        $errData = is_array($ret['data'] ?? null) ? $ret['data'] : [];
        $message = (string)(doubao_get_nested($errData, ['error', 'message'], '') ?: ($ret['message'] ?? '请求失败'));
        doubao_log('submit.failed', [
            'http_code' => $ret['http_code'] ?? 0,
            'elapsed_ms' => $ret['elapsed_ms'] ?? 0,
            'message' => $message,
            'raw' => $ret['raw'] ?? '',
        ]);
        return ['success' => false, 'message' => $message];
    }

    $data = $ret['data'] ?? [];
    $taskId = trim((string)($data['id'] ?? ''));
    if ($taskId === '') {
        $msg = (string)(doubao_get_nested($data, ['error', 'message'], '') ?: '未获取到任务ID');
        doubao_log('submit.invalid_response', ['data' => $data, 'message' => $msg]);
        return ['success' => false, 'message' => $msg];
    }

    doubao_log('submit.ok', [
        'task_id' => $taskId,
        'http_code' => $ret['http_code'] ?? 0,
        'elapsed_ms' => $ret['elapsed_ms'] ?? 0,
    ]);
    return [
        'success' => true,
        'task_id' => $taskId,
        'raw' => $data,
    ];
}

function doubao_query_video(string $taskId): array {
    $cfg = doubao_config();
    $apiKey = trim((string)($cfg['api_key'] ?? ''));
    $baseUrl = trim((string)($cfg['base_url'] ?? ''));
    $endpoint = trim((string)($cfg['create_endpoint'] ?? '/contents/generations/tasks'));
    if ($apiKey === '' || $baseUrl === '') {
        return ['success' => false, 'message' => '豆包配置不完整'];
    }

    $url = doubao_join_url($baseUrl, $endpoint . '/' . rawurlencode($taskId));
    $ret = doubao_http_request('GET', $url, $apiKey);
    if (!$ret['ok']) {
        $errData = is_array($ret['data'] ?? null) ? $ret['data'] : [];
        $message = (string)(doubao_get_nested($errData, ['error', 'message'], '') ?: ($ret['message'] ?? '查询失败'));
        doubao_log('query.failed', [
            'task_id' => $taskId,
            'http_code' => $ret['http_code'] ?? 0,
            'message' => $message,
            'raw' => $ret['raw'] ?? '',
        ]);
        return ['success' => false, 'message' => $message];
    }

    $data = $ret['data'] ?? [];
    $statusRaw = strtolower(trim((string)($data['status'] ?? '')));
    $status = 'processing';
    if (in_array($statusRaw, ['succeeded', 'completed', 'success'], true)) {
        $status = 'completed';
    } elseif (in_array($statusRaw, ['failed', 'cancelled', 'canceled'], true)) {
        $status = 'failed';
    }

    $resultUrl = doubao_extract_first_http_url($data);
    $failReason = (string)(doubao_get_nested($data, ['error', 'message'], '') ?: ($data['error'] ?? ''));

    if ($status === 'completed') {
        doubao_log('query.completed', [
            'task_id' => $taskId,
            'result_url' => $resultUrl ? 'has_url' : 'empty',
            'usage' => $data['usage'] ?? null,
            'raw_keys' => array_keys($data),
        ]);
    }

    return [
        'success' => true,
        'status' => $status,
        'status_raw' => $statusRaw,
        'result_url' => $resultUrl,
        'fail_reason' => $failReason,
        'raw' => $data,
    ];
}
