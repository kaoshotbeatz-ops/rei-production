<?php
// Stripe webhook: on checkout.session.completed, record the order (so stock
// caps stay accurate) and email a notification.
require __DIR__ . '/config.php';
$products = require __DIR__ . '/products.php';

$payload = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

function verify_stripe_signature($payload, $sigHeader, $secret) {
    $parts = [];
    foreach (explode(',', $sigHeader) as $pair) {
        [$k, $v] = array_pad(explode('=', $pair, 2), 2, null);
        $parts[$k] = $v;
    }
    if (empty($parts['t']) || empty($parts['v1'])) return false;
    $expected = hash_hmac('sha256', $parts['t'] . '.' . $payload, $secret);
    return hash_equals($expected, $parts['v1']);
}

if (!verify_stripe_signature($payload, $sigHeader, STRIPE_WEBHOOK_SECRET)) {
    http_response_code(400);
    exit('Invalid signature');
}

$event = json_decode($payload, true);
if (($event['type'] ?? '') !== 'checkout.session.completed') {
    http_response_code(200);
    exit('Ignored');
}

$session = $event['data']['object'];
$cartMeta = $session['metadata']['cart'] ?? '';
$sessionId = $session['id'] ?? '';

$dir = __DIR__ . '/data';
if (!is_dir($dir)) @mkdir($dir, 0755, true);
$ordersFile = $dir . '/orders.json';
$orders = file_exists($ordersFile) ? (json_decode(file_get_contents($ordersFile), true) ?: []) : [];

// Idempotency: skip if we've already recorded this Stripe session.
foreach ($orders as $o) {
    if (($o['session_id'] ?? '') === $sessionId) { http_response_code(200); exit('Already recorded'); }
}

$recorded = [];
foreach (explode(',', $cartMeta) as $pair) {
    if (!$pair) continue;
    [$key, $qty] = array_pad(explode(':', $pair, 2), 2, [null, 1]);
    if (!$key || !isset($products[$key])) continue;
    $order = [
        'session_id' => $sessionId,
        'product_key' => $key,
        'quantity' => (int)$qty,
        'email' => $session['customer_details']['email'] ?? null,
        'amount_total' => $session['amount_total'] ?? null,
        'created' => time(),
    ];
    $orders[] = $order;
    $recorded[] = $order;
}
file_put_contents($ordersFile, json_encode($orders, JSON_PRETTY_PRINT));

if ($recorded) {
    $lines = array_map(fn($o) => "- {$o['product_key']} x{$o['quantity']}", $recorded);
    $body = "New registration(s):\n" . implode("\n", $lines) . "\n\nEmail: " . ($recorded[0]['email'] ?? 'n/a');
    @mail(NOTIFY_EMAIL, 'New conference registration', $body);
}

http_response_code(200);
echo 'OK';
