<?php
/**
 * POST /api/points/subscribe.php
 * 开通会员（开发版：直接生效）
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

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? [];
$planId = trim((string)($input['planId'] ?? ''));

$result = points_subscribe_membership($userId, $planId);
if (!$result['success']) {
    json_error($result['message'] ?? '开通失败', 400);
    exit;
}

json_success($result, '会员开通成功');
