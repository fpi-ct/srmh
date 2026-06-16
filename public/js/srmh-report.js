(function () {
    const table = document.getElementById('reportTable');
    if (!table) return;

    let highlightCol = -1;
    const copyBtn = document.getElementById('reportCopyBtn');
    const copyAllBtn = document.getElementById('reportCopyAllBtn');
    const copyHint = document.getElementById('reportCopyHint');

    function applyHighlight() {
        table.querySelectorAll('th, td').forEach((cell) => {
            cell.classList.remove('col-highlight', 'col-highlight-hd');
        });

        if (highlightCol === -1) return;

        table.querySelectorAll('thead th').forEach((th, i) => {
            if (i === highlightCol) th.classList.add('col-highlight-hd');
        });
        table.querySelectorAll(`tbody tr td:nth-child(${highlightCol + 1})`).forEach((td) => {
            td.classList.add('col-highlight');
        });
    }

    function updateCopyUi() {
        const hasCol = highlightCol !== -1;
        if (copyBtn) {
            copyBtn.classList.toggle('hidden', !hasCol);
            copyBtn.classList.toggle('inline-flex', hasCol);
        }
        if (copyHint) {
            copyHint.textContent = hasCol
                ? `Đang chọn cột: ${table.querySelectorAll('thead th')[highlightCol]?.textContent.trim().replace('▼', '')}`
                : 'Click tiêu đề cột để chọn → copy';
        }
    }

    table.querySelectorAll('.report-col-hd').forEach((th) => {
        th.addEventListener('click', () => {
            const col = parseInt(th.dataset.col, 10);
            highlightCol = highlightCol === col ? -1 : col;
            applyHighlight();
            updateCopyUi();
        });
    });

    function copyText(text) {
        navigator.clipboard.writeText(text).then(() => {
            if (copyHint) copyHint.textContent = '✓ Đã copy vào clipboard';
            setTimeout(updateCopyUi, 2000);
        });
    }

    if (copyBtn) {
        copyBtn.addEventListener('click', () => {
            if (highlightCol === -1) return;
            const cells = table.querySelectorAll(`tbody tr td:nth-child(${highlightCol + 1})`);
            const lines = Array.from(cells).map((c) => c.textContent.trim());
            copyText(lines.join('\n'));
        });
    }

    if (copyAllBtn) {
        copyAllBtn.addEventListener('click', () => {
            const rows = [];
            const headers = Array.from(table.querySelectorAll('thead th')).map((th) =>
                th.textContent.trim().replace('▼', '').trim()
            );
            rows.push(headers.join('\t'));
            table.querySelectorAll('tbody tr').forEach((tr) => {
                const cells = Array.from(tr.querySelectorAll('td')).map((td) => td.textContent.trim());
                rows.push(cells.join('\t'));
            });
            copyText(rows.join('\n'));
        });
    }
})();
