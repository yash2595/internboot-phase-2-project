<?php
require_once __DIR__ . '/../../../src/core/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Must be an admin or staff
require_admin_access($conn);

$userId = (int)($_SESSION['user_id'] ?? 0);
$role   = $_SESSION['role'] ?? 'admin';

// Fetch name + email directly from users + candidates tables
// Admin accounts may not have a candidates row, so we use COALESCE
$stmt = $conn->prepare(
    'SELECT u.email, COALESCE(c.full_name, \'\') AS full_name
     FROM users u
     LEFT JOIN candidates c ON c.user_id = u.id
     WHERE u.id = ?
     LIMIT 1'
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$email    = $row['email']     ?? '';
$fullName = $row['full_name'] ?? '';

// If no candidates row (admin), fall back to session full_name
if (empty(trim($fullName))) {
    $fullName = $_SESSION['full_name'] ?? '';
}

// Last resort: humanize the email prefix
if (empty(trim($fullName))) {
    $prefix   = explode('@', $email)[0] ?? 'admin';
    $fullName = ucwords(str_replace(['.', '_', '-'], ' ', $prefix));
}

// Build initials (max 2 chars)
$nameParts = array_filter(explode(' ', trim($fullName)));
$initials  = '';
foreach ($nameParts as $part) {
    $initials .= strtoupper(mb_substr($part, 0, 1));
    if (strlen($initials) >= 2) break;
}
if ($initials === '') $initials = 'A';

send_json_response('success', 'Admin info loaded.', [
    'full_name' => $fullName,
    'email'     => $email,
    'role'      => $role,
    'initials'  => $initials,
]);
