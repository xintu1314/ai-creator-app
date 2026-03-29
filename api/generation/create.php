<?php
/**
 * POST /api/generation/create.php
 * 创建生成任务（快速入队版）
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';
require_once __DIR__ . '/../common/auth.php';
require_once __DIR__ . '/../common/points.php';
require_once __DIR__ . '/../common/tasks.php';
require_once __DIR__ . '/../common/queue.php';
require_once __DIR__ . '/../common/generation_jobs.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? [];

$is_allowed_media_url = static function ($url): bool {
    if (!is_string($url)) return false;
    $url = trim($url);
    if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) return false;
    $parts = parse_url($url);
    $scheme = strtolower((string)($parts['scheme'] ?? ''));
    $host = strtolower((string)($parts['host'] ?? ''));
    if ($scheme !== 'https' || $host === '') return false;
    if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') return false;
    if (preg_match('/^\d+\.\d+\.\d+\.\d+$/', $host) === 1) {
        // Block direct IPv4 to avoid private/internal address probing.
        return false;
    }
    return true;
};

$prompt = trim($input['prompt'] ?? '');
if (empty($prompt)) {
    json_error('请输入提示词');
    exit;
}

$type = $input['type'] ?? 'image';
if (!in_array($type, ['image', 'video'])) {
    json_error('无效的 type，应为 image 或 video');
    exit;
}

$model = $input['model'] ?? '';
$taskId = 'task_' . uniqid() . '_' . time();
$params = [
    'prompt' => $prompt,
    'model' => $model,
    'aspectRatio' => $input['aspectRatio'] ?? ($type === 'image' ? '3:4' : '16:9'),
    'mode' => $input['mode'] ?? 'single',
    'quality' => $input['quality'] ?? '2k',
    'count' => max(1, min(4, (int)($input['count'] ?? 1))),
    'duration' => $input['duration'] ?? 5,
];
if (!empty($input['referenceImageUrls']) && is_array($input['referenceImageUrls'])) {
    $params['referenceImageUrls'] = array_values(array_filter($input['referenceImageUrls'], $is_allowed_media_url));
    if (count($params['referenceImageUrls']) !== count($input['referenceImageUrls'])) {
        json_error('参考图地址不合法，仅支持 HTTPS 公网地址');
        exit;
    }
}
if (!empty($input['firstFrameUrl'])) {
    if (!$is_allowed_media_url($input['firstFrameUrl'])) {
        json_error('首帧地址不合法，仅支持 HTTPS 公网地址');
        exit;
    }
    $params['firstFrameUrl'] = $input['firstFrameUrl'];
}
if (!empty($input['lastFrameUrl'])) {
    if (!$is_allowed_media_url($input['lastFrameUrl'])) {
        json_error('尾帧地址不合法，仅支持 HTTPS 公网地址');
        exit;
    }
    $params['lastFrameUrl'] = $input['lastFrameUrl'];
}

try {
    $userId = auth_get_current_user_id();
    if ($userId <= 0) {
        json_error('请先登录后再生成', 401);
        exit;
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    try {
        $spec = generation_resolve_spec($type, (string)$model);
    } catch (RuntimeException $e) {
        json_error($e->getMessage());
        exit;
    }
    $requestedCount = $type === 'image' ? max(1, min(4, (int)($params['count'] ?? 1))) : 1;
    $pointsPerTask = $type === 'image'
        ? points_calculate_image_points((string)$spec['provider_model'], (string)$params['quality'])
        : points_calculate_video_points((string)$spec['provider_model'], max(1, min(30, (int)($params['duration'] ?? 5))));

    if ($type === 'video') {
        $params['duration'] = max(1, min(30, (int)($params['duration'] ?? 5)));
        if ((string)$spec['provider'] === 'wuyinkeji') {
            $params['size'] = strtolower((string)($params['quality'] ?? 'standard')) === 'high' ? '1080p' : '720p';
        }
        if ((string)$spec['provider'] === 'doubao') {
            $params['cameraFixed'] = (bool)($input['cameraFixed'] ?? false);
            $params['watermark'] = array_key_exists('watermark', $input) ? (bool)$input['watermark'] : true;
        }
    }

    $taskIds = [];
    $submittedCount = 0;
    $lastConsumeError = '';

    for ($i = 0; $i < $requestedCount; $i++) {
        $loopTaskId = $i === 0 ? $taskId : ('task_' . uniqid() . '_' . time() . '_' . $i);
        $consumeRet = points_consume(
            $userId,
            $pointsPerTask,
            'generate_consume',
            $type === 'video' ? '视频生成扣费' : '图片生成扣费',
            [
                'taskId' => $loopTaskId,
                'model' => (string)$spec['provider_model'],
                'quality' => strtolower((string)($params['quality'] ?? '')),
                'duration' => (int)($params['duration'] ?? 0),
                'index' => $i + 1,
                'requestedCount' => $requestedCount,
            ]
        );
        if (!$consumeRet['success']) {
            $lastConsumeError = $consumeRet['message'] ?? '积分不足';
            if ($submittedCount === 0) {
                json_error($lastConsumeError);
                exit;
            }
            break;
        }

        $loopParams = $params;
        $loopParams['model'] = (string)$model;
        $loopParams['provider'] = (string)$spec['provider'];
        $loopParams['provider_model'] = (string)$spec['provider_model'];
        $loopParams['count_requested'] = $requestedCount;
        $loopParams['count_index'] = $i + 1;
        $loopParams['points_charged'] = $pointsPerTask;
        $loopParams['points_charged_paid'] = (int)($consumeRet['paidUsed'] ?? $pointsPerTask);
        $loopParams['points_charged_bonus'] = (int)($consumeRet['bonusUsed'] ?? 0);

        $taskInserted = false;
        try {
            tasks_insert_queued([
                'id' => $loopTaskId,
                'user_id' => $userId,
                'type' => $type,
                'status' => 'pending',
                'provider' => (string)$spec['provider'],
                'provider_model' => (string)$spec['provider_model'],
                'params_json' => $loopParams,
                'sync_status' => 'pending',
            ]);
            $taskInserted = true;
            queue_push_submit_job($loopTaskId);
        } catch (Throwable $queueErr) {
            if ($taskInserted) {
                tasks_mark_failed($loopTaskId, '队列服务不可用，请稍后重试');
            }
            points_refund_to_paid(
                $userId,
                $pointsPerTask,
                'generate_refund_queue_fail',
                '任务入队失败退回积分',
                [
                    'taskId' => $loopTaskId,
                    'model' => (string)$spec['provider_model'],
                ]
            );
            $lastConsumeError = '任务入队失败，请稍后重试';
            if ($submittedCount === 0) {
                json_error($lastConsumeError);
                exit;
            }
            break;
        }

        $taskIds[] = $loopTaskId;
        $submittedCount++;
    }

    if ($submittedCount === 0) {
        json_error($lastConsumeError ?: '任务提交失败');
        exit;
    }

    $wallet = points_get_wallet_summary($userId);
    $respMsg = $submittedCount > 1
        ? "已提交 {$submittedCount} 个任务，已进入生成队列"
        : '任务已提交，已进入生成队列';

    $data = [
        'taskId' => $taskIds[0],
        'taskIds' => $taskIds,
        'submittedCount' => $submittedCount,
        'status' => 'processing',
        'message' => $respMsg,
        'wallet' => $wallet,
    ];
    if ($type === 'image') {
        $data['pointsPerImage'] = $pointsPerTask;
    } else {
        $data['pointsPerVideo'] = $pointsPerTask;
    }
    json_success($data);
} catch (Throwable $e) {
    json_exception('任务创建失败，请稍后重试', $e, 500);
}
