document.addEventListener('DOMContentLoaded', () => {
    const toggleFilter = document.getElementById('toggle-filter');
    const filterContent = document.getElementById('filter-content');
    const toggleIcon = document.getElementById('toggle-icon');

    toggleFilter.addEventListener('click', () => {
        filterContent.classList.toggle('hidden');
        toggleIcon.classList.toggle('rotate-180');
    });

    window.toggleDropdown = function(dropdownId) {
        const dropdown = document.getElementById(dropdownId);
        dropdown.classList.toggle('hidden');
    };

    window.updateDropdown = function(dropdownId) {
        const dropdown = document.getElementById(dropdownId);
        const button = dropdown.previousElementSibling;
        const checked = dropdown.querySelectorAll('input:checked');
        button.textContent = checked.length ? Array.from(checked).map(c => c.nextElementSibling.textContent).join(', ') : 'Mặc định';
    };

    window.cycleTagState = function(tagId, element) {
        const currentState = element.querySelector('input').name === 'include[]' ? 'include' : (element.querySelector('input').name === 'exclude[]' ? 'exclude' : 'none');
        const nextState = currentState === 'none' ? 'include' : (currentState === 'include' ? 'exclude' : 'none');
        
        element.querySelector('span:first-child').textContent = nextState === 'include' ? '+' : (nextState === 'exclude' ? '-' : '');
        const nameSpan = element.querySelector('span:last-child');
        nameSpan.className = nextState === 'include' ? 'text-blue-500' : (nextState === 'exclude' ? 'text-red-500' : '');
        const input = element.querySelector('input');
        input.name = nextState === 'none' ? '' : `${nextState}[]`;
        input.disabled = nextState === 'none';
    };

    window.toggleDialog = function() {
        const dialog = document.getElementById('guide-dialog');
        const overlay = document.getElementById('overlay');
        dialog.classList.toggle('hidden');
        overlay.classList.toggle('hidden');
    };

    window.toggleAccordion = function(trigger) {
        const item = trigger.parentElement;
        item.classList.toggle('active');
        const content = item.querySelector('.accordion-content');
        content.classList.toggle('hidden');
    };

    const yearInput = document.querySelector('input[name="year"]');
    const decreaseBtn = document.querySelector('.decrease-year');
    const increaseBtn = document.querySelector('.increase-year');

    decreaseBtn.addEventListener('click', () => {
        let value = parseInt(yearInput.value) || 0;
        yearInput.value = value - 1 || '';
    });

    increaseBtn.addEventListener('click', () => {
        let value = parseInt(yearInput.value) || 0;
        yearInput.value = value + 1 || '';
    });

    document.getElementById('reset-filter').addEventListener('click', () => {
        document.getElementById('search-form').reset();
        filterContent.querySelectorAll('.dropdown-container button').forEach(btn => btn.textContent = 'Mặc định');
        filterContent.querySelectorAll('.tag-state-include, .tag-state-exclude').forEach(tag => {
            tag.querySelector('span:first-child').textContent = '';
            tag.querySelector('span:last-child').className = '';
            const input = tag.querySelector('input');
            input.name = '';
            input.disabled = true;
        });
    });
});