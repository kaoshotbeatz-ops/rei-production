<?php
require __DIR__ . '/lib.php';
require __DIR__ . '/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw = $_POST['password'] ?? '';
    if (password_verify($pw, ADMIN_PASSWORD_HASH)) {
        $_SESSION['admin_authed'] = true;
        header('Location: /admin/index.php');
        exit;
    }
    $error = 'Wrong password.';
}

if (!empty($_SESSION['admin_authed'])):
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Admin · <?= h(SITE_NAME) ?></title>
<style>
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;max-width:640px;margin:60px auto;padding:0 20px;color:#1c1c1a}
h1{font-size:24px}
.card{display:block;background:#f6f5f0;border-radius:12px;padding:20px 24px;margin-bottom:14px;text-decoration:none;color:#1c1c1a}
.card:hover{background:#eeece4}
.card h2{font-size:17px;margin:0 0 4px}
.card p{margin:0;color:#666;font-size:14px}
.logout{float:right;font-size:13px;color:#a5331d}
</style></head>
<body>
<a class="logout" href="/admin/logout.php">Log out</a>
<h1><?= h(SITE_NAME) ?> admin</h1>
<a class="card" href="/admin/trainings.php"><h2>Trainings</h2><p>Add, edit, or remove upcoming sessions.</p></a>
<a class="card" href="/admin/blog.php"><h2>Blog posts</h2><p>Add, edit, or remove blog posts.</p></a>
<a class="card" href="/admin/bibliography.php"><h2>Bibliography</h2><p>Manage the reading list and categories.</p></a>
<a class="card" href="/admin/orders.php"><h2>Orders</h2><p>See conference registrations recorded from Stripe.</p></a>
</body>
</html>
<?php
else:
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Admin login · <?= h(SITE_NAME) ?></title>
<style>
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;max-width:360px;margin:120px auto;padding:0 20px;color:#1c1c1a}
h1{font-size:22px}
input{width:100%;padding:10px;border:1px solid #ccc;border-radius:8px;margin-bottom:12px;box-sizing:border-box}
button{background:#a5331d;color:#fff;border:none;padding:10px 20px;border-radius:8px;cursor:pointer;width:100%}
.error{color:#a5331d;font-size:14px;margin-bottom:12px}
</style></head>
<body>
<h1><?= h(SITE_NAME) ?> admin</h1>
<?php if ($error): ?><p class="error"><?= h($error) ?></p><?php endif; ?>
<form method="post">
  <input type="password" name="password" placeholder="Password" autofocus required>
  <button type="submit">Log in</button>
</form>
</body>
</html>
<?php endif; ?>
