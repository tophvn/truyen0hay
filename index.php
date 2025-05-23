<?php
require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/src/session.php';

$r18 = isset($_SESSION['r18']) ? $_SESSION['r18'] : R18;

$languages = TRANSLATED_LANGUAGES;
$latestChapters = getLatestChapters(12, $languages, $r18); 
$recentMangasData = getRecentlyMangas(12, $languages, $r18); 
$recentMangas = $recentMangasData['mangas'];
$completedMangas = getCompletedMangas($languages, $r18, 12); 

[$part1, $part2, $part3] = splitArr($latestChapters);

function render_recent_mangas($recentMangas) {
    echo '
    <div class="mb-8 flex flex-col">
        <div class="flex justify-between">
            <div>
                <hr class="w-9 h-1 bg-blue-500 border-none">
                <h1 class="text-2xl font-bold uppercase">Truyện mới</h1>
            </div>
        </div>
        <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">';
    if (empty($recentMangas)) {
        echo '<p class="col-span-full text-gray-500 dark:text-gray-400">Không có truyện mới.</p>';
    } else {
        foreach ($recentMangas as $manga) {
            $mangaId = htmlspecialchars($manga['id'] ?? '#');
            $mangaTitle = htmlspecialchars($manga['title'] ?? 'Không có tiêu đề');
            $cover = htmlspecialchars($manga['cover'] ?? 'loading.png'); 

            echo '
                <a href="manga.php?id=' . $mangaId . '" class="relative rounded-sm shadow-md transition-colors duration-200 w-full border-none manga-card">
                    <div class="relative w-full aspect-[5/7] overflow-hidden">
                        <img 
                            src="' . COVER_BASE_URL . '/' . $mangaId . '/' . $cover . '.512.jpg" 
                            alt="' . $mangaTitle . '" 
                            class="absolute inset-0 w-full h-full rounded-sm object-cover object-center"
                            loading="lazy"
                            onerror="this.src=\'/public/images/loading.png\'"> 
                    </div>
                    <div class="absolute bottom-0 p-2 bg-gradient-to-t from-black w-full rounded-b-sm h-[40%] max-h-full flex items-end">
                        <p class="text-base font-semibold line-clamp-2 hover:line-clamp-none text-white drop-shadow-sm">' . $mangaTitle . '</p>
                    </div>
                </a>';
        }
    }
    echo '
        </div>
    </div>';
}

function render_completed_mangas($completedMangas) {
    echo '
    <div class="mb-8">
        <hr class="w-9 h-1 bg-blue-500 border-none">
        <h1 class="text-2xl font-bold uppercase">Hoàn thành</h1>
        <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">';
    if (empty($completedMangas)) {
        echo '<p class="col-span-full text-gray-500 dark:text-gray-400">Không có truyện hoàn thành.</p>';
    } else {
        foreach ($completedMangas as $manga) {
            $mangaId = htmlspecialchars($manga['id'] ?? '#');
            $mangaTitle = htmlspecialchars($manga['title'] ?? 'Không có tiêu đề');
            $cover = htmlspecialchars($manga['cover'] ?? 'loading.png'); 

            echo '
                <a href="manga.php?id=' . $mangaId . '" class="relative rounded-sm shadow-md transition-colors duration-200 w-full border-none manga-card">
                    <div class="relative w-full aspect-[5/7] overflow-hidden">
                        <img 
                            src="' . COVER_BASE_URL . '/' . $mangaId . '/' . $cover . '.512.jpg" 
                            alt="' . $mangaTitle . '" 
                            class="absolute inset-0 w-full h-full rounded-sm object-cover object-center"
                            loading="lazy"
                            onerror="this.src=\'/public/images/loading.png\'">
                    </div>
                    <div class="absolute bottom-0 p-2 bg-gradient-to-t from-black w-full rounded-b-sm h-[40%] max-h-full flex items-end">
                        <p class="text-base font-semibold line-clamp-2 hover:line-clamp-none text-white drop-shadow-sm">' . $mangaTitle . '</p>
                    </div>
                </a>';
        }
    }
    echo '
        </div>
    </div>';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Truyen0Hay - Đọc Truyện Tranh Online | Manga Mới Nhất</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO Meta -->
    <meta name="description" content="Truyen0Hay - Đọc truyện tranh online miễn phí, truyện Manhua, Manhwa, Manga hay nhất. Cập nhật liên tục các bộ truyện hot như NetTruyen, TruyenQQ, Vlogtruyen, Mê Đọc Truyện.">
    <meta name="keywords" content="truyện tranh, truyen tranh, truyen tranh online, manhua, manhwa, manga, nettruyen, truyenqq, vlogtruyen, đọc truyện hay, truyện mới, truyen0hay, doc truyen tranh">
    <meta name="author" content="Truyen0Hay.site">
    <meta name="theme-color" content="#ffffff">

    <!-- Open Graph / Facebook -->
    <meta property="og:title" content="Truyen0Hay - Đọc Truyện Tranh Online | Manhua, Manhwa, Manga Mới Nhất">
    <meta property="og:description" content="Đọc truyện tranh online miễn phí: Manhua, Manhwa, Manga, cập nhật nhanh và đầy đủ như NetTruyen, TruyenQQ.">
    <meta property="og:image" content="https://truyen0hay.site/public/images/logo.png">
    <meta property="og:url" content="https://truyen0hay.site">
    <meta property="og:type" content="website">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Truyen0Hay - Đọc Truyện Tranh Online">
    <meta name="twitter:description" content="Truyện tranh hot nhất: NetTruyen, TruyenQQ, Vlogtruyen và nhiều hơn.">
    <meta name="twitter:image" content="https://truyen0hay.site/public/images/logo.png">

    <!-- Favicon -->
    <link href="/public/images/logo.png" rel="icon">

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
</head>

<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <?php include 'includes/navbar.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <div id="main-content">
        <div class="container mx-auto p-4">
            <?php include 'includes/staff-pick-card.php'; ?>
            
            <div class="mb-8">
                <hr class="w-9 h-1 bg-blue-500 border-none">
                <h1 class="text-2xl font-bold uppercase">Mới cập nhật</h1>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                    <div class="space-y-4">
                        <?php 
                        if (empty($part1)) {
                            echo '<p class="text-gray-500 dark:text-gray-400">Không có chương mới.</p>';
                        } else {
                            foreach ($part1 as $chapter) include 'includes/latest-card.php';
                        }
                        ?>
                    </div>
                    <div class="space-y-4 hidden sm:block">
                        <?php 
                        if (empty($part2)) {
                            echo '<p class="text-gray-500 dark:text-gray-400">Không có chương mới.</p>';
                        } else {
                            foreach ($part2 as $chapter) include 'includes/latest-card.php';
                        }
                        ?>
                    </div>
                    <div class="space-y-4 hidden lg:block">
                        <?php 
                        if (empty($part3)) {
                            echo '<p class="text-gray-500 dark:text-gray-400">Không có chương mới.</p>';
                        } else {
                            foreach ($part3 as $chapter) include 'includes/latest-card.php';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <?php render_recent_mangas($recentMangas); ?>
            <?php render_completed_mangas($completedMangas); ?>
            <?php include 'includes/manga-up.php'; ?>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>

    <script src="/js/main.js"></script>
    <script src="/js/search.js"></script>
</body>
</html>