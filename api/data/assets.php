<?php
/**
 * 从数据库获取资产列表
 */
require_once __DIR__ . '/../common/db.php';

function assets_ensure_meta_column(PDO $pdo): void {
    static $checked = false;
    if ($checked) return;
    try {
        $pdo->exec("ALTER TABLE assets ADD COLUMN IF NOT EXISTS meta_json JSONB");
    } catch (Throwable $e) {
        // ignore; fallback queries below remain compatible
    }
    $checked = true;
}

function get_assets($filter = 'all', $page = 1, $limit = 50, $userId = 0) {
    try {
        $pdo = get_db();
        assets_ensure_meta_column($pdo);
    } catch (Throwable $e) {
        return [];
    }

    $sql = "SELECT id, title, image, type, model, prompt, meta_json, created_at FROM assets WHERE user_id = :user_id";
    $params = ['user_id' => max(0, (int)$userId)];
    if ($filter === 'image') {
        $sql .= " AND type = :type";
        $params['type'] = 'image';
    } elseif ($filter === 'video') {
        $sql .= " AND type = :type";
        $params['type'] = 'video';
    }
    $sql .= " ORDER BY created_at ASC";

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
            'meta' => json_decode((string)($row['meta_json'] ?? '{}'), true) ?? [],
            'createdAt' => date('Y-m-d H:i', strtotime($row['created_at'])),
        ];
    }
    return $items;
}

/**
 * 添加资产（供生成完成后调用）
 */
function add_asset($title, $imageUrl, $type, $model, $prompt, $userId = 0, array $meta = []): ?int {
    try {
        $pdo = get_db();
        assets_ensure_meta_column($pdo);
        $stmt = $pdo->prepare("
            INSERT INTO assets (user_id, title, image, type, model, prompt, meta_json)
            VALUES (:user_id, :title, :image, :type, :model, :prompt, :meta_json)
            RETURNING id
        ");
        $stmt->execute([
            'user_id' => $userId,
            'title' => $title,
            'image' => $imageUrl,
            'type' => $type,
            'model' => $model,
            'prompt' => $prompt,
            'meta_json' => json_encode($meta, JSON_UNESCAPED_UNICODE),
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['id'];
    } catch (Throwable $e) {
        return null;
    }
}
