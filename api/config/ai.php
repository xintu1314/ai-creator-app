<?php
/**
 * AI 配置
 * 配置优先级：ai.local.php > 环境变量
 * 环境变量：WUYIN_API_KEY
 */
$default = [
    'wuyinkeji' => [
        'api_key' => getenv('WUYIN_API_KEY') ?: '',
        'base_url' => 'https://api.wuyinkeji.com',
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
];
$localFile = __DIR__ . '/ai.local.php';
if (file_exists($localFile)) {
    $local = require $localFile;
    if (isset($local['wuyinkeji']) && is_array($local['wuyinkeji'])) {
        $default['wuyinkeji'] = array_merge($default['wuyinkeji'], $local['wuyinkeji']);
    }
}
return $default;
