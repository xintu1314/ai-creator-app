<?php
/**
 * 无形科技 API 调用封装
 * - 优先按文档接口提交: /api/img/nanoBanana
 * - 若账号仍是旧协议，自动回退到 /api/async/image_nanoBanana
 * - 轮询根据 taskId 类型自动切换 drawDetail / async/detail
 */

function wuyinkeji_request_id() {
    static $rid = null;
    if ($rid !== null) return $rid;
    $incoming = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';
    if (is_string($incoming) && $incoming !== '') {
        $rid = $incoming;
        return $rid;
    }
    try {
        $rid = 'req_' . bin2hex(random_bytes(6));
    } catch (Throwable $e) {
        $rid = 'req_' . uniqid();
    }
    return $rid;
}

function wuyinkeji_log_file() {
    $dir = __DIR__ . '/../logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir . '/wuyin-' . date('Ymd') . '.log';
}

function wuyinkeji_mask_secret($s, $left = 6, $right = 4) {
    if (!is_string($s) || $s === '') return '';
    $len = strlen($s);
    if ($len <= ($left + $right)) return str_repeat('*', $len);
    return substr($s, 0, $left) . str_repeat('*', $len - $left - $right) . substr($s, -$right);
}

function wuyinkeji_log($event, $context = []) {
    $line = [
        'ts' => date('c'),
        'request_id' => wuyinkeji_request_id(),
        'event' => $event,
        'context' => $context,
    ];
    @file_put_contents(
        wuyinkeji_log_file(),
        json_encode($line, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
        FILE_APPEND
    );
}

function wuyinkeji_http_post_json($url, $apiKey, $payload) {
    $startedAt = microtime(true);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . $apiKey,
            'Content-Type: application/json;charset=utf-8',
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    @curl_close($ch);
    $elapsedMs = (int)round((microtime(true) - $startedAt) * 1000);
    if ($err) return ['ok' => false, 'message' => '请求失败：' . $err, 'http_code' => $httpCode, 'elapsed_ms' => $elapsedMs, 'raw_response' => $response ?: ''];
    $data = json_decode($response, true);
    if (!is_array($data)) return ['ok' => false, 'message' => '接口返回格式异常', 'http_code' => $httpCode, 'elapsed_ms' => $elapsedMs, 'raw_response' => $response ?: ''];
    return ['ok' => true, 'data' => $data, 'http_code' => $httpCode, 'elapsed_ms' => $elapsedMs, 'raw_response' => $response ?: ''];
}

function wuyinkeji_http_get_json($url, $apiKey, $contentType = 'application/json;charset=utf-8') {
    $startedAt = microtime(true);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . $apiKey,
            'Content-Type: ' . $contentType,
        ],
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    @curl_close($ch);
    $elapsedMs = (int)round((microtime(true) - $startedAt) * 1000);
    if ($err) return ['ok' => false, 'message' => '请求失败：' . $err, 'http_code' => $httpCode, 'elapsed_ms' => $elapsedMs, 'raw_response' => $response ?: ''];
    $data = json_decode($response, true);
    if (!is_array($data)) return ['ok' => false, 'message' => '接口返回格式异常', 'http_code' => $httpCode, 'elapsed_ms' => $elapsedMs, 'raw_response' => $response ?: ''];
    return ['ok' => true, 'data' => $data, 'http_code' => $httpCode, 'elapsed_ms' => $elapsedMs, 'raw_response' => $response ?: ''];
}

function wuyinkeji_is_unbound_param_error($msg) {
    if (!is_string($msg)) return false;
    return str_contains($msg, '未绑定的参数') || str_contains(strtolower($msg), 'unbound');
}

/**
 * 提交图片生成任务
 * @param string $modelId 'banana' | 'banana_pro'
 * @param string $prompt
 * @param array  $options ['imageSize','aspectRatio','urls','count']
 */
