<?php
/**
 * 轻量 Redis 客户端（RESP2）
 * 依赖最小，避免服务器未安装 phpredis 扩展时无法运行。
 */

function redis_config(): array {
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }
    $all = require __DIR__ . '/../config/queue.php';
    $cfg = is_array($all['redis'] ?? null) ? $all['redis'] : [];
    return $cfg;
}

class AppRedisClient {
    /** @var resource|null */
    private $stream;
    /** @var array<string, mixed> */
    private $config;

    public function __construct(?array $config = null) {
        $this->config = $config ?: redis_config();
        $this->stream = null;
    }

    public function __destruct() {
        $this->disconnect();
    }

    public function disconnect(): void {
        if (is_resource($this->stream)) {
            @fclose($this->stream);
        }
        $this->stream = null;
    }

    public function ping(): bool {
        return $this->command(['PING']) === 'PONG';
    }

    public function get(string $key) {
        return $this->command(['GET', $key]);
    }

    public function set(string $key, string $value, ?int $ttlSeconds = null): bool {
        $args = ['SET', $key, $value];
        if ($ttlSeconds !== null && $ttlSeconds > 0) {
            $args[] = 'EX';
            $args[] = (string)$ttlSeconds;
        }
        return $this->command($args) === 'OK';
    }

    public function setNx(string $key, string $value, int $ttlSeconds): bool {
        return $this->command(['SET', $key, $value, 'EX', (string)$ttlSeconds, 'NX']) === 'OK';
    }

    public function del(string ...$keys): int {
        if (empty($keys)) return 0;
        return (int)$this->command(array_merge(['DEL'], $keys));
    }

    public function incr(string $key): int {
        return (int)$this->command(['INCR', $key]);
    }

    public function decr(string $key): int {
        return (int)$this->command(['DECR', $key]);
    }

    public function expire(string $key, int $ttlSeconds): bool {
        return (int)$this->command(['EXPIRE', $key, (string)$ttlSeconds]) === 1;
    }

    public function lpush(string $key, string ...$values): int {
        return (int)$this->command(array_merge(['LPUSH', $key], $values));
    }

    public function rpush(string $key, string ...$values): int {
        return (int)$this->command(array_merge(['RPUSH', $key], $values));
    }

    public function brpop(string $key, int $timeoutSeconds = 0): ?array {
        $result = $this->command(['BRPOP', $key, (string)$timeoutSeconds], max(0, $timeoutSeconds) + 2);
        return is_array($result) ? $result : null;
    }

    public function blpop(string $key, int $timeoutSeconds = 0): ?array {
        $result = $this->command(['BLPOP', $key, (string)$timeoutSeconds], max(0, $timeoutSeconds) + 2);
        return is_array($result) ? $result : null;
    }

    public function zadd(string $key, float $score, string $member): int {
        return (int)$this->command(['ZADD', $key, (string)$score, $member]);
    }

    public function zrem(string $key, string $member): int {
        return (int)$this->command(['ZREM', $key, $member]);
    }

    public function zrangeByScore(string $key, string $min, string $max, int $limit = 0): array {
        $args = ['ZRANGEBYSCORE', $key, $min, $max];
        if ($limit > 0) {
            $args[] = 'LIMIT';
            $args[] = '0';
            $args[] = (string)$limit;
        }
        $result = $this->command($args);
        return is_array($result) ? $result : [];
    }

    public function hget(string $key, string $field) {
        return $this->command(['HGET', $key, $field]);
    }

    public function hset(string $key, string $field, string $value): int {
        return (int)$this->command(['HSET', $key, $field, $value]);
    }

    public function hgetall(string $key): array {
        $result = $this->command(['HGETALL', $key]);
        if (!is_array($result)) return [];
        $assoc = [];
        for ($i = 0; $i < count($result); $i += 2) {
            $field = $result[$i] ?? null;
            if (!is_string($field)) continue;
            $assoc[$field] = $result[$i + 1] ?? null;
        }
        return $assoc;
    }

    public function hdel(string $key, string $field): int {
        return (int)$this->command(['HDEL', $key, $field]);
    }

