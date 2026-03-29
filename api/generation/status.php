<?php
/**
 * GET /api/generation/status.php?taskId=xxx
 * 仅查询本地任务状态
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';
require_once __DIR__ . '/../common/auth.php';
require_once __DIR__ . '/../common/tasks.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed', 405);
    exit;
}

$taskId = trim($_GET['taskId'] ?? '');
if ($taskId === '') {
    json_error('请提供 taskId');
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

    $row = tasks_get_by_id($taskId, $userId);
    if (!$row) {
        json_error('任务不存在');
        exit;
    }

    $status = (string)($row['status'] ?? 'pending');
    $publicStatus = in_array($status, ['completed', 'failed'], true) ? $status : 'processing';
    $resultUrl = trim((string)($row['result_url'] ?? ''));
    if ($publicStatus === 'completed' && $resultUrl === '') {
        $publicStatus = 'processing';
    }

    json_success([
        'taskId' => (string)$row['id'],
        'status' => $publicStatus,
        'syncStatus' => (string)($row['sync_status'] ?? 'pending'),
        'resultUrl' => $resultUrl,
        'errorMessage' => (string)($row['error_message'] ?? ''),
    ]);
} catch (Throwable $e) {
    json_exception('查询任务状态失败，请稍后重试', $e, 500);
}
