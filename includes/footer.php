<?php
require_once __DIR__ . '/track_visits.php';
$onlineUsers = rand(1, 10); 
?>

<footer class="bg-gray-800 text-white py-4">
    <div class="container mx-auto px-4 flex flex-col items-center">
        <div class="flex items-center space-x-4 mb-4">
            <a href="https://www.facebook.com/truyen0hay" target="_blank" rel="noopener noreferrer" class="hover:text-blue-400 transition-colors">
                <svg class="w-6 h-6" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M22.675 0H1.325C.593 0 0 .593 0 1.325v21.351C0 23.407.593 24 1.325 24H12.82v-9.294H9.692v-3.622h3.128V8.413c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24h-1.918c-1.504 0-1.795.715-1.795 1.763v2.313h3.587l-.467 3.622h-3.12V24h6.116c.73 0 1.323-.593 1.323-1.325V1.325C24 .593 23.407 0 22.675 0z"/>
                </svg>
            </a>
        </div>
        <!-- Copyright -->
        <div class="text-sm text-center mb-4">
            <p>© 2025 <a href="https://truyen0hay.site" class="hover:underline">truyen0hay.site</a>. All rights reserved.</p>
        </div>
        <div class="text-sm text-center">
            <p>Số lượt truy cập: <span class="font-semibold"><?php echo number_format(getTotalVisits(), 0, '.', ','); ?></span></p>
            <p>Đang truy cập: <span class="font-semibold"><?php echo $onlineUsers; ?></span></p>
        </div>
    </div>
</footer>