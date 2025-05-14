<?php
// Sessiyani boshlash
session_start();

// Barcha sessiya o'zgaruvchilarini tozalash
$_SESSION = array();

// Sessiyani yo'q qilish
session_destroy();

// Login sahifasiga yo'naltirish
header('Location: login.php');
exit;
?>