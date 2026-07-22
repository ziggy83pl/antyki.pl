export let formChanged = false;

export function initForms() {
    initRequiredFields();
    initOptionSelects();
    initNestedCheckboxes();
    initHiddenOptions();
    initFormSubmissions();
    initUnsavedChangesWarning();
    initPhoneMasking();
}

function initRequiredFields() {
    const toggleRequired = (checkbox) => {
        const targetClass = checkbox.dataset.target;
        if (!targetClass) return;

        const targets = document.querySelectorAll(`.${targetClass}`);
        targets.forEach(target => {
            target.required = checkbox.checked;
        });
    };

    document.querySelectorAll('.set_required').forEach(checkbox => {
        checkbox.addEventListener('click', () => toggleRequired(checkbox));
        toggleRequired(checkbox); // Initial state
    });
}

function initOptionSelects() {
    const handleChange = (select) => {
        const form = select.closest('form');
        if (!form) return;

        const optionLabel = form.querySelector('.option_label');
        const optionLabelRequired = form.querySelector('.option_label_required');

        switch(select.value) {
            case 'select':
                optionLabel?.classList.remove('d-none');
                optionLabel?.querySelector('textarea')?.removeAttribute('disabled');
                optionLabelRequired?.classList.remove('d-none');
                optionLabelRequired?.querySelector('input[type="checkbox"]')?.removeAttribute('disabled');
                break;
            case 'checkbox':
                optionLabel?.classList.remove('d-none');
                optionLabel?.querySelector('textarea')?.removeAttribute('disabled');
                optionLabelRequired?.classList.add('d-none');
                optionLabelRequired?.querySelector('input[type="checkbox"]')?.setAttribute('disabled', 'true');
                break;
            default:
                optionLabel?.classList.add('d-none');
                optionLabel?.querySelector('textarea')?.setAttribute('disabled', 'true');
                optionLabelRequired?.classList.remove('d-none');
                optionLabelRequired?.querySelector('input[type="checkbox"]')?.removeAttribute('disabled');
        }
    };

    document.querySelectorAll('.option_select').forEach(select => {
        select.addEventListener('change', () => handleChange(select));
        handleChange(select); // Initial state
    });
}

function initNestedCheckboxes() {
    document.querySelectorAll('.select_option').forEach(option => {
        option.addEventListener('click', () => {
            const depth = parseInt(option.dataset.depth, 10);
            const next = option.nextElementSibling;

            if (!next || parseInt(next.dataset.depth, 10) < depth) return;

            const checkbox = option.querySelector('input[type="checkbox"]');
            if (!checkbox) return;

            const isChecked = checkbox.checked;
            let current = option.nextElementSibling;

            while (current && parseInt(current.dataset.depth, 10) >= depth) {
                if (!current.classList.contains(`depth_${depth - 1}`)) {
                    const cb = current.querySelector('input[type="checkbox"]');
                    if (cb) cb.checked = isChecked;
                }
                current = current.nextElementSibling;
            }
        });
    });
}

function initHiddenOptions() {
    document.querySelectorAll('.link_to_hidden_option').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const id = link.dataset.id;
            if (!id) return;

            const hiddenOptions = document.querySelectorAll(`.hidden_option_${id}`);

            if (link.classList.contains('active')) {
                hiddenOptions.forEach(el => el.classList.add('d-none'));
                link.classList.remove('active');
                link.querySelector('.span_inactive')?.classList.add('d-none');
                link.querySelector('.span_active')?.classList.remove('d-none');
            } else {
                if (hiddenOptions.length) {
                    hiddenOptions.forEach(el => el.classList.remove('d-none'));
                } else {
                    link.remove();
                    return;
                }
                link.classList.add('active');
                link.querySelector('.span_active')?.classList.add('d-none');
                link.querySelector('.span_inactive')?.classList.remove('d-none');
            }
        });
    });
}

function initFormSubmissions() {
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', (e) => {
            if (e.defaultPrevented) return;

            // Respect HTML5 form validation
            if (form.checkValidity && !form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                form.classList.add('was-validated');
                return;
            }

            // Find and disable submit buttons, showing a spinner
            const submitButtons = form.querySelectorAll('input[type="submit"], button[type="submit"]');
            submitButtons.forEach(btn => {
                btn.disabled = true;

                if (btn.tagName === 'BUTTON') {
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Zapisywanie...';
                } else if (btn.tagName === 'INPUT') {
                    btn.value = 'Zapisywanie...';
                }
            });
        });
    });
}

export function markFormChanged() {
    formChanged = true;
}

export function setFormChanged(value) {
    formChanged = value;
}

function initUnsavedChangesWarning() {
    document.querySelectorAll('form').forEach(form => {
        const markChanged = () => { formChanged = true; };
        form.addEventListener('input', markChanged);
        form.addEventListener('change', markChanged);

        // Bypass warning on form submit
        form.addEventListener('submit', () => {
            formChanged = false;
        });
    });

    window.addEventListener('beforeunload', (e) => {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = ''; // Standard browser requirement
        }
    });
}

function initPhoneMasking() {
    document.addEventListener('input', (e) => {
        const target = e.target;
        if (target.tagName === 'INPUT' && (target.name === 'admin_phone' || target.type === 'tel')) {
            let value = target.value.replace(/[^\d+]/g, ''); // preserve digits and '+'
            let formatted = '';
            
            // Extract prefix plus if exists
            const hasPlus = value.startsWith('+');
            let digits = value.replace('+', '');

            if (digits.startsWith('48')) {
                // Polish calling code: +48 XXX XXX XXX
                formatted = (hasPlus ? '+' : '') + '48 ';
                let rest = digits.substring(2);
                if (rest.length > 0) {
                    formatted += rest.substring(0, 3);
                }
                if (rest.length > 3) {
                    formatted += ' ' + rest.substring(3, 6);
                }
                if (rest.length > 6) {
                    formatted += ' ' + rest.substring(6, 9);
                }
                if (rest.length > 9) {
                    formatted += ' ' + rest.substring(9, 12);
                }
            } else {
                // Default spacing format: XXX XXX XXX ...
                let rest = digits;
                formatted = (hasPlus ? '+' : '');
                if (rest.length > 0) {
                    formatted += rest.substring(0, 3);
                }
                if (rest.length > 3) {
                    formatted += ' ' + rest.substring(3, 6);
                }
                if (rest.length > 6) {
                    formatted += ' ' + rest.substring(6, 9);
                }
                if (rest.length > 9) {
                    formatted += ' ' + rest.substring(9, 12);
                }
            }
            target.value = formatted;
        }
    });
}
