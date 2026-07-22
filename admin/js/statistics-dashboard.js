/**
 * Modern Statistics Dashboard
 * Chart.js + Dark Mode + Vanilla JS
 */

(function() {
    'use strict';

    const config = window.STAT_CONFIG || {};
    const csrfToken = config.csrfToken || '';
    const lang = config.lang || {};

    // DOM refs
    const els = {
        select1: document.getElementById('stat_select_1'),
        select2: document.getElementById('stat_select_2'),
        dateFrom: document.getElementById('stat_date_from'),
        dateTo: document.getElementById('stat_date_to'),
        generateBtn: document.getElementById('stat_generate'),
        refreshBtn: document.getElementById('btn-refresh-stats'),
        exportBtn: document.getElementById('btn-export-csv'),
        canvas: document.getElementById('statsChart'),
        skeleton: document.getElementById('chart-skeleton'),
        empty: document.getElementById('chart-empty'),
        wrapper: document.getElementById('chart-wrapper')
    };

    let chartInstance = null;
    let currentData = null;

    function getTheme() {
        return document.documentElement.getAttribute('data-theme') || 'light';
    }

    // ── Chart Colors by Theme ──────────────────────────────
    function getChartColors(theme) {
        const isDark = theme === 'dark';
        return {
            grid: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.03)',
            text: isDark ? '#94a3b8' : '#5c636a',
            tooltipBg: isDark ? 'rgba(15,23,42,0.95)' : 'rgba(33,37,41,0.95)',
            tooltipBorder: isDark ? 'rgba(255,255,255,0.1)' : 'transparent'
        };
    }

    function updateChartTheme(theme) {
        const c = getChartColors(theme);
        chartInstance.options.scales.x.grid.color = c.grid;
        chartInstance.options.scales.y.grid.color = c.grid;
        chartInstance.options.scales.x.ticks.color = c.text;
        chartInstance.options.scales.y.ticks.color = c.text;
        chartInstance.options.plugins.tooltip.backgroundColor = c.tooltipBg;
        chartInstance.options.plugins.tooltip.borderColor = c.tooltipBorder;
        chartInstance.options.plugins.tooltip.borderWidth = 1;
        chartInstance.update('none');
    }

    // ── Metric Labels ──────────────────────────────────────
    const metricLabels = {
        'logins': 'Logins',
        'unique_logins': 'Unique Logins',
        'registration': 'Registrations',
        'activation_users': 'Activations',
        'offers': 'Added Offers',
        'views_offers': 'Offer Views'
    };

    // ── Initialize ───────────────────────────────────────────
    function init() {
        if (!els.canvas) return;

        // Listen for global theme changes
        window.addEventListener('themechanged', function(e) {
            try {
                if (chartInstance) {
                    updateChartTheme(e.detail.theme);
                }
            } catch (err) {
                console.error('Błąd podczas aktualizacji motywu wykresu:', err);
            }
        });

        // Inputs
        const today = new Date().toISOString().split('T')[0];
        els.dateFrom.max = today;
        els.dateTo.max = today;

        // Events
        els.generateBtn.addEventListener('click', loadData);
        els.refreshBtn.addEventListener('click', loadData);
        els.exportBtn.addEventListener('click', exportCSV);

        // Auto-load
        loadData();

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem('admin-theme')) {
                setTheme(e.matches ? 'dark' : 'light');
            }
        });
    }

    // ── Loading State ──────────────────────────────────────
    function setLoading(loading) {
        if (loading) {
            els.skeleton.classList.remove('d-none');
            els.wrapper.classList.add('d-none');
            els.empty.classList.add('d-none');
            els.generateBtn.disabled = true;
            els.generateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + (lang.loading || 'Loading...');
        } else {
            els.skeleton.classList.add('d-none');
            els.generateBtn.disabled = false;
            els.generateBtn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Generate';
        }
    }

    // ── Fetch Data ─────────────────────────────────────────
    async function loadData() {
        const dateFrom = els.dateFrom.value;
        const dateTo = els.dateTo.value;

        if (!dateFrom || !dateTo) {
            alert('Please select both dates');
            return;
        }
        if (dateFrom > dateTo) {
            alert('Start date must be before end date');
            return;
        }

        setLoading(true);

        try {
            const formData = new FormData();
            formData.append('select_1', els.select1.value);
            formData.append('select_2', els.select2.value);
            formData.append('date_from', dateFrom);
            formData.append('date_to', dateTo);
            formData.append('token', csrfToken);

            const response = await fetch('php/statistics_ajax.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!response.ok) {
                const err = await response.json().catch(() => ({}));
                throw new Error(err.error || 'HTTP ' + response.status);
            }

            const data = await response.json();
            currentData = data;

            if (data.error) throw new Error(data.error);

            renderChart(data);

        } catch (err) {
            console.error('Statistics error:', err);
            els.empty.innerHTML = `
                <i class="bi bi-exclamation-triangle text-danger fs-1 mb-3 d-block"></i>
                <p class="text-danger mb-0">${lang.error || 'Error'}: ${err.message}</p>
            `;
            els.empty.classList.remove('d-none');
            els.wrapper.classList.add('d-none');
        } finally {
            setLoading(false);
        }
    }

    // ── Render Chart ───────────────────────────────────────
    function renderChart(data) {
        const datasets = data.datasets || [];
        const hasData = datasets.some(ds => ds.data.some(pt => pt.y > 0));

        if (!hasData) {
            els.empty.classList.remove('d-none');
            els.wrapper.classList.add('d-none');
            return;
        }

        els.empty.classList.add('d-none');
        els.wrapper.classList.remove('d-none');

        if (chartInstance) chartInstance.destroy();

        const theme = getTheme();
        const c = getChartColors(theme);
        const ctx = els.canvas.getContext('2d');

        chartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                datasets: datasets.map((ds) => ({
                    ...ds,
                    label: metricLabels[ds.label] || ds.label,
                    pointBackgroundColor: ds.borderColor,
                    pointBorderColor: theme === 'dark' ? '#1e293b' : '#fff',
                    pointBorderWidth: 2,
                    borderWidth: 2.5
                }))
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 20,
                            color: theme === 'dark' ? '#e2e8f0' : '#212529',
                            font: { size: 12, family: "'Segoe UI', system-ui, sans-serif" }
                        }
                    },
                    tooltip: {
                        backgroundColor: c.tooltipBg,
                        titleColor: theme === 'dark' ? '#e2e8f0' : '#fff',
                        bodyColor: theme === 'dark' ? '#cbd5e1' : '#fff',
                        borderColor: c.tooltipBorder,
                        borderWidth: 1,
                        titleFont: { size: 13 },
                        bodyFont: { size: 12 },
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: true,
                        callbacks: {
                            title: (ctx) => {
                                const d = new Date(ctx[0].parsed.x);
                                return d.toLocaleDateString(undefined, {
                                    weekday: 'short', year: 'numeric', month: 'short', day: 'numeric'
                                });
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        type: 'time',
                        time: { unit: 'day', displayFormats: { day: 'dd MMM' } },
                        grid: { color: c.grid, drawBorder: false },
                        ticks: { color: c.text, font: { size: 11 }, maxRotation: 45 }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: c.grid, drawBorder: false },
                        ticks: { color: c.text, font: { size: 11 }, precision: 0 }
                    }
                },
                animation: { duration: 800, easing: 'easeOutQuart' }
            }
        });
    }

    // ── Export CSV ───────────────────────────────────────────
    function exportCSV() {
        if (!currentData?.datasets) {
            alert('No data to export');
            return;
        }
        const ds1 = currentData.datasets[0];
        const ds2 = currentData.datasets[1];
        const l1 = metricLabels[ds1.label] || ds1.label;
        const l2 = metricLabels[ds2.label] || ds2.label;

        let csv = 'Date,' + l1 + ',' + l2 + '\n';
        for (let i = 0; i < ds1.data.length; i++) {
            csv += ds1.data[i].x + ',' + ds1.data[i].y + ',' + (ds2.data[i]?.y || 0) + '\n';
        }

        const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.href = url;
        link.download = 'stats_' + ds1.data[0]?.x + '_to_' + ds1.data[ds1.data.length-1]?.x + '.csv';
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    // Start
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();