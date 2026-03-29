<?php
require_once __DIR__ . '/queue.php';
require_once __DIR__ . '/tasks.php';
require_once __DIR__ . '/points.php';
require_once __DIR__ . '/provider_keys.php';
require_once __DIR__ . '/wuyinkeji.php';
require_once __DIR__ . '/grsai.php';
require_once __DIR__ . '/doubao.php';
require_once __DIR__ . '/oss.php';
require_once __DIR__ . '/../data/assets.php';

function generation_log(string $event, array $context = []): void {
    $dir = __DIR__ . '/../logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    @file_put_contents(
        $dir . '/generation-worker-' . date('Ymd') . '.log',
        json_encode([
            'ts' => date('c'),
            'event' => $event,
            'context' => $context,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
        FILE_APPEND
    );
}

function generation_map_ratio(string $ratio, string $fallback = '3:4'): string {
    $ratio = trim($ratio);
    if ($ratio === '9:21') return '9:16';
    return $ratio !== '' ? $ratio : $fallback;
}

function generation_make_title(string $prompt, string $fallback): string {
    $prompt = trim($prompt);
    if ($prompt === '') return $fallback;
    return function_exists('mb_substr')
        ? mb_substr($prompt, 0, 60)
        : substr($prompt, 0, 60);
}

function generation_resolve_spec(string $type, string $model): array {
    $type = strtolower(trim($type));
    $modelNorm = strtolower(trim($model));

    if ($type === 'image') {
        if (in_array($modelNorm, ['banana pro', 'banana-pro', 'banana_pro'], true)) {
            return ['provider' => 'wuyinkeji', 'provider_model' => 'banana_pro', 'type' => 'image'];
        }
        if ($modelNorm === 'nanobanana2') {
            return ['provider' => 'grsai', 'provider_model' => 'nanobanana2', 'type' => 'image'];
        }
        return ['provider' => 'wuyinkeji', 'provider_model' => 'banana', 'type' => 'image'];
    }

    if (in_array($modelNorm, ['veo3.1-pro', 'veo3.1_pro', 'veo3.1', 'veo3_1_pro'], true)) {
        return ['provider' => 'wuyinkeji', 'provider_model' => 'veo3_1_pro', 'type' => 'video'];
    }

    if (in_array($modelNorm, ['豆包视频', 'doubao-video', 'doubao-seedance-1-5-pro-251215', 'doubao_video'], true)) {
        return ['provider' => 'doubao', 'provider_model' => 'doubao_video', 'type' => 'video'];
    }

    throw new RuntimeException('不支持的模型：' . $model);
}

function generation_should_retry_submit(string $message): bool {
    $message = strtolower(trim($message));
    if ($message === '') return false;
    foreach (['timeout', 'timed out', '请求失败', 'network', 'http 5', 'rate', 'limit', 'busy', '连接'] as $needle) {
        if (str_contains($message, $needle)) {
            return true;
        }
    }
    return false;
}

function generation_poll_backoff_seconds(int $attempt): int {
    $cfg = queue_worker_settings();
    $base = max(2, (int)($cfg['poll_retry_base_seconds'] ?? 4));
    $max = max($base, (int)($cfg['poll_retry_max_seconds'] ?? 30));
    $delay = (int)min($max, $base * pow(1.6, max(0, $attempt - 1)));
    return max($base, $delay);
}

function generation_provider_poll_max_attempts(string $provider): int {
    $cfg = provider_keys_config();
    $providerCfg = is_array($cfg[$provider] ?? null) ? $cfg[$provider] : [];
    return max(20, (int)($providerCfg['poll_max_attempts'] ?? 90));
}

function generation_submit_retry_delay_seconds(): int {
    $cfg = queue_worker_settings();
    return max(2, (int)($cfg['submit_retry_seconds'] ?? 10));
}

function generation_active_limit_message(string $scope, string $name, int $limit, int $active): string {
    if ($scope === 'model') {
        return sprintf('模型 %s 并发已满（%d/%d），已进入排队等待', $name, $active, $limit);
    }
    return sprintf('系统生成并发已满（%d/%d），已进入排队等待', $active, $limit);
}

function generation_check_active_capacity(array $task): ?array {
    $limits = queue_generation_limits();
    $totalLimit = max(0, (int)($limits['active_total_limit'] ?? 0));
    if ($totalLimit > 0) {
        $activeTotal = tasks_count_active();
        if ($activeTotal >= $totalLimit) {
            return [
                'scope' => 'total',
                'limit' => $totalLimit,
                'active' => $activeTotal,
                'message' => generation_active_limit_message('total', 'system', $totalLimit, $activeTotal),
            ];
        }
    }

    $model = strtolower(trim((string)($task['provider_model'] ?? '')));
    $modelLimits = is_array($limits['active_model_limits'] ?? null) ? $limits['active_model_limits'] : [];
    $modelLimit = max(0, (int)($model !== '' ? ($modelLimits[$model] ?? 0) : 0));
    if ($model !== '' && $modelLimit > 0) {
        $activeModel = tasks_count_active($model);
        if ($activeModel >= $modelLimit) {
            return [
                'scope' => 'model',
                'limit' => $modelLimit,
                'active' => $activeModel,
                'message' => generation_active_limit_message('model', $model, $modelLimit, $activeModel),
            ];
        }
    }

    return null;
}

function generation_refund_once(array $task, array $params, string $source, string $description): void {
    if (!empty($params['points_refunded'])) {
        return;
    }
    $points = (int)($params['points_charged'] ?? 0);
    if ($points <= 0) {
        return;
    }

    $ret = points_refund_to_paid(
        (int)($task['user_id'] ?? 0),
        $points,
        $source,
        $description,
        [
            'taskId' => (string)($task['id'] ?? ''),
            'provider' => (string)($task['provider'] ?? ''),
            'providerModel' => (string)($task['provider_model'] ?? ''),
        ]
    );
    if (($ret['success'] ?? false) === true) {
        $params['points_refunded'] = true;
        tasks_update_params_json((string)$task['id'], $params);
    }
}

function generation_asset_exists(PDO $pdo, int $userId, string $url): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM assets WHERE user_id = :user_id AND image = :image LIMIT 1");
    $stmt->execute([
        'user_id' => $userId,
        'image' => $url,
    ]);
    return (bool)$stmt->fetchColumn();
}

