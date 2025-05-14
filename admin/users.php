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

// Sahifalash uchun o'zgaruvchilar
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Qidiruv parametrlari
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Foydalanuvchini o'chirish
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    
    // Foydalanuvchini o'chirish so'rovi
    $delete_query = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $success_message = "Foydalanuvchi muvaffaqiyatli o'chirildi.";
    } else {
        $error_message = "Foydalanuvchini o'chirishda xatolik yuz berdi: " . $conn->error;
    }
    
    $stmt->close();
}

// Foydalanuvchi holatini o'zgartirish
if (isset($_GET['toggle_status']) && !empty($_GET['toggle_status'])) {
    $user_id = intval($_GET['toggle_status']);
    $new_status = ($_GET['new_status'] === 'active') ? 'active' : 'inactive';
    
    // Foydalanuvchi holatini yangilash so'rovi
    $update_query = "UPDATE users SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $new_status, $user_id);
    
    if ($stmt->execute()) {
        $success_message = "Foydalanuvchi holati muvaffaqiyatli yangilandi.";
    } else {
        $error_message = "Foydalanuvchi holatini yangilashda xatolik yuz berdi: " . $conn->error;
    }
    
    $stmt->close();
}

// WHERE shartini tuzish
$where_clause = "";
$params = [];
$types = "";

if (!empty($search)) {
    $where_clause = "WHERE (username LIKE ? OR email LIKE ? OR full_name LIKE ? OR phone LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param, $search_param];
    $types = "ssss";
}

if (!empty($status)) {
    if (!empty($where_clause)) {
        $where_clause .= " AND status = ?";
    } else {
        $where_clause = "WHERE status = ?";
    }
    $params[] = $status;
    $types .= "s";
}

// Jami foydalanuvchilar sonini olish
$count_query = "SELECT COUNT(*) as total FROM users $where_clause";
$stmt = $conn->prepare($count_query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$count_result = $stmt->get_result();
$total_users = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_users / $per_page);
$stmt->close();

// Foydalanuvchilar ro'yxatini olish
$users_query = "SELECT * FROM users $where_clause ORDER BY created_at DESC LIMIT ?, ?";
$stmt = $conn->prepare($users_query);

if (!empty($params)) {
    $params[] = $offset;
    $params[] = $per_page;
    $types .= "ii";
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param("ii", $offset, $per_page);
}

$stmt->execute();
$users_result = $stmt->get_result();
$users = [];

while ($user = $users_result->fetch_assoc()) {
    $users[] = $user;
}

$stmt->close();

// Foydalanuvchilar statistikasini olish
$stats_query = "SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
                    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_users,
                    COUNT(DISTINCT CASE WHEN last_login IS NOT NULL THEN id END) as logged_in_users
                FROM users";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Ulanishni yopish
$conn->close();
?>

