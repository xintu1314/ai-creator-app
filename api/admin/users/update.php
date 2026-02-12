<?php
/**
 * POST /api/admin/users/update.php
 * body: { action, userId, ... }
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
$userId = (int)($input['userId'] ?? 0);

if ($userId <= 0) {
    json_error('缺少 userId');
    exit;
}

try {
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT id, role, status FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $target = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$target) {
        json_error('用户不存在', 404);
        exit;
    }

    if ($action === 'set_status') {
        $status = trim((string)($input['status'] ?? ''));
        if (!in_array($status, ['active', 'disabled'], true)) {
            json_error('无效状态');
            exit;
        }
        if ($userId === $adminUserId && $status !== 'active') {
            json_error('不能禁用当前管理员账号');
            exit;
        }

        $upd = $pdo->prepare("UPDATE users SET status = :status WHERE id = :id");
        $upd->execute(['status' => $status, 'id' => $userId]);
        admin_write_audit_log($adminUserId, 'admin_user_set_status', 'users', (string)$userId, [
            'status' => $status,
        ]);
        json_success(['userId' => $userId, 'status' => $status], '更新成功');
        exit;
    }

    if ($action === 'set_role') {
        $role = trim((string)($input['role'] ?? ''));
        if (!in_array($role, ['user', 'admin'], true)) {
            json_error('无效角色');
            exit;
        }
        if ($userId === $adminUserId && $role !== 'admin') {
            json_error('不能降级当前管理员');
            exit;
        }

        $upd = $pdo->prepare("UPDATE users SET role = :role WHERE id = :id");
        $upd->execute(['role' => $role, 'id' => $userId]);
        admin_write_audit_log($adminUserId, 'admin_user_set_role', 'users', (string)$userId, [
            'role' => $role,
        ]);
        json_success(['userId' => $userId, 'role' => $role], '更新成功');
        exit;
    }

    if ($action === 'reset_password') {
        $newPassword = (string)($input['newPassword'] ?? '');
        if ($newPassword === '') {
            $newPassword = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'), 0, 10);
        }
        if (strlen($newPassword) < 6 || strlen($newPassword) > 64) {
            json_error('新密码长度需为6-64位');
            exit;
        }
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $upd = $pdo->prepare("UPDATE users SET password_hash = :password_hash WHERE id = :id");
        $upd->execute([
            'password_hash' => $hash,
            'id' => $userId,
        ]);
        admin_write_audit_log($adminUserId, 'admin_user_reset_password', 'users', (string)$userId, []);
        json_success([
            'userId' => $userId,
            'newPassword' => $newPassword,
        ], '密码已重置');
        exit;
    }

    json_error('不支持的 action');
} catch (Throwable $e) {
    json_error('用户操作失败：' . $e->getMessage(), 500);
}
