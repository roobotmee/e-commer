<?php
// Sessiyani boshlash
session_start();

// Foydalanuvchi tizimga kirganligini tekshirish
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Konfiguratsiya faylini qo'shish
require_once '../config.php';

// Ma'lumotlar bazasiga ulanish
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Ulanishni tekshirish
if ($conn->connect_error) {
    die("Ulanish xatosi: " . $conn->connect_error);
}

// Kategoriyalarni olish
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_query);
$categories = [];

if ($categories_result && $categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Ulanishni yopish
$conn->close();
?>

<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mahsulot qo'shish - SossMM Admin</title>
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

        .back-btn {
            padding: 8px 15px;
            background-color: var(--background-light);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background-color: var(--background-dark);
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
            background-color: var(--background-light);
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-color);
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
            color: var(--text-color);
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 14px;
            color: var(--text-color);
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        textarea.form-control {
            min-height: 120px;
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
            color: var(--text-color);
        }

        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-col {
            flex: 1;
        }

        .image-preview {
            width: 100%;
            height: 200px;
            border: 1px dashed var(--border-color);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            overflow: hidden;
            position: relative;
        }

        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .image-preview-placeholder {
            color: var(--text-light);
            font-size: 14px;
            text-align: center;
        }

        .image-preview-placeholder i {
            font-size: 48px;
            margin-bottom: 10px;
            display: block;
        }

        .btn {
            padding: 10px 20px;
            border-radius: var(--radius);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #5a4690;
        }

        .btn-secondary {
            background-color: var(--background-light);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background-color: var(--background-dark);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 30px;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--radius);
        }

        .alert-success {
            background-color: #e8f5e9;
            color: #388e3c;
            border: 1px solid #c8e6c9;
        }

        .alert-danger {
            background-color: #ffebee;
            color: #d32f2f;
            border: 1px solid #ffcdd2;
        }

        .specs-container {
            margin-bottom: 20px;
        }

        .spec-item {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .spec-item .form-control {
            flex: 1;
        }

        .spec-remove {
            padding: 10px;
            background-color: #ffebee;
            color: var(--danger-color);
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .spec-remove:hover {
            background-color: var(--danger-color);
            color: white;
        }

        .add-spec-btn {
            padding: 8px 15px;
            background-color: var(--background-light);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .add-spec-btn:hover {
            background-color: var(--background-dark);
        }

        .variants-container {
            margin-bottom: 20px;
        }

        .variant-item {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .variant-item .form-control {
            flex: 1;
        }

        .variant-remove {
            padding: 10px;
            background-color: #ffebee;
            color: var(--danger-color);
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .variant-remove:hover {
            background-color: var(--danger-color);
            color: white;
        }

        .add-variant-btn {
            padding: 8px 15px;
            background-color: var(--background-light);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .add-variant-btn:hover {
            background-color: var(--background-dark);
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 250px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                padding: 20px 0;
            }
            
            .logo {
                padding: 0 10px 20px;
                justify-content: center;
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
            
            .nav-link i {
                width: auto;
                margin: 0;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 15px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
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
                        <span>Boshqaruv paneli</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="products.php" class="nav-link active">
                        <i class="fas fa-box"></i>
                        <span>Mahsulotlar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="categories.php" class="nav-link">
                        <i class="fas fa-tags"></i>
                        <span>Kategoriyalar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="orders.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Buyurtmalar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="users.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>Foydalanuvchilar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="settings.php" class="nav-link">
                        <i class="fas fa-cog"></i>
                        <span>Sozlamalar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Chiqish</span>
                    </a>
                </li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="header">
                <h1 class="page-title">Mahsulot qo'shish</h1>
                <a href="products.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Mahsulotlarga qaytish
                </a>
            </div>
            
            <form action="process-product.php" method="post" enctype="multipart/form-data">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Asosiy ma'lumotlar</h2>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="name" class="form-label">Mahsulot nomi *</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="category_id" class="form-label">Kategoriya</label>
                                    <select id="category_id" name="category_id" class="form-control">
                                        <option value="">Kategoriyani tanlang</option>
                                        <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="sku" class="form-label">SKU (Mahsulot kodi)</label>
                                    <input type="text" id="sku" name="sku" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description" class="form-label">Tavsif</label>
                            <textarea id="description" name="description" class="form-control"></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Narxlash</h2>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="price" class="form-label">Narxi (so'm) *</label>
                                    <input type="number" id="price" name="price" class="form-control" min="0" step="1" required>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="old_price" class="form-label">Eski narxi (so'm)</label>
                                    <input type="number" id="old_price" name="old_price" class="form-control" min="0" step="1">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="discount" class="form-label">Chegirma (%)</label>
                                    <input type="number" id="discount" name="discount" class="form-control" min="0" max="100" step="1">
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="stock" class="form-label">Ombordagi miqdori</label>
                                    <input type="number" id="stock" name="stock" class="form-control" min="0" step="1">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Rasmlar</h2>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Asosiy rasm</label>
                            <div class="image-preview" id="mainImagePreview">
                                <div class="image-preview-placeholder">
                                    <i class="fas fa-image"></i>
                                    <span>Rasm tanlanmagan</span>
                                </div>
                            </div>
                            <input type="file" id="image" name="image" class="form-control" accept="image/*" onchange="previewImage(this, 'mainImagePreview')">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Qo'shimcha rasmlar (5 tagacha)</label>
                            <div class="form-row" style="flex-wrap: wrap;">
                                <div class="form-col" style="flex: 0 0 calc(20% - 12px);">
                                    <div class="image-preview" id="additionalImagePreview1">
                                        <div class="image-preview-placeholder">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    </div>
                                    <input type="file" name="additional_images[]" class="form-control" accept="image/*" onchange="previewImage(this, 'additionalImagePreview1')">
                                </div>
                                <div class="form-col" style="flex: 0 0 calc(20% - 12px);">
                                    <div class="image-preview" id="additionalImagePreview2">
                                        <div class="image-preview-placeholder">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    </div>
                                    <input type="file" name="additional_images[]" class="form-control" accept="image/*" onchange="previewImage(this, 'additionalImagePreview2')">
                                </div>
                                <div class="form-col" style="flex: 0 0 calc(20% - 12px);">
                                    <div class="image-preview" id="additionalImagePreview3">
                                        <div class="image-preview-placeholder">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    </div>
                                    <input type="file" name="additional_images[]" class="form-control" accept="image/*" onchange="previewImage(this, 'additionalImagePreview3')">
                                </div>
                                <div class="form-col" style="flex: 0 0 calc(20% - 12px);">
                                    <div class="image-preview" id="additionalImagePreview4">
                                        <div class="image-preview-placeholder">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    </div>
                                    <input type="file" name="additional_images[]" class="form-control" accept="image/*" onchange="previewImage(this, 'additionalImagePreview4')">
                                </div>
                                <div class="form-col" style="flex: 0 0 calc(20% - 12px);">
                                    <div class="image-preview" id="additionalImagePreview5">
                                        <div class="image-preview-placeholder">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    </div>
                                    <input type="file" name="additional_images[]" class="form-control" accept="image/*" onchange="previewImage(this, 'additionalImagePreview5')">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Texnik xususiyatlar</h2>
                    </div>
                    <div class="card-body">
                        <div class="specs-container" id="specsContainer">
                            <div class="spec-item">
                                <input type="text" name="spec_names[]" class="form-control" placeholder="Nomi (masalan, Rang)">
                                <input type="text" name="spec_values[]" class="form-control" placeholder="Qiymati (masalan, Qora)">
                                <button type="button" class="spec-remove" onclick="removeSpec(this)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <button type="button" class="add-spec-btn" onclick="addSpec()">
                            <i class="fas fa-plus"></i> Xususiyat qo'shish
                        </button>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Variantlar (masalan, Hajm, Rang)</h2>
                    </div>
                    <div class="card-body">
                        <div class="variants-container" id="variantsContainer">
                            <div class="variant-item">
                                <input type="text" name="variant_names[]" class="form-control" placeholder="Nomi (masalan, 64GB)">
                                <input type="text" name="variant_values[]" class="form-control" placeholder="Qiymati (masalan, 64)">
                                <button type="button" class="variant-remove" onclick="removeVariant(this)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <button type="button" class="add-variant-btn" onclick="addVariant()">
                            <i class="fas fa-plus"></i> Variant qo'shish
                        </button>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Qo'shimcha ma'lumotlar</h2>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="weight" class="form-label">Og'irligi (g)</label>
                                    <input type="number" id="weight" name="weight" class="form-control" min="0" step="0.01">
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="dimensions" class="form-label">O'lchamlari (U x K x B)</label>
                                    <input type="text" id="dimensions" name="dimensions" class="form-control" placeholder="masalan, 10 x 5 x 2 sm">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Holati</label>
                            <div class="form-check">
                                <input type="checkbox" id="status" name="status" class="form-check-input" value="1" checked>
                                <label for="status" class="form-check-label">Faol</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Parametrlar</label>
                            <div class="form-check">
                                <input type="checkbox" id="featured" name="featured" class="form-check-input" value="1">
                                <label for="featured" class="form-check-label">Tavsiya etilgan mahsulot</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" id="is_new" name="is_new" class="form-check-input" value="1">
                                <label for="is_new" class="form-check-label">Yangi mahsulot</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" id="sale" name="sale" class="form-check-input" value="1">
                                <label for="sale" class="form-check-label">Chegirmada</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-undo"></i> Tozalash
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Saqlash
                    </button>
                </div>
            </form>
        </main>
    </div>
    
    <script>
        // Rasmni oldindan ko'rish
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const placeholder = preview.querySelector('.image-preview-placeholder');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    if (placeholder) {
                        placeholder.style.display = 'none';
                    }
                    
                    let img = preview.querySelector('img');
                    if (!img) {
                        img = document.createElement('img');
                        preview.appendChild(img);
                    }
                    
                    img.src = e.target.result;
                }
                
                reader.readAsDataURL(input.files[0]);
            } else {
                if (placeholder) {
                    placeholder.style.display = 'flex';
                }
                
                const img = preview.querySelector('img');
                if (img) {
                    preview.removeChild(img);
                }
            }
        }
        
        // Xususiyat qo'shish
        function addSpec() {
            const container = document.getElementById('specsContainer');
            const specItem = document.createElement('div');
            specItem.className = 'spec-item';
            specItem.innerHTML = `
                <input type="text" name="spec_names[]" class="form-control" placeholder="Nomi (masalan, Rang)">
                <input type="text" name="spec_values[]" class="form-control" placeholder="Qiymati (masalan, Qora)">
                <button type="button" class="spec-remove" onclick="removeSpec(this)">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(specItem);
        }
        
        // Xususiyatni o'chirish
        function removeSpec(button) {
            const specItem = button.parentNode;
            const container = specItem.parentNode;
            container.removeChild(specItem);
        }
        
        // Variant qo'shish
        function addVariant() {
            const container = document.getElementById('variantsContainer');
            const variantItem = document.createElement('div');
            variantItem.className = 'variant-item';
            variantItem.innerHTML = `
                <input type="text" name="variant_names[]" class="form-control" placeholder="Nomi (masalan, 64GB)">
                <input type="text" name="variant_values[]" class="form-control" placeholder="Qiymati (masalan, 64)">
                <button type="button" class="variant-remove" onclick="removeVariant(this)">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(variantItem);
        }
        
        // Variantni o'chirish
        function removeVariant(button) {
            const variantItem = button.parentNode;
            const container = variantItem.parentNode;
            container.removeChild(variantItem);
        }
        
        // Eski narx o'zgartirilganda chegirmani hisoblash
        document.getElementById('old_price').addEventListener('input', function() {
            const oldPrice = parseFloat(this.value) || 0;
            const price = parseFloat(document.getElementById('price').value) || 0;
            
            if (oldPrice > 0 && price > 0 && oldPrice > price) {
                const discount = Math.round(((oldPrice - price) / oldPrice) * 100);
                document.getElementById('discount').value = discount;
            }
        });
        
        // Chegirma o'zgartirilganda eski narxni hisoblash
        document.getElementById('discount').addEventListener('input', function() {
            const discount = parseFloat(this.value) || 0;
            const price = parseFloat(document.getElementById('price').value) || 0;
            
            if (discount > 0 && price > 0) {
                const oldPrice = Math.round(price / (1 - (discount / 100)));
                document.getElementById('old_price').value = oldPrice;
            }
        });
    </script>
</body>
</html>