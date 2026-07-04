<?php
/* REI contact-form handler — sends via Microsoft 365 (authenticated SMTP). Config in smtp-config.php (not in git). */
require __DIR__.'/lib/PHPMailer/Exception.php';
require __DIR__.'/lib/PHPMailer/PHPMailer.php';
require __DIR__.'/lib/PHPMailer/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /'); exit; }
$cfg = @include __DIR__.'/smtp-config.php';
function f($k){ return isset($_POST[$k]) ? trim(substr((string)$_POST[$k],0,5000)) : ''; }
$first=f('first'); $last=f('last'); $name=f('name');
$fullname = $name!=='' ? $name : trim($first.' '.$last);
$email    = filter_var(f('email'), FILTER_VALIDATE_EMAIL) ?: '';
$message  = f('message'); $subject = f('subject')?:'Website "Have Questions?" form'; $page=f('page');

$ok=false; $err='';
if (!is_array($cfg)) { $err='smtp-config.php missing on server'; }
else {
  $mail = new PHPMailer(true);
  try {
    $mail->isSMTP();
    $mail->Host = $cfg['host']; $mail->Port = (int)$cfg['port'];
    $mail->SMTPAuth = true; $mail->Username = $cfg['user']; $mail->Password = $cfg['pass'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->setFrom($cfg['from'], 'REI Website');
    foreach (explode(',', $cfg['to']) as $t) { $t=trim($t); if($t) $mail->addAddress($t); }
    if ($email) $mail->addReplyTo($email, $fullname ?: 'Visitor');
    $mail->Subject = '[REI Website] '.$subject;
    $mail->Body = "New message from the Racial Equity Institute website\n"
      . "--------------------------------------------------\n"
      . "Name:    ".($fullname?:'(none)')."\nEmail:   ".($email?:'(none)')."\nSubject: $subject\n"
      . ($page?"Page:    $page\n":'')."\nMessage:\n".($message?:'(none)')."\n";
    $mail->send(); $ok=true;
  } catch (Exception $e) { $err = $mail->ErrorInfo ?: $e->getMessage(); }
}

header('Content-Type: text/html; charset=UTF-8');
echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.($ok?'Thank you':'Message not sent').'</title>'
 . '<div style="font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Arial,sans-serif;max-width:560px;margin:90px auto;padding:0 20px;text-align:center;color:#1e211c">'
 . ($ok
     ? '<h1 style="font-family:Georgia,serif;font-weight:400">Thank you</h1><p style="color:#4a4f45;line-height:1.6">We&rsquo;ve received your message and will be in touch within 48 hours.</p>'
     : '<h1 style="font-family:Georgia,serif;font-weight:400">Sorry &mdash; that didn&rsquo;t send</h1><p style="color:#4a4f45">Please email us directly at <a href="mailto:info@racialequityinstitute.org" style="color:#586751">info@racialequityinstitute.org</a>.</p>'
       . (isset($_GET['debug'])?'<pre style="text-align:left;color:#999;font-size:11px">'.htmlspecialchars($err).'</pre>':''))
 . '<p style="margin-top:34px"><a href="/" style="color:#586751;text-decoration:none">&larr; Back to the site</a></p></div>';
