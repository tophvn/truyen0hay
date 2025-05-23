<?php
require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/src/session.php'; 
require_once __DIR__ . '/config/database.php';

$chapterId = $_GET['chapter'] ?? '';
if (empty($chapterId)) {
    header('Location: index.php');
    exit;
}

$chapterData = getChapterPages($chapterId);
if (!$chapterData) {
    header('Location: index.php');
    exit;
}

$chapterInfo = callMangadexApi("/chapter/$chapterId");
$mangaId = null;
$chapterDetails = $chapterInfo['data']['attributes'];
$groups = [];
foreach ($chapterInfo['data']['relationships'] as $relationship) {
    if ($relationship['type'] === 'manga') {
        $mangaId = $relationship['id'];
    } elseif ($relationship['type'] === 'scanlation_group') {
        $groups[] = [
            'id' => $relationship['id'],
            'name' => $relationship['attributes']['name'] ?? 'Unknown'
        ];
    }
}

if (!$mangaId) {
    header('Location: index.php');
    exit;
}

$manga = getMangaById($mangaId);
$chapters = getMangaChapters($mangaId, TRANSLATED_LANGUAGES);

if (!$manga) {
    header('Location: index.php');
    exit;
}

$chapterIndex = array_search($chapterId, array_column($chapters, 'id'));
$chapterTitle = $chapterIndex !== false ? htmlspecialchars($chapters[$chapterIndex]['chapter']) : 'Unknown';
$prevChapter = $chapterIndex < count($chapters) - 1 ? $chapters[$chapterIndex + 1]['id'] : null;
$nextChapter = $chapterIndex > 0 ? $chapters[$chapterIndex - 1]['id'] : null;

