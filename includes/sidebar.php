<?php
$siteConfig = isset($siteConfig) ? $siteConfig : [
    'links' => [
        'facebook' => 'https://facebook.com',
        'discord' => 'https://discord.com',
        'github' => 'https://github.com',
    ]
];
$basePath = '/';
$r18 = isset($_SESSION['r18']) ? $_SESSION['r18'] : false; 
?>

<div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden"></div>
<aside id="sidebar" class="md:z-50">
    <!-- Close button (visible only on mobile) -->
    <div class="h-12 flex items-center justify-end p-2 md:hidden">
        <button id="sidebar-close" class="flex items-center justify-center w-10 h-10 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Header -->
    <div class="h-12 flex items-center justify-between px-4 border-b dark:border-gray-700">
        <?php if (isset($_SESSION['user']) && !empty($_SESSION['user'])): ?>
            <!-- Khi đã đăng nhập -->
            <div class="flex items-center gap-2">
                <img src="<?php echo htmlspecialchars($_SESSION['user']['avatar'] ?? $basePath . 'public/images/user.png'); ?>" alt="User Avatar" class="h-8 w-8 rounded-lg object-cover">
                <div class="text-left">
                    <span class="font-semibold"><?php echo htmlspecialchars($_SESSION['user']['name'] ?? 'User'); ?></span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($_SESSION['user']['email'] ?? 'email@example.com'); ?></span>
                </div>
            </div>
            <a href="<?php echo $basePath . 'src/auth/logout.php'; ?>" class="text-gray-600 hover:text-red-500 transition-colors duration-200" title="Đăng xuất">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </a>
        <?php else: ?>
            <!-- Khi chưa đăng nhập -->
            <div class="flex justify-between items-center gap-2">
                <a href="<?php echo $basePath . 'src/auth/login.php'; ?>" 
                   class="flex items-center gap-1 sm:gap-2 px-2 py-1 text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    <span class="font-medium text-sm sm:text-base flex-shrink-0">Đăng nhập</span>
                </a>
                <a href="<?php echo $basePath . 'src/auth/register.php'; ?>" 
                   class="flex items-center gap-1 sm:gap-2 px-2 py-1 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300 transition-colors duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    <span class="font-medium text-sm sm:text-base flex-shrink-0">Đăng ký</span>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main Navigation -->
    <div class="p-4">
        <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">Lối tắt</h3>
        <ul class="mt-2 space-y-2">
            <li>
                <button class="collapsible flex items-center justify-between w-full px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3-7 3V5z" />
                        </svg>
                        <span class="font-semibold">Truyện theo dõi</span>
                    </div>
                    <svg class="h-5 w-5 transform transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
                <ul class="ml-6 mt-1 space-y-1 hidden">
                    <li><a href="<?php echo $basePath . 'follow.php'; ?>" class="block px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">Theo dõi</a></li>
                    <li><a href="<?php echo $basePath . 'history.php'; ?>" class="block px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">Lịch sử đọc</a></li>
                </ul>
            </li>
            <li>
                <button class="collapsible flex items-center justify-between w-full px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <span class="font-semibold">Truyện</span>
                    </div>
                    <svg class="h-5 w-5 transform transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
                <ul class="ml-6 mt-1 space-y-1 hidden">
                    <li><a href="<?php echo $basePath . 'advanced-search.php'; ?>" class="block px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">Tìm kiếm nâng cao</a></li>
                    <li><a href="<?php echo $basePath . 'latest.php'; ?>" class="block px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">Mới cập nhật</a></li>
                    <li><a href="<?php echo $basePath . 'recent.php'; ?>" class="block px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">Truyện mới</a></li>
                </ul>
            </li>
            <li>
                <button class="collapsible flex items-center justify-between w-full px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span class="font-semibold">Cộng đồng</span>
                    </div>
                    <svg class="h-5 w-5 transform transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
                <ul class="ml-6 mt-1 space-y-1 hidden">
                    <li><a href="<?php echo $basePath . 'forum'; ?>" class="block px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">Diễn đàn</a></li>
                    <li><a href="<?php echo $basePath . 'groups.php'; ?>" class="block px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">Nhóm dịch</a></li>
                </ul>
            </li>
            <?php if (isset($_SESSION['user']) && isset($_SESSION['user']['roles']) && $_SESSION['user']['roles'] === 'admin'): ?>
            <li>
                <button class="collapsible flex items-center justify-between w-full px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="font-semibold text-red-500">Quản trị</span>
                    </div>
                    <svg class="h-5 w-5 transform transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
                <ul class="ml-6 mt-1 space-y-1 hidden">
                    <li><a href="<?php echo $basePath . 'admin/upload-manga.php'; ?>" class="block px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">Up truyện</a></li>
                    <li><a href="<?php echo $basePath . 'admin/setting.php'; ?>" class="block px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">Setting</a></li>
                    <li><a href="<?php echo $basePath . 'admin/reports.php'; ?>" class="block px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">Báo cáo</a></li>
                </ul>
            </li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Settings -->
    <div class="p-4 border-t dark:border-gray-700">
        <div class="flex items-center gap-2">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">Tùy chỉnh</h3>
            <span class="relative group">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a5 5 0 010-7.072m0 0l2.829 2.829m-2.829 2.829L3 3" />
                </svg>
                <span class="absolute bottom-full mb-2 hidden group-hover:block w-48 p-2 bg-gray-700 text-white text-xs rounded-md">
                    Những tùy chỉnh này chỉ có hiệu lực trên thiết bị hiện tại, không đồng bộ theo tài khoản!
                </span>
            </span>
        </div>
        <ul class="mt-2 space-y-2">
            <li>
                <button id="open-theme-customizer" class="flex items-center gap-2 w-full px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                    </svg>
                    <span>Giao diện</span>
                </button>
                <div id="theme-customizer" class="ml-6 mt-2 space-y-2 hidden">
                    <div>
                        <label class="font-semibold text-sm">Màu sắc</label>
                        <div class="grid grid-cols-3 gap-2 mt-1">
                            <button data-theme="zinc" class="theme-option flex items-center justify-start px-2 py-1 border rounded-md hover:border-blue-500 text-sm">
                                <span class="w-4 h-4 rounded-full mr-2 bg-zinc-500"></span>
                                White
                            </button>
                            <button data-theme="blue" class="theme-option flex items-center justify-start px-2 py-1 border rounded-md hover:border-blue-500 text-sm">
                                <span class="w-4 h-4 rounded-full mr-2 bg-blue-500"></span>
                                Blue
                            </button>
                            <button data-theme="red" class="theme-option flex items-center justify-start px-2 py-1 border rounded-md hover:border-blue-500 text-sm">
                                <span class="w-4 h-4 rounded-full mr-2 bg-red-500"></span>
                                Red
                            </button>
                        </div>
                    </div>
                </div>
            </li>
            <li>
                <button id="open-content-customizer" class="flex items-center gap-2 w-full px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                    </svg>
                    <span>Nội dung</span>
                </button>
                <div id="content-customizer" class="ml-6 mt-2 space-y-2 hidden">
                    <div>
                        <label class="font-semibold text-sm">R18 (18+)</label>
                        <div class="grid grid-cols-2 gap-2 mt-1">
                            <button data-r18="true" class="r18-option flex items-center justify-start px-2 py-1 border rounded-md hover:border-blue-500 text-sm <?php echo $r18 ? 'bg-blue-500 text-white' : ''; ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Hiện
                            </button>
                            <button data-r18="false" class="r18-option flex items-center justify-start px-2 py-1 border rounded-md hover:border-blue-500 text-sm <?php echo !$r18 ? 'bg-blue-500 text-white' : ''; ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                                Ẩn
                            </button>
                        </div>
                    </div>
                </div>
            </li>
        </ul>
    </div>

    <!-- Supports -->
    <div class="p-4 border-t dark:border-gray-700">
        <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">Góp ý/Báo lỗi</h3>
        <ul class="mt-2 space-y-2">
            <li>
                <a href="<?php echo $siteConfig['links']['facebook']; ?>" target="_blank" class="flex items-center justify-between w-full px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z" />
                        </svg>
                        <span>Facebook</span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </a>
            </li>
        </ul>
    </div>
</aside>