function generation_submit_with_provider(array $task, array $params, array $keyEntry): array {
    $prompt = (string)($params['prompt'] ?? '');
    $provider = (string)($task['provider'] ?? '');
    $providerModel = (string)($task['provider_model'] ?? '');
    $apiKey = (string)($keyEntry['key'] ?? '');

    if ($provider === 'wuyinkeji' && $task['type'] === 'image') {
        return wuyinkeji_submit_image($providerModel, $prompt, [
            'aspectRatio' => generation_map_ratio((string)($params['aspectRatio'] ?? '3:4')),
            'imageSize' => strtoupper((string)($params['quality'] ?? '2K')),
            'urls' => $params['referenceImageUrls'] ?? [],
            'count' => 1,
            'api_key_override' => $apiKey,
        ]);
    }

    if ($provider === 'grsai' && $task['type'] === 'image') {
        return grsai_submit_nanobanana2($prompt, [
            'aspectRatio' => generation_map_ratio((string)($params['aspectRatio'] ?? '3:4')),
            'quality' => (string)($params['quality'] ?? '2k'),
            'referenceImageUrls' => $params['referenceImageUrls'] ?? [],
            'api_key_override' => $apiKey,
        ]);
    }

    if ($provider === 'wuyinkeji' && $task['type'] === 'video') {
        return wuyinkeji_submit_video($providerModel, $prompt, [
            'aspectRatio' => generation_map_ratio((string)($params['aspectRatio'] ?? '16:9'), '16:9'),
            'size' => (string)($params['size'] ?? '720p'),
            'firstFrameUrl' => (string)($params['firstFrameUrl'] ?? ''),
            'lastFrameUrl' => (string)($params['lastFrameUrl'] ?? ''),
            'urls' => $params['referenceImageUrls'] ?? [],
            'api_key_override' => $apiKey,
        ]);
    }

    if ($provider === 'doubao' && $task['type'] === 'video') {
        return doubao_submit_video($prompt, [
            'duration' => (int)($params['duration'] ?? 5),
            'aspect_ratio' => generation_map_ratio((string)($params['aspectRatio'] ?? '16:9'), '16:9'),
            'first_frame_url' => (string)($params['firstFrameUrl'] ?? ''),
            'camera_fixed' => (bool)($params['cameraFixed'] ?? false),
            'watermark' => array_key_exists('watermark', $params) ? (bool)$params['watermark'] : true,
            'api_key_override' => $apiKey,
        ]);
    }

    throw new RuntimeException('未支持的提交 provider：' . $provider);
}

