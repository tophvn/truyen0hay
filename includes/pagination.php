<?php
// Kiểm tra nếu các biến cần thiết không được định nghĩa thì không hiển thị gì
if (!isset($totalPages) || !isset($page) || !isset($_GET)) {
    return;
}

// Xác định tham số phân trang (page, recentPage, hoặc latestPage)
$pageParam = isset($_GET['recentPage']) ? 'recentPage' : (isset($_GET['latestPage']) ? 'latestPage' : 'page');
$currentPage = max(1, (int)($_GET[$pageParam] ?? $page));

// Logic hiển thị phân trang giống với Recent
?>

<?php if ($totalPages > 1): ?>
    <nav class="mt-4 flex justify-center items-center" aria-label="Phân trang">
        <ul class="flex items-center gap-2">
            <!-- Nút Previous -->
            <li>
                <a href="?<?php echo http_build_query(array_merge($_GET, [$pageParam => max(1, $currentPage - 1)])); ?>" 
                   class="flex items-center justify-center w-8 h-8 rounded-full transition-colors duration-200 <?php echo $currentPage == 1 ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-purple-500 text-white hover:bg-purple-600'; ?>" 
                   <?php echo $currentPage == 1 ? 'aria-disabled="true"' : ''; ?> 
                   title="Trang trước">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
            </li>

            <?php if ($totalPages <= 7): ?>
                <!-- Hiển thị tất cả các trang nếu tổng số trang <= 7 -->
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li>
                        <a href="?<?php echo http_build_query(array_merge($_GET, [$pageParam => $i])); ?>" 
                           class="flex items-center justify-center w-8 h-8 rounded-full transition-colors duration-200 <?php echo $i == $currentPage ? 'bg-purple-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'; ?>" 
                           title="Trang <?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
            <?php elseif ($currentPage <= 4): ?>
                <!-- Gần đầu: hiển thị 1, 2, 3, 4, 5, ..., lastPage -->
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <li>
                        <a href="?<?php echo http_build_query(array_merge($_GET, [$pageParam => $i])); ?>" 
                           class="flex items-center justify-center w-8 h-8 rounded-full transition-colors duration-200 <?php echo $i == $currentPage ? 'bg-purple-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'; ?>" 
                           title="Trang <?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
                <li><span class="px-2 text-gray-500 select-none">...</span></li>
                <li>
                    <a href="?<?php echo http_build_query(array_merge($_GET, [$pageParam => $totalPages])); ?>" 
                       class="flex items-center justify-center w-8 h-8 rounded-full bg-white text-gray-700 hover:bg-gray-100 transition-colors duration-200" 
                       title="Trang cuối">
                        <?php echo $totalPages; ?>
                    </a>
                </li>
            <?php elseif ($currentPage >= $totalPages - 3): ?>
                <!-- Gần cuối: hiển thị 1, ..., lastPage-4, lastPage-3, lastPage-2, lastPage-1, lastPage -->
                <li>
                    <a href="?<?php echo http_build_query(array_merge($_GET, [$pageParam => 1])); ?>" 
                       class="flex items-center justify-center w-8 h-8 rounded-full bg-white text-gray-700 hover:bg-gray-100 transition-colors duration-200" 
                       title="Trang 1">
                        1
                    </a>
                </li>
                <li><span class="px-2 text-gray-500 select-none">...</span></li>
                <?php for ($i = $totalPages - 4; $i <= $totalPages; $i++): ?>
                    <li>
                        <a href="?<?php echo http_build_query(array_merge($_GET, [$pageParam => $i])); ?>" 
                           class="flex items-center justify-center w-8 h-8 rounded-full transition-colors duration-200 <?php echo $i == $currentPage ? 'bg-purple-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'; ?>" 
                           title="Trang <?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
            <?php else: ?>
                <!-- Ở giữa: hiển thị 1, ..., page-1, page, page+1, ..., lastPage -->
                <li>
                    <a href="?<?php echo http_build_query(array_merge($_GET, [$pageParam => 1])); ?>" 
                       class="flex items-center justify-center w-8 h-8 rounded-full bg-white text-gray-700 hover:bg-gray-100 transition-colors duration-200" 
                       title="Trang 1">
                        1
                    </a>
                </li>
                <li><span class="px-2 text-gray-500 select-none">...</span></li>
                <?php for ($i = $currentPage - 1; $i <= $currentPage + 1; $i++): ?>
                    <li>
                        <a href="?<?php echo http_build_query(array_merge($_GET, [$pageParam => $i])); ?>" 
                           class="flex items-center justify-center w-8 h-8 rounded-full transition-colors duration-200 <?php echo $i == $currentPage ? 'bg-purple-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'; ?>" 
                           title="Trang <?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
                <li><span class="px-2 text-gray-500 select-none">...</span></li>
                <li>
                    <a href="?<?php echo http_build_query(array_merge($_GET, [$pageParam => $totalPages])); ?>" 
                       class="flex items-center justify-center w-8 h-8 rounded-full bg-white text-gray-700 hover:bg-gray-100 transition-colors duration-200" 
                       title="Trang cuối">
                        <?php echo $totalPages; ?>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Nút Next -->
            <li>
                <a href="?<?php echo http_build_query(array_merge($_GET, [$pageParam => min($totalPages, $currentPage + 1)])); ?>" 
                   class="flex items-center justify-center w-8 h-8 rounded-full transition-colors duration-200 <?php echo $currentPage == $totalPages ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-purple-500 text-white hover:bg-purple-600'; ?>" 
                   <?php echo $currentPage == $totalPages ? 'aria-disabled="true"' : ''; ?> 
                   title="Trang sau">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </li>
        </ul>
    </nav>
<?php endif; ?>