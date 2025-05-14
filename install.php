<?php
// Xatoliklarni ko'rsatish
error_reporting(E_ALL);
ini_set('display_errors', 1);

// O'rnatish bosqichlarini kuzatish
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$error = '';
$success = '';

// Ma'lumotlar bazasi ma'lumotlarini saqlash
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'sossmm';

// Admin foydalanuvchisi ma'lumotlari
$admin_username = 'admin';
$admin_password = '';
$admin_email = '';

// Forma yuborilganligini tekshirish
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1-bosqich: Ma'lumotlar bazasi ma'lumotlarini olish
    if ($step === 1 && isset($_POST['db_host'])) {
        $db_host = trim($_POST['db_host']);
        $db_user = trim($_POST['db_user']);
        $db_pass = $_POST['db_pass'];
        $db_name = trim($_POST['db_name']);
        
        // Ma'lumotlar bazasiga ulanishni tekshirish
        try {
            $conn = new mysqli($db_host, $db_user, $db_pass);
            
            if ($conn->connect_error) {
                throw new Exception("Ma'lumotlar bazasiga ulanib bo'lmadi: " . $conn->connect_error);
            }
            
            // Ma'lumotlar bazasini yaratish
            $sql = "CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            if (!$conn->query($sql)) {
                throw new Exception("Ma'lumotlar bazasini yaratib bo'lmadi: " . $conn->error);
            }
            
            // Ma'lumotlar bazasini tanlash
            $conn->select_db($db_name);
            
            // config.php faylini yaratish
            $config_content = "<?php
// Ma'lumotlar bazasi sozlamalari
define('DB_HOST', '$db_host');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');
define('DB_NAME', '$db_name');

// Sayt sozlamalari
define('SITE_NAME', 'SossMM');
define('SITE_URL', 'http://' . \$_SERVER['HTTP_HOST'] . dirname(\$_SERVER['PHP_SELF']));
define('ADMIN_EMAIL', 'admin@sossmm.uz');

// To'lov sozlamalari
define('PAYMENT_CURRENCY', 'UZS');
define('PAYMENT_METHODS', ['cash', 'card', 'installment']);

// Yetkazib berish sozlamalari
define('FREE_SHIPPING_THRESHOLD', 100000); // 100,000 so'mdan yuqori buyurtmalar uchun bepul yetkazib berish

// Fayl yuklash sozlamalari
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Sessiya sozlamalari
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // HTTPS ishlatilganda 1 ga o'zgartiring

// Xatoliklarni ko'rsatish
error_reporting(E_ALL);
ini_set('display_errors', 0); // Ishlab chiqarishda 0 ga o'zgartiring
ini_set('log_errors', 1);
ini_set('error_log', 'error.log');

// Vaqt mintaqasi
date_default_timezone_set('Asia/Tashkent');

// Xavfsizlik
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_HASH_COST', 12);

// Google Sheets API sozlamalari (muddatli to'lovlar uchun)
define('GOOGLE_SHEETS_API_KEY', '');
define('GOOGLE_SHEETS_SPREADSHEET_ID', '');
";
            
            // config.php faylini yozish
            if (file_put_contents('config.php', $config_content) === false) {
                throw new Exception("config.php faylini yaratib bo'lmadi. Papka yozish huquqlariga ega ekanligini tekshiring.");
            }
            
            // Keyingi bosqichga o'tish
            $step = 2;
            $success = "Ma'lumotlar bazasi muvaffaqiyatli yaratildi va config.php fayli saqlandi.";
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
    
    // 2-bosqich: Jadvallarni yaratish
    else if ($step === 2 && isset($_POST['create_tables'])) {
        try {
            // Konfiguratsiya faylini qo'shish
            if (file_exists('config.php')) {
                require_once 'config.php';
            } else {
                throw new Exception("config.php fayli topilmadi. 1-bosqichni qaytadan bajaring.");
            }
            
            // Ma'lumotlar bazasiga ulanish
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($conn->connect_error) {
                throw new Exception("Ma'lumotlar bazasiga ulanib bo'lmadi: " . $conn->connect_error);
            }
            
            // Jadvallarni yaratish
            $tables = [
                // Adminlar jadvali
                "CREATE TABLE IF NOT EXISTS `admins` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `username` varchar(50) NOT NULL,
                    `password` varchar(255) NOT NULL,
                    `email` varchar(100) NOT NULL,
                    `full_name` varchar(100) DEFAULT NULL,
                    `role` enum('admin','manager','editor') NOT NULL DEFAULT 'admin',
                    `status` tinyint(1) NOT NULL DEFAULT 1,
                    `last_login` datetime DEFAULT NULL,
                    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `username` (`username`),
                    UNIQUE KEY `email` (`email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                // Foydalanuvchilar jadvali
                "CREATE TABLE IF NOT EXISTS `users` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `username` varchar(50) NOT NULL,
                    `password` varchar(255) NOT NULL,
                    `email` varchar(100) NOT NULL,
                    `full_name` varchar(100) DEFAULT NULL,
                    `phone` varchar(20) DEFAULT NULL,
                    `address` text DEFAULT NULL,
                    `status` tinyint(1) NOT NULL DEFAULT 1,
                    `last_login` datetime DEFAULT NULL,
                    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `username` (`username`),
                    UNIQUE KEY `email` (`email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                // Kategoriyalar jadvali
                "CREATE TABLE IF NOT EXISTS `categories` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `parent_id` int(11) DEFAULT NULL,
                    `name` varchar(100) NOT NULL,
                    `slug` varchar(100) NOT NULL,
                    `description` text DEFAULT NULL,
                    `image` varchar(255) DEFAULT NULL,
                    `status` tinyint(1) NOT NULL DEFAULT 1,
                    `sort_order` int(11) NOT NULL DEFAULT 0,
                    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `slug` (`slug`),
                    KEY `parent_id` (`parent_id`),
                    CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                // Mahsulotlar jadvali
                "CREATE TABLE IF NOT EXISTS `products` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `category_id` int(11) DEFAULT NULL,
                    `name` varchar(255) NOT NULL,
                    `slug` varchar(255) NOT NULL,
                    `sku` varchar(50) DEFAULT NULL,
                    `description` text DEFAULT NULL,
                    `price` decimal(15,2) NOT NULL DEFAULT 0.00,
                    `old_price` decimal(15,2) DEFAULT NULL,
                    `discount` int(11) DEFAULT NULL,
                    `stock` int(11) NOT NULL DEFAULT 0,
                    `weight` decimal(10,2) DEFAULT NULL,
                    `dimensions` varchar(50) DEFAULT NULL,
                    `image` varchar(255) DEFAULT NULL,
                    `variants` text DEFAULT NULL,
                    `status` tinyint(1) NOT NULL DEFAULT 1,
                    `featured` tinyint(1) NOT NULL DEFAULT 0,
                    `is_new` tinyint(1) NOT NULL DEFAULT 0,
                    `sale` tinyint(1) NOT NULL DEFAULT 0,
                    `views` int(11) NOT NULL DEFAULT 0,
                    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `slug` (`slug`),
                    KEY `category_id` (`category_id`),
                    CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                // Mahsulot rasmlari jadvali
                "CREATE TABLE IF NOT EXISTS `product_images` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `product_id` int(11) NOT NULL,
                    `image_url` varchar(255) NOT NULL,
                    `sort_order` int(11) NOT NULL DEFAULT 0,
                    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `product_id` (`product_id`),
                    CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                // Mahsulot xususiyatlari jadvali
                "CREATE TABLE IF NOT EXISTS `product_specifications` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `product_id` int(11) NOT NULL,
                    `name` varchar(100) NOT NULL,
                    `value` varchar(255) NOT NULL,
                    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `product_id` (`product_id`),
                    CONSTRAINT `product_specifications_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                // Buyurtmalar jadvali
                "CREATE TABLE IF NOT EXISTS `orders` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `user_id` int(11) DEFAULT NULL,
                    `order_number` varchar(50) NOT NULL,
                    `full_name` varchar(100) NOT NULL,
                    `email` varchar(100) DEFAULT NULL,
                    `phone` varchar(20) NOT NULL,
                    `address` text NOT NULL,
                    `city` varchar(100) DEFAULT NULL,
                    `postal_code` varchar(20) DEFAULT NULL,
                    `payment_method` varchar(50) NOT NULL,
                    `payment_status` enum('pending','paid','failed') NOT NULL DEFAULT 'pending',
                    `shipping_method` varchar(50) DEFAULT NULL,
                    `shipping_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
                    `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
                    `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
                    `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
                    `notes` text DEFAULT NULL,
                    `status` enum('pending','processing','shipped','delivered','completed','cancelled') NOT NULL DEFAULT 'pending',
                    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `order_number` (`order_number`),
                    KEY `user_id` (`user_id`),
                    CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                // Buyurtma elementlari jadvali
                "CREATE TABLE IF NOT EXISTS `order_items` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `order_id` int(11) NOT NULL,
                    `product_id` int(11) DEFAULT NULL,
                    `product_name` varchar(255) NOT NULL,
                    `product_sku` varchar(50) DEFAULT NULL,
                    `product_options` text DEFAULT NULL,
                    `quantity` int(11) NOT NULL DEFAULT 1,
                    `price` decimal(15,2) NOT NULL DEFAULT 0.00,
                    `total` decimal(15,2) NOT NULL DEFAULT 0.00,
                    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `order_id` (`order_id`),
                    KEY `product_id` (`product_id`),
                    CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                // Savatcha jadvali
                "CREATE TABLE IF NOT EXISTS `cart` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `user_id` int(11) DEFAULT NULL,
                    `session_id` varchar(100) NOT NULL,
                    `product_id` int(11) NOT NULL,
                    `quantity` int(11) NOT NULL DEFAULT 1,
                    `options` text DEFAULT NULL,
                    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `user_id` (`user_id`),
                    KEY `product_id` (`product_id`),
                    CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                // Sozlamalar jadvali
                "CREATE TABLE IF NOT EXISTS `settings` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `setting_key` varchar(100) NOT NULL,
                    `setting_value` text DEFAULT NULL,
                    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `setting_key` (`setting_key`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            ];
            
            // Jadvallarni yaratish
            foreach ($tables as $sql) {
                if (!$conn->query($sql)) {
                    throw new Exception("Jadval yaratishda xatolik: " . $conn->error);
                }
            }
            
            // Keyingi bosqichga o'tish
            $step = 3;
            $success = "Barcha jadvallar muvaffaqiyatli yaratildi.";
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
    
    // 3-bosqich: Admin foydalanuvchisini yaratish
    else if ($step === 3 && isset($_POST['admin_username'])) {
        try {
            // Konfiguratsiya faylini qo'shish
            if (file_exists('config.php')) {
                require_once 'config.php';
            } else {
                throw new Exception("config.php fayli topilmadi. 1-bosqichni qaytadan bajaring.");
            }
            
            // Ma'lumotlar bazasiga ulanish
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($conn->connect_error) {
                throw new Exception("Ma'lumotlar bazasiga ulanib bo'lmadi: " . $conn->connect_error);
            }
            
            // Admin ma'lumotlarini olish
            $admin_username = trim($_POST['admin_username']);
            $admin_password = $_POST['admin_password'];
            $admin_email = trim($_POST['admin_email']);
            $admin_full_name = trim($_POST['admin_full_name']);
            
            // Ma'lumotlarni tekshirish
            if (empty($admin_username) || empty($admin_password) || empty($admin_email)) {
                throw new Exception("Barcha majburiy maydonlarni to'ldiring.");
            }
            
            if (strlen($admin_password) < 6) {
                throw new Exception("Parol kamida 6 ta belgidan iborat bo'lishi kerak.");
            }
            
            // Parolni hashlash
            $hashed_password = password_hash($admin_password, PASSWORD_BCRYPT);
            
            // Admin foydalanuvchisini yaratish
            $sql = "INSERT INTO `admins` (`username`, `password`, `email`, `full_name`, `role`, `status`) 
                    VALUES (?, ?, ?, ?, 'admin', 1)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $admin_username, $hashed_password, $admin_email, $admin_full_name);
            
            if (!$stmt->execute()) {
                throw new Exception("Admin foydalanuvchisini yaratishda xatolik: " . $stmt->error);
            }
            
            // Asosiy sozlamalarni qo'shish
            $settings = [
                ['site_name', 'SossMM'],
                ['site_description', 'SossMM - Onlayn do\'kon'],
                ['site_email', $admin_email],
                ['site_phone', '+998 90 123 45 67'],
                ['site_address', 'Toshkent shahri, O\'zbekiston'],
                ['currency', 'UZS'],
                ['currency_symbol', 'so\'m'],
                ['tax_rate', '0'],
                ['shipping_cost', '15000'],
                ['free_shipping_threshold', '100000']
            ];
            
            $sql = "INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            
            foreach ($settings as $setting) {
                $stmt->bind_param("ss", $setting[0], $setting[1]);
                $stmt->execute();
            }
            
            // .htaccess faylini yaratish
            $htaccess_content = "# Xavfsizlik
<IfModule mod_headers.c>
    Header set X-Content-Type-Options \"nosniff\"
    Header set X-XSS-Protection \"1; mode=block\"
    Header set X-Frame-Options \"SAMEORIGIN\"
    Header set Referrer-Policy \"strict-origin-when-cross-origin\"
</IfModule>

# PHP sozlamalari
<IfModule mod_php7.c>
    php_value upload_max_filesize 10M
    php_value post_max_size 10M
    php_value max_execution_time 300
    php_value max_input_time 300
</IfModule>

# URL qayta yo'naltirish
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Asosiy URL qayta yo'naltirish
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^product/([a-zA-Z0-9-]+)$ product.php?slug=$1 [L,QSA]
    RewriteRule ^category/([a-zA-Z0-9-]+)$ category.php?slug=$1 [L,QSA]
    RewriteRule ^page/([a-zA-Z0-9-]+)$ page.php?slug=$1 [L,QSA]
    
    # Admin panelini himoya qilish
    RewriteCond %{REQUEST_URI} ^/admin/
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^admin/(.*)$ admin/index.php [L]
</IfModule>

# Katalogni ko'rishni o'chirish
Options -Indexes

# Maxfiy fayllarni himoya qilish
<FilesMatch \"^\\.(htaccess|htpasswd|ini|log|sh|inc|bak)$\">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# PHP fayllarini himoya qilish
<FilesMatch \"(config|connect)\\.php$\">
    Order Allow,Deny
    Deny from all
</FilesMatch>
";
            
            // .htaccess faylini yozish
            if (file_put_contents('.htaccess', $htaccess_content) === false) {
                throw new Exception(".htaccess faylini yaratib bo'lmadi. Papka yozish huquqlariga ega ekanligini tekshiring.");
            }
            
            // O'rnatish tugallandi
            $step = 4;
            $success = "Admin foydalanuvchisi muvaffaqiyatli yaratildi va o'rnatish tugallandi.";
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SossMM - O'rnatish</title>
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
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .subtitle {
            color: var(--text-light);
        }

        .card {
            background-color: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 30px;
            margin-bottom: 20px;
        }

        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }

        .steps::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: var(--border-color);
            z-index: 1;
        }

        .step {
            position: relative;
            z-index: 2;
            background-color: var(--card-bg);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            border: 2px solid var(--border-color);
        }

        .step.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .step.completed {
            background-color: var(--success-color);
            color: white;
            border-color: var(--success-color);
        }

        .step-label {
            position: absolute;
            top: 35px;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
            font-size: 12px;
            color: var(--text-light);
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
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .btn {
            padding: 12px 20px;
            border-radius: var(--radius);
            font-size: 16px;
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
            background-color: var(--background-color);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background-color: var(--border-color);
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
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

        .requirements {
            margin-bottom: 20px;
        }

        .requirement {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .requirement:last-child {
            border-bottom: none;
        }

        .requirement-status {
            margin-left: auto;
            font-weight: 500;
        }

        .status-success {
            color: var(--success-color);
        }

        .status-error {
            color: var(--danger-color);
        }

        .completed-message {
            text-align: center;
            margin-bottom: 30px;
        }

        .completed-icon {
            font-size: 64px;
            color: var(--success-color);
            margin-bottom: 20px;
        }

        .note {
            font-size: 14px;
            color: var(--text-light);
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .form-actions {
                flex-direction: column;
                gap: 10px;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <i class="fas fa-store"></i>
                <span>SossMM</span>
            </div>
            <p class="subtitle">O'rnatish ustasi</p>
        </div>
        
        <div class="card">
            <div class="steps">
                <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
                    1
                    <span class="step-label">Ma'lumotlar bazasi</span>
                </div>
                <div class="step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
                    2
                    <span class="step-label">Jadvallar</span>
                </div>
                <div class="step <?php echo $step >= 3 ? 'active' : ''; ?> <?php echo $step > 3 ? 'completed' : ''; ?>">
                    3
                    <span class="step-label">Admin</span>
                </div>
                <div class="step <?php echo $step >= 4 ? 'active' : ''; ?>">
                    4
                    <span class="step-label">Tugallash</span>
                </div>
            </div>
            
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($step === 1): ?>
            <!-- 1-bosqich: Ma'lumotlar bazasi ma'lumotlari -->
            <h2>Ma'lumotlar bazasi sozlamalari</h2>
            <p>SossMM tizimini o'rnatish uchun ma'lumotlar bazasi ma'lumotlarini kiriting.</p>
            
            <form method="post" action="install.php?step=1">
                <div class="form-group">
                    <label for="db_host" class="form-label">Ma'lumotlar bazasi serveri</label>
                    <input type="text" id="db_host" name="db_host" class="form-control" value="<?php echo htmlspecialchars($db_host); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="db_user" class="form-label">Foydalanuvchi nomi</label>
                    <input type="text" id="db_user" name="db_user" class="form-control" value="<?php echo htmlspecialchars($db_user); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="db_pass" class="form-label">Parol</label>
                    <input type="password" id="db_pass" name="db_pass" class="form-control" value="<?php echo htmlspecialchars($db_pass); ?>">
                    <p class="note">Agar parol bo'lmasa, bo'sh qoldiring.</p>
                </div>
                
                <div class="form-group">
                    <label for="db_name" class="form-label">Ma'lumotlar bazasi nomi</label>
                    <input type="text" id="db_name" name="db_name" class="form-control" value="<?php echo htmlspecialchars($db_name); ?>" required>
                    <p class="note">Agar mavjud bo'lmasa, avtomatik yaratiladi.</p>
                </div>
                
                <div class="form-actions">
                    <div></div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Davom etish
                    </button>
                </div>
            </form>
            
            <?php elseif ($step === 2): ?>
            <!-- 2-bosqich: Jadvallarni yaratish -->
            <h2>Ma'lumotlar bazasi jadvallarini yaratish</h2>
            <p>SossMM tizimi uchun kerakli jadvallarni yaratish.</p>
            
            <div class="requirements">
                <div class="requirement">
                    <span>Ma'lumotlar bazasiga ulanish</span>
                    <span class="requirement-status status-success">
                        <i class="fas fa-check-circle"></i> Muvaffaqiyatli
                    </span>
                </div>
                <div class="requirement">
                    <span>config.php fayli yaratildi</span>
                    <span class="requirement-status status-success">
                        <i class="fas fa-check-circle"></i> Muvaffaqiyatli
                    </span>
                </div>
            </div>
            
            <form method="post" action="install.php?step=2">
                <input type="hidden" name="create_tables" value="1">
                
                <div class="form-actions">
                    <a href="install.php?step=1" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Orqaga
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-database"></i> Jadvallarni yaratish
                    </button>
                </div>
            </form>
            
            <?php elseif ($step === 3): ?>
            <!-- 3-bosqich: Admin foydalanuvchisini yaratish -->
            <h2>Admin foydalanuvchisini yaratish</h2>
            <p>SossMM tizimiga kirish uchun admin foydalanuvchisini yarating.</p>
            
            <form method="post" action="install.php?step=3">
                <div class="form-group">
                    <label for="admin_username" class="form-label">Foydalanuvchi nomi *</label>
                    <input type="text" id="admin_username" name="admin_username" class="form-control" value="<?php echo htmlspecialchars($admin_username); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_password" class="form-label">Parol *</label>
                    <input type="password" id="admin_password" name="admin_password" class="form-control" required>
                    <p class="note">Kamida 6 ta belgidan iborat bo'lishi kerak.</p>
                </div>
                
                <div class="form-group">
                    <label for="admin_email" class="form-label">Email *</label>
                    <input type="email" id="admin_email" name="admin_email" class="form-control" value="<?php echo htmlspecialchars($admin_email); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_full_name" class="form-label">To'liq ism</label>
                    <input type="text" id="admin_full_name" name="admin_full_name" class="form-control">
                </div>
                
                <div class="form-actions">
                    <a href="install.php?step=2" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Orqaga
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Admin yaratish
                    </button>
                </div>
            </form>
            
            <?php elseif ($step === 4): ?>
            <!-- 4-bosqich: O'rnatish tugallandi -->
            <div class="completed-message">
                <div class="completed-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2>O'rnatish muvaffaqiyatli tugallandi!</h2>
                <p>SossMM tizimi muvaffaqiyatli o'rnatildi. Endi admin paneliga kirib, saytingizni sozlashingiz mumkin.</p>
            </div>
            
            <div class="form-actions" style="justify-content: center;">
                <a href="admin/login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Admin paneliga kirish
                </a>
            </div>
            
            <div class="note" style="text-align: center; margin-top: 20px;">
                <p><strong>Muhim:</strong> Xavfsizlik maqsadida <code>install.php</code> faylini o'chirib tashlang.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>