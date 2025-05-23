<?php
require_once __DIR__ . '/lib/functions.php';

// Lấy tham số từ URL
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$query = $_GET['q'] ?? '';
$limit = 32; // Số lượng nhóm mỗi trang
$offset = ($page - 1) * $limit;

// Tìm kiếm nhóm dịch (cho danh sách chính, vẫn phân trang)
$groupsData = searchGroups($query, $limit, $offset);
$groups = $groupsData['groups'] ?? [];
$total = $groupsData['total'] ?? 0;
$totalPages = ceil($total / $limit);

// Hàm tìm kiếm nhóm dịch
function searchGroups($query, $limit, $offset) {
    $max_total = 10000;
    if ($limit + $offset > $max_total) {
        $limit = $max_total - $offset;
    }

    $params = [
        'limit' => $limit,
        'offset' => $offset,
        'includes' => ['leader'],
    ];

    if ($query) {
        $params['name'] = urlencode($query); // Encode từ khóa để tránh lỗi
    }

    $data = callMangadexApi('/group', $params);
    if (!$data || !isset($data['data'])) {
        return [
            'groups' => [],
            'total' => 0
        ];
    }

    $total = isset($data['total']) && $data['total'] > $max_total ? $max_total : $data['total'];
    $groups = array_map('parseGroup', $data['data']);

    return [
        'groups' => $groups,
        'total' => $total
    ];
}

// Hàm parse nhóm dịch
function parseGroup($data) {
    $id = $data['id'];
    $attributes = $data['attributes'];
    $name = $attributes['name'] ?? 'Unknown';
    $description = $attributes['description'] ?? '';
    $website = $attributes['website'] ?? '';
    $discord = $attributes['discord'] ?? '';
    $email = $attributes['contactEmail'] ?? '';
    $twitter = $attributes['twitter'] ?? '';
    $leader = null;
    $language = $attributes['focusedLanguages'] ?? [];

    foreach ($data['relationships'] as $rel) {
        if ($rel['type'] === 'leader') {
            $leader = [
                'id' => $rel['id'],
                'username' => $rel['attributes']['username'] ?? 'Unknown'
            ];
            break;
        }
    }

    return [
        'id' => $id,
        'name' => $name,
        'description' => $description,
        'website' => $website,
        'discord' => $discord,
        'email' => $email,
        'twitter' => $twitter,
        'language' => $language,
        'leader' => $leader
    ];
}

