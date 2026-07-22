import { debounce } from './utils.js';

export function initTables() {
    initSelectAllCheckboxes();
    initTableSearch();
    initTableSort();
    initBulkActions();
}

function initSelectAllCheckboxes() {
    document.querySelectorAll('.select_checkbox').forEach(checkbox => {
        checkbox.addEventListener('click', () => {
            const parent = checkbox.closest('.parent_select_checkbox');
            if (!parent) return;

            const checkboxes = parent.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
        });
    });
}

function initTableSearch() {
    document.querySelectorAll('.card .table').forEach(table => {
        const tbody = table.querySelector('tbody');
        if (!tbody) return;
        const rows = tbody.querySelectorAll('tr');
        if (rows.length < 8) return; // Only for larger tables

        if (table.previousElementSibling && table.previousElementSibling.classList.contains('table-search-wrapper')) return;

        const searchWrapper = document.createElement('div');
        searchWrapper.className = 'table-search-wrapper mb-3';
        searchWrapper.innerHTML = `
            <div class="input-group">
                <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" class="form-control border-start-0 ps-0" placeholder="Filtruj tabelę (wpisz szukaną frazę)...">
            </div>
        `;

        table.parentNode.insertBefore(searchWrapper, table);

        const input = searchWrapper.querySelector('input');
        
        // Use debounce for performance
        const handleSearch = debounce(() => {
            const query = input.value.toLowerCase().trim();
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }, 200);

        input.addEventListener('input', handleSearch);
    });
}

function initTableSort() {
    document.querySelectorAll('.card .table').forEach(table => {
        const headers = table.querySelectorAll('thead th');
        const tbody = table.querySelector('tbody');
        if (!tbody || headers.length === 0) return;
        const rows = Array.from(tbody.querySelectorAll('tr'));
        if (rows.length < 2) return;

        headers.forEach((header, colIndex) => {
            // Skip action or command columns
            const text = header.innerText.trim();
            if (text === 'Akcja' || text === 'Action' || text === '' || header.classList.contains('no-sort')) {
                return;
            }

            header.style.cursor = 'pointer';
            header.classList.add('position-relative');

            let asc = true;
            header.addEventListener('click', () => {
                headers.forEach(h => {
                    const indicator = h.querySelector('.sort-indicator');
                    if (indicator) indicator.remove();
                });

                const indicator = document.createElement('span');
                indicator.className = 'sort-indicator ms-1 small text-muted';
                indicator.innerHTML = asc ? '▲' : '▼';
                header.appendChild(indicator);

                const sortedRows = rows.sort((a, b) => {
                    const aCol = a.cells[colIndex]?.innerText.trim() || '';
                    const bCol = b.cells[colIndex]?.innerText.trim() || '';

                    // Parse as number if numeric
                    const aNum = parseFloat(aCol.replace(/[^0-9.-]+/g, ''));
                    const bNum = parseFloat(bCol.replace(/[^0-9.-]+/g, ''));

                    if (!isNaN(aNum) && !isNaN(bNum)) {
                        return asc ? aNum - bNum : bNum - aNum;
                    }

                    return asc
                        ? aCol.localeCompare(bCol, undefined, {numeric: true, sensitivity: 'base'})
                        : bCol.localeCompare(aCol, undefined, {numeric: true, sensitivity: 'base'});
                });

                tbody.innerHTML = '';
                sortedRows.forEach(row => tbody.appendChild(row));
                asc = !asc;
            });
        });
    });
}

function initBulkActions() {
    const setupBulk = (checkAllId, cbClass, btnId, countId) => {
        const checkAll = document.getElementById(checkAllId);
        const btnBulkDelete = document.getElementById(btnId);
        const selectedCount = document.getElementById(countId);

        if (!checkAll || !btnBulkDelete) return;

        const updateButtonState = () => {
            const checkedCount = document.querySelectorAll(`${cbClass}:checked`).length;
            if (selectedCount) selectedCount.textContent = checkedCount;
            btnBulkDelete.disabled = checkedCount === 0;
        };

        checkAll.addEventListener('change', () => {
            const checkboxes = document.querySelectorAll(cbClass);
            checkboxes.forEach(cb => {
                const row = cb.closest('tr');
                if (row && row.style.display !== 'none') {
                    cb.checked = checkAll.checked;
                } else {
                    cb.checked = false;
                }
            });
            updateButtonState();
        });

        // Delegate change event for checkboxes of this class
        document.addEventListener('change', (e) => {
            if (e.target && e.target.classList.contains(cbClass.replace('.', ''))) {
                updateButtonState();
            }
        });
    };

    // Initialize for Opinions
    setupBulk('check-all', '.opinion-checkbox', 'btn-bulk-delete', 'selected-count');

    // Initialize for Articles
    setupBulk('check-all-articles', '.article-checkbox', 'btn-bulk-delete-articles', 'selected-count-articles');
}
