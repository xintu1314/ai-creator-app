<?php
/**
 * 统一 JSON 响应
 */

/**
 * 成功响应
 */
function json_success($data = null, $message = 'success') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 失败响应
 */
function json_error($message = 'error', $code = 400) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => $message,
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
}

function app_is_production(): bool {
    $env = strtolower(trim((string)getenv('APP_ENV')));
    return in_array($env, ['prod', 'production'], true);
}

function json_exception(string $publicMessage, Throwable $e, int $code = 500): void {
    error_log($publicMessage . ' | ' . $e->getMessage());
    json_error($publicMessage, $code);
}

function set_public_cache_headers(int $maxAgeSeconds, int $staleWhileRevalidateSeconds = 0): void {
    $maxAge = max(0, $maxAgeSeconds);
    $swr = max(0, $staleWhileRevalidateSeconds);
    $cacheControl = 'public, max-age=' . $maxAge;
    if ($swr > 0) {
        $cacheControl .= ', stale-while-revalidate=' . $swr;
    }
    header('Cache-Control: ' . $cacheControl);
}
