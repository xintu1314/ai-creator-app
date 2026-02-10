<?php
/**
 * GET /api/generation/status.php?taskId=xxx
 * 查询生成任务状态（轮询无形科技 async/detail 接口）
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';
require_once __DIR__ . '/../common/db.php';
require_once __DIR__ . '/../data/assets.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed', 405);
    exit;
}

$taskId = trim($_GET['taskId'] ?? '');
if (empty($taskId)) {
    json_error('请提供 taskId');
    exit;
}

try {
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT id, type, status, params_json, result_url, error_message FROM tasks WHERE id = ?");
    $stmt->execute([$taskId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        json_error('任务不存在');
        exit;
    }

    $params = json_decode($row['params_json'] ?? '{}', true) ?? [];
    $externalId = $params['external_task_id'] ?? null;

$ensureAssetExists = function (array $taskRow, array $taskParams): void {
    $imageUrl = trim((string)($taskRow['result_url'] ?? ''));
    if ($imageUrl === '') return;

    $prompt = trim((string)($taskParams['prompt'] ?? ''));
    $model = trim((string)($taskParams['model'] ?? 'AI'));
    $title = $prompt !== '' ? $prompt : ('AI生成' . ($taskRow['type'] === 'video' ? '视频' : '图片'));
    if (function_exists('mb_substr')) {
        $title = mb_substr($title, 0, 60);
    } else {
        $title = substr($title, 0, 60);
    }

    try {
        $pdo = get_db();
        $check = $pdo->prepare("SELECT id FROM assets WHERE image = :image AND type = :type LIMIT 1");
        $check->execute([
            'image' => $imageUrl,
            'type' => $taskRow['type'],
        ]);
        $exists = $check->fetch(PDO::FETCH_ASSOC);
        if (!$exists) {
            add_asset($title, $imageUrl, $taskRow['type'], $model, $prompt, 0);
        }
    } catch (Throwable $e) {
        // 资产入库失败不影响状态接口返回
    }
};

    // 已完成 → 直接返回
    if ($row['status'] === 'completed') {
        $ensureAssetExists($row, $params);
        json_success([
            'taskId'    => $row['id'],
            'status'    => 'completed',
            'resultUrl' => $row['result_url'],
        ]);
        exit;
    }

    // 已失败 → 直接返回
    if ($row['status'] === 'failed') {
        json_success([
            'taskId'       => $row['id'],
            'status'       => 'failed',
            'errorMessage' => $row['error_message'] ?? '生成失败',
        ]);
        exit;
    }

    // 图片任务：轮询无形科技接口
    if ($row['type'] === 'image' && $externalId) {
        require_once __DIR__ . '/../common/wuyinkeji.php';
        if (function_exists('wuyinkeji_log')) {
            wuyinkeji_log('status.poll_begin', [
                'local_task_id' => $taskId,
                'external_task_id' => (string)$externalId,
                'db_status' => $row['status'],
            ]);
        }
        $result = wuyinkeji_query_image($externalId);
        if (function_exists('wuyinkeji_log')) {
            wuyinkeji_log('status.poll_result', [
                'local_task_id' => $taskId,
                'external_task_id' => (string)$externalId,
                'query_result' => $result,
            ]);
        }

        if (!$result['success']) {
            json_error($result['message'] ?? '查询失败');
            exit;
        }

        $status = $result['status'];

        if ($status === 2) {
            // 成功
            $imageUrl = $result['image_url'] ?? '';
            $stmt = $pdo->prepare("
                UPDATE tasks
                SET status = 'completed', result_url = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND status <> 'completed'
            ");
            $stmt->execute([$imageUrl, $taskId]);
            $justCompleted = $stmt->rowCount() > 0;

            // 首次完成时写入资产库；重复轮询不重复入库
            if ($justCompleted && !empty($imageUrl)) {
                $row['result_url'] = $imageUrl;
                $ensureAssetExists($row, $params);
            }
            if (function_exists('wuyinkeji_log')) {
                wuyinkeji_log('status.db_updated_completed', [
                    'local_task_id' => $taskId,
                    'external_task_id' => (string)$externalId,
                    'image_url' => $imageUrl,
                ]);
            }
            json_success([
                'taskId'    => $taskId,
                'status'    => 'completed',
                'resultUrl' => $imageUrl,
            ]);
        } elseif ($status === 3) {
            // 失败
            $errMsg = $result['fail_reason'] ?: '生成失败';
            $stmt = $pdo->prepare("UPDATE tasks SET status = 'failed', error_message = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$errMsg, $taskId]);
            if (function_exists('wuyinkeji_log')) {
                wuyinkeji_log('status.db_updated_failed', [
                    'local_task_id' => $taskId,
                    'external_task_id' => (string)$externalId,
                    'error_message' => $errMsg,
                ]);
            }
            json_success([
                'taskId'       => $taskId,
                'status'       => 'failed',
                'errorMessage' => $errMsg,
            ]);
        } else {
            // 0=排队中 1=生成中
            json_success([
                'taskId' => $taskId,
                'status' => 'processing',
            ]);
        }
    } else {
        json_success([
            'taskId' => $taskId,
            'status' => $row['status'],
        ]);
    }
} catch (Throwable $e) {
    json_error('查询失败：' . $e->getMessage(), 500);
}
