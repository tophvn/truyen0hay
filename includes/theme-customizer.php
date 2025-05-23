<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = isset($_POST['mode']) ? $_POST['mode'] : 'system';
        $_SESSION['mode'] = $mode;
    
    // Trả về JSON để JavaScript xử lý
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'mode' => $mode]);
    exit;
}
?>