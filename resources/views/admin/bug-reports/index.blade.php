@extends('admin.layout')

@section('title', 'Báo lỗi — Admin')

@section('admin')
<div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
        <h3 class="font-bold text-slate-700">🐛 Báo lỗi / Góp ý hệ thống</h3>
        <span class="text-xs px-2 py-0.5 rounded-full font-semibold {{ $openCount > 0 ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
            {{ $openCount }} chưa xử lý
        </span>
    </div>

    <form method="GET" action="{{ route('admin.bug-reports') }}" class="flex flex-wrap gap-2 px-5 py-3 border-b border-slate-50 bg-slate-50/40">
        <input type="text" name="search" value="{{ $search }}" placeholder="🔍 Nội dung hoặc người gửi..."
               class="flex-1 min-w-[160px] px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:border-indigo-400 focus:outline-none">
        <select name="status" class="px-2 py-1.5 text-sm border border-slate-200 rounded-lg bg-white focus:border-indigo-400 focus:outline-none">
            <option value="">Tất cả</option>
            <option value="open" @selected($status === 'open')>Chưa xử lý</option>
            <option value="resolved" @selected($status === 'resolved')>Đã xử lý</option>
        </select>
        <button type="submit" class="px-3 py-1.5 text-sm font-semibold bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">Lọc</button>
    </form>
    <div class="px-5 py-3 border-b border-slate-50 bg-rose-50/60">
        <form method="POST" action="{{ route('admin.bug-reports.student-data.destroy') }}" onsubmit="return confirm('Xác nhận xoá toàn bộ dữ liệu sinh viên?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-3 py-1.5 text-sm font-semibold bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition">Xoá dữ liệu</button>
        </form>
    </div>

    <div class="divide-y divide-slate-50">
        @forelse($reports as $report)
            <div class="px-5 py-3 {{ $report->status === 'resolved' ? 'opacity-60' : '' }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm text-slate-700">{{ $report->content }}</p>
                        <p class="text-xs text-slate-400 mt-1">{{ $report->author_name }} ({{ $report->author_access_code }}) · {{ $report->created_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</p>
                    </div>
                    @if($report->status === 'open')
                        <form method="POST" action="{{ route('admin.bug-reports.resolve', $report) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-xs px-2 py-1 bg-emerald-50 text-emerald-700 rounded-lg font-semibold hover:bg-emerald-100 shrink-0">Đã xử lý</button>
                        </form>
                    @else
                        <span class="text-xs text-emerald-600 font-semibold shrink-0">✓ Đã xử lý</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-slate-400 text-sm">Không có báo cáo phù hợp bộ lọc</div>
        @endforelse
    </div>

    @if($reports->hasPages())
        <div class="px-5 py-3 border-t border-slate-100">{{ $reports->links() }}</div>
    @endif
</div>
@endsection
