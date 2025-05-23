<?php
require_once __DIR__ . '/../src/session.php';
require_once __DIR__ . '/../lib/functions.php';
require_once __DIR__ . '/../config/database.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['roles'] !== 'admin') {
    header('Location: /login.php');
    exit;
}

$error = null;
$success = null;

$genres = [
    'Oneshot', 'Thriller', 'Award Winning', 'Reincarnation', 'Sci-Fi', 'Time Travel', 'Genderswap', 'Loli',
    'Traditional Games', 'Official Colored', 'Historical', 'Monsters', 'Action', 'Demons', 'Psychological',
    'Ghosts', 'Animals', 'Long Strip', 'Romance', 'Ninja', 'Comedy', 'Mecha', 'Anthology', 'Boys\' Love',
    'Incest', 'Crime', 'Survival', 'Zombies', 'Reverse Harem', 'Sports', 'Superhero', 'Martial Arts',
    'Fan Colored', 'Samurai', 'Magical Girls', 'Mafia', 'Adventure', 'Self-Published', 'Virtual Reality',
    'Office Workers', 'Video Games', 'Post-Apocalyptic', 'Sexual Violence', 'Crossdressing', 'Magic',
    'Girls\' Love', 'Harem', 'Military', 'Wuxia', 'Isekai', '4-Koma', 'Doujinshi', 'Philosophical',
    'Gore', 'Drama', 'Medical', 'School Life', 'Horror', 'Fantasy', 'Villainess', 'Vampires',
    'Delinquents', 'Monster Girls', 'Shota', 'Police', 'Web Comic', 'Slice of Life', 'Aliens',
    'Cooking', 'Supernatural', 'Mystery', 'Adaptation', 'Music', 'Full Color', 'Tragedy', 'Gyaru'
];

// Lấy tab hiện tại từ query parameter
$currentTab = $_GET['tab'] ?? 'create-manga';

