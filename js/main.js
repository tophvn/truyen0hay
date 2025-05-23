document.addEventListener('DOMContentLoaded', () => {
    // Sidebar Toggle
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebarClose = document.getElementById('sidebar-close');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    const header = document.querySelector('header');
    const mainContent = document.getElementById('main-content');

    const toggleSidebar = () => {
        const isMobile = window.innerWidth <= 768;
        sidebar.classList.toggle('open');

        if (isMobile) {
            sidebarOverlay.classList.toggle('hidden');
        } else {
            header.classList.toggle('sidebar-open');
            mainContent.classList.toggle('sidebar-open');
        }

        if (sidebar.classList.contains('open')) {
            sidebarToggle.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            `;
        } else {
            sidebarToggle.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            `;
        }
    };

    const closeSidebar = () => {
        const isMobile = window.innerWidth <= 768;
        sidebar.classList.remove('open');
        if (isMobile) {
            sidebarOverlay.classList.add('hidden');
        } else {
            header.classList.remove('sidebar-open');
            mainContent.classList.remove('sidebar-open');
        }
        sidebarToggle.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        `;
    };

    if (sidebarToggle) sidebarToggle.addEventListener('click', toggleSidebar);
    if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

    // Collapsible Menu
    const collapsibles = document.querySelectorAll('.collapsible');
    collapsibles.forEach(btn => {
        btn.addEventListener('click', () => {
            const submenu = btn.nextElementSibling;
            const icon = btn.querySelector('svg:last-child');
            submenu.classList.toggle('hidden');
            icon.classList.toggle('rotate-90');
        });
    });

    // Theme Customizer (chỉ giữ phần theme màu)
    const themeCustomizer = document.getElementById('theme-customizer');
    const openThemeCustomizer = document.getElementById('open-theme-customizer');
    const themeButtons = document.querySelectorAll('.theme-option');

    if (openThemeCustomizer && themeCustomizer) {
        openThemeCustomizer.addEventListener('click', () => {
            themeCustomizer.classList.toggle('hidden');
        });
    }

    const savedTheme = localStorage.getItem('theme') || 'zinc';
    document.documentElement.setAttribute('data-theme', savedTheme);

    themeButtons.forEach(btn => {
        if (btn.getAttribute('data-theme') === savedTheme) btn.classList.add('active');
        btn.addEventListener('click', () => {
            const theme = btn.getAttribute('data-theme');
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            themeButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });

    // Content Customizer
    const contentCustomizer = document.getElementById('content-customizer');
    const openContentCustomizer = document.getElementById('open-content-customizer');
    const r18Buttons = document.querySelectorAll('.r18-option');

    if (!openContentCustomizer) console.error('open-content-customizer not found');
    if (!contentCustomizer) console.error('content-customizer not found');
    if (r18Buttons.length === 0) console.error('No r18-option buttons found');

    if (openContentCustomizer && contentCustomizer) {
        openContentCustomizer.addEventListener('click', () => {
            console.log('Open content customizer clicked');
            contentCustomizer.classList.toggle('hidden');
        });
    }

    r18Buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            const r18 = btn.getAttribute('data-r18') === 'true';
            console.log('R18 button clicked, value:', r18);

            fetch('/includes/content-customizer.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'r18=' + r18
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    console.log('R18 updated successfully');
                    r18Buttons.forEach(b => b.classList.remove('bg-blue-500', 'text-white'));
                    btn.classList.add('bg-blue-500', 'text-white');
                    window.location.reload();
                } else {
                    console.error('R18 update failed:', data.error);
                }
            })
            .catch(error => console.error('Fetch error:', error));
        });
    });

    // Scroll effect cho header
    if (header) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 0) {
                header.classList.remove('bg-transparent');
                header.classList.add('bg-background');
            } else {
                header.classList.remove('bg-background');
                header.classList.add('bg-transparent');
            }
        });
    }
});