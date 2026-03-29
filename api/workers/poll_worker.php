<?php
require_once __DIR__ . '/bootstrap.php';

$worker = worker_name_from_argv($argv, 'pollWorker');
$once = worker_is_once($argv);
$settings = queue_worker_settings();
$idleSleepMs = max(200, (int)($settings['poll_idle_sleep_ms'] ?? 1000));

worker_stdout($worker, $once ? 'run once' : 'start loop');

do {
    try {
        $jobs = queue_claim_due_poll_jobs(10);
    } catch (Throwable $e) {
        generation_log('worker.poll.queue_exception', ['message' => $e->getMessage()]);
        worker_stdout($worker, 'queue exception: ' . $e->getMessage());
        if ($once) {
            exit(1);
        }
        sleep(3);
        continue;
    }
    if (empty($jobs)) {
        if ($once) break;
        usleep($idleSleepMs * 1000);
        continue;
    }

    foreach ($jobs as $job) {
        if (empty($job['taskId'])) {
            continue;
        }
        $taskId = (string)$job['taskId'];
        try {
            $ret = generation_poll_task_by_id($taskId);
            worker_stdout($worker, $taskId . ' => ' . json_encode($ret, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (Throwable $e) {
            generation_log('worker.poll.exception', ['task_id' => $taskId, 'message' => $e->getMessage()]);
            worker_stdout($worker, $taskId . ' => exception: ' . $e->getMessage());
        }
    }
} while (!$once);
