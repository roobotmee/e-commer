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
    $search_condition = "WHERE o.order_number LIKE '%$search%' OR o.full_name LIKE '%$search%' OR o.email LIKE '%$search%' OR o.phone LIKE '%$search%'";
}

// Filtrlash
$status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
if (!empty($status_filter)) {
    $search_condition = empty($search_condition) ? "WHERE o.status = '$status_filter'" : $search_condition . " AND o.status = '$status_filter'";
}

// Jami buyurtmalar sonini olish
$count_query = "SELECT COUNT(*) as total FROM orders o $search_condition";
$count_result = $conn->query($count_query);
$total_orders = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $items_per_page);

// Buyurtmalarni olish
$orders_query = "SELECT o.*, u.username 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                $search_condition 
                ORDER BY o.created_at DESC 
                LIMIT $offset, $items_per_page";
$orders_result = $conn->query($orders_query);
$orders = [];

if ($orders_result && $orders_result->num_rows > 0) {
    while ($row = $orders_result->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Holat o'zgartirish amalini bajarish
if (isset($_GET['action']) && $_GET['action'] === 'change_status' && isset($_GET['id']) && isset($_GET['status'])) {
    $order_id = intval($_GET['id']);
    $new_status = $conn->real_escape_string($_GET['status']);
    
    $update_query = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $new_status, $order_id);
    
    if ($stmt->execute()) {
        $success_message = "Buyurtma holati muvaffaqiyatli o'zgartirildi.";
    } else {
        $error_message = "Buyurtma holatini o'zgartirishda xatolik yuz berdi: " . $stmt->error;
    }
    
    $stmt->close();
    
    // URL dan amaliyotni olib tashlash uchun qayta yo'naltirish
    header("Location: orders.php" . (!empty($search) ? "?search=$search" : "") . (!empty($status_filter) ? (empty($search) ? "?status=$status_filter" : "&status=$status_filter") : ""));
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
    <title>Buyurtmalar - SossMM Admin</title>
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

        .filter-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .filter-select {
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 14px;
            background-color: white;
        }

        .filter-btn {
            padding: 10px 15px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .filter-btn:hover {
            background-color: #5a4690;
        }

        .reset-btn {
            padding: 10px 15px;
            background-color: var(--background-color);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .reset-btn:hover {
            background-color: var(--border-color);
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

        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th, .orders-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .orders-table th {
            background-color: var(--background-color);
            font-weight: 600;
            color: var(--text-light);
        }

        .orders-table tr:last-child td {
            border-bottom: none;
        }

        .orders-table tr:hover {
            background-color: var(--background-color);
        }

        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }

        .status.pending {
            background-color: #fff8e1;
            color: #ffa000;
        }

        .status.processing {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .status.shipped {
            background-color: #e0f2f1;
            color: #00897b;
        }

        .status.delivered {
            background-color: #f3e5f5;
            color: #7b1fa2;
        }

        .status.completed {
            background-color: #e8f5e9;
            color: #388e3c;
        }

        .status.cancelled {
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

        .view-btn {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        .view-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .status-dropdown {
            position: relative;
            display: inline-block;
        }

        .status-btn {
            padding: 6px 10px;
            border-radius: var(--radius);
            font-size: 13px;
            background-color: var(--background-color);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .status-dropdown-content {
            display: none;
            position: absolute;
            background-color: var(--card-bg);
            min-width: 160px;
            box-shadow: var(--shadow);
            border-radius: var(--radius);
            z-index: 1;
            right: 0;
        }

        .status-dropdown-content a {
            color: var(--text-color);
            padding: 8px 12px;
            text-decoration: none;
            display: block;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .status-dropdown-content a:hover {
            background-color: var(--background-color);
        }

        .status-dropdown:hover .status-dropdown-content {
            display: block;
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

        .no-orders {
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
            
            .orders-table th:nth-child(3), 
            .orders-table td:nth-child(3),
            .orders-table th:nth-child(4), 
            .orders-table td:nth-child(4) {
                display: none;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 15px;
            }
            
            .filter-form {
                flex-direction: column;
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
                    <a href="categories.php" class="nav-link">
                        <i class="fas fa-tags"></i>
                        <span>Kategoriyalar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="orders.php" class="nav-link active">
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
                <h1 class="page-title">Buyurtmalar</h1>
                
                <form action="" method="get" class="search-form">
                    <input type="text" name="search" placeholder="Buyurtmalarni qidirish..." class="search-input" value="<?php echo htmlspecialchars($search); ?>">
                    <?php if (!empty($status_filter)): ?>
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                    <?php endif; ?>
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
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
            
            <form action="" method="get" class="filter-form">
                <select name="status" class="filter-select">
                    <option value="">Barcha holatlar</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Kutilmoqda</option>
                    <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Jarayonda</option>
                    <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Yuborilgan</option>
                    <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Yetkazilgan</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Bajarilgan</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Bekor qilingan</option>
                </select>
                <?php if (!empty($search)): ?>
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <?php endif; ?>
                <button type="submit" class="filter-btn">Filtrlash</button>
                <a href="orders.php" class="reset-btn">Tozalash</a>
            </form>
            
            <div class="card">
                <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <i class="fas fa-shopping-cart" style="font-size: 48px; margin-bottom: 15px;"></i>
                    <h3>Buyurtmalar topilmadi</h3>
                    <?php if (!empty($search) || !empty($status_filter)): ?>
                    <p>Qidiruv yoki filtr bo'yicha buyurtmalar topilmadi. <a href="orders.php">Barcha buyurtmalarni ko'rish</a></p>
                    <?php else: ?>
                    <p>Hozircha buyurtmalar mavjud emas.</p>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Buyurtma ID</th>
                            <th>Mijoz</th>
                            <th>Sana</th>
                            <th>To'lov usuli</th>
                            <th>Summa</th>
                            <th>Holati</th>
                            <th>Amallar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['order_number']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($order['username'] ? $order['username'] : $order['full_name']); ?>
                                <div style="font-size: 12px; color: var(--text-light);"><?php echo htmlspecialchars($order['phone']); ?></div>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                            <td><?php echo number_format($order['total_amount'], 0, '.', ','); ?> so'm</td>
                            <td>
                                <span class="status <?php echo strtolower($order['status']); ?>">
                                    <?php 
                                    $status = strtolower($order['status']);
                                    if ($status == 'pending') echo "Kutilmoqda";
                                    elseif ($status == 'processing') echo "Jarayonda";
                                    elseif ($status == 'shipped') echo "Yuborilgan";
                                    elseif ($status == 'delivered') echo "Yetkazilgan";
                                    elseif ($status == 'completed') echo "Bajarilgan";
                                    elseif ($status == 'cancelled') echo "Bekor qilingan";
                                    else echo ucfirst($status);
                                    ?>
                                </span>
                            </td>
                            <td>
                                <a href="order-details.php?id=<?php echo $order['id']; ?>" class="action-btn view-btn">
                                    <i class="fas fa-eye"></i> Ko'rish
                                </a>
                                <div class="status-dropdown">
                                    <button class="status-btn">
                                        <i class="fas fa-cog"></i> Holat
                                    </button>
                                    <div class="status-dropdown-content">
                                        <a href="orders.php?action=change_status&id=<?php echo $order['id']; ?>&status=pending<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">Kutilmoqda</a>
                                        <a href="orders.php?action=change_status&id=<?php echo $order['id']; ?>&status=processing<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">Jarayonda</a>
                                        <a href="orders.php?action=change_status&id=<?php echo $order['id']; ?>&status=shipped<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">Yuborilgan</a>
                                        <a href="orders.php?action=change_status&id=<?php echo $order['id']; ?>&status=delivered<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">Yetkazilgan</a>
                                        <a href="orders.php?action=change_status&id=<?php echo $order['id']; ?>&status=completed<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">Bajarilgan</a>
                                        <a href="orders.php?action=change_status&id=<?php echo $order['id']; ?>&status=cancelled<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">Bekor qilish</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <a href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" class="pagination-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <i class="fas fa-angle-double-left"></i>
                </a>
                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" class="pagination-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <i class="fas fa-angle-left"></i>
                </a>
                
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" class="pagination-link <?php echo $i == $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" class="pagination-link <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <i class="fas fa-angle-right"></i>
                </a>
                <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" class="pagination-link <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <i class="fas fa-angle-double-right"></i>
                </a>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>