    public function command(array $args, ?int $readTimeoutSeconds = null) {
        $this->connect($readTimeoutSeconds);
        $payload = '*' . count($args) . "\r\n";
        foreach ($args as $arg) {
            $value = (string)$arg;
            $payload .= '$' . strlen($value) . "\r\n" . $value . "\r\n";
        }
        $this->writeAll($payload);
        return $this->readReply();
    }

    private function connect(?int $readTimeoutSeconds = null): void {
        if (is_resource($this->stream)) {
            if ($readTimeoutSeconds !== null) {
                stream_set_timeout($this->stream, $readTimeoutSeconds);
            }
            return;
        }

        $host = (string)($this->config['host'] ?? '127.0.0.1');
        $port = (int)($this->config['port'] ?? 6379);
        $timeout = (float)($this->config['timeout'] ?? 2.0);
        $scheme = strtolower((string)($this->config['scheme'] ?? 'tcp'));
        $target = $scheme === 'unix'
            ? 'unix://' . $host
            : 'tcp://' . $host . ':' . $port;

        $stream = @stream_socket_client($target, $errno, $errstr, $timeout);
        if (!is_resource($stream)) {
            throw new RuntimeException('Redis 连接失败：' . $errstr . ' (' . $errno . ')');
        }

        $rwTimeout = (float)($this->config['read_write_timeout'] ?? 5.0);
        $sec = (int)floor($rwTimeout);
        $usec = (int)(($rwTimeout - $sec) * 1000000);
        stream_set_timeout($stream, $readTimeoutSeconds ?? $sec, $readTimeoutSeconds !== null ? 0 : $usec);
        $this->stream = $stream;

        $password = (string)($this->config['password'] ?? '');
        if ($password !== '') {
            $resp = $this->command(['AUTH', $password]);
            if ($resp !== 'OK') {
                $this->disconnect();
                throw new RuntimeException('Redis AUTH 失败');
            }
        }

        $db = (int)($this->config['database'] ?? 0);
        if ($db > 0) {
            $resp = $this->command(['SELECT', (string)$db]);
            if ($resp !== 'OK') {
                $this->disconnect();
                throw new RuntimeException('Redis SELECT 失败');
            }
        }
    }

    private function writeAll(string $payload): void {
        $length = strlen($payload);
        $written = 0;
        while ($written < $length) {
            $chunk = @fwrite($this->stream, substr($payload, $written));
            if ($chunk === false || $chunk === 0) {
                $this->disconnect();
                throw new RuntimeException('Redis 写入失败');
            }
            $written += $chunk;
        }
    }

    private function readReply() {
        $prefix = $this->readBytes(1);
        if ($prefix === '') {
            $this->disconnect();
            throw new RuntimeException('Redis 响应为空');
        }
        switch ($prefix) {
            case '+':
                return $this->readLine();
            case '-':
                $message = $this->readLine();
                throw new RuntimeException('Redis 错误：' . $message);
            case ':':
                return (int)$this->readLine();
            case '$':
                $length = (int)$this->readLine();
                if ($length < 0) return null;
                $value = $this->readBytes($length);
                $this->readBytes(2);
                return $value;
            case '*':
                $count = (int)$this->readLine();
                if ($count < 0) return null;
                $items = [];
                for ($i = 0; $i < $count; $i++) {
                    $items[] = $this->readReply();
                }
                return $items;
            default:
                $this->disconnect();
                throw new RuntimeException('未知 Redis 响应类型：' . $prefix);
        }
    }

    private function readLine(): string {
        $line = @fgets($this->stream);
        if ($line === false) {
            $this->disconnect();
            throw new RuntimeException('Redis 读取失败');
        }
        return rtrim($line, "\r\n");
    }

    private function readBytes(int $length): string {
        $buffer = '';
        while (strlen($buffer) < $length) {
            $chunk = @fread($this->stream, $length - strlen($buffer));
            if ($chunk === false || $chunk === '') {
                $meta = stream_get_meta_data($this->stream);
                $this->disconnect();
                if (!empty($meta['timed_out'])) {
                    throw new RuntimeException('Redis 读取超时');
                }
                throw new RuntimeException('Redis 连接中断');
            }
            $buffer .= $chunk;
        }
        return $buffer;
    }
}

function redis_client(): AppRedisClient {
    return new AppRedisClient();
}
