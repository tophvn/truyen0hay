<?php
require_once __DIR__ . '/lib/functions.php';

$query = $_GET['q'] ?? '';
$languages = TRANSLATED_LANGUAGES; // ['vi']
$r18 = R18; // false

function searchManga($query, $r18) {
    $params = [
        'title' => $query,
        'limit' => 20, // Hiển thị 20 kết quả
        'includes' => ['cover_art', 'author', 'artist'],
        'contentRating' => $r18 ? ['safe', 'suggestive', 'erotica', 'pornographic'] : ['safe', 'suggestive'],
    ];
    $data = callMangadexApi('/manga', $params);
    if (!$data || !isset($data['data'])) return [];
    return array_map('parseManga', $data['data']);
}

$searchResults = !empty($query) ? searchManga($query, $r18) : [];
?>

<!DOCTYPE html>
<html>
<head>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <meta name="robots" content="index, follow">

    <!-- Tiêu đề trang -->
    <title>
        <?php 
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
        $query = isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '';
        if (!empty($query)) {
            echo "Kết Quả Tìm Kiếm: $query - $siteName";
        } else {
            echo "Kết Quả Tìm Kiếm - $siteName";
        }
        ?>
    </title>

    <!-- Meta Description -->
    <meta name="description" content="<?php 
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
        $query = isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '';
        if (!empty($query)) {
            echo "Tìm kiếm truyện tranh '$query' tại $siteName. Khám phá manga, manhwa, manhua phù hợp với sở thích của bạn, cập nhật hàng ngày!";
        } else {
            echo "Tìm kiếm truyện tranh tại $siteName. Khám phá bộ sưu tập manga, manhwa, manhua đa dạng, cập nhật hàng ngày!";
        }
    ?>">

    <!-- Meta Keywords -->
    <meta name="keywords" content="<?php 
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
        $query = isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '';
        if (!empty($query)) {
            echo "tìm kiếm truyện tranh, $query, manga $query, manhwa $query, manhua $query, đọc truyện online, $siteName";
        } else {
            echo "tìm kiếm truyện tranh, manga, manhwa, manhua, đọc truyện online, $siteName";
        }
    ?>">

    <!-- Canonical URL -->
    <link rel="canonical" href="<?php 
        $queryString = isset($_GET['q']) ? '?q=' . urlencode($_GET['q']) : '';
        echo 'https://' . $_SERVER['HTTP_HOST'] . '/search.php' . $queryString;
    ?>">

    <!-- Favicon -->
    <link href="/public/images/logo.png" rel="icon" type="image/png">

    <!-- Open Graph (OG) Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php 
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
        $query = isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '';
        if (!empty($query)) {
            echo "Kết Quả Tìm Kiếm: $query - $siteName";
        } else {
            echo "Kết Quả Tìm Kiếm - $siteName";
        }
    ?>">
    <meta property="og:description" content="<?php 
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
        $query = isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '';
        if (!empty($query)) {
            echo "Tìm kiếm truyện tranh '$query' tại $siteName. Khám phá manga, manhwa, manhua phù hợp với sở thích của bạn, cập nhật hàng ngày!";
        } else {
            echo "Tìm kiếm truyện tranh tại $siteName. Khám phá bộ sưu tập manga, manhwa, manhua đa dạng, cập nhật hàng ngày!";
        }
    ?>">
    <meta property="og:url" content="<?php 
        $queryString = isset($_GET['q']) ? '?q=' . urlencode($_GET['q']) : '';
        echo 'https://' . $_SERVER['HTTP_HOST'] . '/search.php' . $queryString;
    ?>">
    <meta property="og:image" content="<?php 
        echo 'https://' . $_SERVER['HTTP_HOST'] . '/public/images/logo.png';
    ?>">
    <meta property="og:site_name" content="<?php echo defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY'; ?>">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php 
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
        $query = isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '';
        if (!empty($query)) {
            echo "Kết Quả Tìm Kiếm: $query - $siteName";
        } else {
            echo "Kết Quả Tìm Kiếm - $siteName";
        }
    ?>">
    <meta name="twitter:description" content="<?php 
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
        $query = isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '';
        if (!empty($query)) {
            echo "Tìm kiếm truyện tranh '$query' tại $siteName. Khám phá manga, manhwa, manhua phù hợp với sở thích của bạn, cập nhật hàng ngày!";
        } else {
            echo "Tìm kiếm truyện tranh tại $siteName. Khám phá bộ sưu tập manga, manhwa, manhua đa dạng, cập nhật hàng ngày!";
        }
    ?>">
    <meta name="twitter:image" content="<?php 
        echo 'https://' . $_SERVER['HTTP_HOST'] . '/public/images/logo.png';
    ?>">

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SearchResultsPage",
        "name": "Kết Quả Tìm Kiếm",
        "description": "<?php 
            $siteName = defined('SITE_NAME') ? SITE_NAME : 'TRUYEN0HAY';
            $query = isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '';
            if (!empty($query)) {
                echo "Tìm kiếm truyện tranh '$query' tại $siteName. Khám phá manga, manhwa, manhua phù hợp với sở thích của bạn, cập nhật hàng ngày!";
            } else {
                echo "Tìm kiếm truyện tranh tại $siteName. Khám phá bộ sưu tập manga, manhwa, manhua đa dạng, cập nhật hàng ngày!";
            }
        ?>",
        "url": "<?php 
            $queryString = isset($_GET['q']) ? '?q=' . urlencode($_GET['q']) : '';
            echo 'https://' . $_SERVER['HTTP_HOST'] . '/search.php' . $queryString;
        ?>",
        "query": "<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>",
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
    <style>
        header {
            transition: background-color 0.3s ease;
        }
        header.bg-transparent {
            background-color: transparent;
        }
        header.bg-background {
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        .dark header.bg-background {
            background-color: rgba(17, 24, 39, 0.95);
        }
        .search-container {
            position: relative;
            width: 100%;
            max-width: 300px;
        }
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 50;
            max-height: 80vh;
            overflow-y: auto;
            display: none;
        }
        .dark .search-results {
            background: #1f2937;
        }
        .search-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.3);
            z-index: 40;
            display: none;
        }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .hover\:line-clamp-none:hover {
            -webkit-line-clamp: unset;
        }
        .manga-card {
            position: relative;
            width: 100%;
        }
        .manga-card > div:first-child {
            width: 100%;
            aspect-ratio: 5 / 7;
            overflow: hidden;
            position: relative;
        }
        .manga-card img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <?php include 'includes/navbar.php'; ?>

    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold uppercase mb-4">Kết quả tìm kiếm: <?php echo htmlspecialchars($query); ?></h1>
        <?php if (empty($searchResults)): ?>
            <p class="text-gray-500 dark:text-gray-400">Không tìm thấy kết quả nào.</p>
        <?php else: ?>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-3">
                <?php foreach ($searchResults as $manga): ?>
                    <a href="manga.php?id=<?php echo $manga['id']; ?>" 
                       class="relative rounded-sm shadow-md transition-colors duration-200 w-full border-none manga-card">
                        <div class="relative w-full aspect-[5/7] overflow-hidden">
                            <img 
                                src="<?php echo COVER_BASE_URL . '/' . $manga['id'] . '/' . $manga['cover'] . '.512.jpg'; ?>" 
                                alt="<?php echo htmlspecialchars($manga['title']); ?>" 
                                class="absolute inset-0 w-full h-full rounded-sm object-cover object-center"
                                loading="lazy"
                                onerror="this.src='/public/images/loading.png'">
                        </div>
                        <div class="absolute bottom-0 p-2 bg-gradient-to-t from-black w-full rounded-b-sm h-[40%] max-h-full flex items-end">
                            <p class="text-base font-semibold line-clamp-2 hover:line-clamp-none text-white drop-shadow-sm">
                                <?php echo htmlspecialchars($manga['title']); ?>
                            </p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    </div><br>
    <?php include 'includes/footer.php'; ?>
    <script>
        window.addEventListener('scroll', () => {
            const header = document.querySelector('header');
            if (window.scrollY > 0) {
                header.classList.remove('bg-transparent');
                header.classList.add('bg-background');
            } else {
                header.classList.remove('bg-background');
                header.classList.add('bg-transparent');
            }
        });

        const searchInput = document.getElementById('searchInput');
        const searchResults = document.getElementById('searchResults');
        const searchOverlay = document.getElementById('searchOverlay');
        let debounceTimeout;

        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimeout);
            debounceTimeout = setTimeout(() => {
                const query = searchInput.value.trim();
                if (query.length === 0) {
                    searchResults.style.display = 'none';
                    searchOverlay.style.display = 'none';
                    return;
                }

                fetch(`/search-suggestions.php?q=${encodeURIComponent(query)}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        searchResults.innerHTML = '';
                        if (data.length === 0) {
                            searchResults.innerHTML = '<p class="text-gray-500 dark:text-gray-400 p-2">Không có kết quả</p>';
                        } else {
                            data.forEach(manga => {
                                searchResults.innerHTML += `
                                    <a href="manga.php?id=${manga.id}" class="block p-2 bg-white dark:bg-gray-800 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <div class="flex gap-2">
                                            <img src="${manga.cover}" alt="${manga.title}" class="w-14 h-20 object-cover rounded-md">
                                            <div>
                                                <p class="font-bold">${manga.title}</p>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">${manga.status}</p>
                                            </div>
                                        </div>
                                    </a>
                                `;
                            });
                        }
                        searchResults.style.display = 'block';
                        searchOverlay.style.display = 'block';
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        searchResults.innerHTML = '<p class="text-gray-500 dark:text-gray-400 p-2">Lỗi tìm kiếm</p>';
                        searchResults.style.display = 'block';
                        searchOverlay.style.display = 'block';
                    });
            }, 500); 
        });

        document.addEventListener('click', (e) => {
            const searchContainer = document.querySelector('.search-container');
            if (searchContainer && !searchContainer.contains(e.target)) {
                searchResults.style.display = 'none';
                searchOverlay.style.display = 'none';
            }
        });

        document.getElementById('searchForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const query = searchInput.value.trim();
            if (query) {
                window.location.href = `search.php?q=${encodeURIComponent(query)}`;
            }
        });
    </script>
</body>
</html>