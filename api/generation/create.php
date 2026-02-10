<?php
/**
 * POST /api/generation/create.php
 * 创建生成任务（接入 banana、banana pro 图片接口，豆包视频待接入）
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';
require_once __DIR__ . '/../common/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? [];

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
    $params['referenceImageUrls'] = array_values(array_filter($input['referenceImageUrls'], 'is_string'));
}
if (!empty($input['firstFrameUrl'])) {
    $params['firstFrameUrl'] = $input['firstFrameUrl'];
}
if (!empty($input['lastFrameUrl'])) {
    $params['lastFrameUrl'] = $input['lastFrameUrl'];
}

// 映射前端参数到 API 格式
$mapRatio = function ($r) {
    $m = ['9:21' => '9:16'];
    return $m[$r] ?? $r;
};
$mapQuality = function ($q) {
    $q = strtolower($q ?? '2k');
    return in_array($q, ['2k', '4k']) ? strtoupper($q) : '2K';
};

try {
    $pdo = get_db();

    if ($type === 'image' && in_array($model, ['banana', 'banana pro', 'banana-pro'])) {
        // 图片：banana / banana pro
        require_once __DIR__ . '/../common/wuyinkeji.php';

        $modelId = ($model === 'banana-pro' || $model === 'banana pro') ? 'banana_pro' : 'banana';
        $options = [
            'imageSize' => $mapQuality($params['quality']),
            'aspectRatio' => $mapRatio($params['aspectRatio']),
            'urls' => $params['referenceImageUrls'] ?? [],
            'count' => $params['count'],
        ];

        // 张数逻辑：接口本身不支持 quantity 时，这里按张数循环提交单张任务
        $requestedCount = max(1, min(4, (int)($params['count'] ?? 1)));
        $localTaskIds = [];
        $submittedCount = 0;
        $stmt = $pdo->prepare("
            INSERT INTO tasks (id, user_id, type, status, params_json)
            VALUES (:id, 0, :type, 'processing', :params)
        ");

        for ($i = 0; $i < $requestedCount; $i++) {
            $loopTaskId = $i === 0 ? $taskId : ('task_' . uniqid() . '_' . time() . '_' . $i);
            $result = wuyinkeji_submit_image($modelId, $prompt, $options);
            if (function_exists('wuyinkeji_log')) {
                wuyinkeji_log('create.submit_result', [
                    'local_task_id' => $loopTaskId,
                    'model' => $model,
                    'model_id' => $modelId,
                    'options' => $options,
                    'submit_index' => $i + 1,
                    'submit_total' => $requestedCount,
                    'submit_result' => $result,
                ]);
            }

            if (!$result['success']) {
                // 第一张都失败 -> 直接报错；部分失败 -> 继续返回已提交结果
                if ($submittedCount === 0) {
                    json_error($result['message'] ?? '提交失败');
                    exit;
                }
                break;
            }

            $loopParams = $params;
            $loopParams['external_task_id'] = $result['task_id'];
            $loopParams['count_requested'] = $requestedCount;
            $loopParams['count_index'] = $i + 1;
            $loopParams['count_total_submitted'] = $requestedCount;

            $stmt->execute([
                'id' => $loopTaskId,
                'type' => $type,
                'params' => json_encode($loopParams, JSON_UNESCAPED_UNICODE),
            ]);

            $localTaskIds[] = $loopTaskId;
            $submittedCount++;
            if (function_exists('wuyinkeji_log')) {
                wuyinkeji_log('create.db_inserted', [
                    'local_task_id' => $loopTaskId,
                    'external_task_id' => $loopParams['external_task_id'] ?? null,
                    'status' => 'processing',
                    'params' => $loopParams,
                ]);
            }
        }

        if ($submittedCount === 0) {
            json_error('任务提交失败');
            exit;
        }

        $respMsg = $submittedCount > 1
            ? "已提交 {$submittedCount} 个任务，请在资产中心查看全部结果"
            : '任务已提交，请在资产中心查看生成结果';

        json_success([
            'taskId' => $localTaskIds[0],
            'taskIds' => $localTaskIds,
            'submittedCount' => $submittedCount,
            'status' => 'processing',
            'message' => $respMsg,
        ]);
    } elseif ($type === 'video' && in_array($model, ['豆包视频', 'doubao-video'])) {
        // 视频：豆包视频（待接入）
        $params['external_task_id'] = null;

        $stmt = $pdo->prepare("
            INSERT INTO tasks (id, user_id, type, status, params_json)
            VALUES (:id, 0, :type, 'pending', :params)
        ");
        $stmt->execute([
            'id' => $taskId,
            'type' => $type,
            'params' => json_encode($params, JSON_UNESCAPED_UNICODE),
        ]);

        json_success([
            'taskId' => $taskId,
            'status' => 'pending',
            'message' => '豆包视频接口待接入，任务已创建',
        ]);
    } else {
        json_error('不支持的模型：' . $model);
    }
} catch (Throwable $e) {
    json_error('任务创建失败：' . $e->getMessage(), 500);
}
