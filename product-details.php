<?php
// product-details.php
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

global $conn;

// Defense in depth: explicit typecast for key index lookups
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;

if ($product_id > 0) {
    // Read exact schema structure targeting verified active configurations
    $query = "SELECT id, name, description, price, image_path, stock_quantity FROM products WHERE id = ? AND is_active = 1 LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result && mysqli_num_rows($result) > 0) {
            $product = mysqli_fetch_assoc($result);
        }
        mysqli_stmt_close($stmt);
    }
}

if (!$product) {
    echo "<section class='empty-state'><p>The requested product is unavailable.</p><a class='btn btn-primary' href='" . app_url('index.php') . "'>Back to store</a></section>";
    require_once 'includes/footer.php';
    exit;
}

$cart_csrf_token = get_cart_csrf_token();
$product_redirect = app_url('product-details.php?id=' . (int) $product['id']);
?>

<section class="product-details-container">
        <div class="product-image-panel">
            <img src="<?php echo htmlspecialchars(app_url($product['image_path'])); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
        </div>
        <div class="product-info-panel">
            <p class="kicker">Product file</p>
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
            <p class="price">&#8369;<?php echo number_format($product['price'], 2); ?></p>
            <p class="description"><?php echo htmlspecialchars($product['description']); ?></p>

            <?php if ((int)$product['stock_quantity'] > 0): ?>
                <form action="<?php echo app_url('cart.php'); ?>" method="POST" class="add-to-cart-form">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($cart_csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($product_redirect, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                    
                    <div class="form-group quantity-field">
                        <label for="quantity">Quantity</label>
                        <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo (int)$product['stock_quantity']; ?>" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Add to Cart</button>
                </form>
                <p class="stock-note">In Stock (<?php echo (int)$product['stock_quantity']; ?> units available)</p>
            <?php else: ?>
                <button class="btn btn-disabled" disabled>Out of Stock</button>
            <?php endif; ?>
        </div>
</section>

<?php
require_once 'includes/footer.php';
?>
