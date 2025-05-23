<?php
require_once __DIR__ . '/../lib/functions.php';
header('Content-Type: application/json');

$query = $_GET['q'] ?? '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 32; // Mặc định 32, nhưng có thể thay đổi qua query
$offset = 0;

$groupsData = searchGroups($query, $limit, $offset);
$groups = $groupsData['groups'] ?? [];

echo json_encode(['groups' => $groups]);
?>