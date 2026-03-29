<?php
/**
 * 队列与 Redis 配置
 * 优先级：环境变量 > queue.local.php > 默认值
 */
if (!function_exists('queue_env_model_limits')) {
    function queue_env_model_limits(string $raw): array {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }
        $limits = [];
        foreach (explode(',', $raw) as $item) {
            $item = trim($item);
            if ($item === '' || strpos($item, ':') === false) {
                continue;
            }
            [$model, $limit] = array_map('trim', explode(':', $item, 2));
            if ($model === '' || $limit === '') {
                continue;
            }
            $limits[strtolower($model)] = max(0, (int)$limit);
        }
        return $limits;
    }
}

$config = [
    'redis' => [
        'scheme' => getenv('REDIS_SCHEME') ?: 'tcp',
        'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
        'port' => (int)(getenv('REDIS_PORT') ?: 6379),
        'password' => getenv('REDIS_PASSWORD') ?: '',
        'database' => (int)(getenv('REDIS_DATABASE') ?: 0),
        'timeout' => (float)(getenv('REDIS_TIMEOUT') ?: 2.0),
        'read_write_timeout' => (float)(getenv('REDIS_READ_WRITE_TIMEOUT') ?: 5.0),
        'prefix' => getenv('REDIS_PREFIX') ?: 'ai_creator:',
    ],
    'workers' => [
        'submit_block_seconds' => (int)(getenv('QUEUE_SUBMIT_BLOCK_SECONDS') ?: 5),
        'media_block_seconds' => (int)(getenv('QUEUE_MEDIA_BLOCK_SECONDS') ?: 5),
        'poll_idle_sleep_ms' => (int)(getenv('QUEUE_POLL_IDLE_SLEEP_MS') ?: 1000),
        'poll_retry_base_seconds' => (int)(getenv('QUEUE_POLL_RETRY_BASE_SECONDS') ?: 4),
        'poll_retry_max_seconds' => (int)(getenv('QUEUE_POLL_RETRY_MAX_SECONDS') ?: 30),
        'submit_retry_seconds' => (int)(getenv('QUEUE_SUBMIT_RETRY_SECONDS') ?: 10),
        'media_retry_seconds' => (int)(getenv('QUEUE_MEDIA_RETRY_SECONDS') ?: 20),
        'status_backfill_limit' => (int)(getenv('QUEUE_STATUS_BACKFILL_LIMIT') ?: 50),
    ],
    'limits' => [
        'active_total_limit' => (int)(getenv('QUEUE_ACTIVE_TOTAL_LIMIT') ?: 20),
        'active_model_limits' => queue_env_model_limits((string)(getenv('QUEUE_ACTIVE_MODEL_LIMITS') ?: 'nanobanana2:10')),
    ],
];

$localFile = __DIR__ . '/queue.local.php';
if (file_exists($localFile)) {
    $local = require $localFile;
    if (isset($local['redis']) && is_array($local['redis'])) {
        $config['redis'] = array_merge($config['redis'], $local['redis']);
    }
    if (isset($local['workers']) && is_array($local['workers'])) {
        $config['workers'] = array_merge($config['workers'], $local['workers']);
    }
    if (isset($local['limits']) && is_array($local['limits'])) {
        $config['limits'] = array_merge($config['limits'], $local['limits']);
    }
}

return $config;
