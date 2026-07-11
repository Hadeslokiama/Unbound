<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

require_admin();
global $conn;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . app_url('admin/inventory.php'));
    exit;
}

$action = (string) ($_POST['action'] ?? '');
$admin_id = (int) ($_SESSION['admin_id'] ?? 0);

// Flash keys are accepted session-contract extensions for one-time status messages, separate from auth keys.
$allowed_categories = ['Tops', 'Bottoms', 'Outerwear', 'Accessories'];

$uploaded_image_path = null;
$upload_error = '';

if (isset($_FILES['image']) && array_key_exists('error', $_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $upload_error = 'Image upload failed.';
    } else {
        $tmp_name = $_FILES['image']['tmp_name'];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = $finfo ? finfo_file($finfo, $tmp_name) : '';
        if ($finfo) {
            finfo_close($finfo);
        }

        $allowed_mime_types = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($allowed_mime_types[$mime_type])) {
            $upload_error = 'Only JPG, PNG, and WEBP images are allowed.';
        } else {
            $upload_dir = __DIR__ . '/../uploads/products/';

            if (!is_dir($upload_dir) && !mkdir($upload_dir, 0775, true) && !is_dir($upload_dir)) {
                $upload_error = 'Could not create the image upload directory.';
            } else {
                $file_name = uniqid('product_', true) . '.' . $allowed_mime_types[$mime_type];
                $destination = $upload_dir . $file_name;

                if (move_uploaded_file($tmp_name, $destination)) {
                    $uploaded_image_path = 'uploads/products/' . $file_name;
                } else {
                    $upload_error = 'Failed to save the uploaded image.';
                }
            }
        }
    }
}

if ($upload_error !== '') {
    $_SESSION['flash_error'] = $upload_error;
    header('Location: ' . app_url('admin/inventory.php'));
    exit;
}

if ($action === 'add_product') {
    $name = sanitize_input((string) ($_POST['name'] ?? ''));
    $description = sanitize_input((string) ($_POST['description'] ?? ''));
    $price_input = trim((string) ($_POST['price'] ?? ''));
    $category = sanitize_input((string) ($_POST['category'] ?? ''));
    $stock_input = trim((string) ($_POST['stock_quantity'] ?? ''));

    $errors = [];

    if ($name === '') {
        $errors[] = 'Product name is required.';
    }

    if ($price_input === '' || !is_numeric($price_input) || (float) $price_input <= 0) {
        $errors[] = 'Price must be numeric and positive.';
    }

    if (!in_array($category, $allowed_categories, true)) {
        $errors[] = 'Please choose a valid category.';
    }

    $stock_quantity = filter_var($stock_input, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 0],
    ]);

    if ($stock_quantity === false) {
        $errors[] = 'Stock quantity must be a non-negative whole number.';
    }

    if (!empty($errors)) {
        $_SESSION['flash_error'] = implode(' ', $errors);
        header('Location: ' . app_url('admin/inventory.php'));
        exit;
    }

    $price = (float) $price_input;

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO products (name, description, price, category, stock_quantity, image_path, is_active)
         VALUES (?, ?, ?, ?, ?, ?, 1)'
    );

    if (!$stmt) {
        error_log('Product insert prepare failed: ' . mysqli_error($conn));
        $_SESSION['flash_error'] = 'Unable to add product.';
        header('Location: ' . app_url('admin/inventory.php'));
        exit;
    }

    mysqli_stmt_bind_param(
        $stmt,
        'ssdsis',
        $name,
        $description,
        $price,
        $category,
        $stock_quantity,
        $uploaded_image_path
    );

    if (mysqli_stmt_execute($stmt)) {
        $new_id = (int) mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        log_admin_action(
            $conn,
            $admin_id,
            'add_product',
            'products',
            $new_id,
            'Added product: ' . $name
        );

        $_SESSION['flash_success'] = 'Product added successfully.';
        header('Location: ' . app_url('admin/inventory.php'));
        exit;
    }

    error_log('Product insert execute failed: ' . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);

    $_SESSION['flash_error'] = 'Failed to add product.';
    header('Location: ' . app_url('admin/inventory.php'));
    exit;
}

