<?php
/**
 * GET /api/admin/users/list.php?q=&status=&role=&page=1&limit=20
 */
require_once __DIR__ . '/../../common/cors.php';
require_once __DIR__ . '/../../common/response.php';
require_once __DIR__ . '/../../common/admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed', 405);
    exit;
}

$adminUserId = admin_require();

$q = trim((string)($_GET['q'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));
$role = trim((string)($_GET['role'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

try {
    $pdo = get_db();
    $where = [];
    $params = [];

    if ($q !== '') {
        $where[] = "(u.account ILIKE :q OR u.phone ILIKE :q OR u.nickname ILIKE :q OR CAST(u.id AS TEXT) ILIKE :q)";
        $params['q'] = '%' . $q . '%';
    }
    if (in_array($status, ['active', 'disabled'], true)) {
        $where[] = "u.status = :status";
        $params['status'] = $status;
    }
    if (in_array($role, ['user', 'admin'], true)) {
        $where[] = "u.role = :role";
        $params['role'] = $role;
    }

    $whereSql = empty($where) ? '' : (' WHERE ' . implode(' AND ', $where));
    $countSql = "SELECT COUNT(*) FROM users u" . $whereSql;
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $sql = "
        SELECT
            u.id, u.account, u.phone, u.nickname, u.role, u.status, u.created_at,
            COALESCE(w.paid_balance, 0) AS paid_balance,
            COALESCE(w.bonus_balance, 0) AS bonus_balance,
            (
                SELECT COUNT(*)::INT FROM tasks t WHERE t.user_id = u.id
            ) AS task_count,
            (
                SELECT COUNT(*)::INT FROM publish_templates p WHERE p.user_id = u.id
            ) AS template_count
        FROM users u
        LEFT JOIN user_wallets w ON w.user_id = u.id
        {$whereSql}
        ORDER BY u.created_at DESC
        LIMIT {$limit} OFFSET {$offset}
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    admin_write_audit_log($adminUserId, 'admin_users_list', 'users', '', [
        'q' => $q,
        'status' => $status,
        'role' => $role,
        'page' => $page,
        'limit' => $limit,
    ]);

    json_success([
        'list' => array_map(static function (array $row): array {
            $paid = (int)$row['paid_balance'];
            $bonus = (int)$row['bonus_balance'];
            return [
                'id' => (int)$row['id'],
                'account' => (string)$row['account'],
                'phone' => (string)($row['phone'] ?? ''),
                'nickname' => (string)($row['nickname'] ?? ''),
                'role' => (string)($row['role'] ?? 'user'),
                'status' => (string)($row['status'] ?? 'active'),
                'createdAt' => (string)$row['created_at'],
                'wallet' => [
                    'paidBalance' => $paid,
                    'bonusBalance' => $bonus,
                    'totalBalance' => $paid + $bonus,
                ],
                'taskCount' => (int)$row['task_count'],
                'templateCount' => (int)$row['template_count'],
            ];
        }, $rows),
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
    ]);
} catch (Throwable $e) {
    json_error('获取用户列表失败：' . $e->getMessage(), 500);
}
