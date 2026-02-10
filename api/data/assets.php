<?php
/**
 * 从数据库获取资产列表
 */
require_once __DIR__ . '/../common/db.php';

function get_assets($filter = 'all', $page = 1, $limit = 50) {
    try {
        $pdo = get_db();
    } catch (Throwable $e) {
        return [];
    }

    $sql = "SELECT id, title, image, type, model, prompt, created_at FROM assets WHERE 1=1";
    $params = [];
    if ($filter === 'image') {
        $sql .= " AND type = :type";
        $params['type'] = 'image';
    } elseif ($filter === 'video') {
        $sql .= " AND type = :type";
        $params['type'] = 'video';
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
            'image' => $row['image'],
            'type' => $row['type'],
            'model' => $row['model'],
            'prompt' => $row['prompt'] ?? '',
            'createdAt' => date('Y-m-d H:i', strtotime($row['created_at'])),
        ];
    }
    return $items;
}

/**
 * 添加资产（供生成完成后调用）
 */
function add_asset($title, $imageUrl, $type, $model, $prompt, $userId = 0): ?int {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("
            INSERT INTO assets (user_id, title, image, type, model, prompt)
            VALUES (:user_id, :title, :image, :type, :model, :prompt)
            RETURNING id
        ");
        $stmt->execute([
            'user_id' => $userId,
            'title' => $title,
            'image' => $imageUrl,
            'type' => $type,
            'model' => $model,
            'prompt' => $prompt,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['id'];
    } catch (Throwable $e) {
        return null;
    }
}
