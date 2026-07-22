document.addEventListener('DOMContentLoaded', () => {
    const generateBtn = document.getElementById('statistics_generate');
    if (!generateBtn) return;

    const generatePlot = async () => {
        try {
            const params = new URLSearchParams({
                'select_1': document.getElementById('statitics_select_1').value,
                'select_2': document.getElementById('statitics_select_2').value,
                'date_from': document.getElementById('statistics_date_from').value,
                'date_to': document.getElementById('statistics_date_to').value,
                'token': typeof getCsrfToken === 'function' ? getCsrfToken() : ''
            });

            const response = await fetch('php/statistics_ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: params
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.error || `HTTP ${response.status}`);
            }

            const data = await response.json();
            
            if (typeof $.jqplot === 'function') {
                $.jqplot('jqplot', data, createBarChartOptions()).replot();
            } else {
                console.error('jqPlot is not loaded');
            }
        } catch (error) {
            console.error('Failed to generate statistics plot:', error);
            alert('Wystąpił błąd podczas generowania wykresu: ' + error.message);
        } finally {
            generateBtn.removeAttribute('disabled');
        }
    };

    generateBtn.addEventListener('click', () => {
        generateBtn.setAttribute('disabled', 'disabled');
        generatePlot();
    });

    // Initial load
    generatePlot();

    function createBarChartOptions() {
        return {
            axes: {
                xaxis: {
                    renderer: $.jqplot.DateAxisRenderer,
                    tickOptions: {
                        formatString: '%d-%m-%Y'
                    }
                },
                yaxis: {              
                    min: 0
                }
            },
            highlighter: {
                show: true,
                sizeAdjust: 7.5
            },
            cursor: {
                show: false
            }
        };
    }
});