// Danh sách ngôn ngữ hỗ trợ
$displayLanguage = [
    'en' => ['name' => 'Tiếng Anh', 'flag' => '🇬🇧'],
    'vi' => ['name' => 'Tiếng Việt', 'flag' => '🇻🇳'],
    'ja' => ['name' => 'Tiếng Nhật', 'flag' => '🇯🇵'],
    'ko' => ['name' => 'Tiếng Hàn', 'flag' => '🇰🇷'],
    'zh' => ['name' => 'Tiếng Trung', 'flag' => '🇨🇳'],
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Nhóm dịch - <?php echo SITE_NAME; ?></title>
    <meta name="theme-color" content="#ffffff">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/public/images/logo.png" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
</head>
<body class="bg-gray-100 dark:bg-gray-900">
    <?php include 'includes/navbar.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <main id="main-content" class="container mx-auto p-4 pt-0">
        <!-- Thanh tìm kiếm và tiêu đề -->
        <div id="search-section">
            <h1 class="text-2xl font-bold mb-4 text-gray-900 dark:text-gray-100">Nhóm dịch</h1>

            <!-- Thanh tìm kiếm -->
            <div class="relative w-full mb-4 flex items-center gap-2">
                <div class="relative flex-1">
                    <svg class="h-4 w-4 absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        id="search-input"
                        class="bg-gray-200 dark:bg-gray-800 pl-8 pr-16 w-full h-10 rounded-md text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Nhập từ khóa..."
                        autocomplete="off"
                        value="<?php echo htmlspecialchars($query); ?>"
                    />
                    <button
                        id="clear-button"
                        class="absolute right-8 top-1/2 transform -translate-y-1/2 bg-gray-500 text-white rounded-md w-8 h-8 flex items-center justify-center hidden"
                    >
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <button
                        id="search-button"
                        class="absolute right-1 top-1/2 transform -translate-y-1/2 bg-blue-600 text-white rounded-md w-8 h-8 flex items-center justify-center"
                    >
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </div>
            </div>
            <div id="search-results" class="absolute top-16 left-0 right-0 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md max-h-72 overflow-y-auto z-10 hidden"></div>
        </div>

        <!-- Phần hiển thị: Danh sách nhóm hoặc chi tiết nhóm -->
        <div id="content-section">
            <!-- Danh sách nhóm dịch (mặc định) -->
            <div id="group-list" class="mt-4 w-full">
                <?php if (empty($groups)): ?>
                    <div class="bg-white dark:bg-gray-800 rounded-md p-4 text-center shadow-md">
                        <p class="text-gray-700 dark:text-gray-300 italic">Không có kết quả!</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2">
                        <?php foreach ($groups as $group): ?>
                            <button
                                onclick="showGroupDetails('<?php echo htmlspecialchars($group['id']); ?>')"
                                class="flex items-center gap-2 p-3 bg-gray-200 dark:bg-gray-800 rounded-md hover:bg-gray-300 dark:hover:bg-gray-700 transition-colors w-full text-left"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-5 h-5 sm:w-6 sm:h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span class="text-sm sm:text-base font-medium text-gray-900 dark:text-gray-100 truncate"><?php echo htmlspecialchars($group['name']); ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Phân trang (hiển thị khi ở chế độ danh sách) -->
            <div id="pagination" class="flex justify-center gap-2 mt-4">
                <?php if ($totalPages > 1): ?>
                    <!-- Previous -->
                    <a href="<?php echo $page > 1 ? 'groups.php?' . http_build_query(['page' => $page - 1, 'q' => $query]) : '#'; ?>" class="<?php echo $page === 1 ? 'pointer-events-none bg-gray-200 text-gray-400 dark:bg-gray-700 dark:text-gray-500' : 'bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300'; ?> flex items-center justify-center w-8 h-8 rounded-md transition-colors">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>

                    <?php if ($totalPages <= 7): ?>
                        <!-- Hiển thị tất cả các trang nếu tổng số trang <= 7 -->
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="groups.php?<?php echo http_build_query(['page' => $i, 'q' => $query]); ?>" class="<?php echo $i === $page ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300'; ?> flex items-center justify-center w-8 h-8 rounded-md text-sm transition-colors">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    <?php elseif ($page <= 4): ?>
                        <!-- Gần đầu: hiển thị 1, 2, 3, 4, 5, ..., lastPage -->
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <a href="groups.php?<?php echo http_build_query(['page' => $i, 'q' => $query]); ?>" class="<?php echo $i === $page ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300'; ?> flex items-center justify-center w-8 h-8 rounded-md text-sm transition-colors">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        <span class="flex items-center justify-center w-8 h-8 text-gray-700 dark:text-gray-300">...</span>
                        <a href="groups.php?<?php echo http_build_query(['page' => $totalPages, 'q' => $query]); ?>" class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 flex items-center justify-center w-8 h-8 rounded-md text-sm transition-colors">
                            <?php echo $totalPages; ?>
                        </a>
                    <?php elseif ($page >= $totalPages - 3): ?>
                        <!-- Gần cuối: hiển thị 1, ..., lastPage-4, lastPage-3, lastPage-2, lastPage-1, lastPage -->
                        <a href="groups.php?<?php echo http_build_query(['page' => 1, 'q' => $query]); ?>" class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 flex items-center justify-center w-8 h-8 rounded-md text-sm transition-colors">
                            1
                        </a>
                        <span class="flex items-center justify-center w-8 h-8 text-gray-700 dark:text-gray-300">...</span>
                        <?php for ($i = $totalPages - 4; $i <= $totalPages; $i++): ?>
                            <a href="groups.php?<?php echo http_build_query(['page' => $i, 'q' => $query]); ?>" class="<?php echo $i === $page ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300'; ?> flex items-center justify-center w-8 h-8 rounded-md text-sm transition-colors">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    <?php else: ?>
                        <!-- Ở giữa: hiển thị 1, ..., page-1, page, page+1, ..., lastPage -->
                        <a href="groups.php?<?php echo http_build_query(['page' => 1, 'q' => $query]); ?>" class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 flex items-center justify-center w-8 h-8 rounded-md text-sm transition-colors">
                            1
                        </a>
                        <span class="flex items-center justify-center w-8 h-8 text-gray-700 dark:text-gray-300">...</span>
                        <?php for ($i = $page - 1; $i <= $page + 1; $i++): ?>
                            <a href="groups.php?<?php echo http_build_query(['page' => $i, 'q' => $query]); ?>" class="<?php echo $i === $page ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300'; ?> flex items-center justify-center w-8 h-8 rounded-md text-sm transition-colors">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        <span class="flex items-center justify-center w-8 h-8 text-gray-700 dark:text-gray-300">...</span>
                        <a href="groups.php?<?php echo http_build_query(['page' => $totalPages, 'q' => $query]); ?>" class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 flex items-center justify-center w-8 h-8 rounded-md text-sm transition-colors">
                            <?php echo $totalPages; ?>
                        </a>
                    <?php endif; ?>

                    <!-- Next -->
                    <a href="<?php echo $page < $totalPages ? 'groups.php?' . http_build_query(['page' => $page + 1, 'q' => $query]) : '#'; ?>" class="<?php echo $page === $totalPages ? 'pointer-events-none bg-gray-200 text-gray-400 dark:bg-gray-700 dark:text-gray-500' : 'bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300'; ?> flex items-center justify-center w-8 h-8 rounded-md transition-colors">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Phần hiển thị chi tiết nhóm (ẩn mặc định) -->
            <div id="group-details" class="hidden mt-4 w-full">
                <!-- Nút quay lại -->
                <button
                    onclick="showGroupList()"
                    class="mb-4 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-md py-2 px-4 flex items-center gap-2 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                >
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Quay lại danh sách
                </button>

                <!-- Background banner -->
                <div class="absolute h-48 md:h-64 z-[-2] w-full left-0 right-0 top-0">
                    <div
                        class="absolute h-48 md:h-64 w-full bg-no-repeat bg-cover bg-center"
                        style="background-image: url('/public/images/Elaina_thumb.png');"
                    ></div>
                    <div
                        class="absolute h-48 md:h-64 w-full inset-0 pointer-events-none bg-gradient-to-r from-black/25 to-transparent"
                    ></div>
                </div>

                <!-- Nội dung chính -->
                <div class="flex flex-col md:flex-row gap-4 mt-20 md:mt-24">
                    <!-- Cột bên trái: Ảnh và nút -->
                    <div class="flex flex-row md:flex-col gap-2 md:shrink-0 items-end">
                    <img
                        src="public\images\logo0hay.png"
                        alt="Group Avatar"
                        class="rounded-full border-4 border-blue-500 object-cover shrink-0 w-32 h-32 md:w-48 md:h-48"
                        style="position: relative; z-index: 9999;"
                    />
                        <div class="flex flex-row md:flex-col gap-2 w-full">
                            <button
                                class="w-full bg-blue-600 text-white rounded-md py-2 px-4 flex items-center justify-center gap-2 hover:bg-blue-700 transition-colors"
                                onclick="alert('Chức năng đang phát triển!')"
                            >
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3-7 3V5z" />
                                </svg>
                                Theo dõi
                            </button>
                            <a
                                id="mangadex-link"
                                href=""
                                target="_blank"
                                class="w-full bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-md py-2 px-4 flex items-center justify-center gap-2 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                            >
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                </svg>
                                MangaDex
                            </a>
                            <button
                                class="w-full bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-md py-2 px-4 flex items-center justify-center gap-2 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                                onclick="alert('Chức năng đang phát triển!')"
                            >
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                Chặn
                            </button>
                        </div>
                    </div>

                    <!-- Cột bên phải: Thông tin chi tiết -->
                    <div class="md:mt-32 flex flex-col gap-4 w-full">
                        <h1 id="group-name" class="text-4xl md:text-5xl font-bold text-gray-900 dark:text-gray-100"></h1>

                        <!-- Tabs -->
                        <div class="flex border-b border-gray-200 dark:border-gray-700">
                            <button id="tab-info" onclick="showTab('info')" class="px-4 py-2 text-blue-600 border-b-2 border-blue-600 font-medium">Thông tin</button>
                            <button id="tab-uploads" onclick="showTab('uploads')" class="px-4 py-2 text-gray-500 dark:text-gray-400 font-medium">Truyện đã đăng</button>
                        </div>

                        <!-- Nội dung tab "Thông tin" -->
                        <div id="group-info" class="flex flex-col gap-4">
                            <!-- Mô tả -->
                            <div id="group-description" class="hidden flex flex-col gap-2">
                                <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">Mô tả</h2>
                                <div class="prose dark:prose-invert text-gray-700 dark:text-gray-300"></div>
                            </div>

                            <!-- Liên hệ -->
                            <div id="group-contact" class="hidden flex flex-col gap-2">
                                <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">Liên hệ</h2>
                                <div class="flex flex-col md:flex-row gap-2" id="contact-links"></div>
                            </div>

                            <!-- Trưởng nhóm -->
                            <div id="group-leader" class="hidden flex flex-col gap-2">
                                <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">Trưởng nhóm</h2>
                                <a
                                    id="leader-link"
                                    href=""
                                    target="_blank"
                                    class="w-full md:w-fit bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-md py-2 px-4 flex items-center gap-2 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                                >
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <span id="leader-username"></span>
                                </a>
                            </div>

                            <!-- Ngôn ngữ -->
                            <div id="group-languages" class="hidden flex flex-col gap-2">
                                <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">Ngôn ngữ</h2>
                                <div class="flex flex-col md:flex-row gap-2" id="language-list"></div>
                            </div>
                        </div>

                        <!-- Nội dung tab "Truyện đã đăng" -->
                        <div id="group-uploads" class="hidden flex flex-col gap-4">
                            <div id="manga-list" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                            </div>
                            <div id="manga-pagination" class="flex justify-center gap-2 mt-4">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="/js/main.js"></script>
    <script src="/js/search.js"></script>
    <script>
        const searchInput = document.getElementById('search-input');
        const searchButton = document.getElementById('search-button');
        const clearButton = document.getElementById('clear-button');
        const searchResults = document.getElementById('search-results');
        const groupList = document.getElementById('group-list');
        const pagination = document.getElementById('pagination');
        const groupDetails = document.getElementById('group-details');
        const searchSection = document.getElementById('search-section');
        const groupInfo = document.getElementById('group-info');
        const groupUploads = document.getElementById('group-uploads');
        const tabInfo = document.getElementById('tab-info');
        const tabUploads = document.getElementById('tab-uploads');
        const mangaList = document.getElementById('manga-list');
        const mangaPagination = document.getElementById('manga-pagination');

        // Danh sách ngôn ngữ hỗ trợ
        const displayLanguage = {
            'en': { name: 'Tiếng Anh', flag: '🇬🇧' },
            'vi': { name: 'Tiếng Việt', flag: '🇻🇳' },
            'ja': { name: 'Tiếng Nhật', flag: '🇯🇵' },
            'ko': { name: 'Tiếng Hàn', flag: '🇰🇷' },
            'zh': { name: 'Tiếng Trung', flag: '🇨🇳' }
        };

        let currentGroupId = null;
        let currentMangaPage = 1;
        const mangaLimit = 20;

        // Hàm gọi API tìm kiếm nhóm dịch
        async function fetchGroups(query, limit = 100) {
            try {
                const response = await fetch(`/includes/search-groups.php?q=${encodeURIComponent(query)}&limit=${limit}`);
                const data = await response.json();
                return data.groups || [];
            } catch (error) {
                console.error('Error fetching groups:', error);
                return [];
            }
        }

        // Hàm gọi API lấy chi tiết nhóm và danh sách truyện
        async function fetchGroupDetails(groupId, page = 1) {
            const offset = (page - 1) * mangaLimit;
            try {
                const response = await fetch(`/includes/get-group.php?id=${encodeURIComponent(groupId)}&limit=${mangaLimit}&offset=${offset}`);
                const data = await response.json();
                return data || { group: null, manga: [], total: 0 };
            } catch (error) {
                console.error('Error fetching group details:', error);
                return { group: null, manga: [], total: 0 };
            }
        }

        // Hàm hiển thị kết quả tìm kiếm trong dropdown
        function displaySearchResults(groups, query) {
            searchResults.innerHTML = '';
            if (groups.length === 0) {
                searchResults.classList.remove('block');
                searchResults.classList.add('hidden');
                return;
            }

            // Kiểm tra exact match
            const exactMatch = groups.find(group => group.name.toLowerCase() === query.toLowerCase());
            if (exactMatch) {
                showGroupDetails(exactMatch.id);
                return;
            }

            // Hiển thị danh sách gợi ý
            groups.forEach(group => {
                const groupCard = document.createElement('button');
                groupCard.onclick = () => showGroupDetails(group.id);
                groupCard.className = 'flex items-center gap-2 p-3 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors w-full text-left';
                groupCard.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">${group.name}</span>
                `;
                searchResults.appendChild(groupCard);
            });
            searchResults.classList.remove('hidden');
            searchResults.classList.add('block');
        }

        // Hàm hiển thị danh sách nhóm chính
        function displayGroupList(groups) {
            groupList.innerHTML = groups.length === 0 ? `
                <div class="bg-white dark:bg-gray-800 rounded-md p-4 text-center shadow-md">
                    <p class="text-gray-700 dark:text-gray-300 italic">Không có kết quả!</p>
                </div>
            ` : `
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2">
                    ${groups.map(group => `
                        <button onclick="showGroupDetails('${group.id}')" class="flex items-center gap-2 p-3 bg-gray-200 dark:bg-gray-800 rounded-md hover:bg-gray-300 dark:hover:bg-gray-700 transition-colors w-full text-left">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-5 h-5 sm:w-6 sm:h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span class="text-sm sm:text-base font-medium text-gray-900 dark:text-gray-100 truncate">${group.name}</span>
                        </button>
                    `).join('')}
                </div>
            `;
        }

        // Hàm hiển thị chi tiết nhóm
        async function showGroupDetails(groupId, tab = 'info') {
            currentGroupId = groupId;
            currentMangaPage = 1;

            // Ẩn danh sách nhóm và phân trang, hiển thị chi tiết nhóm
            groupList.classList.add('hidden');
            pagination.classList.add('hidden');
            groupDetails.classList.remove('hidden');
            searchSection.classList.add('hidden');

            // Cập nhật URL mà không reload
            const params = new URLSearchParams(window.location.search);
            params.set('group_id', groupId);
            if (tab !== 'info') params.set('tab', tab);
            else params.delete('tab');
            window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);

            // Lấy thông tin chi tiết nhóm và danh sách truyện
            const data = await fetchGroupDetails(groupId, currentMangaPage);
            const group = data.group;
            const manga = data.manga;
            const total = data.total;

            if (!group) {
                alert('Không tìm thấy nhóm dịch!');
                showGroupList();
                return;
            }

            // Cập nhật giao diện chi tiết nhóm
            document.getElementById('group-name').textContent = group.name;
            document.getElementById('mangadex-link').href = `https://mangadex.org/group/${group.id}`;

            // Mô tả
            const descriptionSection = document.getElementById('group-description');
            const descriptionContent = descriptionSection.querySelector('.prose');
            if (group.description) {
                descriptionContent.textContent = group.description;
                descriptionSection.classList.remove('hidden');
            } else {
                descriptionSection.classList.add('hidden');
            }

            // Liên hệ
            const contactSection = document.getElementById('group-contact');
            const contactLinks = document.getElementById('contact-links');
            contactLinks.innerHTML = '';
            if (group.website || group.discord || group.email || group.twitter) {
                if (group.website) {
                    contactLinks.innerHTML += `
                        <a href="${group.website}" target="_blank" class="w-full md:w-auto bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-md py-2 px-4 flex items-center gap-2 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Website
                        </a>
                    `;
                }
                if (group.discord) {
                    contactLinks.innerHTML += `
                        <a href="https://discord.gg/${group.discord}" target="_blank" class="w-full md:w-auto bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-md py-2 px-4 flex items-center gap-2 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 127.14 96.36">
                                <path d="M107.7,8.07A105.15,105.15,0,0,0,81.47,0a72.06,72.06,0,0,0-3.36,6.52A97.68,97.68,0,0,0,49,6.52,72.06,72.06,0,0,0,45.64,0,105.15,105.15,0,0,0,19.39,8.07C2.79,32.65-1   M42.45,65.69C36.18,65.69,31,60,31,53s5-12.74,11.43-12.74S54,46,53.89,53,48.72,65.69,42.45,65.69Zm42.24,0C78.41,65.69,73.25,60,73.25,53s5-12.74,11.44-12.74S96.23,46,96.12,53,90.95,65.69,84.69,65.69Z"/>
                            </svg>
                            Discord
                        </a>
                    `;
                }
                if (group.email) {
                    contactLinks.innerHTML += `
                        <a href="mailto:${group.email}" target="_blank" class="w-full md:w-auto bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-md py-2 px-4 flex items-center gap-2 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            Email
                        </a>
                    `;
                }
                if (group.twitter) {
                    contactLinks.innerHTML += `
                        <a href="${group.twitter}" target="_blank" class="w-full md:w-auto bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-md py-2 px-4 flex items-center gap-2 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                            Twitter
                        </a>
                    `;
                }
                contactSection.classList.remove('hidden');
            } else {
                contactSection.classList.add('hidden');
            }

            // Trưởng nhóm
            const leaderSection = document.getElementById('group-leader');
            if (group.leader) {
                document.getElementById('leader-link').href = `https://mangadex.org/user/${group.leader.id}`;
                document.getElementById('leader-username').textContent = group.leader.username;
                leaderSection.classList.remove('hidden');
            } else {
                leaderSection.classList.add('hidden');
            }

            // Ngôn ngữ
            const languagesSection = document.getElementById('group-languages');
            const languageList = document.getElementById('language-list');
            languageList.innerHTML = '';
            if (group.language && group.language.length > 0) {
                const knownLangs = group.language.filter(lang => displayLanguage[lang]);
                const unknownLangs = group.language.filter(lang => !displayLanguage[lang]);
                knownLangs.forEach(lang => {
                    languageList.innerHTML += `
                        <div class="w-full md:w-auto bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-md py-2 px-4 flex items-center gap-2">
                            <span>${displayLanguage[lang].flag}</span>
                            <span>${displayLanguage[lang].name}</span>
                        </div>
                    `;
                });
                if (unknownLangs.length > 0) {
                    languageList.innerHTML += `
                        <div class="w-full md:w-auto bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-md py-2 px-4 flex items-center gap-2">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Khác (+${unknownLangs.length})
                        </div>
                    `;
                }
                languagesSection.classList.remove('hidden');
            } else {
                languagesSection.classList.add('hidden');
            }

            // Hiển thị tab mặc định
            showTab(tab, manga, total);
        }

        // Hàm hiển thị tab
        async function showTab(tab, manga = null, total = 0) {
            const params = new URLSearchParams(window.location.search);

            // Cập nhật trạng thái tab
            if (tab === 'info') {
                tabInfo.classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
                tabInfo.classList.remove('text-gray-500', 'dark:text-gray-400');
                tabUploads.classList.remove('text-blue-600', 'border-b-2', 'border-blue-600');
                tabUploads.classList.add('text-gray-500', 'dark:text-gray-400');
                groupInfo.classList.remove('hidden');
                groupUploads.classList.add('hidden');
                params.delete('tab');
            } else if (tab === 'uploads') {
                tabUploads.classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
                tabUploads.classList.remove('text-gray-500', 'dark:text-gray-400');
                tabInfo.classList.remove('text-blue-600', 'border-b-2', 'border-blue-600');
                tabInfo.classList.add('text-gray-500', 'dark:text-gray-400');
                groupInfo.classList.add('hidden');
                groupUploads.classList.remove('hidden');
                params.set('tab', 'uploads');

                // Nếu chưa có dữ liệu manga, lấy lại từ API
                if (!manga) {
                    const data = await fetchGroupDetails(currentGroupId, currentMangaPage);
                    manga = data.manga;
                    total = data.total;
                }

                // Hiển thị danh sách truyện
                displayMangaList(manga, total);
            }

            // Cập nhật URL
            window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
        }

        // Hàm hiển thị danh sách truyện
        function displayMangaList(manga, total) {
            mangaList.innerHTML = '';
            if (manga.length === 0) {
                mangaList.innerHTML = `
                    <div class="col-span-full bg-white dark:bg-gray-800 rounded-md p-4 text-center shadow-md">
                        <p class="text-gray-700 dark:text-gray-300 italic">Nhóm chưa đăng truyện nào!</p>
                    </div>
                `;
                mangaPagination.innerHTML = '';
                return;
            }

            manga.forEach(item => {
                const mangaCard = document.createElement('a');
                mangaCard.href = `/manga.php?id=${item.id}`; // Điều hướng đến trang chi tiết truyện
                mangaCard.className = 'flex flex-col gap-2';
                mangaCard.innerHTML = `
                    <img src="${item.cover || '/images/placeholder.jpg'}" alt="${item.title}" class="w-full h-48 object-cover rounded-md">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 line-clamp-2">${item.title}</h3>
                `;
                mangaList.appendChild(mangaCard);
            });

            // Tạo phân trang cho truyện
            const totalPages = Math.ceil(total / mangaLimit);
            mangaPagination.innerHTML = '';
            if (totalPages > 1) {
                // Previous
                const prevButton = document.createElement('button');
                prevButton.className = `flex items-center justify-center w-8 h-8 rounded-md transition-colors ${currentMangaPage === 1 ? 'pointer-events-none bg-gray-200 text-gray-400 dark:bg-gray-700 dark:text-gray-500' : 'bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300'}`;
                prevButton.innerHTML = `
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                `;
                prevButton.onclick = () => {
                    if (currentMangaPage > 1) {
                        currentMangaPage--;
                        fetchGroupDetails(currentGroupId, currentMangaPage).then(data => {
                            displayMangaList(data.manga, data.total);
                        });
                    }
                };
                mangaPagination.appendChild(prevButton);

                // Số trang
                if (totalPages <= 7) {
                    for (let i = 1; i <= totalPages; i++) {
                        const pageButton = document.createElement('button');
                        pageButton.className = `flex items-center justify-center w-8 h-8 rounded-md text-sm transition-colors ${i === currentMangaPage ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300'}`;
                        pageButton.textContent = i;
                        pageButton.onclick = () => {
                            currentMangaPage = i;
                            fetchGroupDetails(currentGroupId, currentMangaPage).then(data => {
                                displayMangaList(data.manga, data.total);
                            });
                        };
                        mangaPagination.appendChild(pageButton);
                    }
                } else if (currentMangaPage <= 4) {
                    for (let i = 1; i <= 5; i++) {
                        const pageButton = document.createElement('button');
                        pageButton.className = `flex items-center justify-center w-8 h-8 rounded-md text-sm transition-colors ${i === currentMangaPage ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300'}`;
                        pageButton.textContent = i;
                        pageButton.onclick = () => {
                            currentMangaPage = i;
                            fetchGroupDetails(currentGroupId, currentMangaPage).then(data => {
                                displayMangaList(data.manga, data.total);
                            });
                        };
                        mangaPagination.appendChild(pageButton);
                    }
                    const dots = document.createElement('span');
                    dots.className = 'flex items-center justify-center w-8 h-8 text-gray-700 dark:text-gray-300';
                    dots.textContent = '...';
                    mangaPagination.appendChild(dots);
                    const lastPageButton = document.createElement('button');
                    lastPageButton.className = 'flex items-center justify-center w-8 h-8 rounded-md text-sm transition-colors bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300';
                    lastPageButton.textContent = totalPages;
                    lastPageButton.onclick = () => {
                        currentMangaPage = totalPages;
                        fetchGroupDetails(currentGroupId, currentMangaPage).then(data => {
                            displayMangaList(data.manga, data.total);
                        });
                    };
                    mangaPagination.appendChild(lastPageButton);
                } else if (currentMangaPage >= totalPages - 3) {
                    const firstPageButton = document.createElement('button');
                    firstPageButton.className = 'flex items-center justify-center w-8 h-8 rounded-md text-sm transition-colors bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300';
                    firstPageButton.textContent = 1;
                    firstPageButton.onclick = () => {
                        currentMangaPage = 1;
                        fetchGroupDetails(currentGroupId, currentMangaPage).then(data => {
                            displayMangaList(data.manga, data.total);
                        });
                    };
                    mangaPagination.appendChild(firstPageButton);
                    const dots = document.createElement('span');
                    dots.className = 'flex items-center justify-center w-8 h-8 text-gray-700 dark:text-gray-300';
                    dots.textContent = '...';
                    mangaPagination.appendChild(dots);
                    for (let i = totalPages - 4; i <= totalPages; i++) {
                        const pageButton = document.createElement('button');
                        pageButton.className = `flex items-center justify-center w-8 h-8 rounded-md text-sm transition-colors ${i === currentMangaPage ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300'}`;
                        pageButton.textContent = i;
                        pageButton.onclick = () => {
                            currentMangaPage = i;
                            fetchGroupDetails(currentGroupId, currentMangaPage).then(data => {
                                displayMangaList(data.manga, data.total);
                            });
                        };
                        mangaPagination.appendChild(pageButton);
                    }
                } else {
                    const firstPageButton = document.createElement('button');
                    firstPageButton.className = 'flex items-center justify-center w-8 h-8 rounded-md text-sm transition-colors bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300';
                    firstPageButton.textContent = 1;
                    firstPageButton.onclick = () => {
                        currentMangaPage = 1;
                        fetchGroupDetails(currentGroupId, currentMangaPage).then(data => {
                            displayMangaList(data.manga, data.total);
                        });
                    };
                    mangaPagination.appendChild(firstPageButton);
                    const dots1 = document.createElement('span');
                    dots1.className = 'flex items-center justify-center w-8 h-8 text-gray-700 dark:text-gray-300';
                    dots1.textContent = '...';
                    mangaPagination.appendChild(dots1);
                    for (let i = currentMangaPage - 1; i <= currentMangaPage + 1; i++) {
                        const pageButton = document.createElement('button');
                        pageButton.className = `flex items-center justify-center w-8 h-8 rounded-md text-sm transition-colors ${i === currentMangaPage ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300'}`;
                        pageButton.textContent = i;
                        pageButton.onclick = () => {
                            currentMangaPage = i;
                            fetchGroupDetails(currentGroupId, currentMangaPage).then(data => {
                                displayMangaList(data.manga, data.total);
                            });
                        };
                        mangaPagination.appendChild(pageButton);
                    }
                    const dots2 = document.createElement('span');
                    dots2.className = 'flex items-center justify-center w-8 h-8 text-gray-700 dark:text-gray-300';
                    dots2.textContent = '...';
                    mangaPagination.appendChild(dots2);
                    const lastPageButton = document.createElement('button');
                    lastPageButton.className = 'flex items-center justify-center w-8 h-8 rounded-md text-sm transition-colors bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300';
                    lastPageButton.textContent = totalPages;
                    lastPageButton.onclick = () => {
                        currentMangaPage = totalPages;
                        fetchGroupDetails(currentGroupId, currentMangaPage).then(data => {
                            displayMangaList(data.manga, data.total);
                        });
                    };
                    mangaPagination.appendChild(lastPageButton);
                }

                // Next
                const nextButton = document.createElement('button');
                nextButton.className = `flex items-center justify-center w-8 h-8 rounded-md transition-colors ${currentMangaPage === totalPages ? 'pointer-events-none bg-gray-200 text-gray-400 dark:bg-gray-700 dark:text-gray-500' : 'bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300'}`;
                nextButton.innerHTML = `
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                `;
                nextButton.onclick = () => {
                    if (currentMangaPage < totalPages) {
                        currentMangaPage++;
                        fetchGroupDetails(currentGroupId, currentMangaPage).then(data => {
                            displayMangaList(data.manga, data.total);
                        });
                    }
                };
                mangaPagination.appendChild(nextButton);
            }
        }

        // Hàm quay lại danh sách nhóm
        function showGroupList() {
            groupList.classList.remove('hidden');
            pagination.classList.remove('hidden');
            groupDetails.classList.add('hidden');
            searchSection.classList.remove('hidden');

            // Cập nhật URL mà không reload
            const params = new URLSearchParams(window.location.search);
            params.delete('group_id');
            params.delete('tab');
            window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
        }

        // Xử lý nút Search
        searchButton.addEventListener('click', async function(event) {
            event.preventDefault();

            const query = searchInput.value.trim();
            if (!query) return;

            clearButton.classList.remove('hidden');

            const params = new URLSearchParams(window.location.search);
            params.set('q', query);
            params.delete('page');
            params.delete('group_id');
            params.delete('tab');
            window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);

            const groups = await fetchGroups(query);
            displaySearchResults(groups, query);
            displayGroupList(groups);
        });

        // Xử lý nút xóa
        clearButton.addEventListener('click', async function(event) {
            event.preventDefault();

            searchInput.value = '';
            clearButton.classList.add('hidden');

            const params = new URLSearchParams(window.location.search);
            params.delete('q');
            params.delete('page');
            params.delete('group_id');
            params.delete('tab');
            window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);

            searchResults.classList.remove('block');
            searchResults.classList.add('hidden');

            const groups = await fetchGroups('');
            displayGroupList(groups);
        });

        // Ẩn kết quả tìm kiếm khi click ra ngoài
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target) && !searchButton.contains(e.target)) {
                searchResults.classList.remove('block');
                searchResults.classList.add('hidden');
            }
        });

        // Xử lý Enter để tìm kiếm
        searchInput.addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchButton.click();
            }
        });

        // Kiểm tra nếu có group_id và tab trong URL khi tải trang
        window.addEventListener('load', function() {
            const params = new URLSearchParams(window.location.search);
            const groupId = params.get('group_id');
            const tab = params.get('tab') || 'info';
            if (groupId) {
                showGroupDetails(groupId, tab);
            }
        });
    </script>
</body>
</html>