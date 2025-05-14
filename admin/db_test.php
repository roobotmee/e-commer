<?php
// Ma'lumotlar bazasi ulanishini tekshirish
require_once '../config.php';

echo "Ma'lumotlar bazasiga ulanishni tekshirish...<br>";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Ulanish xatosi: " . $conn->connect_error);
    }
    
    echo "Ma'lumotlar bazasiga muvaffaqiyatli ulandi!<br>";
    
    // Jadvallarni tekshirish
    echo "<h3>Jadvallarni tekshirish:</h3>";
    
    $tables = ['admins', 'products', 'categories', 'orders', 'users'];
    
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "✅ <strong>$table</strong> jadvali mavjud.<br>";
            
            // Jadval ma'lumotlarini tekshirish
            $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
            if ($count_result) {
                $row = $count_result->fetch_assoc();
                echo "&nbsp;&nbsp;&nbsp;- Jadvalda " . $row['count'] . " ta yozuv mavjud.<br>";
            } else {
                echo "&nbsp;&nbsp;&nbsp;- Jadval ma'lumotlarini tekshirishda xatolik: " . $conn->error . "<br>";
            }
        } else {
            echo "❌ <strong>$table</strong> jadvali mavjud emas.<br>";
        }
    }
    
    // Ma'lumotlar bazasi konfiguratsiyasini tekshirish
    echo "<h3>Ma'lumotlar bazasi konfiguratsiyasi:</h3>";
    echo "Host: " . DB_HOST . "<br>";
    echo "Foydalanuvchi: " . DB_USER . "<br>";
    echo "Ma'lumotlar bazasi nomi: " . DB_NAME . "<br>";
    
    $conn->close();
} catch (Exception $e) {
    echo "Xatolik: " . $e->getMessage();
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
    
    h3 {
        margin-top: 20px;
        color: #333;
        border-bottom: 1px solid #ddd;
        padding-bottom: 5px;
    }
    
    pre {
        background-color: #f9f9f9;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        overflow: auto;
    }
</style>