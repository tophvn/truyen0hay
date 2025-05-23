// Lưu lịch sử đọc vào localStorage nếu chưa đăng nhập
if (!window.readerData.isLoggedIn) {
    const historyEntry = {
        manga_id: window.readerData.mangaId,
        chapter_id: window.readerData.chapterId,
        chapter_number: window.readerData.chapterNumber,
        chapter_title: window.readerData.chapterTitle,
        manga_title: window.readerData.mangaTitle,
        read_at: new Date().toISOString()
    };
    let history = JSON.parse(localStorage.getItem('readingHistory') || '[]');
    history = history.filter(entry => entry.chapter_id !== window.readerData.chapterId); // Xóa bản cũ nếu có
    history.unshift(historyEntry); // Thêm bản mới lên đầu
    history = history.slice(0, 50); // Giới hạn 50 bản ghi
    localStorage.setItem('readingHistory', JSON.stringify(history));
}

// Xử lý modal danh sách chapter
const chapterListBtn = document.getElementById('chapter-list-btn');
const chapterListModal = document.getElementById('chapter-list-modal');
const closeChapterList = document.getElementById('close-chapter-list');
const closeChapterListFooter = document.getElementById('close-chapter-list-footer');

chapterListBtn.addEventListener('click', () => {
    chapterListModal.classList.remove('hidden');
});

closeChapterList.addEventListener('click', () => {
    chapterListModal.classList.add('hidden');
});

closeChapterListFooter.addEventListener('click', () => {
    chapterListModal.classList.add('hidden');
});

chapterListModal.addEventListener('click', (e) => {
    if (e.target === chapterListModal) {
        chapterListModal.classList.add('hidden');
    }
});

// Xử lý nút cuộn lên đầu
const scrollTopBtn = document.getElementById('scroll-top-btn');
window.addEventListener('scroll', () => {
    const isAtTop = window.scrollY === 0;
    scrollTopBtn.disabled = isAtTop;
    scrollTopBtn.classList.toggle('opacity-50', isAtTop);
});

scrollTopBtn.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

// Xử lý settings dialog
const settingsBtn = document.getElementById('settings-btn');
const settingsDialog = document.getElementById('settings-dialog');
const longStrip = document.getElementById('long-strip');

settingsBtn.addEventListener('click', () => {
    settingsDialog.classList.remove('hidden');
});

settingsDialog.addEventListener('click', (e) => {
    if (e.target === settingsDialog) {
        settingsDialog.classList.add('hidden');
    }
});

// Xử lý các nút trong settings dialog
const readerTypeButtons = document.querySelectorAll('[data-reader-type]');
const imageGapInput = document.getElementById('image-gap');
const resetGapBtn = document.getElementById('reset-gap');
const imageFitButtons = document.querySelectorAll('[data-image-fit]');
const headerButtons = document.querySelectorAll('[data-header]');
const themeButtons = document.querySelectorAll('[data-theme]');

// Kiểu đọc
readerTypeButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        const type = btn.getAttribute('data-reader-type');
        readerTypeButtons.forEach(b => b.classList.remove('bg-blue-100', 'border-blue-500'));
        btn.classList.add('bg-blue-100', 'border-blue-500');
        if (type === 'single') {
            alert('Chức năng "Từng trang" đang phát triển!');
        }
    });
});

// Khoảng cách giữa các ảnh
imageGapInput.addEventListener('change', (e) => {
    const gap = parseInt(e.target.value) || 0;
    longStrip.style.gap = `${gap}px`;
    localStorage.setItem('readerImageGap', gap);
});

resetGapBtn.addEventListener('click', () => {
    imageGapInput.value = 0;
    longStrip.style.gap = '0px';
    localStorage.setItem('readerImageGap', 0);
});

// Kích thước ảnh
imageFitButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        const fit = btn.getAttribute('data-image-fit');
        imageFitButtons.forEach(b => b.classList.remove('bg-blue-100', 'border-blue-500'));
        btn.classList.add('bg-blue-100', 'border-blue-500');
        const images = longStrip.querySelectorAll('img');
        images.forEach(img => {
            img.classList.toggle('max-w-full', fit === 'width');
            img.classList.toggle('max-h-screen', fit === 'height');
        });
        localStorage.setItem('readerImageFit', fit);
    });
});

// Hiển thị/ẩn header
headerButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        const show = btn.getAttribute('data-header') === 'show';
        headerButtons.forEach(b => b.classList.remove('bg-blue-100', 'border-blue-500'));
        btn.classList.add('bg-blue-100', 'border-blue-500');
        document.querySelector('header').classList.toggle('hidden', !show);
        localStorage.setItem('readerHeader', show);
    });
});

// Chuyển đổi theme
themeButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        const theme = btn.getAttribute('data-theme');
        themeButtons.forEach(b => b.classList.remove('bg-blue-100', 'border-blue-500'));
        btn.classList.add('bg-blue-100', 'border-blue-500');
        if (theme === 'dark') {
            document.body.classList.add('dark-theme');
        } else {
            document.body.classList.remove('dark-theme');
        }
        localStorage.setItem('readerTheme', theme);
    });
});

// Áp dụng các giá trị đã lưu
const savedGap = localStorage.getItem('readerImageGap') || 0;
imageGapInput.value = savedGap;
longStrip.style.gap = `${savedGap}px`;

const savedFit = localStorage.getItem('readerImageFit') || 'height';
imageFitButtons.forEach(btn => {
    if (btn.getAttribute('data-image-fit') === savedFit) {
        btn.classList.add('bg-blue-100', 'border-blue-500');
        const images = longStrip.querySelectorAll('img');
        images.forEach(img => {
            img.classList.toggle('max-w-full', savedFit === 'width');
            img.classList.toggle('max-h-screen', savedFit === 'height');
        });
    }
});

const savedHeader = localStorage.getItem('readerHeader') !== 'false';
headerButtons.forEach(btn => {
    if (btn.getAttribute('data-header') === (savedHeader ? 'show' : 'hide')) {
        btn.classList.add('bg-blue-100', 'border-blue-500');
    }
});
document.querySelector('header')?.classList.toggle('hidden', !savedHeader);

const savedTheme = localStorage.getItem('readerTheme') || 'light';
themeButtons.forEach(btn => {
    if (btn.getAttribute('data-theme') === savedTheme) {
        btn.classList.add('bg-blue-100', 'border-blue-500');
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-theme');
        }
    }
});

