<?php
require_once __DIR__ . '/../lib/functions.php';
require_once __DIR__ . '/../src/session.php';
require_once __DIR__ . '/../config/database.php'; 

// Đặt múi giờ cho PHP
date_default_timezone_set('Asia/Ho_Chi_Minh');

$r18 = isset($_SESSION['r18']) ? $_SESSION['r18'] : R18;
$languages = TRANSLATED_LANGUAGES;

function timeAgo($datetime) {
    if (empty($datetime)) {
        return "Chưa có chapter";
    }

    $now = new DateTime();
    $past = new DateTime($datetime);
    $interval = $now->diff($past);

    // Nếu thời gian trong tương lai (do lỗi múi giờ), trả về "vừa xong"
    if ($past > $now) {
        return "vừa xong";
    }

    if ($interval->y > 0) {
        return "{$interval->y} năm trước";
    } elseif ($interval->m > 0) {
        return "{$interval->m} tháng trước";
    } elseif ($interval->d > 0) {
        return "{$interval->d} ngày trước";
    } elseif ($interval->h > 0) {
        return "{$interval->h} giờ trước";
    } elseif ($interval->i > 0) {
        return "{$interval->i} phút trước";
    } else {
        return "vừa xong";
    }
}

$stmt = $conn->prepare("
    SELECT m.manga_id, m.title, m.cover, m.cover_url, MAX(c.updated_at) as latest_chapter_update
    FROM manga m
    LEFT JOIN chapters c ON m.manga_id = c.manga_id
    WHERE m.is_manual = 1 
    AND (m.content_rating = 'safe' OR m.content_rating = ?)
    GROUP BY m.manga_id, m.title, m.cover, m.cover_url
    ORDER BY latest_chapter_update DESC
    LIMIT 12
");
$contentRating = $r18 ? 'pornographic' : 'safe';
$stmt->bind_param("s", $contentRating);
$stmt->execute();
$hotMangas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="mb-8">
    <hr class="w-9 h-1 bg-blue-500 border-none">
    <h1 class="text-2xl font-bold uppercase">Truyện Up</h1>
    <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
        <?php if (empty($hotMangas)): ?>
            <p class="col-span-full text-gray-500 dark:text-gray-400">Không có truyện thủ công nào.</p>
        <?php else: ?>
            <?php foreach ($hotMangas as $manga): ?>
                <?php
                $mangaId = htmlspecialchars($manga['manga_id'] ?? '#');
                $mangaTitle = htmlspecialchars($manga['title'] ?? 'Không có tiêu đề');
                $cover = htmlspecialchars($manga['cover_url'] ?? '/public/images/loading.png');
                $updatedAt = timeAgo($manga['latest_chapter_update']);
                ?>
                <a href="truyen.php?id=<?php echo $mangaId; ?>" class="relative rounded-sm shadow-md transition-colors duration-200 w-full border-none manga-card">
                    <div class="relative w-full aspect-[5/7] overflow-hidden">
                        <img 
                            src="<?php echo $cover; ?>" 
                            alt="<?php echo $mangaTitle; ?>" 
                            class="absolute inset-0 w-full h-full rounded-sm object-cover object-center"
                            loading="lazy"
                            onerror="this.src='/public/images/loading.png'">
                        <span class="time-overlay"><?php echo $updatedAt; ?></span>
                    </div>
                    <div class="title-overlay h-[40%] max-h-full flex items-end">
                        <p class="text-base font-semibold line-clamp-2 hover:line-clamp-none text-white drop-shadow-sm"><?php echo $mangaTitle; ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>