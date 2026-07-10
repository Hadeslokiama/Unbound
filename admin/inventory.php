<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-header.php';

global $conn;

$allowed_categories = ['Tops', 'Bottoms', 'Outerwear', 'Accessories'];

$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$edit_product = null;

if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];

    if ($edit_id > 0) {
        $stmt = mysqli_prepare(
            $conn,
            'SELECT id, name, description, price, category, stock_quantity, image_path, is_active
             FROM products
             WHERE id = ?
             LIMIT 1'
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $edit_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $edit_product = $result ? mysqli_fetch_assoc($result) : null;
            mysqli_stmt_close($stmt);

            if (!$edit_product) {
                $flash_error = 'Product not found.';
            }
        } else {
            error_log('Product edit select prepare failed: ' . mysqli_error($conn));
            $flash_error = 'Unable to load product for editing.';
        }
    }
}

$stmt = mysqli_prepare(
    $conn,
    'SELECT id, name, description, price, category, stock_quantity, image_path, is_active, created_at
     FROM products
     ORDER BY created_at DESC, id DESC'
);

$products_result = false;

if ($stmt) {
    mysqli_stmt_execute($stmt);
    $products_result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
} else {
    error_log('Products list prepare failed: ' . mysqli_error($conn));
    $flash_error = 'Unable to load products.';
}
?>

<h1>Inventory Management</h1>

<?php if ($flash_success !== ''): ?>
    <p class="success"><?= htmlspecialchars($flash_success, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<?php if ($flash_error !== ''): ?>
    <p class="error"><?= htmlspecialchars($flash_error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<section>
    <h2><?= $edit_product ? 'Edit Product' : 'Add New Product' ?></h2>

    <form
        action="process-inventory.php"
        method="post"
        enctype="multipart/form-data"
        class="admin-form"
    >
        <input
            type="hidden"
            name="action"
            value="<?= $edit_product ? 'update_product' : 'add_product' ?>"
        >

        <?php if ($edit_product): ?>
            <input
                type="hidden"
                name="product_id"
                value="<?= (int) $edit_product['id'] ?>"
            >
        <?php endif; ?>

        <p>
            <label for="name">Product Name</label><br>
            <input
                type="text"
                id="name"
                name="name"
                value="<?= htmlspecialchars((string) ($edit_product['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </p>

        <p>
            <label for="description">Description</label><br>
            <textarea
                id="description"
                name="description"
                rows="4"
            ><?= htmlspecialchars((string) ($edit_product['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </p>

        <p>
            <label for="price">Price</label><br>
            <input
                type="number"
                id="price"
                name="price"
                step="0.01"
                min="0.01"
                value="<?= htmlspecialchars((string) ($edit_product['price'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </p>

        <p>
            <label for="category">Category</label><br>
            <select id="category" name="category" required>
                <option value="">-- Select Category --</option>
                <?php foreach ($allowed_categories as $category): ?>
                    <option
                        value="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>"
                        <?= isset($edit_product['category']) && $edit_product['category'] === $category ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="stock_quantity">Stock Quantity</label><br>
            <input
                type="number"
                id="stock_quantity"
                name="stock_quantity"
                min="0"
                step="1"
                value="<?= htmlspecialchars((string) ($edit_product['stock_quantity'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </p>

        <p>
            <label for="image">Product Image</label><br>
            <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp">
        </p>

        <?php if (!empty($edit_product['image_path'])): ?>
            <p>
                <strong>Current Image:</strong><br>
                <img
                    src="../<?= htmlspecialchars($edit_product['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                    alt="Current product image"
                    style="max-width:140px; height:auto; border-radius:8px; margin-top:8px;"
                >
            </p>
        <?php endif; ?>

        <p>
            <button type="submit">
                <?= $edit_product ? 'Update Product' : 'Add Product' ?>
            </button>

            <?php if ($edit_product): ?>
                <a href="inventory.php" style="margin-left:12px;">Cancel Edit</a>
            <?php endif; ?>
        </p>
    </form>
</section>

<section>
    <h2>Product List</h2>

    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>
            <?php if ($products_result && mysqli_num_rows($products_result) > 0): ?>
                <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                    <tr>
                        <td><?= (int) $product['id'] ?></td>
                        <td>
                            <?php if (!empty($product['image_path'])): ?>
                                <img
                                    src="../<?= htmlspecialchars($product['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                                    alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>"
                                    style="width:60px; height:60px; object-fit:cover; border-radius:8px;"
                                >
                            <?php else: ?>
                                No image
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($product['category'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>₱<?= number_format((float) $product['price'], 2) ?></td>
                        <td><?= (int) $product['stock_quantity'] ?></td>
                        <td><?= (int) $product['is_active'] === 1 ? 'Active' : 'Inactive' ?></td>
                        <td>
                            <a href="inventory.php?edit=<?= (int) $product['id'] ?>">Edit</a>

                            <form
                                method="post"
                                action="process-inventory.php"
                                class="inline-form"
                                style="display:inline-block; margin-left:8px;"
                            >
                                <input type="hidden" name="action" value="toggle_product_status">
                                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                <button type="submit">
                                    <?= (int) $product['is_active'] === 1 ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>

                            <form
                                method="post"
                                action="process-inventory.php"
                                class="inline-form"
                                style="display:inline-block; margin-left:8px;"
                                onsubmit="return confirm('Deactivate this product?');"
                            >
                                <input type="hidden" name="action" value="delete_product">
                                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                <button type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8">No products found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>

    </main>
</div>
</body>
</html>
