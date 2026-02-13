<?php
/**
 * 易支付公共能力：签名、下单、回调处理
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/points.php';

function payment_get_config(): array {
    $cfg = require __DIR__ . '/../config/payment.php';
    $epay = $cfg['epay'] ?? [];
    $apiBase = trim((string)($epay['api_base'] ?? ''));
    $pid = trim((string)($epay['pid'] ?? ''));
    $key = trim((string)($epay['key'] ?? ''));
    if ($apiBase === '' || $pid === '' || $key === '') {
        throw new RuntimeException('支付配置缺失，请设置 EPAY_API_BASE/EPAY_PID/EPAY_KEY');
    }
    return $epay;
}

function payment_money_format($money): string {
    return number_format((float)$money, 2, '.', '');
}

function payment_build_sign(array $params, string $key): string {
    ksort($params);
    $pairs = [];
    foreach ($params as $k => $v) {
        if ($k === 'sign' || $k === 'sign_type') continue;
        if ($v === '' || $v === null) continue;
        $pairs[] = $k . '=' . stripslashes((string)$v);
    }
    return strtolower(md5(implode('&', $pairs) . $key));
}

function payment_verify_sign(array $params): bool {
    $cfg = payment_get_config();
    $sign = strtolower((string)($params['sign'] ?? ''));
    if ($sign === '') return false;
    return hash_equals(payment_build_sign($params, (string)$cfg['key']), $sign);
}

function payment_get_base_url(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return $host ? ($scheme . '://' . $host) : '';
}

function payment_generate_order_no(): string {
    // Use integer nanosecond clock to avoid float->int deprecation warnings on PHP 8.5.
    $ms = (int)(hrtime(true) % 1000);
    return date('YmdHis') . str_pad((string)$ms, 3, '0', STR_PAD_LEFT) . random_int(100000, 999999);
}

function payment_create_order(
    int $userId,
    string $orderType,
    string $bizId,
    string $subject,
    float $amount,
    int $pointsAmount = 0,
    int $membershipDays = 0
): array {
    $pdo = get_db();
    $amountText = payment_money_format($amount);
    $payParam = json_encode([
        'userId' => $userId,
        'orderType' => $orderType,
        'bizId' => $bizId,
    ], JSON_UNESCAPED_UNICODE);
    $outTradeNo = '';
    $created = false;
    for ($i = 0; $i < 5; $i++) {
        $outTradeNo = payment_generate_order_no();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO payment_orders (
                    out_trade_no, user_id, order_type, biz_id, subject, amount, points_amount,
                    membership_days, pay_param, status
                ) VALUES (
                    :out_trade_no, :user_id, :order_type, :biz_id, :subject, :amount, :points_amount,
                    :membership_days, :pay_param, 'created'
                )
            ");
            $stmt->execute([
                'out_trade_no' => $outTradeNo,
                'user_id' => $userId,
                'order_type' => $orderType,
                'biz_id' => $bizId,
                'subject' => $subject,
                'amount' => $amountText,
                'points_amount' => $pointsAmount,
                'membership_days' => $membershipDays,
                'pay_param' => $payParam,
            ]);
            $created = true;
            break;
        } catch (PDOException $e) {
            if ((string)$e->getCode() !== '23505') {
                throw $e;
            }
            // rare unique conflict, retry with a new out_trade_no
        }
    }
    if (!$created) {
        throw new RuntimeException('订单号生成失败，请稍后重试');
    }

    return [
        'outTradeNo' => $outTradeNo,
        'amount' => $amountText,
        'subject' => $subject,
        'param' => $bizId,
    ];
}

function payment_build_submit_url(array $data): string {
    $cfg = payment_get_config();
    $base = rtrim((string)($cfg['api_base'] ?? ''), '/') . '/';
    $params = [
        'pid' => (string)($cfg['pid'] ?? ''),
        'type' => (string)($data['type'] ?? 'alipay'),
        'notify_url' => (string)($data['notify_url'] ?? ''),
        'return_url' => (string)($data['return_url'] ?? ''),
        'out_trade_no' => (string)($data['out_trade_no'] ?? ''),
        'name' => (string)($data['name'] ?? ''),
        'money' => payment_money_format($data['money'] ?? '0'),
        'param' => (string)($data['param'] ?? ''),
        'sign_type' => (string)($cfg['sign_type'] ?? 'MD5'),
    ];
    $params['sign'] = payment_build_sign($params, (string)$cfg['key']);
    return $base . 'submit.php?' . http_build_query($params);
}

function payment_detect_client_ip(): string {
    $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        $val = trim((string)($_SERVER[$key] ?? ''));
        if ($val === '') continue;
        if ($key === 'HTTP_X_FORWARDED_FOR') {
            $parts = explode(',', $val);
            $val = trim((string)($parts[0] ?? ''));
        }
        if (filter_var($val, FILTER_VALIDATE_IP)) {
            return $val;
        }
    }
    return '127.0.0.1';
}

function payment_detect_device(): string {
    $ua = strtolower((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
    if ($ua === '') return 'pc';
    if (strpos($ua, 'micromessenger') !== false) return 'mobile';
    if (preg_match('/iphone|ipad|android|mobile|harmony/i', $ua)) return 'mobile';
    return 'pc';
}

function payment_http_post_form(string $url, array $data): array {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    $raw = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    if ($errno !== 0) {
        return ['success' => false, 'message' => '支付网关请求失败：' . $error];
    }
    if ($status >= 400 || $raw === false || $raw === '') {
        return ['success' => false, 'message' => '支付网关响应异常'];
    }
    $json = json_decode($raw, true);
    if (!is_array($json)) {
        return ['success' => false, 'message' => '支付网关返回非JSON：' . substr((string)$raw, 0, 120)];
    }
    return ['success' => true, 'data' => $json];
}

function payment_http_get_json(string $url): array {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    $raw = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    if ($errno !== 0) {
        return ['success' => false, 'message' => '支付网关请求失败：' . $error];
    }
    if ($status >= 400 || $raw === false || $raw === '') {
        return ['success' => false, 'message' => '支付网关响应异常'];
    }
    $json = json_decode($raw, true);
    if (!is_array($json)) {
        return ['success' => false, 'message' => '支付网关返回非JSON'];
    }
    return ['success' => true, 'data' => $json];
}

function payment_finalize_paid_order(
    string $outTradeNo,
    string $tradeNo,
    string $payType,
    string $money,
    array $rawParams = []
): array {
    if ($outTradeNo === '' || $tradeNo === '') {
        return ['success' => false, 'message' => '缺少订单参数'];
    }

    $order = payment_get_order_by_out_trade_no($outTradeNo);
    if (!$order) {
        return ['success' => false, 'message' => '订单不存在'];
    }
    if (payment_money_format($order['amount']) !== payment_money_format($money)) {
        return ['success' => false, 'message' => '金额不一致'];
    }
    if (($order['status'] ?? '') === 'done') {
        return ['success' => true, 'message' => '已处理'];
    }

    $pdo = get_db();
    $rawJson = json_encode($rawParams, JSON_UNESCAPED_UNICODE);
    $claim = $pdo->prepare("
        UPDATE payment_orders
        SET status = 'processing',
            trade_no = :trade_no,
            pay_type = :pay_type,
            paid_at = COALESCE(paid_at, CURRENT_TIMESTAMP),
            callback_raw = :callback_raw::jsonb,
            updated_at = CURRENT_TIMESTAMP
        WHERE out_trade_no = :out_trade_no
          AND status IN ('created', 'paid', 'failed')
    ");
    $claim->execute([
        'trade_no' => $tradeNo,
        'pay_type' => $payType,
        'callback_raw' => $rawJson ?: '{}',
        'out_trade_no' => $outTradeNo,
    ]);

    if ($claim->rowCount() === 0) {
        $latest = payment_get_order_by_out_trade_no($outTradeNo);
        if (($latest['status'] ?? '') === 'done' || ($latest['status'] ?? '') === 'processing') {
            return ['success' => true, 'message' => '处理中或已完成'];
        }
        return ['success' => false, 'message' => '订单状态不可处理'];
    }

    $latest = payment_get_order_by_out_trade_no($outTradeNo);
    if (!$latest) {
        return ['success' => false, 'message' => '订单读取失败'];
    }
    $apply = payment_apply_business($latest, $tradeNo, $payType);
    if (!empty($apply['success'])) {
        $done = $pdo->prepare("
            UPDATE payment_orders
            SET status = 'done',
                fulfilled_at = COALESCE(fulfilled_at, CURRENT_TIMESTAMP),
                updated_at = CURRENT_TIMESTAMP
            WHERE out_trade_no = :out_trade_no
        ");
        $done->execute(['out_trade_no' => $outTradeNo]);
        return ['success' => true, 'message' => '处理成功'];
    }

    $fail = $pdo->prepare("
        UPDATE payment_orders
        SET status = 'failed',
            updated_at = CURRENT_TIMESTAMP
        WHERE out_trade_no = :out_trade_no
    ");
    $fail->execute(['out_trade_no' => $outTradeNo]);
    return ['success' => false, 'message' => (string)($apply['message'] ?? '业务处理失败')];
}

function payment_try_sync_by_query(string $outTradeNo): array {
    $cfg = payment_get_config();
    $base = rtrim((string)($cfg['api_base'] ?? ''), '/') . '/';
    $url = $base . 'api.php?act=order&pid=' . rawurlencode((string)$cfg['pid'])
        . '&key=' . rawurlencode((string)$cfg['key'])
        . '&out_trade_no=' . rawurlencode($outTradeNo);
    $ret = payment_http_get_json($url);
    if (empty($ret['success'])) {
        return $ret;
    }
    $data = (array)($ret['data'] ?? []);
    if ((string)($data['code'] ?? '') !== '1') {
        return ['success' => false, 'message' => (string)($data['msg'] ?? '查单失败')];
    }
    if ((string)($data['status'] ?? '0') !== '1') {
        return ['success' => false, 'message' => '未支付'];
    }

    return payment_finalize_paid_order(
        $outTradeNo,
        (string)($data['trade_no'] ?? ''),
        (string)($data['type'] ?? ''),
        payment_money_format((string)($data['money'] ?? '0')),
        $data
    );
}

function payment_create_mapi_trade(array $order, string $payType, string $notifyUrl): array {
    $cfg = payment_get_config();
    $base = rtrim((string)($cfg['api_base'] ?? ''), '/') . '/';
    $post = [
        'pid' => (string)($cfg['pid'] ?? ''),
        'type' => $payType,
        'notify_url' => $notifyUrl,
        'out_trade_no' => (string)($order['outTradeNo'] ?? ''),
        'name' => (string)($order['subject'] ?? '订单支付'),
        'money' => payment_money_format($order['amount'] ?? '0'),
        'clientip' => payment_detect_client_ip(),
        'device' => payment_detect_device(),
        'param' => (string)($order['param'] ?? ''),
        'sign_type' => (string)($cfg['sign_type'] ?? 'MD5'),
    ];
    $post['sign'] = payment_build_sign($post, (string)$cfg['key']);

    $req = payment_http_post_form($base . 'mapi.php', $post);
    if (empty($req['success'])) {
        return $req;
    }
    $ret = (array)($req['data'] ?? []);
    if ((string)($ret['code'] ?? '') !== '1') {
        return ['success' => false, 'message' => (string)($ret['msg'] ?? '创建支付失败')];
    }

    $payInfo = [
        'tradeNo' => (string)($ret['trade_no'] ?? ''),
        'oid' => (string)($ret['O_id'] ?? ''),
        'payUrl' => (string)($ret['payurl'] ?? ''),
        'qrcode' => (string)($ret['qrcode'] ?? ''),
        'img' => (string)($ret['img'] ?? ''),
    ];

    $pdo = get_db();
    $upd = $pdo->prepare("
        UPDATE payment_orders
        SET trade_no = CASE WHEN :trade_no <> '' THEN :trade_no ELSE trade_no END,
            status = 'created',
            callback_raw = :raw::jsonb,
            updated_at = CURRENT_TIMESTAMP
        WHERE out_trade_no = :out_trade_no
    ");
    $upd->execute([
        'trade_no' => $payInfo['tradeNo'],
        'raw' => json_encode($ret, JSON_UNESCAPED_UNICODE) ?: '{}',
        'out_trade_no' => (string)($order['outTradeNo'] ?? ''),
    ]);

    return ['success' => true, 'data' => $payInfo];
}

function payment_get_order_by_out_trade_no(string $outTradeNo): ?array {
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT * FROM payment_orders WHERE out_trade_no = :out_trade_no LIMIT 1");
    $stmt->execute(['out_trade_no' => $outTradeNo]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function payment_apply_business(array $order, string $tradeNo, string $payType): array {
    $userId = (int)$order['user_id'];
    $source = 'epay';
    if ($order['order_type'] === 'recharge') {
        $points = (int)$order['points_amount'];
        $ok = points_add_paid($userId, $points, $source, '支付充值积分', [
            'outTradeNo' => $order['out_trade_no'],
            'tradeNo' => $tradeNo,
            'payType' => $payType,
            'bizId' => $order['biz_id'],
        ]);
        return ['success' => $ok, 'message' => $ok ? 'ok' : '积分入账失败'];
    }

    if ($order['order_type'] === 'membership') {
        $ret = points_subscribe_membership($userId, (string)$order['biz_id']);
        return [
            'success' => !empty($ret['success']),
            'message' => $ret['message'] ?? '会员开通失败',
        ];
    }

    return ['success' => false, 'message' => '未知订单类型'];
}

function payment_handle_callback(array $params): array {
    $cfg = payment_get_config();
    $outTradeNo = trim((string)($params['out_trade_no'] ?? ''));
    $tradeStatus = trim((string)($params['trade_status'] ?? ''));
    $pid = trim((string)($params['pid'] ?? ''));
    $money = payment_money_format((string)($params['money'] ?? '0'));
    $tradeNo = trim((string)($params['trade_no'] ?? ''));
    $payType = trim((string)($params['type'] ?? ''));

    if ($outTradeNo === '' || $tradeNo === '') {
        return ['success' => false, 'message' => '缺少订单参数'];
    }
    if ($pid !== (string)$cfg['pid']) {
        return ['success' => false, 'message' => 'PID不匹配'];
    }
    if (!payment_verify_sign($params)) {
        return ['success' => false, 'message' => '签名验证失败'];
    }
    if ($tradeStatus !== 'TRADE_SUCCESS') {
        return ['success' => false, 'message' => '交易未成功'];
    }

    return payment_finalize_paid_order($outTradeNo, $tradeNo, $payType, $money, $params);
}
