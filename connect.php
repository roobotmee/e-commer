<?php
// Include configuration
require_once 'config.php';

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    // Log error
    error_log("Database connection failed: " . $conn->connect_error);
    
    // Show user-friendly error
    die("Serverga ulanishda xatolik yuz berdi. Iltimos, keyinroq qayta urinib ko'ring.");
}

// Set character set
$conn->set_charset("utf8mb4");

// Function to sanitize input data
function sanitize($conn, $data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize($conn, $value);
        }
        return $data;
    }
    return $conn->real_escape_string(trim($data));
}

// Function to generate CSRF token
function generate_csrf_token() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

// Function to verify CSRF token
function verify_csrf_token($token) {
    if (!isset($_SESSION[CSRF_TOKEN_NAME]) || $token !== $_SESSION[CSRF_TOKEN_NAME]) {
        return false;
    }
    return true;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
