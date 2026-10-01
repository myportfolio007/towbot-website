<?php
// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

// Sanitize
function clean(string $val): string {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

$name    = clean($_POST['name']    ?? '');
$email   = clean($_POST['email']   ?? '');
$message = clean($_POST['message'] ?? '');

// Validate
if (!$name || !$email || !$message) {
    header('Location: index.html?connect=error');
    exit;
}

if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    header('Location: index.html?connect=error');
    exit;
}

// Send email
$to      = 'gary@towboticsystems.com';
$subject = 'Towbot Website Enquiry — ' . $name;
$body    =
    "New enquiry from the Towbot website.\n\n" .
    "Name    : " . $name    . "\n" .
    "Email   : " . $email   . "\n\n" .
    "Message :\n" . $message . "\n";

$headers =
    "From: noreply@towboticsystems.com\r\n" .
    "Reply-To: " . $email . "\r\n" .
    "X-Mailer: PHP/" . phpversion();

$sent = mail($to, $subject, $body, $headers);

if ($sent) {
    header('Location: index.html?connect=success');
} else {
    header('Location: index.html?connect=error');
}
exit;
