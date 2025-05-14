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

// Sahifalash
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Qidiruv funksiyasi
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$search_condition = '';
if (!empty($search)) {
    $search_condition = "WHERE c.name LIKE '%$search%' OR c.description LIKE '%$search%'";
}

// Jami kategoriyalar sonini olish
$count_query = "SELECT COUNT(*) as total FROM categories c $search_condition";
$count_result = $conn->query($count_query);
$total_categories = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_categories / $items_per_page);

// Kategoriyalarni olish
$categories_query = "SELECT c.*, p.name as parent_name 
                    FROM categories c 
                    LEFT JOIN categories p ON c.parent_id = p.id 
                    $search_condition 
                    ORDER BY c.id DESC 
                    LIMIT $offset, $items_per_page";
$categories_result = $conn->query($categories_query);
$categories = [];

if ($categories_result && $categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// O'chirish amalini bajarish
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $category_id = intval($_GET['id']);
    
    // Kategoriyaga bog'liq mahsulotlar borligini tekshirish
    $check_products_query = "SELECT COUNT(*) as count FROM products WHERE category_id = $category_id";
    $check_products_result = $conn->query($check_products_query);
    $products_count = $check_products_result->fetch_assoc()['count'];
    
    if ($products_count > 0) {
        $error_message = "Bu kategoriyada $products_count ta mahsulot mavjud. Avval mahsulotlarni boshqa kategoriyaga o'tkazing yoki o'chiring.";
    } else {
        // Kategoriyani o'chirish
        $delete_query = "DELETE FROM categories WHERE id = $category_id";
        if ($conn->query($delete_query)) {
            $success_message = "Kategoriya muvaffaqiyatli o'chirildi.";
        } else {
            $error_message = "Kategoriyani o'chirishda xatolik yuz berdi: " . $conn->error;
        }
    }
    
    // URL dan amaliyotni olib tashlash uchun qayta yo'naltirish
    header("Location: categories.php" . (!empty($search) ? "?search=$search" : ""));
    exit;
}

// Ulanishni yopish
$conn->close();
?>

<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategoriyalar - SossMM Admin</title>
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

        .search-form {
            display: flex;
            gap: 10px;
            max-width: 400px;
        }

        .search-input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 14px;
        }

        .search-btn {
            padding: 10px 15px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .search-btn:hover {
            background-color: #5a4690;
        }

        .add-btn {
            padding: 10px 15px;
            background-color: var(--success-color);
            color: white;
            border: none;
            border-radius: var(--radius);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: background-color 0.3s ease;
        }

        .add-btn:hover {
            background-color: #3d8b40;
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

        .card {
            background-color: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .categories-table {
            width: 100%;
            border-collapse: collapse;
        }

        .categories-table th, .categories-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .categories-table th {
            background-color: var(--background-color);
            font-weight: 600;
            color: var(--text-light);
        }

        .categories-table tr:last-child td {
            border-bottom: none;
        }

        .categories-table tr:hover {
            background-color: var(--background-color);
        }

        .category-image {
            width: 60px;
            height: 60px;
            border-radius: var(--radius);
            object-fit: cover;
        }

        .category-name {
            font-weight: 500;
            color: var(--text-color);
            text-decoration: none;
        }

        .category-name:hover {
            color: var(--primary-color);
        }

        .parent-category {
            font-size: 14px;
            color: var(--text-light);
        }

        .category-status {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }

        .status-active {
            background-color: #e8f5e9;
            color: #388e3c;
        }

        .status-inactive {
            background-color: #ffebee;
            color: #d32f2f;
        }

        .action-btn {
            padding: 6px 10px;
            border-radius: var(--radius);
            font-size: 13px;
            text-decoration: none;
            display: inline-block;
            margin-right: 5px;
            transition: all 0.3s ease;
        }

        .edit-btn {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        .edit-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .delete-btn {
            background-color: #ffebee;
            color: var(--danger-color);
        }

        .delete-btn:hover {
            background-color: var(--danger-color);
            color: white;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }

        .pagination-link {
            padding: 8px 12px;
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .pagination-link:hover, .pagination-link.active {
            background-color: var(--primary-light);
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .pagination-link.disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        .no-categories {
            padding: 30px;
            text-align: center;
            color: var(--text-light);
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
            
            .header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .search-form {
                width: 100%;
                max-width: none;
            }
            
            .categories-table th:nth-child(3), 
            .categories-table td:nth-child(3) {
                display: none;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 15px;
            }
            
            .categories-table th:nth-child(4), 
            .categories-table td:nth-child(4) {
                display: none;
            }
            
            .action-btn {
                padding: 5px 8px;
                font-size: 12px;
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
                <h1 class="page-title">Kategoriyalar</h1>
                
                <form action="" method="get" class="search-form">
                    <input type="text" name="search" placeholder="Kategoriyalarni qidirish..." class="search-input" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                
                <a href="add-category.php" class="add-btn">
                    <i class="fas fa-plus"></i> Kategoriya qo'shish
                </a>
            </div>
            
            <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <?php if (empty($categories)): ?>
                <div class="no-categories">
                    <i class="fas fa-tags" style="font-size: 48px; margin-bottom: 15px;"></i>
                    <h3>Kategoriyalar topilmadi</h3>
                    <?php if (!empty($search)): ?>
                    <p>Qidiruv bo'yicha kategoriyalar topilmadi. <a href="categories.php">Barcha kategoriyalarni ko'rish</a></p>
                    <?php else: ?>
                    <p>Birinchi kategoriyangizni qo'shing. <a href="add-category.php">Kategoriya qo'shish</a></p>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <table class="categories-table">
                    <thead>
                        <tr>
                            <th>Rasm</th>
                            <th>Nomi</th>
                            <th>Ota kategoriya</th>
                            <th>Holati</th>
                            <th>Amallar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                        <tr>
                            <td>
                                <img src="<?php echo !empty($category['image']) ? '../' . $category['image'] : '../assets/images/default-category.jpg'; ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" class="category-image">
                            </td>
                            <td>
                                <a href="edit-category.php?id=<?php echo $category['id']; ?>" class="category-name"><?php echo htmlspecialchars($category['name']); ?></a>
                                <div class="parent-category"><?php echo !empty($category['slug']) ? htmlspecialchars($category['slug']) : ''; ?></div>
                            </td>
                            <td><?php echo !empty($category['parent_name']) ? htmlspecialchars($category['parent_name']) : 'Asosiy kategoriya'; ?></td>
                            <td>
                                <?php if ($category['status'] == 1): ?>
                                <span class="category-status status-active">Faol</span>
                                <?php else: ?>
                                <span class="category-status status-inactive">Nofaol</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit-category.php?id=<?php echo $category['id']; ?>" class="action-btn edit-btn">
                                    <i class="fas fa-edit"></i> Tahrirlash
                                </a>
                                <a href="categories.php?action=delete&id=<?php echo $category['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Haqiqatan ham bu kategoriyani o\'chirmoqchimisiz?');">
                                    <i class="fas fa-trash"></i> O'chirish
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <a href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <i class="fas fa-angle-double-left"></i>
                </a>
                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <i class="fas fa-angle-left"></i>
                </a>
                
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-link <?php echo $i == $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-link <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <i class="fas fa-angle-right"></i>
                </a>
                <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-link <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <i class="fas fa-angle-double-right"></i>
                </a>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>