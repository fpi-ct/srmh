window.SrmhNotifications = (function () {
    function csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function statusIcon(status) {
        if (status === 'critical') return '🔴';
        if (status === 'monitoring') return '🟡';
        return '🟢';
    }

    function statusClass(status) {
        if (status === 'critical') return 'text-rose-500';
        if (status === 'monitoring') return 'text-amber-500';
        return 'text-emerald-500';
    }

    function timeAgo(iso) {
        if (!iso) return 'N/A';
        const d = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
        if (d < 60) return 'Vừa xong';
        if (d < 3600) return `${Math.floor(d / 60)} phút trước`;
        if (d < 86400) return `${Math.floor(d / 3600)} giờ trước`;
        return `${Math.floor(d / 86400)} ngày trước`;
    }

    async function fetchJson(url, options = {}) {
        const res = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.headers || {}),
            },
            ...options,
        });
        return res.json();
    }

    function ensurePermission() {
        if (!('Notification' in window)) return;
        if (Notification.permission === 'default') {
            Notification.requestPermission().catch(() => {});
        }
    }

    function notifyBrowser(detail) {
        if (!('Notification' in window) || Notification.permission !== 'granted') return;
        if (!detail || !detail.title) return;
        try {
            const n = new Notification(detail.title, {
                body: detail.body || '',
                tag: 'srmh-' + (detail.id || Date.now()),
                icon: '/icons/icon-192.png',
            });
            n.onclick = () => {
                window.focus();
                if (detail.student_id && window.SrmhModal) window.SrmhModal.open(detail.student_id);
                n.close();
            };
        } catch (e) {
            //
        }
    }

    window.srmhNotifications = function () {
        return {
            open: false,
            items: [],
            count: 0,

            init() {
                ensurePermission();
                this.refresh();
                document.addEventListener('srmh-notification', (e) => {
                    if (e.detail?.id) {
                        if (!this.items.some((n) => n.id === e.detail.id)) {
                            this.items.unshift(e.detail);
                            this.count += 1;
                            notifyBrowser(e.detail);
                        }
                    } else {
                        this.refresh();
                    }
                });
            },

            async refresh() {
                try {
                    const data = await fetchJson('/notifications');
                    this.items = data.items || [];
                    this.count = data.unread_count || 0;
                } catch (e) {
                    this.items = [];
                    this.count = 0;
                }
            },

            toggle() {
                this.open = !this.open;
                if (this.open) this.refresh();
            },

            async markAllRead() {
                await fetchJson('/notifications/read-all', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf() },
                });
                this.items = [];
                this.count = 0;
                this.open = false;
            },

            async goTo(item) {
                this.open = false;
                if (item.id) {
                    await fetchJson(`/notifications/${item.id}/read`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf() },
                    });
                    this.items = this.items.filter((n) => n.id !== item.id);
                    this.count = this.items.length;
                }
                if (item.student_id && window.SrmhModal) {
                    window.SrmhModal.open(item.student_id);
                }
            },

            statusIcon,
            statusClass,
            timeAgo,
        };
    };

    return { refresh: () => document.dispatchEvent(new CustomEvent('srmh-notification')) };
})();
