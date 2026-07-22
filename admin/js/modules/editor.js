import { markFormChanged } from './forms.js';

export function initEditor() {
    initRoxyFileManager();

    // Make functions globally available for inline scripts that might still use them
    window.run_ckeditor = run_ckeditor;
    window.closeRoxySelectFile = closeRoxySelectFile;
}

function initRoxyFileManager() {
    document.addEventListener('click', (e) => {
        const roxyTrigger = e.target.closest('.open_roxy');
        if (!roxyTrigger) return;
        e.preventDefault();

        // Remove previous target
        document.querySelectorAll('.roxy_target').forEach(el => el.classList.remove('roxy_target'));

        const img = roxyTrigger.querySelector('img');
        if (img) img.classList.add('roxy_target');

        const modal = document.getElementById('roxySelectFile');
        if (modal) {
            // BS5: new bootstrap.Modal()
            const bsModal = bootstrap.Modal.getOrCreateInstance(modal);
            const iframe = modal.querySelector('iframe');
            if (iframe) {
                iframe.src = 'media_manager.php';
            }
            bsModal.show();
        }
    });
}

function closeRoxySelectFile() {
    const roxyTarget = document.querySelector('.roxy_target');
    if (!roxyTarget) return;

    const roxyName = roxyTarget.dataset.roxy_name;
    const src = roxyTarget.getAttribute('src');

    if (roxyName && src) {
        const input = document.querySelector(`[name="${CSS.escape(roxyName)}"]`);
        if (input) input.value = src;
    }

    const modal = document.getElementById('roxySelectFile');
    if (modal) {
        const bsModal = bootstrap.Modal.getInstance(modal);
        if (bsModal) bsModal.hide();
    }
}

function run_ckeditor(id, height = 200) {
    let textarea = document.getElementById(id);

    // If not found by ID, try to find by name
    if (!textarea) {
        const elements = document.getElementsByName(id);
        if (elements.length > 0) {
            textarea = elements[0];
            textarea.id = id;
        }
    }

    if (!textarea) {
        console.warn('Textarea not found for CKEditor initialization:', id);
        return;
    }

    const editorClass = (typeof CKEDITOR !== 'undefined' && CKEDITOR.ClassicEditor) ? CKEDITOR.ClassicEditor : (typeof ClassicEditor !== 'undefined' ? ClassicEditor : null);

    if (editorClass) {
        editorClass.create(textarea, {
            language: 'pl',
            toolbar: [
                'heading', '|', 'bold', 'italic', 'fontColor', 'fontFamily', 'fontSize', '|',
                'link', 'insertImage', 'mediaEmbed', 'insertTable', '|',
                'bulletedList', 'numberedList', 'indent', 'outdent', '|',
                'blockQuote', 'sourceEditing', 'findAndReplace', '|', 'undo', 'redo'
            ],
            htmlSupport: {
                allow: [
                    {
                        name: /.*/,
                        attributes: true,
                        classes: true,
                        styles: true
                    }
                ]
            }
        })
        .then(editor => {
            // Ustaw automatyczne dopasowywanie wysokości (min-height)
            editor.editing.view.change(writer => {
                writer.setStyle('min-height', height + 'px', editor.editing.view.document.getRoot());
            });

            // Automatyczna synchronizacja wprowadzanych zmian z ukrytym elementem textarea
            editor.model.document.on('change:data', () => {
                textarea.value = editor.getData();
                markFormChanged();
            });

            // Auto-save specific logic for index_page
            if (id === 'index_page') {
                const draft = localStorage.getItem('ckeditor_draft_index_page');
                if (draft && draft !== editor.getData()) {
                    if (confirm('Wykryto niezapisany szkic (draft) strony głównej z localStorage. Czy chcesz go przywrócić?')) {
                        editor.setData(draft);
                        textarea.value = draft;
                    }
                }

                setInterval(() => {
                    const currentData = editor.getData();
                    if (currentData) {
                        localStorage.setItem('ckeditor_draft_index_page', currentData);
                        showAutosaveNotification();
                    }
                }, 30000);
            }

            // Dodatkowa synchronizacja podczas zatwierdzania formularza
            const form = textarea.closest('form');
            if (form) {
                form.addEventListener('submit', () => {
                    textarea.value = editor.getData();
                    if (id === 'index_page') {
                        localStorage.removeItem('ckeditor_draft_index_page');
                    }
                });
            }
        })
        .catch(error => {
            console.error('Błąd inicjalizacji CKEditor 5:', error);
        });
    } else {
        console.warn('CKEditor 5 (ClassicEditor) not loaded');
    }
}

function showAutosaveNotification() {
    let notification = document.getElementById('autosave-notification');
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'autosave-notification';
        notification.className = 'position-fixed bottom-0 end-0 m-3 p-2 bg-success text-white rounded shadow small';
        notification.style.zIndex = '9999';
        notification.style.transition = 'opacity 0.5s ease';
        document.body.appendChild(notification);
    }
    notification.innerHTML = '<i class="bi bi-cloud-check-fill me-1"></i> Szkic zapisany automatycznie';
    notification.style.opacity = '1';
    setTimeout(() => {
        notification.style.opacity = '0';
    }, 3000);
}
