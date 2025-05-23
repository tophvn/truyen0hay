<div class="rounded-sm shadow-sm transition-colors duration-200 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700">
    <div class="flex gap-2 p-1">
        <?php if (!empty($chapter['manga']['title']) && !empty($chapter['manga']['cover'])): ?>
            <a href="manga.php?id=<?php echo htmlspecialchars($chapter['manga']['id']); ?>">
                <img src="<?php echo COVER_BASE_URL . '/' . htmlspecialchars($chapter['manga']['id']) . '/' . htmlspecialchars($chapter['manga']['cover']) . '.256.jpg'; ?>" 
                     alt="<?php echo htmlspecialchars($chapter['manga']['title']); ?>" 
                     class="w-20 h-28 object-cover rounded-sm"
                     onerror="this.src='/public/images/loading.png'">
            </a>
            <div class="flex flex-col justify-evenly w-full pr-1">
                <a href="manga.php?id=<?php echo htmlspecialchars($chapter['manga']['id']); ?>" 
                   class="line-clamp-2 font-bold text-lg break-words text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400">
                    <?php echo htmlspecialchars($chapter['manga']['title']); ?>
                </a>
                <div class="flex items-center space-x-1">
                    <?php if ($chapter['externalUrl']): ?>
                        <svg class="w-4 h-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 3h6v6"/>
                            <path d="M10 14 21 3"/>
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                        </svg>
                    <?php endif; ?>
                    <a href="<?php echo $chapter['externalUrl'] ? $chapter['externalUrl'] : 'chapter/' . htmlspecialchars($chapter['id']); ?>" 
                       class="hover:underline font-semibold text-sm md:text-base line-clamp-1 break-words text-blue-600 dark:text-blue-400" 
                       <?php echo $chapter['externalUrl'] ? 'target="_blank"' : ''; ?>>
                        <?php echo $chapter['chapter'] === 'Oneshot' ? 'Oneshot' : 'Chương: ' . htmlspecialchars($chapter['chapter']) . ($chapter['title'] ? ' - ' . htmlspecialchars($chapter['title']) : ''); ?>
                    </a>
                </div>
                <div class="flex justify-between">
                    <div class="flex items-center space-x-1">
                        <svg class="w-4 h-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        <?php if (empty($chapter['group'])): ?>
                            <span class="line-clamp-1 font-normal text-xs px-1 text-gray-600 dark:text-gray-400">No Group</span>
                        <?php else: ?>
                            <div class="flex items-center space-x-1">
                                <?php foreach ($chapter['group'] as $group): ?>
                                    <a href="index.php?group=<?php echo htmlspecialchars($group['id']); ?>" 
                                       class="font-normal text-xs px-1 hover:text-blue-600 dark:hover:text-blue-400 line-clamp-1 break-words text-gray-600 dark:text-gray-400">
                                        <?php echo htmlspecialchars($group['name']); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center space-x-1 max-w-max justify-end pr-1">
                        <time class="text-xs font-light line-clamp-1 text-gray-500 dark:text-gray-400" 
                              datetime="<?php echo $chapter['updatedAt']; ?>">
                            <?php echo formatTimeToNow($chapter['updatedAt']); ?>
                        </time>
                        <svg class="w-4 h-4 hidden sm:flex flex-shrink-0 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>