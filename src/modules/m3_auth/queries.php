<?php
// Path: src/modules/m3_auth/queries.php

if (!defined('OTP_TTL_MINUTES')) {
    define('OTP_TTL_MINUTES', 10);
}
if (!defined('OTP_MAX_ATTEMPTS')) {
    define('OTP_MAX_ATTEMPTS', 5);
}
if (!defined('OTP_RESEND_COOLDOWN_SECONDS')) {
    define('OTP_RESEND_COOLDOWN_SECONDS', 60);
}
if (!defined('OTP_MAX_RESENDS')) {
    define('OTP_MAX_RESENDS', 5);
}
//
// Matches the real schema.sql from M1:
//   users(id, email, password, role, is_active, created_at, updated_at)
//   candidates(id, user_id FK->users.id, full_name, phone UNIQUE, profile_details, ...)
//
// Registration is a two-table write (users + candidates), so it runs inside
// a transaction. There is no status/progress field on users — eligibility
// (paid/enrolled) lives in `enrollments.eligibility_status`, which belongs
// to M4/M5, not this module.

/**
 * Fetch a user (joined with their candidate profile) by email.
 * Returns null if not found.
 */
function find_user_by_email(mysqli $conn, string $email): ?array {
    $stmt = $conn->prepare(
        'SELECT u.id, u.email, u.password, u.role, u.is_active,
                c.id AS candidate_id, c.full_name, c.phone
         FROM users u
         LEFT JOIN candidates c ON c.user_id = u.id
         WHERE u.email = ?
         LIMIT 1'
    );
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user ?: null;
}

/**
 * Whether a phone number is already taken (candidates.phone is UNIQUE).
 */
function candidate_phone_exists(mysqli $conn, string $phone): bool {
    $stmt = $conn->prepare('SELECT id FROM candidates WHERE phone = ? LIMIT 1');
    $stmt->bind_param('s', $phone);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

/**
 * Creates the users row + candidates row together in one transaction.
 * Returns ['success' => bool, 'user_id' => int, 'message' => string, 'code' => int]
 *
 * Pre-checking email/phone in service.php avoids most duplicate-key hits,
 * but this still wraps the two inserts in a transaction and rolls back on
 * any failure (e.g. a race between the pre-check and the insert) so a
 * candidate row is never left orphaned without its user row, or vice versa.
 */
function insert_user_and_candidate(mysqli $conn, string $email, string $passwordHash, string $fullName, string $phone, string $role = 'candidate'): array {
    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $email, $passwordHash, $role);
        $stmt->execute();

        if ($conn->errno) {
            throw new mysqli_sql_exception($conn->error, $conn->errno);
        }

        $userId = (int) $stmt->insert_id;
        $stmt->close();

        $stmt2 = $conn->prepare('INSERT INTO candidates (user_id, full_name, phone) VALUES (?, ?, ?)');
        $stmt2->bind_param('iss', $userId, $fullName, $phone);
        $stmt2->execute();

        if ($conn->errno) {
            throw new mysqli_sql_exception($conn->error, $conn->errno);
        }
        $stmt2->close();

        $conn->commit();
        return ['success' => true, 'user_id' => $userId];
    } catch (Throwable $e) {
        $conn->rollback();

        // 1062 = MySQL duplicate-entry error code
        $isDuplicate = (method_exists($e, 'getCode') && (int) $e->getCode() === 1062) || $conn->errno === 1062;
        if ($isDuplicate) {
            $onPhone = str_contains($e->getMessage(), 'phone') || str_contains($conn->error, 'phone');
            return [
                'success' => false,
                'code' => 409,
                'message' => $onPhone
                    ? 'This phone number is already registered.'
                    : 'An account with this email already exists.',
            ];
        }

        return ['success' => false, 'code' => 500, 'message' => 'Could not complete registration. Please try again.'];
    }
}

/**
 * Creates an admin/staff user account without creating a candidate profile.
 * Returns ['success' => bool, 'user_id' => int, 'message' => string, 'code' => int]
 */
