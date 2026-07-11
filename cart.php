<?php
// cart.php
require_once 'config/db.php';
require_once 'includes/functions.php';
require_login();

global $conn;

// Check user context via custom function hook
$logged_in = is_logged_in();
$user_id = $logged_in ? (int)$_SESSION['user_id'] : 0;

function cart_safe_redirect_target(string $target, string $fallback): string
{
    $target = trim($target);
    $base_url = app_url('');

    if ($target !== '' && str_starts_with($target, '/')) {
        if ($base_url === '' || $target === $base_url || str_starts_with($target, $base_url . '/') || str_starts_with($target, $base_url . '?') || str_starts_with($target, $base_url . '#')) {
            return $target;
        }
    }

    return app_url($fallback);
}

// Execute operational data-mutations if matching signature post arrays exist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $logged_in) {
    $action = sanitize_input($_POST['action']);
    $posted_token = (string) ($_POST['csrf_token'] ?? '');
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

    if (!verify_cart_csrf_token($posted_token)) {
        header('Location: ' . app_url('cart.php'));
        exit;
    }

    if ($product_id > 0 && in_array($action, ['add', 'update', 'remove'], true)) {
        if ($action === 'add') {
            $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
            $quantity = max(1, min($quantity, 99));

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
            $quantity = max(0, min($quantity, 99));
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

    $redirect_to = $action === 'add'
        ? cart_safe_redirect_target((string) ($_POST['redirect_to'] ?? ''), 'index.php#collection')
        : app_url('cart.php');

    header('Location: ' . $redirect_to);
    exit;
}

$cart_csrf_token = get_cart_csrf_token();
require_once 'includes/header.php';

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

<section class="cart-container">
        <h1>Your Shopping Cart</h1>
        <?php if (!$logged_in): ?>
            <p>Please <a href="<?= app_url('auth/login.php') ?>">login</a> to view or track items assigned to your cart profile.</p>
        <?php elseif (!empty($items)): ?>
            <table class="admin-table cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="cart-product-cell">
                                <img src="<?php echo htmlspecialchars(app_url($item['image_path'])); ?>" alt="">
                                <span><?php echo htmlspecialchars($item['name']); ?></span>
                            </td>
                            <td>&#8369;<?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <form action="<?php echo app_url('cart.php'); ?>" method="POST" class="inline-form">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($cart_csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="product_id" value="<?php echo (int)$item['product_id']; ?>">
                                    <input type="number" name="quantity" value="<?php echo (int)$item['quantity']; ?>" min="1" class="quantity-input">
                                    <button type="submit" class="btn btn-secondary btn-sm">Update</button>
                                </form>
                            </td>
                            <td>&#8369;<?php echo number_format($item['subtotal'], 2); ?></td>
                            <td>
                                <form action="<?php echo app_url('cart.php'); ?>" method="POST">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($cart_csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="product_id" value="<?php echo (int)$item['product_id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="cart-summary">
                <h3>Total Amount: &#8369;<?php echo number_format($cart_total, 2); ?></h3>
                <a href="<?= app_url('checkout.php') ?>" class="btn btn-primary">Proceed to Checkout</a>
            </div>
        <?php else: ?>
            <p class="empty-state">Your cart layout is currently empty. <a href="<?= app_url('index.php') ?>">Browse Products</a></p>
        <?php endif; ?>
</section>

<?php
require_once 'includes/footer.php';
?>
