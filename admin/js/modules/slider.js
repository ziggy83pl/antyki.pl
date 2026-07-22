export function initSlider() {
    initSlideRemovers();
    initSlideSorting();
    initLiveImagePreview();
}

function initSlideRemovers() {
    document.querySelectorAll('.link_remove_slide').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const id = link.dataset.id;
            if (id) {
                const element = document.getElementById(id);
                if (element) {
                    element.remove();
                    reindexSlides();
                }
            }
        });
    });
}

function initSlideSorting() {
    const container = document.getElementById('slides-container');
    if (!container) return;

    const cards = container.querySelectorAll('.card');
    cards.forEach(card => {
        card.setAttribute('draggable', 'true');

        const header = card.querySelector('.card-header');
        if (header) {
            header.style.cursor = 'grab';
            if (!header.querySelector('.drag-handle')) {
                const handle = document.createElement('span');
                handle.className = 'drag-handle bi bi-grid-3x3-gap text-muted me-2';
                handle.style.cursor = 'grab';
                header.insertBefore(handle, header.firstChild);
            }
        }

        card.addEventListener('dragstart', (e) => {
            card.classList.add('dragging');
            if (header) header.style.cursor = 'grabbing';
            e.dataTransfer.effectAllowed = 'move';
        });

        card.addEventListener('dragend', () => {
            card.classList.remove('dragging');
            if (header) header.style.cursor = 'grab';
            reindexSlides();
        });
    });

    container.addEventListener('dragover', (e) => {
        e.preventDefault();
        const draggingCard = container.querySelector('.dragging');
        if (!draggingCard) return;

        const afterElement = getDragAfterElement(container, e.clientY);
        if (afterElement == null) {
            container.appendChild(draggingCard);
        } else {
            container.insertBefore(draggingCard, afterElement);
        }
    });

    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.card:not(.dragging)')];

        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }
}

function reindexSlides() {
    const cards = document.querySelectorAll('#slides-container .card');
    cards.forEach((card, index) => {
        // Update name attributes for inputs and textareas
        card.querySelectorAll('[name]').forEach(input => {
            const name = input.getAttribute('name');
            const newName = name.replace(/\[\d+\]/, `[${index}]`);
            input.setAttribute('name', newName);

            const id = input.getAttribute('id');
            if (id) {
                const newId = id.replace(/_\d+$/, `_${index}`);
                input.setAttribute('id', newId);
            }
        });

        // Update label htmlFor attributes
        card.querySelectorAll('label[for]').forEach(label => {
            const htmlFor = label.getAttribute('for');
            const newFor = htmlFor.replace(/_\d+$/, `_${index}`);
            label.setAttribute('for', newFor);
        });

        // Update image data-roxy_name attributes
        card.querySelectorAll('[data-roxy_name]').forEach(img => {
            const roxyName = img.getAttribute('data-roxy_name');
            const newRoxyName = roxyName.replace(/\[\d+\]/, `[${index}]`);
            img.setAttribute('data-roxy_name', newRoxyName);
        });

        // Update slide title index text
        const titleSpan = card.querySelector('.card-title');
        if (titleSpan) {
            titleSpan.innerHTML = titleSpan.innerHTML.replace(/\d+$/, index + 1);
        }

        // Update link remove dataset ID
        const removeLink = card.querySelector('.link_remove_slide');
        if (removeLink) {
            removeLink.setAttribute('data-id', `box_slide_${index}`);
        }

        // Update card box slide ID
        card.id = `box_slide_${index}`;
    });
}

function initLiveImagePreview() {
    document.addEventListener('input', (e) => {
        const target = e.target;
        if (target.tagName === 'INPUT' && target.type === 'text') {
            const name = target.getAttribute('name');
            if (name) {
                const form = target.closest('form') || document;
                const img = form.querySelector(`img[data-roxy_name="${CSS.escape(name)}"]`);
                if (img) {
                    img.src = target.value.trim() || 'images/no_image.png';
                }
            }
        }
    });
}
