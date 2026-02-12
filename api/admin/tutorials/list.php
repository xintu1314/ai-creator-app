<?php
/**
 * GET /api/admin/tutorials/list.php?q=&published=
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
$published = trim((string)($_GET['published'] ?? ''));
$limit = max(1, min(200, (int)($_GET['limit'] ?? 100)));

try {
    $pdo = get_db();
    $where = [];
    $params = [];
    if ($q !== '') {
        $where[] = "(title ILIKE :q OR description ILIKE :q)";
        $params['q'] = '%' . $q . '%';
    }
    if ($published === '1' || $published === '0') {
        $where[] = "is_published = :published";
        $params['published'] = $published === '1';
    }
    $whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));
    $sql = "
        SELECT id, title, description, cover_url, video_url, is_published, sort_order, created_by, created_at, updated_at
        FROM tutorials
        {$whereSql}
        ORDER BY sort_order ASC, created_at DESC
        LIMIT {$limit}
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    admin_write_audit_log($adminUserId, 'admin_tutorials_list', 'tutorials', '', [
        'q' => $q,
        'published' => $published,
        'limit' => $limit,
    ]);

    json_success([
        'list' => array_map(static function (array $row): array {
            return [
                'id' => (int)$row['id'],
                'title' => (string)$row['title'],
                'description' => (string)($row['description'] ?? ''),
                'coverUrl' => (string)($row['cover_url'] ?? ''),
                'videoUrl' => (string)($row['video_url'] ?? ''),
                'isPublished' => (bool)($row['is_published'] ?? true),
                'sortOrder' => (int)($row['sort_order'] ?? 0),
                'createdBy' => (int)($row['created_by'] ?? 0),
                'createdAt' => (string)($row['created_at'] ?? ''),
                'updatedAt' => (string)($row['updated_at'] ?? ''),
            ];
        }, $rows),
    ]);
} catch (Throwable $e) {
    json_error('获取教程列表失败：' . $e->getMessage(), 500);
}
