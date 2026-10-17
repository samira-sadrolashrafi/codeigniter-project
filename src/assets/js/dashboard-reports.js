/** TARAZINO | Dashboard cashflow report */
(function (w, d) {
    'use strict';

    const API_URL = '/api/dashboard/cashflow';
    const CANVAS_ID = 'cashflowChart';
    const JALALI_DATE_FORMATTER = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        timeZone: 'UTC'
    });

    let chart = null;
    let loading = false;

    function getCanvas() {
        return d.getElementById(CANVAS_ID);
    }

    function getStatusElement(canvas) {
        const parent = canvas.parentElement;

        if (!parent) {
            return null;
        }

        let status = parent.querySelector(
            '[data-tarazino-report-status]'
        );

        if (!status) {
            status = d.createElement('p');
            status.setAttribute(
                'data-tarazino-report-status',
                ''
            );
            status.setAttribute('role', 'status');
            status.setAttribute('aria-live', 'polite');
            status.className = 'text-muted small';
            status.hidden = true;

            canvas.insertAdjacentElement('afterend', status);
        }

        return status;
    }

    function showStatus(message, canvas) {
        canvas = canvas || getCanvas();

        if (!canvas) {
            return;
        }

        const status = getStatusElement(canvas);

        if (!status) {
            return;
        }

        status.textContent = message || '';
        status.hidden = !message;
    }

    function destroyChart() {
        if (chart) {
            chart.destroy();
            chart = null;
        }
    }

    function initialize() {
        const canvas = getCanvas();

        if (!canvas) {
            return false;
        }

        getStatusElement(canvas);

        return true;
    }

    function unavailable(message, canvas) {
        destroyChart();

        showStatus(
            message || 'اطلاعاتی برای نمایش نمودار در دسترس نیست.',
            canvas
        );
    }

    function formatAmount(value) {
        return new Intl.NumberFormat('fa-IR', {
            maximumFractionDigits: 2
        }).format(value);
    }

    function formatJalaliDate(value) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return value;
        const date = new Date(value + 'T00:00:00Z');
        if (Number.isNaN(date.getTime())) return value;
        const parts = {};
        JALALI_DATE_FORMATTER.formatToParts(date).forEach(part => {
            if (part.type === 'year' || part.type === 'month' || part.type === 'day') {
                parts[part.type] = part.value;
            }
        });
        return parts.year && parts.month && parts.day
            ? parts.year + '/' + parts.month + '/' + parts.day
            : value;
    }

    function validateSeries(report) {
        if (
            !report ||
            !Array.isArray(report.labels) ||
            !Array.isArray(report.income) ||
            !Array.isArray(report.expense) ||
            !Array.isArray(report.balance)
        ) {
            return false;
        }

        const length = report.labels.length;

        if (
            report.income.length !== length ||
            report.expense.length !== length ||
            report.balance.length !== length
        ) {
            return false;
        }

        if (!report.labels.every(function (date) {
            return typeof date === 'string';
        })) {
            return false;
        }

        const series = [
            report.income,
            report.expense,
            report.balance
        ];

        return series.every(function (values) {
            return values.every(function (value) {
                return (
                    value !== null &&
                    value !== '' &&
                    Number.isFinite(Number(value))
                );
            });
        });
    }

    function createChart(canvas, report) {
        const context = canvas.getContext('2d');

        if (!context) {
            unavailable(
                'امکان نمایش نمودار وجود ندارد.',
                canvas
            );

            return false;
        }

        destroyChart();

        chart = new w.Chart(context, {
            type: 'line',

            data: {
                labels: report.labels.map(formatJalaliDate),

                datasets: [
                    {
                        type: 'line',
                        label: 'درآمد',
                        data: report.income.map(Number),
                        borderColor: 'rgb(22, 163, 74)',
                        backgroundColor: 'rgba(22, 163, 74, 0.10)',
                        borderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointHitRadius: 8,
                        tension: 0.25,
                        fill: false,
                        yAxisID: 'y'
                    },
                    {
                        type: 'line',
                        label: 'هزینه',
                        data: report.expense.map(Number),
                        borderColor: 'rgb(220, 38, 38)',
                        backgroundColor: 'rgba(220, 38, 38, 0.10)',
                        borderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointHitRadius: 8,
                        tension: 0.25,
                        fill: false,
                        yAxisID: 'y'
                    },
                    {
                        type: 'line',
                        label: 'موجودی',
                        data: report.balance.map(Number),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.10)',
                        borderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointHitRadius: 8,
                        tension: 0.25,
                        fill: false,
                        yAxisID: 'yBalance'
                    }
                ]
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,

                interaction: {
                    mode: 'index',
                    intersect: false
                },

                plugins: {
                    legend: {
                        position: 'top'
                    },

                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const label =
                                    context.dataset.label || '';

                                const value = context.parsed.y;

                                return label + ': ' +
                                    formatAmount(value) + ' تومان';
                            }
                        }
                    }
                },

                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'تاریخ'
                        }
                    },

                    y: {
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true,

                        title: {
                            display: true,
                            text: 'درآمد و هزینه (تومان)'
                        },

                        ticks: {
                            callback: function (value) {
                                return formatAmount(value);
                            }
                        }
                    },

                    yBalance: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: false,

                        title: {
                            display: true,
                            text: 'موجودی (تومان)'
                        },

                        grid: {
                            drawOnChartArea: false
                        },

                        ticks: {
                            callback: function (value) {
                                return formatAmount(value);
                            }
                        }
                    }
                }
            }
        });

        return true;
    }

    async function render() {
        if (loading) {
            return false;
        }

        const canvas = getCanvas();

        if (!canvas) {
            return unavailable(
                'عنصر نمودار در صفحه پیدا نشد.'
            );
        }

        if (typeof w.Chart !== 'function') {
            return unavailable(
                'کتابخانه Chart.js بارگذاری نشده است.',
                canvas
            );
        }

        loading = true;

        showStatus(
            'در حال دریافت اطلاعات نمودار...',
            canvas
        );

        try {
            const response = await w.fetch(API_URL, {
                method: 'GET',

                headers: {
                    'Accept': 'application/json'
                },

                credentials: 'same-origin'
            });

            const result = await response.json();

            if (
                !response.ok ||
                !result ||
                result.success !== true
            ) {
                throw new Error(
                    result && result.message
                        ? result.message
                        : 'دریافت اطلاعات نمودار ناموفق بود.'
                );
            }

            if (!validateSeries(result)) {
                throw new Error(
                    'ساختار داده‌های نمودار معتبر نیست.'
                );
            }

            if (result.labels.length === 0) {
                unavailable(
                    'هنوز اطلاعاتی برای نمایش نمودار ثبت نشده است.',
                    canvas
                );

                return false;
            }

            const created = createChart(canvas, result);

            if (created) {
                showStatus('', canvas);
            }

            return created;

        } catch (error) {
            console.error(
                '[TarazinoReports] Failed to load cashflow report:',
                error
            );

            unavailable(
                'دریافت اطلاعات نمودار با خطا مواجه شد.',
                canvas
            );

            return false;

        } finally {
            loading = false;
        }
    }

    w.TarazinoReports = Object.freeze({
        initialize: initialize,
        render: render,
        unavailable: unavailable
    });

})(window, document);
