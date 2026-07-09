<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

start_secure_session();

if (is_logged_in()) {
    header('Location: ../index.php');
    exit;
}

$errors = [];
$full_name = '';
$email = '';
$address = '';
$contact_number = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize_input($_POST['full_name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $address = sanitize_input($_POST['address'] ?? '');
    $contact_number = sanitize_input($_POST['contact_number'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($full_name === '') {
        $errors[] = 'Full name is required.';
    }

    if ($email === '') {
        $errors[] = 'Email address is required.';
    } elseif (!validate_email($email)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($address === '') {
        $errors[] = 'Address is required.';
    }

    if ($contact_number === '') {
        $errors[] = 'Contact number is required.';
    } elseif (!validate_contact_number($contact_number)) {
        $errors[] = 'Contact number must follow 09XXXXXXXXX or +639XXXXXXXXX format.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (!validate_password_strength($password)) {
        $errors[] = 'Password must be at least 8 characters and include at least one letter and one number.';
    }

    if ($confirm_password === '') {
        $errors[] = 'Please confirm your password.';
    } elseif ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? LIMIT 1');

        if (!$stmt) {
            error_log('Email check prepare failed: ' . mysqli_error($conn));
            $errors[] = 'Something went wrong. Please try again.';
        } else {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) > 0) {
                $errors[] = 'This email address is already registered.';
            }

            mysqli_stmt_close($stmt);
        }
    }

    if (empty($errors)) {
        $password_hash = hash_password($password);
        $verification_token = generate_verification_token();

        $stmt = mysqli_prepare(
            $conn,
            'INSERT INTO users (full_name, email, password_hash, address, contact_number, email_verified, verification_token)
             VALUES (?, ?, ?, ?, ?, 0, ?)'
        );

        if (!$stmt) {
            error_log('User insert prepare failed: ' . mysqli_error($conn));
            $errors[] = 'Something went wrong. Please try again.';
        } else {
            mysqli_stmt_bind_param(
                $stmt,
                'ssssss',
                $full_name,
                $email,
                $password_hash,
                $address,
                $contact_number,
                $verification_token
            );

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header('Location: verify-email.php?token=' . urlencode($verification_token));
                exit;
            }

            error_log('User insert execute failed: ' . mysqli_stmt_error($stmt));
            $errors[] = 'Registration failed. Please try again.';
            mysqli_stmt_close($stmt);
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="auth-section">
    <h1>Create Account</h1>
    <p>Register as a buyer to start shopping at Unbound.</p>

    <?php if (!empty($errors)): ?>
        <div class="message error-message">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="register.php" class="auth-form" novalidate>
        <div class="form-group">
            <label for="full_name">Full Name</label>
            <input
                type="text"
                id="full_name"
                name="full_name"
                value="<?= htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </div>

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
            <label for="address">Complete Address</label>
            <textarea id="address" name="address" rows="3" required><?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="form-group">
            <label for="contact_number">Contact Number</label>
            <input
                type="text"
                id="contact_number"
                name="contact_number"
                value="<?= htmlspecialchars($contact_number, ENT_QUOTES, 'UTF-8') ?>"
                placeholder="09XXXXXXXXX or +639XXXXXXXXX"
                required
            >
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <small>Minimum 8 characters with at least one letter and one number.</small>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>

        <button type="submit">Register</button>
    </form>

    <p class="auth-link">
        Already have an account? <a href="login.php">Login here</a>.
    </p>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
