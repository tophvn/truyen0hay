<?php
require_once __DIR__ . '/src/session.php';
require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/config/database.php';

$history = [];
if (isset($_SESSION['user'])) {
    // Người dùng đã đăng nhập: Lấy từ cơ sở dữ liệu
    $userId = $_SESSION['user']['user_id'];
    $stmt = $conn->prepare("
        SELECT manga_id, chapter_id, chapter_number, chapter_title, manga_title, read_at
        FROM user_history
        WHERE user_id = ?
        ORDER BY read_at DESC
        LIMIT 50
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $history = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Sử Đọc - <?php echo SITE_NAME; ?></title>
    <link href="/public/images/logo.png" rel="icon">
    <link href="/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .history-card { 
            transition: transform 0.2s; 
            max-width: 150px; /* Giảm chiều rộng tối đa của card */
        }
        .history-card:hover { transform: scale(1.03); }
        .cover-image { 
            aspect-ratio: 2/3; 
            object-fit: cover; 
            height: 200px; /* Giảm chiều cao ảnh bìa */
        }
        .history-card h3 { 
            font-size: 0.875rem; /* text-sm thay vì text-base */
            line-height: 1.25rem;
        }
        .history-card p { 
            font-size: 0.75rem; /* text-xs cho các dòng nhỏ hơn */
            line-height: 1rem;
        }
        .history-card .p-3 { 
            padding: 0.5rem; /* Giảm padding từ p-3 (12px) xuống p-2 (8px) */
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen">
    <?php include __DIR__ . '/includes/navbar.php'; ?>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="container mx-auto p-4 pt-20">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Lịch Sử Đọc</h1>
            <button id="clearHistoryBtn" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">Xóa Lịch Sử</button>
        </div>

        <div id="historyContainer" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4">
            <?php if (isset($_SESSION['user'])): ?>
                <?php if (empty($history)): ?>
                    <p class="col-span-full text-gray-700 dark:text-gray-300 text-center text-lg">Bạn chưa đọc truyện nào!</p>
                <?php else: ?>
                    <?php foreach ($history as $entry): ?>
                        <div class="history-card bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden flex flex-col">
                            <a href="/chapter/<?php echo htmlspecialchars($entry['chapter_id']); ?>" class="block">
                                <img src="/cover_proxy.php?manga_id=<?php echo htmlspecialchars($entry['manga_id']); ?>&cover_file=<?php echo htmlspecialchars(getMangaById($entry['manga_id'])['cover'] ?? 'default_cover.png'); ?>" 
                                     alt="<?php echo htmlspecialchars($entry['manga_title']); ?>" 
                                     class="w-full cover-image rounded-t-lg"
                                     loading="lazy"
                                     onerror="this.onerror=null; this.src='/public/images/default_cover.jpg';">
                            </a>
                            <div class="p-3 flex-grow">
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100 line-clamp-2">
                                    <a href="/manga.php?id=<?php echo htmlspecialchars($entry['manga_id']); ?>" class="hover:text-blue-600">
                                        <?php echo htmlspecialchars($entry['manga_title']); ?>
                                    </a>
                                </h3>
                                <p class="text-gray-600 dark:text-gray-400 mt-1">
                                    <?php echo htmlspecialchars($entry['chapter_number']); ?>
                                    <?php echo $entry['chapter_title'] ? ' - ' . htmlspecialchars($entry['chapter_title']) : ''; ?>
                                </p>
                                <p class="text-gray-500 dark:text-gray-400 mt-1">
                                    <?php echo date('d/m/Y H:i', strtotime($entry['read_at'])); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php else: ?>
                <!-- Dữ liệu từ localStorage sẽ được hiển thị bằng JavaScript -->
                <p id="noHistoryMessage" class="col-span-full text-gray-700 dark:text-gray-300 text-center text-lg hidden">Bạn chưa đọc truyện nào!</p>
            <?php endif; ?>
        </div>
    </main>

    <script src="/js/main.js"></script>
    <script src="/js/search.js"></script>
    <script>
        // Xử lý lịch sử cho người dùng chưa đăng nhập
        <?php if (!isset($_SESSION['user'])): ?>
            const history = JSON.parse(localStorage.getItem('readingHistory') || '[]');
            const container = document.getElementById('historyContainer');
            const noHistoryMessage = document.getElementById('noHistoryMessage');

            if (history.length === 0) {
                noHistoryMessage.classList.remove('hidden');
            } else {
                history.forEach(entry => {
                    const card = document.createElement('div');
                    card.className = 'history-card bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden flex flex-col';
                    card.innerHTML = `
                        <a href="/chapter/${entry.chapter_id}" class="block">
                            <img src="/cover_proxy.php?manga_id=${entry.manga_id}&cover_file=${entry.manga_id ? 'default_cover.png' : 'default_cover.png'}" 
                                 alt="${entry.manga_title}" 
                                 class="w-full cover-image rounded-t-lg" 
                                 loading="lazy" 
                                 onerror="this.onerror=null; this.src='/public/images/default_cover.jpg';">
                        </a>
                        <div class="p-3 flex-grow">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100 line-clamp-2">
                                <a href="/manga.php?id=${entry.manga_id}" class="hover:text-blue-600">${entry.manga_title}</a>
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400 mt-1">
                                ${entry.chapter_number}${entry.chapter_title ? ' - ' + entry.chapter_title : ''}
                            </p>
                            <p class="text-gray-500 dark:text-gray-400 mt-1">
                                ${new Date(entry.read_at).toLocaleString('vi-VN')}
                            </p>
                        </div>
                    `;
                    container.appendChild(card);
                });
            }

            document.getElementById('clearHistoryBtn').addEventListener('click', () => {
                if (confirm('Bạn có chắc muốn xóa lịch sử đọc?')) {
                    localStorage.removeItem('readingHistory');
                    container.innerHTML = '';
                    noHistoryMessage.classList.remove('hidden');
                }
            });
        <?php else: ?>
            // Xóa lịch sử cho người dùng đã đăng nhập
            document.getElementById('clearHistoryBtn').addEventListener('click', () => {
                if (confirm('Bạn có chắc muốn xóa lịch sử đọc?')) {
                    fetch('/history.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'clear_history=1'
                    }).then(() => location.reload());
                }
            });
        <?php endif; ?>

        // Xử lý POST để xóa lịch sử từ cơ sở dữ liệu
        <?php if (isset($_POST['clear_history']) && isset($_SESSION['user'])): ?>
            <?php
            $stmt = $conn->prepare("DELETE FROM user_history WHERE user_id = ?");
            $stmt->bind_param("i", $_SESSION['user']['user_id']);
            $stmt->execute();
            ?>
        <?php endif; ?>
    </script>
</body>
</html>