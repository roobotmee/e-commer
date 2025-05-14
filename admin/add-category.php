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

// Xatolik va muvaffaqiyat xabarlarini saqlash uchun o'zgaruvchilar
$errors = [];
$success = false;

// Mavjud ikonkalar ro'yxati
$available_icons = [
    'fas fa-mobile-alt' => 'Telefon',
    'fas fa-laptop' => 'Noutbuk',
    'fas fa-tv' => 'Televizor',
    'fas fa-headphones' => 'Quloqchinlar',
    'fas fa-camera' => 'Kamera',
    'fas fa-gamepad' => 'O\'yin qurilmalari',
    'fas fa-tshirt' => 'Kiyim',
    'fas fa-shoe-prints' => 'Poyabzal',
    'fas fa-gem' => 'Aksessuarlar',
    'fas fa-couch' => 'Mebel',
    'fas fa-blender' => 'Maishiy texnika',
    'fas fa-utensils' => 'Oshxona jihozlari',
    'fas fa-book' => 'Kitoblar',
    'fas fa-baby' => 'Bolalar uchun',
    'fas fa-futbol' => 'Sport',
    'fas fa-car' => 'Avtomobil',
    'fas fa-tools' => 'Asboblar',
    'fas fa-paint-brush' => 'San\'at',
    'fas fa-gift' => 'Sovg\'alar',
    'fas fa-heartbeat' => 'Sog\'liq',
    'fas fa-pills' => 'Dori-darmon',
    'fas fa-home' => 'Uy-joy',
    'fas fa-paw' => 'Hayvonlar',
    'fas fa-seedling' => 'Bog\'dorchilik'
];

// Forma yuborilganligini tekshirish
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Forma ma'lumotlarini olish va tozalash
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $icon = trim($_POST['icon']);
    $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
    $status = isset($_POST['status']) ? 'active' : 'inactive';
    $sort_order = !empty($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
    
    // Validatsiya
    if (empty($name)) {
        $errors[] = "Kategoriya nomi kiritilishi shart";
    } elseif (strlen($name) > 100) {
        $errors[] = "Kategoriya nomi juda uzun (maksimum 100 belgi)";
    }
    
    if (strlen($description) > 500) {
        $errors[] = "Tavsif juda uzun (maksimum 500 belgi)";
    }
    
    if (empty($icon)) {
        $errors[] = "Ikonka tanlash shart";
    } elseif (!array_key_exists($icon, $available_icons)) {
        $errors[] = "Noto'g'ri ikonka tanlandi";
    }
    
    // Agar parent_id berilgan bo'lsa, u mavjudligini tekshirish
    if (!empty($parent_id)) {
        $check_parent = "SELECT id FROM categories WHERE id = ?";
        $stmt = $conn->prepare($check_parent);
        $stmt->bind_param("i", $parent_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $errors[] = "Tanlangan ota-kategoriya mavjud emas";
        }
        
        $stmt->close();
    }
    
    // Xatolik yo'q bo'lsa, ma'lumotlarni saqlash
    if (empty($errors)) {
        // Kategoriya nomining takrorlanmasligini tekshirish
        $check_query = "SELECT id FROM categories WHERE name = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Bu nomdagi kategoriya allaqachon mavjud";
        } else {
            // Yangi kategoriyani qo'shish
            if ($parent_id === null) {
                // parent_id null bo'lsa
                $insert_query = "INSERT INTO categories (name, description, icon, status, sort_order, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("ssssi", $name, $description, $icon, $status, $sort_order);
            } else {
                // parent_id mavjud bo'lsa
                $insert_query = "INSERT INTO categories (name, description, icon, parent_id, status, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("sssisi", $name, $description, $icon, $parent_id, $status, $sort_order);
            }
            
            if ($stmt->execute()) {
                $category_id = $stmt->insert_id;
                $success = true;
                $success_message = "Kategoriya muvaffaqiyatli qo'shildi! ID: " . $category_id;
                
                // Formani tozalash
                $name = $description = '';
                $icon = '';
                $parent_id = null;
                $status = 'active';
                $sort_order = 0;
            } else {
                $errors[] = "Kategoriyani saqlashda xatolik yuz berdi: " . $stmt->error;
            }
            
            $stmt->close();
        }
    }
}

// Mavjud kategoriyalarni olish (ota-kategoriya tanlash uchun)
$categories_query = "SELECT id, name, parent_id FROM categories ORDER BY sort_order, name";
$categories_result = $conn->query($categories_query);
$categories = [];

