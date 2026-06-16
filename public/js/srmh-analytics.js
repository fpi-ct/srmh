(function () {
    const data = window.__SRMH_ANALYTICS__;
    if (!data || typeof Chart === 'undefined') return;

    function goToDashboard(params) {
        const qs = new URLSearchParams(params).toString();
        window.location.href = '/dashboard?' + qs;
    }

    const centerTextPlugin = {
        id: 'centerText',
        beforeDraw(chart) {
            const { ctx, width, height } = chart;
            const meta = chart.getDatasetMeta(0);
            if (!meta || meta.data.length === 0) return;
            const txt = chart.config.options.plugins.centerText?.text || '';
            const sub = chart.config.options.plugins.centerText?.sub || '';
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.font = 'bold 28px Inter, sans-serif';
            ctx.fillStyle = '#334155';
            ctx.fillText(txt, width / 2, height / 2 - 8);
            if (sub) {
                ctx.font = '500 11px Inter, sans-serif';
                ctx.fillStyle = '#94a3b8';
                ctx.fillText(sub, width / 2, height / 2 + 16);
            }
            ctx.restore();
        },
    };

    const legendRect = {
        position: 'bottom',
        labels: { font: { family: 'Inter', size: 12 }, padding: 16, usePointStyle: true, pointStyle: 'rect', pointStyleWidth: 10 },
    };

    const legendNoBox = {
        position: 'bottom',
        labels: { font: { family: 'Inter', size: 12 }, padding: 16, boxWidth: 0, boxHeight: 0, usePointStyle: false },
    };

    new Chart(document.getElementById('coverageDonutChart'), {
        type: 'doughnut',
        plugins: [centerTextPlugin],
        data: {
            labels: ['Đã có Feedback', 'Chưa có Feedback'],
            datasets: [{
                data: [data.coverage.with, data.coverage.without],
                backgroundColor: ['#6366f1', '#e2e8f0'],
                borderWidth: 0,
                hoverOffset: 6,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                centerText: { text: data.coverage.pct + '%', sub: 'bao phủ' },
                legend: legendRect,
                tooltip: { callbacks: { label: (ctx) => ` ${ctx.label}: ${ctx.raw} SV` } },
            },
        },
    });

    const statusToValue = ['green', 'yellow', 'red'];
    new Chart(document.getElementById('donutChart'), {
        type: 'doughnut',
        plugins: [centerTextPlugin],
        data: {
            labels: ['🟢 Ổn định', '🟡 Theo dõi', '🔴 Cảnh báo'],
            datasets: [{
                data: [data.status.stable, data.status.monitoring, data.status.critical],
                backgroundColor: ['#10b981', '#f59e0b', '#f43f5e'],
                borderWidth: 0,
                hoverOffset: 6,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            onClick: (evt, elements) => {
                if (!elements.length) return;
                goToDashboard({ status: statusToValue[elements[0].index] });
            },
            plugins: {
                centerText: { text: data.status.pct + '%', sub: 'cần lưu ý' },
                legend: legendNoBox,
                tooltip: { callbacks: { label: (ctx) => ` ${ctx.label}: ${ctx.raw} SV` } },
            },
        },
    });

    const bucketColors = { '0-10%': '#cbd5e1', '10-17%': '#fcd34d', '17-20%': '#fb923c', '≥20%': '#f43f5e' };

    const absence = data.absence_histogram;
    const absenceChart = new Chart(document.getElementById('absenceHistogramChart'), {
        type: 'bar',
        data: {
            labels: absence.labels,
            datasets: [{
                label: 'Số SV',
                data: absence.data,
                backgroundColor: absence.labels.map((l) => bucketColors[l] || '#cbd5e1'),
                borderRadius: 4,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => ` ${ctx.raw} SV` } } },
            scales: {
                x: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 11, weight: '600' } } },
                y: { beginAtZero: true, ticks: { stepSize: 1, font: { family: 'Inter', size: 11 } }, grid: { color: '#f1f5f9' } },
            },
        },
    });

    const absenceFaculty = data.absence_by_faculty;
    const absenceFacultyChart = new Chart(document.getElementById('absenceFacultyChart'), {
        type: 'bar',
        data: {
            labels: absenceFaculty.labels,
            datasets: absenceFaculty.buckets.map((bucket) => ({
                label: bucket,
                data: absenceFaculty.series[bucket],
                backgroundColor: bucketColors[bucket],
                borderRadius: 2,
            })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { font: { family: 'Inter', size: 11 }, padding: 16, usePointStyle: true, pointStyle: 'rect', pointStyleWidth: 10 } },
                tooltip: { callbacks: { label: (ctx) => ` ${ctx.dataset.label}: ${ctx.raw} SV` } },
            },
            scales: {
                x: { stacked: true, grid: { display: false }, ticks: { font: { family: 'Inter', size: 11, weight: '600' } } },
                y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1, font: { family: 'Inter', size: 11 } }, grid: { color: '#f1f5f9' } },
            },
        },
    });

    document.querySelectorAll('[data-absence-tab]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const tab = btn.dataset.absenceTab;

            document.querySelectorAll('[data-absence-tab]').forEach((b) => {
                const active = b.dataset.absenceTab === tab;
                b.classList.toggle('bg-white', active);
                b.classList.toggle('text-indigo-600', active);
                b.classList.toggle('shadow-sm', active);
                b.classList.toggle('text-slate-500', !active);
            });

            document.querySelectorAll('[data-absence-panel]').forEach((panel) => {
                panel.classList.toggle('hidden', panel.dataset.absencePanel !== tab);
            });

            (tab === 'faculty' ? absenceFacultyChart : absenceChart).resize();
        });
    });

    const trend = data.trend;
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: trend.labels,
            datasets: [
                { label: 'Feedback', data: trend.feedbacks, borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,.1)', fill: true, tension: 0.35, pointRadius: 3 },
                { label: 'Yêu cầu hỗ trợ', data: trend.escalations, borderColor: '#f43f5e', backgroundColor: 'rgba(244,63,94,.08)', fill: true, tension: 0.35, pointRadius: 3 },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: legendRect },
            scales: {
                x: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 11 } } },
                y: { beginAtZero: true, ticks: { stepSize: 1, font: { family: 'Inter', size: 11 } }, grid: { color: '#f1f5f9' } },
            },
        },
    });

    const faculty = data.by_faculty;
    new Chart(document.getElementById('facultyStackedBarChart'), {
        type: 'bar',
        data: {
            labels: faculty.labels,
            datasets: [
                { label: 'Ổn định', data: faculty.stable, backgroundColor: '#10b981', borderRadius: 2 },
                { label: 'Theo dõi', data: faculty.monitoring, backgroundColor: '#f59e0b', borderRadius: 2 },
                { label: 'Cảnh báo', data: faculty.critical, backgroundColor: '#f43f5e', borderRadius: 2 },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            onClick: (evt, elements) => {
                if (!elements.length) return;
                goToDashboard({ status: 'all', faculty: faculty.labels[elements[0].index] });
            },
            plugins: {
                legend: { labels: { font: { family: 'Inter', size: 11 }, padding: 16, usePointStyle: true, pointStyle: 'rect', pointStyleWidth: 10 } },
            },
            scales: {
                x: { stacked: true, grid: { display: false }, ticks: { font: { family: 'Inter', size: 11, weight: '600' } } },
                y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1, font: { family: 'Inter', size: 11 } }, grid: { color: '#f1f5f9' } },
            },
        },
    });
})();
