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
