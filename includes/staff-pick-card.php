<?php
require_once __DIR__ . '/../lib/functions.php';

$staffPicks = getStaffPicks(8); // Lấy 8 truyện
?>

<div class="mb-8">
    <hr class="w-9 h-1 bg-blue-500 border-none">
    <h1 class="text-2xl font-bold uppercase">Truyện Đề Xuất</h1>
        <div class="relative mt-4 overflow-hidden">
        <div id="staff-pick-carousel" class="flex transition-transform duration-500 ease-in-out">
            <?php if (empty($staffPicks)): ?>
                <p class="text-gray-500">Không có truyện nào được đề xuất</p>
            <?php else: ?>
                <?php foreach ($staffPicks as $index => $manga): ?>
                    <div class="flex-shrink-0 w-1/2 sm:w-1/3 md:w-1/5 lg:w-1/6 px-1" data-index="<?php echo $index; ?>">
                        <a href="<?php echo htmlspecialchars($manga['manga_link']); ?>" 
                           class="block relative rounded-sm shadow-lg transition-colors duration-200 w-full border-none manga-card">
                            <div class="relative w-full aspect-[5/7] overflow-hidden">
                                <img 
                                    src="<?php echo htmlspecialchars($manga['cover_url']); ?>" 
                                    alt="<?php echo htmlspecialchars($manga['title']); ?>" 
                                    class="absolute inset-0 w-full h-full rounded-sm object-cover object-center"
                                    loading="lazy"
                                    onerror="this.src='/public/images/loading.png'">
                            </div>
                            <div class="absolute bottom-0 p-2 bg-gradient-to-t from-black w-full rounded-b-sm h-[50%] max-h-full flex items-end">
                                <p class="text-base font-semibold line-clamp-2 hover:line-clamp-none text-white drop-shadow-md">
                                    <?php echo htmlspecialchars($manga['title']); ?>
                                </p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Navigation Buttons -->
        <?php if (count($staffPicks) > 1): ?>
            <button id="prev-btn" 
                    class="absolute left-0 top-1/2 transform -translate-y-1/2 bg-gray-800 bg-opacity-50 text-white p-2 rounded-full hover:bg-opacity-75 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <button id="next-btn" 
                    class="absolute right-0 top-1/2 transform -translate-y-1/2 bg-gray-800 bg-opacity-50 text-white p-2 rounded-full hover:bg-opacity-75 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const carousel = document.getElementById('staff-pick-carousel');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    const items = carousel.querySelectorAll('[data-index]');
    let currentIndex = 0;

    if (!carousel || !items.length || !prevBtn || !nextBtn) {
        console.error('Carousel elements not found');
        return;
    }

    const totalItems = items.length;
    const itemWidth = items[0].offsetWidth + 2;
    carousel.innerHTML += carousel.innerHTML;

    const updateCarousel = () => {
        carousel.style.transition = 'transform 0.5s ease-in-out';
        carousel.style.transform = `translateX(-${currentIndex * itemWidth}px)`;
        if (currentIndex >= totalItems) {
            setTimeout(() => {
                carousel.style.transition = 'none';
                currentIndex = 0;
                carousel.style.transform = `translateX(0px)`;
            }, 500);
        } else if (currentIndex < 0) {
            setTimeout(() => {
                carousel.style.transition = 'none';
                currentIndex = totalItems - 1;
                carousel.style.transform = `translateX(-${(totalItems - 1) * itemWidth}px)`;
            }, 500);
        }
    };

    prevBtn.addEventListener('click', () => {
        currentIndex--;
        updateCarousel();
    });

    nextBtn.addEventListener('click', () => {
        currentIndex++;
        updateCarousel();
    });

    let autoSlide = setInterval(() => {
        currentIndex++;
        updateCarousel();
    }, 3000);

    carousel.addEventListener('mouseenter', () => clearInterval(autoSlide));
    carousel.addEventListener('mouseleave', () => {
        autoSlide = setInterval(() => {
            currentIndex++;
            updateCarousel();
        }, 3000);
    });

    window.addEventListener('resize', () => {
        const newItemWidth = items[0].offsetWidth + 2;
        carousel.style.transform = `translateX(-${currentIndex * newItemWidth}px)`;
    });
});
</script>