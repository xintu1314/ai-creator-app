<?php
require_once __DIR__ . '/queue.php';

function provider_keys_config(): array {
    static $cfg = null;
    if ($cfg !== null) return $cfg;
    $cfg = require __DIR__ . '/../config/ai.php';
    return $cfg;
}

function provider_make_key_id(string $provider, string $key): string {
    $hash = substr(hash('sha256', $provider . '|' . $key), 0, 16);
    return $provider . '_key_' . $hash;
}

function provider_make_pool_entry(string $provider, string $key, array $legacyIds = []): array {
    $key = trim($key);
    if ($key === '') {
        return [];
    }

    $normalizedLegacyIds = [];
    foreach ($legacyIds as $legacyId) {
        $legacyId = trim((string)$legacyId);
        if ($legacyId !== '' && !in_array($legacyId, $normalizedLegacyIds, true)) {
            $normalizedLegacyIds[] = $legacyId;
        }
    }

    return [
        'id' => provider_make_key_id($provider, $key),
        'key' => $key,
        'legacy_ids' => $normalizedLegacyIds,
    ];
}

function provider_key_pool_list(string $provider): array {
    $cfg = provider_keys_config();
    $providerCfg = is_array($cfg[$provider] ?? null) ? $cfg[$provider] : [];
    $pool = [];
    $seenKeys = [];

    $multiKeys = $providerCfg['api_keys'] ?? [];
    if (is_array($multiKeys)) {
        foreach ($multiKeys as $index => $key) {
            $key = trim((string)$key);
            if ($key === '') continue;
            if (isset($seenKeys[$key])) {
                continue;
            }
            $entry = provider_make_pool_entry($provider, $key, [
                $provider . '_pool_' . ($index + 1),
            ]);
            if (!empty($entry)) {
                $pool[] = $entry;
                $seenKeys[$key] = true;
            }
        }
    }

    $singleKey = trim((string)($providerCfg['api_key'] ?? ''));
    if ($singleKey !== '') {
        if (isset($seenKeys[$singleKey])) {
            foreach ($pool as &$entry) {
                if (($entry['key'] ?? '') !== $singleKey) {
                    continue;
                }
                $legacyIds = is_array($entry['legacy_ids'] ?? null) ? $entry['legacy_ids'] : [];
                if (!in_array($provider . '_primary', $legacyIds, true)) {
                    $legacyIds[] = $provider . '_primary';
                }
                $entry['legacy_ids'] = $legacyIds;
                break;
            }
            unset($entry);
        } else {
            $entry = provider_make_pool_entry($provider, $singleKey, [
                $provider . '_primary',
            ]);
            if (!empty($entry)) {
                $pool[] = $entry;
            }
        }
    }

    return $pool;
}

function provider_pick_key(string $provider): array {
    $pool = provider_key_pool_list($provider);
    if (empty($pool)) {
        throw new RuntimeException('未配置可用的 ' . $provider . ' API key');
    }

    if (count($pool) === 1) {
        return $pool[0];
    }

    $redis = redis_client();
    $seq = $redis->incr(queue_key('provider_rr:' . $provider));
    $index = ($seq - 1) % count($pool);
    return $pool[$index];
}

function provider_find_key_by_id(string $provider, string $keyId): ?array {
    foreach (provider_key_pool_list($provider) as $entry) {
        if (($entry['id'] ?? '') === $keyId) {
            return $entry;
        }
        $legacyIds = is_array($entry['legacy_ids'] ?? null) ? $entry['legacy_ids'] : [];
        if (in_array($keyId, $legacyIds, true)) {
            return $entry;
        }
    }
    return null;
}

function provider_submit_concurrency_limit(string $provider): int {
    $cfg = provider_keys_config();
    $providerCfg = is_array($cfg[$provider] ?? null) ? $cfg[$provider] : [];
    return (int)($providerCfg['submit_concurrency'] ?? 0);
}
