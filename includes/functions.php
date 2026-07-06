<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

// ============================================================
// INPUT HANDLING
// ============================================================

function sanitize_input(string $data): string
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function validate_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validate_contact_number(string $number): bool
{
    // Philippine mobile format: 09XXXXXXXXX or +639XXXXXXXXX
    return (bool) preg_match('/^(09\d{9}|\+639\d{9})$/', $number);
}

function validate_password_strength(string $password): bool
{
    // Minimum 8 chars, at least one letter and one number.
    return (bool) preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', $password);
}

// ============================================================
// PASSWORD HANDLING
// ============================================================

function hash_password(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function verify_password(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

// ============================================================
// SESSION / AUTH GUARDS
// ============================================================

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

function is_logged_in(): bool
{
    start_secure_session();
    return isset($_SESSION['user_id']);
}

function is_admin_logged_in(): bool
{
    start_secure_session();
    return isset($_SESSION['admin_id']);
}

function require_login(string $redirect = '/auth/login.php'): void
{
    if (!is_logged_in()) {
        header("Location: $redirect");
        exit;
    }
}

function require_admin(string $redirect = '/auth/login.php'): void
{
    if (!is_admin_logged_in()) {
        header("Location: $redirect");
        exit;
    }
}

// ============================================================
// AUDIT LOGGING
// ============================================================

function log_admin_action(
    mysqli $conn,
    int $admin_id,
    string $action,
    ?string $target_table = null,
    ?int $target_id = null,
    ?string $details = null
): bool {
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO audit_logs (admin_id, action, target_table, target_id, details)
         VALUES (?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        error_log("log_admin_action prepare failed: " . mysqli_error($conn));
        return false;
    }

    mysqli_stmt_bind_param($stmt, "issis", $admin_id, $action, $target_table, $target_id, $details);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $result;
}

// ============================================================
// TOKEN GENERATION (email verification)
// ============================================================

function generate_verification_token(): string
{
    return bin2hex(random_bytes(32));
}