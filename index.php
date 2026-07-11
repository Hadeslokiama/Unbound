<?php
// index.php
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

global $conn;

// Safely handle optional category filter via query string
$category = isset($_GET['category']) ? sanitize_input($_GET['category']) : '';

if ($category !== '') {
    // Parameterized filtering for active items under specific category
    $query = "SELECT id, name, description, price, image_path FROM products WHERE is_active = 1 AND category = ? ORDER BY id DESC";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $category);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    // Fallback default query showcasing all globally active inventory items
    $query = "SELECT id, name, description, price, image_path FROM products WHERE is_active = 1 ORDER BY id DESC";
    $result = mysqli_query($conn, $query);
    $stmt = null;
}
?>

<main class="site-main">
    <section class="products-grid-container">
        <h2>Our Collection<?php echo $category !== '' ? ' - ' . htmlspecialchars($category) : ''; ?></h2>
        <div class="products-grid">
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <?php while ($product = mysqli_fetch_assoc($result)): ?>
                    <div class="product-card">
                        <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
                            
                            <div class="product-actions" style="display: flex; gap: 10px; margin-top: 10px;">
                                <a href="product-details.php?id=<?php echo (int)$product['id']; ?>" class="btn btn-secondary">View Details</a>
                                
                                <form action="cart.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn btn-primary">Add to Cart</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No apparel options are available at this moment.</p>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php
if ($stmt) {
    mysqli_stmt_close($stmt);
}
require_once 'includes/footer.php';
?>
