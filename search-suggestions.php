<?php
require_once __DIR__ . '/lib/functions.php';

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';
if (empty($query)) {
    echo json_encode([]);
    exit;
}

$params = [
    'title' => $query,
    'limit' => 5, // Giới hạn 5 gợi ý
    'includes' => ['cover_art', 'author', 'artist'],
    'contentRating' => R18 ? ['safe', 'suggestive', 'erotica', 'pornographic'] : ['safe', 'suggestive'],
];
$data = callMangadexApi('/manga', $params);

if (!$data || !isset($data['data'])) {
    echo json_encode([]);
    exit;
}

$results = array_map(function ($item) {
    $coverArt = null;
    foreach ($item['relationships'] as $rel) {
        if ($rel['type'] === 'cover_art') {
            $coverArt = $rel['attributes']['fileName'];
            break;
        }
    }
    $title = $item['attributes']['title']['vi'] ?? $item['attributes']['title']['en'] ?? 'Unknown';
    return [
        'id' => $item['id'],
        'title' => $title,
        'cover' => COVER_BASE_URL . '/' . $item['id'] . '/' . $coverArt . '.256.jpg',
        'status' => $item['attributes']['status'] ?? 'ongoing'
    ];
}, $data['data']);

echo json_encode($results);