<?php
require __DIR__ . '/lib.php';
require __DIR__ . '/config.php';
admin_require_login();

$trainings = admin_load_json('trainings.json');

function regenerate_trainings_html($trainings) {
    // Sort by date so the live page always lists soonest-first.
    usort($trainings, fn($a, $b) => strcmp($a['date'], $b['date']));
    $today = date('Y-m-d');
    $html = '';
    foreach ($trainings as $t) {
        if (($t['hidden'] ?? false)) continue;
        $chip = h($t['format']);
        $title = h($t['title']);
        $when = h($t['when']);
        $note = !empty($t['note']) ? '<p style="color:var(--muted);font-size:.88rem">' . h($t['note']) . '</p>' : '';
        $link = h($t['link']);
        $html .= <<<HTML

      <div class="reveal tcard" style="border-radius:16px;padding:24px;display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap">
        <div>
          <span class="chip" style="margin-bottom:8px">{$chip}</span>
          <h3 style="font-size:1.25rem">{$title}</h3>
          <p style="color:var(--muted);margin-top:4px">{$when}</p>
          {$note}
        </div>
        <a href="{$link}" target="_blank" rel="noopener" class="btn btn-dark" data-magnetic>Register →</a>
      </div>

HTML;
    }
    admin_replace_block(TRAININGS_HTML_PATH, 'TRAININGS', $html);
}

$action = $_POST['action'] ?? '';
if ($action === 'save') {
    $id = $_POST['id'] ?: bin2hex(random_bytes(6));
    $entry = [
        'id' => $id,
        'title' => trim($_POST['title']),
        'date' => trim($_POST['date']), // YYYY-MM-DD, used for sorting/auto-hide
        'when' => trim($_POST['when']), // human-readable display string
        'format' => trim($_POST['format']),
        'location' => trim($_POST['location'] ?? ''),
        'note' => trim($_POST['note'] ?? ''),
        'link' => trim($_POST['link']),
        'hidden' => false,
    ];
    $found = false;
    foreach ($trainings as &$t) {
        if ($t['id'] === $id) { $t = $entry; $found = true; break; }
    }
    unset($t);
    if (!$found) $trainings[] = $entry;
    admin_save_json('trainings.json', $trainings);
    regenerate_trainings_html($trainings);
    header('Location: /admin/trainings.php?saved=1');
    exit;
} elseif ($action === 'delete') {
    $id = $_POST['id'];
    $trainings = array_values(array_filter($trainings, fn($t) => $t['id'] !== $id));
    admin_save_json('trainings.json', $trainings);
    regenerate_trainings_html($trainings);
    header('Location: /admin/trainings.php?deleted=1');
    exit;
} elseif ($action === 'hide_past') {
    $today = date('Y-m-d');
    foreach ($trainings as &$t) {
        if ($t['date'] < $today) $t['hidden'] = true;
    }
    unset($t);
    admin_save_json('trainings.json', $trainings);
    regenerate_trainings_html($trainings);
    header('Location: /admin/trainings.php?cleaned=1');
    exit;
}

usort($trainings, fn($a, $b) => strcmp($a['date'], $b['date']));
$today = date('Y-m-d');
$editId = $_GET['edit'] ?? null;
$editing = null;
if ($editId) {
    foreach ($trainings as $t) if ($t['id'] === $editId) { $editing = $t; break; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Trainings · Admin</title>
<style>
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;max-width:820px;margin:40px auto;padding:0 20px;color:#1c1c1a}
a.back{color:#a5331d;text-decoration:none;font-size:14px}
h1{font-size:24px}
table{width:100%;border-collapse:collapse;margin:20px 0}
th,td{text-align:left;padding:8px 6px;border-bottom:1px solid #eee;font-size:14px}
tr.expired{color:#999}
.badge{background:#fcebeb;color:#791f1f;padding:2px 8px;border-radius:10px;font-size:11px}
form.entry{background:#f6f5f0;padding:20px;border-radius:12px;margin:20px 0}
form.entry label{display:block;font-size:13px;color:#666;margin-top:10px}
form.entry input{width:100%;padding:8px;border:1px solid #ccc;border-radius:6px;box-sizing:border-box}
button,.btn{background:#a5331d;color:#fff;border:none;padding:8px 16px;border-radius:8px;cursor:pointer;font-size:14px;text-decoration:none;display:inline-block}
button.secondary{background:#eee;color:#333}
.notice{background:#eaf3de;color:#27500a;padding:10px 14px;border-radius:8px;margin-bottom:16px}
.row-actions form{display:inline}
.row-actions a, .row-actions button{font-size:12px;padding:4px 8px}
</style></head>
<body>
<a class="back" href="/admin/index.php">← Admin home</a>
<h1>Trainings</h1>
<?php if (isset($_GET['saved'])): ?><p class="notice">Saved and published to the live page.</p><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><p class="notice">Removed and published.</p><?php endif; ?>
<?php if (isset($_GET['cleaned'])): ?><p class="notice">Past sessions hidden and published.</p><?php endif; ?>

<form method="post" style="margin-bottom:20px">
  <input type="hidden" name="action" value="hide_past">
  <button type="submit" class="secondary">Hide all past sessions now</button>
</form>

<form class="entry" method="post">
  <input type="hidden" name="action" value="save">
  <input type="hidden" name="id" value="<?= h($editing['id'] ?? '') ?>">
  <label>Title <input name="title" required value="<?= h($editing['title'] ?? '') ?>"></label>
  <label>Date (for sorting/hiding, YYYY-MM-DD) <input name="date" type="date" required value="<?= h($editing['date'] ?? '') ?>"></label>
  <label>Display text (e.g. "July 20–21, 2026 · 9:00 AM – 5:00 PM · Los Angeles, CA") <input name="when" required value="<?= h($editing['when'] ?? '') ?>"></label>
  <label>Format (e.g. "In-person" or "Virtual") <input name="format" required value="<?= h($editing['format'] ?? '') ?>"></label>
  <label>Note (optional, e.g. "Hosted by CARA") <input name="note" value="<?= h($editing['note'] ?? '') ?>"></label>
  <label>Registration link <input name="link" required value="<?= h($editing['link'] ?? '') ?>"></label>
  <div style="margin-top:14px"><button type="submit">Save and publish</button></div>
</form>

<table>
  <thead><tr><th>Date</th><th>Title</th><th>Format</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($trainings as $t): ?>
    <tr class="<?= $t['date'] < $today ? 'expired' : '' ?>">
      <td><?= h($t['date']) ?> <?= $t['date'] < $today ? '<span class="badge">expired</span>' : '' ?></td>
      <td><?= h($t['title']) ?></td>
      <td><?= h($t['format']) ?></td>
      <td class="row-actions">
        <a class="btn secondary" href="/admin/trainings.php?edit=<?= urlencode($t['id']) ?>">Edit</a>
        <form method="post"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= h($t['id']) ?>"><button type="submit" class="secondary" onclick="return confirm('Remove this session?')">Delete</button></form>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</body>
</html>