// Xử lý form upload manga
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_manga') {
    $title = trim($_POST['title'] ?? '');
    $alt_title = trim($_POST['alt_title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'ongoing';
    $content_rating = $_POST['content_rating'] ?? 'safe';
    $tags = isset($_POST['tags']) ? array_filter(array_map('trim', $_POST['tags'])) : [];
    $author = trim($_POST['author'] ?? '');
    $artist = trim($_POST['artist'] ?? '');
    // Kiểm tra trường bắt buộc
    if (empty($title)) {
        $error = "Vui lòng nhập tên truyện!";
    } else {
        $coverUrl = '';
        $coverFileName = '';

        // Upload ảnh bìa lên Imgur nếu có
        if (!empty($_FILES['cover']['name']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
            $imgurClientId = '3cea3f0e5d5c043'; // Client ID của bạn
            $coverFile = $_FILES['cover']['tmp_name'];
            $imageData = file_get_contents($coverFile);
            $base64Image = base64_encode($imageData);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.imgur.com/3/image');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Client-ID $imgurClientId"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'image' => $base64Image
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($httpCode === 200) {
                $imgurResponse = json_decode($response, true);
                $coverUrl = $imgurResponse['data']['link'];
                $coverFileName = basename($coverUrl);
            } else {
                $error = "Lỗi khi upload ảnh bìa lên Imgur: HTTP Code $httpCode";
                if (!empty($curlError)) {
                    $error .= " (cURL Error: $curlError)";
                }
                if (!empty($response)) {
                    $error .= " (Imgur Response: $response)";
                }
            }
        }

        if (empty($error)) {
            $mangaId = bin2hex(random_bytes(18)); 
            $metaData = [
                'author' => $author ? [$author] : [],
                'artist' => $artist ? [$artist] : [],
                'tags' => $tags
            ];
            $descriptionWithMeta = $description . ($metaData['author'] || $metaData['artist'] || $metaData['tags'] ? "\n" . json_encode($metaData) : '');
            $stmt = $conn->prepare("
                INSERT INTO manga (manga_id, title, alt_title, description, cover, cover_url, status, content_rating, manga_link, is_manual)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $mangaLink = "/truyen.php?id=$mangaId";
            $stmt->bind_param("sssssssss", $mangaId, $title, $alt_title, $descriptionWithMeta, $coverFileName, $coverUrl, $status, $content_rating, $mangaLink);
            $stmt->execute();

            $success = "Tạo truyện thành công! <a href='/truyen.php?id=$mangaId' class='text-blue-600 hover:underline'>Xem truyện</a>";
            // Redirect để giữ tab hiện tại
            header("Location: /admin/upload-manga.php?tab=create-manga");
            exit;
        }
    }
}

// Xử lý form upload chương
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_chapter') {
    $mangaId = $_POST['manga_id'] ?? '';
    $chapterNumber = floatval($_POST['chapter_number'] ?? 0);
    $chapterTitle = trim($_POST['chapter_title'] ?? '');
    $files = $_FILES['chapter_pages'] ?? [];
    $stmt = $conn->prepare("SELECT * FROM manga WHERE manga_id = ? AND is_manual = 1");
    $stmt->bind_param("s", $mangaId);
    $stmt->execute();
    $manga = $stmt->get_result()->fetch_assoc();
    if (!$manga) {
        $error = "Truyện không tồn tại hoặc không phải truyện thủ công!";
    } elseif ($chapterNumber <= 0 || empty($files['name'][0])) {
        $error = "Vui lòng nhập số chương hợp lệ và upload ít nhất một ảnh!";
    } else {
        $imgurClientId = '3cea3f0e5d5c043'; // Client ID của bạn
        $pageUrls = [];

        // Upload từng ảnh lên Imgur
        foreach ($files['tmp_name'] as $index => $tmpName) {
            if ($files['error'][$index] !== UPLOAD_ERR_OK) {
                $error = "Lỗi khi upload file tại vị trí " . ($index + 1) . ": " . $files['error'][$index];
                break;
            }

            $imageData = file_get_contents($tmpName);
            $base64Image = base64_encode($imageData);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.imgur.com/3/image');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Client-ID $imgurClientId"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'image' => $base64Image
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($httpCode === 200) {
                $imgurResponse = json_decode($response, true);
                $pageUrls[] = $imgurResponse['data']['link'];
            } else {
                $error = "Lỗi khi upload ảnh lên Imgur tại vị trí " . ($index + 1) . ": HTTP Code $httpCode";
                if (!empty($curlError)) {
                    $error .= " (cURL Error: $curlError)";
                }
                if (!empty($response)) {
                    $error .= " (Imgur Response: $response)";
                }
                break;
            }
        }

        if (empty($error) && !empty($pageUrls)) {
            $chapterId = bin2hex(random_bytes(18));
            $pagesJson = json_encode($pageUrls);
            $stmt = $conn->prepare("
                INSERT INTO chapters (id, manga_id, chapter_number, title, pages)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssdss", $chapterId, $mangaId, $chapterNumber, $chapterTitle, $pagesJson);
            $stmt->execute();

            $success = "Thêm chương thành công! <a href='/truyen.php?id=$mangaId' class='text-blue-600 hover:underline'>Xem truyện</a>";
            // Redirect để giữ tab hiện tại
            header("Location: /admin/upload-manga.php?tab=created-manga");
            exit;
        }
    }
}

// Xử lý xóa truyện
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_manga') {
    $mangaId = $_POST['manga_id'] ?? '';
    $stmt = $conn->prepare("SELECT * FROM manga WHERE manga_id = ? AND is_manual = 1");
    $stmt->bind_param("s", $mangaId);
    $stmt->execute();
    $manga = $stmt->get_result()->fetch_assoc();

    if (!$manga) {
        $error = "Truyện không tồn tại hoặc không phải truyện thủ công!";
    } else {
        $stmt = $conn->prepare("DELETE FROM chapters WHERE manga_id = ?");
        $stmt->bind_param("s", $mangaId);
        $stmt->execute();
        // Xóa truyện
        $stmt = $conn->prepare("DELETE FROM manga WHERE manga_id = ? AND is_manual = 1");
        $stmt->bind_param("s", $mangaId);
        $stmt->execute();

        $success = "Xóa truyện thành công!";
        // Redirect để giữ tab hiện tại
        header("Location: /admin/upload-manga.php?tab=created-manga");
        exit;
    }
}

// Xử lý xóa chương
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_chapter') {
    $chapterId = $_POST['chapter_id'] ?? '';
    $mangaId = $_POST['manga_id'] ?? '';
    $stmt = $conn->prepare("SELECT * FROM chapters WHERE id = ? AND manga_id = ?");
    $stmt->bind_param("ss", $chapterId, $mangaId);
    $stmt->execute();
    $chapter = $stmt->get_result()->fetch_assoc();

    if (!$chapter) {
        $error = "Chương không tồn tại!";
    } else {
        // Xóa chương
        $stmt = $conn->prepare("DELETE FROM chapters WHERE id = ?");
        $stmt->bind_param("s", $chapterId);
        $stmt->execute();

        $success = "Xóa chương thành công!";
        // Redirect để giữ tab hiện tại
        header("Location: /admin/upload-manga.php?tab=created-manga");
        exit;
    }
}

// Xử lý sửa chương
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_chapter') {
    $chapterId = $_POST['chapter_id'] ?? '';
    $mangaId = $_POST['manga_id'] ?? '';
    $chapterNumber = floatval($_POST['chapter_number'] ?? 0);
    $chapterTitle = trim($_POST['chapter_title'] ?? '');
    $files = $_FILES['chapter_pages'] ?? [];
    $stmt = $conn->prepare("SELECT * FROM chapters WHERE id = ? AND manga_id = ?");
    $stmt->bind_param("ss", $chapterId, $mangaId);
    $stmt->execute();
    $chapter = $stmt->get_result()->fetch_assoc();

    if (!$chapter) {
        $error = "Chương không tồn tại!";
    } elseif ($chapterNumber <= 0) {
        $error = "Vui lòng nhập số chương hợp lệ!";
    } else {
        $pageUrls = json_decode($chapter['pages'], true);

        // Nếu có file mới, upload ảnh mới lên Imgur
        if (!empty($files['name'][0])) {
            $imgurClientId = '3cea3f0e5d5c043'; // Client ID của bạn
            $pageUrls = []; // Reset danh sách ảnh

            foreach ($files['tmp_name'] as $index => $tmpName) {
                if ($files['error'][$index] !== UPLOAD_ERR_OK) {
                    $error = "Lỗi khi upload file tại vị trí " . ($index + 1) . ": " . $files['error'][$index];
                    break;
                }

                $imageData = file_get_contents($tmpName);
                $base64Image = base64_encode($imageData);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://api.imgur.com/3/image');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Client-ID $imgurClientId"
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, [
                    'image' => $base64Image
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                if ($httpCode === 200) {
                    $imgurResponse = json_decode($response, true);
                    $pageUrls[] = $imgurResponse['data']['link'];
                } else {
                    $error = "Lỗi khi upload ảnh lên Imgur tại vị trí " . ($index + 1) . ": HTTP Code $httpCode";
                    if (!empty($curlError)) {
                        $error .= " (cURL Error: $curlError)";
                    }
                    if (!empty($response)) {
                        $error .= " (Imgur Response: $response)";
                    }
                    break;
                }
            }
        }

        if (empty($error)) {
            $pagesJson = json_encode($pageUrls);
            $stmt = $conn->prepare("
                UPDATE chapters 
                SET chapter_number = ?, title = ?, pages = ? 
                WHERE id = ?
            ");
            $stmt->bind_param("dsss", $chapterNumber, $chapterTitle, $pagesJson, $chapterId);
            $stmt->execute();

            $success = "Sửa chương thành công! <a href='/truyen.php?id=$mangaId' class='text-blue-600 hover:underline'>Xem truyện</a>";
            // Redirect để giữ tab hiện tại
            header("Location: /admin/upload-manga.php?tab=created-manga");
            exit;
        }
    }
}

$stmt = $conn->prepare("SELECT * FROM manga WHERE is_manual = 1");
$stmt->execute();
$manualManga = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($manualManga as $key => $manga) {
    $mangaId = $manga['manga_id'];
    $stmt = $conn->prepare("SELECT * FROM chapters WHERE manga_id = ? ORDER BY chapter_number ASC");
    $stmt->bind_param("s", $mangaId);
    $stmt->execute();
    $manualManga[$key]['chapters'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Manga Thủ Công - <?php echo SITE_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="/../css/style.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include '../includes/navbar.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">Quản lý Manga Thủ Công</h1>

        <!-- Tabs -->
        <div class="mb-4">
            <ul class="flex border-b flex-wrap">
                <li class="mr-1">
                    <a href="?tab=create-manga" class="tab-link bg-white inline-block py-2 px-4 text-gray-600 font-semibold">Tạo truyện</a>
                </li>
                <li class="mr-1">
                    <a href="?tab=created-manga" class="tab-link bg-white inline-block py-2 px-4 text-gray-600 font-semibold">Truyện đã tạo</a>
                </li>
            </ul>
        </div>

        <!-- Thông báo -->
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded mb-4"><?php echo $error; ?></div>
            <?php $error = null; ?>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="bg-green-100 text-green-700 p-4 rounded mb-4"><?php echo $success; ?></div>
            <?php $success = null; ?>
        <?php endif; ?>

        <!-- Tab Tạo truyện -->
        <div id="create-manga" class="tab-content <?php echo $currentTab !== 'create-manga' ? 'hidden' : ''; ?>">
            <form method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-lg shadow-md">
                <input type="hidden" name="action" value="create_manga">
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Tên truyện <span class="text-red-500">*</span></label>
                    <input type="text" name="title" class="w-full p-2 border rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Tên khác</label>
                    <input type="text" name="alt_title" class="w-full p-2 border rounded">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Mô tả</label>
                    <textarea name="description" class="w-full p-2 border rounded" rows="5"></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Ảnh bìa</label>
                    <input type="file" name="cover" accept="image/*" class="w-full p-2 border rounded">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Trạng thái</label>
                    <select name="status" class="w-full p-2 border rounded">
                        <option value="ongoing">Đang tiến hành</option>
                        <option value="completed">Hoàn thành</option>
                        <option value="hiatus">Tạm dừng</option>
                        <option value="cancelled">Hủy bỏ</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Xếp hạng nội dung</label>
                    <select name="content_rating" class="w-full p-2 border rounded">
                        <option value="safe">An toàn</option>
                        <option value="suggestive">Gợi ý</option>
                        <option value="erotica">Khiêu dâm nhẹ</option>
                        <option value="pornographic">Khiêu dâm</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Thể loại (chọn nhiều thể loại)</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 max-h-64 overflow-y-auto border p-2 rounded">
                        <?php foreach ($genres as $genre): ?>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="tags[]" value="<?php echo htmlspecialchars($genre); ?>" class="form-checkbox">
                                <span><?php echo htmlspecialchars($genre); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Tác giả</label>
                    <input type="text" name="author" class="w-full p-2 border rounded">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Họa sĩ</label>
                    <input type="text" name="artist" class="w-full p-2 border rounded">
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Tạo truyện</button>
            </form>
        </div>

        <!-- Tab Truyện đã tạo -->
        <div id="created-manga" class="tab-content <?php echo $currentTab !== 'created-manga' ? 'hidden' : ''; ?>">
            <?php if (empty($manualManga)): ?>
                <p class="text-gray-600 italic">Chưa có truyện thủ công nào được tạo!</p>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($manualManga as $manga): ?>
                        <div class="bg-white p-4 rounded-lg shadow-md">
                            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                                <img src="<?php echo htmlspecialchars($manga['cover_url'] ?: '/public/images/loading.png'); ?>" 
                                     alt="<?php echo htmlspecialchars($manga['title']); ?>" 
                                     class="w-16 h-24 object-cover rounded self-center sm:self-start"
                                     onerror="this.src='/public/images/loading.png';">
                                <div class="flex-1 text-center sm:text-left">
                                    <h3 class="text-lg font-semibold">
                                        <a href="/truyen.php?id=<?php echo $manga['manga_id']; ?>" class="text-blue-600 hover:underline">
                                            <?php echo htmlspecialchars($manga['title']); ?>
                                        </a>
                                    </h3>
                                    <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($manga['alt_title'] ?: 'Không có tên khác'); ?></p>
                                </div>
                                <div class="flex flex-wrap justify-center sm:justify-end gap-2 mt-2 sm:mt-0">
                                    <button class="toggle-chapter-form bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 text-sm">Thêm chương</button>
                                    <button class="toggle-chapters-list bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 text-sm">Xem danh sách chương</button>
                                    <form method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa truyện này?');" class="inline">
                                        <input type="hidden" name="action" value="delete_manga">
                                        <input type="hidden" name="manga_id" value="<?php echo $manga['manga_id']; ?>">
                                        <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 text-sm">Xóa truyện</button>
                                    </form>
                                </div>
                            </div>

                            <!-- Form thêm chương (ẩn mặc định) -->
                            <div class="chapter-form mt-4 hidden">
                                <form method="POST" enctype="multipart/form-data" class="bg-gray-50 p-4 rounded-lg">
                                    <input type="hidden" name="action" value="add_chapter">
                                    <input type="hidden" name="manga_id" value="<?php echo $manga['manga_id']; ?>">
                                    <div class="mb-4">
                                        <label class="block text-gray-700 font-semibold mb-2">Số chương <span class="text-red-500">*</span></label>
                                        <input type="number" name="chapter_number" step="0.1" class="w-full p-2 border rounded" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-gray-700 font-semibold mb-2">Tiêu đề chương</label>
                                        <input type="text" name="chapter_title" class="w-full p-2 border rounded">
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-gray-700 font-semibold mb-2">Ảnh chương <span class="text-red-500">*</span></label>
                                        <input type="file" name="chapter_pages[]" accept="image/*" multiple class="w-full p-2 border rounded" required>
                                    </div>
                                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Thêm chương</button>
                                </form>
                            </div>

                            <!-- Danh sách chương (ẩn mặc định) -->
                            <div class="chapters-list mt-4 hidden">
                                <h4 class="text-lg font-semibold mb-2">Danh sách chương</h4>
                                <?php if (empty($manga['chapters'])): ?>
                                    <p class="text-gray-600 italic">Chưa có chương nào.</p>
                                <?php else: ?>
                                    <div class="space-y-4">
                                        <?php foreach ($manga['chapters'] as $chapter): ?>
                                            <div class="bg-gray-50 p-4 rounded-lg">
                                                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
                                                    <div>
                                                        <p class="font-semibold">Chương <?php echo htmlspecialchars($chapter['chapter_number']); ?>: 
                                                            <?php echo htmlspecialchars($chapter['title'] ?: 'Không có tiêu đề'); ?></p>
                                                        <p class="text-sm text-gray-600">
                                                            Số ảnh: <?php echo count(json_decode($chapter['pages'], true)); ?>
                                                        </p>
                                                    </div>
                                                    <div class="flex justify-center sm:justify-end gap-2">
                                                        <button class="toggle-edit-form bg-yellow-600 text-white px-3 py-1 rounded hover:bg-yellow-700 text-sm">Sửa</button>
                                                        <form method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa chương này?');" class="inline">
                                                            <input type="hidden" name="action" value="delete_chapter">
                                                            <input type="hidden" name="chapter_id" value="<?php echo $chapter['id']; ?>">
                                                            <input type="hidden" name="manga_id" value="<?php echo $manga['manga_id']; ?>">
                                                            <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 text-sm">Xóa</button>
                                                        </form>
                                                    </div>
                                                </div>

                                                <!-- Form sửa chương (ẩn mặc định) -->
                                                <div class="edit-form mt-4 hidden">
                                                    <form method="POST" enctype="multipart/form-data" class="bg-gray-100 p-4 rounded-lg">
                                                        <input type="hidden" name="action" value="edit_chapter">
                                                        <input type="hidden" name="chapter_id" value="<?php echo $chapter['id']; ?>">
                                                        <input type="hidden" name="manga_id" value="<?php echo $manga['manga_id']; ?>">
                                                        <div class="mb-4">
                                                            <label class="block text-gray-700 font-semibold mb-2">Số chương <span class="text-red-500">*</span></label>
                                                            <input type="number" name="chapter_number" step="0.1" value="<?php echo $chapter['chapter_number']; ?>" class="w-full p-2 border rounded" required>
                                                        </div>
                                                        <div class="mb-4">
                                                            <label class="block text-gray-700 font-semibold mb-2">Tiêu đề chương</label>
                                                            <input type="text" name="chapter_title" value="<?php echo htmlspecialchars($chapter['title']); ?>" class="w-full p-2 border rounded">
                                                        </div>
                                                        <div class="mb-4">
                                                            <label class="block text-gray-700 font-semibold mb-2">Ảnh chương (để trống nếu không muốn thay đổi)</label>
                                                            <input type="file" name="chapter_pages[]" accept="image/*" multiple class="w-full p-2 border rounded">
                                                        </div>
                                                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Lưu thay đổi</button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../js/main.js"></script>
    <script src="../js/search.js"></script>
    <script>
        // Xử lý chuyển đổi tab
        document.querySelectorAll('.tab-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetTab = new URL(this.href).searchParams.get('tab');

                // Ẩn tất cả các tab
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.add('hidden');
                });
                document.querySelectorAll('.tab-link').forEach(tab => {
                    tab.classList.remove('text-blue-600', 'border-blue-600');
                    tab.classList.add('text-gray-600');
                });

                // Hiển thị tab được chọn
                document.getElementById(targetTab).classList.remove('hidden');
                this.classList.add('text-blue-600', 'border-blue-600');
                this.classList.remove('text-gray-600');

                // Cập nhật URL mà không reload trang
                window.history.pushState({}, '', `?tab=${targetTab}`);
            });
        });

        // Hiển thị tab dựa trên query parameter
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab') || 'create-manga';
        document.querySelector(`a[href="?tab=${tab}"]`).click();

        // Xử lý hiển thị/ẩn form thêm chương
        document.querySelectorAll('.toggle-chapter-form').forEach(button => {
            button.addEventListener('click', function() {
                const form = this.closest('.bg-white').querySelector('.chapter-form');
                form.classList.toggle('hidden');
                this.textContent = form.classList.contains('hidden') ? 'Thêm chương' : 'Ẩn form';
            });
        });

        // Xử lý hiển thị/ẩn danh sách chương
        document.querySelectorAll('.toggle-chapters-list').forEach(button => {
            button.addEventListener('click', function() {
                const chaptersList = this.closest('.bg-white').querySelector('.chapters-list');
                chaptersList.classList.toggle('hidden');
                this.textContent = chaptersList.classList.contains('hidden') ? 'Xem danh sách chương' : 'Ẩn danh sách chương';
            });
        });

        // Xử lý hiển thị/ẩn form sửa chương
        document.querySelectorAll('.toggle-edit-form').forEach(button => {
            button.addEventListener('click', function() {
                const form = this.closest('.bg-gray-50').querySelector('.edit-form');
                form.classList.toggle('hidden');
                this.textContent = form.classList.contains('hidden') ? 'Sửa' : 'Ẩn form';
            });
        });
    </script>
</body>
</html>