if ($categories_result && $categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Ulanishni yopish
$conn->close();

// Kategoriyalar ierarxiyasini tuzish
function buildCategoryTree($categories, $parent_id = null) {
    $tree = [];
    
    foreach ($categories as $category) {
        if ($category['parent_id'] == $parent_id) {
            $children = buildCategoryTree($categories, $category['id']);
            if ($children) {
                $category['children'] = $children;
            }
            $tree[] = $category;
        }
    }
    
    return $tree;
}

// Kategoriyalar tanlash uchun rekursiv funksiya
function buildCategoryOptions($categories, $indent = '', $selected = null) {
    $html = '';
    
    foreach ($categories as $category) {
        $html .= '<option value="' . $category['id'] . '"';
        if ($selected == $category['id']) {
            $html .= ' selected';
        }
        $html .= '>' . $indent . htmlspecialchars($category['name']) . '</option>';
        
        if (isset($category['children'])) {
            $html .= buildCategoryOptions($category['children'], $indent . '— ', $selected);
        }
    }
    
    return $html;
}

// Kategoriyalar daraxtini tuzish
$category_tree = buildCategoryTree($categories);
?>

<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategoriya qo'shish - SossMM Admin</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            font-size: 16px;
            color: var(--text-color);
            background-color: var(--card-bg);
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .form-text {
            font-size: 14px;
            color: var(--text-light);
            margin-top: 5px;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .form-check-label {
            font-size: 16px;
            cursor: pointer;
        }

        .btn {
            padding: 10px 20px;
            border-radius: var(--radius);
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
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

        .icon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .icon-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 15px 10px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .icon-option:hover {
            background-color: var(--background-light);
        }

        .icon-option.selected {
            background-color: var(--primary-light);
            border-color: var(--primary-color);
        }

        .icon-option i {
            font-size: 24px;
            color: var(--primary-color);
        }

        .icon-option span {
            font-size: 14px;
            text-align: center;
            color: var(--text-color);
        }

        .icon-search {
            margin-bottom: 15px;
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
            
            .icon-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }
        }

        @media (max-width: 576px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
            
            .icon-grid {
                grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
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
                    <a href="products.php" class="nav-link">
                        <i class="fas fa-box"></i>
                        <span>Mahsulotlar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="categories.php" class="nav-link active">
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
                <h1 class="page-title">Yangi kategoriya qo'shish</h1>
                <a href="categories.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Orqaga
                </a>
            </div>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo isset($success_message) ? $success_message : 'Kategoriya muvaffaqiyatli qo\'shildi!'; ?>
            </div>
            <?php endif; ?>
            
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
                    <h2 class="card-title">Kategoriya ma'lumotlari</h2>
                </div>
                <div class="card-body">
                    <form action="add-category.php" method="post">
                        <div class="form-group">
                            <label for="name" class="form-label">Kategoriya nomi *</label>
                            <input type="text" id="name" name="name" class="form-control" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                            <div class="form-text">Kategoriya nomi 100 belgidan oshmasligi kerak</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description" class="form-label">Tavsif</label>
                            <textarea id="description" name="description" class="form-control" rows="4"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                            <div class="form-text">Kategoriya haqida qisqacha ma'lumot (ixtiyoriy)</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="parent_id" class="form-label">Ota-kategoriya</label>
                            <select id="parent_id" name="parent_id" class="form-control">
                                <option value="">Ota-kategoriyasiz (asosiy kategoriya)</option>
                                <?php echo buildCategoryOptions($category_tree, '', isset($parent_id) ? $parent_id : null); ?>
                            </select>
                            <div class="form-text">Agar bu kategoriya boshqa kategoriyaning ichida bo'lsa, ota-kategoriyani tanlang</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Ikonka *</label>
                            <div class="icon-search">
                                <input type="text" id="iconSearch" class="form-control" placeholder="Ikonkalarni qidirish...">
                            </div>
                            <input type="hidden" id="icon" name="icon" value="<?php echo isset($icon) ? htmlspecialchars($icon) : ''; ?>">
                            <div class="icon-grid">
                                <?php foreach ($available_icons as $icon_class => $icon_name): ?>
                                <div class="icon-option <?php echo (isset($icon) && $icon === $icon_class) ? 'selected' : ''; ?>" data-icon="<?php echo $icon_class; ?>">
                                    <i class="<?php echo $icon_class; ?>"></i>
                                    <span><?php echo $icon_name; ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="sort_order" class="form-label">Saralash tartibi</label>
                            <input type="number" id="sort_order" name="sort_order" class="form-control" value="<?php echo isset($sort_order) ? $sort_order : 0; ?>" min="0">
                            <div class="form-text">Kichik raqamli kategoriyalar oldinroq ko'rsatiladi</div>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" id="status" name="status" class="form-check-input" <?php echo (!isset($status) || $status === 'active') ? 'checked' : ''; ?>>
                                <label for="status" class="form-check-label">Faol</label>
                            </div>
                            <div class="form-text">Agar belgilangan bo'lsa, kategoriya saytda ko'rsatiladi</div>
                        </div>
                        
                        <div class="form-actions">
                            <a href="categories.php" class="btn btn-secondary">Bekor qilish</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Saqlash
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Ikonka tanlash
        const iconOptions = document.querySelectorAll('.icon-option');
        const iconInput = document.getElementById('icon');
        
        iconOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Barcha ikonkalardan 'selected' klassini olib tashlash
                iconOptions.forEach(opt => opt.classList.remove('selected'));
                
                // Tanlangan ikonkaga 'selected' klassini qo'shish
                this.classList.add('selected');
                
                // Tanlangan ikonka qiymatini input'ga saqlash
                iconInput.value = this.getAttribute('data-icon');
            });
        });
        
        // Ikonkalarni qidirish
        const iconSearch = document.getElementById('iconSearch');
        const iconGrid = document.querySelector('.icon-grid');
        
        iconSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            iconOptions.forEach(option => {
                const iconName = option.querySelector('span').textContent.toLowerCase();
                const iconClass = option.getAttribute('data-icon').toLowerCase();
                
                if (iconName.includes(searchTerm) || iconClass.includes(searchTerm)) {
                    option.style.display = 'flex';
                } else {
                    option.style.display = 'none';
                }
            });
        });
        
        // Sahifa yuklanganda ikonka tanlangan bo'lsa, uni belgilash
        document.addEventListener('DOMContentLoaded', function() {
            const selectedIcon = iconInput.value;
            if (selectedIcon) {
                const iconOption = document.querySelector(`.icon-option[data-icon="${selectedIcon}"]`);
                if (iconOption) {
                    iconOption.classList.add('selected');
                }
            }
        });
    </script>
</body>
</html>
