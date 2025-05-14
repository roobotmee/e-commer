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

// Admin ma'lumotlarini olish
$admin_id = $_SESSION['admin_id'];
$admin_query = "SELECT * FROM admins WHERE id = ?";
$stmt = $conn->prepare($admin_query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin_result = $stmt->get_result();
$admin = $admin_result->fetch_assoc();

// Statistikani olish
$total_products = 0;
$total_orders = 0;
$total_users = 0;
$total_revenue = 0;

$query = "SELECT COUNT(*) as count FROM products";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $total_products = $row['count'];
}

$query = "SELECT COUNT(*) as count FROM orders";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $total_orders = $row['count'];
}

$query = "SELECT COUNT(*) as count FROM users";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $total_users = $row['count'];
}

$query = "SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $total_revenue = $row['total'] ? $row['total'] : 0;
}

// So'nggi buyurtmalarni olish
$recent_orders = [];
$query = "SELECT o.*, u.username FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recent_orders[] = $row;
    }
}

// So'nggi foydalanuvchilarni olish
$recent_users = [];
$query = "SELECT * FROM users ORDER BY created_at DESC LIMIT 5";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recent_users[] = $row;
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
    <title>Boshqaruv paneli - SossMM</title>
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

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-name {
            font-weight: 500;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-weight: 600;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--card-bg);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }

        .stat-icon.products {
            background-color: var(--primary-color);
        }

        .stat-icon.orders {
            background-color: var(--success-color);
        }

        .stat-icon.users {
            background-color: var(--info-color);
        }

        .stat-icon.revenue {
            background-color: var(--warning-color);
        }

        .stat-info {
            flex: 1;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 14px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .card {
            background-color: var(--card-bg);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
        }

        .card-action {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
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
            font-weight: 600;
            color: var(--text-light);
            font-size: 14px;
        }

        .orders-table tr:last-child td {
            border-bottom: none;
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

        .status.completed {
            background-color: #e8f5e9;
            color: #388e3c;
        }

        .status.cancelled {
            background-color: #ffebee;
            color: #d32f2f;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: var(--radius);
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
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

        .activity-list {
            list-style: none;
        }

        .activity-item {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 16px;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 500;
            margin-bottom: 5px;
        }

        .activity-time {
            font-size: 13px;
            color: var(--text-light);
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .quick-action {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 15px;
            text-align: center;
            text-decoration: none;
            color: var(--text-color);
            transition: all 0.3s ease;
        }

        .quick-action:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .quick-action i {
            font-size: 24px;
            margin-bottom: 10px;
            display: block;
        }

        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .content-grid {
                grid-template-columns: 1fr;
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
        }

        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .user-info {
                align-self: flex-end;
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
                    <a href="index.php" class="nav-link active">
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
                <h1 class="page-title">Boshqaruv paneli</h1>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($admin['full_name'] ?? $admin['username']); ?></span>
                    <div class="user-avatar"><?php echo strtoupper(substr($admin['username'], 0, 1)); ?></div>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon products">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $total_products; ?></div>
                        <div class="stat-label">Jami mahsulotlar</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon orders">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $total_orders; ?></div>
                        <div class="stat-label">Jami buyurtmalar</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $total_users; ?></div>
                        <div class="stat-label">Jami foydalanuvchilar</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon revenue">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($total_revenue, 0, '.', ','); ?> so'm</div>
                        <div class="stat-label">Jami daromad</div>
                    </div>
                </div>
            </div>
            
            <div class="content-grid">
                <div class="left-column">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">So'nggi buyurtmalar</h2>
                            <a href="orders.php" class="card-action">Barchasini ko'rish</a>
                        </div>
                        <div class="card-content">
                            <table class="orders-table">
                                <thead>
                                    <tr>
                                        <th>Buyurtma ID</th>
                                        <th>Mijoz</th>
                                        <th>Sana</th>
                                        <th>Summa</th>
                                        <th>Holati</th>
                                        <th>Amallar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo $order['username'] ? htmlspecialchars($order['username']) : htmlspecialchars($order['full_name']); ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($order['created_at'])); ?></td>
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
                                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="action-btn view-btn">Ko'rish</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($recent_orders)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center;">Buyurtmalar topilmadi</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="right-column">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Tezkor amallar</h2>
                        </div>
                        <div class="card-content">
                            <div class="quick-actions">
                                <a href="add-product.php" class="quick-action">
                                    <i class="fas fa-plus"></i>
                                    Mahsulot qo'shish
                                </a>
                                <a href="add-category.php" class="quick-action">
                                    <i class="fas fa-folder-plus"></i>
                                    Kategoriya qo'shish
                                </a>
                                <a href="reports.php" class="quick-action">
                                    <i class="fas fa-chart-bar"></i>
                                    Hisobotlarni ko'rish
                                </a>
                                <a href="backup.php" class="quick-action">
                                    <i class="fas fa-database"></i>
                                    Ma'lumotlar zaxirasi
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">So'nggi foydalanuvchilar</h2>
                            <a href="users.php" class="card-action">Barchasini ko'rish</a>
                        </div>
                        <div class="card-content">
                            <ul class="activity-list">
                                <?php foreach ($recent_users as $user): ?>
                                <li class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title"><?php echo htmlspecialchars($user['username']); ?></div>
                                        <div class="activity-time"><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></div>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                                <?php if (empty($recent_users)): ?>
                                <li class="activity-item">
                                    <div class="activity-content">
                                        <div class="activity-title">Foydalanuvchilar topilmadi</div>
                                    </div>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>