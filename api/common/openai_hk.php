<?php
/**
 * OpenAI-HK 兼容接口（https://api.openai-hk.com）— nanobanana2 等模型
 * 使用 POST /v1/chat/completions，从助手回复中解析图片 HTTPS 链接。
 */

function openai_hk_config(): array {
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }
    $all = require __DIR__ . '/../config/ai.php';
    $cfg = is_array($all['openai_hk'] ?? null) ? $all['openai_hk'] : [];
    return $cfg;
}

function openai_hk_log(string $event, array $context = []): void {
    $dir = __DIR__ . '/../logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $line = [
        'ts' => date('c'),
        'event' => $event,
        'context' => $context,
    ];
    @file_put_contents(
        $dir . '/openai-hk-' . date('Ymd') . '.log',
        json_encode($line, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
        FILE_APPEND
    );
}

/**
 * 从模型文本回复中提取首张可展示的 HTTPS 图片地址。
 */
function openai_hk_extract_image_url(string $content): ?string {
    $content = trim($content);
    if ($content === '') {
        return null;
    }
    // Markdown: ![alt](url)
    if (preg_match('/!\[[^\]]*\]\((https:\/\/[^)\s]+)\)/', $content, $m)) {
        return rtrim($m[1], '.,;)]\'"');
    }
    // 裸链
    if (preg_match('#https://[^\s\)"\'<>]+#u', $content, $m)) {
        return rtrim($m[0], '.,;)]\'"');
    }
    // JSON 片段
    $tryJson = json_decode($content, true);
    if (is_array($tryJson)) {
        foreach (['url', 'image_url', 'imageUrl', 'result_url'] as $k) {
            $v = $tryJson[$k] ?? null;
            if (is_string($v) && preg_match('#^https://#', $v)) {
                return $v;
            }
        }
    }
    return null;
}

/**
 * 调用 chat/completions 生成图片（模型在回复中返回图片链接）。
 *
 * @return array{success:bool, message?:string, image_url?:string, raw?:string}
 */
function openai_hk_nanobanana2_image(string $prompt, array $options = []): array {
    $cfg = openai_hk_config();
    $apiKey = trim((string)($cfg['api_key'] ?? ''));
    $base = rtrim((string)($cfg['base_url'] ?? 'https://api.openai-hk.com'), '/');
    $model = (string)($cfg['model'] ?? 'nanobanana2');
    $path = (string)($cfg['chat_path'] ?? '/v1/chat/completions');

    if ($apiKey === '') {
        return ['success' => false, 'message' => '未配置 OpenAI-HK：请设置环境变量 OPENAI_HK_API_KEY 或在 api/config/ai.local.php 中填写 openai_hk.api_key'];
    }

    $aspect = (string)($options['aspectRatio'] ?? '');
    $userParts = [];
    $refs = isset($options['referenceImageUrls']) && is_array($options['referenceImageUrls'])
        ? array_values(array_filter($options['referenceImageUrls'], 'is_string'))
        : [];
    $text = $prompt;
    if ($aspect !== '') {
        $text .= "\n\n画面比例：" . $aspect . "。";
    }
    if ($refs !== []) {
        $userParts[] = ['type' => 'text', 'text' => $text];
        foreach (array_slice($refs, 0, 8) as $u) {
            $userParts[] = [
                'type' => 'image_url',
                'image_url' => ['url' => $u],
            ];
        }
        $userContent = $userParts;
    } else {
        $userContent = $text;
    }

    $system = (string)($cfg['system_prompt'] ?? '你是图像生成助手。生成图片后请只输出一张图片的可访问 HTTPS 链接，或使用 Markdown 图片语法 ![图](url)。不要输出与链接无关的长篇说明。');

    $payload = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $userContent],
        ],
        'temperature' => isset($cfg['temperature']) ? (float)$cfg['temperature'] : 0.5,
    ];

    $url = $base . $path;
    $timeout = (int)($cfg['timeout'] ?? 180);
    if ($timeout < 30) {
        $timeout = 180;
    }

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
    @curl_close($ch);
    $elapsedMs = (int)round((microtime(true) - $startedAt) * 1000);

    if ($err !== '') {
        openai_hk_log('nanobanana2.curl_error', ['message' => $err, 'http' => $httpCode]);
        return ['success' => false, 'message' => '请求失败：' . $err, 'raw' => (string)$raw];
    }

    $data = json_decode((string)$raw, true);
    if (!is_array($data)) {
        return ['success' => false, 'message' => '接口返回非 JSON', 'raw' => (string)$raw];
    }

    if (!empty($data['error'])) {
        $em = is_array($data['error']) ? ($data['error']['message'] ?? json_encode($data['error'], JSON_UNESCAPED_UNICODE)) : (string)$data['error'];
        openai_hk_log('nanobanana2.api_error', ['error' => $data['error'], 'http' => $httpCode, 'elapsed_ms' => $elapsedMs]);
        $hint = '';
        if (stripos((string)$em, 'key error') !== false) {
            $hint = '（OpenAI-HK 需使用控制台「获取 key」里以 hk- 开头的密钥；sk- 多为官方 OpenAI key，不能用于 api.openai-hk.com。）';
        }
        return ['success' => false, 'message' => '接口错误：' . $em . $hint, 'raw' => (string)$raw];
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        return ['success' => false, 'message' => 'HTTP ' . $httpCode, 'raw' => (string)$raw];
    }

    $content = $data['choices'][0]['message']['content'] ?? '';
    if (is_array($content)) {
        // 少数实现返回 content 数组
        $flat = '';
        foreach ($content as $part) {
            if (is_array($part) && isset($part['text'])) {
                $flat .= $part['text'];
            } elseif (is_string($part)) {
                $flat .= $part;
            }
        }
        $content = $flat;
    }
    $content = (string)$content;

    $imageUrl = openai_hk_extract_image_url($content);
    if ($imageUrl === null || $imageUrl === '') {
        $preview = function_exists('mb_substr') ? mb_substr($content, 0, 500) : substr($content, 0, 500);
        openai_hk_log('nanobanana2.no_url', ['content_preview' => $preview, 'elapsed_ms' => $elapsedMs]);
        return [
            'success' => false,
            'message' => '模型未返回可识别的图片链接，请稍后重试或调整提示词',
            'raw' => (string)$raw,
        ];
    }

    openai_hk_log('nanobanana2.ok', ['elapsed_ms' => $elapsedMs, 'url_len' => strlen($imageUrl)]);
    return ['success' => true, 'image_url' => $imageUrl, 'raw' => (string)$raw];
}
