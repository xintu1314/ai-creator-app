<?php
/**
 * POST /api/admin/points/adjust.php
 * body: { userId, delta, reason }
 */
require_once __DIR__ . '/../../common/cors.php';
require_once __DIR__ . '/../../common/response.php';
require_once __DIR__ . '/../../common/admin.php';
require_once __DIR__ . '/../../common/points.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
    exit;
}

$adminUserId = admin_require();
$input = admin_get_json_input();
$userId = (int)($input['userId'] ?? 0);
$delta = (int)($input['delta'] ?? 0);
$reason = trim((string)($input['reason'] ?? ''));

if ($userId <= 0) {
    json_error('缺少 userId');
    exit;
}
if ($delta === 0) {
    json_error('delta 不能为0');
    exit;
}
if ($reason === '') {
    json_error('请填写调整原因');
    exit;
}

$ret = points_admin_adjust_paid($userId, $delta, '管理员调整积分：' . $reason, [
    'adminUserId' => $adminUserId,
    'reason' => $reason,
    'delta' => $delta,
]);

if (!$ret['success']) {
    json_error($ret['message'] ?? '积分调整失败');
    exit;
}

admin_write_audit_log($adminUserId, 'admin_points_adjust', 'users', (string)$userId, [
    'delta' => $delta,
    'reason' => $reason,
    'wallet' => $ret['wallet'] ?? null,
]);

json_success([
    'userId' => $userId,
    'delta' => $delta,
    'wallet' => $ret['wallet'] ?? null,
], '积分调整成功');
