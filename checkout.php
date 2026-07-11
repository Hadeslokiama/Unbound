<?php
// checkout.php
require_once 'config/db.php';
require_once 'includes/functions.php';

// Check authorization wall explicitly prior to running markup operations
require_login();

require_once 'includes/header.php';

global $conn;
$user_id = (int)$_SESSION['user_id'];

// Aggregate the item collection array directly via active cart rows
$cart_items = [];
$total_amount = 0.00;

$cart_query = "SELECT c.product_id, c.quantity, p.name, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?";
$stmt = mysqli_prepare($conn, $cart_query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $subtotal = $row['price'] * $row['quantity'];
        $total_amount += $subtotal;
        $row['subtotal'] = $subtotal;
        $cart_items[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Bounce user to index if zero line records are fetched
if (empty($cart_items)) {
    header('Location: ' . app_url('index.php'));
    exit;
}

// Document Requirement: Pre-fill delivery parameter from users.address
$user_address = "";
$user_stmt = mysqli_prepare($conn, "SELECT address FROM users WHERE id = ? LIMIT 1");
if ($user_stmt) {
    mysqli_stmt_bind_param($user_stmt, "i", $user_id);
    mysqli_stmt_execute($user_stmt);
    $user_res = mysqli_stmt_get_result($user_stmt);
    if ($u_row = mysqli_fetch_assoc($user_res)) {
        $user_address = $u_row['address'] ?? '';
    }
    mysqli_stmt_close($user_stmt);
}

$order_success = false;
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = sanitize_input($_POST['shipping_address'] ?? '');
    
    if (empty($shipping_address)) {
        $error_msg = "A delivery shipping address profile parameter is required.";
    } else {
        // Strict Transaction Strategy guarding structural records
        mysqli_begin_transaction($conn);
        try {
            // Status initialized explicitly as 'pending' matching flow blueprints
            $order_stmt = mysqli_prepare($conn, "INSERT INTO orders (user_id, total_amount, shipping_address, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
            mysqli_stmt_bind_param($order_stmt, "ids", $user_id, $total_amount, $shipping_address);
            mysqli_stmt_execute($order_stmt);
            $order_id = mysqli_insert_id($conn);
            mysqli_stmt_close($order_stmt);

            foreach ($cart_items as $item) {
                // Populate child records safely
                $item_stmt = mysqli_prepare($conn, "INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($item_stmt, "iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
                mysqli_stmt_execute($item_stmt);
                mysqli_stmt_close($item_stmt);

                // Update column structure reflecting strict requirements: stock_quantity
                $stock_stmt = mysqli_prepare($conn, "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                mysqli_stmt_bind_param($stock_stmt, "ii", $item['quantity'], $item['product_id']);
                mysqli_stmt_execute($stock_stmt);
                mysqli_stmt_close($stock_stmt);
            }

            // Document Requirement: Clear user's cart rows upon completion
            $clear_stmt = mysqli_prepare($conn, "DELETE FROM cart WHERE user_id = ?");
            mysqli_stmt_bind_param($clear_stmt, "i", $user_id);
            mysqli_stmt_execute($clear_stmt);
            mysqli_stmt_close($clear_stmt);

            mysqli_commit($conn);
            $order_success = true;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_msg = "Processing transaction failed cleanly. Rolling back parameters.";
        }
    }
}
?>

<section class="checkout-container">
        <h1>Checkout</h1>

        <?php if ($order_success): ?>
            <div class="alert alert-success">
                <h3>Order Placed Successfully!</h3>
                <p>Your minimalist apparel procurement request has been finalized.</p>
                <a href="<?= app_url('index.php') ?>" class="btn btn-primary">Return to Shop</a>
            </div>
        <?php else: ?>
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>

            <div class="order-summary-box">
                <h3>Order Summary</h3>
                <ul>
                    <?php foreach ($cart_items as $item): ?>
                        <li>
                            <span><?php echo htmlspecialchars($item['name']); ?> (x<?php echo (int)$item['quantity']; ?>)</span>
                            <span>&#8369;<?php echo number_format($item['subtotal'], 2); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="summary-total">
                    <span>Total Owed:</span>
                    <span>&#8369;<?php echo number_format($total_amount, 2); ?></span>
                </div>
            </div>

            <form action="<?= app_url('checkout.php') ?>" method="POST" class="checkout-form">
                <div class="form-group">
                    <label for="shipping_address">Shipping Address</label>
                    <textarea id="shipping_address" name="shipping_address" rows="4" required><?php echo htmlspecialchars($user_address); ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Complete Purchase</button>
            </form>
        <?php endif; ?>
</section>

<?php
require_once 'includes/footer.php';
?>
