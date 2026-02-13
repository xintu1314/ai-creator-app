<?php
/**
 * POST /api/points/checkin.php
 * 每日签到领积分（当天有效，次日清零）
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';
require_once __DIR__ . '/../common/auth.php';
require_once __DIR__ . '/../common/points.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
    exit;
}

$userId = auth_get_current_user_id();
if ($userId <= 0) {
    json_error('请先登录', 401);
    exit;
}

try {
    $ret = points_daily_checkin($userId);
    if (empty($ret['success'])) {
        json_error((string)($ret['message'] ?? '签到失败'));
        exit;
    }
    json_success([
        'wallet' => $ret['wallet'] ?? null,
    ], (string)($ret['message'] ?? '签到成功'));
} catch (Throwable $e) {
    json_exception('签到失败，请稍后重试', $e, 500);
}
