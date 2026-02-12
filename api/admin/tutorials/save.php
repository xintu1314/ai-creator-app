<?php
/**
 * POST /api/admin/tutorials/save.php
 * body: { id?, title, description, coverUrl, videoUrl, isPublished, sortOrder }
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
$title = trim((string)($input['title'] ?? ''));
$description = trim((string)($input['description'] ?? ''));
$coverUrl = trim((string)($input['coverUrl'] ?? ''));
$videoUrl = trim((string)($input['videoUrl'] ?? ''));
$isPublished = array_key_exists('isPublished', $input) ? (bool)$input['isPublished'] : true;
$sortOrder = (int)($input['sortOrder'] ?? 0);

if ($title === '' || $videoUrl === '') {
    json_error('标题和视频地址不能为空');
    exit;
}

try {
    $pdo = get_db();
    if ($id > 0) {
        $existsStmt = $pdo->prepare("SELECT id FROM tutorials WHERE id = :id LIMIT 1");
        $existsStmt->execute(['id' => $id]);
        $exists = $existsStmt->fetch(PDO::FETCH_ASSOC);
        if (!$exists) {
            json_error('要更新的教程不存在，请清空教程ID后新建', 404);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE tutorials
            SET title = :title,
                description = :description,
                cover_url = :cover_url,
                video_url = :video_url,
                is_published = :is_published,
                sort_order = :sort_order,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        $stmt->execute([
            'title' => $title,
            'description' => $description,
            'cover_url' => $coverUrl,
            'video_url' => $videoUrl,
            'is_published' => $isPublished,
            'sort_order' => $sortOrder,
            'id' => $id,
        ]);
        if ($stmt->rowCount() <= 0) {
            json_error('教程未发生变更或更新失败');
            exit;
        }

        admin_write_audit_log($adminUserId, 'admin_tutorial_update', 'tutorials', (string)$id, [
            'title' => $title,
            'isPublished' => $isPublished,
            'sortOrder' => $sortOrder,
        ]);
        json_success(['id' => $id], '更新成功');
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO tutorials (title, description, cover_url, video_url, is_published, sort_order, created_by)
        VALUES (:title, :description, :cover_url, :video_url, :is_published, :sort_order, :created_by)
        RETURNING id
    ");
    $stmt->execute([
        'title' => $title,
        'description' => $description,
        'cover_url' => $coverUrl,
        'video_url' => $videoUrl,
        'is_published' => $isPublished,
        'sort_order' => $sortOrder,
        'created_by' => $adminUserId,
    ]);
    $newId = (int)$stmt->fetchColumn();

    admin_write_audit_log($adminUserId, 'admin_tutorial_create', 'tutorials', (string)$newId, [
        'title' => $title,
        'isPublished' => $isPublished,
        'sortOrder' => $sortOrder,
    ]);
    json_success(['id' => $newId], '创建成功');
} catch (Throwable $e) {
    json_error('保存教程失败：' . $e->getMessage(), 500);
}
