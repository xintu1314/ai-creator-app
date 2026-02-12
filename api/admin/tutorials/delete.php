<?php
/**
 * POST /api/admin/tutorials/delete.php
 * body: { id }
 */
require_once __DIR__ . '/../../common/cors.php';
require_once __DIR__ . '/../../common/response.php';
require_once __DIR__ . '/../../common/admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
    exit;
}

$adminUserId = admin_require();
$input = admin_get_json_input();
$id = (int)($input['id'] ?? 0);
if ($id <= 0) {
    json_error('缺少教程ID');
    exit;
}

try {
    $pdo = get_db();
    $stmt = $pdo->prepare("DELETE FROM tutorials WHERE id = :id");
    $stmt->execute(['id' => $id]);
    if ($stmt->rowCount() === 0) {
        json_error('教程不存在', 404);
        exit;
    }
    admin_write_audit_log($adminUserId, 'admin_tutorial_delete', 'tutorials', (string)$id, []);
    json_success(['id' => $id], '删除成功');
} catch (Throwable $e) {
    json_error('删除教程失败：' . $e->getMessage(), 500);
}