<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foydalanuvchilar - SossMM Admin</title>
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

        .stat-icon.total {
            background-color: var(--primary-color);
        }

        .stat-icon.active {
            background-color: var(--success-color);
        }

        .stat-icon.inactive {
            background-color: var(--warning-color);
        }

        .stat-icon.logged {
            background-color: var(--info-color);
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

        .search-filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 200px;
            display: flex;
            align-items: center;
            gap: 10px;
            background-color: var(--background-light);
            border-radius: var(--radius);
            padding: 10px 15px;
            border: 1px solid var(--border-color);
        }

        .search-box input {
            flex: 1;
            border: none;
            outline: none;
            background: none;
            font-size: 14px;
            color: var(--text-color);
        }

        .search-box i {
            color: var(--text-light);
        }

        .filter-select {
            min-width: 150px;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            background-color: var(--background-light);
            font-size: 14px;
            color: var(--text-color);
            outline: none;
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

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #c62828;
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background-color: #2e7d32;
        }

        .btn-warning {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-warning:hover {
            background-color: #f57c00;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .table-container {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .table th {
            font-weight: 600;
            color: var(--text-light);
            font-size: 14px;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tr:hover td {
            background-color: var(--background-light);
        }

        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }

        .status.active {
            background-color: #e8f5e9;
            color: #388e3c;
        }

        .status.inactive {
            background-color: #fff8e1;
            color: #ffa000;
        }

        .actions {
            display: flex;
            gap: 5px;
        }

        .action-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: var(--text-color);
            background-color: var(--background-light);
            border: 1px solid var(--border-color);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            background-color: var(--background-dark);
        }

        .action-btn.view {
            color: var(--info-color);
        }

        .action-btn.edit {
            color: var(--warning-color);
        }

        .action-btn.delete {
            color: var(--danger-color);
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .pagination-link {
            padding: 8px 15px;
            border-radius: var(--radius);
            background-color: var(--background-light);
            border: 1px solid var(--border-color);
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
            cursor: not-allowed;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background-color: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: var(--text-light);
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .user-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .detail-item {
            margin-bottom: 15px;
        }

        .detail-label {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 16px;
            font-weight: 500;
        }

        .empty-state {
            text-align: center;
            padding: 50px 0;
        }

        .empty-icon {
            font-size: 48px;
            color: var(--text-lighter);
            margin-bottom: 20px;
        }

        .empty-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .empty-description {
            font-size: 14px;
            color: var(--text-light);
            max-width: 400px;
            margin: 0 auto;
        }

        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
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
            
            .user-details {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .search-filters {
                flex-direction: column;
            }
            
            .pagination {
                flex-wrap: wrap;
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
                    <a href="orders.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Buyurtmalar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="users.php" class="nav-link active">
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
                <h1 class="page-title">Foydalanuvchilar</h1>
                <a href="add-user.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Yangi foydalanuvchi
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
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                        <div class="stat-label">Jami foydalanuvchilar</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon active">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['active_users']; ?></div>
                        <div class="stat-label">Faol foydalanuvchilar</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon inactive">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['inactive_users']; ?></div>
                        <div class="stat-label">Nofaol foydalanuvchilar</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon logged">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['logged_in_users']; ?></div>
                        <div class="stat-label">Kirgan foydalanuvchilar</div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Foydalanuvchilar ro'yxati</h2>
                </div>
                <div class="card-body">
                    <form action="users.php" method="get" class="search-filters">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Qidirish..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <select name="status" class="filter-select">
                            <option value="">Barcha holatlar</option>
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Faol</option>
                            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Nofaol</option>
                        </select>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filtrlash
                        </button>
                        
                        <?php if (!empty($search) || !empty($status)): ?>
                        <a href="users.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Tozalash
                        </a>
                        <?php endif; ?>
                    </form>
                    
                    <?php if (empty($users)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="empty-title">Foydalanuvchilar topilmadi</h3>
                        <p class="empty-description">Hech qanday foydalanuvchi mavjud emas yoki qidiruv natijasi bo'sh.</p>
                    </div>
                    <?php else: ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Foydalanuvchi nomi</th>
                                    <th>To'liq ism</th>
                                    <th>Email</th>
                                    <th>Telefon</th>
                                    <th>Ro'yxatdan o'tgan sana</th>
                                    <th>Holat</th>
                                    <th>Amallar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <span class="status <?php echo $user['status']; ?>">
                                            <?php echo $user['status'] === 'active' ? 'Faol' : 'Nofaol'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <button class="action-btn view" onclick="viewUser(<?php echo $user['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a href="edit-user.php?id=<?php echo $user['id']; ?>" class="action-btn edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($user['status'] === 'active'): ?>
                                            <a href="users.php?toggle_status=<?php echo $user['id']; ?>&new_status=inactive" class="action-btn" title="Nofaol qilish">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                            <?php else: ?>
                                            <a href="users.php?toggle_status=<?php echo $user['id']; ?>&new_status=active" class="action-btn" title="Faollashtirish">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <?php endif; ?>
                                            <button class="action-btn delete" onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>" class="pagination-link">
                            <i class="fas fa-chevron-left"></i> Oldingi
                        </a>
                        <?php else: ?>
                        <span class="pagination-link disabled">
                            <i class="fas fa-chevron-left"></i> Oldingi
                        </span>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1) {
                            echo '<a href="?page=1&search=' . urlencode($search) . '&status=' . urlencode($status) . '" class="pagination-link">1</a>';
                            if ($start_page > 2) {
                                echo '<span class="pagination-link disabled">...</span>';
                            }
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            if ($i == $page) {
                                echo '<span class="pagination-link active">' . $i . '</span>';
                            } else {
                                echo '<a href="?page=' . $i . '&search=' . urlencode($search) . '&status=' . urlencode($status) . '" class="pagination-link">' . $i . '</a>';
                            }
                        }
                        
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<span class="pagination-link disabled">...</span>';
                            }
                            echo '<a href="?page=' . $total_pages . '&search=' . urlencode($search) . '&status=' . urlencode($status) . '" class="pagination-link">' . $total_pages . '</a>';
                        }
                        ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>" class="pagination-link">
                            Keyingi <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php else: ?>
                        <span class="pagination-link disabled">
                            Keyingi <i class="fas fa-chevron-right"></i>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Foydalanuvchi ma'lumotlari modali -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Foydalanuvchi ma'lumotlari</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="userDetails" class="user-details">
                    <!-- Foydalanuvchi ma'lumotlari JavaScript orqali to'ldiriladi -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal()">Yopish</button>
            </div>
        </div>
    </div>
    
    <!-- O'chirish tasdiqlash modali -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Foydalanuvchini o'chirish</h2>
                <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Siz rostdan ham <strong id="deleteUserName"></strong> foydalanuvchisini o'chirmoqchimisiz?</p>
                <p>Bu amal qaytarib bo'lmaydi.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">Bekor qilish</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">O'chirish</a>
            </div>
        </div>
    </div>
    
    <script>
        // Foydalanuvchi ma'lumotlarini ko'rish
        function viewUser(userId) {
            // AJAX so'rovi orqali foydalanuvchi ma'lumotlarini olish
            fetch('get-user.php?id=' + userId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const user = data.user;
                        let html = `
                            <div class="detail-item">
                                <div class="detail-label">ID</div>
                                <div class="detail-value">${user.id}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Foydalanuvchi nomi</div>
                                <div class="detail-value">${user.username}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">To'liq ism</div>
                                <div class="detail-value">${user.full_name || '-'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Email</div>
                                <div class="detail-value">${user.email}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Telefon</div>
                                <div class="detail-value">${user.phone || '-'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Manzil</div>
                                <div class="detail-value">${user.address || '-'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Ro'yxatdan o'tgan sana</div>
                                <div class="detail-value">${formatDate(user.created_at)}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Oxirgi kirish</div>
                                <div class="detail-value">${user.last_login ? formatDate(user.last_login) : 'Hech qachon'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Holat</div>
                                <div class="detail-value">
                                    <span class="status ${user.status}">${user.status === 'active' ? 'Faol' : 'Nofaol'}</span>
                                </div>
                            </div>
                        `;
                        
                        document.getElementById('userDetails').innerHTML = html;
                        document.getElementById('userModal').classList.add('active');
                    } else {
                        alert('Foydalanuvchi ma'lumotlarini olishda xatolik yuz berdi.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Foydalanuvchi ma'lumotlarini olishda xatolik yuz berdi.');
                });
        }
        
        // Modalni yopish
        function closeModal() {
            document.getElementById('userModal').classList.remove('active');
        }
        
        // O'chirish tasdiqlash modali
        function confirmDelete(userId, username) {
            document.getElementById('deleteUserName').textContent = username;
            document.getElementById('confirmDeleteBtn').href = 'users.php?delete=' + userId;
            document.getElementById('deleteModal').classList.add('active');
        }
        
        // O'chirish modalni yopish
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }
        
        // Sanani formatlash
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('uz-UZ', { 
                day: '2-digit', 
                month: '2-digit', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        // Esc tugmasi bosilganda modalni yopish
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
                closeDeleteModal();
            }
        });
        
        // Modal tashqarisiga bosilganda yopish
        window.addEventListener('click', function(event) {
            if (event.target === document.getElementById('userModal')) {
                closeModal();
            }
            if (event.target === document.getElementById('deleteModal')) {
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>