function generation_query_with_provider(array $task, array $params, ?array $keyEntry): array {
    $provider = (string)($task['provider'] ?? '');
    $externalTaskId = trim((string)($task['external_task_id'] ?? ($params['external_task_id'] ?? '')));
    $apiKey = trim((string)($keyEntry['key'] ?? ''));
    $commonOptions = $apiKey !== '' ? ['api_key_override' => $apiKey] : [];

    if ($externalTaskId === '') {
        return ['success' => false, 'message' => '缺少 external_task_id'];
    }

    if ($provider === 'wuyinkeji' && $task['type'] === 'image') {
        $ret = wuyinkeji_query_image($externalTaskId, $commonOptions);
        if (!$ret['success']) {
            return ['success' => false, 'message' => (string)($ret['message'] ?? '查询失败')];
        }
        $status = (int)($ret['status'] ?? -1);
        if ($status === 2) {
            return ['success' => true, 'phase' => 'completed', 'result_url' => (string)($ret['image_url'] ?? '')];
        }
        if ($status === 3) {
            return ['success' => true, 'phase' => 'failed', 'message' => (string)($ret['fail_reason'] ?? '生成失败')];
        }
        return ['success' => true, 'phase' => 'running'];
    }

    if ($provider === 'grsai' && $task['type'] === 'image') {
        $ret = grsai_query_draw_status($externalTaskId, $commonOptions);
        if (!$ret['success']) {
            return ['success' => false, 'message' => (string)($ret['message'] ?? '查询失败')];
        }
        if (($ret['phase'] ?? '') === 'completed') {
            return ['success' => true, 'phase' => 'completed', 'result_url' => (string)($ret['image_url'] ?? '')];
        }
        if (($ret['phase'] ?? '') === 'failed') {
            return ['success' => true, 'phase' => 'failed', 'message' => (string)($ret['message'] ?? '生成失败')];
        }
        return ['success' => true, 'phase' => 'running'];
    }

    if ($provider === 'wuyinkeji' && $task['type'] === 'video') {
        $ret = wuyinkeji_query_video($externalTaskId, $commonOptions);
        if (!$ret['success']) {
            return ['success' => false, 'message' => (string)($ret['message'] ?? '查询失败')];
        }
        $status = strtolower((string)($ret['status'] ?? 'processing'));
        if ($status === 'completed') {
            return ['success' => true, 'phase' => 'completed', 'result_url' => (string)($ret['result_url'] ?? '')];
        }
        if ($status === 'failed') {
            return ['success' => true, 'phase' => 'failed', 'message' => (string)($ret['fail_reason'] ?? '生成失败')];
        }
        return ['success' => true, 'phase' => 'running'];
    }

    if ($provider === 'doubao' && $task['type'] === 'video') {
        $ret = doubao_query_video($externalTaskId, $commonOptions);
        if (!$ret['success']) {
            return ['success' => false, 'message' => (string)($ret['message'] ?? '查询失败')];
        }
        if (($ret['status'] ?? '') === 'completed') {
            return ['success' => true, 'phase' => 'completed', 'result_url' => (string)($ret['result_url'] ?? '')];
        }
        if (($ret['status'] ?? '') === 'failed') {
            return ['success' => true, 'phase' => 'failed', 'message' => (string)($ret['fail_reason'] ?? '生成失败')];
        }
        return ['success' => true, 'phase' => 'running'];
    }

    throw new RuntimeException('未支持的查询 provider：' . $provider);
}