function wuyinkeji_submit_image($modelId, $prompt, $options = []) {
    $config = require __DIR__ . '/../config/ai.php';
    $cfg = $config['wuyinkeji'] ?? null;
    if (!$cfg || empty($cfg['api_key'])) {
        return ['success' => false, 'message' => 'AI 配置未完成，请配置 api/config/ai.local.php'];
    }

    $baseUrl = rtrim($cfg['base_url'], '/');
    $apiKey = $cfg['api_key'];

    $aspectRatio = $options['aspectRatio'] ?? 'auto';
    $validRatios = ['auto', '1:1', '16:9', '9:16', '4:3', '3:4', '3:2', '2:3', '5:4', '4:5', '21:9'];
    if (!in_array($aspectRatio, $validRatios, true)) $aspectRatio = 'auto';
    $imageSize = strtoupper($options['imageSize'] ?? '2K');
    if (!in_array($imageSize, ['1K', '2K', '4K'], true)) $imageSize = '2K';
    $count = max(1, min(4, (int)($options['count'] ?? 1)));

    $urls = [];
    if (!empty($options['urls']) && is_array($options['urls'])) {
        $urls = array_values(array_filter($options['urls'], 'is_string'));
    }

    wuyinkeji_log('submit.begin', [
        'model_id' => $modelId,
        'prompt_len' => mb_strlen((string)$prompt),
        'aspect_ratio' => $aspectRatio,
        'image_size' => $imageSize,
        'count' => $count,
        'urls_count' => count($urls),
        'api_key_masked' => wuyinkeji_mask_secret($apiKey),
    ]);

    // 1) 文档版接口（优先）
    $imgEndpoints = $cfg['img_endpoints'] ?? [];
    $imgEndpoint = $imgEndpoints[$modelId] ?? ($imgEndpoints['banana'] ?? '/api/img/nanoBanana');
    $docModelNames = $cfg['model_names'] ?? [];
    $docModel = $docModelNames[$modelId] ?? 'nano-banana';

    $docPayload = [
        'prompt' => $prompt,
        'aspectRatio' => $aspectRatio,
        'imageSize' => $imageSize,
    ];
    // banana 文档接口保留 model；banana-pro 文档未要求 model，不传以避免参数绑定报错
    if ($modelId !== 'banana_pro') {
        $docPayload['model'] = $docModel;
    }
    if (!empty($urls)) $docPayload['img_url'] = $urls;

    wuyinkeji_log('submit.try_doc_api', [
        'endpoint' => $imgEndpoint,
        'url' => $baseUrl . $imgEndpoint,
        'payload' => $docPayload,
    ]);

    $docResp = wuyinkeji_http_post_json($baseUrl . $imgEndpoint, $apiKey, $docPayload);
    if (!$docResp['ok']) {
        wuyinkeji_log('submit.doc_api_transport_error', [
            'endpoint' => $imgEndpoint,
            'error_message' => $docResp['message'] ?? '',
            'http_code' => $docResp['http_code'] ?? 0,
            'elapsed_ms' => $docResp['elapsed_ms'] ?? 0,
            'raw_response' => $docResp['raw_response'] ?? '',
        ]);
        return ['success' => false, 'message' => $docResp['message']];
    }
    $docData = $docResp['data'];
    wuyinkeji_log('submit.doc_api_response', [
        'endpoint' => $imgEndpoint,
        'http_code' => $docResp['http_code'] ?? 0,
        'elapsed_ms' => $docResp['elapsed_ms'] ?? 0,
        'response' => $docData,
    ]);
    if (($docData['code'] ?? 0) === 200) {
        $taskId = $docData['data']['id'] ?? null; // 文档版一般是数字ID
        if ($taskId === null || $taskId === '') {
            wuyinkeji_log('submit.doc_api_missing_task_id', ['response' => $docData]);
            return ['success' => false, 'message' => '未获取到任务ID'];
        }
        wuyinkeji_log('submit.success', ['channel' => 'doc', 'task_id' => $taskId]);
        return ['success' => true, 'task_id' => $taskId, 'channel' => 'doc'];
    }

    $docErrMsg = $docData['msg'] ?? '接口调用失败';
    // 2) 若文档接口在该账号下不兼容，自动回退 async 接口
    if (!wuyinkeji_is_unbound_param_error($docErrMsg)) {
        wuyinkeji_log('submit.doc_api_failed_no_fallback', [
            'message' => $docErrMsg,
            'response' => $docData,
        ]);
        return ['success' => false, 'message' => $docErrMsg];
    }

    wuyinkeji_log('submit.fallback_to_async', [
        'reason' => $docErrMsg,
    ]);

    $asyncEndpoints = $cfg['async_endpoints'] ?? [];
    $asyncEndpoint = $asyncEndpoints[$modelId] ?? ($asyncEndpoints['banana'] ?? '/api/async/image_nanoBanana');
    $asyncPayload = [
        'prompt' => $prompt,
        'imageSize' => $imageSize,
        'aspectRatio' => $aspectRatio,
    ];
    if (!empty($urls)) $asyncPayload['urls'] = $urls;

    wuyinkeji_log('submit.try_async_api', [
        'endpoint' => $asyncEndpoint,
        'url' => $baseUrl . $asyncEndpoint,
        'payload' => $asyncPayload,
    ]);

    $asyncResp = wuyinkeji_http_post_json($baseUrl . $asyncEndpoint, $apiKey, $asyncPayload);
    if (!$asyncResp['ok']) {
        wuyinkeji_log('submit.async_api_transport_error', [
            'endpoint' => $asyncEndpoint,
            'error_message' => $asyncResp['message'] ?? '',
            'http_code' => $asyncResp['http_code'] ?? 0,
            'elapsed_ms' => $asyncResp['elapsed_ms'] ?? 0,
            'raw_response' => $asyncResp['raw_response'] ?? '',
        ]);
        return ['success' => false, 'message' => $asyncResp['message']];
    }
    $asyncData = $asyncResp['data'];
    wuyinkeji_log('submit.async_api_response', [
        'endpoint' => $asyncEndpoint,
        'http_code' => $asyncResp['http_code'] ?? 0,
        'elapsed_ms' => $asyncResp['elapsed_ms'] ?? 0,
        'response' => $asyncData,
    ]);
    if (($asyncData['code'] ?? 0) !== 200) {
        wuyinkeji_log('submit.async_api_failed', ['response' => $asyncData]);
        return ['success' => false, 'message' => $asyncData['msg'] ?? '接口调用失败'];
    }
    $taskId = $asyncData['data']['id'] ?? null; // 兼容版一般是 image_xxx
    if ($taskId === null || $taskId === '') {
        wuyinkeji_log('submit.async_api_missing_task_id', ['response' => $asyncData]);
        return ['success' => false, 'message' => '未获取到任务ID'];
    }
    wuyinkeji_log('submit.success', ['channel' => 'async', 'task_id' => $taskId]);
    return ['success' => true, 'task_id' => $taskId, 'channel' => 'async'];
}

