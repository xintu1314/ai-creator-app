<?php
/**
 * GET /api/admin/points/ledger.php?userId=&q=&limit=
 */
require_once __DIR__ . '/../../common/cors.php';
require_once __DIR__ . '/../../common/response.php';
require_once __DIR__ . '/../../common/admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed', 405);
    exit;
}

$adminUserId = admin_require();
$userId = (int)($_GET['userId'] ?? 0);
$q = trim((string)($_GET['q'] ?? ''));
$limit = max(1, min(200, (int)($_GET['limit'] ?? 50)));

try {
    $pdo = get_db();
    $where = [];
    $params = [];
    if ($userId > 0) {
        $where[] = "l.user_id = :user_id";
        $params['user_id'] = $userId;
    }
    if ($q !== '') {
        $where[] = "(l.description ILIKE :q OR l.source ILIKE :q OR CAST(l.user_id AS TEXT) ILIKE :q)";
        $params['q'] = '%' . $q . '%';
    }
    $whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));
    $sql = "
        SELECT l.id, l.user_id, l.change_amount, l.balance_after, l.source, l.description, l.meta_json, l.created_at,
               u.account, u.phone, u.nickname
        FROM points_ledger l
        LEFT JOIN users u ON u.id = l.user_id
        {$whereSql}
        ORDER BY l.created_at DESC
        LIMIT {$limit}
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    admin_write_audit_log($adminUserId, 'admin_points_ledger_query', 'points_ledger', '', [
        'userId' => $userId,
        'q' => $q,
        'limit' => $limit,
    ]);

    json_success([
        'list' => array_map(static function (array $row): array {
            return [
                'id' => (int)$row['id'],
                'userId' => (int)$row['user_id'],
                'changeAmount' => (int)$row['change_amount'],
                'balanceAfter' => (int)$row['balance_after'],
                'source' => (string)$row['source'],
                'description' => (string)($row['description'] ?? ''),
                'meta' => json_decode((string)($row['meta_json'] ?? '{}'), true) ?: [],
                'createdAt' => (string)$row['created_at'],
                'user' => [
                    'account' => (string)($row['account'] ?? ''),
                    'phone' => (string)($row['phone'] ?? ''),
                    'nickname' => (string)($row['nickname'] ?? ''),
                ],
            ];
        }, $rows),
    ]);
} catch (Throwable $e) {
    json_error('获取积分流水失败：' . $e->getMessage(), 500);
}
