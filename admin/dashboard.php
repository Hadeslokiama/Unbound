<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin-header.php';

global $conn;

// Total products and low-stock count
$product_stats = mysqli_query($conn, "
    SELECT
        COUNT(*) AS total_products,
        SUM(CASE WHEN stock_quantity <= 5 THEN 1 ELSE 0 END) AS low_stock_count
    FROM products
    WHERE is_active = 1
");
$stats = mysqli_fetch_assoc($product_stats);

// Total registered buyers
$user_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total_users FROM users");
$user_stats = mysqli_fetch_assoc($user_count_result);

// Pending orders
$order_result = mysqli_query($conn, "SELECT COUNT(*) AS pending_orders FROM orders WHERE status = 'pending'");
$order_stats = mysqli_fetch_assoc($order_result);

// Recent audit activity (last 10)
$recent_logs = mysqli_query($conn, "
    SELECT a.action, a.target_table, a.target_id, a.created_at, ad.full_name
    FROM audit_logs a
    JOIN admins ad ON a.admin_id = ad.id
    ORDER BY a.created_at DESC
    LIMIT 10
");
?>

<h1>Dashboard</h1>

<section class="stats-grid">
    <div class="stat-card">
        <span class="stat-label">Total Products</span>
        <span class="stat-value"><?= (int) $stats['total_products'] ?></span>
    </div>
    <div class="stat-card <?= $stats['low_stock_count'] > 0 ? 'stat-warning' : '' ?>">
        <span class="stat-label">Low Stock Items</span>
        <span class="stat-value"><?= (int) $stats['low_stock_count'] ?></span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Registered Buyers</span>
        <span class="stat-value"><?= (int) $user_stats['total_users'] ?></span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Pending Orders</span>
        <span class="stat-value"><?= (int) $order_stats['pending_orders'] ?></span>
    </div>
</section>

<section class="recent-activity">
    <h2>Recent Admin Activity</h2>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Admin</th>
                <th>Action</th>
                <th>Target</th>
                <th>Timestamp</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($recent_logs) === 0): ?>
                <tr><td colspan="4">No activity recorded.</td></tr>
            <?php else: ?>
                <?php while ($log = mysqli_fetch_assoc($recent_logs)): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['full_name']) ?></td>
                        <td><?= htmlspecialchars($log['action']) ?></td>
                        <td><?= htmlspecialchars(($log['target_table'] ?? '') . ' #' . ($log['target_id'] ?? '')) ?></td>
                        <td><?= htmlspecialchars($log['created_at']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

    </main>
</div>
</body>
</html>