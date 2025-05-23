<?php
defined('SITE_NAME') || define('SITE_NAME', 'TRUYENOHAY');
?>

<header class="fixed top-0 left-0 z-50 w-full px-4 md:px-8 lg:px-12 bg-transparent backdrop-blur-md transition-all duration-300">
    <div class="container mx-auto">
        <div class="flex h-14 items-center justify-between">
            <div>
                <a href="/" class="flex items-center gap-2">
                    <span class="text-2xl font-extrabold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-blue-500 to-purple-600 dark:from-blue-400 dark:to-purple-500 hover:from-blue-600 hover:to-purple-700 transition-all duration-300">
                        <?php echo SITE_NAME; ?>
                    </span>
                </a>
            </div>
            <div class="flex items-center gap-3">
                <!-- Search Icon for Mobile, Full Search Bar for Desktop -->
                <div class="search-container relative max-w-xs w-full">
                    <!-- Search Icon (visible on mobile) -->
                    <button id="mobile-search-toggle" class="md:hidden p-2 rounded-full bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-all duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>

                    <!-- Search Form (visible on desktop) -->
                    <form id="searchFormDesktop" action="search.php" method="GET" class="hidden md:flex items-center w-full transition-all duration-300 relative">
                        <div class="relative w-full flex justify-end">
                            <input type="text" name="q" id="searchInputDesktop" placeholder="Tìm kiếm truyện..." 
                                class="w-60 lg:w-80 xl:w-96 h-10 px-5 py-2 text-sm rounded-full bg-gray-100 dark:bg-gray-800 border-none focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 shadow-sm transition-all duration-300 focus:w-[36rem] lg:focus:w-[40rem] xl:focus:w-[48rem] focus:-translate-x-32 focus:bg-white dark:focus:bg-gray-900">
                            <div id="desktopSearchIcon" class="absolute right-4 top-1/2 transform -translate-y-1/2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <button id="desktopClearButton" type="button" class="hidden absolute right-4 top-1/2 transform -translate-y-1/2 h-6 w-6 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-all duration-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </form>

                    <!-- Modal Search Form (visible on mobile when toggled) -->
                    <div id="searchModal" class="fixed inset-0 z-50 hidden">
                        <div class="flex items-center justify-center h-14 px-4 bg-gray-100 dark:bg-gray-900 shadow-sm">
                            <form id="searchFormMobile" action="search.php" method="GET" class="flex items-center w-full max-w-md gap-2">
                                <div class="relative w-full">
                                    <input type="text" name="q" id="searchInputMobile" placeholder="Tìm kiếm truyện..." 
                                           class="w-full h-9 px-4 py-2 text-sm rounded-full bg-white dark:bg-gray-800 border-none focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 shadow-sm transition-all duration-200">
                                    <div id="mobileSearchIcon" class="absolute right-4 top-1/2 transform -translate-y-1/2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                    <button id="mobileClearButton" type="button" class="hidden absolute right-4 top-1/2 transform -translate-y-1/2 h-6 w-6 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-all duration-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <button type="button" id="closeSearchModal" class="p-2 rounded-full bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 transition-all duration-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                        <!-- Mobile Search Results -->
                        <div id="mobileSearchResults" class="w-full bg-white dark:bg-gray-800 rounded-lg shadow-lg z-40 max-h-[calc(100vh-3.5rem)] overflow-y-auto p-2"></div>
                    </div>

                    <!-- Desktop Search Results -->
                    <div id="desktopSearchResults" class="absolute top-full right-0 w-80 lg:w-[28rem] xl:w-[32rem] bg-white dark:bg-gray-800 rounded-lg shadow-lg mt-1 z-40 max-h-[80vh] overflow-y-auto p-2 hidden"></div>
                </div>

                <!-- Nút mở/đóng sidebar -->
                <button id="sidebar-toggle" class="p-2 rounded-full bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-all duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</header><br><br>