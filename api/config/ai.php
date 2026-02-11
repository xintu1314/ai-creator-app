<?php
/**
 * AI 配置
 * 配置优先级：ai.local.php > 环境变量
 * 环境变量：WUYIN_API_KEY、ARK_API_KEY
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
    'doubao' => [
        'api_key' => getenv('ARK_API_KEY') ?: '',
        'base_url' => getenv('ARK_BASE_URL') ?: 'https://ark.cn-beijing.volces.com/api/v3',
        'model' => getenv('ARK_VIDEO_MODEL') ?: 'doubao-seedance-1-5-pro-251215',
        'create_endpoint' => '/contents/generations/tasks',
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
}
return $default;
