<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-header.php';

global $conn;

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        a.id,
        ad.full_name,
        a.action,
        a.target_table,
        a.target_id,
        a.details,
        a.created_at
     FROM audit_logs a
     JOIN admins ad
        ON a.admin_id = ad.id
     ORDER BY a.created_at DESC"
);

$logs = false;

if ($stmt) {
    mysqli_stmt_execute($stmt);
    $logs = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
} else {
    error_log('Audit log prepare failed: ' . mysqli_error($conn));
}
?>

<h1>Audit Log</h1>

<section>

    <table class="admin-table">

        <thead>

            <tr>

                <th>ID</th>
                <th>Admin</th>
                <th>Action</th>
                <th>Table</th>
                <th>Target ID</th>
                <th>Details</th>
                <th>Date</th>

            </tr>

        </thead>

        <tbody>

        <?php if ($logs && mysqli_num_rows($logs) > 0): ?>

            <?php while ($log = mysqli_fetch_assoc($logs)): ?>

                <tr>

                    <td><?= (int)$log['id']; ?></td>

                    <td><?= htmlspecialchars($log['full_name']); ?></td>

                    <td><?= htmlspecialchars($log['action']); ?></td>

                    <td><?= htmlspecialchars($log['target_table']); ?></td>

                    <td><?= htmlspecialchars((string)$log['target_id']); ?></td>

                    <td><?= htmlspecialchars($log['details'] ?? ''); ?></td>

                    <td><?= htmlspecialchars($log['created_at']); ?></td>

                </tr>

            <?php endwhile; ?>

        <?php else: ?>

            <tr>

                <td colspan="7">

                    No audit records found.

                </td>

            </tr>

        <?php endif; ?>

        </tbody>

    </table>

</section>

</main>
</div>
</body>
</html>
