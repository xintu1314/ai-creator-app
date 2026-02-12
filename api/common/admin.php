<?php
/**
 * 管理后台公共函数
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

function admin_ensure_schema(): void {
    static $ensured = false;
    if ($ensured) return;
    $ensured = true;
    try {
        $pdo = get_db();
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(20) NOT NULL DEFAULT 'user'");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT 'active'");

        $pdo->exec("ALTER TABLE publish_templates ADD COLUMN IF NOT EXISTS review_status VARCHAR(20) NOT NULL DEFAULT 'approved'");
        $pdo->exec("ALTER TABLE publish_templates ADD COLUMN IF NOT EXISTS is_online BOOLEAN NOT NULL DEFAULT true");
        $pdo->exec("ALTER TABLE publish_templates ADD COLUMN IF NOT EXISTS review_note TEXT");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS tutorials (
                id SERIAL PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT DEFAULT '',
                cover_url VARCHAR(500) DEFAULT '',
                video_url VARCHAR(500) NOT NULL,
                is_published BOOLEAN NOT NULL DEFAULT true,
                sort_order INTEGER NOT NULL DEFAULT 0,
                created_by INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_tutorials_published_sort ON tutorials(is_published, sort_order, created_at DESC)");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_audit_logs (
                id BIGSERIAL PRIMARY KEY,
                admin_user_id INTEGER NOT NULL,
                action VARCHAR(80) NOT NULL,
                target_type VARCHAR(40) DEFAULT '',
                target_id VARCHAR(64) DEFAULT '',
                payload_json JSONB,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_admin_audit_admin_created ON admin_audit_logs(admin_user_id, created_at DESC)");
    } catch (Throwable $e) {
        // ignore
    }
}

function admin_require(): int {
    admin_ensure_schema();
    auth_require_admin(true);
    $user = auth_get_current_user();
    return (int)($user['id'] ?? 0);
}

function admin_get_json_input(): array {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    return is_array($input) ? $input : [];
}

function admin_write_audit_log(int $adminUserId, string $action, string $targetType = '', string $targetId = '', array $payload = []): void {
    if ($adminUserId <= 0) return;
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("
            INSERT INTO admin_audit_logs (admin_user_id, action, target_type, target_id, payload_json)
            VALUES (:admin_user_id, :action, :target_type, :target_id, :payload_json)
        ");
        $stmt->execute([
            'admin_user_id' => $adminUserId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);
    } catch (Throwable $e) {
        // 审计写入失败不阻塞主流程
    }
}
