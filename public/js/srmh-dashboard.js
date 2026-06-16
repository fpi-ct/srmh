window.SrmhDashboard = (function () {
    let loading = false;

    function statsEl() {
        return document.getElementById('dashboard-stats');
    }

    function studentsEl() {
        return document.getElementById('dashboard-students');
    }

    function paginationEl() {
        return document.getElementById('dashboard-pagination');
    }

    async function reload() {
        if (loading || !statsEl()) return;

        loading = true;

        try {
            const qs = window.location.search.replace(/^\?/, '');
            const url = `/dashboard/data${qs ? `?${qs}` : ''}`;
            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (!res.ok) return;

            const json = await res.json();

            if (json.stats != null && statsEl()) {
                statsEl().innerHTML = json.stats;
            }
            if (json.students != null && studentsEl()) {
                studentsEl().innerHTML = json.students;
            }
            if (paginationEl()) {
                paginationEl().innerHTML = json.pagination ?? '';
            }
        } catch (e) {
        } finally {
            loading = false;
        }
    }

    document.addEventListener('student-care-status-updated', reload);

    return { reload };
})();
