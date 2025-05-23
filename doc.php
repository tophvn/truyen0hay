<?php
require_once __DIR__ . '/src/session.php';
require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/config/database.php';

$mangaId = $_GET['id'] ?? '';
$chapterId = $_GET['chapter_id'] ?? '';

if (empty($mangaId) || empty($chapterId)) {
    header('Location: /index.php');
    exit;
}

// Lấy thông tin manga
$stmt = $conn->prepare("SELECT * FROM manga WHERE manga_id = ?");
$stmt->bind_param("s", $mangaId);
$stmt->execute();
$manga = $stmt->get_result()->fetch_assoc();

if (!$manga || !$manga['is_manual']) {
    // Nếu không phải manga thủ công, chuyển hướng đến chapter.php
    if ($manga && !$manga['is_manual']) {
        header("Location: /chapter/$chapterId");
        exit;
    }
    header('Location: /index.php');
    exit;
}

// Lấy thông tin chương
$stmt = $conn->prepare("SELECT * FROM chapters WHERE id = ? AND manga_id = ?");
$stmt->bind_param("ss", $chapterId, $mangaId);
$stmt->execute();
$chapter = $stmt->get_result()->fetch_assoc();

if (!$chapter) {
    header('Location: /truyen.php?id=' . $mangaId);
    exit;
}

$chapterNumber = $chapter['chapter_number'];
$pages = json_decode($chapter['pages'], true);

// Lấy danh sách chương để điều hướng
$stmt = $conn->prepare("SELECT id, chapter_number FROM chapters WHERE manga_id = ? ORDER BY chapter_number ASC");
$stmt->bind_param("s", $mangaId);
$stmt->execute();
$chaptersResult = $stmt->get_result();
$chapters = $chaptersResult->fetch_all(MYSQLI_ASSOC);

$chapterIndex = array_search($chapterId, array_column($chapters, 'id'));
$prevChapter = $chapterIndex > 0 ? $chapters[$chapterIndex - 1]['id'] : null;
$nextChapter = $chapterIndex < count($chapters) - 1 ? $chapters[$chapterIndex + 1]['id'] : null;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Đọc <?php echo htmlspecialchars($manga['title']); ?> - Chương <?php echo htmlspecialchars($chapterNumber); ?> - <?php echo SITE_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/public/images/logo.png" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include 'includes/navbar.php'; ?>
    <main class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4 text-gray-900">
            Đọc truyện: <?php echo htmlspecialchars($manga['title']); ?> - Chương <?php echo htmlspecialchars($chapterNumber); ?>
        </h1>
        <div class="space-y-4">
            <?php foreach ($pages as $index => $page): ?>
                <img 
                    src="<?php echo htmlspecialchars($page); ?>" 
                    alt="Trang <?php echo $index + 1; ?>" 
                    class="h-auto w-auto mx-auto !max-h-screen"
                    loading="lazy"
                    onerror="this.onerror=null; this.src='/public/images/loading.png';"
                >
            <?php endforeach; ?>
        </div>
        <div class="mt-4 flex flex-col sm:flex-row justify-between gap-4">
            <?php if ($prevChapter): ?>
                <a href="/doc.php?id=<?php echo $mangaId; ?>&chapter_id=<?php echo $prevChapter; ?>" 
                   class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors text-center">
                    Chương trước
                </a>
            <?php endif; ?>
            <a href="/truyen.php?id=<?php echo $mangaId; ?>" 
               class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors text-center">
                Quay lại danh sách chương
            </a>
            <?php if ($nextChapter): ?>
                <a href="/doc.php?id=<?php echo $mangaId; ?>&chapter_id=<?php echo $nextChapter; ?>" 
                   class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors text-center">
                    Chương sau
                </a>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>