function generation_submit_task_by_id(string $taskId): array {
    $task = tasks_get_by_id($taskId);
    if (!$task) {
        return ['success' => false, 'message' => '任务不存在'];
    }
    if (in_array((string)$task['status'], ['completed', 'failed'], true)) {
        return ['success' => true, 'message' => '任务已结束，跳过'];
    }

    $params = tasks_decode_params($task['params_json'] ?? []);
    $existingExternalId = trim((string)($task['external_task_id'] ?? ($params['external_task_id'] ?? '')));
    $existingResultUrl = trim((string)($params['provider_result_url'] ?? ($task['result_url'] ?? '')));
    if ($existingExternalId !== '' || $existingResultUrl !== '') {
        return ['success' => true, 'message' => '任务已提交，跳过重复 submit'];
    }

    $attempt = tasks_mark_submit_attempt($taskId);
    $provider = (string)($task['provider'] ?? '');
    $retryDelay = generation_submit_retry_delay_seconds();
    $capacity = generation_check_active_capacity($task);
    if ($capacity !== null) {
        queue_schedule_submit_job($taskId, $retryDelay);
        return ['success' => true, 'phase' => 'queued', 'message' => (string)$capacity['message']];
    }
    $keyEntry = provider_pick_key($provider);
    $keyId = (string)($keyEntry['id'] ?? '');
    $limit = provider_submit_concurrency_limit($provider);

    if (!queue_acquire_provider_slot($provider, $keyId, $limit)) {
        queue_schedule_submit_job($taskId, $retryDelay);
        return ['success' => true, 'phase' => 'queued', 'message' => 'provider 并发已满，已重新排队'];
    }

    try {
        $submitRet = generation_submit_with_provider($task, $params, $keyEntry);
        if (!$submitRet['success'] && $attempt < 2 && generation_should_retry_submit((string)($submitRet['message'] ?? ''))) {
            $submitRet = generation_submit_with_provider($task, $params, $keyEntry);
        }
    } finally {
        queue_release_provider_slot($provider, $keyId);
    }

    if (!$submitRet['success']) {
        $message = (string)($submitRet['message'] ?? '任务提交失败');
        tasks_mark_failed($taskId, $message);
        generation_refund_once($task, $params, 'generate_refund_submit_fail', '生成任务提交失败退回积分');
        generation_log('submit.failed', ['task_id' => $taskId, 'message' => $message]);
        return ['success' => false, 'message' => $message];
    }

    $params['provider_key_id'] = $keyId;
    if (!empty($submitRet['task_id'])) {
        $params['external_task_id'] = (string)$submitRet['task_id'];
        tasks_mark_submitted($taskId, (string)$submitRet['task_id'], $keyId, $params, 3);
        queue_schedule_poll_job($taskId, 3);
        generation_log('submit.queued_poll', ['task_id' => $taskId, 'external_task_id' => $submitRet['task_id']]);
        return ['success' => true, 'phase' => 'submitted'];
    }

    $resultUrl = trim((string)($submitRet['image_url'] ?? ($submitRet['result_url'] ?? '')));
    if ($resultUrl === '') {
        $message = '提交成功但未返回任务 ID 或结果地址';
        tasks_mark_failed($taskId, $message);
        generation_refund_once($task, $params, 'generate_refund_submit_fail', '生成任务提交异常退回积分');
        return ['success' => false, 'message' => $message];
    }

    $params['provider_result_url'] = $resultUrl;
    tasks_mark_syncing($taskId, $resultUrl, $params);
    queue_push_media_job($taskId);
    generation_log('submit.direct_result', ['task_id' => $taskId]);
    return ['success' => true, 'phase' => 'syncing'];
}

