<?php
require __DIR__ . '/lib.php';
require __DIR__ . '/config.php';
admin_require_login();

// Structure: [{cat: "...", books: [{title, author}, ...]}, ...]
$categories = admin_load_json('bibliography.json');

function regenerate_bibliography_html($categories) {
    $html = '';
    foreach ($categories as $cat) {
        $catName = h($cat['cat']);
        $html .= "\n    <div style=\"margin-bottom:42px\">\n      <h2 style=\"font-size:1.5rem;margin-bottom:16px;color:#333\">{$catName}</h2>\n      <div style=\"display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px\">\n";
        foreach ($cat['books'] as $book) {
            $entry = h($book['title'] . ' — ' . $book['author']);
            $html .= "        <div style=\"padding:18px 22px;border:1px solid #e2e0d7;border-radius:10px;background:#fff\"><p style=\"color:#1c1c1a;font-weight:500;margin:0\">📖 {$entry}</p></div>\n";
        }
        $html .= "      </div>\n    </div>\n";
    }
    admin_replace_block(BIBLIOGRAPHY_HTML_PATH, 'BIBLIOGRAPHY', $html);
}

$action = $_POST['action'] ?? '';
if ($action === 'add_book') {
    $catIdx = (int)$_POST['cat_idx'];
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    if (isset($categories[$catIdx]) && $title && $author) {
        $categories[$catIdx]['books'][] = ['title' => $title, 'author' => $author];
    }
    admin_save_json('bibliography.json', $categories);
    regenerate_bibliography_html($categories);
    header('Location: /admin/bibliography.php?saved=1');
    exit;
} elseif ($action === 'remove_book') {
    $catIdx = (int)$_POST['cat_idx'];
    $bookIdx = (int)$_POST['book_idx'];
    if (isset($categories[$catIdx]['books'][$bookIdx])) {
        array_splice($categories[$catIdx]['books'], $bookIdx, 1);
    }
    admin_save_json('bibliography.json', $categories);
    regenerate_bibliography_html($categories);
    header('Location: /admin/bibliography.php?removed=1');
    exit;
} elseif ($action === 'add_category') {
    $name = trim($_POST['cat_name']);
    if ($name) $categories[] = ['cat' => $name, 'books' => []];
    admin_save_json('bibliography.json', $categories);
    regenerate_bibliography_html($categories);
    header('Location: /admin/bibliography.php?saved=1');
    exit;
} elseif ($action === 'remove_category') {
    $catIdx = (int)$_POST['cat_idx'];
    if (isset($categories[$catIdx])) array_splice($categories, $catIdx, 1);
    admin_save_json('bibliography.json', $categories);
    regenerate_bibliography_html($categories);
    header('Location: /admin/bibliography.php?removed=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Bibliography · Admin</title>
<style>
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;max-width:820px;margin:40px auto;padding:0 20px;color:#1c1c1a}
a.back{color:#a5331d;text-decoration:none;font-size:14px}
h1{font-size:24px}
.cat{background:#f6f5f0;padding:16px 20px;border-radius:12px;margin-bottom:16px}
.cat h2{font-size:16px;margin:0 0 10px}
.book{display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid #e5e3da;font-size:14px}
.book:last-of-type{border-bottom:none}
form.inline{display:inline;margin:0}
input{padding:6px 8px;border:1px solid #ccc;border-radius:6px;font-size:13px}
button,.btn{background:#a5331d;color:#fff;border:none;padding:6px 12px;border-radius:6px;cursor:pointer;font-size:13px}
button.secondary{background:#eee;color:#333}
.notice{background:#eaf3de;color:#27500a;padding:10px 14px;border-radius:8px;margin-bottom:16px}
.addform{display:flex;gap:8px;margin-top:10px}
.addform input{flex:1}
</style></head>
<body>
<a class="back" href="/admin/index.php">← Admin home</a>
<h1>Bibliography</h1>
<?php if (isset($_GET['saved'])): ?><p class="notice">Saved and published.</p><?php endif; ?>
<?php if (isset($_GET['removed'])): ?><p class="notice">Removed and published.</p><?php endif; ?>

<?php foreach ($categories as $i => $cat): ?>
<div class="cat">
  <h2><?= h($cat['cat']) ?>
    <form class="inline" method="post" style="float:right"><input type="hidden" name="action" value="remove_category"><input type="hidden" name="cat_idx" value="<?= $i ?>"><button type="submit" class="secondary" onclick="return confirm('Remove this whole category and its books?')">Remove category</button></form>
  </h2>
  <?php foreach ($cat['books'] as $j => $book): ?>
  <div class="book">
    <span><?= h($book['title']) ?> — <?= h($book['author']) ?></span>
    <form class="inline" method="post"><input type="hidden" name="action" value="remove_book"><input type="hidden" name="cat_idx" value="<?= $i ?>"><input type="hidden" name="book_idx" value="<?= $j ?>"><button type="submit" class="secondary">Remove</button></form>
  </div>
  <?php endforeach; ?>
  <form class="addform" method="post">
    <input type="hidden" name="action" value="add_book">
    <input type="hidden" name="cat_idx" value="<?= $i ?>">
    <input name="title" placeholder="Book title" required>
    <input name="author" placeholder="Author" required>
    <button type="submit">Add book</button>
  </form>
</div>
<?php endforeach; ?>

<form class="addform" method="post" style="margin-top:24px">
  <input type="hidden" name="action" value="add_category">
  <input name="cat_name" placeholder="New category name" required>
  <button type="submit">Add category</button>
</form>
</body>
</html>
