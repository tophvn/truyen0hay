<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

function callMangadexApi($endpoint, $params = [], $retries = 2) {
    $url = API_BASE_URL . $endpoint . '?' . http_build_query($params);
    $attempt = 0;

    while ($attempt <= $retries) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); 
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: Truyen0Hay/1.0 (PHP Client)'
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            return json_decode($response, true);
        }
        $attempt++;
        sleep(1);
    }
    return null;
}

function getLatestChapters($max, $languages, $r18, $offset = 0) {
    $limitPerRequest = 50; // Giới hạn mỗi lần gọi API
    $currentOffset = $offset; 
    $mangaChapters = [];
    $mangaIdsSeen = []; 

    while (count($mangaChapters) < $max) {
        $params = [
            'limit' => $limitPerRequest,
            'offset' => $currentOffset,
            'includes' => ['scanlation_group', 'manga'],
            'contentRating' => $r18 ? ['safe', 'suggestive', 'erotica', 'pornographic'] : ['safe', 'suggestive'],
            'translatedLanguage' => $languages,
            'order' => ['readableAt' => 'desc']
        ];
        $data = callMangadexApi('/chapter', $params);
        if (!$data || !isset($data['data']) || empty($data['data'])) {
            break;
        }

        $chapters = parseChaptersLite($data['data']);
        if (empty($chapters)) {
            break;
        }

        foreach ($chapters as $chapter) {
            $mangaId = $chapter['manga_id'];
            if (!isset($mangaIdsSeen[$mangaId])) {
                $mangaChapters[] = $chapter;
                $mangaIdsSeen[$mangaId] = true;
            }
            if (count($mangaChapters) >= $max) {
                break;
            }
        }
        $currentOffset += $limitPerRequest;
        if (count($chapters) < $limitPerRequest) {
            break;
        }
    }

    if (empty($mangaChapters)) {
        return [];
    }

    $mangaIDs = array_column($mangaChapters, 'manga_id');
    if (empty($mangaIDs)) {
        return [];
    }

    // Gọi API để lấy thông tin manga
    $mangaData = callMangadexApi('/manga', [
        'ids' => $mangaIDs,
        'includes' => ['cover_art', 'author', 'artist'],
        'limit' => count($mangaIDs)
    ]);

    if (!$mangaData || !isset($mangaData['data'])) {
        return $mangaChapters;
    }

    $mangas = array_map('parseManga', $mangaData['data']);
    $mangaMapById = array_column($mangas, null, 'id');

    $result = [];
    foreach ($mangaChapters as $chapter) {
        $mangaId = $chapter['manga_id'];
        if (isset($mangaMapById[$mangaId])) {
            $chapter['manga'] = $mangaMapById[$mangaId];
            $result[] = $chapter;
        }
    }

    return $result;
}

// Phiên bản nhẹ của parseChapters
function parseChaptersLite($data) {
    $chapters = [];
    foreach ($data as $item) {
        $mangaId = null;
        $groups = [];
        foreach ($item['relationships'] as $rel) {
            if ($rel['type'] === 'manga') {
                $mangaId = $rel['id'];
            } elseif ($rel['type'] === 'scanlation_group') {
                $groups[] = [
                    'id' => $rel['id'],
                    'name' => $rel['attributes']['name'] ?? 'No Group'
                ];
            }
        }
        if (!$mangaId) continue;

        $chapters[] = [
            'id' => $item['id'],
            'chapter' => $item['attributes']['chapter'] ?? 'Oneshot',
            'title' => $item['attributes']['title'] ?? '',
            'updatedAt' => $item['attributes']['readableAt'] ?? $item['attributes']['updatedAt'],
            'externalUrl' => $item['attributes']['externalUrl'] ?? null,
            'language' => $item['attributes']['translatedLanguage'],
            'group' => $groups,
            'manga_id' => $mangaId 
        ];
    }
    return $chapters;
}

function parseChapters($data) {
    $chapters = [];
    foreach ($data as $item) {
        $mangaData = null;
        $groups = [];
        foreach ($item['relationships'] as $rel) {
            if ($rel['type'] === 'manga') {
                $mangaData = $rel;
            } elseif ($rel['type'] === 'scanlation_group') {
                $groups[] = [
                    'id' => $rel['id'],
                    'name' => $rel['attributes']['name'] ?? 'No Group'
                ];
            }
        }

        if (!$mangaData) continue;

        $manga = getMangaById($mangaData['id']);
        if (!$manga) continue;

        $chapters[] = [
            'id' => $item['id'],
            'chapter' => $item['attributes']['chapter'] ?? 'Oneshot',
            'title' => $item['attributes']['title'] ?? '',
            'updatedAt' => $item['attributes']['readableAt'] ?? $item['attributes']['updatedAt'],
            'externalUrl' => $item['attributes']['externalUrl'] ?? null,
            'language' => $item['attributes']['translatedLanguage'],
            'group' => $groups,
            'manga' => $manga
        ];
    }
    return $chapters;
}

