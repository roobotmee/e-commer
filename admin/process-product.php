<?php
// Sessiyani boshlash
session_start();

// Foydalanuvchi tizimga kirganligini tekshirish
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Forma yuborilganligini tekshirish
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: products.php');
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

// Ma'lumotlarni tozalash uchun funksiya
function sanitize($conn, $data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize($conn, $value);
        }
        return $data;
    }
    return $conn->real_escape_string(trim($data));
}

// Fayl yuklash uchun funksiya
function uploadFile($file, $target_dir = '../uploads/products/') {
    // Papka mavjud bo'lmasa yaratish
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $target_file = $target_dir . basename($file["name"]);
    $upload_ok = 1;
    $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Noyob fayl nomi yaratish
    $filename = uniqid() . '.' . $image_file_type;
    $target_file = $target_dir . $filename;
    
    // Fayl haqiqatan ham rasm ekanligini tekshirish
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return [
            'success' => false,
            'message' => "Fayl rasm emas."
        ];
    }
    
    // Fayl hajmini tekshirish (5MB maksimal)
    if ($file["size"] > 5 * 1024 * 1024) {
        return [
            'success' => false,
            'message' => "Kechirasiz, fayl hajmi juda katta. Maksimal hajm 5MB."
        ];
    }
    
    // Faqat ma'lum formatdagi fayllarni qabul qilish
    if ($image_file_type != "jpg" && $image_file_type != "png" && $image_file_type != "jpeg" && $image_file_type != "gif") {
        return [
            'success' => false,
            'message' => "Kechirasiz, faqat JPG, JPEG, PNG va GIF formatidagi fayllar qabul qilinadi."
        ];
    }
    
    // Faylni yuklashga harakat qilish
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return [
            'success' => true,
            'file_path' => str_replace('../', '', $target_file)
        ];
    } else {
        return [
            'success' => false,
            'message' => "Kechirasiz, faylni yuklashda xatolik yuz berdi."
        ];
    }
}

// Forma ma'lumotlarini olish
$name = sanitize($conn, $_POST['name']);
$category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
$sku = sanitize($conn, $_POST['sku']);
$description = sanitize($conn, $_POST['description']);
$price = !empty($_POST['price']) ? floatval($_POST['price']) : 0;
$old_price = !empty($_POST['old_price']) ? floatval($_POST['old_price']) : 0;
$discount = !empty($_POST['discount']) ? intval($_POST['discount']) : 0;
$stock = !empty($_POST['stock']) ? intval($_POST['stock']) : 0;
$weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : 0;
$dimensions = sanitize($conn, $_POST['dimensions']);
$status = isset($_POST['status']) ? 1 : 0;
$featured = isset($_POST['featured']) ? 1 : 0;
$is_new = isset($_POST['is_new']) ? 1 : 0;
$sale = isset($_POST['sale']) ? 1 : 0;

// Xususiyatlar va variantlarni olish
$spec_names = isset($_POST['spec_names']) ? $_POST['spec_names'] : [];
$spec_values = isset($_POST['spec_values']) ? $_POST['spec_values'] : [];
$variant_names = isset($_POST['variant_names']) ? $_POST['variant_names'] : [];
$variant_values = isset($_POST['variant_values']) ? $_POST['variant_values'] : [];

// Xususiyatlarni formatlash
$specifications = [];
for ($i = 0; $i < count($spec_names); $i++) {
    if (!empty($spec_names[$i]) && !empty($spec_values[$i])) {
        $specifications[] = [
            'name' => sanitize($conn, $spec_names[$i]),
            'value' => sanitize($conn, $spec_values[$i])
        ];
    }
}

// Variantlarni formatlash
$variants = [];
for ($i = 0; $i < count($variant_names); $i++) {
    if (!empty($variant_names[$i]) && !empty($variant_values[$i])) {
        $variants[] = [
            'name' => sanitize($conn, $variant_names[$i]),
            'value' => sanitize($conn, $variant_values[$i])
        ];
    }
}

// Variantlarni JSON formatiga o'tkazish
$variants_json = !empty($variants) ? json_encode($variants) : null;

// Asosiy rasmni yuklash
$image_path = '';
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $upload_result = uploadFile($_FILES['image']);
    if ($upload_result['success']) {
        $image_path = $upload_result['file_path'];
    } else {
        $_SESSION['error_message'] = $upload_result['message'];
        header('Location: add-product.php');
        exit;
    }
}

// Mahsulotni ma'lumotlar bazasiga qo'shish
$query = "INSERT INTO products (name, category_id, sku, description, price, old_price, discount, stock, weight, dimensions, status, featured, is_new, sale, image, variants, created_at) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($query);
$stmt->bind_param("sissddiiissiiiss", $name, $category_id, $sku, $description, $price, $old_price, $discount, $stock, $weight, $dimensions, $status, $featured, $is_new, $sale, $image_path, $variants_json);

if ($stmt->execute()) {
    $product_id = $conn->insert_id;
    
    // Xususiyatlarni saqlash
    if (!empty($specifications)) {
        $spec_query = "INSERT INTO product_specifications (product_id, name, value) VALUES (?, ?, ?)";
        $spec_stmt = $conn->prepare($spec_query);
        
        foreach ($specifications as $spec) {
            $spec_stmt->bind_param("iss", $product_id, $spec['name'], $spec['value']);
            $spec_stmt->execute();
        }
        
        $spec_stmt->close();
    }
    
    // Qo'shimcha rasmlarni yuklash
    if (isset($_FILES['additional_images'])) {
        $additional_images = $_FILES['additional_images'];
        $images_query = "INSERT INTO product_images (product_id, image_url) VALUES (?, ?)";
        $images_stmt = $conn->prepare($images_query);
        
        for ($i = 0; $i < count($additional_images['name']); $i++) {
            if ($additional_images['error'][$i] == 0) {
                $file = [
                    'name' => $additional_images['name'][$i],
                    'type' => $additional_images['type'][$i],
                    'tmp_name' => $additional_images['tmp_name'][$i],
                    'error' => $additional_images['error'][$i],
                    'size' => $additional_images['size'][$i]
                ];
                
                $upload_result = uploadFile($file);
                if ($upload_result['success']) {
                    $image_url = $upload_result['file_path'];
                    $images_stmt->bind_param("is", $product_id, $image_url);
                    $images_stmt->execute();
                }
            }
        }
        
        $images_stmt->close();
    }
    
    $_SESSION['success_message'] = "Mahsulot muvaffaqiyatli qo'shildi.";
    header('Location: products.php');
    exit;
} else {
    $_SESSION['error_message'] = "Mahsulotni qo'shishda xatolik yuz berdi: " . $stmt->error;
    header('Location: add-product.php');
    exit;
}

// Ulanishni yopish
$stmt->close();
$conn->close();