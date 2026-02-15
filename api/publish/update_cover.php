<?php
/**
 * POST /api/publish/update_cover.php
 * 更新模板封面（仅限本人模板）
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';
require_once __DIR__ . '/../common/db.php';
require_once __DIR__ . '/../common/auth.php';

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
$id = (int)($input['id'] ?? 0);
$image = trim((string)($input['image'] ?? ''));

if ($id <= 0) {
    json_error('缺少模板ID');
    exit;
}

if ($image === '') {
    json_error('请提供封面地址');
    exit;
}

// 仅允许 http/https
if (!preg_match('#^https?://#i', $image)) {
    json_error('封面地址格式无效');
    exit;
}

try {
    $pdo = get_db();
    $stmt = $pdo->prepare("
        UPDATE publish_templates
        SET image = :image
        WHERE id = :id AND user_id = :user_id
    ");
    $stmt->execute([
        'image' => $image,
        'id' => $id,
        'user_id' => $userId,
    ]);

    if ($stmt->rowCount() === 0) {
        json_error('模板不存在或无权修改');
        exit;
    }

    json_success(['id' => $id, 'image' => $image], '封面已更新');
} catch (Throwable $e) {
    json_exception('更新失败，请稍后重试', $e, 500);
}