function getMangaById($id) {
    $params = [
        'includes' => ['cover_art', 'author', 'artist']
    ];
    $data = callMangadexApi("/manga/$id", $params);
    if (!$data || !isset($data['data'])) return null;

    $item = $data['data'];
    $coverArt = null;
    $authors = [];
    $artists = [];
    foreach ($item['relationships'] as $rel) {
        if ($rel['type'] === 'cover_art') {
            $coverArt = $rel['attributes']['fileName'] ?? null;
        } elseif ($rel['type'] === 'author') {
            $authors[] = ['name' => $rel['attributes']['name'] ?? 'Unknown'];
        } elseif ($rel['type'] === 'artist') {
            $artists[] = ['name' => $rel['attributes']['name'] ?? 'Unknown'];
        }
    }

    $titles = $item['attributes']['title'] ?? [];
    $altTitles = $item['attributes']['altTitles'] ?? [];
    $viTitle = null;
    $enTitle = $titles['en'] ?? null;
    foreach ($altTitles as $t) {
        if (isset($t['vi'])) $viTitle = $t['vi'];
        elseif (isset($t['en']) && !$enTitle) $enTitle = $t['en'];
    }
    $title = $viTitle ?? $enTitle ?? $titles[array_key_first($titles)] ?? 'Unknown';

    $description = $item['attributes']['description']['vi'] ?? $item['attributes']['description']['en'] ?? '';
    return [
        'id' => $item['id'],
        'title' => $title,
        'cover' => $coverArt,
        'author' => $authors,
        'artist' => $artists,
        'description' => ['content' => $description],
        'contentRating' => $item['attributes']['contentRating'] ?? 'safe',
        'status' => $item['attributes']['status'] ?? 'ongoing'
    ];
}

function formatTimeToNow($datetime) {
    $now = new DateTime('now', new DateTimeZone('UTC'));
    $time = new DateTime($datetime, new DateTimeZone('UTC'));
    $diff = $now->getTimestamp() - $time->getTimestamp();

    if ($diff < 60) return 'vừa xong';
    if ($diff < 3600) return floor($diff / 60) . ' phút trước';
    if ($diff < 86400) return floor($diff / 3600) . ' giờ trước';
    if ($diff < 604800) return floor($diff / 86400) . ' ngày trước';
    if ($diff < 2592000) return floor($diff / 604800) . ' tuần trước';
    if ($diff < 31536000) return floor($diff / 2592000) . ' tháng trước';
    return floor($diff / 31536000) . ' năm trước';
}

function splitArr($array) {
    $total = count($array);
    $size = (int) floor($total / 3);
    $remainder = $total % 3;

    $part1 = array_slice($array, 0, $size + ($remainder > 0 ? 1 : 0));
    $part2 = array_slice($array, $size + ($remainder > 0 ? 1 : 0), $size + ($remainder > 1 ? 1 : 0));
    $part3 = array_slice($array, 2 * $size + ($remainder > 0 ? 1 : 0) + ($remainder > 1 ? 1 : 0));

    return [$part1, $part2, $part3];
}

function getPopularMangas($languages, $r18) {
    $params = [
        'limit' => 10,
        'includes' => ['cover_art', 'author', 'artist'],
        'contentRating' => $r18 ? ['safe', 'suggestive', 'erotica', 'pornographic'] : ['safe', 'suggestive', 'erotica'],
        'hasAvailableChapters' => 'true',
        'availableTranslatedLanguage' => $languages,
        'order' => ['followedCount' => 'desc'],
        'createdAtSince' => date('c', strtotime('-30 days'))
    ];
    $data = callMangadexApi('/manga', $params);
    if (!$data || !isset($data['data'])) {
        return [];
    }
    return array_map('parseManga', $data['data']);
}

function getCompletedMangas($languages, $r18) {
    $params = [
        'limit' => 12,
        'includes' => ['cover_art', 'author', 'artist'],
        'hasAvailableChapters' => 'true',
        'availableTranslatedLanguage' => $languages,
        'contentRating' => $r18 ? ['safe', 'suggestive', 'erotica', 'pornographic'] : ['safe', 'suggestive', 'erotica'],
        'status' => ['completed']
    ];
    $data = callMangadexApi('/manga', $params);
    if (!$data || !isset($data['data'])) return [];
    return array_map('parseManga', $data['data']);
}

