<?php
// cart.php
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

global $conn;

// Check user context via custom function hook
$logged_in = is_logged_in();
$user_id = $logged_in ? (int)$_SESSION['user_id'] : 0;

// Execute operational data-mutations if matching signature post arrays exist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $logged_in) {
    $action = sanitize_input($_POST['action']);
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    
    if ($product_id > 0) {
        if ($action === 'add') {
            $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
            
            // Check if user already holds a reference row to this item
            $check_stmt = mysqli_prepare($conn, "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $product_id);
            mysqli_stmt_execute($check_stmt);
            $check_res = mysqli_stmt_get_result($check_stmt);
            
            if ($row = mysqli_fetch_assoc($check_res)) {
                // Increment existing allocation mapping cleanly
                $new_qty = $row['quantity'] + $quantity;
                $up_stmt = mysqli_prepare($conn, "UPDATE cart SET quantity = ? WHERE id = ?");
                mysqli_stmt_bind_param($up_stmt, "ii", $new_qty, $row['id']);
                mysqli_stmt_execute($up_stmt);
                mysqli_stmt_close($up_stmt);
            } else {
                // Map direct standard database insertion
                $ins_stmt = mysqli_prepare($conn, "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($ins_stmt, "iii", $user_id, $product_id, $quantity);
                mysqli_stmt_execute($ins_stmt);
                mysqli_stmt_close($ins_stmt);
            }
            mysqli_stmt_close($check_stmt);
            
        } elseif ($action === 'update') {
            $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
            if ($quantity <= 0) {
                $del_stmt = mysqli_prepare($conn, "DELETE FROM cart WHERE user_id = ? AND product_id = ?");
                mysqli_stmt_bind_param($del_stmt, "ii", $user_id, $product_id);
                mysqli_stmt_execute($del_stmt);
                mysqli_stmt_close($del_stmt);
            } else {
                $up_stmt = mysqli_prepare($conn, "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                mysqli_stmt_bind_param($up_stmt, "iii", $quantity, $user_id, $product_id);
                mysqli_stmt_execute($up_stmt);
                mysqli_stmt_close($up_stmt);
            }
        } elseif ($action === 'remove') {
            $del_stmt = mysqli_prepare($conn, "DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            mysqli_stmt_bind_param($del_stmt, "ii", $user_id, $product_id);
            mysqli_stmt_execute($del_stmt);
            mysqli_stmt_close($del_stmt);
        }
    }
    header("Location: cart.php");
    exit;
}

// Fetch all persisted cart lines linked to this specific user ID
$items = [];
$cart_total = 0.00;

if ($logged_in) {
    $cart_query = "SELECT c.product_id, c.quantity, p.name, p.price, p.image_path
                   FROM cart c
                   JOIN products p ON c.product_id = p.id
                   WHERE c.user_id = ?";
    $stmt = mysqli_prepare($conn, $cart_query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $subtotal = $row['price'] * $row['quantity'];
            $cart_total += $subtotal;
            $row['subtotal'] = $subtotal;
            $items[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<main class="site-main">
    <div class="cart-container" style="padding: 20px;">
        <h1>Your Shopping Cart</h1>
        <?php if (!$logged_in): ?>
            <p>Please <a href="login.php">login</a> to view or track items assigned to your cart profile.</p>
        <?php elseif (!empty($items)): ?>
            <table class="admin-table" style="width:100%; border-collapse: collapse; margin-top: 20px;">
                <thead>
                    <tr style="border-bottom: 2px solid #ccc; text-align: left;">
                        <th style="padding: 10px;">Product</th>
                        <th style="padding: 10px;">Price</th>
                        <th style="padding: 10px;">Quantity</th>
                        <th style="padding: 10px;">Subtotal</th>
                        <th style="padding: 10px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 10px; display: flex; align-items: center; gap: 15px;">
                                <img src="<?php echo htmlspecialchars($item['image_path']); ?>" width="50" style="height:auto;" alt="">
                                <span><?php echo htmlspecialchars($item['name']); ?></span>
                            </td>
                            <td style="padding: 10px;">$<?php echo number_format($item['price'], 2); ?></td>
                            <td style="padding: 10px;">
                                <form action="cart.php" method="POST" style="display:inline-flex; gap: 5px;">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?php echo (int)$item['product_id']; ?>">
                                    <input type="number" name="quantity" value="<?php echo (int)$item['quantity']; ?>" min="1" style="width:60px; padding: 4px;">
                                    <button type="submit" class="btn btn-secondary" style="padding: 4px 8px;">Update</button>
                                </form>
                            </td>
                            <td style="padding: 10px;">$<?php echo number_format($item['subtotal'], 2); ?></td>
                            <td style="padding: 10px;">
                                <form action="cart.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?php echo (int)$item['product_id']; ?>">
                                    <button type="submit" class="btn btn-danger" style="padding: 4px 8px; background: red; color: white; border: none; cursor: pointer;">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="cart-summary" style="margin-top: 30px; text-align: right;">
                <h3>Total Amount: $<?php echo number_format($cart_total, 2); ?></h3>
                <a href="checkout.php" class="btn btn-primary" style="display: inline-block; padding: 12px 24px; margin-top: 10px; text-decoration: none;">Proceed to Checkout</a>
            </div>
        <?php else: ?>
            <p>Your cart layout is currently empty. <a href="index.php">Browse Products</a></p>
        <?php endif; ?>
    </div>
</main>

<?php
require_once 'includes/footer.php';
?>
