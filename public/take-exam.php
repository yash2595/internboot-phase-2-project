<?php
/**
 * Note: Source of truth for the exam engine HTML/JS runner is src/modules/m6_exam_engine/exam.php.
 * public/take-exam.php is the public entrypoint copy.
 */

if (file_exists(__DIR__ . '/../src/core/bootstrap.php')) {
    require_once __DIR__ . '/../src/core/bootstrap.php';
} elseif (file_exists(dirname(__DIR__) . '/src/core/bootstrap.php')) {
    require_once dirname(__DIR__) . '/src/core/bootstrap.php';
} else {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Session guard
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

if (!isset($_SESSION['candidate_id']) || !is_numeric($_SESSION['candidate_id'])) {
    header('Location: login.php');
    exit;
}

// Delegate to the single source of truth for the exam engine UI/logic
require_once __DIR__ . '/../src/modules/m6_exam_engine/exam.php';