function parseManga($item) {
    $coverArt = null;
    $authors = [];
    $artists = [];
    foreach ($item['relationships'] as $rel) {
        if ($rel['type'] === 'cover_art') {
            $coverArt = $rel['attributes']['fileName'] ?? null;
        } elseif ($rel['type'] === 'author') {
            $authors[] = ['name' => $rel['attributes']['name'] ?? 'Unknown'];
        } elseif ($rel['type'] === 'artist') {
            $artists[] = ['name' => $rel['attributes']['name'] ?? 'Unknown'];
        }
    }

    $titles = $item['attributes']['title'] ?? [];
    $altTitles = $item['attributes']['altTitles'] ?? [];
    $viTitle = null;
    $enTitle = $titles['en'] ?? null;
    foreach ($altTitles as $t) {
        if (isset($t['vi'])) $viTitle = $t['vi'];
        elseif (isset($t['en']) && !$enTitle) $enTitle = $t['en'];
    }
    $title = $viTitle ?? $enTitle ?? $titles[array_key_first($titles)] ?? 'Unknown';

    $description = $item['attributes']['description']['vi'] ?? $item['attributes']['description']['en'] ?? '';
    return [
        'id' => $item['id'],
        'title' => $title,
        'cover' => $coverArt,
        'author' => $authors,
        'artist' => $artists,
        'description' => ['content' => $description],
        'contentRating' => $item['attributes']['contentRating'] ?? 'safe',
        'status' => $item['attributes']['status'] ?? 'ongoing',
        'createdAt' => $item['attributes']['createdAt'] ?? '' 
    ];
}

function getMangaChapters($mangaId, $preferredLanguages = ['vi'], $fallbackLanguages = ['en']) {
    $chapters = [];
    $offset = 0;
    $limit = 100;

    // Hàm phụ để lấy chương theo ngôn ngữ
    $fetchChapters = function ($languages) use ($mangaId, $limit, &$offset) {
        $chapters = [];
        do {
            $params = [
                'limit' => $limit,
                'offset' => $offset,
                'includes' => ['scanlation_group'],
                'translatedLanguage' => $languages,
                'order' => ['volume' => 'desc', 'chapter' => 'desc']
            ];

            $data = callMangadexApi("/manga/$mangaId/feed", $params);
            if (!$data || !isset($data['data']) || empty($data['data'])) {
                break;
            }

            foreach ($data['data'] as $chapter) {
                $groups = [];
                foreach ($chapter['relationships'] as $rel) {
                    if ($rel['type'] === 'scanlation_group') {
                        $groups[] = [
                            'id' => $rel['id'],
                            'name' => $rel['attributes']['name'] ?? 'Unknown'
                        ];
                    }
                }
                $chapters[] = [
                    'id' => $chapter['id'],
                    'chapter' => $chapter['attributes']['chapter'] ?? 'Oneshot',
                    'volume' => $chapter['attributes']['volume'] ?? '',
                    'title' => $chapter['attributes']['title'] ?? '',
                    'translatedLanguage' => $chapter['attributes']['translatedLanguage'] ?? $languages[0],
                    'updatedAt' => $chapter['attributes']['readableAt'] ?? $chapter['attributes']['updatedAt'],
                    'group' => $groups
                ];
            }

            $offset += $limit;
        } while (count($data['data']) === $limit);

        return $chapters;
    };

    // Bước 1: Lấy các chương tiếng Việt
    $chapters = $fetchChapters($preferredLanguages);

    // Bước 2: Nếu không có chương tiếng Việt, lấy các chương tiếng Anh
    if (empty($chapters)) {
        $offset = 0; // Reset offset cho lần gọi API thứ hai
        $chapters = $fetchChapters($fallbackLanguages);
    }

    return $chapters;
}

function getChapterPages($chapterId) {
    $data = callMangadexApi("/at-home/server/$chapterId");
    if (!$data || !isset($data['baseUrl']) || !isset($data['chapter']) || empty($data['chapter']['data'])) {
        return null;
    }

    $baseUrl = $data['baseUrl'];
    $chapterHash = $data['chapter']['hash'];
    $pages = array_map(function ($page) use ($baseUrl, $chapterHash) {
        $imageUrl = "$baseUrl/data/$chapterHash/$page";
        return "/proxy-0hay.php?url=" . urlencode($imageUrl); 
    }, $data['chapter']['data']);

    return [
        'hash' => $chapterHash,
        'pages' => $pages
    ];
}


