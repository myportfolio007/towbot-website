<?php
// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.html?error=method');
    exit;
}

// ── Sanitize ─────────────────────────────────────────
function clean(string $val): string {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

// ── Validate required fields ─────────────────────────
$name    = clean($_POST['name']    ?? '');
$company = clean($_POST['company'] ?? '');
$email   = clean($_POST['email']   ?? '');
$message = clean($_POST['message'] ?? '');

if (!$name || !$company || !$email || !$message) {
    header('Location: contact.html?error=required');
    exit;
}

if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    header('Location: contact.html?error=email');
    exit;
}

// ── Build record ─────────────────────────────────────
$submission = [
    'id'                  => uniqid('sub_', true),
    'submitted_at'        => date('Y-m-d H:i:s'),
    'ip'                  => $_SERVER['REMOTE_ADDR'] ?? '',
    'name'                => $name,
    'company'             => $company,
    'email'               => $email,
    'country'             => clean($_POST['country']      ?? ''),
    'org_type'            => clean($_POST['org-type']     ?? ''),
    'application'         => clean($_POST['application']  ?? ''),
    'vessel_type'         => clean($_POST['vessel-type']  ?? ''),
    'dimensions'          => clean($_POST['dimensions']   ?? ''),
    'manoeuvre'           => clean($_POST['manoeuvre']    ?? ''),
    'current_arrangement' => clean($_POST['current-arrangement'] ?? ''),
    'project_date'        => clean($_POST['project-date'] ?? ''),
    'message'             => $message,
];

// ── Find admin folder ─────────────────────────────────
// submit.php lives at: public_html/dist/contact/submit.php
// admin folder is at:  public_html/admin/
$adminDir = dirname(__DIR__, 2) . '/admin';
if (!is_dir($adminDir)) {
    $adminDir = dirname(__DIR__) . '/admin';
}
if (!is_dir($adminDir)) {
    mkdir($adminDir, 0755, true);
}

$file = $adminDir . '/submissions.json';

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
    header('Location: contact.html?error=server');
    exit;
}

// ── Success ───────────────────────────────────────────
header('Location: contact.html?submitted=1');
exit;
