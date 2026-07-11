<?php
// index.php
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

global $conn;

// Safely handle optional category filter via query string
$category_order = ['Tops', 'Bottoms', 'Outerwear', 'Accessories'];
$category = isset($_GET['category']) ? sanitize_input($_GET['category']) : '';
$selected_category = in_array($category, $category_order, true) ? $category : '';

if ($selected_category !== '') {
    // Parameterized filtering for active items under specific category
    $query = "SELECT id, name, description, price, image_path, category FROM products WHERE is_active = 1 AND category = ? ORDER BY id DESC";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $selected_category);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    // Fallback default query showcasing all globally active inventory items
    $query = "SELECT id, name, description, price, image_path, category
              FROM products
              WHERE is_active = 1
              ORDER BY FIELD(category, 'Tops', 'Bottoms', 'Outerwear', 'Accessories'), id DESC";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
}

$products_by_category = array_fill_keys($category_order, []);

if ($result) {
    while ($product = mysqli_fetch_assoc($result)) {
        $product_category = $product['category'] ?? '';

        if (isset($products_by_category[$product_category])) {
            $products_by_category[$product_category][] = $product;
        }
    }
}

$visible_categories = $selected_category !== '' ? [$selected_category] : $category_order;
$has_products = false;
$cart_csrf_token = get_cart_csrf_token();
$collection_redirect = app_url('index.php' . ($selected_category !== '' ? '?category=' . rawurlencode($selected_category) : '') . '#collection');
?>

<section class="storefront">
    <div class="storefront-hero">
        <div class="hero-title-block">
            <p class="kicker">New line / open form</p>
            <h1>
                Wear without permission.<a href="#collection" class="btn btn-primary hero-cta">Shop Now</a>
            </h1>
        </div>
    </div>

    <div class="kinetic-marquee" aria-label="Unbound store cues">
        <div class="kinetic-track">
            <span>Sharp essentials</span>
            <span>Direct movement</span>
            <span>No permission</span>
            <span>Clean silhouettes</span>
            <span aria-hidden="true">Sharp essentials</span>
            <span aria-hidden="true">Direct movement</span>
            <span aria-hidden="true">No permission</span>
            <span aria-hidden="true">Clean silhouettes</span>
        </div>
    </div>

    <section class="signal-grid" aria-label="Unbound principles">
        <article class="signal-card">
            <span class="signal-number" aria-hidden="true">01</span>
            <h2>Essential Fits</h2>
            <p>Browse sharp tops, bottoms, outerwear, and accessories made for everyday wear.</p>
        </article>
        <article class="signal-card">
            <span class="signal-number" aria-hidden="true">02</span>
            <h2>Cart Ready</h2>
            <p>Add pieces fast, review quantities, and keep your selected apparel organized.</p>
        </article>
        <article class="signal-card">
            <span class="signal-number" aria-hidden="true">03</span>
            <h2>Checkout Clean</h2>
            <p>Complete orders with clear totals, delivery details, and secure account access.</p>
        </article>
    </section>

    <div class="kinetic-marquee kinetic-marquee-muted" aria-label="Catalog categories">
        <div class="kinetic-track kinetic-track-reverse">
            <span>Tops</span>
            <span>Bottoms</span>
            <span>Outerwear</span>
            <span>Accessories</span>
            <span aria-hidden="true">Tops</span>
            <span aria-hidden="true">Bottoms</span>
            <span aria-hidden="true">Outerwear</span>
            <span aria-hidden="true">Accessories</span>
        </div>
    </div>

    <section id="collection" class="products-grid-container">
        <div class="section-heading">
            <h2>Our Collection<?php echo $selected_category !== '' ? ' - ' . htmlspecialchars($selected_category) : ''; ?></h2>
            <span>Built for daily motion</span>
        </div>

        <div class="collection-groups">
            <?php foreach ($visible_categories as $collection_category): ?>
                <?php if (!empty($products_by_category[$collection_category])): ?>
                    <?php $has_products = true; ?>
                    <section class="category-section" aria-labelledby="category-<?php echo strtolower($collection_category); ?>">
                        <div class="category-heading">
                            <h3 id="category-<?php echo strtolower($collection_category); ?>"><?php echo htmlspecialchars($collection_category); ?></h3>
                            <span><?php echo count($products_by_category[$collection_category]); ?> pieces</span>
                        </div>

                        <div class="products-grid">
                            <?php foreach ($products_by_category[$collection_category] as $product): ?>
                                <div class="product-card">
                                    <img src="<?php echo htmlspecialchars(app_url($product['image_path'])); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                                    <div class="product-info">
                                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <p class="product-price">&#8369;<?php echo number_format($product['price'], 2); ?></p>

                                        <div class="product-actions">
                                            <a href="<?php echo app_url('product-details.php?id=' . (int)$product['id']); ?>" class="btn btn-secondary">View Details</a>

                                            <form action="<?php echo app_url('cart.php'); ?>" method="POST">
                                                <input type="hidden" name="action" value="add">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($cart_csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($collection_redirect, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                                                <input type="hidden" name="quantity" value="1">
                                                <button type="submit" class="btn btn-primary">Add to Cart</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if (!$has_products): ?>
                <p class="empty-state">No apparel options are available at this moment.</p>
            <?php endif; ?>
        </div>
    </section>
</section>

<?php
if ($stmt) {
    mysqli_stmt_close($stmt);
}
require_once 'includes/footer.php';
?>