function getCovers($mangaId) {
    $params = [
        'limit' => 100,
        'order' => ['volume' => 'asc']
    ];
    $data = callMangadexApi("/manga/$mangaId/covers", $params);
    if ($data && isset($data['data'])) {
        return array_map(function ($item) {
            return [
                'id' => $item['id'],
                'fileName' => $item['attributes']['fileName'],
                'volume' => $item['attributes']['volume'] ?? 'No Volume',
                'locale' => $item['attributes']['locale'] ?? 'ja'
            ];
        }, $data['data']);
    }
    return [];
}

function getMangaStats($mangaId) {
    $data = callMangadexApi("/statistics/manga/$mangaId");
    if ($data && isset($data['statistics'][$mangaId])) {
        $stats = $data['statistics'][$mangaId];
        $distribution = $stats['rating']['distribution'];
        return [
            'rating' => [
                'bayesian' => $stats['rating']['bayesian'],
                'distribution' => $distribution,
                'max' => max(array_values($distribution))
            ],
            'follows' => $stats['follows'],
            'comments' => $stats['comments']['repliesCount'] ?? 0
        ];
    }
    return [
        'rating' => ['bayesian' => 0, 'distribution' => array_fill(1, 10, 0), 'max' => 0],
        'follows' => 0,
        'comments' => 0
    ];
}

function getRecentlyMangas($limit, $languages, $r18, $offset = 0) {
    $max_total = 10000;
    $safeOffset = $offset ?: 0;
    if ($limit + $safeOffset > $max_total) {
        $limit = $max_total - $safeOffset;
    }

    $params = [
        'limit' => $limit,
        'offset' => $safeOffset,
        'includes' => ['cover_art', 'author', 'artist'],
        'availableTranslatedLanguage' => $languages,
        'contentRating' => $r18 ? ['safe', 'suggestive', 'erotica', 'pornographic'] : ['safe', 'suggestive', 'erotica'],
        'order' => [
            'createdAt' => 'desc',
        ],
    ];

    $data = callMangadexApi('/manga', $params);

    if (!$data || !isset($data['data'])) {
        return [
            'mangas' => [],
            'total' => 0
        ];
    }

    $total = isset($data['total']) && $data['total'] > $max_total ? $max_total : $data['total'];
    $mangas = array_map('parseManga', $data['data']);

    return [
        'mangas' => $mangas,
        'total' => $total
    ];
}

if (!function_exists('parseManga')) {
    function parseManga($item) {
        $coverArt = null;
        foreach ($item['relationships'] as $rel) {
            if ($rel['type'] === 'cover_art') {
                $coverArt = $rel['attributes']['fileName'];
                break;
            }
        }

        $altTitles = $item['attributes']['altTitles'];
        $titles = $item['attributes']['title'];

        $enTitles = array_merge(
            isset($titles['en']) ? [$titles['en']] : [],
            array_column(array_filter($altTitles, fn($t) => isset($t['en'])), 'en')
        );
        $viTitle = null;
        $jaTitle = null;
        foreach ($altTitles as $t) {
            if (isset($t['vi'])) $viTitle = $t['vi'];
            if (isset($t['ja'])) $jaTitle = $t['ja'];
        }

        $title = $viTitle ?? $enTitles[0] ?? $jaTitle ?? 'Unknown';
        $altTitle = null;
        if ($title === $viTitle) {
            $altTitle = $enTitles[0] ?? $jaTitle ?? null;
        } elseif ($title === $enTitles[0]) {
            $altTitle = $enTitles[1] ?? $jaTitle ?? null;
        }

        return [
            'id' => $item['id'],
            'title' => $title,
            'altTitle' => $altTitle,
            'cover' => $coverArt,
            'status' => $item['attributes']['status'] ?? 'ongoing',
        ];
    }
}

