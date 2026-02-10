<?php
/**
 * AI 配置示例 - 复制为 ai.local.php 并填写实际值
 * 或使用环境变量：WUYIN_API_KEY
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
        'model_names' => [
            'banana' => 'nano-banana',
            'banana_pro' => 'nano-banana-pro',
        ],
        'draw_detail' => '/api/img/drawDetail',
        'async_detail' => '/api/async/detail',
    ],
];
