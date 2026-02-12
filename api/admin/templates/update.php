<?php
/**
 * POST /api/admin/templates/update.php
 * body: { action, id, ... }
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
$action = trim((string)($input['action'] ?? ''));
$id = (int)($input['id'] ?? 0);

if ($id <= 0) {
    json_error('缺少模板ID');
    exit;
}

try {
    $pdo = get_db();

    if ($action === 'set_review') {
        $reviewStatus = trim((string)($input['reviewStatus'] ?? ''));
        $reviewNote = trim((string)($input['reviewNote'] ?? ''));
        if (!in_array($reviewStatus, ['pending', 'approved', 'rejected'], true)) {
            json_error('无效审核状态');
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE publish_templates
            SET review_status = :review_status,
                review_note = :review_note
            WHERE id = :id
        ");
        $stmt->execute([
            'review_status' => $reviewStatus,
            'review_note' => $reviewNote,
            'id' => $id,
        ]);

        admin_write_audit_log($adminUserId, 'admin_template_set_review', 'publish_templates', (string)$id, [
            'reviewStatus' => $reviewStatus,
            'reviewNote' => $reviewNote,
        ]);
        json_success(['id' => $id, 'reviewStatus' => $reviewStatus], '审核状态已更新');
        exit;
    }

    if ($action === 'set_online') {
        $isOnline = (bool)($input['isOnline'] ?? false);
        $stmt = $pdo->prepare("
            UPDATE publish_templates
            SET is_online = :is_online
            WHERE id = :id
        ");
        $stmt->execute([
            'is_online' => $isOnline,
            'id' => $id,
        ]);
        admin_write_audit_log($adminUserId, 'admin_template_set_online', 'publish_templates', (string)$id, [
            'isOnline' => $isOnline,
        ]);
        json_success(['id' => $id, 'isOnline' => $isOnline], '上下线状态已更新');
        exit;
    }

    json_error('不支持的 action');
} catch (Throwable $e) {
    json_error('模板操作失败：' . $e->getMessage(), 500);
}
