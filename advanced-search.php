<?php
require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/src/session.php';

// Constants
const LIMIT = 30;
const MAX_TOTAL = 10000;

// Utility functions
function safeString($value) {
    return is_array($value) ? implode(',', array_map('htmlspecialchars', $value)) : htmlspecialchars($value ?? '');
}

function getTags() {
    $data = callMangadexApi('/manga/tag');
    return $data && isset($data['data']) 
        ? array_map(fn($tag) => ['id' => $tag['id'], 'name' => $tag['attributes']['name']['en'] ?? $tag['id']], $data['data']) 
        : [];
}

function advancedSearchManga($params) {
    $limit = min($params['limit'], MAX_TOTAL - $params['offset']);
    $searchParams = [
        'title' => $params['query'],
        'limit' => $limit,
        'offset' => $params['offset'],
        'includes' => ['cover_art', 'author', 'artist'],
    ];

    foreach (['contentRating', 'status', 'include', 'exclude', 'author', 'demos', 'origin', 'translated'] as $key) {
        if (!empty($params[$key])) $searchParams[$key === 'include' ? 'includedTags' : ($key === 'exclude' ? 'excludedTags' : ($key === 'demos' ? 'publicationDemographic' : $key))] = $params[$key];
    }
    if ($params['availableChapter']) $searchParams['hasAvailableChapters'] = 'true';
    if ($params['availableChapter'] && !empty($params['translated'])) $searchParams['availableTranslatedLanguage'] = $params['translated'];
    if ($params['year']) $searchParams['year'] = (int)$params['year'];

    $data = callMangadexApi('/manga', $searchParams);
    if (!$data || !isset($data['data'])) return ['total' => 0, 'mangas' => []];

    return [
        'total' => min($data['total'] ?? count($data['data']), MAX_TOTAL),
        'mangas' => array_map('parseManga', $data['data'])
    ];
}

// Filter options
$filterOptions = [
    'status' => ['completed' => 'Đã hoàn thành', 'ongoing' => 'Đang tiến hành', 'hiatus' => 'Tạm ngừng', 'cancelled' => 'Đã hủy'],
    'demos' => ['shounen' => 'Shounen', 'shoujo' => 'Shoujo', 'seinen' => 'Seinen', 'jousei' => 'Jousei'],
    'contentRating' => [
        'safe' => 'Lành mạnh',
        'suggestive' => 'Hơi hơi',
        'erotica' => 'Cũng tạm',
        'pornographic' => 'Segggg!',
    ],
    'origin' => [
        'vi' => 'Tiếng Việt',
        'en' => 'Tiếng Anh',
        'ja' => 'Tiếng Nhật',
        'ko' => 'Tiếng Hàn',
        'zh' => 'Tiếng Trung',
    ],
    'translated' => ['vi' => 'Tiếng Việt', 'en' => 'Tiếng Anh'],
];

// Parse GET parameters
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * LIMIT;

// Set default status to "ongoing" if no parameters are provided
$defaultParams = empty($_GET) ? ['status' => ['ongoing']] : [];

$searchParams = array_merge([
    'query' => $_GET['q'] ?? '',
    'author' => isset($_GET['author']) ? (is_array($_GET['author']) ? array_filter($_GET['author']) : array_filter(explode(',', $_GET['author']))) : [],
    'contentRating' => isset($_GET['contentRating']) ? (is_array($_GET['contentRating']) ? array_filter($_GET['contentRating']) : array_filter(explode(',', $_GET['contentRating']))) : [],
    'status' => isset($_GET['status']) ? (is_array($_GET['status']) ? array_filter($_GET['status']) : array_filter(explode(',', $_GET['status']))) : (isset($defaultParams['status']) ? $defaultParams['status'] : []),
    'demos' => isset($_GET['demos']) ? (is_array($_GET['demos']) ? array_filter($_GET['demos']) : array_filter(explode(',', $_GET['demos']))) : [],
    'include' => isset($_GET['include']) ? (is_array($_GET['include']) ? array_filter($_GET['include']) : array_filter(explode(',', $_GET['include']))) : [],
    'exclude' => isset($_GET['exclude']) ? (is_array($_GET['exclude']) ? array_filter($_GET['exclude']) : array_filter(explode(',', $_GET['exclude']))) : [],
    'origin' => isset($_GET['origin']) ? (is_array($_GET['origin']) ? array_filter($_GET['origin']) : array_filter(explode(',', $_GET['origin']))) : [],
    'availableChapter' => ($_GET['availableChapter'] ?? '') === 'true',
    'translated' => isset($_GET['translated']) ? (is_array($_GET['translated']) ? array_filter($_GET['translated']) : array_filter(explode(',', $_GET['translated']))) : [],
    'year' => $_GET['year'] ?? '',
    'offset' => $offset,
    'limit' => LIMIT,
], $defaultParams);

// Fetch data
$tagOptions = getTags();
$result = advancedSearchManga($searchParams);
$totalPages = $result ? ceil($result['total'] / LIMIT) : 0;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/public/images/logo.png" rel="icon">
    <title>Tìm kiếm nâng cao - <?php echo defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <style>
        .title-overlay {
            position: absolute;
            bottom: 0;
            width: 100%;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            padding: 0.5rem;
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 font-sans">
    <?php include __DIR__ . '/includes/navbar.php'; ?>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div id="main-content" class="container mx-auto px-4 pt-20 pb-8">
        <!-- Search Section -->
        <section class="flex flex-col gap-4 mb-8">
            <div class="flex items-center gap-2">
                <hr class="w-9 h-1 bg-purple-500 border-none">
                <h1 class="text-2xl font-bold uppercase">Tìm kiếm nâng cao</h1>
            </div>
            <?php include __DIR__ . '/includes/advanced-search-form.php'; ?>
        </section>

        <!-- Results Section -->
        <section>
            <?php if ($result && !empty($result['mangas'])): ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                    <?php foreach ($result['mangas'] as $manga): ?>
                        <a href="/manga.php?id=<?php echo $manga['id']; ?>" class="manga-card rounded-sm shadow-md hover:shadow-lg transition-shadow">
                            <div>
                                <img 
                                    src="/proxy-0hay.php?url=<?php echo urlencode('https://uploads.mangadex.org/covers/' . $manga['id'] . '/' . $manga['cover'] . '.512.jpg'); ?>" 
                                    alt="Ảnh bìa <?php echo htmlspecialchars($manga['title']); ?>" 
                                    class="rounded-t-sm" 
                                    loading="lazy" 
                                    onerror="this.src='/public/images/loading.png'"
                                />
                                <div class="title-overlay">
                                    <p class="text-base font-semibold text-white line-clamp-2 hover:line-clamp-none drop-shadow-sm">
                                        <?php echo htmlspecialchars($manga['title']); ?>
                                    </p>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php include __DIR__ . '/includes/pagination.php'; ?>
            <?php else: ?>
                <p class="text-center text-gray-500 text-lg">Không tìm thấy kết quả nào.</p>
            <?php endif; ?>
        </section>
    </div>

    <script src="/js/main.js"></script>
    <script src="/js/search.js"></script>
    <script src="/js/advanced-search.js"></script>
</body>
</html>