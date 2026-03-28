<?php
/**
 * AI 配置示例 - 复制为 ai.local.php 并填写实际值
 * 或使用环境变量：WUYIN_API_KEY、GRSAI_API_KEY
 */
return [
    'wuyinkeji' => [
        'api_key' => getenv('WUYIN_API_KEY') ?: 'your-wuyinkeji-api-key',
        'base_url' => 'https://api.wuyinkeji.com',
        // 文档接口（优先）
        'img_endpoints' => [
            'banana' => '/api/img/nanoBanana',
            'banana_pro' => '/api/img/nanoBanana-pro',
        ],
        // 兼容旧接口（自动回退）
        'async_endpoints' => [
            'banana' => '/api/async/image_nanoBanana',
            'banana_pro' => '/api/async/image_nanoBananaPro',
        ],
        'video_async_endpoints' => [
            'veo3_1_pro' => '/api/async/video_veo3.1_pro',
        ],
        'model_names' => [
            'banana' => 'nano-banana',
            'banana_pro' => 'nano-banana-pro',
        ],
        'draw_detail' => '/api/img/drawDetail',
        'async_detail' => '/api/async/detail',
    ],
    'openai_hk' => [
        'api_key' => getenv('OPENAI_HK_API_KEY') ?: 'your-openai-hk-api-key',
        'base_url' => 'https://api.openai-hk.com',
        'model' => 'nanobanana2',
        'chat_path' => '/v1/chat/completions',
        'timeout' => 180,
    ],
    'grsai' => [
        'api_key' => getenv('GRSAI_API_KEY') ?: 'sk-your-grsai-key',
        'base_url' => 'https://grsai.dakka.com.cn',
        'submit_mode' => 'draw',
        'chat_path' => '/v1/chat/completions',
        'draw_endpoint' => '/v1/draw/nano-banana',
        'result_endpoint' => '/v1/draw/result',
        'nanobanana2_model' => 'nano-banana-2',
    ],
];
