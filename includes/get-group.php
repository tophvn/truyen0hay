<?php
require_once __DIR__ . '/../lib/functions.php';
header('Content-Type: application/json');

$group_id = $_GET['id'] ?? '';
if (!$group_id) {
    echo json_encode(['group' => null]);
    exit;
}

// Hàm lấy thông tin chi tiết nhóm dịch
function getGroup($group_id) {
    $params = [
        'includes' => ['leader'],
    ];

    $data = callMangadexApi("/group/{$group_id}", $params);
    if (!$data || !isset($data['data'])) {
        return null;
    }

    return parseGroup($data['data']);
}

// Hàm parse nhóm dịch (giữ nguyên từ groups.php)
function parseGroup($data) {
    $id = $data['id'];
    $attributes = $data['attributes'];
    $name = $attributes['name'] ?? 'Unknown';
    $description = $attributes['description'] ?? '';
    $website = $attributes['website'] ?? '';
    $discord = $attributes['discord'] ?? '';
    $email = $attributes['contactEmail'] ?? '';
    $twitter = $attributes['twitter'] ?? '';
    $leader = null;
    $language = $attributes['focusedLanguages'] ?? [];

    foreach ($data['relationships'] as $rel) {
        if ($rel['type'] === 'leader') {
            $leader = [
                'id' => $rel['id'],
                'username' => $rel['attributes']['username'] ?? 'Unknown'
            ];
            break;
        }
    }

    return [
        'id' => $id,
        'name' => $name,
        'description' => $description,
        'website' => $website,
        'discord' => $discord,
        'email' => $email,
        'twitter' => $twitter,
        'language' => $language,
        'leader' => $leader
    ];
}

$group = getGroup($group_id);
echo json_encode(['group' => $group]);
?>