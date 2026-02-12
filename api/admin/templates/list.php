<?php
/**
 * GET /api/admin/templates/list.php?type=&reviewStatus=&isOnline=&q=&limit=
 */
require_once __DIR__ . '/../../common/cors.php';
require_once __DIR__ . '/../../common/response.php';
require_once __DIR__ . '/../../common/admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed', 405);
    exit;
}

$adminUserId = admin_require();
$type = trim((string)($_GET['type'] ?? ''));
$reviewStatus = trim((string)($_GET['reviewStatus'] ?? ''));
$isOnlineRaw = trim((string)($_GET['isOnline'] ?? ''));
$q = trim((string)($_GET['q'] ?? ''));
$limit = max(1, min(300, (int)($_GET['limit'] ?? 100)));

try {
    $pdo = get_db();
    $where = [];
    $params = [];
    if (in_array($type, ['image', 'video'], true)) {
        $where[] = "p.content_type = :type";
        $params['type'] = $type;
    }
    if (in_array($reviewStatus, ['pending', 'approved', 'rejected'], true)) {
        $where[] = "p.review_status = :review_status";
        $params['review_status'] = $reviewStatus;
    }
    if ($isOnlineRaw === '1' || $isOnlineRaw === '0') {
        $where[] = "p.is_online = :is_online";
        $params['is_online'] = $isOnlineRaw === '1';
    }
    if ($q !== '') {
        $where[] = "(p.title ILIKE :q OR p.content ILIKE :q OR p.model_id ILIKE :q OR u.account ILIKE :q OR u.nickname ILIKE :q)";
        $params['q'] = '%' . $q . '%';
    }
    $whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

    $sql = "
        SELECT
            p.id, p.user_id, p.content_type, p.model_id, p.model_name, p.category, p.title, p.content, p.image,
            p.review_status, p.is_online, p.review_note, p.created_at,
            u.account, u.nickname, u.phone
        FROM publish_templates p
        LEFT JOIN users u ON u.id = p.user_id
        {$whereSql}
        ORDER BY p.created_at DESC
        LIMIT {$limit}
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    admin_write_audit_log($adminUserId, 'admin_templates_list', 'publish_templates', '', [
        'type' => $type,
        'reviewStatus' => $reviewStatus,
        'isOnline' => $isOnlineRaw,
        'q' => $q,
        'limit' => $limit,
    ]);

    json_success([
        'list' => array_map(static function (array $row): array {
            return [
                'id' => (int)$row['id'],
                'userId' => (int)$row['user_id'],
                'type' => (string)$row['content_type'],
                'modelId' => (string)($row['model_id'] ?? ''),
                'modelName' => (string)($row['model_name'] ?? ''),
                'category' => (string)($row['category'] ?? ''),
                'title' => (string)$row['title'],
                'prompt' => (string)($row['content'] ?? ''),
                'image' => (string)($row['image'] ?? ''),
                'reviewStatus' => (string)($row['review_status'] ?? 'approved'),
                'isOnline' => (bool)($row['is_online'] ?? true),
                'reviewNote' => (string)($row['review_note'] ?? ''),
                'createdAt' => (string)($row['created_at'] ?? ''),
                'author' => [
                    'account' => (string)($row['account'] ?? ''),
                    'nickname' => (string)($row['nickname'] ?? ''),
                    'phone' => (string)($row['phone'] ?? ''),
                ],
            ];
        }, $rows),
    ]);
} catch (Throwable $e) {
    json_error('获取模板列表失败：' . $e->getMessage(), 500);
}
