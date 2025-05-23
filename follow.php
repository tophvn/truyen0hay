<?php
require_once __DIR__ . '/src/session.php';
require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: /src/auth/login.php');
    exit;
}

$userId = $_SESSION['user']['user_id'];

// Xử lý xóa truyện
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_manga_id'])) {
        $mangaId = $_POST['delete_manga_id'];
        $stmt = $conn->prepare("DELETE FROM user_follows WHERE user_id = ? AND manga_id = ?");
        $stmt->bind_param("is", $userId, $mangaId);
        $stmt->execute();
    } elseif (isset($_POST['delete_all'])) {
        $stmt = $conn->prepare("DELETE FROM user_follows WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Lấy danh sách manga_id và cover_url từ user_follows
$stmt = $conn->prepare("
    SELECT manga_id, followed_at, last_read_chapter, last_read_at, cover_url
    FROM user_follows 
    WHERE user_id = ? 
    ORDER BY followed_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$followedManga = $result->fetch_all(MYSQLI_ASSOC);

// Kiểm tra và cập nhật cover_url nếu chưa có
foreach ($followedManga as &$manga) {
    if (empty($manga['cover_url']) || $manga['cover_url'] === 'default_cover.png') {
        $apiResponse = callMangadexApi("/manga/{$manga['manga_id']}", ['includes' => ['cover_art']]);
        if (!isset($apiResponse['data']) || !isset($apiResponse['data']['attributes'])) {
            error_log("API MangaDex error for manga_id {$manga['manga_id']}: " . json_encode($apiResponse));
            $manga['title'] = 'Unknown';
            $manga['cover_url'] = '/public/images/default_cover.jpg'; // Đường dẫn đầy đủ
        } else {
            $attributes = $apiResponse['data']['attributes'];
            $relationships = $apiResponse['data']['relationships'] ?? [];

            $manga['title'] = $attributes['title']['en'] ?? $attributes['title']['ja'] ?? 'Unknown';

            $coverFileName = null;
            foreach ($relationships as $rel) {
                if ($rel['type'] === 'cover_art' && isset($rel['attributes']['fileName'])) {
                    $coverFileName = $rel['attributes']['fileName'];
                    break;
                }
            }
            // Lưu đường dẫn đầy đủ vào cover_url
            if ($coverFileName) {
                $manga['cover_url'] = "https://uploads.mangadex.org/covers/{$manga['manga_id']}/{$coverFileName}.256.jpg";
            } else {
                $manga['cover_url'] = '/public/images/default_cover.jpg'; // Đường dẫn đầy đủ
            }

            // Cập nhật cover_url vào cơ sở dữ liệu
            $stmt = $conn->prepare("UPDATE user_follows SET cover_url = ? WHERE user_id = ? AND manga_id = ?");
            $stmt->bind_param("sis", $manga['cover_url'], $userId, $manga['manga_id']);
            $stmt->execute();
        }
    } else {
        // Nếu cover_url đã có, kiểm tra xem nó có phải là default không
        if (strpos($manga['cover_url'], 'default_cover') !== false) {
            $manga['cover_url'] = '/public/images/default_cover.jpg';
        }
        $apiResponse = callMangadexApi("/manga/{$manga['manga_id']}");
        $manga['title'] = $apiResponse['data']['attributes']['title']['en'] ?? $apiResponse['data']['attributes']['title']['ja'] ?? 'Unknown';
    }
}
unset($manga);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <meta name="robots" content="index, follow">

    <!-- Tiêu đề trang -->
    <title>Truyện Theo Dõi - <?php echo defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY'; ?></title>

    <!-- Meta Description -->
    <meta name="description" content="<?php 
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
        echo "Xem danh sách truyện tranh bạn đang theo dõi tại $siteName. Cập nhật chương mới nhất, đọc manga, manhwa, manhua online!";
    ?>">

    <!-- Meta Keywords -->
    <meta name="keywords" content="<?php 
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
        echo "truyện theo dõi, danh sách truyện, manga theo dõi, manhwa theo dõi, manhua theo dõi, đọc truyện online, $siteName";
    ?>">

    <!-- Canonical URL -->
    <link rel="canonical" href="<?php 
        echo 'https://' . $_SERVER['HTTP_HOST'] . '/followed.php';
    ?>">

    <!-- Favicon -->
    <link href="/public/images/logo.png" rel="icon" type="image/png">

    <!-- Open Graph (OG) Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php 
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
        echo "Truyện Theo Dõi - $siteName";
    ?>">
    <meta property="og:description" content="<?php 
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
        echo "Xem danh sách truyện tranh bạn đang theo dõi tại $siteName. Cập nhật chương mới nhất, đọc manga, manhwa, manhua online!";
    ?>">
    <meta property="og:url" content="<?php 
        echo 'https://' . $_SERVER['HTTP_HOST'] . '/followed.php';
    ?>">
    <meta property="og:image" content="<?php 
        echo 'https://' . $_SERVER['HTTP_HOST'] . '/public/images/logo.png';
    ?>">
    <meta property="og:site_name" content="<?php echo defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY'; ?>">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php 
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
        echo "Truyện Theo Dõi - $siteName";
    ?>">
    <meta name="twitter:description" content="<?php 
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
        echo "Xem danh sách truyện tranh bạn đang theo dõi tại $siteName. Cập nhật chương mới nhất, đọc manga, manhwa, manhua online!";
    ?>">
    <meta name="twitter:image" content="<?php 
        echo 'https://' . $_SERVER['HTTP_HOST'] . '/public/images/logo.png';
    ?>">

    <!-- Structured Data (JSON-LD) cho trang danh sách truyện theo dõi -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "CollectionPage",
        "name": "Truyện Theo Dõi",
        "description": "<?php 
            $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
            echo "Xem danh sách truyện tranh bạn đang theo dõi tại $siteName. Cập nhật chương mới nhất, đọc manga, manhwa, manhua online!";
        ?>",
        "url": "<?php echo 'https://' . $_SERVER['HTTP_HOST'] . '/followed.php'; ?>",
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
    <style>
        .manga-card { transition: transform 0.2s; }
        .manga-card:hover { transform: scale(1.03); }
        .cover-image { aspect-ratio: 2/3; object-fit: cover; }
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            overflow-x: hidden;
        }
        header {
            transition: width 0.3s ease, background-color 0.3s ease;
            z-index: 60;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
        }
        header.bg-transparent {
            background-color: transparent;
        }
        header.bg-background {
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
        }
        .dark header.bg-background {
            background-color: rgba(17, 24, 39, 0.9);
        }
        header.sidebar-open {
            width: calc(100% - 280px);
        }
        #sidebar {
            position: fixed;
            top: 0;
            right: 0;
            width: 280px;
            height: 100%;
            background: white;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            z-index: 50;
        }
        .dark #sidebar {
            background: #1f2937;
        }
        #sidebar.open {
            transform: translateX(0);
        }
        #sidebar-overlay {
            display: none;
        }
        #sidebar.open + #sidebar-overlay {
            display: none; 
        }
        #main-content {
            transition: width 0.3s ease;
            width: 100%;
        }
        #main-content.sidebar-open {
            width: calc(100% - 280px);
        }
        @media (max-width: 768px) {
            #main-content.sidebar-open {
                width: 100%; 
            }
            header.sidebar-open {
                width: 100%;
            }
            #sidebar {
                width: 280px; 
                z-index: 50;
            }
            #sidebar.open + #sidebar-overlay {
                display: block;
            }
        }
        @media (max-width: 640px) {
            #sidebar {
                width: 260px;
            }
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen">
    <?php include __DIR__ . '/includes/navbar.php'; ?>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="container mx-auto p-4 pt-20">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Truyện Theo Dõi</h1>
            <?php if (!empty($followedManga)): ?>
                <button onclick="if(confirm('Bạn có chắc muốn xóa tất cả truyện?')) document.getElementById('deleteAllForm').submit();" 
                        class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">
                    Xóa Tất Cả
                </button>
                <form id="deleteAllForm" method="POST" class="hidden">
                    <input type="hidden" name="delete_all" value="1">
                </form>
            <?php endif; ?>
        </div>

        <?php if (empty($followedManga)): ?>
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow text-center">
                <p class="text-gray-700 dark:text-gray-300 text-lg">Bạn chưa theo dõi truyện nào!</p>
                <a href="/index.php" class="mt-4 inline-block bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Tìm truyện ngay</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
                <?php foreach ($followedManga as $manga): ?>
                    <div class="manga-card bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden flex flex-col">
                        <a href="/manga.php?id=<?php echo htmlspecialchars($manga['manga_id']); ?>" class="block">
                            <img src="<?php 
                                // Nếu cover_url là ảnh mặc định, sử dụng đường dẫn trực tiếp
                                if (strpos($manga['cover_url'], 'default_cover') !== false) {
                                    echo htmlspecialchars($manga['cover_url']);
                                } else {
                                    // Nếu không, gọi proxy-0hay.php
                                    $coverFile = basename(parse_url($manga['cover_url'], PHP_URL_PATH));
                                    echo htmlspecialchars("/proxy-0hay.php?manga_id=" . $manga['manga_id'] . "&cover_file=" . $coverFile);
                                }
                            ?>" 
                                 alt="<?php echo htmlspecialchars($manga['title']); ?>" 
                                 class="w-full cover-image rounded-t-lg"
                                 loading="lazy"
                                 onerror="this.onerror=null; this.src='/public/images/default_cover.jpg';">
                        </a>
                        <div class="p-3 flex-grow">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 line-clamp-2">
                                <a href="/manga.php?id=<?php echo htmlspecialchars($manga['manga_id']); ?>" class="hover:text-blue-600">
                                    <?php echo htmlspecialchars($manga['title']); ?>
                                </a>
                            </h3>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                Theo dõi: <?php echo date('d/m/Y', strtotime($manga['followed_at'])); ?>
                            </p>
                            <?php if ($manga['last_read_chapter']): ?>
                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                    Chương <?php echo htmlspecialchars($manga['last_read_chapter']); ?> 
                                    (<?php echo date('d/m/Y', strtotime($manga['last_read_at'])); ?>)
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="p-3 pt-0">
                            <button onclick="if(confirm('Xóa <?php echo htmlspecialchars($manga['title']); ?> khỏi danh sách?')) document.getElementById('delete-<?php echo htmlspecialchars($manga['manga_id']); ?>').submit();" 
                                    class="w-full bg-red-500 text-white px-2 py-1 rounded-md hover:bg-red-600 text-sm">
                                Xóa
                            </button>
                            <form id="delete-<?php echo htmlspecialchars($manga['manga_id']); ?>" method="POST" class="hidden">
                                <input type="hidden" name="delete_manga_id" value="<?php echo htmlspecialchars($manga['manga_id']); ?>">
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    <?php include 'includes/footer.php'; ?>
    <script src="/js/main.js"></script>
    <script src="/js/search.js"></script>
</body>
</html>