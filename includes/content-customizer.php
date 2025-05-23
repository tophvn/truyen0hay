<?php
session_start();
header('Content-Type: application/json');

if (isset($_POST['r18'])) {
    $r18 = filter_var($_POST['r18'], FILTER_VALIDATE_BOOLEAN);
    $_SESSION['r18'] = $r18;
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'No r18 value provided']);
}
exit;