<?php
// Popular Swiper
function render_popular_swiper($popularMangas) {
    if (empty($popularMangas)) {
        render_popular_slide_skeleton();
        return;
    }
    ?>
    <div class="relative">
        <div class="absolute z-10">
            <hr class="w-9 h-1 bg-blue-500 border-none">
            <h1 class="text-2xl font-bold uppercase">Tiêu điểm</h1>
        </div>
        <div class="popular-swiper pt-12" style="height: 430px;">
            <div class="swiper-wrapper">
                <?php foreach ($popularMangas as $index => $manga) { ?>
                    <div class="swiper-slide">
                        <?php render_popular_slide($manga); ?>
                    </div>
                <?php } ?>
            </div>
            <div class="absolute flex gap-2 w-full bottom-0 left-0 z-10 justify-between md:justify-end items-center px-4 md:pr-16 lg:pr-20">
                <?php render_popular_slide_control($index); ?>
            </div>
            <div class="popular-swiper-button-prev"></div>
            <div class="popular-swiper-button-next"></div>
        </div>
    </div>
    <?php
}

// Popular Slide
function render_popular_slide($manga) {
    $bannerSrc = "https://api.suicaodex.com/covers/{$manga['id']}/{$manga['cover']}";
    ?>
    <div class="absolute h-[324px] md:h-[400px] z-[-2] w-auto left-0 right-0 top-0 block">
        <div class="absolute h-[324px] md:h-[400px] w-full bg-no-repeat bg-cover bg-center" style="background-image: url('<?php echo $bannerSrc; ?>');"></div>
        <div class="absolute h-[324px] md:h-[400px] w-auto inset-0 pointer-events-none" style="background: linear-gradient(to bottom, rgba(243, 244, 246, 0.25) 0%, rgb(243, 244, 246) 100%);"></div>
    </div>
    <div class="flex gap-4 h-full pt-28 px-4 md:pl-8 lg:pl-12 md:pr-16 lg:pr-20">
        <a href="/manga/<?php echo $manga['id']; ?>">
            <img src="https://uploads.mangadex.org/covers/<?php echo $manga['id']; ?>/<?php echo $manga['cover']; ?>.256.jpg" alt="<?php echo htmlspecialchars($manga['title']); ?>" class="w-[130px] md:w-[200px] lg:w-[215px] h-auto shadow-md object-cover">
        </a>
        <div class="grid gap-6 sm:gap-2 h-full min-h-0 pb-8 md:pb-1.5 lg:pb-1 <?php echo isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false ? 'grid-rows-[1fr_auto]' : 'grid-rows-[max-content_min-content_auto_max-content]'; ?>">
            <a href="/manga/<?php echo $manga['id']; ?>">
                <p class="drop-shadow-md font-bold text-xl sm:line-clamp-2 lg:text-4xl overflow-hidden lg:leading-[2.75rem] line-clamp-5"><?php echo htmlspecialchars($manga['title']); ?></p>
            </a>
            <div class="hidden md:flex flex-wrap gap-1">
                <span class="bg-gray-200 text-gray-800 text-xs font-medium px-2 py-1 rounded"><?php echo htmlspecialchars($manga['contentRating']); ?></span>
                <span class="bg-gray-200 text-gray-800 text-xs font-medium px-2 py-1 rounded"><?php echo htmlspecialchars($manga['status']); ?></span>
            </div>
            <div class="hidden md:block min-h-0 relative overflow-auto">
                <div class="relative overflow-hidden">
                    <p class="text-sm"><?php echo htmlspecialchars($manga['description']['content']); ?></p>
                </div>
            </div>
            <p class="self-end text-base md:text-lg italic font-medium line-clamp-1 max-w-full md:max-w-[80%]">
                <?php echo implode(', ', array_unique(array_merge(array_column($manga['author'], 'name'), array_column($manga['artist'], 'name')))); ?>
            </p>
        </div>
    </div>
    <?php
}

// Popular Slide Control
function render_popular_slide_control($index) {
    ?>
    <p class="hidden md:flex text-sm font-bold uppercase <?php echo $index === 0 ? 'text-blue-500' : ''; ?>">No. <?php echo $index + 1; ?></p>
    <button class="h-8 w-8 md:h-10 md:w-10 bg-transparent hover:bg-transparent hover:text-blue-500 rounded-full text-inherit flex items-center justify-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
    </button>
    <p class="md:hidden text-sm uppercase"><?php echo $index + 1; ?> / 10</p>
    <button class="h-8 w-8 md:h-10 md:w-10 bg-transparent hover:bg-transparent hover:text-blue-500 rounded-full text-inherit flex items-center justify-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
    </button>
    <?php
}

