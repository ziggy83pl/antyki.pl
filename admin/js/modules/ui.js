export function initUI() {
    initDarkMode();
    initDatepickers();
    initCategoryToggle();
    initInactiveLinks();
    initModalFix();
}

function initCategoryToggle() {
    document.querySelectorAll('.option_all_categories').forEach(checkbox => {
        checkbox.addEventListener('click', () => {
            const form = checkbox.closest('form');
            if (!form) return;

            const div = form.querySelector('.option_all_categories_div');
            if (!div) return;

            const checkboxes = div.querySelectorAll('input[type="checkbox"]');

            if (checkbox.checked) {
                div.style.display = 'block';
                checkboxes.forEach(cb => cb.checked = true);
            } else {
                div.style.display = 'none';
                checkboxes.forEach(cb => cb.checked = false);
            }
        });
    });
}

function initInactiveLinks() {
    document.querySelectorAll('.inactive').forEach(link => {
        link.addEventListener('click', (e) => e.preventDefault());
    });
}

function initDatepickers() {
    const datepickers = document.querySelectorAll('.datepicker');

    if (typeof flatpickr !== 'undefined') {
        datepickers.forEach(dp => {
            flatpickr(dp, {
                dateFormat: 'Y-m-d',
                locale: 'pl'
            });
        });
    } else if (typeof $ !== 'undefined' && $.fn.datepicker) {
        datepickers.forEach(dp => {
            $(dp).datepicker({ language: 'pl', format: 'yyyy-mm-dd' });
        });
    }
}

function initModalFix() {
    document.addEventListener('hidden.bs.modal', (e) => {
        if (document.querySelectorAll('.modal.show').length > 0) {
            document.body.classList.add('modal-open');
        }
    });
}

function initDarkMode() {
    const toggleButtons = document.querySelectorAll('.theme-toggle-btn');
    if (toggleButtons.length === 0) return;

    const getTheme = () => {
        try {
            const saved = localStorage.getItem('admin-theme');
            if (saved) return saved;
        } catch (e) {}
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    };

    const updateToggleButtonState = (theme) => {
        toggleButtons.forEach(btn => {
            const icon = btn.querySelector('.theme-toggle-icon');
            const text = btn.querySelector('.theme-toggle-text');
            if (theme === 'dark') {
                if (icon) {
                    icon.classList.remove('bi-moon-fill');
                    icon.classList.add('bi-sun-fill');
                }
                if (text) text.textContent = 'Tryb jasny';
            } else {
                if (icon) {
                    icon.classList.remove('bi-sun-fill');
                    icon.classList.add('bi-moon-fill');
                }
                if (text) text.textContent = 'Tryb ciemny';
            }
        });
    };

    const setTheme = (theme) => {
        document.documentElement.setAttribute('data-theme', theme);
        try {
            localStorage.setItem('admin-theme', theme);
        } catch (e) {}
        updateToggleButtonState(theme);
        try {
            window.dispatchEvent(new CustomEvent('themechanged', { detail: { theme } }));
        } catch (err) {
            console.error('Błąd podczas rozsyłania zdarzenia themechange:', err);
        }
    };

    // Set initial state
    const currentTheme = getTheme();
    updateToggleButtonState(currentTheme);

    toggleButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const newTheme = getTheme() === 'dark' ? 'light' : 'dark';
            setTheme(newTheme);
        });
    });

    // Listen for system changes if no preference is pinned
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        try {
            if (!localStorage.getItem('admin-theme')) {
                setTheme(e.matches ? 'dark' : 'light');
            }
        } catch (err) {
            setTheme(e.matches ? 'dark' : 'light');
        }
    });
}
