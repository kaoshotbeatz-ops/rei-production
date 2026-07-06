<?php
session_start();

function admin_require_login() {
    if (empty($_SESSION['admin_authed'])) {
        header('Location: /admin/index.php');
        exit;
    }
}

function admin_data_path($file) {
    $dir = __DIR__ . '/data';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    return $dir . '/' . $file;
}

function admin_load_json($file, $default = []) {
    $path = admin_data_path($file);
    if (!file_exists($path)) return $default;
    return json_decode(file_get_contents($path), true) ?: $default;
}

function admin_save_json($file, $data) {
    file_put_contents(admin_data_path($file), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

// Replaces the content between "<!-- ADMIN:$name:START -->" and
// "<!-- ADMIN:$name:END -->" in $htmlPath with $newInnerHtml, preserving the
// markers themselves. Throws if the markers aren't found, so a missing
// marker fails loudly instead of silently no-op'ing.
function admin_replace_block($htmlPath, $name, $newInnerHtml) {
    $html = file_get_contents($htmlPath);
    $start = "<!-- ADMIN:$name:START -->";
    $end = "<!-- ADMIN:$name:END -->";
    $startPos = strpos($html, $start);
    $endPos = strpos($html, $end);
    if ($startPos === false || $endPos === false) {
        throw new Exception("Markers for $name not found in $htmlPath");
    }
    $before = substr($html, 0, $startPos + strlen($start));
    $after = substr($html, $endPos);
    $newHtml = $before . "\n" . $newInnerHtml . "\n  " . $after;
    file_put_contents($htmlPath, $newHtml);
}

function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES); }