function render_popular_slide_skeleton() {
    ?>
    <div class="absolute z-10">
        <hr class="w-9 h-1 bg-blue-500 border-none">
        <h1 class="text-2xl font-bold uppercase">Tiêu điểm</h1>
    </div>
    <div class="flex flex-col gap-4 pt-12">
        <div class="flex flex-row gap-4">
            <div class="relative bg-white rounded-md">
                <div class="w-[130px] md:w-[200px] h-[182px] md:h-[285px] lg:w-[215px] lg:h-[307px] bg-gray-500 rounded-md"></div>
            </div>
            <div class="flex flex-col w-full justify-between">
                <div class="flex flex-col gap-4">
                    <div class="w-full h-12 bg-gray-500 rounded-md"></div>
                    <div class="hidden md:flex w-2/3 h-6 bg-gray-500 rounded-md"></div>
                </div>
                <div class="w-1/2 h-4 md:h-8 bg-gray-500 rounded-md"></div>
            </div>
        </div>
        <div class="flex md:hidden justify-between">
            <div class="w-1/12 h-5 bg-gray-500 rounded-full"></div>
            <div class="w-1/6 h-5 bg-gray-500 rounded-md"></div>
            <div class="w-1/12 h-5 bg-gray-500 rounded-full"></div>
        </div>
    </div>
    <?php
}

// Completed Swiper
function render_completed_swiper($completedMangas) {
    if (empty($completedMangas)) {
        ?>
        <div class="flex flex-col">
            <hr class="w-9 h-1 bg-blue-500 border-none">
            <h1 class="text-2xl font-bold uppercase">Đã hoàn thành</h1>
            <div class="mt-4 w-full h-[280px] md:h-[400px] rounded-sm bg-gray-500"></div>
        </div>
        <?php
        return;
    }
    ?>
    <div class="grid grid-cols-1 gap-4">
        <div class="flex justify-between">
            <div>
                <hr class="w-9 h-1 bg-blue-500 border-none">
                <h1 class="text-2xl font-bold uppercase">Đã hoàn thành</h1>
            </div>
            <a href="/advanced-search?status=completed" class="h-10 w-10 flex items-center justify-center text-gray-500 hover:text-blue-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            </a>
        </div>
        <div class="overflow-hidden">
            <div class="completed-swiper">
                <div class="swiper-wrapper">
                    <?php foreach ($completedMangas as $index => $manga) { ?>
                        <div class="swiper-slide">
                            <?php render_completed_card($manga); ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// Completed Card
function render_completed_card($manga) {
    $src = "https://api.suicaodex.com/covers/{$manga['id']}/{$manga['cover']}.512.jpg";
    ?>
    <div class="relative rounded-sm shadow-none transition-colors duration-200 w-full h-full border-none bg-white">
        <div class="relative p-0 rounded-sm">
            <div class="z-10 flex rounded-sm opacity-0 hover:opacity-100 transition-opacity absolute inset-0 bg-black bg-opacity-75">
                <div class="p-2.5 grid grid-cols-1 gap-2 justify-between">
                    <p class="text-sm text-white overflow-auto"><?php echo htmlspecialchars($manga['description']['content']); ?></p>
                    <a href="/manga/<?php echo $manga['id']; ?>" class="self-end h-10 w-10 flex items-center justify-center bg-gray-700 text-white hover:bg-blue-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </a>
                </div>
            </div>
            <img src="<?php echo $src; ?>" alt="Ảnh bìa <?php echo htmlspecialchars($manga['title']); ?>" class="h-auto w-full rounded-sm block object-cover aspect-[5/7]" onerror="this.src='/public/images/manga_loading.webp';">
        </div>
        <div class="py-2 px-0 w-full">
            <a href="/manga/<?php echo $manga['id']; ?>">
                <p class="text-base font-semibold line-clamp-2 drop-shadow-sm"><?php echo htmlspecialchars($manga['title']); ?></p>
            </a>
        </div>
    </div>
    <?php
}
?>