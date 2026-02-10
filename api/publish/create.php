<?php
/**
 * POST /api/publish/create.php
 * 发布模板（存入数据库）
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';
require_once __DIR__ . '/../common/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? [];

$contentType = $input['contentType'] ?? '';
$modelId = $input['modelId'] ?? '';
$modelName = trim($input['modelName'] ?? '');
$category = $input['category'] ?? '';
$title = trim($input['title'] ?? '');
$content = trim($input['content'] ?? '');
$image = trim($input['image'] ?? '');

if (empty($modelId) || empty($category) || empty($title) || empty($content)) {
    json_error('请填写完整：模型、分类、标题、内容');
    exit;
}

if (!in_array($contentType, ['image', 'video'])) {
    json_error('无效的 contentType，应为 image 或 video');
    exit;
}

// 若未传 modelName，则从 models 中查找
if (empty($modelName)) {
    require_once __DIR__ . '/../data/models.php';
    $allModels = array_merge(get_models('image'), get_models('video'));
    foreach ($allModels as $m) {
        if ($m['id'] === $modelId) {
            $modelName = $m['name'];
            break;
        }
    }
    $modelName = $modelName ?: $modelId;
}

try {
    $pdo = get_db();
    $stmt = $pdo->prepare("
        INSERT INTO publish_templates (user_id, content_type, model_id, model_name, category, title, content, image)
        VALUES (0, :content_type, :model_id, :model_name, :category, :title, :content, :image)
        RETURNING id
    ");
    $stmt->execute([
        'content_type' => $contentType,
        'model_id' => $modelId,
        'model_name' => $modelName,
        'category' => $category,
        'title' => $title,
        'content' => $content,
        'image' => $image ?: null,
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $id = $row['id'];

    json_success([
        'id' => $id,
        'message' => '发布成功',
    ]);
} catch (Throwable $e) {
    json_error('数据库写入失败：' . $e->getMessage(), 500);
}