// Lưu lịch sử đọc
if (isset($_SESSION['user'])) {
    $userId = $_SESSION['user']['user_id'];
    $mangaTitle = $manga['title'];
    $chapterNumber = $chapterDetails['chapter'] ?? 'Oneshot';
    $chapterFullTitle = $chapterDetails['title'] ? htmlspecialchars($chapterDetails['title']) : '';

    $stmt = $conn->prepare("
        INSERT INTO user_history (user_id, manga_id, chapter_id, chapter_number, chapter_title, manga_title, read_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE read_at = NOW()
    ");
    $stmt->bind_param("isssss", $userId, $mangaId, $chapterId, $chapterNumber, $chapterFullTitle, $mangaTitle);
    $stmt->execute();
}

function chapterTitle($chapterDetails) {
    if (!$chapterDetails['chapter']) {
        return "Oneshot";
    }
    return $chapterDetails['title'] ? "Ch. {$chapterDetails['chapter']} - {$chapterDetails['title']}" : "Ch. {$chapterDetails['chapter']}";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <meta name="robots" content="index, follow">

    <title>
        <?php 
        if (isset($manga) && is_array($manga) && !empty($manga['title']) && isset($chapterDetails) && is_array($chapterDetails)) {
            $mangaTitle = htmlspecialchars($manga['title']);
            $chapterTitle = chapterTitle($chapterDetails);
            echo "$mangaTitle - $chapterTitle - Đọc Truyện Tranh Online - " . (defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY');
        } else {
            echo "Không tìm thấy - Đọc Truyện Tranh Online - " . (defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY');
        }
        ?>
    </title>

    <!-- Meta Description -->
    <meta name="description" content="<?php 
        if (isset($manga) && is_array($manga) && !empty($manga['title']) && isset($chapterDetails) && is_array($chapterDetails)) {
            $mangaTitle = htmlspecialchars($manga['title']);
            $chapterTitle = chapterTitle($chapterDetails);
            $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
            $description = "Đọc $mangaTitle $chapterTitle online, cập nhật chương mới nhất tại $siteName. Khám phá truyện tranh hấp dẫn!";
            echo substr($description, 0, 160);
        } else {
            $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
            echo "Không tìm thấy truyện. Khám phá bộ sưu tập truyện tranh đa dạng, cập nhật hàng ngày tại $siteName.";
        }
    ?>">

    <!-- Meta Keywords -->
    <meta name="keywords" content="<?php 
        if (isset($manga) && is_array($manga) && !empty($manga['title']) && isset($chapterDetails) && is_array($chapterDetails)) {
            $mangaTitle = htmlspecialchars($manga['title']);
            $chapterTitle = chapterTitle($chapterDetails);
            $tags = [];
            if (isset($manga['tags']) && is_array($manga['tags'])) {
                $tags = array_map(function($tag) {
                    return $tag['attributes']['name']['en'] ?? $tag['attributes']['name']['vi'] ?? 'Unknown';
                }, $manga['tags']);
            }
            $tagsString = implode(', ', $tags);
            echo "$mangaTitle, $mangaTitle $chapterTitle, đọc truyện tranh $mangaTitle $chapterTitle, truyện tranh online, $tagsString, manga, manhwa, manhua";
        } else {
            $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
            echo "truyện tranh, manga, manhwa, manhua, đọc truyện online, $siteName";
        }
    ?>">

    <!-- Canonical URL -->
    <link rel="canonical" href="<?php 
        if (isset($manga) && is_array($manga) && !empty($manga['manga_id']) && isset($chapterDetails) && is_array($chapterDetails) && !empty($chapterDetails['id'])) {
            echo 'https://' . $_SERVER['HTTP_HOST'] . '/chapter/' . $chapterDetails['id'];
        } else {
            echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }
    ?>">

    <!-- Favicon -->
    <link href="/public/images/logo.png" rel="icon" type="image/png">

    <!-- Open Graph (OG) Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php 
        if (isset($manga) && is_array($manga) && !empty($manga['title']) && isset($chapterDetails) && is_array($chapterDetails)) {
            $mangaTitle = htmlspecialchars($manga['title']);
            $chapterTitle = chapterTitle($chapterDetails);
            echo "$mangaTitle - $chapterTitle - Đọc Truyện Tranh Online - " . (defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY');
        } else {
            echo "Không tìm thấy - Đọc Truyện Tranh Online - " . (defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY');
        }
    ?>">
    <meta property="og:description" content="<?php 
        if (isset($manga) && is_array($manga) && !empty($manga['title']) && isset($chapterDetails) && is_array($chapterDetails)) {
            $mangaTitle = htmlspecialchars($manga['title']);
            $chapterTitle = chapterTitle($chapterDetails);
            $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
            $description = "Đọc $mangaTitle $chapterTitle online, cập nhật chương mới nhất tại $siteName. Khám phá truyện tranh hấp dẫn!";
            echo substr($description, 0, 160);
        } else {
            $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
            echo "Không tìm thấy truyện. Khám phá bộ sưu tập truyện tranh đa dạng, cập nhật hàng ngày tại $siteName.";
        }
    ?>">
    <meta property="og:url" content="<?php 
        if (isset($manga) && is_array($manga) && !empty($manga['manga_id']) && isset($chapterDetails) && is_array($chapterDetails) && !empty($chapterDetails['id'])) {
            echo 'https://' . $_SERVER['HTTP_HOST'] . '/chapter/' . $chapterDetails['id'];
        } else {
            echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }
    ?>">
    <meta property="og:image" content="<?php 
        if (isset($manga) && is_array($manga) && !empty($manga['manga_id']) && !empty($manga['cover'])) {
            echo htmlspecialchars(COVER_BASE_URL . "/" . $manga['manga_id'] . "/" . $manga['cover'] . ".256.jpg");
        } else {
            echo 'https://' . $_SERVER['HTTP_HOST'] . '/public/images/logo.png';
        }
    ?>">
    <meta property="og:site_name" content="<?php echo defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY'; ?>">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php 
        if (isset($manga) && is_array($manga) && !empty($manga['title']) && isset($chapterDetails) && is_array($chapterDetails)) {
            $mangaTitle = htmlspecialchars($manga['title']);
            $chapterTitle = chapterTitle($chapterDetails);
            echo "$mangaTitle - $chapterTitle - Đọc Truyện Tranh Online - " . (defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY');
        } else {
            echo "Không tìm thấy - Đọc Truyện Tranh Online - " . (defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY');
        }
    ?>">
    <meta name="twitter:description" content="<?php 
        if (isset($manga) && is_array($manga) && !empty($manga['title']) && isset($chapterDetails) && is_array($chapterDetails)) {
            $mangaTitle = htmlspecialchars($manga['title']);
            $chapterTitle = chapterTitle($chapterDetails);
            $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
            $description = "Đọc $mangaTitle $chapterTitle online, cập nhật chương mới nhất tại $siteName. Khám phá truyện tranh hấp dẫn!";
            echo substr($description, 0, 160);
        } else {
            $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
            echo "Không tìm thấy truyện. Khám phá bộ sưu tập truyện tranh đa dạng, cập nhật hàng ngày tại $siteName.";
        }
    ?>">
    <meta name="twitter:image" content="<?php 
        if (isset($manga) && is_array($manga) && !empty($manga['manga_id']) && !empty($manga['cover'])) {
            echo htmlspecialchars(COVER_BASE_URL . "/" . $manga['manga_id'] . "/" . $manga['cover'] . ".256.jpg");
        } else {
            echo 'https://' . $_SERVER['HTTP_HOST'] . '/public/images/logo.png';
        }
    ?>">

    <?php if (isset($manga) && is_array($manga) && !empty($manga['title']) && isset($chapterDetails) && is_array($chapterDetails) && !empty($chapterDetails['id'])): ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "ComicIssue",
        "name": "<?php 
            $mangaTitle = htmlspecialchars($manga['title']);
            $chapterTitle = chapterTitle($chapterDetails);
            echo "$mangaTitle - $chapterTitle";
        ?>",
        "issueNumber": "<?php 
            echo isset($chapterDetails['chapter']) ? htmlspecialchars($chapterDetails['chapter']) : '';
        ?>",
        "partOfSeries": {
            "@type": "ComicSeries",
            "name": "<?php echo htmlspecialchars($manga['title']); ?>",
            "url": "<?php echo 'https://' . $_SERVER['HTTP_HOST'] . '/manga.php?id=' . $manga['manga_id']; ?>"
        },
        "url": "<?php echo 'https://' . $_SERVER['HTTP_HOST'] . '/chapter/' . $chapterDetails['id']; ?>",
        "image": "<?php 
            if (isset($manga['manga_id']) && !empty($manga['cover'])) {
                echo htmlspecialchars(COVER_BASE_URL . "/" . $manga['manga_id'] . "/" . $manga['cover'] . ".256.jpg");
            } else {
                echo '';
            }
        ?>",
        "genre": <?php 
            $tags = [];
            if (isset($manga['tags']) && is_array($manga['tags'])) {
                $tags = array_map(function($tag) {
                    return $tag['attributes']['name']['en'] ?? $tag['attributes']['name']['vi'] ?? 'Unknown';
                }, $manga['tags']);
            }
            echo json_encode($tags);
        ?>,
        "author": {
            "@type": "Person",
            "name": "<?php 
                $author = 'Unknown';
                if (isset($manga['author']) && is_array($manga['author']) && !empty($manga['author'])) {
                    $author = $manga['author'][0]['name'] ?? 'Unknown';
                }
                echo htmlspecialchars($author);
            ?>"
        },
        "inLanguage": "vi",
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
    <?php endif; ?>

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <style>
        #chapter-nav { bottom: 0; transform: translateX(-50%); z-index: 50; }
        #settings-dialog { z-index: 60; }
        body { padding-bottom: 60px; }
        select { max-height: 100px; overflow-y: auto; }
        select::-webkit-scrollbar { width: 8px; }
        select::-webkit-scrollbar-thumb { background: #888; border-radius: 4px; }
        select::-webkit-scrollbar-thumb:hover { background: #555; }
        #main-content {
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Style cho chế độ tối */
        body.dark-theme {
            background-color: #1f2937; /* gray-800 */
        }
        body.dark-theme #main-content {
            background-color: #1f2937; /* gray-800 */
        }
        body.dark-theme #main-content #long-strip {
            background-color: #1f2937; /* gray-800 */
        }
        body.dark-theme #main-content .bg-white {
            background-color: #374151; /* gray-700 */
        }
        body.dark-theme #main-content .bg-gray-100 {
            background-color: #4b5563; /* gray-600 */
        }
        body.dark-theme #main-content .text-gray-900 {
            color: #f3f4f6; /* gray-100 */
        }
        body.dark-theme #main-content .text-gray-700 {
            color: #d1d5db; /* gray-300 */
        }
        body.dark-theme #main-content .text-red-500 {
            color: #f87171; /* red-400 */
        }
        body.dark-theme #main-content .border-gray-600 {
            border-color: #4b5563; /* gray-600 */
        }
        body.dark-theme #main-content .bg-gray-200 {
            background-color: #4b5563; /* gray-600 */
        }
        body.dark-theme #main-content .text-gray-300 {
            color: #d1d5db; /* gray-300 */
        }
        body.dark-theme #main-content .hover\:bg-gray-300:hover {
            background-color: #6b7280; /* gray-500 */
        }

        /* Đảm bảo sidebar và navbar không bị ảnh hưởng */
        #sidebar, header {
            background-color: #ffffff !important; /* Luôn là màu trắng */
        }
        #sidebar *, header * {
            background-color: transparent !important; /* Đảm bảo các phần tử con không bị ảnh hưởng */
        }
        #sidebar .bg-gray-100, header .bg-gray-100 {
            background-color: #f3f4f6 !important; /* gray-100 */
        }
        #sidebar .bg-gray-200, header .bg-gray-200 {
            background-color: #e5e7eb !important; /* gray-200 */
        }
        #sidebar .text-gray-700, header .text-gray-700 {
            color: #4b5563 !important; /* gray-600 */
        }
        #sidebar .text-gray-900, header .text-gray-900 {
            color: #1f2937 !important; /* gray-800 */
        }
        #sidebar .hover\:bg-gray-200:hover, header .hover\:bg-gray-200:hover {
            background-color: #e5e7eb !important; /* gray-200 */
        }
        #sidebar a, header a {
            color: #1f2937 !important; /* gray-800 */
        }
        #sidebar a:hover, header a:hover {
            color: #4b5563 !important; /* gray-600 */
        }
        #sidebar button, header button {
            background-color: #e5e7eb !important; /* gray-200 */
            color: #1f2937 !important; /* gray-800 */
        }
        #sidebar button:hover, header button:hover {
            background-color: #d1d5db !important; /* gray-300 */
        }
    </style>
