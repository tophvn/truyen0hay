<?php
// Đảm bảo các biến cần thiết đã được định nghĩa trước khi include file này
if (!isset($searchParams) || !isset($tagOptions) || !isset($filterOptions)) {
    return;
}
?>

<form method="GET" action="/advanced-search.php" id="search-form" class="transition-all">
    <div class="flex gap-2 items-center">
        <div class="relative flex-grow">
            <svg class="h-4 w-4 absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="q" value="<?php echo safeString($searchParams['query']); ?>" placeholder="Nhập từ khóa..." class="w-full pl-7 py-2 rounded-md bg-white border border-gray-300 focus:ring-2 focus:ring-purple-500">
        </div>
        <button type="button" id="toggle-filter" class="px-3 py-2 bg-purple-500 text-white rounded-md hover:bg-purple-600 flex items-center text-sm">
            <svg id="toggle-icon" class="h-4 w-4 mr-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            Bộ lọc
        </button>
    </div>

    <div id="filter-content" class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4 hidden">
        <!-- Tags -->
        <div class="flex flex-col gap-2">
            <label class="font-semibold">Thể loại
                <?php if (!empty($searchParams['include'])) echo " <span class='text-blue-500'>+" . count($searchParams['include']) . "</span>"; ?>
                <?php if (!empty($searchParams['exclude'])) echo " <span class='text-red-500'>-" . count($searchParams['exclude']) . "</span>"; ?>
            </label>
            <div class="dropdown-container relative">
                <button type="button" class="w-full px-3 py-2 rounded-md bg-white border border-gray-300 text-left text-gray-500 hover:bg-gray-100" onclick="toggleDropdown('tags-dropdown')">
                    <?php 
                        $included = array_filter($tagOptions, fn($t) => in_array($t['id'], $searchParams['include']));
                        $excluded = array_filter($tagOptions, fn($t) => in_array($t['id'], $searchParams['exclude']));
                        echo empty($included) && empty($excluded) ? 'Chọn thể loại' : (implode(', ', array_map(fn($t) => '+'.$t['name'], $included)) . ' ' . implode(', ', array_map(fn($t) => '-'.$t['name'], $excluded)));
                    ?>
                </button>
                <div id="tags-dropdown" class="filter-options hidden absolute top-full left-0 w-full mt-1 max-h-60 overflow-y-auto bg-white border border-gray-300 rounded-md shadow-lg z-50">
                    <?php foreach ($tagOptions as $tag): 
                        $state = in_array($tag['id'], $searchParams['include']) ? 'include' : (in_array($tag['id'], $searchParams['exclude']) ? 'exclude' : 'none');
                    ?>
                        <div class="flex items-center px-2 py-1 hover:bg-gray-100 cursor-pointer" onclick="cycleTagState('<?php echo $tag['id']; ?>', this)">
                            <span class="w-4 mr-2"><?php echo $state === 'include' ? '+' : ($state === 'exclude' ? '-' : ''); ?></span>
                            <span class="<?php echo $state === 'include' ? 'text-blue-500' : ($state === 'exclude' ? 'text-red-500' : ''); ?>"><?php echo htmlspecialchars($tag['name']); ?></span>
                            <input type="hidden" name="<?php echo $state === 'include' ? 'include[]' : ($state === 'exclude' ? 'exclude[]' : ''); ?>" value="<?php echo $tag['id']; ?>" <?php echo $state === 'none' ? 'disabled' : ''; ?>>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Author -->
        <div class="flex flex-col gap-2">
            <label class="font-semibold">Tác giả<?php if (!empty($searchParams['author'])) echo " <span class='text-blue-500'>+" . count($searchParams['author']) . "</span>"; ?></label>
            <input type="text" name="author" value="<?php echo safeString($searchParams['author']); ?>" placeholder="Tên tác giả" class="w-full px-3 py-2 rounded-md bg-white border border-gray-300">
        </div>

        <!-- Status -->
        <div class="flex flex-col gap-2">
            <label class="font-semibold">Tình trạng<?php if (!empty($searchParams['status'])) echo " <span class='text-blue-500'>+" . count($searchParams['status']) . "</span>"; ?></label>
            <div class="dropdown-container relative">
                <button type="button" class="w-full px-3 py-2 rounded-md bg-white border border-gray-300 text-left text-gray-500 hover:bg-gray-100" onclick="toggleDropdown('status-dropdown')">
                    <?php echo empty($searchParams['status']) ? 'Mặc định' : implode(', ', array_map(fn($v) => $filterOptions['status'][$v], $searchParams['status'])); ?>
                </button>
                <div id="status-dropdown" class="filter-options hidden absolute top-full left-0 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg z-50">
                    <?php foreach ($filterOptions['status'] as $val => $label): ?>
                        <label class="flex items-center p-2 hover:bg-gray-100 cursor-pointer">
                            <input type="checkbox" name="status[]" value="<?php echo $val; ?>" <?php if (in_array($val, $searchParams['status'])) echo 'checked'; ?> class="mr-2" onchange="updateDropdown('status-dropdown')">
                            <span><?php echo $label; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Demographics -->
        <div class="flex flex-col gap-2">
            <label class="font-semibold">Dành cho<?php if (!empty($searchParams['demos'])) echo " <span class='text-blue-500'>+" . count($searchParams['demos']) . "</span>"; ?></label>
            <div class="dropdown-container relative">
                <button type="button" class="w-full px-3 py-2 rounded-md bg-white border border-gray-300 text-left text-gray-500 hover:bg-gray-100" onclick="toggleDropdown('demos-dropdown')">
                    <?php echo empty($searchParams['demos']) ? 'Mặc định' : implode(', ', array_map(fn($v) => $filterOptions['demos'][$v], $searchParams['demos'])); ?>
                </button>
                <div id="demos-dropdown" class="filter-options hidden absolute top-full left-0 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg z-50">
                    <?php foreach ($filterOptions['demos'] as $val => $label): ?>
                        <label class="flex items-center p-2 hover:bg-gray-100 cursor-pointer">
                            <input type="checkbox" name="demos[]" value="<?php echo $val; ?>" <?php if (in_array($val, $searchParams['demos'])) echo 'checked'; ?> class="mr-2" onchange="updateDropdown('demos-dropdown')">
                            <span><?php echo $label; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Content Rating -->
        <div class="flex flex-col gap-2">
            <label class="font-semibold">Giới hạn nội dung<?php if (!empty($searchParams['contentRating'])) echo " <span class='text-blue-500'>+" . count($searchParams['contentRating']) . "</span>"; ?></label>
            <div class="dropdown-container relative">
                <button type="button" class="w-full px-3 py-2 rounded-md bg-white border border-gray-300 text-left text-gray-500 hover:bg-gray-100" onclick="toggleDropdown('content-dropdown')">
                    <?php echo empty($searchParams['contentRating']) ? 'Mặc định' : implode(', ', array_map(fn($v) => $filterOptions['contentRating'][$v], $searchParams['contentRating'])); ?>
                </button>
                <div id="content-dropdown" class="filter-options hidden absolute top-full left-0 w-full mt-1 grid grid-cols-2 gap-2 p-2 bg-white border border-gray-300 rounded-md shadow-lg z-50">
                    <?php foreach ($filterOptions['contentRating'] as $val => $label): ?>
                        <label class="flex items-center p-1 hover:bg-gray-100 rounded cursor-pointer">
                            <input type="checkbox" name="contentRating[]" value="<?php echo $val; ?>" <?php if (in_array($val, $searchParams['contentRating'])) echo 'checked'; ?> class="mr-2" onchange="updateDropdown('content-dropdown')">
                            <span><?php echo $label; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Origin Language -->
        <div class="flex flex-col gap-2">
            <label class="font-semibold">Ngôn ngữ gốc<?php if (!empty($searchParams['origin'])) echo " <span class='text-blue-500'>+" . count($searchParams['origin']) . "</span>"; ?></label>
            <div class="dropdown-container relative">
                <button type="button" class="w-full px-3 py-2 rounded-md bg-white border border-gray-300 text-left text-gray-500 hover:bg-gray-100" onclick="toggleDropdown('origin-dropdown')">
                    <?php echo empty($searchParams['origin']) ? 'Mặc định' : implode(', ', array_map(fn($v) => $filterOptions['origin'][$v], $searchParams['origin'])); ?>
                </button>
                <div id="origin-dropdown" class="filter-options hidden absolute top-full left-0 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg z-50">
                    <?php foreach ($filterOptions['origin'] as $val => $label): ?>
                        <label class="flex items-center p-2 hover:bg-gray-100 cursor-pointer">
                            <input type="checkbox" name="origin[]" value="<?php echo $val; ?>" <?php if (in_array($val, $searchParams['origin'])) echo 'checked'; ?> class="mr-2" onchange="updateDropdown('origin-dropdown')">
                            <span><?php echo $label; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Year -->
        <div class="flex flex-col gap-2">
            <label class="font-semibold">Năm phát hành</label>
            <div class="year-input-container relative">
                <input type="number" name="year" value="<?php echo safeString($searchParams['year']); ?>" placeholder="Mặc định" class="w-full px-3 py-2 rounded-md bg-white border border-gray-300">
                <div class="year-controls absolute right-2 top-1/2 transform -translate-y-1/2 flex gap-1">
                    <button type="button" class="decrease-year p-1 text-gray-500 hover:text-blue-500">-</button>
                    <button type="button" class="increase-year p-1 text-gray-500 hover:text-blue-500">+</button>
                </div>
            </div>
        </div>

        <!-- Translated Language -->
        <!-- <div class="flex flex-col gap-2">
            <label class="font-semibold">Có bản dịch?<?php if (!empty($searchParams['translated'])) echo " <span class='text-blue-500'>+" . count($searchParams['translated']) . "</span>"; ?></label>
            <div class="flex items-center gap-2">
                <input type="checkbox" id="hasAvailableChapter" name="availableChapter" value="true" <?php if ($searchParams['availableChapter']) echo 'checked'; ?> class="rounded">
                <label for="hasAvailableChapter">Có chương dịch</label>
            </div>
            <div class="dropdown-container relative">
                <button type="button" class="w-full px-3 py-2 rounded-md bg-white border border-gray-300 text-left text-gray-500 hover:bg-gray-100 <?php if (!$searchParams['availableChapter']) echo 'opacity-50 pointer-events-none'; ?>" onclick="toggleDropdown('translated-dropdown')">
                    <?php echo empty($searchParams['translated']) ? 'Mặc định' : implode(', ', array_map(fn($v) => $filterOptions['translated'][$v], $searchParams['translated'])); ?>
                </button>
                <div id="translated-dropdown" class="filter-options hidden absolute top-full left-0 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg z-50">
                    <?php foreach ($filterOptions['translated'] as $val => $label): ?>
                        <label class="flex items-center p-2 hover:bg-gray-100 cursor-pointer">
                            <input type="checkbox" name="translated[]" value="<?php echo $val; ?>" <?php if (in_array($val, $searchParams['translated'])) echo 'checked'; ?> class="mr-2" onchange="updateDropdown('translated-dropdown')">
                            <span><?php echo $label; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div> -->
    </div>

    <div class="flex justify-end gap-2 mt-4 flex-wrap">
        <button type="button" class="p-2 bg-gray-200 rounded-full hover:bg-gray-300" onclick="toggleDialog()">
            <svg class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/></svg>
        </button>
        <div id="guide-dialog" class="dialog hidden">
            <h2 class="text-lg font-bold">Hướng dẫn</h2>
            <p class="text-sm text-gray-500">Lưu ý: Tìm kiếm nâng cao không bị ảnh hưởng bởi Tùy chỉnh nội dung của bạn.</p>
            <div class="accordion mt-2 bg-white rounded-md shadow-sm">
                <div class="accordion-item border-b border-gray-200 last:border-b-0">
                    <div class="accordion-trigger p-2 flex justify-between items-center cursor-pointer" onclick="toggleAccordion(this)">
                        <div>
                            <span class="text-base">Thể loại</span><br>
                            <span class="text-sm text-gray-500">Mặc định: Tất cả</span>
                        </div>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                    <div class="accordion-content p-4 hidden">
                        <p>Click 1 lần để thêm <span class="text-blue-500">+Tag</span></p>
                        <p>Click 2 lần để loại trừ <span class="text-red-500">-Tag</span></p>
                        <p>Click 3 lần để reset <span class="text-gray-500">Tag</span></p>
                    </div>
                </div>
                <div class="accordion-item border-b border-gray-200 last:border-b-0">
                    <div class="accordion-trigger p-2 flex justify-between items-center cursor-pointer" onclick="toggleAccordion(this)">
                        <div>
                            <span class="text-base">Giới hạn nội dung</span><br>
                            <span class="text-sm text-gray-500">Mặc định: Safe → Erotica</span>
                        </div>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                    <div class="accordion-content p-4 hidden">
                        <ul class="list-disc pl-5">
                            <li><span class="text-green-500">Safe</span> - Lành mạnh</li>
                            <li><span class="text-yellow-400">Suggestive</span> - Hơi hơi</li>
                            <li><span class="text-red-400">Erotica</span> - Cũng tạm</li>
                            <li><span class="text-red-600">Pornographic</span> - 18+</li>
                        </ul>
                    </div>
                </div>
                <div class="accordion-item border-b border-gray-200 last:border-b-0">
                    <div class="accordion-trigger p-2 flex justify-between items-center cursor-pointer" onclick="toggleAccordion(this)">
                        <div>
                            <span class="text-base">Còn lại</span><br>
                            <span class="text-sm text-gray-500">Mặc định: Tất cả</span>
                        </div>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                    <div class="accordion-content p-4 hidden">
                        <p>:v</p>
                    </div>
                </div>
            </div>
            <button class="mt-4 px-4 py-2 bg-purple-500 text-white rounded-md hover:bg-purple-600" onclick="toggleDialog()">Đóng</button>
        </div>
        <div id="overlay" class="overlay hidden" onclick="toggleDialog()"></div>

        <button type="button" id="reset-filter" class="px-4 py-2 bg-pink-100 text-pink-500 rounded-md hover:bg-pink-200 flex items-center">
            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            Đặt lại
        </button>
        <button type="submit" class="px-4 py-2 bg-purple-500 text-white rounded-md hover:bg-purple-600 flex items-center">
            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            Tìm kiếm
        </button>
    </div>
</form>