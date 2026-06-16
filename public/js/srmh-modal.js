window.SrmhModal = (function () {
    let currentStudentId = null;
    let loading = false;

    const overlay = () => document.getElementById('studentModal');
    const body = () => document.getElementById('studentModalBody');

    function csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function modalHeaders() {
        return {
            'X-SRMH-Modal': '1',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        };
    }

    function showToast(message) {
        if (!message) return;
        const el = document.getElementById('srmh-toast');
        if (!el) return;
        el.textContent = message;
        el.classList.remove('hidden');
        clearTimeout(showToast._t);
        showToast._t = setTimeout(() => el.classList.add('hidden'), 2800);
    }

    function firstError(json) {
        if (!json?.errors) return json?.message || null;
        const values = Object.values(json.errors);
        return values.length ? values.flat()[0] : null;
    }

    function appendOptimisticBubble(content, parentId) {
        const timeline = body()?.querySelector('#feedback-timeline');
        if (!timeline) return null;

        const node = document.createElement('div');
        node.className = 'tl-item opacity-60';
        node.dataset.optimistic = '1';

        const bubble = document.createElement('div');
        bubble.className = 'fb-bubble border bg-white rounded-xl' + (parentId ? ' text-sm' : '');

        const head = document.createElement('div');
        head.className = 'fb-bubble-head';
        head.innerHTML = '<div class="min-w-0"><span class="font-bold text-sm text-slate-700">Bạn</span></div>'
            + '<span class="text-xs text-slate-400 shrink-0">Đang gửi…</span>';

        const bodyP = document.createElement('p');
        bodyP.className = 'fb-bubble-body text-slate-700';
        bodyP.textContent = content;

        bubble.appendChild(head);
        bubble.appendChild(bodyP);
        node.appendChild(bubble);

        const empty = timeline.querySelector('p.text-center');
        if (empty) empty.remove();

        if (parentId) {
            const parentEl = timeline.querySelector(`[data-feedback-id="${parentId}"]`);
            if (parentEl) {
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
        } else {
            timeline.appendChild(node);
        }

        scrollChatToBottom();
        return node;
    }

    async function submitForm(form) {
        if (loading) return;

        const confirmMsg = form.dataset.confirm;
        if (confirmMsg && !confirm(confirmMsg)) return;

        const errorEl = body()?.querySelector('#student-modal-form-error');
        if (errorEl) {
            errorEl.classList.add('hidden');
            errorEl.textContent = '';
        }

        const contentInput = form.querySelector('[name=content]');
        let optimisticNode = null;
        let sentContent = '';

        if (contentInput) {
            sentContent = contentInput.value.trim();
            if (!sentContent) return;
            const parentId = form.querySelector('[name=parent_id]')?.value || null;
            optimisticNode = appendOptimisticBubble(sentContent, parentId);
            contentInput.value = '';
        }

        loading = true;

        try {
            const data = new FormData(form);
            if (contentInput) {
                data.set('content', sentContent);
            }
            const spoofedMethod = (form.dataset.method || data.get('_method') || '').toString().toUpperCase();
            if (spoofedMethod) {
                data.set('_method', spoofedMethod);
            }
            const method = spoofedMethod ? 'POST' : (form.getAttribute('method') || 'POST').toUpperCase();

            const res = await fetch(form.action, {
                method,
                body: data,
                headers: {
                    ...modalHeaders(),
                    'X-CSRF-TOKEN': csrf(),
                },
            });

            const json = await res.json().catch(() => ({}));

            if (!res.ok) {
                if (optimisticNode) optimisticNode.remove();
                if (contentInput) contentInput.value = sentContent;
                const msg = firstError(json) || 'Không thể thực hiện thao tác.';
                if (errorEl) {
                    errorEl.textContent = msg;
                    errorEl.classList.remove('hidden');
                } else {
                    alert(msg);
                }
                return;
            }

            await reload();
            showToast(json.message || 'Đã lưu.');
            document.dispatchEvent(new CustomEvent('student-modal-updated'));
            if (form.classList.contains('js-care-status-form')) {
                document.dispatchEvent(new CustomEvent('student-care-status-updated'));
            }
        } catch (e) {
            if (optimisticNode) optimisticNode.remove();
            if (contentInput) contentInput.value = sentContent;
            alert(e.message || 'Lỗi kết nối.');
        } finally {
            loading = false;
        }
    }

    function scrollChatToBottom() {
        const chat = body()?.querySelector('#student-modal-chat');
        if (!chat) return;
        requestAnimationFrame(() => {
            chat.scrollTop = chat.scrollHeight;
        });
    }

    async function load(studentId, options = {}) {
        const scrollToBottom = options.scrollToBottom !== false;
        const chat = body()?.querySelector('#student-modal-chat');
        const scrollTop = chat?.scrollTop ?? 0;

        const res = await fetch(`/students/${studentId}/panel`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (!res.ok) {
            throw new Error('Không thể tải hồ sơ sinh viên.');
        }

        body().innerHTML = await res.text();

        if (window.Alpine) {
            Alpine.initTree(body());
        }

        bindPanel(body());

        if (scrollToBottom) {
            scrollChatToBottom();
        } else if (chat) {
            const newChat = body().querySelector('#student-modal-chat');
            if (newChat) newChat.scrollTop = scrollTop;
        }
    }

    async function submitReact(form, button) {
        if (loading) return;
        loading = true;

        try {
            const data = new FormData(form);
            const res = await fetch(form.action, {
                method: 'POST',
                body: data,
                headers: {
                    ...modalHeaders(),
                    'X-CSRF-TOKEN': csrf(),
                },
            });

            const json = await res.json().catch(() => ({}));
            if (!res.ok) {
                alert(firstError(json) || 'Không thể thả tim.');
                return;
            }

            button.classList.toggle('reacted', !!json.reacted);
            const icon = button.querySelector('.js-react-icon');
            const countEl = button.querySelector('.js-react-count');
            if (icon) icon.textContent = json.reacted ? '❤️' : '🤍';
            if (countEl) {
                if (json.count > 0) {
                    countEl.textContent = json.count;
                    countEl.classList.remove('hidden');
                } else {
                    countEl.textContent = '';
                    countEl.classList.add('hidden');
                }
            }
        } catch (e) {
            alert(e.message || 'Lỗi kết nối.');
        } finally {
            loading = false;
        }
    }

    function bindPanel(root) {
        root.querySelectorAll('.js-modal-form').forEach((form) => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form);
            });
            const input = form.querySelector('[name=content]');
            if (input) {
                input.addEventListener('focus', scrollChatToBottom);
            }
        });

        root.querySelectorAll('.js-resolve-form').forEach((form) => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const note = prompt('Nhập ghi chú xử lý (bắt buộc):');
                if (!note || !note.trim()) {
                    alert('Bạn phải nhập ghi chú xử lý để tiếp tục!');
                    return;
                }
                if (!confirm('Xác nhận đã xử lý?')) return;
                form.querySelector('[name=note]').value = note.trim();
                await submitForm(form);
            });
        });

        root.querySelectorAll('.js-react-form').forEach((form) => {
            const button = form.querySelector('button');
            if (!button) return;
            button.addEventListener('click', (e) => {
                e.preventDefault();
                submitReact(form, button);
            });
        });

        root.querySelectorAll('[data-care-status]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const form = btn.closest('form');
                const reason = prompt('Lý do thay đổi trạng thái (bắt buộc):');
                if (!reason || !reason.trim()) {
                    alert('Cần nhập lý do!');
                    return;
                }
                form.querySelector('[name=care_status]').value = btn.dataset.careStatus;
                form.querySelector('[name=reason]').value = reason.trim();
                submitForm(form);
            });
        });
    }

    async function open(studentId) {
        currentStudentId = studentId;
        overlay()?.classList.add('active');
        document.body.style.overflow = 'hidden';
        document.dispatchEvent(new CustomEvent('student-modal-opened', { detail: { studentId } }));

        try {
            await load(studentId);
        } catch (e) {
            close();
            alert(e.message);
        }
    }

    async function reload() {
        if (!currentStudentId) return;
        await load(currentStudentId);
    }

    function close() {
        overlay()?.classList.remove('active');
        document.body.style.overflow = '';
        currentStudentId = null;
        if (body()) body().innerHTML = '';
        document.dispatchEvent(new CustomEvent('student-modal-closed'));
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
    });

    document.addEventListener('DOMContentLoaded', () => {
        const params = new URLSearchParams(window.location.search);
        const studentId = params.get('student');
        if (studentId) {
            open(studentId);
            params.delete('student');
            const qs = params.toString();
            const url = window.location.pathname + (qs ? `?${qs}` : '');
            window.history.replaceState({}, '', url);
        }
    });

    return { open, close, reload, bindPanel, currentStudentId: () => currentStudentId };
})();