function insert_admin_user(mysqli $conn, string $email, string $passwordHash, string $fullName, string $phone, string $role): array {
    if (!in_array($role, ['admin', 'staff'], true)) {
        return ['success' => false, 'code' => 422, 'message' => 'Invalid staff role specified.'];
    }

    try {
        $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $email, $passwordHash, $role);
        $stmt->execute();

        if ($conn->errno) {
            throw new mysqli_sql_exception($conn->error, $conn->errno);
        }

        $userId = (int) $stmt->insert_id;
        $stmt->close();

        return ['success' => true, 'user_id' => $userId];
    } catch (Throwable $e) {
        $isDuplicate = (method_exists($e, 'getCode') && (int) $e->getCode() === 1062) || $conn->errno === 1062;
        if ($isDuplicate) {
            return [
                'success' => false,
                'code' => 409,
                'message' => 'An account with this email already exists.',
            ];
        }

        return ['success' => false, 'code' => 500, 'message' => 'Could not create staff account. Please try again.'];
    }
}


function save_pending_registration(mysqli $conn, string $email, string $otp, string $fullName, string $phone, string $passwordHash, string $role, int $resendCount = 0): bool {
    // Clear any old pending entries for this email first
    $del = $conn->prepare('DELETE FROM email_verifications WHERE email = ?');
    $del->bind_param('s', $email);
    $del->execute();
    $del->close();

    $stmt = $conn->prepare(
        'INSERT INTO email_verifications (email, otp_code, full_name, phone, password_hash, role, resend_count, expires_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))'
    );
    $stmt->bind_param('ssssssi', $email, $otp, $fullName, $phone, $passwordHash, $role, $resendCount);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function find_latest_pending_verification(mysqli $conn, string $email): ?array {
    $stmt = $conn->prepare(
        'SELECT id, email, otp_code, full_name, phone, password_hash, role, is_used, attempts, resend_count, expires_at, created_at,
                TIMESTAMPDIFF(SECOND, created_at, NOW()) AS age_seconds,
                (expires_at > NOW()) AS is_unexpired
         FROM email_verifications
         WHERE email = ? AND is_used = 0
         ORDER BY id DESC LIMIT 1'
    );
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function consume_verification_attempt(mysqli $conn, int $id): int {
    $maxAttempts = OTP_MAX_ATTEMPTS;
    $stmt = $conn->prepare(
        'UPDATE email_verifications
         SET attempts = attempts + 1
         WHERE id = ? AND is_used = 0 AND expires_at > NOW() AND attempts < ?'
    );
    $stmt->bind_param('ii', $id, $maxAttempts);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $affected;
}

function find_pending_registration(mysqli $conn, string $email, string $otp): ?array {
    $stmt = $conn->prepare(
        'SELECT * FROM email_verifications
         WHERE email = ? AND otp_code = ? AND is_used = 0 AND expires_at > NOW()
         ORDER BY id DESC LIMIT 1'
    );
    $stmt->bind_param('ss', $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function mark_pending_registration_used(mysqli $conn, int $id): void {
    $stmt = $conn->prepare('UPDATE email_verifications SET is_used = 1 WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
}

function check_login_rate_limit(mysqli $conn, string $email, string $ipAddress): bool {
    $stmt = $conn->prepare(
        'SELECT COUNT(*) AS failed_count 
         FROM login_attempts 
         WHERE email = ? AND ip_address = ? 
           AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)'
    );
    $stmt->bind_param('ss', $email, $ipAddress);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return ((int)$row['failed_count'] >= 5);
}

function record_failed_login(mysqli $conn, string $email, string $ipAddress): void {
    $stmt = $conn->prepare('INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)');
    $stmt->bind_param('ss', $email, $ipAddress);
    $stmt->execute();
    $stmt->close();
}

function clear_failed_logins(mysqli $conn, string $email, string $ipAddress): void {
    $stmt = $conn->prepare('DELETE FROM login_attempts WHERE email = ? AND ip_address = ?');
    $stmt->bind_param('ss', $email, $ipAddress);
    $stmt->execute();
    $stmt->close();
}

?>
