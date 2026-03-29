<?php
require_once __DIR__ . '/bootstrap.php';

$worker = worker_name_from_argv($argv, 'ossWorker');
$once = worker_is_once($argv);
$settings = queue_worker_settings();
$blockSeconds = max(1, (int)($settings['media_block_seconds'] ?? 5));

worker_stdout($worker, $once ? 'run once' : 'start loop');

do {
    try {
        $job = queue_pop_media_job($blockSeconds);
    } catch (Throwable $e) {
        generation_log('worker.sync.queue_exception', ['message' => $e->getMessage()]);
        worker_stdout($worker, 'queue exception: ' . $e->getMessage());
        if ($once) {
            exit(1);
        }
        sleep(3);
        continue;
    }
    if (!$job || empty($job['taskId'])) {
        if ($once) break;
        continue;
    }

    $taskId = (string)$job['taskId'];
    try {
        $ret = generation_sync_media_task_by_id($taskId);
        worker_stdout($worker, $taskId . ' => ' . json_encode($ret, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    } catch (Throwable $e) {
        generation_log('worker.sync.exception', ['task_id' => $taskId, 'message' => $e->getMessage()]);
        worker_stdout($worker, $taskId . ' => exception: ' . $e->getMessage());
    }
} while (!$once);
