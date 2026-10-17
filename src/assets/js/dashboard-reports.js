/** TARAZINO | Period totals + historical cashflow (Gregorian API, Persian UI).
 * Runs independently of dashboard transactions/balance modules.
 */
(function (w, d) {
    'use strict';

    const el = id => d.getElementById(id);
    const DATE_PARTS = new Intl.DateTimeFormat('en-US-u-ca-persian', {
        year: 'numeric', month: 'numeric', day: 'numeric', timeZone: 'UTC'
    });
    const JALALI_DISPLAY = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
        year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'UTC'
    });
    const money = new Intl.NumberFormat('fa-IR', { maximumFractionDigits: 2 });
    let chart = null;
    let initialized = false;
    let requestSequence = 0;
    let apiUrl = '';
    let lastApplied = null;
    let lastReport = null;

    function iso(date) { return date.toISOString().slice(0, 10); }

    // Noon UTC prevents date rollover while computing the user's local calendar day.
    function today() {
        const now = new Date();
        return new Date(Date.UTC(now.getFullYear(), now.getMonth(), now.getDate(), 12));
    }

    function isIsoDate(value) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(value || '')) return false;
        const date = new Date(value + 'T12:00:00Z');
        return Number.isFinite(date.getTime()) && iso(date) === value;
    }

    function persianParts(date) {
        const result = {};
        DATE_PARTS.formatToParts(date).forEach(part => {
            if (part.type === 'year' || part.type === 'month') result[part.type] = Number(part.value);
        });
        if (!Number.isInteger(result.year) || !Number.isInteger(result.month)) {
            throw new Error('محاسبه تقویم شمسی در این مرورگر ممکن نیست.');
        }
        return result;
    }

    function periodRange(mode) {
        const end = today();
        if (mode === 'custom') {
            const from = el('dashboardDateFrom')?.value || '';
            const to = el('dashboardDateTo')?.value || '';
            return isIsoDate(from) && isIsoDate(to) && from <= to ? { from, to } : null;
        }
        const start = new Date(end.getTime());
        if (mode === 'week') {
            start.setUTCDate(start.getUTCDate() - ((start.getUTCDay() + 1) % 7));
        } else if (mode === 'month' || mode === 'year') {
            const current = persianParts(end);
            for (let i = 0; i < 370; i++) {
                const previous = new Date(start.getTime());
                previous.setUTCDate(previous.getUTCDate() - 1);
                const value = persianParts(previous);
                if (value.year !== current.year ||
                    (mode === 'month' && value.month !== current.month)) break;
                start.setTime(previous.getTime());
            }
        } else {
            return null;
        }
        return { from: iso(start), to: iso(end) };
    }

    function mode() {
        return d.querySelector('input[name="dashboardPeriod"]:checked')?.value || 'year';
    }

    function jalali(isoValue) {
        if (!isIsoDate(isoValue)) return isoValue;
        const date = new Date(isoValue + 'T12:00:00Z');
        const parts = {};
        JALALI_DISPLAY.formatToParts(date).forEach(part => {
            if (['year', 'month', 'day'].includes(part.type)) parts[part.type] = part.value;
        });
        return parts.year + '/' + parts.month + '/' + parts.day;
    }

    function rangeCaption(range) {
        return 'از ' + jalali(range.from) + ' تا ' + jalali(range.to);
    }

    function filterMessage(message, isError) {
        const target = el('dashboardDateFilterMessage');
        if (!target) return;
        target.textContent = message;
        target.classList.toggle('text-danger', !!isError);
        target.classList.toggle('text-success', !isError && message.startsWith('اعمال شد:'));
    }

    function setLoading(loading) {
        const button = el('applyDashboardDateFilter');
        if (!button) return;
        // Always actionable when idle, including preset modes and custom range.
        button.disabled = !!loading;
        button.setAttribute('aria-busy', loading ? 'true' : 'false');
    }

    function updateFilters() {
        const selected = mode();
        const custom = selected === 'custom';
        el('dashboardCustomRange')?.classList.toggle('d-none', !custom);
        if (custom) {
            const range = periodRange('custom');
            filterMessage(range
                ? 'بازه دلخواه آماده است: ' + rangeCaption(range) + '؛ برای دریافت گزارش، «اعمال بازه» را بزنید.'
                : 'برای بازه دلخواه، تاریخ شروع و پایان معتبر را از تقویم شمسی انتخاب کنید.', !range);
        } else {
            const range = periodRange(selected);
            filterMessage(range ? 'بازه انتخاب‌شده: ' + rangeCaption(range) : 'بازه زمانی معتبر نیست.', !range);
        }
    }

    function chartStatus(text) {
        const canvas = el('cashflowChart');
        const parent = canvas?.parentElement;
        if (!parent) return;
        let status = parent.querySelector('[data-tarazino-report-status]');
        if (!status) {
            status = d.createElement('div');
            status.setAttribute('data-tarazino-report-status', '');
            status.setAttribute('role', 'status');
            status.setAttribute('aria-live', 'polite');
            status.className = 'text-body-secondary small';
            parent.append(status);
        }
        status.textContent = text || '';
        status.hidden = !text;
    }

    function destroyChart() {
        if (chart) { chart.destroy(); chart = null; }
    }

    function setTotals(totals) {
        const income = el('incomeAmount');
        const expense = el('expenseAmount');
        if (!income || !expense) return;
        income.textContent = totals ? money.format(Number(totals.income)) : '—';
        expense.textContent = totals ? money.format(Number(totals.expense)) : '—';
    }

    function validReport(report) {
        if (!report || !Array.isArray(report.labels) || !Array.isArray(report.income) ||
            !Array.isArray(report.expense) || !Array.isArray(report.balance) ||
            !report.totals || !Number.isFinite(Number(report.totals.income)) ||
            !Number.isFinite(Number(report.totals.expense))) return false;
        const length = report.labels.length;
        return report.income.length === length && report.expense.length === length &&
            report.balance.length === length && report.labels.every(isIsoDate) &&
            [report.income, report.expense, report.balance].every(series =>
                series.every(value => value !== null && value !== '' && Number.isFinite(Number(value))));
    }

    function createChart(report) {
        const canvas = el('cashflowChart');
        if (!canvas || !canvas.getContext('2d') || typeof w.Chart !== 'function') return false;
        destroyChart();
        chart = new w.Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: report.labels.map(jalali),
                datasets: [
                    { label: 'درآمد', data: report.income.map(Number), borderColor: '#16a34a',
                        backgroundColor: 'rgba(22,163,74,.10)', tension: .25, borderWidth: 2,
                        pointRadius: 3, pointHoverRadius: 5, yAxisID: 'amount' },
                    { label: 'هزینه', data: report.expense.map(Number), borderColor: '#dc2626',
                        backgroundColor: 'rgba(220,38,38,.10)', tension: .25, borderWidth: 2,
                        pointRadius: 3, pointHoverRadius: 5, yAxisID: 'amount' },
                    { label: 'موجودی', data: report.balance.map(Number), borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59,130,246,.10)', tension: .25, borderWidth: 2,
                        pointRadius: 3, pointHoverRadius: 5, yAxisID: 'balance' }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { callbacks: {
                        label: ctx => ctx.dataset.label + ': ' + money.format(ctx.parsed.y) + ' تومان'
                    }}
                },
                scales: {
                    x: { title: { display: true, text: 'تاریخ شمسی' } },
                    amount: { type: 'linear', position: 'left', beginAtZero: true,
                        title: { display: true, text: 'درآمد و هزینه (تومان)' },
                        ticks: { callback: value => money.format(value) } },
                    balance: { type: 'linear', position: 'right', beginAtZero: false,
                        title: { display: true, text: 'موجودی (تومان)' },
                        grid: { drawOnChartArea: false },
                        ticks: { callback: value => money.format(value) } }
                }
            }
        });
        return true;
    }

    async function render() {
        const sequence = ++requestSequence;
        const selected = mode();
        const range = periodRange(selected);
        if (!range) {
            filterMessage('بازه انتخابی نامعتبر است؛ ابتدا تاریخ‌ها را تصحیح کنید.', true);
            // This render supersedes any in-flight request. Its finally block
            // will no longer clear the loading state because its sequence is stale.
            setLoading(false);
            return false;
        }
        if (!apiUrl || !w.TarazinoApi?.get) {
            filterMessage('ارتباط با API گزارش آماده نیست. آدرس یا فایل جاوااسکریپت را بررسی کنید.', true);
            setTotals(null);
            destroyChart();
            chartStatus('بارگذاری گزارش مالی ممکن نیست.');
            return false;
        }

        setLoading(true);
        chartStatus('در حال دریافت گزارش...');
        filterMessage('در حال بارگذاری: ' + rangeCaption(range), false);
        try {
            const separator = apiUrl.includes('?') ? '&' : '?';
            const url = apiUrl + separator + 'from=' + encodeURIComponent(range.from) +
                '&to=' + encodeURIComponent(range.to);
            const report = await w.TarazinoApi.get(url);
            if (sequence !== requestSequence) return false;
            if (!validReport(report)) throw new Error('ساختار داده‌های گزارش معتبر نیست.');
            lastApplied = { mode: selected, ...range };
            lastReport = report;
            setTotals(report.totals);
            filterMessage('اعمال شد: ' + rangeCaption(range), false);
            if (!report.labels.length) {
                destroyChart();
                chartStatus('در این بازه تراکنش یا تغییر موجودی ثبت نشده است؛ مجموع درآمد و هزینه صفر است.');
                return true;
            }
            if (typeof w.Chart !== 'function') {
                destroyChart();
                chartStatus('مجموع‌ها دریافت شدند، اما برای نمودار باید Chart.js بارگذاری شود.');
                return true;
            }
            createChart(report);
            chartStatus('');
            return true;
        } catch (error) {
            if (sequence !== requestSequence) return false;
            destroyChart();
            setTotals(null);
            lastReport = null;
            chartStatus('خطا در دریافت گزارش: ' + (error.message || 'خطای نامشخص'));
            filterMessage('گزارش دریافت نشد. ' + (error.message || 'خطا در ارتباط با سرور'), true);
            console.error('[TarazinoReports]', error);
            return false;
        } finally {
            if (sequence === requestSequence) setLoading(false);
        }
    }

    function initialize() {
        if (initialized) return true;
        const root = d.querySelector('.dashboard-shell[data-cashflow-url]');
        if (!root || !el('cashflowChart')) return false;
        initialized = true;
        apiUrl = (root.dataset.cashflowUrl || '').trim();
        // Initialize the custom range to the currently selected period, so Apply is usable.
        const preset = periodRange(mode()) || periodRange('year');
        if (el('dashboardDateFrom') && !el('dashboardDateFrom').value) el('dashboardDateFrom').value = preset.from;
        if (el('dashboardDateTo') && !el('dashboardDateTo').value) el('dashboardDateTo').value = preset.to;
        if (w.TarazinoDatepicker?.refresh) w.TarazinoDatepicker.refresh(el('dashboardDateFilter'));

        d.querySelectorAll('input[name="dashboardPeriod"]').forEach(radio => {
            radio.addEventListener('change', () => {
                if (!radio.checked) return;
                updateFilters();
                // Preset buttons are immediate; custom range needs the Apply action.
                if (radio.value !== 'custom') render();
            });
        });
        ['dashboardDateFrom', 'dashboardDateTo'].forEach(id => {
            const input = el(id);
            if (!input) return;
            input.addEventListener('input', updateFilters);
            input.addEventListener('change', updateFilters);
        });
        el('applyDashboardDateFilter')?.addEventListener('click', () => render());
        updateFilters();
        render();
        return true;
    }

    // Report controls and initial report must work even if the transactions module fails.
    if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', initialize, { once: true });
    else initialize();

    w.TarazinoReports = Object.freeze({ initialize, render });
})(window, document);
