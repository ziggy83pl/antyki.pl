import { setFormChanged } from './forms.js';

export function initAjaxHandlers() {
    // Standard AJAX
    document.querySelectorAll('.ajax:not(.inactive)').forEach(link => {
        link.addEventListener('click', async (e) => {
            e.preventDefault();
            const mydata = link.dataset;

            try {
                const response = await fetch('php/ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        'data': JSON.stringify(mydata),
                        'send': 'ok'
                    })
                });

                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                const data = await response.text();
                console.log(data);
                setFormChanged(false);
                window.location.reload(); 
            } catch (error) {
                console.error('AJAX Error:', error);
                alert('Wystąpił błąd podczas wykonywania akcji. Spróbuj ponownie.');
            }
        });
    });

    // AJAX with confirmation
    document.querySelectorAll('.ajax_confirm:not(.inactive)').forEach(link => {
        link.addEventListener('click', async (e) => {
            e.preventDefault();
            const title = link.dataset.title || 'Czy na pewno?';

            if (!confirm(title)) return;

            const mydata = link.dataset;

            try {
                const response = await fetch('php/ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        'data': JSON.stringify(mydata),
                        'send': 'ok'
                    })
                });

                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                setFormChanged(false);
                window.location.reload();
            } catch (error) {
                console.error('AJAX Confirm Error:', error);
                alert('Wystąpił błąd podczas wykonywania akcji. Spróbuj ponownie.');
            }
        });
    });
}
