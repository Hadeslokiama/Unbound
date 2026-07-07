<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin-header.php';

global $conn;

$current_admin_id = $_SESSION['admin_id'];
$error = '';
$success = '';

// Only super_admin may create or modify admin accounts.
$self_result = mysqli_query($conn, "SELECT role FROM admins WHERE id = " . (int) $current_admin_id);
$self = mysqli_fetch_assoc($self_result);
$is_super_admin = ($self['role'] === 'super_admin');

if (!$is_super_admin) {
    echo '<p class="access-denied">Access restricted to super admins.</p>';
    echo '</main></div></body></html>';
    exit;
}

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $target_id = (int) ($_POST['admin_id'] ?? 0);
    $allowed_roles = ['super_admin', 'inventory_manager', 'staff'];

    if ($_POST['action'] === 'update_role' && in_array($_POST['new_role'] ?? '', $allowed_roles, true)) {
        $new_role = $_POST['new_role'];
        $stmt = mysqli_prepare($conn, "UPDATE admins SET role = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $new_role, $target_id);
        if (mysqli_stmt_execute($stmt)) {
            log_admin_action($conn, $current_admin_id, 'update_role', 'admins', $target_id, "New role: $new_role");
            $success = 'Role updated.';
        } else {
            $error = 'Failed to update role.';
        }
        mysqli_stmt_close($stmt);
    }

    if ($_POST['action'] === 'toggle_active') {
        $stmt = mysqli_prepare($conn, "UPDATE admins SET is_active = NOT is_active WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $target_id);
        if (mysqli_stmt_execute($stmt)) {
            log_admin_action($conn, $current_admin_id, 'toggle_active', 'admins', $target_id);
            $success = 'Status updated.';
        } else {
            $error = 'Failed to update status.';
        }
        mysqli_stmt_close($stmt);
    }
}

$admins_result = mysqli_query($conn, "SELECT id, full_name, email, role, is_active FROM admins ORDER BY id");
?>

<h1>Manage Admin Users</h1>

<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>

<table class="admin-table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($admin = mysqli_fetch_assoc($admins_result)): ?>
            <tr>
                <td><?= htmlspecialchars($admin['full_name']) ?></td>
                <td><?= htmlspecialchars($admin['email']) ?></td>
                <td>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="action" value="update_role">
                        <input type="hidden" name="admin_id" value="<?= (int) $admin['id'] ?>">
                        <select name="new_role">
                            <?php foreach (['super_admin', 'inventory_manager', 'staff'] as $r): ?>
                                <option value="<?= $r ?>" <?= $admin['role'] === $r ? 'selected' : '' ?>><?= $r ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit">Update</button>
                    </form>
                </td>
                <td><?= $admin['is_active'] ? 'Active' : 'Disabled' ?></td>
                <td>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="action" value="toggle_active">
                        <input type="hidden" name="admin_id" value="<?= (int) $admin['id'] ?>">
                        <button type="submit"><?= $admin['is_active'] ? 'Disable' : 'Enable' ?></button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

    </main>
</div>
</body>
</html>