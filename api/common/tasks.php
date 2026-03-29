<?php
require_once __DIR__ . '/db.php';

function tasks_ensure_queue_schema(PDO $pdo): void {
    static $checked = false;
    if ($checked) return;

    $checked = true;

    $pdo->exec("ALTER TABLE tasks ADD COLUMN IF NOT EXISTS provider VARCHAR(50)");
    $pdo->exec("ALTER TABLE tasks ADD COLUMN IF NOT EXISTS provider_model VARCHAR(100)");
    $pdo->exec("ALTER TABLE tasks ADD COLUMN IF NOT EXISTS external_task_id VARCHAR(128)");
    $pdo->exec("ALTER TABLE tasks ADD COLUMN IF NOT EXISTS provider_key_id VARCHAR(80)");
    $pdo->exec("ALTER TABLE tasks ADD COLUMN IF NOT EXISTS submit_attempts INTEGER NOT NULL DEFAULT 0");
    $pdo->exec("ALTER TABLE tasks ADD COLUMN IF NOT EXISTS poll_attempts INTEGER NOT NULL DEFAULT 0");
    $pdo->exec("ALTER TABLE tasks ADD COLUMN IF NOT EXISTS next_poll_at TIMESTAMP");
    $pdo->exec("ALTER TABLE tasks ADD COLUMN IF NOT EXISTS queued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    $pdo->exec("ALTER TABLE tasks ADD COLUMN IF NOT EXISTS started_at TIMESTAMP");
    $pdo->exec("ALTER TABLE tasks ADD COLUMN IF NOT EXISTS finished_at TIMESTAMP");
    $pdo->exec("ALTER TABLE tasks ADD COLUMN IF NOT EXISTS sync_status VARCHAR(20) NOT NULL DEFAULT 'pending'");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_tasks_status_next_poll ON tasks(status, next_poll_at)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_tasks_user_created_desc ON tasks(user_id, created_at DESC)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_tasks_provider_status_created ON tasks(provider, status, created_at DESC)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_tasks_provider_model_status_created ON tasks(provider_model, status, created_at DESC)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_tasks_provider_key_status ON tasks(provider_key_id, status)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_tasks_external_task_id ON tasks(external_task_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_assets_user_created_desc ON assets(user_id, created_at DESC)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_points_ledger_user_created_desc ON points_ledger(user_id, created_at DESC)");
}

function tasks_get_db(): PDO {
    $pdo = get_db();
    tasks_ensure_queue_schema($pdo);
    return $pdo;
}

function tasks_decode_params($raw): array {
    if (is_array($raw)) return $raw;
    $decoded = json_decode((string)$raw, true);
    return is_array($decoded) ? $decoded : [];
}

function tasks_get_by_id(string $taskId, int $userId = 0): ?array {
    $pdo = tasks_get_db();
    $sql = "SELECT * FROM tasks WHERE id = :id";
    $params = ['id' => $taskId];
    if ($userId > 0) {
        $sql .= " AND user_id = :user_id";
        $params['user_id'] = $userId;
    }
    $sql .= " LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function tasks_count_active(?string $providerModel = null): int {
    $pdo = tasks_get_db();
    $sql = "
        SELECT COUNT(*)
        FROM tasks
        WHERE status = 'processing'
          AND COALESCE(provider, '') <> ''
          AND COALESCE(provider_model, '') <> ''
          AND (
              started_at IS NOT NULL
              OR next_poll_at IS NOT NULL
              OR COALESCE(external_task_id, '') <> ''
              OR COALESCE(result_url, '') <> ''
              OR sync_status IN ('queued', 'processing')
          )
    ";
    $params = [];
    if ($providerModel !== null && trim($providerModel) !== '') {
        $sql .= " AND provider_model = :provider_model";
        $params['provider_model'] = trim($providerModel);
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function tasks_insert_queued(array $payload): string {
    $pdo = tasks_get_db();
    $taskId = (string)($payload['id'] ?? ('task_' . uniqid() . '_' . time()));
    $params = $payload['params_json'] ?? [];
    if (!is_array($params)) {
        $params = tasks_decode_params($params);
    }

    $stmt = $pdo->prepare("
        INSERT INTO tasks (
            id, user_id, type, status, params_json, provider, provider_model,
            queued_at, sync_status, created_at, updated_at
        ) VALUES (
            :id, :user_id, :type, :status, :params_json, :provider, :provider_model,
            CURRENT_TIMESTAMP, :sync_status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
        )
    ");
    $stmt->execute([
        'id' => $taskId,
        'user_id' => (int)($payload['user_id'] ?? 0),
        'type' => (string)($payload['type'] ?? 'image'),
        'status' => (string)($payload['status'] ?? 'pending'),
        'params_json' => json_encode($params, JSON_UNESCAPED_UNICODE),
        'provider' => (string)($payload['provider'] ?? ''),
        'provider_model' => (string)($payload['provider_model'] ?? ''),
        'sync_status' => (string)($payload['sync_status'] ?? 'pending'),
    ]);

    return $taskId;
}

function tasks_update_params_json(string $taskId, array $params): void {
    $pdo = tasks_get_db();
    $stmt = $pdo->prepare("
        UPDATE tasks
        SET params_json = :params_json, updated_at = CURRENT_TIMESTAMP
        WHERE id = :id
    ");
    $stmt->execute([
        'id' => $taskId,
        'params_json' => json_encode($params, JSON_UNESCAPED_UNICODE),
    ]);
}

function tasks_mark_submit_attempt(string $taskId): int {
    $pdo = tasks_get_db();
    $stmt = $pdo->prepare("
        UPDATE tasks
        SET submit_attempts = submit_attempts + 1,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id
        RETURNING submit_attempts
    ");
    $stmt->execute(['id' => $taskId]);
    return (int)$stmt->fetchColumn();
}

function tasks_mark_poll_attempt(string $taskId): int {
    $pdo = tasks_get_db();
    $stmt = $pdo->prepare("
        UPDATE tasks
        SET poll_attempts = poll_attempts + 1,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id
        RETURNING poll_attempts
    ");
    $stmt->execute(['id' => $taskId]);
    return (int)$stmt->fetchColumn();
}

function tasks_mark_submitted(string $taskId, string $externalTaskId, string $providerKeyId, array $params, int $nextPollDelaySeconds = 3): void {
    $pdo = tasks_get_db();
    $stmt = $pdo->prepare("
        UPDATE tasks
        SET status = 'processing',
            external_task_id = :external_task_id,
            provider_key_id = :provider_key_id,
            params_json = :params_json,
            started_at = COALESCE(started_at, CURRENT_TIMESTAMP),
            next_poll_at = CURRENT_TIMESTAMP + (:delay * INTERVAL '1 second'),
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id
    ");
    $stmt->execute([
        'id' => $taskId,
        'external_task_id' => $externalTaskId,
        'provider_key_id' => $providerKeyId,
        'params_json' => json_encode($params, JSON_UNESCAPED_UNICODE),
        'delay' => max(1, $nextPollDelaySeconds),
    ]);
}

function tasks_schedule_next_poll(string $taskId, int $delaySeconds): void {
    $pdo = tasks_get_db();
    $stmt = $pdo->prepare("
        UPDATE tasks
        SET next_poll_at = CURRENT_TIMESTAMP + (:delay * INTERVAL '1 second'),
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id
    ");
    $stmt->execute([
        'id' => $taskId,
        'delay' => max(1, $delaySeconds),
    ]);
}

function tasks_mark_syncing(string $taskId, string $resultUrl, array $params): void {
    $pdo = tasks_get_db();
    $stmt = $pdo->prepare("
        UPDATE tasks
        SET status = 'processing',
            result_url = :result_url,
            sync_status = 'queued',
            params_json = :params_json,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id
    ");
    $stmt->execute([
        'id' => $taskId,
        'result_url' => $resultUrl,
        'params_json' => json_encode($params, JSON_UNESCAPED_UNICODE),
    ]);
}

function tasks_mark_completed(string $taskId, string $resultUrl, array $params = []): void {
    $pdo = tasks_get_db();
    if (!empty($params)) {
        $stmt = $pdo->prepare("
            UPDATE tasks
            SET status = 'completed',
                result_url = :result_url,
                sync_status = 'done',
                params_json = :params_json,
                finished_at = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        $stmt->execute([
            'id' => $taskId,
            'result_url' => $resultUrl,
            'params_json' => json_encode($params, JSON_UNESCAPED_UNICODE),
        ]);
        return;
    }

    $stmt = $pdo->prepare("
        UPDATE tasks
        SET status = 'completed',
            result_url = :result_url,
            sync_status = 'done',
            finished_at = CURRENT_TIMESTAMP,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id
    ");
    $stmt->execute([
        'id' => $taskId,
        'result_url' => $resultUrl,
    ]);
}

function tasks_mark_failed(string $taskId, string $errorMessage, bool $syncFailure = false): void {
    $pdo = tasks_get_db();
    $syncStatus = $syncFailure ? 'failed' : 'pending';
    $stmt = $pdo->prepare("
        UPDATE tasks
        SET status = 'failed',
            error_message = :error_message,
            sync_status = :sync_status,
            finished_at = CURRENT_TIMESTAMP,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id
    ");
    $stmt->execute([
        'id' => $taskId,
        'error_message' => $errorMessage,
        'sync_status' => $syncStatus,
    ]);
}

function tasks_mark_sync_processing(string $taskId): void {
    $pdo = tasks_get_db();
    $stmt = $pdo->prepare("
        UPDATE tasks
        SET sync_status = 'processing',
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id
    ");
    $stmt->execute(['id' => $taskId]);
}
