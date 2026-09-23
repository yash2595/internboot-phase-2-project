<?php
// Path: public/api/slots/preference.php

require_once __DIR__ . '/../../../src/core/bootstrap.php';
require_once __DIR__ . '/../../../src/modules/m5_batch_slots/service.php';
require_once __DIR__ . '/../../../src/modules/m5_batch_slots/controller.php';
require_once __DIR__ . '/../../../src/core/candidate_resolver.php';

// Authenticate Candidate
$candidateId = validate_candidate_session($conn);
if (!$candidateId) {
    send_json_response('error', 'Unauthorized: candidate authentication required', null, 401);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $assessmentId = isset($_GET['assessment_id']) ? (int)$_GET['assessment_id'] : 0;
    if ($assessmentId <= 0) {
        send_json_response('error', 'A valid assessment_id is required', null, 400);
    }

    $enrollment = get_candidate_enrollment($candidateId, $assessmentId, $conn);
    if (!$enrollment) {
        send_json_response('error', 'Candidate is not enrolled in the specified assessment', null, 403);
    }

    // Get upcoming valid weekend dates
    $weekends = calculate_next_weekend_dates();
    $validDates = [];
    foreach ($weekends as $dateStr) {
        if (is_registration_open_for_date($dateStr)) {
            $validDates[] = $dateStr;
        }
    }

    // Standard slots
    $allowedSlots = ['10:00:00-11:00:00', '14:00:00-15:00:00'];
    
    $options = [];
    foreach ($validDates as $d) {
        foreach ($allowedSlots as $s) {
            $options[] = ['date' => $d, 'time_slot' => $s];
        }
    }

    $response = [
        'current_preference' => [
            'preferred_date' => $enrollment['preferred_date'] ?? null,
            'preferred_time_slot' => $enrollment['preferred_time_slot'] ?? null,
        ],
        'options' => $options
    ];

    send_json_response('success', 'Preferences retrieved', $response, 200);

} elseif ($method === 'POST') {
    require_csrf();
    
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (!is_array($input)) {
        send_json_response('error', 'Invalid JSON payload', null, 400);
    }

    $assessmentId = isset($input['assessment_id']) ? (int)$input['assessment_id'] : 0;
    $preferredDate = isset($input['preferred_date']) ? trim($input['preferred_date']) : '';
    $preferredTimeSlot = isset($input['preferred_time_slot']) ? trim($input['preferred_time_slot']) : '';

    if ($assessmentId <= 0 || empty($preferredDate) || empty($preferredTimeSlot)) {
        send_json_response('error', 'assessment_id, preferred_date, and preferred_time_slot are required', null, 400);
    }

    try {
        $result = record_candidate_preference($candidateId, $assessmentId, $preferredDate, $preferredTimeSlot, $conn);
        send_json_response('success', 'Preference recorded successfully', $result, 200);
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        // Do NOT mask business logic exceptions like cutoff passed or invalid slots
        if (stripos($msg, 'cutoff') !== false || stripos($msg, 'invalid') !== false || stripos($msg, 'eligible') !== false || stripos($msg, 'enrolled') !== false || stripos($msg, 'already assigned') !== false) {
            send_json_response('error', $msg, null, 400);
        } else {
            error_log("Preference recording error: " . $msg);
            send_json_response('error', is_dev_env() ? $msg : 'An error occurred processing the request.', null, 500);
        }
    }

} else {
    send_json_response('error', 'Method not allowed', null, 405);
}
