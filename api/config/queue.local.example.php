<?php
/**
 * 队列/Redis 本地配置示例
 * 复制为 queue.local.php 后填写真实值
 */
return [
    'redis' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',
        'database' => 0,
        'prefix' => 'ai_creator:',
    ],
    'workers' => [
        'submit_block_seconds' => 5,
        'media_block_seconds' => 5,
        'poll_idle_sleep_ms' => 1000,
    ],
    'limits' => [
        'active_total_limit' => 20,
        'active_model_limits' => [
            'nanobanana2' => 10,
        ],
    ],
];
