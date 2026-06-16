<div class="relative" x-data="srmhNotifications()" x-init="init()" @click.outside="open = false">
    <button type="button" @click="toggle()" id="notifBtn"
            class="relative p-2 rounded-lg hover:bg-slate-100 transition" title="Thông báo">
        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        <span x-show="count > 0" x-text="count > 9 ? '9+' : count"
              class="absolute top-1 right-1 min-w-4 h-4 px-0.5 bg-rose-500 text-white text-[10px] rounded-full flex items-center justify-center font-bold"></span>
    </button>

    <div id="notifDropdown" class="notif-dropdown" :class="{ 'active': open }">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
            <p class="text-sm font-bold text-slate-700">🔔 Thông báo (24h qua)</p>
            <button type="button" x-show="count > 0" @click="markAllRead()"
                    class="text-xs text-indigo-600 hover:text-indigo-700 font-semibold">Đọc hết</button>
        </div>
        <div id="notifList" class="py-1 max-h-80 overflow-y-auto">
            <template x-if="items.length === 0">
                <div class="px-4 py-8 text-center">
                    <div class="text-3xl mb-2">✅</div>
                    <p class="text-sm text-slate-500">Không có cập nhật nào trong 24h qua</p>
                </div>
            </template>
            <template x-for="item in items" :key="item.id">
                <div @click="goTo(item)"
                     class="flex items-start gap-3 px-4 py-3 bg-white hover:bg-slate-50 cursor-pointer transition border-b border-slate-100 last:border-0">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 mt-0.5 bg-white border border-slate-200 shadow-sm"
                         x-text="statusIcon(item.care_status)"></div>
                    <div class="min-w-0 pr-4">
                        <p class="text-sm font-semibold text-slate-800" x-text="item.student_name || item.title"></p>
                        <p class="text-xs text-slate-500 font-mono" x-show="item.student_code"
                           x-text="item.student_code"></p>
                        <p class="text-xs text-slate-500 mt-0.5 truncate" x-text="'💬 ' + (item.body || '')"></p>
                        <p class="text-xs mt-1 font-medium" :class="statusClass(item.care_status)"
                           x-text="'⏱️ ' + timeAgo(item.created_at)"></p>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
