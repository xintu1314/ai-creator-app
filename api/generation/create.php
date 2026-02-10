<?php
/**
 * POST /api/generation/create.php
 * 创建生成任务（写入 tasks 表，后续接入真实生图/生视频 API）
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

$taskId = 'task_' . uniqid() . '_' . time();
$params = [
    'prompt' => $prompt,
    'model' => $input['model'] ?? '',
    'aspectRatio' => $input['aspectRatio'] ?? ($type === 'image' ? '3:4' : '16:9'),
    'mode' => $input['mode'] ?? 'single',
    'quality' => $input['quality'] ?? '2k',
    'duration' => $input['duration'] ?? 5,
];

try {
    $pdo = get_db();
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
        'message' => '任务已创建，等待接入真实生成接口',
    ]);
} catch (Throwable $e) {
    json_error('任务创建失败：' . $e->getMessage(), 500);
}