</head>
<body class="bg-white">
    <?php include 'includes/navbar.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <div id="main-content" class="container mx-auto p-4">
        <div class="bg-white rounded-lg shadow p-6 mb-4">
            <!-- Thông tin chapter -->
            <div class="mb-6">
                <h1 class="text-2xl font-semibold mb-2 text-gray-900"><?php echo chapterTitle($chapterDetails); ?></h1>
                <p class="text-gray-700">
                    <span>
                        <svg class="w-5 h-5 inline-block mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5s3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253" />
                        </svg>
                        Truyện: 
                        <a href="/manga.php?id=<?php echo $mangaId; ?>" class="text-gray-900 hover:text-indigo-500 text-xl font-semibold">
                            <?php echo htmlspecialchars($manga['title']); ?>
                        </a>
                    </span><br>
                    <span>
                        <svg class="w-5 h-5 inline-block mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Cập nhật: <?php echo date('d/m/Y', strtotime($chapterDetails['updatedAt'])); ?>
                    </span>
                </p><br>
                <p class="text-gray-700 text-center">
                    Nếu không xem được truyện vui lòng đổi <span class="font-semibold text-yellow-400">"SERVER"</span> bên dưới
                </p>
            </div>

            <!-- Nút Server -->
            <div class="flex justify-center space-x-3 mb-4">
                <button class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition">Server 1</button>
                <button class="bg-indigo-500 text-white px-4 py-2 rounded hover:bg-indigo-400 transition">Server VIP</button>
                <button class="bg-red-500 text-white px-4 py-2 rounded flex items-center space-x-2 hover:bg-red-600 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.721-1.36 3.486 0l6.518 11.6c.75 1.334-.213 3-1.743 3H3.482c-1.53 0-2.493-1.666-1.743-3l6.518-11.6zM11 14a1 1 0 11-2 0 1 1 0 012 0zm-1-2a1 1 0 01-1-1V7a1 1 0 112 0v4a1 1 0 01-1 1z" clip-rule="evenodd" />
                    </svg>
                    <span>Báo lỗi</span>
                </button>
            </div>

            <!-- Thanh điều hướng -->
            <div class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg">
                <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                    <div class="flex items-center justify-center space-x-2">
                        <svg class="w-4 h-4 fill-current text-indigo-500 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/>
                        </svg>
                        <span class="text-sm md:text-base text-center">Sử dụng mũi tên trái (←) hoặc phải (→) để chuyển chapter</span>
                    </div>
                    <div class="flex justify-center space-x-2">
                        <button id="prevChapterBtn" <?php echo $prevChapter ? '' : 'disabled'; ?> 
                                onclick="window.location.href='/chapter/<?php echo $prevChapter; ?>'"
                                class="bg-indigo-500 text-white px-2 py-1 rounded hover:bg-indigo-400 transition disabled:bg-gray-400 disabled:cursor-not-allowed text-sm">
                            ← Chap trước
                        </button>
                        <button id="nextChapterBtn" <?php echo $nextChapter ? '' : 'disabled'; ?> 
                                onclick="window.location.href='/chapter/<?php echo $nextChapter; ?>'"
                                class="bg-indigo-500 text-white px-2 py-1 rounded hover:bg-indigo-400 transition disabled:bg-gray-400 disabled:cursor-not-allowed text-sm">
                            Chap sau →
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="min-w-0 relative mt-2" id="reader">
            <div class="overflow-x-auto flex flex-col items-center h-full select-none bg-white gap-0" id="long-strip">
                <?php if (empty($chapterData['pages'])): ?>
                    <p class="text-red-500">Không có ảnh nào được tìm thấy cho chương này!</p>
                <?php else: ?>
                    <?php foreach ($chapterData['pages'] as $index => $page): ?>
                        <span class="block overflow-hidden">
                            <img 
                                src="<?php echo htmlspecialchars($page); ?>" 
                                alt="Trang <?php echo $index + 1; ?>" 
                                class="h-auto w-auto mx-auto max-h-screen rounded-sm"
                                loading="lazy"
                                onerror="this.onerror=null; this.src='/public/images/loading.png';"
                            >
                        </span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <br><hr class="border-gray-600"><br>
        <div class="flex justify-center space-x-3 mb-4 flex-nowrap">
            <?php if ($prevChapter): ?>
                <a href="/chapter/<?php echo $prevChapter; ?>" class="bg-indigo-500 text-white px-2 py-1 sm:px-4 sm:py-2 rounded-md hover:bg-indigo-600 transition-colors text-center flex items-center justify-center gap-1 sm:gap-2 text-sm sm:text-lg shrink-0">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span>Chương trước</span>
                </a>
            <?php endif; ?>
            <a href="/manga.php?id=<?php echo $mangaId; ?>" class="bg-indigo-500 text-white px-2 py-1 sm:px-4 sm:py-2 rounded-md hover:bg-indigo-600 transition-colors text-center flex items-center justify-center gap-1 sm:gap-2 text-sm sm:text-lg shrink-0">
                <svg class="w-4 h-4 sm:w-5 sm:h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7m-9 2v10a1 1 0 001 1h2a1 1 0 001-1v-4h2v4a1 1 0 001-1V10m-4-4l4 4" />
                </svg>
                <span>Quay lại chi tiết truyện</span>
            </a>
            <?php if ($nextChapter): ?>
                <a href="/chapter/<?php echo $nextChapter; ?>" class="bg-indigo-500 text-white px-2 py-1 sm:px-4 sm:py-2 rounded-md hover:bg-indigo-600 transition-colors text-center flex items-center justify-center gap-1 sm:gap-2 text-sm sm:text-lg shrink-0">
                    <span>Chương tiếp theo</span>
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            <?php endif; ?>
        </div>

        <div id="chapter-nav" class="fixed bottom-0 left-1/2 transform -translate-x-1/2 z-10 w-full md:w-auto md:-translate-y-2 md:rounded-lg bg-white border-none flex items-center justify-center p-2 gap-2 overflow-x-auto shadow-lg">
            <a href="/manga.php?id=<?php echo $mangaId; ?>" 
            class="bg-green-500 text-white p-2 rounded-md hover:bg-green-600 transition-colors shrink-0" 
            title="Chi tiết truyện">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5s3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253" />
                </svg>
            </a>
            <a href="<?php echo $prevChapter ? '/chapter/' . $prevChapter : '#'; ?>" 
            class="bg-gray-200 text-gray-700 p-2 rounded-md hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed shrink-0" 
            <?php echo !$prevChapter ? 'disabled' : ''; ?>>
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <button id="chapter-list-btn" 
                    class="bg-gray-200 text-gray-900 p-2 rounded-md min-w-0 md:min-w-48 max-w-[120px] md:max-w-none text-center overflow-hidden text-ellipsis whitespace-nowrap">
                <?php echo chapterTitle($chapterDetails); ?>
            </button>
            <a href="<?php echo $nextChapter ? '/chapter/' . $nextChapter : '#'; ?>" 
            class="bg-gray-200 text-gray-700 p-2 rounded-md hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed shrink-0" 
            <?php echo !$nextChapter ? 'disabled' : ''; ?>>
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
            <button id="settings-btn" class="bg-gray-200 text-gray-700 p-2 rounded-md hover:bg-gray-300 shrink-0">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37 1 .608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </button>     
            <button class="bg-gray-200 text-gray-700 p-2 rounded-md hover:bg-gray-300 disabled:opacity-50 shrink-0" id="scroll-top-btn">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 11l7-7 7 7M5 19l7-7 7 7" />
                </svg>
            </button>
        </div>

        <!-- Modal danh sách chapter -->
        <div id="chapter-list-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-20 flex items-center justify-center">
            <div class="bg-white rounded-lg w-full max-w-md p-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Danh sách chương</h3>
                    <button id="close-chapter-list" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="max-h-64 overflow-y-auto">
                    <ul class="space-y-2">
                        <?php foreach ($chapters as $chapter): ?>
                            <li>
                                <a href="/chapter/<?php echo $chapter['id']; ?>" 
                                class="block px-4 py-2 rounded-md <?php echo $chapter['id'] === $chapterId ? 'bg-gray-300 text-gray-900' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                                    <?php echo $chapter['chapter'] !== 'Oneshot' ? "Chương {$chapter['chapter']}" : 'Oneshot'; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="mt-4 flex justify-end">
                    <button id="close-chapter-list-footer" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">Đóng</button>
                </div>
            </div>
        </div>

        <!-- Settings Dialog -->
        <div id="settings-dialog" class="hidden fixed inset-0 bg-black bg-opacity-50 z-20 flex items-center justify-center">
            <div class="bg-white p-4 rounded-lg w-full max-w-md">
                <div class="grid grid-cols-1 gap-2">
                    <!-- Kiểu đọc -->
                    <div class="space-y-2">
                        <label class="font-semibold text-gray-900">Kiểu đọc</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button class="border border-gray-300 p-2 rounded-md flex items-center justify-center gap-2 hover:bg-gray-100" data-reader-type="single">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-3-9v18" />
                                </svg>
                                <span>Từng trang</span>
                            </button>
                            <button class="border border-gray-300 p-2 rounded-md flex items-center justify-center gap-2 bg-blue-100 border-blue-500" data-reader-type="long-strip">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m-4-4H4m16 0h-4" />
                                </svg>
                                <span>Trượt dọc</span>
                            </button>
                        </div>
                    </div>
                    <!-- Khoảng cách giữa các ảnh -->
                    <!-- <div class="space-y-2">
                        <label class="font-semibold text-gray-900">Khoảng cách giữa các ảnh (px)</label>
                        <div class="flex gap-2">
                            <input type="number" min="0" value="0" id="image-gap" class="border border-gray-300 p-2 rounded-md w-full bg-white text-gray-900" />
                            <button id="reset-gap" class="border border-gray-300 p-2 rounded-md hover:bg-gray-100">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </button>
                        </div>
                    </div> -->
                    <!-- Ảnh truyện -->
                    <div class="space-y-2">
                        <label class="font-semibold text-gray-900">Ảnh truyện</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button class="border border-gray-300 p-2 rounded-md flex items-center justify-center gap-2 bg-blue-100 border-blue-500" data-image-fit="height">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m-8-8h16" />
                                </svg>
                                <span>Vừa chiều dọc</span>
                            </button>
                            <button class="border border-gray-300 p-2 rounded-md flex items-center justify-center gap-2 hover:bg-gray-100" data-image-fit="width">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12h16m-8-8v16" />
                                </svg>
                                <span>Vừa chiều ngang</span>
                            </button>
                        </div>
                    </div>
                    <!-- Thanh Header -->
                    <div class="space-y-2">
                        <label class="font-semibold text-gray-900">Thanh Header</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button class="border border-gray-300 p-2 rounded-md flex items-center justify-center gap-2 hover:bg-gray-100" data-header="hide">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h18v18H3V3z" />
                                </svg>
                                <span>Ẩn</span>
                            </button>
                            <button class="border border-gray-300 p-2 rounded-md flex items-center justify-center gap-2 bg-blue-100 border-blue-500" data-header="show">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 3h18v18H3V3z" />
                                </svg>
                                <span>Hiện</span>
                            </button>
                        </div>
                    </div>
                    <!-- Giao diện (Theme) -->
                    <div class="space-y-2">
                        <label class="font-semibold text-gray-900">Giao diện</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button class="border border-gray-300 p-2 rounded-md flex items-center justify-center gap-2 bg-blue-100 border-blue-500" data-theme="light">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span>Sáng</span>
                            </button>
                            <button class="border border-gray-300 p-2 rounded-md flex items-center justify-center gap-2 hover:bg-gray-100" data-theme="dark">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                </svg>
                                <span>Tối</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.readerData = {
            mangaId: '<?php echo $mangaId; ?>',
            chapterId: '<?php echo $chapterId; ?>',
            chapterNumber: '<?php echo $chapterDetails['chapter'] ?? 'Oneshot'; ?>',
            chapterTitle: '<?php echo htmlspecialchars($chapterDetails['title'] ?? ''); ?>',
            mangaTitle: '<?php echo htmlspecialchars($manga['title']); ?>',
            isLoggedIn: <?php echo isset($_SESSION['user']) ? 'true' : 'false'; ?>
        };
    </script>
    <script src="/js/main.js"></script>
    <script src="/js/search.js"></script>
    <script src="/js/reader.js"></script>
</body>
</html>