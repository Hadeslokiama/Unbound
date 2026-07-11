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
    echo "<main class='site-main'><p>The requested product is unavailable.</p><a href='" . app_url('index.php') . "'>Back to store</a></main>";
    require_once 'includes/footer.php';
    exit;
}
?>

<main class="site-main">
    <div class="product-details-container" style="display: flex; gap: 40px; margin-top: 20px;">
        <div class="product-image-panel" style="flex: 1;">
            <img src="<?php echo htmlspecialchars(app_url($product['image_path'])); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="max-width:100%; height:auto;">
        </div>
        <div class="product-info-panel" style="flex: 1;">
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
            <p class="price" style="font-size: 24px; font-weight: bold; margin: 15px 0;">$<?php echo number_format($product['price'], 2); ?></p>
            <p class="description" style="line-height: 1.6; margin-bottom: 25px;"><?php echo htmlspecialchars($product['description']); ?></p>

            <?php if ((int)$product['stock_quantity'] > 0): ?>
                <form action="<?php echo app_url('cart.php'); ?>" method="POST" class="add-to-cart-form">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                    
                    <div style="margin-bottom: 15px;">
                        <label for="quantity" style="font-weight: bold; display: block; margin-bottom: 5px;">Quantity:</label>
                        <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo (int)$product['stock_quantity']; ?>" style="padding: 8px; width: 80px;" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Add to Cart</button>
                </form>
                <p style="color: green; font-size: 14px; margin-top: 10px;">In Stock (<?php echo (int)$product['stock_quantity']; ?> units available)</p>
            <?php else: ?>
                <button class="btn btn-disabled" style="background: #ccc; cursor: not-allowed; padding: 10px 20px;" disabled>Out of Stock</button>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
require_once 'includes/footer.php';
?>
