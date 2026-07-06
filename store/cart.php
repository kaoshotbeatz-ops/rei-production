<?php
// Shared shopping cart: session-based, holds {product_key => quantity}.
// GET renders the cart page. POST with action=add/remove/update/clear
// mutates the session cart, then redirects back to the cart page.
session_start();
require __DIR__ . '/config.php';
$products = require __DIR__ . '/products.php';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

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

// How many of $key are still available, accounting for what's already
// completed (orders.json) AND what's already sitting in this cart.
function store_available($key, $products, $excludeCartQty = 0) {
    $product = $products[$key];
    $cap = $product['stock'] ?? ($GLOBALS['STORE_GROUP_CAPS'][$product['group'] ?? ''] ?? null);
    if ($cap === null) return null; // unlimited
    $sold = store_sold_count($key, $products);
    return max(0, $cap - $sold - $excludeCartQty);
}

$action = $_POST['action'] ?? '';
$key = $_POST['product'] ?? '';
$errors = [];

if ($action === 'add' && isset($products[$key])) {
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    $current = $_SESSION['cart'][$key] ?? 0;
    $group = $products[$key]['group'] ?? null;
    $cartGroupQty = 0;
    foreach ($_SESSION['cart'] as $k => $q) {
        if ($k === $key) continue;
        if ($group && ($products[$k]['group'] ?? null) === $group) $cartGroupQty += $q;
    }
    $available = store_available($key, $products, $cartGroupQty);
    $newQty = $current + $qty;
    if ($available !== null && $newQty > $available) {
        $errors[] = $products[$key]['name'] . ': only ' . $available . ' left.';
        $newQty = $current + max(0, $available - $current);
    }
    if ($newQty > 0) {
        $_SESSION['cart'][$key] = $newQty;
    }
    $_SESSION['cart_errors'] = $errors;
} elseif ($action === 'update' && isset($products[$key])) {
    $qty = max(0, (int)($_POST['qty'] ?? 0));
    if ($qty === 0) {
        unset($_SESSION['cart'][$key]);
    } else {
        $_SESSION['cart'][$key] = $qty;
    }
} elseif ($action === 'remove' && isset($_SESSION['cart'][$key])) {
    unset($_SESSION['cart'][$key]);
} elseif ($action === 'clear') {
    $_SESSION['cart'] = [];
}

// After any mutation via POST, redirect back to this same page (GET) so a
// page refresh doesn't resubmit the form.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: ' . SITE_URL . '/cart');
    exit;
}

$cartErrors = $_SESSION['cart_errors'] ?? [];
unset($_SESSION['cart_errors']);

$items = [];
$total = 0;
foreach ($_SESSION['cart'] as $k => $qty) {
    if (!isset($products[$k])) continue;
    $p = $products[$k];
    $lineTotal = $p['amount'] * $qty;
    $total += $lineTotal;
    $items[] = ['key' => $k, 'name' => $p['name'], 'qty' => $qty, 'amount' => $p['amount'], 'lineTotal' => $lineTotal];
}
function fmt_price($cents) { return '$' . number_format($cents / 100, 2); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cart · Racial Equity Institute</title>
<style>
  body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif; max-width: 720px; margin: 0 auto; padding: 40px 20px 80px; color: #1c1c1a; }
  h1 { font-size: 28px; margin-bottom: 24px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
  th { text-align: left; font-size: 12px; text-transform: uppercase; color: #666; padding: 8px 6px; border-bottom: 2px solid #eee; }
  td { padding: 12px 6px; border-bottom: 1px solid #eee; vertical-align: middle; }
  input[type=number] { width: 56px; padding: 6px; border: 1px solid #ccc; border-radius: 6px; }
  button, .btn { background: #a5331d; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-size: 15px; cursor: pointer; text-decoration: none; display: inline-block; }
  button.secondary, .btn.secondary { background: #eee; color: #333; }
  .remove-btn { background: none; color: #a5331d; border: none; text-decoration: underline; cursor: pointer; font-size: 13px; padding: 0; }
  .empty { padding: 40px 0; text-align: center; color: #666; }
  .errors { background: #fcebeb; color: #791f1f; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; }
  .total-row td { font-weight: 600; font-size: 18px; border-top: 2px solid #333; border-bottom: none; }
  .actions { display: flex; gap: 12px; margin-top: 24px; }
  form.inline { display: inline; }
</style>
</head>
<body>
<h1>Your cart</h1>

<?php if ($cartErrors): ?>
<div class="errors"><?php foreach ($cartErrors as $e) echo '<div>' . htmlspecialchars($e) . '</div>'; ?></div>
<?php endif; ?>

<?php if (empty($items)): ?>
  <div class="empty">
    <p>Your cart is empty.</p>
    <a class="btn" href="/equity-paradox-conference">Browse registration options</a>
  </div>
<?php else: ?>
  <table>
    <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($items as $item): ?>
      <tr>
        <td><?= htmlspecialchars($item['name']) ?></td>
        <td>
          <form class="inline" method="post" action="/store/cart.php">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="product" value="<?= htmlspecialchars($item['key']) ?>">
            <input type="number" name="qty" min="0" value="<?= $item['qty'] ?>" onchange="this.form.submit()">
          </form>
        </td>
        <td><?= fmt_price($item['lineTotal']) ?></td>
        <td>
          <form class="inline" method="post" action="/store/cart.php">
            <input type="hidden" name="action" value="remove">
            <input type="hidden" name="product" value="<?= htmlspecialchars($item['key']) ?>">
            <button type="submit" class="remove-btn">Remove</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <tr class="total-row"><td colspan="2">Total</td><td colspan="2"><?= fmt_price($total) ?></td></tr>
    </tbody>
  </table>
  <div class="actions">
    <form method="post" action="/store/checkout.php">
      <button type="submit">Checkout</button>
    </form>
    <form method="post" action="/store/cart.php">
      <input type="hidden" name="action" value="clear">
      <button type="submit" class="secondary">Clear cart</button>
    </form>
    <a class="btn secondary" href="/equity-paradox-conference">Add more items</a>
  </div>
<?php endif; ?>

</body>
</html>
