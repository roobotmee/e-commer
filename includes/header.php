<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['user_name'] : '';

// Get cart count
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = count($_SESSION['cart']);
}

// Get favorites count
$favorites_count = 0;
if (isset($_SESSION['favorites']) && is_array($_SESSION['favorites'])) {
    $favorites_count = count($_SESSION['favorites']);
}

// Get current page for active menu
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- No visible content in header.php as it's included in other files -->
