<?php
/**
 * 批量补齐资产：
 * 将 tasks 表中已完成但未进入 assets 的历史记录回填到 assets。
 *
 * 用法：
 *   php api/db/backfill_assets.php
 */
require_once __DIR__ . '/../common/db.php';

try {
    $pdo = get_db();

    $countSql = "
        SELECT COUNT(*) AS cnt
        FROM tasks t
        WHERE t.status = 'completed'
          AND t.result_url IS NOT NULL
          AND BTRIM(t.result_url) <> ''
          AND NOT EXISTS (
                SELECT 1
                FROM assets a
                WHERE a.image = t.result_url
                  AND a.type = t.type
          )
    ";
    $pendingCount = (int)$pdo->query($countSql)->fetchColumn();

    if ($pendingCount === 0) {
        echo "⏭  无需补齐：没有待回填的 completed 任务\n";
        exit(0);
    }

    $insertSql = "
        INSERT INTO assets (user_id, title, image, type, model, prompt)
        SELECT
            COALESCE(t.user_id, 0) AS user_id,
            LEFT(
                COALESCE(
                    NULLIF(BTRIM(t.params_json->>'prompt'), ''),
                    CASE WHEN t.type = 'video' THEN 'AI生成视频' ELSE 'AI生成图片' END
                ),
                255
            ) AS title,
            t.result_url AS image,
            t.type AS type,
            COALESCE(NULLIF(BTRIM(t.params_json->>'model'), ''), 'AI') AS model,
            COALESCE(t.params_json->>'prompt', '') AS prompt
        FROM tasks t
        WHERE t.status = 'completed'
          AND t.result_url IS NOT NULL
          AND BTRIM(t.result_url) <> ''
          AND NOT EXISTS (
                SELECT 1
                FROM assets a
                WHERE a.image = t.result_url
                  AND a.type = t.type
          )
    ";

    $stmt = $pdo->prepare($insertSql);
    $stmt->execute();
    $inserted = $stmt->rowCount();

    echo "✅ 补齐完成：新增 {$inserted} 条资产记录\n";
    if ($inserted < $pendingCount) {
        echo "ℹ  预估待补 {$pendingCount} 条，实际新增 {$inserted} 条（可能存在并发或重复数据）\n";
    }
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "❌ 补齐失败: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

