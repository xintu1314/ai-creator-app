<?php
/**
 * POST /api/generation/status_batch.php
 * 批量查询本地任务状态
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';
require_once __DIR__ . '/../common/auth.php';
require_once __DIR__ . '/../common/tasks.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? [];
$taskIds = array_values(array_filter(array_map('strval', $input['taskIds'] ?? []), 'strlen'));
if (empty($taskIds)) {
    json_error('请提供 taskIds');
    exit;
}

try {
    $userId = auth_get_current_user_id();
    if ($userId <= 0) {
        json_error('请先登录', 401);
        exit;
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    $pdo = tasks_get_db();
    $placeholders = implode(',', array_fill(0, count($taskIds), '?'));
    $params = $taskIds;
    $params[] = $userId;
    $stmt = $pdo->prepare("
        SELECT id, status, result_url, error_message, sync_status
        FROM tasks
        WHERE id IN ($placeholders)
          AND user_id = ?
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $map = [];
    foreach ($rows as $row) {
        $status = (string)($row['status'] ?? 'pending');
        $publicStatus = in_array($status, ['completed', 'failed'], true) ? $status : 'processing';
        $resultUrl = trim((string)($row['result_url'] ?? ''));
        if ($publicStatus === 'completed' && $resultUrl === '') {
            $publicStatus = 'processing';
        }
        $map[(string)$row['id']] = [
            'taskId' => (string)$row['id'],
            'status' => $publicStatus,
            'syncStatus' => (string)($row['sync_status'] ?? 'pending'),
            'resultUrl' => $resultUrl,
            'errorMessage' => (string)($row['error_message'] ?? ''),
        ];
    }

    $items = [];
    foreach ($taskIds as $taskId) {
        $items[] = $map[$taskId] ?? [
            'taskId' => $taskId,
            'status' => 'failed',
            'syncStatus' => 'failed',
            'resultUrl' => '',
            'errorMessage' => '任务不存在',
        ];
    }

    json_success([
        'items' => $items,
    ]);
} catch (Throwable $e) {
    json_exception('批量查询任务状态失败，请稍后重试', $e, 500);
}
