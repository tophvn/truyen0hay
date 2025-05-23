document.addEventListener('DOMContentLoaded', () => {
    // Lấy các phần tử DOM
    const elements = {
        mobileSearchToggle: document.getElementById('mobile-search-toggle'),
        searchModal: document.getElementById('searchModal'),
        closeSearchModal: document.getElementById('closeSearchModal'),
        searchInputMobile: document.getElementById('searchInputMobile'),
        searchInputDesktop: document.getElementById('searchInputDesktop'),
        mobileSearchResults: document.getElementById('mobileSearchResults'),
        desktopSearchResults: document.getElementById('desktopSearchResults'),
        mobileClearButton: document.getElementById('mobileClearButton'),
        mobileSearchIcon: document.getElementById('mobileSearchIcon'),
        desktopClearButton: document.getElementById('desktopClearButton'),
        desktopSearchIcon: document.getElementById('desktopSearchIcon'),
        searchFormMobile: document.getElementById('searchFormMobile'),
        searchFormDesktop: document.getElementById('searchFormDesktop'),
        searchInput: document.getElementById('searchInput'), // Từ main.js
        searchResults: document.getElementById('searchResults'), // Từ main.js
        searchOverlay: document.getElementById('searchOverlay'), // Từ main.js
        searchForm: document.getElementById('searchForm'), // Từ main.js
        searchContainer: document.querySelector('.search-container')
    };

    let mobileDebounceTimeout, desktopDebounceTimeout, simpleDebounceTimeout;

    // Hàm render kết quả tìm kiếm (dùng chung cho mobile và desktop)
    const renderSearchResults = (resultsElement, data, query, isSimple = false) => {
        resultsElement.innerHTML = '';
        if (data.length === 0) {
            resultsElement.innerHTML = '<p class="text-gray-500 dark:text-gray-400 p-2">Không có kết quả</p>';
        } else {
            // Nếu là giao diện đơn giản (từ main.js)
            if (isSimple) {
                data.forEach(manga => {
                    resultsElement.innerHTML += `
                        <a href="manga.php?id=${manga.id}" class="block p-2 bg-white dark:bg-gray-800 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                            <div class="flex gap-2">
                                <img src="${manga.cover}" alt="${manga.title}" class="w-14 h-20 object-cover rounded-md">
                                <div>
                                    <p class="font-bold">${manga.title}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">${manga.status}</p>
                                </div>
                            </div>
                        </a>
                    `;
                });
            } else {
                // Giao diện nâng cao (từ search.js)
                resultsElement.innerHTML = `
                    <div class="mb-2 flex justify-between items-center">
                        <p class="font-bold text-lg">Manga</p>
                        <a href="/search.php?q=${encodeURIComponent(query)}" class="text-blue-500 hover:underline text-sm flex items-center gap-1">
                            Tìm kiếm nâng cao
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                `;
                data.forEach(manga => {
                    resultsElement.innerHTML += `
                        <a href="manga.php?id=${manga.id}" class="block p-2 bg-white dark:bg-gray-800 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                            <div class="flex gap-2">
                                <img src="${manga.cover}" alt="${manga.title}" class="w-14 h-20 object-cover rounded-md">
                                <div class="flex-1">
                                    <p class="font-bold text-base line-clamp-1">${manga.title}</p>
                                    <div class="mt-1">
                                        <span class="inline-block px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full">
                                            ${manga.status || 'N/A'}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    `;
                });
            }
        }
        resultsElement.style.display = 'block';
        if (elements.searchOverlay && isSimple) {
            elements.searchOverlay.style.display = 'block';
        }
    };

    // Hàm xử lý tìm kiếm chung (dùng cho cả mobile, desktop và giao diện đơn giản)
    const handleSearch = (input, resultsElement, clearButton, searchIcon, debounceTimeout, isDesktop = false, isSimple = false) => {
        clearTimeout(debounceTimeout);
        debounceTimeout = setTimeout(() => {
            const query = input.value.trim();

            // Hiển thị/xóa nút "X" và icon tìm kiếm (chỉ áp dụng cho mobile/desktop)
            if (!isSimple) {
                if (query.length > 0) {
                    clearButton.classList.remove('hidden');
                    searchIcon.classList.add('hidden');
                } else {
                    clearButton.classList.add('hidden');
                    searchIcon.classList.remove('hidden');
                    resultsElement.style.display = 'none';
                    if (elements.searchOverlay) {
                        elements.searchOverlay.style.display = 'none';
                    }
                    return;
                }
            } else {
                // Giao diện đơn giản (từ main.js)
                if (query.length === 0) {
                    resultsElement.style.display = 'none';
                    if (elements.searchOverlay) {
                        elements.searchOverlay.style.display = 'none';
                    }
                    return;
                }
            }

            fetch(`/search-suggestions.php?q=${encodeURIComponent(query)}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    renderSearchResults(resultsElement, data, query, isSimple);
                    if (isDesktop && !isSimple) {
                        Object.assign(resultsElement.style, {
                            position: 'absolute',
                            top: '100%',
                            left: 'auto',
                            right: '0'
                        });
                    }
                })
                .catch(error => {
                    console.error(`${isSimple ? 'Simple' : isDesktop ? 'Desktop' : 'Mobile'} Search error:`, error);
                    resultsElement.innerHTML = '<p class="text-gray-500 dark:text-gray-400 p-2">Lỗi tìm kiếm</p>';
                    resultsElement.style.display = 'block';
                    if (elements.searchOverlay && isSimple) {
                        elements.searchOverlay.style.display = 'block';
                    }
                    if (isDesktop && !isSimple) {
                        Object.assign(resultsElement.style, {
                            position: 'absolute',
                            top: '100%',
                            left: 'auto',
                            right: '0'
                        });
                    }
                });
        }, 500);
        return debounceTimeout;
    };

    // Hàm xử lý xóa query (dùng chung cho mobile và desktop)
    const handleClear = (input, clearButton, searchIcon, resultsElement) => {
        input.value = '';
        clearButton.classList.add('hidden');
        searchIcon.classList.remove('hidden');
        resultsElement.style.display = 'none';
        if (elements.searchOverlay) {
            elements.searchOverlay.style.display = 'none';
        }
        input.focus();
    };

    // Hàm xử lý submit form (dùng chung cho tất cả)
    const handleSubmit = (e, input) => {
        e.preventDefault();
        const query = input.value.trim();
        if (query) {
            window.location.href = `search.php?q=${encodeURIComponent(query)}`;
        }
    };

    // Mở modal trên mobile
    if (elements.mobileSearchToggle) {
        elements.mobileSearchToggle.addEventListener('click', () => {
            elements.searchModal.classList.remove('hidden');
            elements.searchInputMobile.focus();
        });
    }

    // Đóng modal trên mobile
    if (elements.closeSearchModal) {
        elements.closeSearchModal.addEventListener('click', () => {
            elements.searchModal.classList.add('hidden');
            handleClear(elements.searchInputMobile, elements.mobileClearButton, elements.mobileSearchIcon, elements.mobileSearchResults);
        });
    }

    // Gắn sự kiện tìm kiếm cho mobile
    if (elements.searchInputMobile) {
        elements.searchInputMobile.addEventListener('input', () => {
            mobileDebounceTimeout = handleSearch(
                elements.searchInputMobile,
                elements.mobileSearchResults,
                elements.mobileClearButton,
                elements.mobileSearchIcon,
                mobileDebounceTimeout
            );
        });
    }

    // Gắn sự kiện tìm kiếm cho desktop
    if (elements.searchInputDesktop) {
        elements.searchInputDesktop.addEventListener('input', () => {
            desktopDebounceTimeout = handleSearch(
                elements.searchInputDesktop,
                elements.desktopSearchResults,
                elements.desktopClearButton,
                elements.desktopSearchIcon,
                desktopDebounceTimeout,
                true
            );
        });
    }

    // Gắn sự kiện tìm kiếm cho giao diện đơn giản (từ main.js)
    if (elements.searchInput) {
        elements.searchInput.addEventListener('input', () => {
            simpleDebounceTimeout = handleSearch(
                elements.searchInput,
                elements.searchResults,
                null, // Không có clearButton
                null, // Không có searchIcon
                simpleDebounceTimeout,
                false,
                true // isSimple = true
            );
        });
    }

    // Gắn sự kiện xóa query cho mobile
    if (elements.mobileClearButton) {
        elements.mobileClearButton.addEventListener('click', () => {
            handleClear(elements.searchInputMobile, elements.mobileClearButton, elements.mobileSearchIcon, elements.mobileSearchResults);
        });
    }

    // Gắn sự kiện xóa query cho desktop
    if (elements.desktopClearButton) {
        elements.desktopClearButton.addEventListener('click', () => {
            handleClear(elements.searchInputDesktop, elements.desktopClearButton, elements.desktopSearchIcon, elements.desktopSearchResults);
        });
    }

    // Gắn sự kiện submit form
    if (elements.searchFormMobile) {
        elements.searchFormMobile.addEventListener('submit', (e) => handleSubmit(e, elements.searchInputMobile));
    }
    if (elements.searchFormDesktop) {
        elements.searchFormDesktop.addEventListener('submit', (e) => handleSubmit(e, elements.searchInputDesktop));
    }
    if (elements.searchForm) {
        elements.searchForm.addEventListener('submit', (e) => handleSubmit(e, elements.searchInput));
    }

    // Ẩn gợi ý khi click ra ngoài (gộp logic từ main.js và search.js)
    document.addEventListener('click', (e) => {
        if (elements.searchContainer) {
            // Ẩn kết quả desktop (từ search.js)
            if (!elements.searchContainer.contains(e.target) && !elements.searchModal?.contains(e.target)) {
                if (elements.desktopSearchResults) {
                    elements.desktopSearchResults.style.display = 'none';
                }
            }
            // Ẩn kết quả đơn giản (từ main.js)
            if (!elements.searchContainer.contains(e.target)) {
                if (elements.searchResults) {
                    elements.searchResults.style.display = 'none';
                }
                if (elements.searchOverlay) {
                    elements.searchOverlay.style.display = 'none';
                }
            }
        }
    });

    // Hiển thị gợi ý khi focus vào input trên desktop
    if (elements.searchInputDesktop) {
        elements.searchInputDesktop.addEventListener('focus', () => {
            if (elements.searchInputDesktop.value.trim().length > 0) {
                elements.desktopSearchResults.style.display = 'block';
                Object.assign(elements.desktopSearchResults.style, {
                    position: 'absolute',
                    top: '100%',
                    left: 'auto',
                    right: '0'
                });
            }
        });
    }
});