<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

start_secure_session();

if (is_logged_in()) {
    header('Location: ../index.php');
    exit;
}

$errors = [];
$info_message = '';
$email = '';
$verification_link = '';

if (isset($_GET['verified']) && $_GET['verified'] === '1') {
    $info_message = 'Email verified successfully. You may now log in.';
}

if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    $info_message = 'You have been logged out.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '') {
        $errors[] = 'Email address is required.';
    } elseif (!validate_email($email)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare(
            $conn,
            'SELECT id, full_name, email, password_hash, email_verified, verification_token
             FROM users
             WHERE email = ?
             LIMIT 1'
        );

        if (!$stmt) {
            error_log('Login prepare failed: ' . mysqli_error($conn));
            $errors[] = 'Something went wrong. Please try again.';
        } else {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if (!$user || !verify_password($password, $user['password_hash'])) {
                $errors[] = 'Invalid email or password.';
            } elseif ((int) $user['email_verified'] !== 1) {
                $errors[] = 'Please verify your email before logging in.';

                if (!empty($user['verification_token'])) {
                    $verification_link = 'verify-email.php?token=' . urlencode($user['verification_token']);
                }
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];

                header('Location: ../index.php');
                exit;
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="auth-section">
    <h1>Login</h1>
    <p>Access your Unbound buyer account.</p>

    <?php if ($info_message !== ''): ?>
        <div class="message success-message">
            <?= htmlspecialchars($info_message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="message error-message">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>

            <?php if ($verification_link !== ''): ?>
                <p>
                    Development verification link:
                    <a href="<?= htmlspecialchars($verification_link, ENT_QUOTES, 'UTF-8') ?>">Verify account</a>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="login.php" class="auth-form" novalidate>
        <div class="form-group">
            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit">Login</button>
    </form>

    <p class="auth-link">
        Do not have an account yet? <a href="register.php">Register here</a>.
    </p>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
