<?php

// Load central bootstrap
if (file_exists(dirname(__DIR__, 3) . '/src/core/bootstrap.php')) {
    require_once dirname(__DIR__, 3) . '/src/core/bootstrap.php';
} elseif (file_exists(__DIR__ . '/../../../src/core/bootstrap.php')) {
    require_once __DIR__ . '/../../../src/core/bootstrap.php';
} else {
    require_once __DIR__ . '/../src/core/bootstrap.php';
}

try {

    if (
        (!isset($_SESSION['candidate_id']) || !is_numeric($_SESSION['candidate_id'])) &&
        isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) &&
        isset($conn)
    ) {
        $userStmt = $conn->prepare("SELECT id FROM candidates WHERE user_id = ? LIMIT 1");
        if ($userStmt) {
            $uId = (int)$_SESSION['user_id'];
            $userStmt->bind_param("i", $uId);
            $userStmt->execute();
            $userRes = $userStmt->get_result()->fetch_assoc();
            $userStmt->close();
            if ($userRes) {
                $_SESSION['candidate_id'] = (int)$userRes['id'];
            }
        }
    }

    if (
        !isset($_SESSION['candidate_id']) ||
        !is_numeric($_SESSION['candidate_id'])
    ) {
        send_json_response('error', 'Candidate authentication required', null, 401);
    }

    $candidateId = (int) $_SESSION['candidate_id'];

    $attemptId = isset($_GET['attempt_id'])
        ? (int) $_GET['attempt_id']
        : 0;

    if ($attemptId <= 0) {
        send_json_response('error', 'Invalid attempt ID', null, 400);
    }

    /*
     * Retrieve the attempt with slot, schedule, and assessment info.
     */
    $sql = "
        SELECT
            a.id AS attempt_id,
            a.id,
            a.candidate_id,
            a.assessment_id,
            a.exam_slot_id,
            a.status,
            a.start_time,
            a.end_time,
            a.submitted_at,

            es.start_time AS slot_start_time,
            es.end_time AS slot_end_time,

            sch.exam_date,
            sch.status AS schedule_status,

            ass.duration_minutes,
            ass.total_questions

        FROM attempts a

        LEFT JOIN exam_slots es
            ON es.id = a.exam_slot_id

        LEFT JOIN exam_schedules sch
            ON sch.id = es.exam_schedule_id

        LEFT JOIN assessments ass
            ON ass.id = a.assessment_id

        WHERE a.id = ?
          AND a.candidate_id = ?

        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $attemptId, $candidateId);
    $stmt->execute();

    $result = $stmt->get_result();
    $attempt = $result->fetch_assoc();

    if (!$attempt) {
        send_json_response('error', 'Attempt not found or access denied', null, 404);
    }

    /*
     * Calculate remaining time using SERVER time (only if started).
     */
    $remainingSeconds = 0;

    if (!empty($attempt['start_time']) && !empty($attempt['end_time'])) {

        $now = new DateTime();
        $endTime = new DateTime($attempt['end_time']);

        $remainingSeconds = max(
            0,
            $endTime->getTimestamp() - $now->getTimestamp()
        );
    }

    /*
     * Automatically expire an in-progress attempt
     * when server time reaches end_time (only if started).
     */
    if (
        $attempt['status'] === 'in_progress' &&
        !empty($attempt['start_time']) &&
        !empty($attempt['end_time']) &&
        $remainingSeconds <= 0
    ) {

        $expireSql = "
            UPDATE attempts
            SET
                status = 'expired',
                submitted_at = NOW()
            WHERE id = ?
              AND status = 'in_progress'
        ";

        $expireStmt = $conn->prepare($expireSql);
        $expireStmt->bind_param("i", $attemptId);
        $expireStmt->execute();

        require_once __DIR__ . '/../../../src/modules/m7_evaluation_admin/service.php';
        try {
            evaluate_attempt($conn, $attemptId, false);
        } catch (Throwable $evalError) {
            error_log('Auto-evaluation failed for attempt ' . $attemptId . ': ' . $evalError->getMessage());
        }

        $attempt['status'] = 'expired';
        $attempt['submitted_at'] = date('Y-m-d H:i:s');
        $remainingSeconds = 0;
    }

    /*
     * Scheduled Date and Time-Window Gate (Read-only check for unstarted attempts).
     */
    $isStarted = !empty($attempt['start_time']);
    $canStart = false;
    $gateMessage = null;

    if (!$isStarted && $attempt['status'] === 'in_progress') {
        $canStart = true;
        $now = new DateTime();
        $today = $now->format('Y-m-d');

        $examDate = !empty($attempt['exam_date']) ? $attempt['exam_date'] : null;
        $slotStartTime = !empty($attempt['slot_start_time']) ? $attempt['slot_start_time'] : null;
        $slotEndTime = !empty($attempt['slot_end_time']) ? $attempt['slot_end_time'] : null;

        if ($examDate !== null) {
            if ($examDate > $today) {
                $canStart = false;
                $gateMessage = "Your exam is scheduled for {$examDate}. This assessment is not yet active.";
            } elseif ($examDate < $today) {
                $canStart = false;
                $gateMessage = "Your scheduled exam window has passed.";
            } elseif ($slotStartTime !== null && $slotEndTime !== null) {
                $slotStart = new DateTime($examDate . ' ' . $slotStartTime);
                $slotEnd = new DateTime($examDate . ' ' . $slotEndTime);

                if ($now < $slotStart) {
                    $canStart = false;
                    $gateMessage = "Your exam slot opens at {$slotStartTime}.";
                } elseif ($now > $slotEnd) {
                    $canStart = false;
                    $gateMessage = "Your exam slot has closed.";
                }
            }
        }
    }

    /*
     * Count answers already saved.
     */
    $answerSql = "
        SELECT COUNT(*) AS answered_count
        FROM answers
        WHERE attempt_id = ?
          AND selected_option_id IS NOT NULL
    ";

    $answerStmt = $conn->prepare($answerSql);
    $answerStmt->bind_param("i", $attemptId);
    $answerStmt->execute();

    $answerResult = $answerStmt->get_result();
    $answerData = $answerResult->fetch_assoc();

    $answeredCount = (int) $answerData['answered_count'];

    /*
     * Get total number of questions for the assessment.
     */
    $totalQuestions = isset($attempt['total_questions'])
        ? (int) $attempt['total_questions']
        : 0;

    send_json_response('success', 'Exam status retrieved', [
        'success' => true,
        'attempt_id' => (int) $attempt['id'],
        'candidate_id' => (int) $attempt['candidate_id'],
        'assessment_id' => (int) $attempt['assessment_id'],
        'exam_slot_id' => (int) $attempt['exam_slot_id'],
        'status' => $attempt['status'],
        'start_time' => $attempt['start_time'],
        'end_time' => $attempt['end_time'],
        'submitted_at' => $attempt['submitted_at'],
        'remaining_seconds' => $remainingSeconds,
        'answered_count' => $answeredCount,
        'total_questions' => $totalQuestions,
        'is_started' => $isStarted,
        'can_start' => $canStart,
        'gate_message' => $gateMessage,
        'exam_date' => $attempt['exam_date'] ?? null,
        'slot_start_time' => $attempt['slot_start_time'] ?? null,
        'slot_end_time' => $attempt['slot_end_time'] ?? null,
        'duration_minutes' => isset($attempt['duration_minutes']) ? (int) $attempt['duration_minutes'] : 0
    ], 200);

} catch (Throwable $e) {
    error_log('exam_status error: ' . $e->getMessage());
    send_json_response('error', 'Internal server error', null, 500);
}