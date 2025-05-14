<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: products.php');
    exit;
}

$product_id = intval($_GET['id']);

// Include configuration file
require_once '../config.php';

// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get product data
$product_query = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($product_query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: products.php');
    exit;
}

$product = $result->fetch_assoc();
$stmt->close();

// Get categories for dropdown
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_query);
$categories = [];

if ($categories_result && $categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Initialize variables with product data
$name = $product['name'];
$description = $product['description'];
$price = $product['price'];
$old_price = $product['old_price'];
$category_id = $product['category_id'];
$image = $product['image'];
$is_active = $product['is_active'];
$is_featured = $product['featured'];
$is_new = $product['is_new'];
$discount = $product['discount'];
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    $old_price = trim($_POST['old_price']);
    $category_id = intval($_POST['category_id']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_new = isset($_POST['is_new']) ? 1 : 0;
    $discount = !empty($_POST['discount']) ? intval($_POST['discount']) : 0;
    
    // Validate input
    if (empty($name)) {
        $errors[] = 'Product name is required';
    }
    
    if (empty($price) || !is_numeric($price) || $price <= 0) {
        $errors[] = 'Valid price is required';
    }
    
    if (!empty($old_price) && (!is_numeric($old_price) || $old_price <= 0)) {
        $errors[] = 'Old price must be a valid number';
    }
    
    if ($category_id <= 0) {
        $errors[] = 'Please select a category';
    }
    
    // Handle image upload if a new image is provided
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $errors[] = 'Only JPG, PNG, GIF, and WebP images are allowed';
        } elseif ($_FILES['image']['size'] > $max_size) {
            $errors[] = 'Image size must be less than 5MB';
        } else {
            // Create uploads directory if it doesn't exist
            $upload_dir = '../uploads/products/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('product_') . '.' . $file_extension;
            $target_file = $upload_dir . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                // Delete old image if it exists and is not a default image
                if (!empty($product['image']) && file_exists('..' . $product['image']) && strpos($product['image'], 'default') === false) {
                    unlink('..' . $product['image']);
                }
                
                $image = '/uploads/products/' . $filename;
            } else {
                $errors[] = 'Failed to upload image';
            }
        }
    }
    
    // If no errors, update product
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, old_price = ?, category_id = ?, image = ?, is_active = ?, featured = ?, is_new = ?, discount = ? WHERE id = ?");
        $stmt->bind_param("ssddisiiiii", $name, $description, $price, $old_price, $category_id, $image, $is_active, $is_featured, $is_new, $discount, $product_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = 'Product updated successfully';
            header('Location: products.php');
            exit;
        } else {
            $errors[] = 'Failed to update product: ' . $stmt->error;
        }
        
        $stmt->close();
    }
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - SossMM Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6750a4;
            --primary-light: #e8def8;
            --background-color: #f9f9f9;
            --card-bg: #ffffff;
            --text-color: #1c1b1f;
            --text-light: #49454f;
            --border-color: #e0e0e0;
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
            --info-color: #2196f3;
            --radius: 8px;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background-color: var(--card-bg);
            box-shadow: var(--shadow);
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .logo {
            padding: 0 20px 20px;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo h1 {
            font-size: 20px;
            color: var(--primary-color);
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s ease;
            gap: 10px;
        }

        .nav-link:hover, .nav-link.active {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        .nav-link i {
            width: 20px;
            text-align: center;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background-color: var(--card-bg);
            padding: 15px 20px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .page-title {
            font-size: 24px;
            font-weight: 600;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 15px;
            border-radius: var(--radius);
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #5a4690;
        }

        .btn-secondary {
            background-color: var(--text-light);
            color: white;
        }

        .btn-secondary:hover {
            background-color: #3a3a3a;
        }

        .card {
            background-color: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
        }

        .card-body {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .form-check-input {
            width: 16px;
            height: 16px;
        }

        .form-check-label {
            font-size: 14px;
        }

        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-col {
            flex: 1;
        }

        .alert {
            padding: 15px;
            border-radius: var(--radius);
            margin-bottom: 20px;
        }

        .alert-danger {
            background-color: #ffebee;
            color: var(--danger-color);
            border: 1px solid #ffcdd2;
        }

        .current-image {
            width: 100%;
            max-width: 300px;
            height: 200px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
            overflow: hidden;
            margin-bottom: 15px;
        }

        .current-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .image-preview {
            width: 100%;
            max-width: 300px;
            height: 200px;
            border: 1px dashed var(--border-color);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
            overflow: hidden;
        }

        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .logo h1 {
                display: none;
            }
            
            .nav-link span {
                display: none;
            }
            
            .nav-link {
                justify-content: center;
                padding: 12px;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .header-actions {
                width: 100%;
                justify-content: flex-end;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="logo">
                <i class="fas fa-store"></i>
                <h1>SossMM Admin</h1>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="index.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="products.php" class="nav-link active">
                        <i class="fas fa-box"></i>
                        <span>Products</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="categories.php" class="nav-link">
                        <i class="fas fa-tags"></i>
                        <span>Categories</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="orders.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Orders</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="users.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>Users</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="settings.php" class="nav-link">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="header">
                <h1 class="page-title">Edit Product</h1>
                <div class="header-actions">
                    <a href="products.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Products
                    </a>
                </div>
            </div>
            
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Product Information</h2>
                </div>
                <div class="card-body">
                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="name" class="form-label">Product Name *</label>
                            <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($name); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="category_id" class="form-label">Category *</label>
                            <select id="category_id" name="category_id" class="form-control" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo $category['name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label for="price" class="form-label">Price (so'm) *</label>
                                <input type="number" id="price" name="price" class="form-control" value="<?php echo htmlspecialchars($price); ?>" min="0" required>
                            </div>
                            <div class="form-col">
                                <label for="old_price" class="form-label">Old Price (so'm)</label>
                                <input type="number" id="old_price" name="old_price" class="form-control" value="<?php echo htmlspecialchars($old_price); ?>" min="0">
                            </div>
                            <div class="form-col">
                                <label for="discount" class="form-label">Discount (%)</label>
                                <input type="number" id="discount" name="discount" class="form-control" value="<?php echo htmlspecialchars($discount); ?>" min="0" max="100">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" class="form-control"><?php echo htmlspecialchars($description); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Current Image</label>
                            <div class="current-image">
                                <?php if (!empty($image)): ?>
                                <img src="<?php echo $image; ?>" alt="<?php echo $name; ?>">
                                <?php else: ?>
                                <span>No image</span>
                                <?php endif; ?>
                            </div>
                            
                            <label for="image" class="form-label">Change Image (leave empty to keep current image)</label>
                            <input type="file" id="image" name="image" class="form-control" accept="image/*">
                            <div class="image-preview" id="imagePreview">
                                <span>Image Preview</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" id="is_active" name="is_active" class="form-check-input" <?php echo $is_active ? 'checked' : ''; ?>>
                                <label for="is_active" class="form-check-label">Active</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" id="is_featured" name="is_featured" class="form-check-input" <?php echo $is_featured ? 'checked' : ''; ?>>
                                <label for="is_featured" class="form-check-label">Featured</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" id="is_new" name="is_new" class="form-check-input" <?php echo $is_new ? 'checked' : ''; ?>>
                                <label for="is_new" class="form-check-label">New</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Image preview
        const imageInput = document.getElementById('image');
        const imagePreview = document.getElementById('imagePreview');
        
        imageInput.addEventListener('change', function() {
            imagePreview.innerHTML = '';
            
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    imagePreview.appendChild(img);
                }
                
                reader.readAsDataURL(this.files[0]);
            } else {
                imagePreview.innerHTML = '<span>Image Preview</span>';
            }
        });
        
        // Auto calculate discount
        const priceInput = document.getElementById('price');
        const oldPriceInput = document.getElementById('old_price');
        const discountInput = document.getElementById('discount');
        
        function calculateDiscount() {
            const price = parseFloat(priceInput.value) || 0;
            const oldPrice = parseFloat(oldPriceInput.value) || 0;
            
            if (price > 0 && oldPrice > price) {
                const discount = Math.round(((oldPrice - price) / oldPrice) * 100);
                discountInput.value = discount;
            }
        }
        
        function calculateOldPrice() {
            const price = parseFloat(priceInput.value) || 0;
            const discount = parseFloat(discountInput.value) || 0;
            
            if (price > 0 && discount > 0 && discount <= 100) {
                const oldPrice = Math.round(price / (1 - discount / 100));
                oldPriceInput.value = oldPrice;
            }
        }
        
        priceInput.addEventListener('input', calculateDiscount);
        oldPriceInput.addEventListener('input', calculateDiscount);
        discountInput.addEventListener('input', calculateOldPrice);
    </script>
</body>
</html>

## 4. admin/delete-product.php - Mahsulotni o'chirish

```php
<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: products.php');
    exit;
}

$product_id = intval($_GET['id']);

// Include configuration file
require_once '../config.php';

// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get product image path before deleting
$image_query = "SELECT image FROM products WHERE id = ?";
$stmt = $conn->prepare($image_query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
    $image_path = $product['image'];
    
    // Delete product from database
    $delete_query = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $product_id);
    
    if ($stmt->execute()) {
        // Delete product image if it exists and is not a default image
        if (!empty($image_path) && file_exists('..' . $image_path) && strpos($image_path, 'default') === false) {
            unlink('..' . $image_path);
        }
        
        $_SESSION['success_message'] = 'Product deleted successfully';
    } else {
        $_SESSION['error_message'] = 'Failed to delete product: ' . $stmt->error;
    }
} else {
    $_SESSION['error_message'] = 'Product not found';
}

// Close connection
$stmt->close();
$conn->close();

// Redirect back to products page
header('Location: products.php');
exit;