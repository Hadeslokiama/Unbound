<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

start_secure_session();

$status = 'error';
$message = 'Invalid verification request.';
$token = sanitize_input($_GET['token'] ?? '');

if ($token !== '') {
    $stmt = mysqli_prepare(
        $conn,
        'SELECT id, email_verified
         FROM users
         WHERE verification_token = ?
         LIMIT 1'
    );

    if (!$stmt) {
        error_log('Verify select prepare failed: ' . mysqli_error($conn));
        $message = 'Something went wrong. Please try again.';
    } else {
        mysqli_stmt_bind_param($stmt, 's', $token);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$user) {
            $message = 'Invalid or already used verification token.';
        } elseif ((int) $user['email_verified'] === 1) {
            $status = 'success';
            $message = 'Your email is already verified. You may log in.';
        } else {
            $user_id = (int) $user['id'];
            $update = mysqli_prepare(
                $conn,
                'UPDATE users
                 SET email_verified = 1, verification_token = NULL
                 WHERE id = ?'
            );

            if (!$update) {
                error_log('Verify update prepare failed: ' . mysqli_error($conn));
                $message = 'Something went wrong. Please try again.';
            } else {
                mysqli_stmt_bind_param($update, 'i', $user_id);

                if (mysqli_stmt_execute($update)) {
                    $status = 'success';
                    $message = 'Email verified successfully. You may now log in.';
                } else {
                    error_log('Verify update execute failed: ' . mysqli_stmt_error($update));
                    $message = 'Verification failed. Please try again.';
                }

                mysqli_stmt_close($update);
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="auth-section">
    <h1>Email Verification</h1>

    <div class="message <?= $status === 'success' ? 'success-message' : 'error-message' ?>">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>

    <?php if ($status === 'success'): ?>
        <p><a href="login.php?verified=1">Go to Login</a></p>
    <?php else: ?>
        <p><a href="register.php">Back to Register</a></p>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
