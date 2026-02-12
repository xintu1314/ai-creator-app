<?php
/**
 * GET /api/admin/dashboard/overview.php
 */
require_once __DIR__ . '/../../common/cors.php';
require_once __DIR__ . '/../../common/response.php';
require_once __DIR__ . '/../../common/admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed', 405);
    exit;
}

$adminUserId = admin_require();

try {
    $pdo = get_db();

    $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $todayUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= CURRENT_DATE")->fetchColumn();
    $totalTasks = (int)$pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
    $todayTasks = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE created_at >= CURRENT_DATE")->fetchColumn();
    $todayCompleted = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE created_at >= CURRENT_DATE AND status = 'completed'")->fetchColumn();
    $todayFailed = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE created_at >= CURRENT_DATE AND status = 'failed'")->fetchColumn();
    $totalTemplates = (int)$pdo->query("SELECT COUNT(*) FROM publish_templates")->fetchColumn();
    $todayTemplates = (int)$pdo->query("SELECT COUNT(*) FROM publish_templates WHERE created_at >= CURRENT_DATE")->fetchColumn();
    $todayPointsConsume = (int)$pdo->query("
        SELECT COALESCE(SUM(ABS(change_amount)), 0)
        FROM points_ledger
        WHERE created_at >= CURRENT_DATE
          AND change_amount < 0
          AND source = 'generate_consume'
    ")->fetchColumn();

    $taskTypeStmt = $pdo->query("
        SELECT type, COUNT(*)::INT AS cnt
        FROM tasks
        WHERE created_at >= CURRENT_DATE
        GROUP BY type
        ORDER BY cnt DESC
    ");
    $taskTypeRows = $taskTypeStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $modelStmt = $pdo->query("
        SELECT COALESCE(params_json->>'model', 'unknown') AS model, COUNT(*)::INT AS cnt
        FROM tasks
        WHERE created_at >= CURRENT_DATE
        GROUP BY model
        ORDER BY cnt DESC
        LIMIT 8
    ");
    $modelRows = $modelStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $recentUsersStmt = $pdo->query("
        SELECT id, account, phone, nickname, role, status, created_at
        FROM users
        ORDER BY created_at DESC
        LIMIT 8
    ");
    $recentUsers = $recentUsersStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    admin_write_audit_log($adminUserId, 'admin_dashboard_overview', 'dashboard', '', []);

    json_success([
        'kpis' => [
            'totalUsers' => $totalUsers,
            'todayUsers' => $todayUsers,
            'totalTasks' => $totalTasks,
            'todayTasks' => $todayTasks,
            'todayCompleted' => $todayCompleted,
            'todayFailed' => $todayFailed,
            'totalTemplates' => $totalTemplates,
            'todayTemplates' => $todayTemplates,
            'todayPointsConsume' => $todayPointsConsume,
        ],
        'todayTaskTypeDistribution' => array_map(static function (array $row): array {
            return [
                'type' => (string)$row['type'],
                'count' => (int)$row['cnt'],
            ];
        }, $taskTypeRows),
        'todayModelTop' => array_map(static function (array $row): array {
            return [
                'model' => (string)$row['model'],
                'count' => (int)$row['cnt'],
            ];
        }, $modelRows),
        'recentUsers' => array_map(static function (array $row): array {
            return [
                'id' => (int)$row['id'],
                'account' => (string)$row['account'],
                'phone' => (string)($row['phone'] ?? ''),
                'nickname' => (string)($row['nickname'] ?? ''),
                'role' => (string)($row['role'] ?? 'user'),
                'status' => (string)($row['status'] ?? 'active'),
                'createdAt' => (string)$row['created_at'],
            ];
        }, $recentUsers),
    ]);
} catch (Throwable $e) {
    json_error('获取运营看板失败：' . $e->getMessage(), 500);
}
