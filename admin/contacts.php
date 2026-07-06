<?php
require __DIR__ . '/lib.php';
require __DIR__ . '/config.php';
admin_require_login();

$contacts = admin_load_json('contacts.json');
usort($contacts, fn($a, $b) => ($b['created'] ?? 0) <=> ($a['created'] ?? 0));

$filter = $_GET['type'] ?? 'all';
$filtered = $filter === 'all' ? $contacts : array_values(array_filter($contacts, fn($c) => ($c['type'] ?? 'contact') === $filter));

$counts = ['all' => count($contacts), 'contact' => 0, 'subscribe' => 0];
foreach ($contacts as $c) {
    $t = $c['type'] ?? 'contact';
    if (isset($counts[$t])) $counts[$t]++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Contacts · Admin</title>
<style>
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;max-width:900px;margin:40px auto;padding:0 20px;color:#1c1c1a}
a.back{color:#a5331d;text-decoration:none;font-size:14px}
h1{font-size:24px}
.tabs{display:flex;gap:8px;margin:16px 0 20px}
.tabs a{padding:6px 14px;border-radius:20px;font-size:13px;text-decoration:none;color:#444;background:#f1efe8}
.tabs a.active{background:#a5331d;color:#fff}
table{width:100%;border-collapse:collapse;margin:10px 0}
th,td{text-align:left;padding:8px 6px;border-bottom:1px solid #eee;font-size:13.5px;vertical-align:top}
.badge{display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600}
.badge.contact{background:#e6f1fb;color:#0c447c}
.badge.subscribe{background:#eaf3de;color:#27500a}
.empty{color:#666;padding:30px 0;text-align:center}
.msg{max-width:320px;white-space:pre-wrap}
.hint{color:#888;font-size:13px}
</style></head>
<body>
<a class="back" href="/admin/index.php">← Admin home</a>
<h1>Contacts</h1>
<p class="hint">Every contact-form and newsletter submission, captured automatically. This is the closest equivalent to Squarespace's own Contacts panel.</p>

<div class="tabs">
  <a href="?type=all" class="<?= $filter==='all'?'active':'' ?>">All (<?= $counts['all'] ?>)</a>
  <a href="?type=contact" class="<?= $filter==='contact'?'active':'' ?>">Contact form (<?= $counts['contact'] ?>)</a>
  <a href="?type=subscribe" class="<?= $filter==='subscribe'?'active':'' ?>">Newsletter (<?= $counts['subscribe'] ?>)</a>
</div>

<?php if (empty($filtered)): ?>
  <p class="empty">No submissions yet.</p>
<?php else: ?>
<table>
  <thead><tr><th>Date</th><th>Type</th><th>Name</th><th>Email</th><th>Subject / message</th></tr></thead>
  <tbody>
    <?php foreach ($filtered as $c): ?>
    <tr>
      <td><?= $c['created'] ? date('M j, Y g:ia', $c['created']) : '—' ?></td>
      <td><span class="badge <?= h($c['type'] ?? 'contact') ?>"><?= h($c['type'] ?? 'contact') ?></span></td>
      <td><?= h($c['name'] ?? '') ?><?php if (!empty($c['organization'])): ?><br><span class="hint"><?= h($c['organization']) ?></span><?php endif; ?></td>
      <td><a href="mailto:<?= h($c['email']) ?>"><?= h($c['email']) ?></a></td>
      <td class="msg"><strong><?= h($c['subject'] ?? '') ?></strong><?php if (!empty($c['message'])): ?><br><?= h($c['message']) ?><?php endif; ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
</body>
</html>