if ($action === 'update_product') {
    $product_id = (int) ($_POST['product_id'] ?? 0);
    $name = sanitize_input((string) ($_POST['name'] ?? ''));
    $description = sanitize_input((string) ($_POST['description'] ?? ''));
    $price_input = trim((string) ($_POST['price'] ?? ''));
    $category = sanitize_input((string) ($_POST['category'] ?? ''));
    $stock_input = trim((string) ($_POST['stock_quantity'] ?? ''));

    $errors = [];

    if ($product_id <= 0) {
        $errors[] = 'Invalid product selected.';
    }

    if ($name === '') {
        $errors[] = 'Product name is required.';
    }

    if ($price_input === '' || !is_numeric($price_input) || (float) $price_input <= 0) {
        $errors[] = 'Price must be numeric and positive.';
    }

    if (!in_array($category, $allowed_categories, true)) {
        $errors[] = 'Please choose a valid category.';
    }

    $stock_quantity = filter_var($stock_input, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 0],
    ]);

    if ($stock_quantity === false) {
        $errors[] = 'Stock quantity must be a non-negative whole number.';
    }

    if (!empty($errors)) {
        $_SESSION['flash_error'] = implode(' ', $errors);
        header('Location: ' . app_url('admin/inventory.php'));
        exit;
    }

    $stmt = mysqli_prepare(
        $conn,
        'SELECT id, name, image_path
         FROM products
         WHERE id = ?
         LIMIT 1'
    );

    if (!$stmt) {
        error_log('Product select prepare failed: ' . mysqli_error($conn));
        $_SESSION['flash_error'] = 'Unable to load product for editing.';
        header('Location: ' . app_url('admin/inventory.php'));
        exit;
    }

    mysqli_stmt_bind_param($stmt, 'i', $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $current_product = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if (!$current_product) {
        $_SESSION['flash_error'] = 'Product not found.';
        header('Location: ' . app_url('admin/inventory.php'));
        exit;
    }

    $price = (float) $price_input;
    $final_image_path = $current_product['image_path'] ?: null;
    $old_image_path = $current_product['image_path'] ?: null;

    if ($uploaded_image_path !== null) {
        $final_image_path = $uploaded_image_path;
    }

    $stmt = mysqli_prepare(
        $conn,
        'UPDATE products
         SET name = ?, description = ?, price = ?, category = ?, stock_quantity = ?, image_path = ?, updated_at = CURRENT_TIMESTAMP
         WHERE id = ?'
    );

    if (!$stmt) {
        error_log('Product update prepare failed: ' . mysqli_error($conn));
        $_SESSION['flash_error'] = 'Unable to update product.';
        header('Location: ' . app_url('admin/inventory.php'));
        exit;
    }

    mysqli_stmt_bind_param(
        $stmt,
        'ssdissi',
        $name,
        $description,
        $price,
        $category,
        $stock_quantity,
        $final_image_path,
        $product_id
    );

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);

        if ($uploaded_image_path !== null && !empty($old_image_path)) {
            $old_file = __DIR__ . '/../' . ltrim($old_image_path, '/');
            if (is_file($old_file)) {
                @unlink($old_file);
            }
        }

        log_admin_action(
            $conn,
            $admin_id,
            'edit_product',
            'products',
            $product_id,
            'Updated product: ' . $name
        );

        $_SESSION['flash_success'] = 'Product updated successfully.';
        header('Location: ' . app_url('admin/inventory.php'));
        exit;
    }

    error_log('Product update execute failed: ' . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);

    $_SESSION['flash_error'] = 'Failed to update product.';
    header('Location: ' . app_url('admin/inventory.php'));
    exit;
}

if ($action === 'delete_product' || $action === 'toggle_product_status') {
    $product_id = (int) ($_POST['product_id'] ?? 0);

    if ($product_id <= 0) {
        $_SESSION['flash_error'] = 'Invalid product selected.';
        header('Location: ' . app_url('admin/inventory.php'));
        exit;
    }

    $stmt = mysqli_prepare(
        $conn,
        'SELECT id, name, is_active
         FROM products
         WHERE id = ?
         LIMIT 1'
    );

    if (!$stmt) {
        error_log('Product lookup prepare failed: ' . mysqli_error($conn));
        $_SESSION['flash_error'] = 'Unable to process the selected product.';
        header('Location: ' . app_url('admin/inventory.php'));
        exit;
    }

    mysqli_stmt_bind_param($stmt, 'i', $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $current_product = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if (!$current_product) {
        $_SESSION['flash_error'] = 'Product not found.';
        header('Location: ' . app_url('admin/inventory.php'));
        exit;
    }

    if ($action === 'delete_product') {
        $new_status = 0;
        $stmt = mysqli_prepare(
            $conn,
            'UPDATE products
             SET is_active = 0, updated_at = CURRENT_TIMESTAMP
             WHERE id = ?'
        );

        if (!$stmt) {
            error_log('Product deactivate prepare failed: ' . mysqli_error($conn));
            $_SESSION['flash_error'] = 'Unable to deactivate product.';
            header('Location: ' . app_url('admin/inventory.php'));
            exit;
        }

        mysqli_stmt_bind_param($stmt, 'i', $product_id);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);

            log_admin_action(
                $conn,
                $admin_id,
                'delete_product',
                'products',
                $product_id,
                'Soft-deleted product: ' . $current_product['name']
            );

            $_SESSION['flash_success'] = 'Product deactivated successfully.';
            header('Location: ' . app_url('admin/inventory.php'));
            exit;
        }

        error_log('Product deactivate execute failed: ' . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);

        $_SESSION['flash_error'] = 'Failed to deactivate product.';
        header('Location: ' . app_url('admin/inventory.php'));
        exit;
    }

    $new_status = ((int) $current_product['is_active'] === 1) ? 0 : 1;

    $stmt = mysqli_prepare(
        $conn,
        'UPDATE products
         SET is_active = ?, updated_at = CURRENT_TIMESTAMP
         WHERE id = ?'
    );

    if (!$stmt) {
        error_log('Product status toggle prepare failed: ' . mysqli_error($conn));
        $_SESSION['flash_error'] = 'Unable to toggle product status.';
        header('Location: ' . app_url('admin/inventory.php'));
        exit;
    }

    mysqli_stmt_bind_param($stmt, 'ii', $new_status, $product_id);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);

        $status_label = $new_status === 1 ? 'Activated' : 'Deactivated';

        log_admin_action(
            $conn,
            $admin_id,
            'toggle_product_status',
            'products',
            $product_id,
            $status_label . ' product: ' . $current_product['name']
        );

        $_SESSION['flash_success'] = 'Product status updated successfully.';
        header('Location: ' . app_url('admin/inventory.php'));
        exit;
    }

    error_log('Product status toggle execute failed: ' . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);

    $_SESSION['flash_error'] = 'Failed to update product status.';
    header('Location: ' . app_url('admin/inventory.php'));
    exit;
}

$_SESSION['flash_error'] = 'Invalid action.';
header('Location: ' . app_url('admin/inventory.php'));
exit;