function generation_poll_task_by_id(string $taskId): array {
    $task = tasks_get_by_id($taskId);
    if (!$task) {
        return ['success' => false, 'message' => '任务不存在'];
    }
    if (in_array((string)$task['status'], ['completed', 'failed'], true)) {
        return ['success' => true, 'message' => '任务已结束，跳过'];
    }

    $params = tasks_decode_params($task['params_json'] ?? []);
    $attempt = tasks_mark_poll_attempt($taskId);
    $provider = (string)($task['provider'] ?? '');
    $keyEntry = null;
    $providerKeyId = trim((string)($task['provider_key_id'] ?? ''));
    if ($providerKeyId !== '') {
        $keyEntry = provider_find_key_by_id($provider, $providerKeyId);
    }

    $ret = generation_query_with_provider($task, $params, $keyEntry);
    if (!$ret['success']) {
        $maxAttempts = generation_provider_poll_max_attempts($provider);
        if ($attempt < $maxAttempts) {
            $delay = generation_poll_backoff_seconds($attempt);
            tasks_schedule_next_poll($taskId, $delay);
            queue_schedule_poll_job($taskId, $delay);
            return ['success' => true, 'phase' => 'retrying', 'message' => (string)($ret['message'] ?? '查询失败')];
        }
        $message = (string)($ret['message'] ?? '状态查询失败');
        tasks_mark_failed($taskId, $message);
        generation_refund_once($task, $params, 'generate_refund_poll_fail', '生成任务查询失败退回积分');
        return ['success' => false, 'message' => $message];
    }

    if (($ret['phase'] ?? '') === 'completed') {
        $resultUrl = trim((string)($ret['result_url'] ?? ''));
        if ($resultUrl === '') {
            $delay = generation_poll_backoff_seconds($attempt);
            tasks_schedule_next_poll($taskId, $delay);
            queue_schedule_poll_job($taskId, $delay);
            return ['success' => true, 'phase' => 'waiting_url'];
        }
        $params['provider_result_url'] = $resultUrl;
        tasks_mark_syncing($taskId, $resultUrl, $params);
        queue_push_media_job($taskId);
        return ['success' => true, 'phase' => 'syncing'];
    }

    if (($ret['phase'] ?? '') === 'failed') {
        $message = (string)($ret['message'] ?? '生成失败');
        tasks_mark_failed($taskId, $message);
        generation_refund_once($task, $params, 'generate_refund_failed', '生成失败退回积分');
        return ['success' => false, 'message' => $message];
    }

    $delay = generation_poll_backoff_seconds($attempt);
    tasks_schedule_next_poll($taskId, $delay);
    queue_schedule_poll_job($taskId, $delay);
    return ['success' => true, 'phase' => 'running'];
}

function generation_sync_media_task_by_id(string $taskId): array {
    $task = tasks_get_by_id($taskId);
    if (!$task) {
        return ['success' => false, 'message' => '任务不存在'];
    }
    if ((string)$task['status'] === 'completed') {
        return ['success' => true, 'message' => '任务已完成，跳过'];
    }

    $params = tasks_decode_params($task['params_json'] ?? []);
    $sourceUrl = trim((string)($params['provider_result_url'] ?? ($task['result_url'] ?? '')));
    if ($sourceUrl === '') {
        $message = '缺少待同步结果地址';
        tasks_mark_failed($taskId, $message, true);
        generation_refund_once($task, $params, 'generate_refund_sync_fail', '生成结果同步失败退回积分');
        return ['success' => false, 'message' => $message];
    }

    tasks_mark_sync_processing($taskId);

    $finalUrl = $sourceUrl;
    try {
        $mirrored = oss_mirror_remote_media($sourceUrl, (string)$task['type']);
        if (!empty($mirrored)) {
            $finalUrl = $mirrored;
        }
    } catch (Throwable $e) {
        generation_log('sync.mirror_failed', ['task_id' => $taskId, 'message' => $e->getMessage()]);
    }

    $pdo = tasks_get_db();
    if (!generation_asset_exists($pdo, (int)($task['user_id'] ?? 0), $finalUrl)) {
        add_asset(
            generation_make_title((string)($params['prompt'] ?? ''), $task['type'] === 'video' ? 'AI生成视频' : 'AI生成图片'),
            $finalUrl,
            (string)$task['type'],
            (string)($params['model'] ?? $task['provider_model'] ?? ''),
            (string)($params['prompt'] ?? ''),
            (int)($task['user_id'] ?? 0),
            array_merge($params, [
                'taskId' => (string)$task['id'],
                'provider' => (string)($task['provider'] ?? ''),
                'providerModel' => (string)($task['provider_model'] ?? ''),
            ])
        );
    }

    $params['result_url'] = $finalUrl;
    tasks_mark_completed($taskId, $finalUrl, $params);
    return ['success' => true, 'result_url' => $finalUrl];
}
