<?php
require __DIR__ . '/lib.php';
require __DIR__ . '/config.php';
admin_require_login();

$posts = admin_load_json('blog.json');

function slugify($title) {
    $s = strtolower(trim($title));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

function regenerate_blog_index($posts) {
    usort($posts, fn($a, $b) => strcmp($b['date'], $a['date']));
    $html = '';
    foreach ($posts as $p) {
        $title = h($p['title']);
        $slug = h($p['slug']);
        $excerpt = h($p['excerpt']);
        $date = h($p['date']);
        $author = h($p['author'] ?? '');
        $tag = h($p['tag'] ?? 'Blog');
        $byline = $author ? "{$author} · {$date}" : $date;
        $html .= <<<HTML

      <a class="card reveal" data-tilt href="/blog/{$slug}">
        <span class="tag" style="color:var(--teal);font-weight:700;font-size:.72rem;letter-spacing:.1em;text-transform:uppercase">{$tag}</span>
        <h3 style="margin-top:8px;font-size:1.18rem">{$title}</h3>
        <p style="color:var(--muted);font-size:.82rem;margin:6px 0 10px">{$byline}</p>
        <p>{$excerpt}</p><span class="more">Read article →</span></a>

HTML;
    }
    admin_replace_block(BLOG_INDEX_HTML_PATH, 'BLOG_INDEX', $html);
}

function write_blog_post_file($post) {
    $dir = BLOG_POSTS_DIR;
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $path = rtrim($dir, '/') . '/' . $post['slug'] . '.html';
    $body = nl2br(h($post['body']));
    $html = <<<HTML
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$post['title']} · Racial Equity Institute</title>
<link rel="stylesheet" href="/css/fonts.css"><link rel="stylesheet" href="/css/site.css"><link rel="stylesheet" href="/css/theme.css"><link rel="stylesheet" href="/css/pack.css">
</head><body>
<main class="wrap" style="max-width:720px;margin:60px auto;padding:0 20px">
<p><a href="/blog">&larr; Back to blog</a></p>
<h1>{$post['title']}</h1>
<p style="color:#888">{$post['date']}</p>
<div style="line-height:1.7;margin-top:24px">{$body}</div>
</main>
</body></html>
HTML;
    file_put_contents($path, $html);
}

$action = $_POST['action'] ?? '';
if ($action === 'save') {
    $id = $_POST['id'] ?: bin2hex(random_bytes(6));
    $title = trim($_POST['title']);
    $slug = !empty($_POST['slug']) ? trim($_POST['slug']) : slugify($title);
    $entry = [
        'id' => $id,
        'title' => $title,
        'slug' => $slug,
        'date' => trim($_POST['date']),
        'author' => trim($_POST['author'] ?? ''),
        'tag' => trim($_POST['tag'] ?? 'Blog'),
        'excerpt' => trim($_POST['excerpt']),
        'body' => trim($_POST['body']),
    ];
    $found = false;
    foreach ($posts as &$p) {
        if ($p['id'] === $id) { $p = $entry; $found = true; break; }
    }
    unset($p);
    if (!$found) $posts[] = $entry;
    admin_save_json('blog.json', $posts);
    regenerate_blog_index($posts);
    write_blog_post_file($entry);
    header('Location: /admin/blog.php?saved=1');
    exit;
} elseif ($action === 'delete') {
    $id = $_POST['id'];
    $posts = array_values(array_filter($posts, fn($p) => $p['id'] !== $id));
    admin_save_json('blog.json', $posts);
    regenerate_blog_index($posts);
    header('Location: /admin/blog.php?deleted=1');
    exit;
}

usort($posts, fn($a, $b) => strcmp($b['date'], $a['date']));
$editId = $_GET['edit'] ?? null;
$editing = null;
if ($editId) {
    foreach ($posts as $p) if ($p['id'] === $editId) { $editing = $p; break; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Blog · Admin</title>
<style>
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;max-width:820px;margin:40px auto;padding:0 20px;color:#1c1c1a}
a.back{color:#a5331d;text-decoration:none;font-size:14px}
h1{font-size:24px}
table{width:100%;border-collapse:collapse;margin:20px 0}
th,td{text-align:left;padding:8px 6px;border-bottom:1px solid #eee;font-size:14px}
form.entry{background:#f6f5f0;padding:20px;border-radius:12px;margin:20px 0}
form.entry label{display:block;font-size:13px;color:#666;margin-top:10px}
form.entry input,form.entry textarea{width:100%;padding:8px;border:1px solid #ccc;border-radius:6px;box-sizing:border-box;font-family:inherit}
form.entry textarea{min-height:160px}
button,.btn{background:#a5331d;color:#fff;border:none;padding:8px 16px;border-radius:8px;cursor:pointer;font-size:14px;text-decoration:none;display:inline-block}
button.secondary{background:#eee;color:#333}
.notice{background:#eaf3de;color:#27500a;padding:10px 14px;border-radius:8px;margin-bottom:16px}
.row-actions form{display:inline}
.row-actions a, .row-actions button{font-size:12px;padding:4px 8px}
</style></head>
<body>
<a class="back" href="/admin/index.php">← Admin home</a>
<h1>Blog posts</h1>
<?php if (isset($_GET['saved'])): ?><p class="notice">Saved and published (index page and post page both updated).</p><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><p class="notice">Removed from the index. The post file itself was left in place (not linked).</p><?php endif; ?>

<form class="entry" method="post">
  <input type="hidden" name="action" value="save">
  <input type="hidden" name="id" value="<?= h($editing['id'] ?? '') ?>">
  <label>Title <input name="title" required value="<?= h($editing['title'] ?? '') ?>"></label>
  <label>URL slug (leave blank to auto-generate from title) <input name="slug" value="<?= h($editing['slug'] ?? '') ?>"></label>
  <label>Date <input name="date" type="date" required value="<?= h($editing['date'] ?? '') ?>"></label>
  <label>Author <input name="author" value="<?= h($editing['author'] ?? '') ?>"></label>
  <label>Tag/category (e.g. "History", "Policy") <input name="tag" value="<?= h($editing['tag'] ?? 'Blog') ?>"></label>
  <label>Excerpt (shown on the blog index) <input name="excerpt" required value="<?= h($editing['excerpt'] ?? '') ?>"></label>
  <label>Body <textarea name="body" required><?= h($editing['body'] ?? '') ?></textarea></label>
  <div style="margin-top:14px"><button type="submit">Save and publish</button></div>
</form>

<table>
  <thead><tr><th>Date</th><th>Title</th><th>Slug</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($posts as $p): ?>
    <tr>
      <td><?= h($p['date']) ?></td>
      <td><?= h($p['title']) ?></td>
      <td><?= h($p['slug']) ?></td>
      <td class="row-actions">
        <a class="btn secondary" href="/admin/blog.php?edit=<?= urlencode($p['id']) ?>">Edit</a>
        <form method="post"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= h($p['id']) ?>"><button type="submit" class="secondary" onclick="return confirm('Remove this post from the index?')">Delete</button></form>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</body>
</html>
