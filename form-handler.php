<?php
// REI website form handler — emails submissions. (TEST destination: omar@dbaomarhuertasllc.com)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /'); exit; }
function f($k){ return isset($_POST[$k]) ? trim(substr((string)$_POST[$k], 0, 5000)) : ''; }

$first = f('first'); $last = f('last'); $name = f('name');
$fullname = $name !== '' ? $name : trim($first.' '.$last);
$email    = filter_var(f('email'), FILTER_VALIDATE_EMAIL) ?: '';
$message  = f('message');
$subject  = f('subject') !== '' ? f('subject') : 'Website “Have Questions?” form';
$page     = f('page');

$to    = 'omar@dbaomarhuertasllc.com';                 // TEST recipient
$from  = 'noreply@racialequityinstitute.org';          // SPF-aligned (secureserver.net)
$body  = "New message from the Racial Equity Institute website\n"
       . "--------------------------------------------------\n"
       . "Name:    ".($fullname ?: '(none)')."\n"
       . "Email:   ".($email ?: '(none)')."\n"
       . "Subject: $subject\n"
       . ($page ? "Page:    $page\n" : "")
       . "\nMessage:\n".($message ?: '(none)')."\n";

$headers  = "From: REI Website <$from>\r\n";
if ($email) $headers .= "Reply-To: ".($fullname ?: 'Visitor')." <$email>\r\n";
$headers .= "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";

$ok = @mail($to, "[REI Website] $subject", $body, $headers, "-f$from");

header('Content-Type: text/html; charset=UTF-8');
echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Thank you</title>'
   . '<div style="font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Arial,sans-serif;max-width:560px;margin:90px auto;padding:0 20px;text-align:center;color:#1e211c">'
   . ($ok
        ? '<h1 style="font-family:Georgia,serif;font-weight:400">Thank you</h1><p style="color:#4a4f45;line-height:1.6">We&rsquo;ve received your message and will be in touch within 48 hours.</p>'
        : '<h1 style="font-family:Georgia,serif;font-weight:400">Hmm&mdash;something went wrong</h1><p style="color:#4a4f45">Please email us directly at <a href="mailto:info@racialequityinstitute.org" style="color:#586751">info@racialequityinstitute.org</a>.</p>')
   . '<p style="margin-top:34px"><a href="/" style="color:#586751;text-decoration:none">&larr; Back to the site</a></p></div>';
