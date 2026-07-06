<?php
// Shared capture endpoint for the contact form and newsletter signup.
// Stores every submission into admin/data/contacts.json (the same
// protected data directory the admin panel already uses) so a page owner
// can browse them from /admin/contacts.php — Squarespace's own Contacts
// panel did this automatically; this replaces that for the decoupled site.
// Email notification is best-effort (plain mail()) since no SMTP relay is
// configured yet — capture always succeeds even if the email doesn't send.
require __DIR__ . '/admin/lib.php';
require __DIR__ . '/store/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

function field($key) {
    return isset($_POST[$key]) ? trim(substr((string)$_POST[$key], 0, 5000)) : '';
}

$type = field('type') ?: 'contact';
$name = field('name');
if ($name === '') {
    $first = field('first');
    $last = field('last');
    $name = trim("$first $last");
}
$email = filter_var(field('email'), FILTER_VALIDATE_EMAIL) ?: '';
$organization = field('organization');
$subject = field('subject') ?: ($type === 'subscribe' ? 'Newsletter signup' : 'Website contact form');
$message = field('message') ?: field('body');

if ($email === '') {
    http_response_code(400);
    echo 'A valid email is required.';
    exit;
}

$contacts = admin_load_json('contacts.json');
$entry = [
    'id' => bin2hex(random_bytes(6)),
    'type' => $type,
    'name' => $name,
    'email' => $email,
    'organization' => $organization,
    'subject' => $subject,
    'message' => $message,
    'created' => time(),
];
$contacts[] = $entry;
admin_save_json('contacts.json', $contacts);

$body = "Name: $name\nEmail: $email\n" . ($organization ? "Organization: $organization\n" : '') . "\n$message";
@mail(NOTIFY_EMAIL, "[Website] $subject", $body, "From: $email");

$wantsJson = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (field('ajax') !== '');
if ($wantsJson) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

$redirect = field('redirect') ?: '/contact';
$sep = strpos($redirect, '?') !== false ? '&' : '?';
header('Location: ' . $redirect . $sep . 'sent=1');
