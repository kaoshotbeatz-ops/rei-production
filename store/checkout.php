<?php
// Creates a single Stripe Checkout Session covering every item currently in
// the visitor's cart (session-based), then redirects to Stripe's hosted
// checkout. Stock caps are re-validated here against orders.json, since the
// cart may have sat open for a while.
session_start();
require __DIR__ . '/config.php';
$products = require __DIR__ . '/products.php';

function store_data_path($file) {
    $dir = __DIR__ . '/data';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    return $dir . '/' . $file;
}

function store_sold_count($key, $products) {
    $ordersFile = store_data_path('orders.json');
    if (!file_exists($ordersFile)) return 0;
    $orders = json_decode(file_get_contents($ordersFile), true) ?: [];
    $group = $products[$key]['group'] ?? null;
    $count = 0;
    foreach ($orders as $o) {
        $k = $o['product_key'] ?? '';
        if ($k === $key) { $count += (int)($o['quantity'] ?? 1); continue; }
        if ($group && ($products[$k]['group'] ?? null) === $group) { $count += (int)($o['quantity'] ?? 1); }
    }
    return $count;
}

function stripe_request($method, $path, $params = []) {
    $ch = curl_init("https://api.stripe.com/v1/$path");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SECRET_KEY . ':');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    }
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return [$code, json_decode($resp, true)];
}

function stripe_flatten($params, $prefix = '') {
    $out = [];
    foreach ($params as $k => $v) {
        $key = $prefix === '' ? $k : "{$prefix}[$k]";
        if (is_array($v)) {
            $isList = array_keys($v) === range(0, count($v) - 1);
            if ($isList) {
                foreach ($v as $i => $item) {
                    if (is_array($item)) {
                        $out += stripe_flatten($item, "{$key}[$i]");
                    } else {
                        $out["{$key}[$i]"] = is_bool($item) ? ($item ? 'true' : 'false') : $item;
                    }
                }
            } else {
                $out += stripe_flatten($v, $key);
            }
        } else {
            $out[$key] = is_bool($v) ? ($v ? 'true' : 'false') : $v;
        }
    }
    return $out;
}

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header('Location: ' . SITE_URL . '/cart');
    exit;
}

// Re-validate stock caps against combined cart quantities per group/key.
$groupTotals = [];
foreach ($cart as $key => $qty) {
    if (!isset($products[$key])) continue;
    $group = $products[$key]['group'] ?? $key;
    $groupTotals[$group] = ($groupTotals[$group] ?? 0) + $qty;
}
$errors = [];
foreach ($groupTotals as $group => $qty) {
    $cap = $GLOBALS['STORE_GROUP_CAPS'][$group] ?? ($products[$group]['stock'] ?? null);
    if ($cap === null) continue;
    $sold = 0;
    foreach ($products as $k => $p) {
        if (($p['group'] ?? $k) === $group) $sold += store_sold_count($k, $products);
    }
    if ($sold + $qty > $cap) {
        $errors[] = true;
    }
}
if ($errors) {
    header('Location: ' . SITE_URL . '/cart?sold_out=1');
    exit;
}

$lineItems = [];
$metaKeys = [];
foreach ($cart as $key => $qty) {
    if (!isset($products[$key])) continue;
    $lineItems[] = ['price' => $products[$key]['price'], 'quantity' => $qty];
    $metaKeys[] = "$key:$qty";
}

$params = [
    'mode' => 'payment',
    'line_items' => $lineItems,
    'success_url' => SITE_URL . '/store/success.php?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url' => SITE_URL . '/cart',
    'metadata' => ['cart' => implode(',', $metaKeys)],
    'custom_fields' => [
        ['key' => 'organization', 'label' => ['type' => 'custom', 'custom' => 'Organization / Business Affiliation'], 'type' => 'text', 'optional' => true],
    ],
];

[$code, $session] = stripe_request('POST', 'checkout/sessions', stripe_flatten($params));

if ($code >= 300 || empty($session['url'])) {
    http_response_code(502);
    echo 'Could not start checkout. Please try again shortly.';
    error_log('Stripe checkout session creation failed: ' . json_encode($session));
    exit;
}

// Clear the cart optimistically; webhook.php records the authoritative
// order once Stripe confirms payment.
$_SESSION['cart'] = [];

header('Location: ' . $session['url']);
