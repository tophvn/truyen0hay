<?php
session_start(); 

// Lấy URL trang trước đó từ HTTP_REFERER (nếu có)
$referer = $_SERVER['HTTP_REFERER'] ?? '../../index.php'; // Mặc định về index nếu không có referer

$_SESSION = [];
session_destroy();

// Chuyển hướng về trang trước đó
header("Location: $referer");
exit();
?>