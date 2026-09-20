<?php
// Path: src/core/candidate_resolver.php

function demo_mode(): bool {
    return ($_ENV['M4_DEMO_MODE'] ?? '0') === '1';
}

function resolve_candidate_id(array $input = []): int {
    global $conn;

    $hasSession = isset($_SESSION['candidate_id']) || isset($_SESSION['user_id']);

    if (isset($_SESSION['candidate_id']) && ctype_digit((string)$_SESSION['candidate_id'])) {
        return (int)$_SESSION['candidate_id'];
    }

    if (isset($_SESSION['user_id']) && ctype_digit((string)$_SESSION['user_id'])) {
        $stmt = $conn->prepare('SELECT id FROM candidates WHERE user_id = ? LIMIT 1');
        $userId = (int) $_SESSION['user_id'];
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $_SESSION['candidate_id'] = (int)$row['id'];
            return (int)$row['id'];
        }
    }

    $appEnv = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'production';
    
    // FAIL-SAFE GUARD: Require a dedicated secret to prevent accidental impersonation if APP_ENV is misconfigured
    $expectedSecret = $_ENV['M4_DEMO_SECRET'] ?? getenv('M4_DEMO_SECRET') ?: '';
    $providedSecret = $input['demo_secret'] ?? $_GET['demo_secret'] ?? '';

    if (
        !$hasSession && 
        demo_mode() && 
        $appEnv !== 'production' && 
        $expectedSecret !== '' && 
        hash_equals($expectedSecret, $providedSecret)
    ) {
        $cid = $input['candidate_id'] ?? $_GET['candidate_id'] ?? 1;
        if (ctype_digit((string)$cid)) return (int)$cid;
    }

    send_json_response('error', 'Candidate authentication/session is required.', null, 401);
    exit;
}
