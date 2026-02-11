<?php
/**
 * 从数据库获取模板列表（发布模板 = 全站可见的灵感模板）
 */
require_once __DIR__ . '/../common/db.php';

const TEMPLATE_PLACEHOLDER_IMAGE = 'https://images.unsplash.com/photo-1557658017-ecd0d1e3f9c5?w=400&h=500&fit=crop';

function get_templates($type = 'image') {
    try {
        $pdo = get_db();
    } catch (Throwable $e) {
        return [];
    }

    $stmt = $pdo->prepare("
        SELECT id, title, image, model_name, model_id, content_type, category, content
        FROM publish_templates
        WHERE content_type = :type
        ORDER BY created_at DESC
    ");
    $stmt->execute(['type' => $type]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $items = [];
    foreach ($rows as $row) {
        $items[] = [
            'id' => (string)$row['id'],
            'title' => $row['title'],
            'image' => $row['image'] ?: TEMPLATE_PLACEHOLDER_IMAGE,
            'model' => !empty($row['model_name']) ? $row['model_name'] : ($row['model_id'] ?? ''),
            'modelId' => $row['model_id'] ?? '',
            'type' => $row['content_type'],
            'category' => $row['category'] ?? '',
            'prompt' => trim((string)($row['content'] ?? '')),
        ];
    }
    return $items;
}
