<?php
/**
 * AI 配置
 * 配置优先级：ai.local.php > 环境变量
 * 环境变量：WUYIN_API_KEY、ARK_API_KEY、GRSAI_API_KEY（nanobanana2）
 */
if (!function_exists('ai_env_list')) {
    function ai_env_list(string $name): array {
        $raw = trim((string)getenv($name));
        if ($raw === '') return [];
        $parts = array_map('trim', explode(',', $raw));
        return array_values(array_filter($parts, 'strlen'));
    }
}

$default = [
    'wuyinkeji' => [
        'api_key' => getenv('WUYIN_API_KEY') ?: '',
        'api_keys' => ai_env_list('WUYIN_API_KEYS'),
        'base_url' => 'https://api.wuyinkeji.com',
        'submit_concurrency' => (int)(getenv('WUYIN_SUBMIT_CONCURRENCY') ?: 6),
        // 文档版提交端点（优先尝试）
        'img_endpoints' => [
            'banana' => '/api/img/nanoBanana',
            'banana_pro' => '/api/img/nanoBanana-pro',
        ],
        // 兼容旧版提交端点（文档端点不兼容时回退）
        'async_endpoints' => [
            'banana' => '/api/async/image_nanoBanana',
            'banana_pro' => '/api/async/image_nanoBananaPro',
        ],
        // 视频异步端点
        'video_async_endpoints' => [
            'veo3_1_pro' => '/api/async/video_veo3.1_pro',
        ],
        // 文档版模型名
        'model_names' => [
            'banana' => 'nano-banana',
            'banana_pro' => 'nano-banana-pro',
        ],
        // 轮询端点（文档版：数字ID）
        'draw_detail' => '/api/img/drawDetail',
        // 轮询端点（兼容版：image_xxx）
        'async_detail' => '/api/async/detail',
    ],
    'doubao' => [
        'api_key' => getenv('ARK_API_KEY') ?: '',
        'api_keys' => ai_env_list('ARK_API_KEYS'),
        'base_url' => getenv('ARK_BASE_URL') ?: 'https://ark.cn-beijing.volces.com/api/v3',
        'model' => getenv('ARK_VIDEO_MODEL') ?: 'doubao-seedance-1-5-pro-251215',
        'create_endpoint' => '/contents/generations/tasks',
        'submit_concurrency' => (int)(getenv('ARK_SUBMIT_CONCURRENCY') ?: 4),
    ],
    'openai_hk' => [
        'api_key' => getenv('OPENAI_HK_API_KEY') ?: '',
        'base_url' => getenv('OPENAI_HK_BASE_URL') ?: 'https://api.openai-hk.com',
        'model' => getenv('OPENAI_HK_MODEL') ?: 'nanobanana2',
        'chat_path' => '/v1/chat/completions',
        'timeout' => 180,
    ],
    /** GrsAI Nano Banana（官方 sk- key，Host 见控制台） */
    'grsai' => [
        'api_key' => getenv('GRSAI_API_KEY') ?: '',
        'api_keys' => ai_env_list('GRSAI_API_KEYS'),
        'base_url' => getenv('GRSAI_BASE_URL') ?: 'https://grsai.dakka.com.cn',
        /** draw：/v1/draw/nano-banana（实测 nano-banana-2 有效）。chat_then_draw：先 chat 再 draw（部分节点 chat 无此模型名） */
        'submit_mode' => getenv('GRSAI_SUBMIT_MODE') ?: 'draw',
        'chat_path' => '/v1/chat/completions',
        'chat_timeout' => 120,
        'draw_endpoint' => '/v1/draw/nano-banana',
        'result_endpoint' => '/v1/draw/result',
        'nanobanana2_model' => getenv('GRSAI_NANOBANANA2_MODEL') ?: 'nano-banana-2',
        'submit_timeout' => 90,
        'poll_max_attempts' => 90,
        'poll_interval_sec' => 2,
        'submit_concurrency' => (int)(getenv('GRSAI_SUBMIT_CONCURRENCY') ?: 4),
        /** 若上游报错不认 imageSize，可在 ai.local.php 设为 true 以不传该字段 */
        'omit_image_size' => false,
    ],
];
$localFile = __DIR__ . '/ai.local.php';
if (file_exists($localFile)) {
    $local = require $localFile;
    if (isset($local['wuyinkeji']) && is_array($local['wuyinkeji'])) {
        $default['wuyinkeji'] = array_merge($default['wuyinkeji'], $local['wuyinkeji']);
    }
    if (isset($local['doubao']) && is_array($local['doubao'])) {
        $default['doubao'] = array_merge($default['doubao'], $local['doubao']);
    }
    if (isset($local['openai_hk']) && is_array($local['openai_hk'])) {
        $default['openai_hk'] = array_merge($default['openai_hk'] ?? [], $local['openai_hk']);
    }
    if (isset($local['grsai']) && is_array($local['grsai'])) {
        $default['grsai'] = array_merge($default['grsai'] ?? [], $local['grsai']);
    }
}
return $default;
