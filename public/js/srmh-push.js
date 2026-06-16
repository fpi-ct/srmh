window.SrmhPush = (function () {
    const VISIT_KEY = 'srmh_visit_count';

    function csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const raw = atob(base64);
        const arr = new Uint8Array(raw.length);
        for (let i = 0; i < raw.length; ++i) arr[i] = raw.charCodeAt(i);
        return arr;
    }

    async function subscribe(registration) {
        const vapid = window.__SRMH_VAPID__;
        if (!vapid) return;

        const sub = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapid),
        });

        const json = sub.toJSON();
        await fetch('/push/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                endpoint: json.endpoint,
                keys: json.keys,
            }),
        });
    }

    async function init() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;

        const visits = Number(localStorage.getItem(VISIT_KEY) || '0') + 1;
        localStorage.setItem(VISIT_KEY, String(visits));

        try {
            const registration = await navigator.serviceWorker.register('/sw.js');
            if (visits < 2) return;
            if (Notification.permission === 'granted') {
                await subscribe(registration);
                return;
            }
            if (Notification.permission !== 'denied') {
                const permission = await Notification.requestPermission();
                if (permission === 'granted') {
                    await subscribe(registration);
                }
            }
        } catch (e) {
            //
        }
    }

    return { init };
})();
