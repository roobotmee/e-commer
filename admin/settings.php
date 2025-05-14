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
$stmt->close();

// Sozlamalarni olish
$settings = [];
$settings_query = "SELECT * FROM settings";
$settings_result = $conn->query($settings_query);

if ($settings_result && $settings_result->num_rows > 0) {
    while ($row = $settings_result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Forma yuborilganligini tekshirish
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tab = isset($_POST['tab']) ? $_POST['tab'] : 'general';
    
    // Umumiy sozlamalar
    if ($tab === 'general') {
        $site_name = trim($_POST['site_name']);
        $site_description = trim($_POST['site_description']);
        $site_email = trim($_POST['site_email']);
        $site_phone = trim($_POST['site_phone']);
        $site_address = trim($_POST['site_address']);
        
        // Sozlamalarni yangilash
        updateSetting($conn, 'site_name', $site_name);
        updateSetting($conn, 'site_description', $site_description);
        updateSetting($conn, 'site_email', $site_email);
        updateSetting($conn, 'site_phone', $site_phone);
        updateSetting($conn, 'site_address', $site_address);
        
        $success_message = "Umumiy sozlamalar muvaffaqiyatli yangilandi.";
    }
    
    // To'lov sozlamalari
    elseif ($tab === 'payment') {
        $currency = trim($_POST['currency']);
        $currency_symbol = trim($_POST['currency_symbol']);
        $payment_cash = isset($_POST['payment_cash']) ? 1 : 0;
        $payment_card = isset($_POST['payment_card']) ? 1 : 0;
        $payment_installment = isset($_POST['payment_installment']) ? 1 : 0;
        $installment_terms = trim($_POST['installment_terms']);
        
        // Sozlamalarni yangilash
        updateSetting($conn, 'currency', $currency);
        updateSetting($conn, 'currency_symbol', $currency_symbol);
        updateSetting($conn, 'payment_cash', $payment_cash);
        updateSetting($conn, 'payment_card', $payment_card);
        updateSetting($conn, 'payment_installment', $payment_installment);
        updateSetting($conn, 'installment_terms', $installment_terms);
        
        $success_message = "To'lov sozlamalari muvaffaqiyatli yangilandi.";
    }
    
    // Yetkazib berish sozlamalari
    elseif ($tab === 'shipping') {
        $shipping_cost = trim($_POST['shipping_cost']);
        $free_shipping_threshold = trim($_POST['free_shipping_threshold']);
        $shipping_regions = trim($_POST['shipping_regions']);
        
        // Sozlamalarni yangilash
        updateSetting($conn, 'shipping_cost', $shipping_cost);
        updateSetting($conn, 'free_shipping_threshold', $free_shipping_threshold);
        updateSetting($conn, 'shipping_regions', $shipping_regions);
        
        $success_message = "Yetkazib berish sozlamalari muvaffaqiyatli yangilandi.";
    }
    
    // Email sozlamalari
    elseif ($tab === 'email') {
        $smtp_host = trim($_POST['smtp_host']);
        $smtp_port = trim($_POST['smtp_port']);
        $smtp_username = trim($_POST['smtp_username']);
        $smtp_password = trim($_POST['smtp_password']);
        $smtp_encryption = trim($_POST['smtp_encryption']);
        $email_from_name = trim($_POST['email_from_name']);
        
        // Sozlamalarni yangilash
        updateSetting($conn, 'smtp_host', $smtp_host);
        updateSetting($conn, 'smtp_port', $smtp_port);
        updateSetting($conn, 'smtp_username', $smtp_username);
        
        // Parol kiritilgan bo'lsagina yangilash
        if (!empty($smtp_password)) {
            updateSetting($conn, 'smtp_password', $smtp_password);
        }
        
        updateSetting($conn, 'smtp_encryption', $smtp_encryption);
        updateSetting($conn, 'email_from_name', $email_from_name);
        
        $success_message = "Email sozlamalari muvaffaqiyatli yangilandi.";
    }
    
    // Admin profili
    elseif ($tab === 'profile') {
        $username = trim($_POST['username']);
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Foydalanuvchi nomini tekshirish
        if ($username !== $admin['username']) {
            $check_username_query = "SELECT id FROM admins WHERE username = ? AND id != ?";
            $stmt = $conn->prepare($check_username_query);
            $stmt->bind_param("si", $username, $admin_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error_message = "Bu foydalanuvchi nomi allaqachon mavjud. Iltimos, boshqa foydalanuvchi nomini tanlang.";
            } else {
                // Foydalanuvchi nomini yangilash
                $update_username_query = "UPDATE admins SET username = ? WHERE id = ?";
                $stmt = $conn->prepare($update_username_query);
                $stmt->bind_param("si", $username, $admin_id);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        // Agar xatolik bo'lmasa, davom etish
        if (!isset($error_message)) {
            // To'liq ism va emailni yangilash
            $update_profile_query = "UPDATE admins SET full_name = ?, email = ? WHERE id = ?";
            $stmt = $conn->prepare($update_profile_query);
            $stmt->bind_param("ssi", $full_name, $email, $admin_id);
            $stmt->execute();
            $stmt->close();
            
            // Parolni yangilash (agar kiritilgan bo'lsa)
            if (!empty($current_password) && !empty($new_password)) {
                // Joriy parolni tekshirish
                if (password_verify($current_password, $admin['password'])) {
                    // Yangi parol va tasdiqlash paroli bir xil ekanligini tekshirish
                    if ($new_password === $confirm_password) {
                        // Yangi parolni heshlab saqlash
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $update_password_query = "UPDATE admins SET password = ? WHERE id = ?";
                        $stmt = $conn->prepare($update_password_query);
                        $stmt->bind_param("si", $hashed_password, $admin_id);
                        $stmt->execute();
                        $stmt->close();
                        
                        $success_message = "Profil va parol muvaffaqiyatli yangilandi.";
                    } else {
                        $error_message = "Yangi parol va tasdiqlash paroli bir xil emas.";
                    }
                } else {
                    $error_message = "Joriy parol noto'g'ri.";
                }
            } else {
                $success_message = "Profil muvaffaqiyatli yangilandi.";
            }
        }
    }
    
    // Sozlamalarni qayta olish
    $settings_result = $conn->query($settings_query);
    $settings = [];
    
    if ($settings_result && $settings_result->num_rows > 0) {
        while ($row = $settings_result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    // Admin ma'lumotlarini qayta olish
    $stmt = $conn->prepare($admin_query);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $admin_result = $stmt->get_result();
    $admin = $admin_result->fetch_assoc();
    $stmt->close();
}

// Sozlamani yangilash yoki qo'shish uchun funksiya
function updateSetting($conn, $key, $value) {
    // Sozlama mavjudligini tekshirish
    $check_query = "SELECT id FROM settings WHERE setting_key = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    
    if ($result->num_rows > 0) {
        // Sozlamani yangilash
        $update_query = "UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ss", $value, $key);
        $stmt->execute();
        $stmt->close();
    } else {
        // Yangi sozlama qo'shish
        $insert_query = "INSERT INTO settings (setting_key, setting_value, created_at, updated_at) VALUES (?, ?, NOW(), NOW())";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ss", $key, $value);
        $stmt->execute();
        $stmt->close();
    }
}

// Aktiv tabni aniqlash
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

// Ulanishni yopish
$conn->close();
?>

<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sozlamalar - SossMM Admin</title>
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

        .settings-tabs {
            display: flex;
            margin-bottom: 20px;
            background-color: var(--card-bg);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .tab-link {
            padding: 15px 20px;
            color: var(--text-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab-link.active {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        .tab-link:hover:not(.active) {
            background-color: var(--background-light);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
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
            min-height: 100px;
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

        .note {
            font-size: 14px;
            color: var(--text-light);
            margin-top: 5px;
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
            
            .settings-tabs {
                flex-wrap: wrap;
            }
            
            .tab-link {
                padding: 10px 15px;
                font-size: 13px;
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
                    <a href="settings.php" class="nav-link active">
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
                <h1 class="page-title">Sozlamalar</h1>
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
            
            <div class="settings-tabs">
                <a href="?tab=general" class="tab-link <?php echo $active_tab === 'general' ? 'active' : ''; ?>">
                    <i class="fas fa-globe"></i> Umumiy
                </a>
                <a href="?tab=payment" class="tab-link <?php echo $active_tab === 'payment' ? 'active' : ''; ?>">
                    <i class="fas fa-credit-card"></i> To'lov
                </a>
                <a href="?tab=shipping" class="tab-link <?php echo $active_tab === 'shipping' ? 'active' : ''; ?>">
                    <i class="fas fa-truck"></i> Yetkazib berish
                </a>
                <a href="?tab=email" class="tab-link <?php echo $active_tab === 'email' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i> Email
                </a>
                <a href="?tab=profile" class="tab-link <?php echo $active_tab === 'profile' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i> Profil
                </a>
            </div>
            
            <!-- Umumiy sozlamalar -->
            <div id="general-tab" class="tab-content <?php echo $active_tab === 'general' ? 'active' : ''; ?>">
                <form action="settings.php?tab=general" method="post">
                    <input type="hidden" name="tab" value="general">
                    
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Umumiy sozlamalar</h2>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="site_name" class="form-label">Sayt nomi</label>
                                <input type="text" id="site_name" name="site_name" class="form-control" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'SossMM'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="site_description" class="form-label">Sayt tavsifi</label>
                                <textarea id="site_description" name="site_description" class="form-control"><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="site_email" class="form-label">Sayt email</label>
                                        <input type="email" id="site_email" name="site_email" class="form-control" value="<?php echo htmlspecialchars($settings['site_email'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="site_phone" class="form-label">Sayt telefon</label>
                                        <input type="text" id="site_phone" name="site_phone" class="form-control" value="<?php echo htmlspecialchars($settings['site_phone'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="site_address" class="form-label">Sayt manzili</label>
                                <textarea id="site_address" name="site_address" class="form-control"><?php echo htmlspecialchars($settings['site_address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Saqlash
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- To'lov sozlamalari -->
            <div id="payment-tab" class="tab-content <?php echo $active_tab === 'payment' ? 'active' : ''; ?>">
                <form action="settings.php?tab=payment" method="post">
                    <input type="hidden" name="tab" value="payment">
                    
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">To'lov sozlamalari</h2>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="currency" class="form-label">Valyuta</label>
                                        <input type="text" id="currency" name="currency" class="form-control" value="<?php echo htmlspecialchars($settings['currency'] ?? 'UZS'); ?>">
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="currency_symbol" class="form-label">Valyuta belgisi</label>
                                        <input type="text" id="currency_symbol" name="currency_symbol" class="form-control" value="<?php echo htmlspecialchars($settings['currency_symbol'] ?? 'so\'m'); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">To'lov usullari</label>
                                <div class="form-check">
                                    <input type="checkbox" id="payment_cash" name="payment_cash" class="form-check-input" value="1" <?php echo (isset($settings['payment_cash']) && $settings['payment_cash'] == 1) ? 'checked' : ''; ?>>
                                    <label for="payment_cash" class="form-check-label">Naqd pul</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" id="payment_card" name="payment_card" class="form-check-input" value="1" <?php echo (isset($settings['payment_card']) && $settings['payment_card'] == 1) ? 'checked' : ''; ?>>
                                    <label for="payment_card" class="form-check-label">Karta orqali</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" id="payment_installment" name="payment_installment" class="form-check-input" value="1" <?php echo (isset($settings['payment_installment']) && $settings['payment_installment'] == 1) ? 'checked' : ''; ?>>
                                    <label for="payment_installment" class="form-check-label">Muddatli to'lov</label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="installment_terms" class="form-label">Muddatli to'lov shartlari (oy)</label>
                                <input type="text" id="installment_terms" name="installment_terms" class="form-control" value="<?php echo htmlspecialchars($settings['installment_terms'] ?? '3,6,12,24'); ?>">
                                <p class="note">Vergul bilan ajrating, masalan: 3,6,12,24</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Saqlash
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Yetkazib berish sozlamalari -->
            <div id="shipping-tab" class="tab-content <?php echo $active_tab === 'shipping' ? 'active' : ''; ?>">
                <form action="settings.php?tab=shipping" method="post">
                    <input type="hidden" name="tab" value="shipping">
                    
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Yetkazib berish sozlamalari</h2>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="shipping_cost" class="form-label">Yetkazib berish narxi</label>
                                        <input type="number" id="shipping_cost" name="shipping_cost" class="form-control" value="<?php echo htmlspecialchars($settings['shipping_cost'] ?? '15000'); ?>">
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="free_shipping_threshold" class="form-label">Bepul yetkazib berish chegarasi</label>
                                        <input type="number" id="free_shipping_threshold" name="free_shipping_threshold" class="form-control" value="<?php echo htmlspecialchars($settings['free_shipping_threshold'] ?? '100000'); ?>">
                                        <p class="note">Bu summadan yuqori buyurtmalar uchun yetkazib berish bepul</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="shipping_regions" class="form-label">Yetkazib berish hududlari</label>
                                <textarea id="shipping_regions" name="shipping_regions" class="form-control"><?php echo htmlspecialchars($settings['shipping_regions'] ?? 'Toshkent shahar, Toshkent viloyati'); ?></textarea>
                                <p class="note">Har bir hududni yangi qatorga yozing</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Saqlash
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Email sozlamalari -->
            <div id="email-tab" class="tab-content <?php echo $active_tab === 'email' ? 'active' : ''; ?>">
                <form action="settings.php?tab=email" method="post">
                    <input type="hidden" name="tab" value="email">
                    
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Email sozlamalari</h2>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="smtp_host" class="form-label">SMTP host</label>
                                        <input type="text" id="smtp_host" name="smtp_host" class="form-control" value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="smtp_port" class="form-label">SMTP port</label>
                                        <input type="text" id="smtp_port" name="smtp_port" class="form-control" value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="smtp_username" class="form-label">SMTP foydalanuvchi nomi</label>
                                        <input type="text" id="smtp_username" name="smtp_username" class="form-control" value="<?php echo htmlspecialchars($settings['smtp_username'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="smtp_password" class="form-label">SMTP parol</label>
                                        <input type="password" id="smtp_password" name="smtp_password" class="form-control" placeholder="<?php echo empty($settings['smtp_password']) ? '' : '••••••••'; ?>">
                                        <p class="note">O'zgartirish kerak bo'lmasa bo'sh qoldiring</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="smtp_encryption" class="form-label">SMTP shifrlash</label>
                                        <select id="smtp_encryption" name="smtp_encryption" class="form-control">
                                            <option value="tls" <?php echo (isset($settings['smtp_encryption']) && $settings['smtp_encryption'] == 'tls') ? 'selected' : ''; ?>>TLS</option>
                                            <option value="ssl" <?php echo (isset($settings['smtp_encryption']) && $settings['smtp_encryption'] == 'ssl') ? 'selected' : ''; ?>>SSL</option>
                                            <option value="" <?php echo (empty($settings['smtp_encryption'])) ? 'selected' : ''; ?>>Yo'q</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="email_from_name" class="form-label">Yuboruvchi nomi</label>
                                        <input type="text" id="email_from_name" name="email_from_name" class="form-control" value="<?php echo htmlspecialchars($settings['email_from_name'] ?? 'SossMM'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Saqlash
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Profil sozlamalari -->
            <div id="profile-tab" class="tab-content <?php echo $active_tab === 'profile' ? 'active' : ''; ?>">
                <form action="settings.php?tab=profile" method="post">
                    <input type="hidden" name="tab" value="profile">
                    
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Profil sozlamalari</h2>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="username" class="form-label">Foydalanuvchi nomi</label>
                                        <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($admin['username']); ?>">
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="full_name" class="form-label">To'liq ism</label>
                                        <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo htmlspecialchars($admin['full_name'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Parolni o'zgartirish</h2>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="current_password" class="form-label">Joriy parol</label>
                                <input type="password" id="current_password" name="current_password" class="form-control">
                                <p class="note">Parolni o'zgartirish kerak bo'lmasa bo'sh qoldiring</p>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="new_password" class="form-label">Yangi parol</label>
                                        <input type="password" id="new_password" name="new_password" class="form-control">
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="confirm_password" class="form-label">Yangi parolni tasdiqlash</label>
                                        <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Saqlash
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <script>
        // Tab o'zgartirish
        document.addEventListener('DOMContentLoaded', function() {
            const tabLinks = document.querySelectorAll('.tab-link');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // URL dan tab parametrini olish
                    const url = new URL(this.href);
                    const tab = url.searchParams.get('tab');
                    
                    // Barcha tablarni yashirish
                    tabContents.forEach(content => {
                        content.classList.remove('active');
                    });
                    
                    // Tanlangan tabni ko'rsatish
                    document.getElementById(tab + '-tab').classList.add('active');
                    
                    // Barcha tab linklar uchun active klassini o'chirish
                    tabLinks.forEach(link => {
                        link.classList.remove('active');
                    });
                    
                    // Tanlangan tab linkiga active klassini qo'shish
                    this.classList.add('active');
                });
            });
        });
    </script>
</body>
</html>