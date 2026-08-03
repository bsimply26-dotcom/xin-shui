<?php
/* ==========================================================================
   XIN SHUI - ENQUIRY FORM HANDLER
   Location: public_html/enquiry.php

   Replaces the Netlify form. Sends each enquiry by authenticated SMTP
   through the Hostinger mailbox, then redirects to thanks.html.

   Raw PHP mail() is deliberately NOT used. On a new domain it lands in
   spam or is dropped silently, and a silent drop means a lost enquiry
   nobody ever knows about.

   Requires:
     1. xinshui-config.php one level ABOVE public_html, never in the repo
     2. PHPMailer in public_html/lib/, three files

   Runs on PHP 7.4 through 8.3.
   ========================================================================== */

declare(strict_types=1);

/* --------------------------------------------------------------------------
   Configuration. Held outside the web root so it is not downloadable and
   is not in version control.
   -------------------------------------------------------------------------- */
$configPath = __DIR__ . '/../xinshui-config.php';
if (!is_readable($configPath)) {
    error_log('XINSHUI: config file missing at ' . $configPath);
    header('Location: /thanks.html?e=1');
    exit;
}
$cfg = require $configPath;

require __DIR__ . '/lib/Exception.php';
require __DIR__ . '/lib/PHPMailer.php';
require __DIR__ . '/lib/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* --------------------------------------------------------------------------
   Only accept POST.
   -------------------------------------------------------------------------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: /');
    exit;
}

/* --------------------------------------------------------------------------
   Honeypot. The company field is hidden from people by CSS. Anything that
   fills it is automated. Redirect as though it worked so the sender learns
   nothing, and send no mail.
   -------------------------------------------------------------------------- */
if (trim((string)($_POST['company'] ?? '')) !== '') {
    header('Location: /thanks.html');
    exit;
}

/* --------------------------------------------------------------------------
   Collect and clean input.
   -------------------------------------------------------------------------- */
function clean(string $v, int $max): string {
    $v = str_replace(["\r", "\n", "\0"], ' ', $v);
    $v = trim($v);
    return mb_substr($v, 0, $max);
}

$name    = clean((string)($_POST['name'] ?? ''), 120);
$email   = clean((string)($_POST['email'] ?? ''), 200);
$phone   = clean((string)($_POST['phone'] ?? ''), 40);
$phoneD  = $phone !== '' ? $phone : 'Not given';
$message = trim((string)($_POST['message'] ?? ''));
$message = mb_substr($message, 0, 5000);

$accepted = isset($_POST['accept']);

$errors = [];
if ($name === '')                                        { $errors[] = 'name'; }
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'email'; }
if (!$accepted)                                          { $errors[] = 'accept'; }

if ($errors) {
    header('Location: /?err=' . implode(',', $errors) . '#contact');
    exit;
}

/* --------------------------------------------------------------------------
   Basic flood control. One submission per IP per 60 seconds.
   -------------------------------------------------------------------------- */
$ip  = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
$key = sys_get_temp_dir() . '/xs_' . md5($ip);
if (is_file($key) && (time() - (int)filemtime($key)) < 60) {
    header('Location: /thanks.html');
    exit;
}
@touch($key);

/* --------------------------------------------------------------------------
   Compose and send.
   -------------------------------------------------------------------------- */
$when = (new DateTime('now', new DateTimeZone('Asia/Dubai')))->format('D j M Y, H:i') . ' Dubai';

$plain = "New enquiry from the Xin Shui website\n"
       . str_repeat('-', 46) . "\n\n"
       . "Name:    {$name}\n"
       . "Email:   {$email}\n"
       . "Phone:   {$phoneD}\n"
       . "Time:    {$when}\n"
       . "Accepted: Terms of Service and Privacy Notice, at submission ({$when})\n\n"
       . "Message:\n"
       . ($message !== '' ? $message : '(no message given)') . "\n\n"
       . str_repeat('-', 46) . "\n"
       . "Reply directly to this email to answer the enquirer.\n";

$html = '<div style="font-family:Inter,Arial,sans-serif;font-size:15px;color:#1C2630;line-height:1.6">'
      . '<p style="font-size:13px;letter-spacing:.14em;text-transform:uppercase;color:#1F6FA8;margin:0 0 18px">New enquiry</p>'
      . '<table cellpadding="0" cellspacing="0" style="font-size:15px">'
      . '<tr><td style="padding:3px 18px 3px 0;color:#5b6875">Name</td><td>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</td></tr>'
      . '<tr><td style="padding:3px 18px 3px 0;color:#5b6875">Email</td><td><a href="mailto:' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</a></td></tr>'
     . '<tr><td style="padding:3px 18px 3px 0;color:#5b6875">Phone</td><td>' . htmlspecialchars($phoneD, ENT_QUOTES, 'UTF-8') . '</td></tr>'
. '<tr><td style="padding:3px 18px 3px 0;color:#5b6875">Time</td><td>' . htmlspecialchars($when, ENT_QUOTES, 'UTF-8') . '</td></tr>'
      . '<tr><td style="padding:3px 18px 3px 0;color:#5b6875">Accepted</td><td>Terms of Service and Privacy Notice</td></tr>'
      . '</table>'
      . '<p style="margin:20px 0 6px;color:#5b6875;font-size:13px">Message</p>'
      . '<div style="background:#EAF5FB;padding:16px 18px;border-radius:4px;white-space:pre-wrap">'
      . ($message !== '' ? nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) : '<em style="color:#5b6875">No message given</em>')
      . '</div>'
      . '<p style="margin-top:22px;font-size:13px;color:#5b6875">Reply directly to this email to answer the enquirer.</p>'
      . '</div>';

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = $cfg['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $cfg['smtp_user'];
    $mail->Password   = $cfg['smtp_pass'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = (int)$cfg['smtp_port'];
    $mail->CharSet    = 'UTF-8';
    $mail->Timeout    = 20;

    /* From must be the authenticated mailbox or the send is rejected.
       The enquirer goes in Reply-To so replying works normally. */
    $mail->setFrom($cfg['smtp_user'], 'Xin Shui website');
    $mail->addReplyTo($email, $name);

    foreach ($cfg['send_to'] as $to) {
        $mail->addAddress($to);
    }

    $mail->Subject = 'Enquiry from ' . $name;
    $mail->isHTML(true);
    $mail->Body    = $html;
    $mail->AltBody = $plain;

    $mail->send();

} catch (Exception $e) {
    /* Never show the visitor an error. Log it, keep a local copy so the
       enquiry is not lost, and redirect normally. Check this log if an
       enquiry is ever reported as sent but not received. */
    error_log('XINSHUI mail failure: ' . $mail->ErrorInfo);
    @file_put_contents(
        __DIR__ . '/../xinshui-enquiries.log',
        "==== {$when} ====\n{$plain}\n",
        FILE_APPEND | LOCK_EX
    );
}

header('Location: /thanks.html');
exit;