function wuyinkeji_extract_url_from_async_result($result) {
    if (!is_array($result)) return '';
    if (!empty($result['result']) && is_array($result['result'])) {
        $first = $result['result'][0] ?? '';
        if (is_string($first) && str_starts_with($first, 'http')) return $first;
    }
    $imageUrl = $result['image_url'] ?? $result['imageUrl'] ?? $result['url'] ?? $result['output'] ?? '';
    if (is_string($imageUrl) && $imageUrl !== '') return $imageUrl;
    if (!empty($result['images']) && is_array($result['images'])) {
        $first = $result['images'][0] ?? '';
        if (is_string($first) && $first !== '') return $first;
    }
    return '';
}

/**
 * 查询图片生成结果（自动选择端点）
 */
function wuyinkeji_query_image($taskId) {
    $config = require __DIR__ . '/../config/ai.php';
    $cfg = $config['wuyinkeji'] ?? null;
    if (!$cfg || empty($cfg['api_key'])) return ['success' => false, 'message' => 'AI 配置未完成'];

    $baseUrl = rtrim($cfg['base_url'], '/');
    $apiKey = $cfg['api_key'];

    // 纯数字ID => 文档版 drawDetail
    if (is_numeric((string)$taskId)) {
        $endpoint = $cfg['draw_detail'] ?? '/api/img/drawDetail';
        wuyinkeji_log('query.begin', [
            'task_id' => (string)$taskId,
            'channel' => 'doc',
            'endpoint' => $endpoint,
        ]);
        $resp = wuyinkeji_http_get_json($baseUrl . $endpoint . '?id=' . urlencode((string)$taskId), $apiKey);
        if (!$resp['ok']) {
            wuyinkeji_log('query.transport_error', [
                'task_id' => (string)$taskId,
                'channel' => 'doc',
                'error_message' => $resp['message'] ?? '',
                'http_code' => $resp['http_code'] ?? 0,
                'elapsed_ms' => $resp['elapsed_ms'] ?? 0,
                'raw_response' => $resp['raw_response'] ?? '',
            ]);
            return ['success' => false, 'message' => $resp['message']];
        }
        $data = $resp['data'];
        wuyinkeji_log('query.response', [
            'task_id' => (string)$taskId,
            'channel' => 'doc',
            'http_code' => $resp['http_code'] ?? 0,
            'elapsed_ms' => $resp['elapsed_ms'] ?? 0,
            'response' => $data,
        ]);
        if (($data['code'] ?? 0) !== 200) return ['success' => false, 'message' => $data['msg'] ?? '查询失败'];
        $result = $data['data'] ?? [];
        if (!is_array($result)) $result = [];
        $parsed = [
            'status' => (int)($result['status'] ?? -1),
            'image_url' => $result['image_url'] ?? '',
            'fail_reason' => $result['fail_reason'] ?? ($result['message'] ?? ''),
        ];
        wuyinkeji_log('query.parsed', [
            'task_id' => (string)$taskId,
            'channel' => 'doc',
            'parsed' => $parsed,
        ]);
        return [
            'success' => true,
            'status' => $parsed['status'],
            'image_url' => $parsed['image_url'],
            'fail_reason' => $parsed['fail_reason'],
        ];
    }

    // 非数字（如 image_xxx）=> async/detail
    $endpoint = $cfg['async_detail'] ?? '/api/async/detail';
    wuyinkeji_log('query.begin', [
        'task_id' => (string)$taskId,
        'channel' => 'async',
        'endpoint' => $endpoint,
    ]);
    $resp = wuyinkeji_http_get_json(
        $baseUrl . $endpoint . '?id=' . urlencode((string)$taskId),
        $apiKey,
        'application/x-www-form-urlencoded;charset=utf-8'
    );
    if (!$resp['ok']) {
        wuyinkeji_log('query.transport_error', [
            'task_id' => (string)$taskId,
            'channel' => 'async',
            'error_message' => $resp['message'] ?? '',
            'http_code' => $resp['http_code'] ?? 0,
            'elapsed_ms' => $resp['elapsed_ms'] ?? 0,
            'raw_response' => $resp['raw_response'] ?? '',
        ]);
        return ['success' => false, 'message' => $resp['message']];
    }
    $data = $resp['data'];
    wuyinkeji_log('query.response', [
        'task_id' => (string)$taskId,
        'channel' => 'async',
        'http_code' => $resp['http_code'] ?? 0,
        'elapsed_ms' => $resp['elapsed_ms'] ?? 0,
        'response' => $data,
    ]);
    if (($data['code'] ?? 0) !== 200) return ['success' => false, 'message' => $data['msg'] ?? '查询失败'];
    $result = $data['data'] ?? [];
    if (!is_array($result)) $result = [];
    $parsed = [
        'status' => (int)($result['status'] ?? -1),
        'image_url' => wuyinkeji_extract_url_from_async_result($result),
        'fail_reason' => $result['fail_reason'] ?? ($result['message'] ?? ''),
    ];
    wuyinkeji_log('query.parsed', [
        'task_id' => (string)$taskId,
        'channel' => 'async',
        'parsed' => $parsed,
    ]);
    return [
        'success' => true,
        'status' => $parsed['status'],
        'image_url' => $parsed['image_url'],
        'fail_reason' => $parsed['fail_reason'],
    ];
}
