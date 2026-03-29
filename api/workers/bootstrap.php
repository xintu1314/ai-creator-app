<?php
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This worker must run in CLI.\n");
    exit(1);
}

require_once __DIR__ . '/../common/generation_jobs.php';

function worker_has_flag(array $argv, string $flag): bool {
    return in_array($flag, $argv, true);
}

function worker_is_once(array $argv): bool {
    return worker_has_flag($argv, '--once');
}

function worker_name_from_argv(array $argv, string $fallback): string {
    foreach ($argv as $arg) {
        if (str_starts_with($arg, '--name=')) {
            return substr($arg, 7);
        }
    }
    return $fallback;
}

function worker_stdout(string $worker, string $message): void {
    fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . '][' . $worker . '] ' . $message . PHP_EOL);
}
