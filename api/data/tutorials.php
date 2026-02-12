<?php
require_once __DIR__ . '/../common/db.php';

function get_tutorials(bool $includeUnpublished = false): array {
    try {
        $pdo = get_db();
        $sql = "
            SELECT id, title, description, cover_url, video_url, is_published, sort_order, created_at, updated_at
            FROM tutorials
        ";
        $params = [];
        if (!$includeUnpublished) {
            $sql .= " WHERE is_published = true";
        }
        $sql .= " ORDER BY sort_order ASC, created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }

    return array_map(static function (array $row): array {
        return [
            'id' => (int)$row['id'],
            'title' => (string)$row['title'],
            'description' => (string)($row['description'] ?? ''),
            'coverUrl' => (string)($row['cover_url'] ?? ''),
            'videoUrl' => (string)($row['video_url'] ?? ''),
            'isPublished' => (bool)($row['is_published'] ?? true),
            'sortOrder' => (int)($row['sort_order'] ?? 0),
            'createdAt' => (string)($row['created_at'] ?? ''),
            'updatedAt' => (string)($row['updated_at'] ?? ''),
        ];
    }, $rows);
}
