<?php
require_once __DIR__ . '/redis.php';

function queue_config(): array {
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }
    $cfg = require __DIR__ . '/../config/queue.php';
    return $cfg;
}

function queue_key(string $suffix): string {
    $cfg = queue_config();
    $prefix = (string)($cfg['redis']['prefix'] ?? 'ai_creator:');
    return $prefix . $suffix;
}

function queue_submit_key(): string {
    return queue_key('queue:generation:submit');
}

function queue_submit_schedule_key(): string {
    return queue_key('queue:generation:submit_schedule');
}

function queue_media_key(): string {
    return queue_key('queue:generation:media');
}

function queue_poll_schedule_key(): string {
    return queue_key('queue:generation:poll_schedule');
}

function queue_provider_counter_key(string $provider, string $keyId): string {
    return queue_key('provider_counter:' . $provider . ':' . $keyId);
}

function queue_job_encode(array $payload): string {
    return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function queue_job_decode(?string $payload): array {
    if (!is_string($payload) || $payload === '') {
        return [];
    }
    $decoded = json_decode($payload, true);
    return is_array($decoded) ? $decoded : [];
}

function queue_push_submit_job(string $taskId): void {
    redis_client()->lpush(queue_submit_key(), queue_job_encode([
        'taskId' => $taskId,
        'queuedAt' => time(),
    ]));
}

function queue_schedule_submit_job(string $taskId, int $delaySeconds = 0): void {
    $when = time() + max(0, $delaySeconds);
    redis_client()->zadd(queue_submit_schedule_key(), (float)$when, queue_job_encode([
        'taskId' => $taskId,
        'scheduledAt' => $when,
    ]));
}

function queue_push_media_job(string $taskId): void {
    redis_client()->lpush(queue_media_key(), queue_job_encode([
        'taskId' => $taskId,
        'queuedAt' => time(),
    ]));
}

function queue_schedule_poll_job(string $taskId, int $delaySeconds = 0): void {
    $when = time() + max(0, $delaySeconds);
    redis_client()->zadd(queue_poll_schedule_key(), (float)$when, queue_job_encode([
        'taskId' => $taskId,
        'scheduledAt' => $when,
    ]));
}

function queue_pop_submit_job(int $timeoutSeconds = 5): ?array {
    $result = redis_client()->brpop(queue_submit_key(), max(0, $timeoutSeconds));
    if (!is_array($result) || count($result) < 2) {
        return null;
    }
    return queue_job_decode((string)$result[1]);
}

function queue_claim_due_submit_jobs(int $limit = 10): array {
    $redis = redis_client();
    $jobs = [];
    $members = $redis->zrangeByScore(queue_submit_schedule_key(), '-inf', (string)time(), max(1, $limit));
    foreach ($members as $member) {
        if (!is_string($member)) continue;
        if ($redis->zrem(queue_submit_schedule_key(), $member) !== 1) {
            continue;
        }
        $job = queue_job_decode($member);
        if (!empty($job['taskId'])) {
            $jobs[] = $job;
        }
    }
    return $jobs;
}

function queue_pop_media_job(int $timeoutSeconds = 5): ?array {
    $result = redis_client()->brpop(queue_media_key(), max(0, $timeoutSeconds));
    if (!is_array($result) || count($result) < 2) {
        return null;
    }
    return queue_job_decode((string)$result[1]);
}

function queue_claim_due_poll_jobs(int $limit = 10): array {
    $redis = redis_client();
    $jobs = [];
    $members = $redis->zrangeByScore(queue_poll_schedule_key(), '-inf', (string)time(), max(1, $limit));
    foreach ($members as $member) {
        if (!is_string($member)) continue;
        if ($redis->zrem(queue_poll_schedule_key(), $member) !== 1) {
            continue;
        }
        $job = queue_job_decode($member);
        if (!empty($job['taskId'])) {
            $jobs[] = $job;
        }
    }
    return $jobs;
}

function queue_worker_settings(): array {
    $cfg = queue_config();
    return is_array($cfg['workers'] ?? null) ? $cfg['workers'] : [];
}

function queue_generation_limits(): array {
    $cfg = queue_config();
    return is_array($cfg['limits'] ?? null) ? $cfg['limits'] : [];
}

function queue_acquire_provider_slot(string $provider, string $keyId, int $limit, int $ttlSeconds = 120): bool {
    if ($limit <= 0) return true;
    $redis = redis_client();
    $counterKey = queue_provider_counter_key($provider, $keyId);
    $value = $redis->incr($counterKey);
    $redis->expire($counterKey, $ttlSeconds);
    if ($value > $limit) {
        $redis->decr($counterKey);
        return false;
    }
    return true;
}

function queue_release_provider_slot(string $provider, string $keyId): void {
    if ($provider === '' || $keyId === '') return;
    $redis = redis_client();
    $counterKey = queue_provider_counter_key($provider, $keyId);
    try {
        $value = $redis->decr($counterKey);
        if ($value <= 0) {
            $redis->del($counterKey);
        }
    } catch (Throwable $e) {
        // ignore slot release errors
    }
}
