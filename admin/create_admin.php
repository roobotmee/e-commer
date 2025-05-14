<?php
// Admin foydalanuvchisini yaratish
require_once '../config.php';

// Admin ma'lumotlari
$admin_username = 'admin';
$admin_email = 'admin@example.com';
$admin_password = 'admin123'; // Bu parolni o'zgartiring!

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Ulanish xatosi: " . $conn->connect_error);
    }
    
    echo "<h2>Admin foydalanuvchisini yaratish</h2>";
    
    // Admins jadvalini tekshirish
    $result = $conn->query("SHOW TABLES LIKE 'admins'");
    if ($result->num_rows == 0) {
        // Jadval mavjud emas, uni yaratamiz
        $sql = "CREATE TABLE IF NOT EXISTS `admins` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL,
            `email` varchar(100) NOT NULL,
            `password` varchar(255) NOT NULL,
            `full_name` varchar(100) DEFAULT NULL,
            `role` varchar(20) DEFAULT 'admin',
            `status` tinyint(1) DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `email` (`email`),
            UNIQUE KEY `username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✅ Admins jadvali muvaffaqiyatli yaratildi.</p>";
        } else {
            die("<p style='color: red;'>❌ Admins jadvalini yaratishda xatolik: " . $conn->error . "</p>");
        }
    } else {
        echo "<p>ℹ️ Admins jadvali allaqachon mavjud.</p>";
    }
    
    // Admin foydalanuvchisi mavjudligini tekshirish
    $check_admin = $conn->prepare("SELECT id FROM admins WHERE username = ?");
    $check_admin->bind_param("s", $admin_username);
    $check_admin->execute();
    $admin_result = $check_admin->get_result();
    
    if ($admin_result->num_rows > 0) {
        echo "<p style='color: blue;'>ℹ️ '$admin_username' nomli admin foydalanuvchisi allaqachon mavjud.</p>";
        
        // Parolni yangilash uchun so'rov
        echo "<form method='post' action=''>";
        echo "<p>Mavjud admin foydalanuvchisi parolini yangilashni istaysizmi?</p>";
        echo "<input type='hidden' name='update_password' value='1'>";
        echo "<button type='submit' style='padding: 8px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;'>Parolni yangilash</button>";
        echo "</form>";
        
        // Parolni yangilash
        if (isset($_POST['update_password'])) {
            $hashed_password = password_hash($admin_password, PASSWORD_BCRYPT);
            $update_stmt = $conn->prepare("UPDATE admins SET password = ? WHERE username = ?");
            $update_stmt->bind_param("ss", $hashed_password, $admin_username);
            
            if ($update_stmt->execute()) {
                echo "<p style='color: green;'>✅ Admin foydalanuvchisi paroli muvaffaqiyatli yangilandi.</p>";
                echo "<p><strong>Yangi parol:</strong> " . $admin_password . "</p>";
                echo "<p><strong>Eslatma:</strong> Bu parolni eslab qoling va xavfsizlik maqsadida bu faylni o'chirib tashlang!</p>";
            } else {
                echo "<p style='color: red;'>❌ Admin foydalanuvchisi parolini yangilashda xatolik: " . $update_stmt->error . "</p>";
            }
        }
    } else {
        // Admin foydalanuvchisini yaratish
        $hashed_password = password_hash($admin_password, PASSWORD_BCRYPT);
        
        $stmt = $conn->prepare("INSERT INTO `admins` (`username`, `email`, `password`, `full_name`, `role`) VALUES (?, ?, ?, 'Admin User', 'admin')");
        $stmt->bind_param("sss", $admin_username, $admin_email, $hashed_password);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Admin foydalanuvchisi muvaffaqiyatli yaratildi.</p>";
            echo "<div style='background-color: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 4px; margin: 15px 0;'>";
            echo "<p><strong>Foydalanuvchi nomi:</strong> " . $admin_username . "</p>";
            echo "<p><strong>Parol:</strong> " . $admin_password . "</p>";
            echo "</div>";
            echo "<p><strong>Eslatma:</strong> Bu ma'lumotlarni eslab qoling va xavfsizlik maqsadida bu faylni o'chirib tashlang!</p>";
        } else {
            echo "<p style='color: red;'>❌ Admin foydalanuvchisini yaratishda xatolik: " . $stmt->error . "</p>";
        }
    }
    
    echo "<h3>Admin paneliga kirish</h3>";
    echo "<p>Admin paneliga kirish uchun quyidagi URL manzilidan foydalaning:</p>";
    echo "<p><a href='login.php' target='_blank'>Admin Login</a></p>";
    
    $conn->close();
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Xatolik: " . $e->getMessage() . "</p>";
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        line-height: 1.6;
        margin: 20px;
        padding: 20px;
        background-color: #f5f5f5;
    }
    
    h2, h3 {
        color: #333;
        border-bottom: 1px solid #ddd;
        padding-bottom: 5px;
    }
    
    p {
        margin: 10px 0;
    }
    
    a {
        color: #4CAF50;
        text-decoration: none;
    }
    
    a:hover {
        text-decoration: underline;
    }
</style>