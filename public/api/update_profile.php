<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/core/bootstrap.php';
require_once __DIR__ . '/../../src/core/candidate_resolver.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response('error', 'Method not allowed.', null, 405);
}

try {
    $candidateId = resolve_candidate_id($_GET);

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        send_json_response('error', 'Invalid payload', null, 400);
    }

    $name = trim($data['name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $linkedin = trim($data['linkedin'] ?? '');
    $github = trim($data['github'] ?? '');

    if (!$name) {
        send_json_response('error', 'Name is required', null, 400);
    }

    // Fetch existing profile_details
    global $conn;
    $stmt = $conn->prepare('SELECT profile_details FROM candidates WHERE id = ?');
    $stmt->bind_param('i', $candidateId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $profileDetails = [];
    if ($row && $row['profile_details']) {
        $profileDetails = json_decode($row['profile_details'], true) ?: [];
    }

    $profileDetails['linkedin'] = $linkedin;
    $profileDetails['github'] = $github;
    $newProfileDetails = json_encode($profileDetails);

    // Update
    $updateStmt = $conn->prepare('UPDATE candidates SET full_name = ?, phone = ?, profile_details = ? WHERE id = ?');
    $updateStmt->bind_param('sssi', $name, $phone, $newProfileDetails, $candidateId);
    $updateStmt->execute();
    $updateStmt->close();

    send_json_response('success', 'Profile updated successfully', []);

} catch (Throwable $e) {
    error_log('Update profile error: ' . $e->getMessage());
    send_json_response('error', 'Unable to update profile. Please try again.', null, 500);
}