if (!function_exists('callMangadexApi')) {
    function callMangadexApi($endpoint, $params = []) {
        $url = 'https://api.mangadex.org' . $endpoint;
        $query = http_build_query($params);
        if ($query) {
            $url .= '?' . $query;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    function searchGroups($query, $limit, $offset) {
        $max_total = 10000;
        if ($limit + $offset > $max_total) {
            $limit = $max_total - $offset;
        }
    
        $params = [
            'limit' => $limit,
            'offset' => $offset,
            'includes' => ['leader'],
        ];
    
        if ($query) {
            $params['name'] = $query;
        }
    
        $data = callMangadexApi('/group', $params);
        if (!$data || !isset($data['data'])) {
            return [
                'groups' => [],
                'total' => 0
            ];
        }
    
        $total = isset($data['total']) && $data['total'] > $max_total ? $max_total : $data['total'];
        $groups = array_map('parseGroup', $data['data']);
    
        return [
            'groups' => $groups,
            'total' => $total
        ];
    }
    
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
}

function saveMangaToDb($mangaId) {
    global $conn;
    $stmt = $conn->prepare("SELECT manga_id FROM manga WHERE manga_id = ?");
    $stmt->bind_param("s", $mangaId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return true; 
    }
    $data = callMangadexApi("/manga/$mangaId", ['includes' => ['cover_art']]);
    if (!$data || !isset($data['data'])) return false;
    $manga = parseManga($data['data']);
    $title = $manga['title'];
    $cover = $manga['cover'] ?? 'default_cover.png'; 
    $manga_link = "/manga.php?id=$mangaId";
    $stmt = $conn->prepare("INSERT INTO manga (manga_id, title, cover, manga_link) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $mangaId, $title, $cover, $manga_link);
    return $stmt->execute();
}

function getStaffPicks($limit = 8) {
    global $conn;

    $stmt = $conn->prepare("
        SELECT m.manga_id, m.title, m.cover, m.manga_link 
        FROM staff_picks sp
        JOIN manga m ON sp.manga_id = m.manga_id
        ORDER BY sp.order_position ASC
        LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $staffPicks = [];
    while ($row = $result->fetch_assoc()) {
        $staffPicks[] = [
            'manga_id' => $row['manga_id'],
            'title' => $row['title'],
            'cover_url' => COVER_BASE_URL . '/' . $row['manga_id'] . '/' . $row['cover'] . '.256.jpg', 
            'manga_link' => $row['manga_link']
        ];
    }

    return $staffPicks;
}

// Lấy tất cả truyện từ bảng manga
function getAllManga() {
    global $conn;

    $stmt = $conn->prepare("SELECT manga_id, title, cover, manga_link FROM manga ORDER BY title ASC");
    $stmt->execute();
    $result = $stmt->get_result();

    $mangaList = [];
    while ($row = $result->fetch_assoc()) {
        $mangaList[] = [
            'manga_id' => $row['manga_id'],
            'title' => $row['title'],
            'cover_url' => COVER_BASE_URL . '/' . $row['manga_id'] . '/' . $row['cover'] . '.256.jpg', 
            'manga_link' => $row['manga_link']
        ];
    }

    return $mangaList;
}

function updateStaffPicksOrder($orderData) {
    global $conn;

    $stmt = $conn->prepare("UPDATE staff_picks SET order_position = ? WHERE manga_id = ?");
    foreach ($orderData as $position => $mangaId) {
        $stmt->bind_param("is", $position, $mangaId);
        $stmt->execute();
    }
}

function getAllUsers() {
    global $conn;

    $stmt = $conn->prepare("
        SELECT user_id, username, name, email, roles, score, created_at 
        FROM users 
        ORDER BY created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'user_id' => $row['user_id'],
            'username' => $row['username'],
            'name' => $row['name'],
            'email' => $row['email'],
            'roles' => $row['roles'],
            'score' => $row['score'],
            'created_at' => $row['created_at']
        ];
    }

    return $users;
}

function getRandomManga($limit = 5) {
    global $conn;

    $stmt = $conn->prepare("
        SELECT manga_id, title, cover, manga_link 
        FROM manga 
        ORDER BY RAND() 
        LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $randomManga = [];
    while ($row = $result->fetch_assoc()) {
        $randomManga[] = [
            'manga_id' => $row['manga_id'],
            'title' => $row['title'],
            'cover_url' => COVER_BASE_URL . '/' . $row['manga_id'] . '/' . $row['cover'] . '.256.jpg',
            'manga_link' => $row['manga_link']
        ];
    }

    return $randomManga;
}

function getCommentsByMangaId($mangaId) {
    global $conn;

    $stmt = $conn->prepare("
        SELECT c.id, c.content, c.created_at, u.user_id, u.username 
        FROM comments c
        JOIN users u ON c.user_id = u.user_id
        WHERE c.manga_id = ?
        ORDER BY c.created_at DESC");
    $stmt->bind_param("s", $mangaId);
    $stmt->execute();
    $result = $stmt->get_result();

    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = [
            'id' => $row['id'],
            'user_id' => $row['user_id'],
            'username' => $row['username'],
            'content' => $row['content'],
            'created_at' => $row['created_at']
        ];
    }

    return $comments;
}

function addComment($mangaId, $userId, $content) {
    global $conn;

    $stmt = $conn->prepare("INSERT INTO comments (manga_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $mangaId, $userId, $content);
    return $stmt->execute();
}

function deleteComment($commentId, $userId) {
    global $conn;

    $stmt = $conn->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $commentId, $userId);
    return $stmt->execute();
}


?>