<?php
// Ma'lumotlar bazasi sozlamalari
define('DB_HOST', 'localhost');
define('DB_USER', 'roo393_sos');
define('DB_PASS', 'roo393_sos');
define('DB_NAME', 'roo393_sos');

// Sayt sozlamalari
define('SITE_NAME', 'SossMM');
define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']));
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
