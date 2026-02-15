<?php
/**
 * 认证相关公共方法（Session）
 */

require_once __DIR__ . '/db.php';

function auth_set_session_error(string $message): void {
    $GLOBALS['__auth_session_error'] = $message;
}

function auth_get_session_error(): ?string {
    $msg = $GLOBALS['__auth_session_error'] ?? null;
    return is_string($msg) && trim($msg) !== '' ? $msg : null;
}

function auth_ensure_user_admin_columns(): void {
    static $ensured = false;
    if ($ensured) return;
    $ensured = true;
    try {
        $pdo = get_db();
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(20) NOT NULL DEFAULT 'user'");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT 'active'");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_role_status ON users(role, status)");
    } catch (Throwable $e) {
        // 迁移失败时保持兼容，后续按非管理员处理
    }
}

function auth_boot_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        // Some production environments have an unwritable session.save_path.
        // Fix it proactively to avoid "login works but API still 401" (session not persisted).
        $handler = strtolower((string)ini_get('session.save_handler'));
        if ($handler === 'files') {
            $rawSavePath = (string)session_save_path();
            if ($rawSavePath === '') {
                $rawSavePath = (string)ini_get('session.save_path');
            }
            // session.save_path may contain "N;/path" format, take last segment.
            $savePath = $rawSavePath;
            if (strpos($savePath, ';') !== false) {
                $savePath = (string)substr($savePath, strrpos($savePath, ';') + 1);
            }
            $savePath = trim($savePath);
            if ($savePath === '' || !is_dir($savePath) || !is_writable($savePath)) {
                $fallback = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'php_sessions';
                if (!is_dir($fallback)) {
                    @mkdir($fallback, 0777, true);
                }
                if (is_dir($fallback) && is_writable($fallback)) {
                    session_save_path($fallback);
                }
            }
        }

        // Suppress warnings (they break JSON responses) and store a readable error.
        $ok = @session_start();
        if (!$ok && session_status() !== PHP_SESSION_ACTIVE) {
            $err = error_get_last();
            $msg = 'Session 启动失败';
            if (is_array($err) && !empty($err['message'])) {
                $msg .= '：' . (string)$err['message'];
            }
            auth_set_session_error($msg);
        }
    }
}

function auth_get_current_user(): ?array {
    auth_ensure_user_admin_columns();
    auth_boot_session();
    $user = $_SESSION['user'] ?? null;
    if (!is_array($user)) {
        return null;
    }
    $id = (int)($user['id'] ?? 0);
    $account = trim((string)($user['account'] ?? ''));
    $phone = trim((string)($user['phone'] ?? ''));
    if ($id <= 0 || ($account === '' && $phone === '')) {
        return null;
    }
    $role = trim((string)($user['role'] ?? 'user'));
    $status = trim((string)($user['status'] ?? 'active'));
    if ($role === '') $role = 'user';
    if ($status === '') $status = 'active';
    return [
        'id' => $id,
        'account' => $account,
        'phone' => $phone,
        'nickname' => trim((string)($user['nickname'] ?? '')),
        'role' => $role,
        'status' => $status,
    ];
}

function auth_get_current_user_id(): int {
    $user = auth_get_current_user();
    return $user ? (int)$user['id'] : 0;
}

function auth_login(array $user): void {
    auth_ensure_user_admin_columns();
    auth_boot_session();
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'account' => (string)($user['account'] ?? ''),
        'phone' => (string)($user['phone'] ?? ''),
        'nickname' => (string)($user['nickname'] ?? ''),
        'role' => (string)($user['role'] ?? 'user'),
        'status' => (string)($user['status'] ?? 'active'),
    ];
}

function auth_logout(): void {
    auth_boot_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function auth_is_admin(): bool {
    auth_ensure_user_admin_columns();
    $user = auth_get_current_user();
    if (!$user || (int)$user['id'] <= 0) {
        return false;
    }

    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("SELECT role, status FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => (int)$user['id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            auth_logout();
            return false;
        }
        $status = (string)($row['status'] ?? 'active');
        $role = (string)($row['role'] ?? 'user');
        if ($status !== 'active') {
            auth_logout();
            return false;
        }
        $_SESSION['user']['role'] = $role;
        $_SESSION['user']['status'] = $status;
        return $role === 'admin';
    } catch (Throwable $e) {
        return false;
    }
}

function auth_require_admin(bool $json = true): void {
    if (auth_is_admin()) return;

    if ($json) {
        if (!function_exists('json_error')) {
            require_once __DIR__ . '/response.php';
        }
        json_error('无管理员权限', 403);
        exit;
    }

    header('Location: /index.php?tab=create');
    exit;
}

