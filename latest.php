<?php
require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/src/session.php';

const LIMIT = 30; 
const MAX_TOTAL = 10000;

$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * LIMIT;

$latestChapters = getLatestChapters(LIMIT, ['vi'], false, $offset);
$total = MAX_TOTAL;
$totalPages = ceil($total / LIMIT);

$latestMangas = [
    'mangas' => array_map(function ($chapter) {
        return [
            'id' => $chapter['manga']['id'] ?? '',
            'title' => $chapter['manga']['title'] ?? 'Unknown',
            'cover' => $chapter['manga']['cover'] ?? '',
            'updatedAt' => $chapter['updatedAt'] ?? '', 
        ];
    }, $latestChapters),
    'total' => $total,
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <meta name="robots" content="index, follow">
    <title>Truyện Mới Cập Nhật - <?php echo defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY'; ?></title>
    <meta name="description" content="<?php 
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
        echo "Khám phá danh sách truyện tranh mới cập nhật tại $siteName. Đọc manga, manhwa, manhua online với các chương mới nhất, cập nhật hàng ngày!";
    ?>">
    <meta name="keywords" content="<?php 
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
        echo "truyện mới cập nhật, truyện tranh mới, đọc truyện online, manga mới, manhwa mới, manhua mới, $siteName";
    ?>">

    <!-- Canonical URL -->
    <link rel="canonical" href="<?php 
        echo 'https://' . $_SERVER['HTTP_HOST'] . '/index.php';
    ?>">

    <!-- Favicon -->
    <link href="/public/images/logo.png" rel="icon" type="image/png">

    <!-- Open Graph (OG) Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php 
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
        echo "Truyện Mới Cập Nhật - $siteName";
    ?>">
    <meta property="og:description" content="<?php 
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
        echo "Khám phá danh sách truyện tranh mới cập nhật tại $siteName. Đọc manga, manhwa, manhua online với các chương mới nhất, cập nhật hàng ngày!";
    ?>">
    <meta property="og:url" content="<?php 
        echo 'https://' . $_SERVER['HTTP_HOST'] . '/index.php';
    ?>">
    <meta property="og:image" content="<?php 
        echo 'https://' . $_SERVER['HTTP_HOST'] . '/public/images/logo.png';
    ?>">
    <meta property="og:site_name" content="<?php echo defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY'; ?>">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php 
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
        echo "Truyện Mới Cập Nhật - $siteName";
    ?>">
    <meta name="twitter:description" content="<?php 
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
        echo "Khám phá danh sách truyện tranh mới cập nhật tại $siteName. Đọc manga, manhwa, manhua online với các chương mới nhất, cập nhật hàng ngày!";
    ?>">
    <meta name="twitter:image" content="<?php 
        echo 'https://' . $_SERVER['HTTP_HOST'] . '/public/images/logo.png';
    ?>">

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "CollectionPage",
        "name": "Truyện Mới Cập Nhật",
        "description": "<?php 
            $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
            echo "Khám phá danh sách truyện tranh mới cập nhật tại $siteName. Đọc manga, manhwa, manhua online với các chương mới nhất, cập nhật hàng ngày!";
        ?>",
        "url": "<?php echo 'https://' . $_SERVER['HTTP_HOST'] . '/index.php'; ?>",
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
    <?php include __DIR__ . '/includes/navbar.php'; ?>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div id="main-content" class="container mx-auto px-4 pt-20 pb-8">
        <section class="flex flex-col gap-4 mb-8">
            <div class="flex items-center gap-2">
                <hr class="w-9 h-1 bg-purple-500 border-none">
                <h1 class="text-2xl font-bold uppercase">Truyện mới cập nhật</h1>
            </div>
            <?php include 'includes/advanced-search-form.php'; ?>
        </section>

        <section>
            <?php if (!empty($latestMangas['mangas'])): ?>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-4">
                    <?php foreach ($latestMangas['mangas'] as $manga): ?>
                        <?php if (!empty($manga['id']) && !empty($manga['cover'])): ?>
                            <a href="/manga.php?id=<?php echo $manga['id']; ?>" class="manga-card rounded-sm shadow-md hover:shadow-lg transition-shadow relative">
                                <div>
                                    <img 
                                        src="/proxy-0hay.php?url=<?php echo urlencode('https://uploads.mangadex.org/covers/' . $manga['id'] . '/' . $manga['cover'] . '.512.jpg'); ?>" 
                                        alt="Ảnh bìa <?php echo htmlspecialchars($manga['title']); ?>" 
                                        class="rounded-t-sm" 
                                        loading="lazy" 
                                        onerror="this.src='/public/images/loading.png'"
                                    />
                                    <?php if (!empty($manga['updatedAt'])): ?>
                                        <div class="time-overlay">
                                            <?php echo htmlspecialchars(formatTimeToNow($manga['updatedAt'])); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="title-overlay">
                                        <p class="text-base font-semibold text-white line-clamp-2 hover:line-clamp-none drop-shadow-sm">
                                            <?php echo htmlspecialchars($manga['title']); ?>
                                        </p>
                                    </div>
                                </div>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php include __DIR__ . '/includes/pagination.php'; ?>
            <?php else: ?>
                <p class="text-center text-gray-500 text-lg">Không có truyện mới cập nhật để hiển thị.</p>
            <?php endif; ?>
        </section>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="/js/main.js"></script>
    <script src="/js/search.js"></script>
    <script src="/js/advanced-search.js"></script>
</body>
</html>