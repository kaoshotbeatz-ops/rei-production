<?php
require __DIR__ . '/lib.php';
require __DIR__ . '/config.php';
admin_require_login();

$ordersPath = STORE_ORDERS_JSON_PATH;
$orders = file_exists($ordersPath) ? (json_decode(file_get_contents($ordersPath), true) ?: []) : [];
usort($orders, fn($a, $b) => ($b['created'] ?? 0) <=> ($a['created'] ?? 0));
$products = require STORE_PRODUCTS_PHP_PATH;

$totalCents = 0;
foreach ($orders as $o) $totalCents += ($o['amount_total'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Orders · Admin</title>
<style>
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;max-width:820px;margin:40px auto;padding:0 20px;color:#1c1c1a}
a.back{color:#a5331d;text-decoration:none;font-size:14px}
h1{font-size:24px}
table{width:100%;border-collapse:collapse;margin:20px 0}
th,td{text-align:left;padding:8px 6px;border-bottom:1px solid #eee;font-size:14px}
.empty{color:#666;padding:30px 0;text-align:center}
.summary{background:#f6f5f0;padding:16px 20px;border-radius:12px;margin-bottom:20px;display:flex;gap:30px}
.summary div span{display:block;font-size:12px;color:#666;text-transform:uppercase}
.summary div strong{font-size:20px}
</style></head>
<body>
<a class="back" href="/admin/index.php">← Admin home</a>
<h1>Orders</h1>
<p style="color:#666;font-size:14px">Recorded automatically by the Stripe webhook when a checkout completes. Read-only.</p>

<div class="summary">
  <div><span>Total orders</span><strong><?= count($orders) ?></strong></div>
  <div><span>Total collected</span><strong>$<?= number_format($totalCents / 100, 2) ?></strong></div>
</div>

<?php if (empty($orders)): ?>
  <p class="empty">No orders recorded yet.</p>
<?php else: ?>
<table>
  <thead><tr><th>Date</th><th>Product</th><th>Qty</th><th>Email</th><th>Amount</th></tr></thead>
  <tbody>
    <?php foreach ($orders as $o): ?>
    <tr>
      <td><?= $o['created'] ? date('M j, Y g:ia', $o['created']) : '—' ?></td>
      <td><?= h($products[$o['product_key']]['name'] ?? $o['product_key']) ?></td>
      <td><?= (int)($o['quantity'] ?? 1) ?></td>
      <td><?= h($o['email'] ?? '—') ?></td>
      <td><?= $o['amount_total'] !== null ? '$' . number_format($o['amount_total'] / 100, 2) : '—' ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
</body>
</html>
