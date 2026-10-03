<?php
// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Redirect back to contact page — path auto-detected
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    header('Location: ' . $base . '/dist/contact/contact.html?error=method');
    exit;
}

// Set timezone
date_default_timezone_set('Europe/Amsterdam');

// Auto-detect base path so it works on any folder structure
// e.g. public_html root → ""
// e.g. public_html/gary veg → "/gary%20veg" (handled by browser encoding)
$base    = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$contact = $base . '/dist/contact/contact.html';

// ── Sanitize ─────────────────────────────────────────
function clean(string $val): string {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

// ── Validate required fields ─────────────────────────
$name    = clean($_POST['name']    ?? '');
$company = clean($_POST['company'] ?? '');
$email   = clean($_POST['email']   ?? '');

if (!$name || !$company || !$email) {
    header('Location: ' . $contact . '?error=required');
    exit;
}

if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . $contact . '?error=email');
    exit;
}

// ── Build record ─────────────────────────────────────
$submission = [
    'id'                  => uniqid('sub_', true),
    'submitted_at'        => date('Y-m-d h:i A'),
    'ip'                  => $_SERVER['REMOTE_ADDR'] ?? '',
    'name'                => $name,
    'company'             => $company,
    'email'               => $email,
    'country'             => clean($_POST['country']             ?? ''),
    'org_type'            => clean($_POST['org-type']            ?? ''),
    'application'         => clean($_POST['application']         ?? ''),
    'vessel_type'         => clean($_POST['vessel-type']         ?? ''),
    'message'             => clean($_POST['message']             ?? ''),
];

// ── Save to JSON ─────────────────────────────────────
$dir  = __DIR__ . '/admin';
$file = $dir . '/submissions.json';

if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$all = [];
if (file_exists($file) && filesize($file) > 2) {
    $decoded = json_decode(file_get_contents($file), true);
    if (is_array($decoded)) {
        $all = $decoded;
    }
}

array_unshift($all, $submission);

$written = file_put_contents(
    $file,
    json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
    LOCK_EX
);

if ($written === false) {
    error_log('Towbot submit.php: failed to write to ' . $file);
    header('Location: ' . $contact . '?error=server');
    exit;
}

// ── Success ───────────────────────────────────────────
header('Location: ' . $contact . '?submitted=1');
exit;
