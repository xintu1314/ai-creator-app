<?php
/**
 * 从数据库获取模板列表（发布模板 = 全站可见的灵感模板）
 */
require_once __DIR__ . '/../common/db.php';

const TEMPLATE_PLACEHOLDER_IMAGE = '/assets/images/template-placeholder.svg';

function get_templates($type = 'image') {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("
            SELECT id, title, image, model_name, model_id, content_type, category, content, review_status, is_online
            FROM publish_templates
            WHERE content_type = :type
              AND review_status = 'approved'
              AND is_online = true
            ORDER BY created_at DESC
        ");
        $stmt->execute(['type' => $type]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        // 兼容旧库：publish_templates 可能尚未包含 image/model_name 列
        try {
            $stmt = $pdo->prepare("
                SELECT id, title, model_id, content_type, category, content
                FROM publish_templates
                WHERE content_type = :type
                ORDER BY created_at DESC
            ");
            $stmt->execute(['type' => $type]);
            $legacyRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $rows = [];
            foreach ($legacyRows as $row) {
                $row['image'] = '';
                $row['model_name'] = $row['model_id'] ?? '';
                $rows[] = $row;
            }
        } catch (Throwable $e2) {
            return [];
        }
    }

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
            'reviewStatus' => (string)($row['review_status'] ?? 'approved'),
            'isOnline' => (bool)($row['is_online'] ?? true),
        ];
    }
    return $items;
}

/**
 * 获取某个用户的发布模板历史
 */
function get_templates_by_user(int $userId, string $type = 'all', int $limit = 50): array {
    if ($userId <= 0) return [];
    $limit = max(1, min(200, $limit));

    try {
        $pdo = get_db();
        $sql = "
            SELECT id, title, image, model_name, model_id, content_type, category, content
            FROM publish_templates
            WHERE user_id = :user_id
        ";
        $params = ['user_id' => $userId];
        if ($type === 'image' || $type === 'video') {
            $sql .= " AND content_type = :type";
            $params['type'] = $type;
        }
        $sql .= " ORDER BY created_at DESC LIMIT " . (int)$limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        // 兼容旧库：可能缺 image/model_name 列
        try {
            $sql = "
                SELECT id, title, model_id, content_type, category, content
                FROM publish_templates
                WHERE user_id = :user_id
            ";
            $params = ['user_id' => $userId];
            if ($type === 'image' || $type === 'video') {
                $sql .= " AND content_type = :type";
                $params['type'] = $type;
            }
            $sql .= " ORDER BY created_at DESC LIMIT " . (int)$limit;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $legacyRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $rows = [];
            foreach ($legacyRows as $row) {
                $row['image'] = '';
                $row['model_name'] = $row['model_id'] ?? '';
                $rows[] = $row;
            }
        } catch (Throwable $e2) {
            return [];
        }
    }

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

/**
 * 获取当前用户的发布模板历史
 */
function get_user_templates($userId, $type = 'all') {
    $uid = (int)$userId;
    if ($uid <= 0) return [];

    try {
        $pdo = get_db();
    } catch (Throwable $e) {
        return [];
    }

    $sql = "
        SELECT id, title, image, model_name, model_id, content_type, category, content
        FROM publish_templates
        WHERE user_id = :user_id
    ";
    $params = ['user_id' => $uid];
    if (in_array($type, ['image', 'video'], true)) {
        $sql .= " AND content_type = :type";
        $params['type'] = $type;
    }
    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
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
