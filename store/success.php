<?php
require __DIR__ . '/config.php';

function stripe_get($path) {
    $ch = curl_init("https://api.stripe.com/v1/$path");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SECRET_KEY . ':');
    $resp = curl_exec($ch);
    return json_decode($resp, true);
}

$sessionId = $_GET['session_id'] ?? '';
$session = $sessionId ? stripe_get('checkout/sessions/' . urlencode($sessionId)) : null;
$email = $session['customer_details']['email'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Registration confirmed · Racial Equity Institute</title>
<style>
  body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif; max-width: 600px; margin: 80px auto; padding: 0 20px; text-align: center; color: #1c1c1a; }
  h1 { font-size: 26px; }
  a { color: #a5331d; }
</style>
</head>
<body>
<h1>You're registered.</h1>
<?php if ($email): ?>
<p>A confirmation has been sent to <strong><?= htmlspecialchars($email) ?></strong>.</p>
<?php else: ?>
<p>Your payment was received.</p>
<?php endif; ?>
<p><a href="/">Return to the homepage</a></p>
</body>
</html>
