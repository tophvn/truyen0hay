<?php
require_once __DIR__ . '/../src/session.php';
require_once __DIR__ . '/../lib/functions.php';
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['roles'] !== 'admin') {
    header('Location: /login.php');
    exit;
}

// Xử lý thêm/xóa truyện đề xuất
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $mangaId = $_POST['manga_id'] ?? '';
    if ($_POST['action'] === 'add' && !empty($mangaId)) {
        $stmt = $conn->prepare("INSERT INTO staff_picks (manga_id, order_position) 
            SELECT ?, IFNULL(MAX(order_position) + 1, 0) FROM staff_picks");
        $stmt->bind_param("s", $mangaId);
        $stmt->execute();
    } elseif ($_POST['action'] === 'remove' && !empty($mangaId)) {
        $stmt = $conn->prepare("DELETE FROM staff_picks WHERE manga_id = ?");
        $stmt->bind_param("s", $mangaId);
        $stmt->execute();
    } elseif ($_POST['action'] === 'update_order') {
        $orderData = json_decode($_POST['order_data'], true);
        updateStaffPicksOrder($orderData);
    }
    header('Location: setting.php');
    exit;
}

$staffPicks = getStaffPicks(100); // Lấy tất cả truyện đề xuất
$allManga = getAllManga(); // Lấy tất cả truyện từ bảng manga
$allUsers = getAllUsers(); // Lấy tất cả người dùng
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cài đặt - <?php echo SITE_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
</head>
<body class="bg-gray-100 dark:bg-gray-900">
    <?php include '../includes/navbar.php'; ?>

    <main class="container mx-auto p-4">
        <h1 class="text-3xl font-bold mb-6 text-gray-900 dark:text-gray-100">Cài đặt Admin</h1>

        <!-- Tabs -->
        <div class="tabs">
            <ul class="flex border-b border-gray-300 dark:border-gray-700">
                <li class="tab-link cursor-pointer px-4 py-2 bg-gray-200 dark:bg-gray-800 text-gray-900 dark:text-gray-100 font-semibold" data-tab="staff-picks">Truyện Đề Xuất</li>
                <li class="tab-link cursor-pointer px-4 py-2 text-gray-700 dark:text-gray-300" data-tab="users">Quản lý Người Dùng</li>
                <li class="tab-link cursor-pointer px-4 py-2 text-gray-700 dark:text-gray-300" data-tab="content">Quản lý Nội Dung</li>
                <li class="tab-link cursor-pointer px-4 py-2 text-gray-700 dark:text-gray-300" data-tab="settings">Cài đặt Chung</li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content mt-4">
                <!-- Tab Truyện Đề Xuất -->
                <div id="staff-picks" class="tab-pane active">
                    <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-gray-100">Quản lý Truyện Đề Xuất</h2>
                    
                    <div class="flex gap-4">
                        <!-- Danh sách truyện có sẵn -->
                        <div class="w-1/2">
                            <h3 class="text-lg font-semibold mb-2 text-gray-900 dark:text-gray-100">Danh sách truyện</h3>
                            <div class="max-h-96 overflow-y-auto border rounded-md p-2 bg-white dark:bg-gray-800">
                                <?php foreach ($allManga as $manga): ?>
                                    <?php if (!in_array($manga['manga_id'], array_column($staffPicks, 'manga_id'))): ?>
                                        <div class="flex items-center gap-2 p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                                            <img 
                                                src="<?php echo htmlspecialchars($manga['cover_url']); ?>" 
                                                alt="<?php echo htmlspecialchars($manga['title']); ?>" 
                                                class="w-12 h-auto rounded"
                                                onerror="this.onerror=null; this.src='/public/images/loading.png';"
                                            >
                                            <p class="flex-1 text-sm text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($manga['title']); ?></p>
                                            <form method="POST">
                                                <input type="hidden" name="action" value="add">
                                                <input type="hidden" name="manga_id" value="<?php echo htmlspecialchars($manga['manga_id']); ?>">
                                                <button type="submit" class="px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">Thêm</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Danh sách truyện đề xuất -->
                        <div class="w-1/2">
                            <h3 class="text-lg font-semibold mb-2 text-gray-900 dark:text-gray-100">Truyện đề xuất (Kéo để sắp xếp)</h3>
                            <div id="staff-picks-list" class="max-h-96 overflow-y-auto border rounded-md p-2 bg-white dark:bg-gray-800">
                                <?php if (empty($staffPicks)): ?>
                                    <p class="text-gray-500">Chưa có truyện nào trong danh sách đề xuất.</p>
                                <?php else: ?>
                                    <?php foreach ($staffPicks as $manga): ?>
                                        <div class="flex items-center gap-2 p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded draggable" data-manga-id="<?php echo htmlspecialchars($manga['manga_id']); ?>">
                                            <img 
                                                src="<?php echo htmlspecialchars($manga['cover_url']); ?>" 
                                                alt="<?php echo htmlspecialchars($manga['title']); ?>" 
                                                class="w-12 h-auto rounded"
                                                onerror="this.onerror=null; this.src='/public/images/loading.png';"
                                            >
                                            <p class="flex-1 text-sm text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($manga['title']); ?></p>
                                            <form method="POST">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="manga_id" value="<?php echo htmlspecialchars($manga['manga_id']); ?>">
                                                <button type="submit" class="px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700">Xóa</button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Quản lý Người Dùng -->
                <div id="users" class="tab-pane hidden">
                    <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-gray-100">Quản lý Người Dùng</h2>
                    <div class="max-h-96 overflow-y-auto border rounded-md p-4 bg-white dark:bg-gray-800">
                        <?php if (empty($allUsers)): ?>
                            <p class="text-gray-500">Chưa có người dùng nào.</p>
                        <?php else: ?>
                            <table class="w-full text-left text-sm text-gray-900 dark:text-gray-100">
                                <thead class="bg-gray-200 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2">ID</th>
                                        <th class="px-4 py-2">Tên đăng nhập</th>
                                        <th class="px-4 py-2">Tên</th>
                                        <th class="px-4 py-2">Email</th>
                                        <th class="px-4 py-2">Vai trò</th>
                                        <th class="px-4 py-2">Điểm</th>
                                        <th class="px-4 py-2">Ngày tạo</th>
                                        <th class="px-4 py-2">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allUsers as $user): ?>
                                        <tr class="border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700">
                                            <td class="px-4 py-2"><?php echo htmlspecialchars($user['user_id']); ?></td>
                                            <td class="px-4 py-2"><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td class="px-4 py-2"><?php echo htmlspecialchars($user['name'] ?? 'N/A'); ?></td>
                                            <td class="px-4 py-2"><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td class="px-4 py-2"><?php echo htmlspecialchars($user['roles']); ?></td>
                                            <td class="px-4 py-2"><?php echo htmlspecialchars($user['score']); ?></td>
                                            <td class="px-4 py-2"><?php echo htmlspecialchars($user['created_at']); ?></td>
                                            <td class="px-4 py-2">
                                                <button class="px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">Sửa</button>
                                                <button class="px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700">Xóa</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tab Quản lý Nội Dung -->
                <div id="content" class="tab-pane hidden">
                    <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-gray-100">Quản lý Nội Dung</h2>
                    <p class="text-gray-700 dark:text-gray-300">Chức năng đang phát triển...</p>
                </div>

                <!-- Tab Cài đặt Chung -->
                <div id="settings" class="tab-pane hidden">
                    <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-gray-100">Cài đặt Chung</h2>
                    <p class="text-gray-700 dark:text-gray-300">Chức năng đang phát triển...</p>
                </div>
            </div>
        </div>
    </main>

    <script>
    document.querySelectorAll('.tab-link').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.tab-link').forEach(t => {
                t.classList.remove('bg-gray-200', 'dark:bg-gray-800', 'text-gray-900', 'dark:text-gray-100', 'font-semibold');
                t.classList.add('text-gray-700', 'dark:text-gray-300');
            });
            this.classList.add('bg-gray-200', 'dark:bg-gray-800', 'text-gray-900', 'dark:text-gray-100', 'font-semibold');
            this.classList.remove('text-gray-700', 'dark:text-gray-300');

            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.add('hidden'));
            document.getElementById(this.getAttribute('data-tab')).classList.remove('hidden');
        });
    });

    // Kéo thả sắp xếp truyện đề xuất
    const staffPicksList = document.getElementById('staff-picks-list');
    if (staffPicksList) {
        Sortable.create(staffPicksList, {
            animation: 150,
            handle: '.draggable',
            onEnd: function (evt) {
                const items = staffPicksList.querySelectorAll('.draggable');
                const orderData = {};
                items.forEach((item, index) => {
                    orderData[index] = item.getAttribute('data-manga-id');
                });

                fetch('/admin/setting.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=update_order&order_data=' + encodeURIComponent(JSON.stringify(orderData))
                });
            }
        });
    }
    </script>
</body>
</html>