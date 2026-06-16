window.SrmhEcho = (function () {
    let echo = null;
    let studentChannel = null;
    let connected = false;

    function config() {
        return window.__SRMH_REVERB__ || {};
    }

    function csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function echoClass() {
        const E = window.Echo;
        if (!E) return null;
        if (typeof E === 'function') return E;
        if (typeof E.default === 'function') return E.default;
        return null;
    }

    function initEcho() {
        if (echo) return echo;

        const EchoClass = echoClass();
        if (!EchoClass || !window.Pusher) return null;

        const cfg = config();
        if (!cfg.key) return null;

        echo = new EchoClass({
            broadcaster: 'reverb',
            key: cfg.key,
            wsHost: cfg.wsHost,
            wsPort: cfg.wsPort ?? 8080,
            wssPort: cfg.wssPort ?? 443,
            forceTLS: !!cfg.forceTLS,
            enabledTransports: ['ws', 'wss'],
            disableStats: true,
            cluster: '',
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
            },
        });

        const pusher = echo.connector?.pusher;
        if (pusher) {
            pusher.connection.bind('connected', () => { connected = true; });
            pusher.connection.bind('disconnected', () => { connected = false; });
            pusher.connection.bind('failed', () => { connected = false; });
            pusher.connection.bind('unavailable', () => { connected = false; });
        }

        return echo;
    }

    function waitForConnection(client, ms = 15000) {
        return new Promise((resolve, reject) => {
            const pusher = client?.connector?.pusher;
            if (!pusher) {
                reject(new Error('no pusher'));
                return;
            }
            if (pusher.connection.state === 'connected') {
                resolve();
                return;
            }
            const onConnected = () => {
                cleanup();
                resolve();
            };
            const onFailed = () => {
                cleanup();
                reject(new Error('ws failed'));
            };
            const timer = setTimeout(() => {
                cleanup();
                reject(new Error('ws timeout'));
            }, ms);
            const cleanup = () => {
                clearTimeout(timer);
                pusher.connection.unbind('connected', onConnected);
                pusher.connection.unbind('failed', onFailed);
                pusher.connection.unbind('unavailable', onFailed);
            };
            pusher.connection.bind('connected', onConnected);
            pusher.connection.bind('failed', onFailed);
            pusher.connection.bind('unavailable', onFailed);
        });
    }

    function timelineRoot() {
        return document.querySelector('#feedback-timeline');
    }

    async function appendFeedback(studentId, feedbackId, parentId) {
        const timeline = timelineRoot();
        if (!timeline || !feedbackId) return;

        if (timeline.querySelector(`[data-feedback-id="${feedbackId}"]`)) return;

        try {
            const res = await fetch(`/students/${studentId}/feedbacks/${feedbackId}/item`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) return;

            const html = await res.text();
            const wrap = document.createElement('div');
            wrap.innerHTML = html.trim();
            const node = wrap.firstElementChild;
            if (!node) return;

            node.dataset.feedbackId = feedbackId;

            const empty = timeline.querySelector('p.text-center');
            if (empty) empty.remove();

            if (parentId) {
                const parentEl = timeline.querySelector(`[data-feedback-id="${parentId}"]`);
                if (!parentEl) return;

                let replyBox = parentEl.nextElementSibling;
                if (!replyBox || !replyBox.classList.contains('ml-7')) {
                    replyBox = document.createElement('div');
                    replyBox.className = 'ml-7 pl-3 border-l-2 border-slate-200 space-y-2';
                    parentEl.insertAdjacentElement('afterend', replyBox);
                }
                replyBox.appendChild(node);
            } else {
                timeline.appendChild(node);
            }

            if (window.Alpine) Alpine.initTree(node);
            if (window.SrmhModal?.bindPanel) window.SrmhModal.bindPanel(node);

            const chat = document.getElementById('student-modal-chat');
            if (chat) chat.scrollTop = chat.scrollHeight;
        } catch (e) {
            //
        }
    }

    async function replaceFeedback(studentId, feedbackId) {
        const timeline = timelineRoot();
        if (!timeline || !feedbackId) return;

        const existing = timeline.querySelector(`[data-feedback-id="${feedbackId}"]`);
        if (!existing) return;

        try {
            const res = await fetch(`/students/${studentId}/feedbacks/${feedbackId}/item`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) return;

            const html = await res.text();
            const wrap = document.createElement('div');
            wrap.innerHTML = html.trim();
            const node = wrap.firstElementChild;
            if (!node) return;

            node.dataset.feedbackId = feedbackId;
            existing.replaceWith(node);

            if (window.Alpine) Alpine.initTree(node);
            if (window.SrmhModal?.bindPanel) window.SrmhModal.bindPanel(node);
        } catch (e) {
            //
        }
    }

    function applyReaction(feedbackId, count) {
        const timeline = timelineRoot();
        if (!timeline) return;
        const item = timeline.querySelector(`[data-feedback-id="${feedbackId}"]`);
        if (!item) return;
        const countEl = item.querySelector('.js-react-count');
        if (!countEl) return;
        if (count > 0) {
            countEl.textContent = count;
            countEl.classList.remove('hidden');
        } else {
            countEl.textContent = '';
            countEl.classList.add('hidden');
        }
    }

    function applyAgree(feedbackId, names) {
        const timeline = timelineRoot();
        if (!timeline) return;
        const item = timeline.querySelector(`[data-feedback-id="${feedbackId}"]`);
        if (!item) return;
        const namesEl = item.querySelector('.js-agree-names');
        if (!namesEl) return;
        if (Array.isArray(names) && names.length > 0) {
            namesEl.textContent = `(+ ${names.join(', ')})`;
            namesEl.classList.remove('hidden');
        } else {
            namesEl.textContent = '';
            namesEl.classList.add('hidden');
        }
    }

    function bindFeedbackListener(client, studentId) {
        const channelName = `student.${studentId}`;
        studentChannel = channelName;

        const myCode = window.__SRMH_USER__;
        const isCurrentStudent = (sid) => {
            const modalOpen = document.getElementById('studentModal')?.classList.contains('active');
            return modalOpen && Number(window.SrmhModal?.currentStudentId?.()) === Number(sid);
        };

        client.private(channelName)
            .listen('.FeedbackCreated', (e) => {
                const feedback = e.feedback;
                if (!feedback) return;

                const incomingStudentId = Number(feedback.student_id);
                if (isCurrentStudent(incomingStudentId) && feedback.author_access_code !== myCode) {
                    appendFeedback(incomingStudentId, feedback.id, feedback.parent_id);
                }

                document.dispatchEvent(new CustomEvent('srmh-notification'));
                document.dispatchEvent(new CustomEvent('student-modal-updated'));
            })
            .listen('.FeedbackReacted', (e) => {
                if (e.actor === myCode) return;
                applyReaction(e.feedback_id, Number(e.count));
            })
            .listen('.FeedbackAgreed', (e) => {
                if (e.actor === myCode) return;
                applyAgree(e.feedback_id, e.agree_names);
            })
            .listen('.FeedbackItemRefreshed', (e) => {
                if (e.actor === myCode) return;
                if (isCurrentStudent(e.student_id)) {
                    replaceFeedback(Number(e.student_id), e.feedback_id);
                }
            });
    }

    async function subscribeUser(accessCode) {
        const client = initEcho();
        if (!client || !accessCode) return;

        try {
            await waitForConnection(client);
            client.private(`user.${accessCode}`)
                .listen('.NotificationSent', (e) => {
                    document.dispatchEvent(new CustomEvent('srmh-notification', { detail: e.notification }));
                });
        } catch (e) {
            //
        }
    }

    async function subscribeStudent(studentId) {
        const client = initEcho();
        if (!client) return;

        if (studentChannel) {
            client.leave(studentChannel);
            studentChannel = null;
        }

        if (!studentId) return;

        try {
            await waitForConnection(client);
            bindFeedbackListener(client, studentId);
        } catch (e) {
            //
        }
    }

    function init() {
        if (!config().key || !echoClass() || !window.Pusher) return;

        initEcho();
        subscribeUser(window.__SRMH_USER__);

        document.addEventListener('student-modal-opened', (e) => {
            subscribeStudent(Number(e.detail?.studentId));
        });

        document.addEventListener('student-modal-closed', () => {
            subscribeStudent(null);
        });
    }

    function status() {
        const pusher = echo?.connector?.pusher;
        return {
            connected,
            state: pusher?.connection?.state ?? 'not_init',
            socketId: pusher?.connection?.socket_id ?? null,
        };
    }

    return { init, subscribeStudent, isConnected: () => connected, status };
})();
