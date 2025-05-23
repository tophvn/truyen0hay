<?php
require_once __DIR__ . '/lib/functions.php';

const LIMIT = 30;
const MAX_TOTAL = 10000;
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

$pageSearch = max(1, (int)($_GET['page'] ?? 1));
$offsetSearch = ($pageSearch - 1) * LIMIT;
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
    'offset' => $offsetSearch,
    'limit' => LIMIT,
], $defaultParams);

$tagOptions = getTags();
$pageRecent = max(1, (int)($_GET['recentPage'] ?? 1));
$offsetRecent = ($pageRecent - 1) * LIMIT;
$recentlyMangas = getRecentlyMangas(LIMIT, ['vi'], false, $offsetRecent);
$totalPagesRecent = $recentlyMangas['total'] ? ceil($recentlyMangas['total'] / LIMIT) : 0;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <meta name="robots" content="index, follow">
    <title>Truyện Mới - <?php echo defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY'; ?></title>
    <meta name="description" content="<?php 
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
        echo "Khám phá các truyện tranh mới nhất tại $siteName. Đọc manga, manhwa, manhua mới phát hành, cập nhật liên tục mỗi ngày!";
    ?>">

    <!-- Meta Keywords -->
    <meta name="keywords" content="<?php 
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
        echo "truyện mới, truyện tranh mới, manga mới, manhwa mới, manhua mới, đọc truyện online, $siteName";
    ?>">

    <!-- Canonical URL -->
    <link rel="canonical" href="<?php 
        echo 'https://' . $_SERVER['HTTP_HOST'] . '/new-manga.php';
    ?>">

    <!-- Favicon -->
    <link href="/public/images/logo.png" rel="icon" type="image/png">

    <!-- Open Graph (OG) Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php 
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
        echo "Truyện Mới - $siteName";
    ?>">
    <meta property="og:description" content="<?php 
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
        echo "Khám phá các truyện tranh mới nhất tại $siteName. Đọc manga, manhwa, manhua mới phát hành, cập nhật liên tục mỗi ngày!";
    ?>">
    <meta property="og:url" content="<?php 
        echo 'https://' . $_SERVER['HTTP_HOST'] . '/new-manga.php';
    ?>">
    <meta property="og:image" content="<?php 
        echo 'https://' . $_SERVER['HTTP_HOST'] . '/public/images/logo.png';
    ?>">
    <meta property="og:site_name" content="<?php echo defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY'; ?>">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php 
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
        echo "Truyện Mới - $siteName";
    ?>">
    <meta name="twitter:description" content="<?php 
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
        echo "Khám phá các truyện tranh mới nhất tại $siteName. Đọc manga, manhwa, manhua mới phát hành, cập nhật liên tục mỗi ngày!";
    ?>">
    <meta name="twitter:image" content="<?php 
        echo 'https://' . $_SERVER['HTTP_HOST'] . '/public/images/logo.png';
    ?>">

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "CollectionPage",
        "name": "Truyện Mới",
        "description": "<?php 
            $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
            echo "Khám phá các truyện tranh mới nhất tại $siteName. Đọc manga, manhwa, manhua mới phát hành, cập nhật liên tục mỗi ngày!";
        ?>",
        "url": "<?php echo 'https://' . $_SERVER['HTTP_HOST'] . '/new-manga.php'; ?>",
        "publisher": {
            "@type": "Organization",
            "name": "<?php echo defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY'; ?>",
            "logo": {
                "@type": "ImageObject",
                "url": "https://<?php echo $_SERVER['HTTP_HOST']; ?>/public/images/logo.png"
            }
        }
    }
    </script>

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-900 font-sans">
    <?php include 'includes/navbar.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <div id="main-content" class="container mx-auto p-4">
        <section class="flex flex-col gap-4 mb-8">
            <div>
                <hr class="w-9 h-1 bg-purple-500 border-none">
                <h1 class="text-2xl font-bold uppercase">Tìm kiếm nâng cao</h1>
            </div>
            <?php include 'includes/advanced-search-form.php'; ?>
        </section>

        <section>
            <div class="flex flex-col">
                <div class="flex justify-between">
                    <div>
                        <hr class="w-9 h-1 bg-purple-500 border-none" />
                        <h1 class="text-2xl font-bold uppercase">Truyện mới</h1>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-3">
                    <?php if (!empty($recentlyMangas['mangas'])): ?>
                        <?php foreach ($recentlyMangas['mangas'] as $manga): ?>
                            <a href="/manga.php?id=<?php echo $manga['id']; ?>" class="manga-card rounded-sm shadow-md hover:shadow-lg transition-shadow relative">
                                <div>
                                    <img 
                                        src="/proxy-0hay.php?url=<?php echo urlencode('https://uploads.mangadex.org/covers/' . $manga['id'] . '/' . $manga['cover'] . '.512.jpg'); ?>" 
                                        alt="Ảnh bìa <?php echo htmlspecialchars($manga['title']); ?>" 
                                        class="rounded-t-sm" 
                                        loading="lazy" 
                                        onerror="this.src='/public/images/loading.png'"
                                    />
                                    <?php if (!empty($manga['createdAt'])): ?>
                                        <div class="time-overlay">
                                            <?php echo htmlspecialchars(formatTimeToNow($manga['createdAt'])); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="title-overlay">
                                        <p class="text-base font-semibold text-white line-clamp-2 hover:line-clamp-none drop-shadow-sm">
                                            <?php echo htmlspecialchars($manga['title']); ?>
                                        </p>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center text-gray-500 col-span-full">Không có truyện mới để hiển thị.</p>
                    <?php endif; ?>
                </div>

                <?php if ($totalPagesRecent > 1): ?>
                    <?php
                    $page = $pageRecent;
                    $totalPages = $totalPagesRecent;
                    $_GET['recentPage'] = $pageRecent; 
                    include 'includes/pagination.php';
                    ?>
                <?php endif; ?>
            </div>
        </section>
        
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="/js/main.js"></script>
    <script src="/js/search.js"></script>
    <script src="/js/advanced-search.js"></script>
</body>
</html>