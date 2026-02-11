<?php
/**
 * 阿里云短信（SendSms）工具
 */

function sms_config(): array {
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require __DIR__ . '/../config/sms.php';
    }
    return $cfg['aliyun'] ?? [];
}

function sms_is_valid_phone(string $phone): bool {
    return (bool)preg_match('/^1\d{10}$/', $phone);
}

function sms_generate_code(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function sms_percent_encode(string $value): string {
    return str_replace(['+', '*', '%7E'], ['%20', '%2A', '~'], rawurlencode($value));
}

function sms_send_aliyun(string $phone, string $templateCode, array $templateParams): array {
    $cfg = sms_config();
    if (empty($cfg['enabled'])) {
        return ['success' => false, 'message' => '短信通道未启用'];
    }
    if (empty($cfg['access_key_id']) || empty($cfg['access_key_secret']) || empty($cfg['sign_name'])) {
        return ['success' => false, 'message' => '短信配置不完整'];
    }

    $params = [
        'AccessKeyId' => $cfg['access_key_id'],
        'Action' => 'SendSms',
        'Format' => 'JSON',
        'PhoneNumbers' => $phone,
        'RegionId' => $cfg['region'] ?: 'cn-hangzhou',
        'SignName' => $cfg['sign_name'],
        'SignatureMethod' => 'HMAC-SHA1',
        'SignatureNonce' => bin2hex(random_bytes(8)),
        'SignatureVersion' => '1.0',
        'TemplateCode' => $templateCode,
        'TemplateParam' => json_encode($templateParams, JSON_UNESCAPED_UNICODE),
        'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        'Version' => '2017-05-25',
    ];

    ksort($params);
    $canonicalized = [];
    foreach ($params as $k => $v) {
        $canonicalized[] = sms_percent_encode((string)$k) . '=' . sms_percent_encode((string)$v);
    }
    $canonicalizedQueryString = implode('&', $canonicalized);
    $stringToSign = 'GET&%2F&' . sms_percent_encode($canonicalizedQueryString);
    $signature = base64_encode(hash_hmac('sha1', $stringToSign, $cfg['access_key_secret'] . '&', true));
    $url = 'https://' . ($cfg['endpoint'] ?: 'dysmsapi.aliyuncs.com') . '/?Signature=' . sms_percent_encode($signature) . '&' . $canonicalizedQueryString;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($resp === false) {
        return ['success' => false, 'message' => '短信请求失败：' . $err];
    }
    $data = json_decode($resp, true);
    if (!is_array($data)) {
        return ['success' => false, 'message' => '短信响应解析失败'];
    }
    if (($data['Code'] ?? '') !== 'OK') {
        return ['success' => false, 'message' => '短信发送失败：' . ($data['Message'] ?? 'unknown')];
    }
    return ['success' => true, 'data' => $data];
}

