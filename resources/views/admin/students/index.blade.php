@extends('admin.layout')

@section('title', 'Sinh viên — Admin')

@section('admin')
<div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
        <div>
            <h3 class="font-bold text-slate-700">🎓 Danh sách sinh viên</h3>
            <p class="text-xs text-slate-400">
                Dữ liệu cập nhật lần cuối:
                {{ $lastUpdatedAt ? $lastUpdatedAt->format('d/m/Y H:i') : '--' }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-slate-400 font-semibold">{{ $students->total() }} SV</span>
            <button type="button" id="openImportModalBtn" class="text-xs px-3 py-2 bg-emerald-600 text-white rounded-xl font-semibold hover:bg-emerald-700 transition shadow-sm">
                📄 Import FAP CSV
            </button>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.students') }}" class="flex flex-wrap gap-2 px-5 py-3 border-b border-slate-50 bg-slate-50/40">
        <input type="text" name="search" value="{{ $search }}" placeholder="🔍 MSSV hoặc tên..."
               class="flex-1 min-w-[160px] px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:border-indigo-400 focus:outline-none">
        <select name="care_status" class="px-2 py-1.5 text-sm border border-slate-200 rounded-lg bg-white focus:border-indigo-400 focus:outline-none">
            <option value="">Mọi trạng thái</option>
            @foreach(\App\Enums\CareStatus::cases() as $cs)
                <option value="{{ $cs->value }}" @selected($status === $cs->value)>{{ \App\Support\CareStatusUi::emoji($cs) }} {{ \App\Support\CareStatusUi::label($cs) }}</option>
            @endforeach
        </select>
        <select name="faculty" class="px-2 py-1.5 text-sm border border-slate-200 rounded-lg bg-white focus:border-indigo-400 focus:outline-none">
            <option value="">Tất cả bộ môn</option>
            @foreach($faculties as $f)
                <option value="{{ $f }}" @selected($faculty === $f)>{{ $f }}</option>
            @endforeach
        </select>
        <select name="class_section" class="px-2 py-1.5 text-sm border border-slate-200 rounded-lg bg-white focus:border-indigo-400 focus:outline-none">
            <option value="">Tất cả lớp</option>
            @foreach($classes as $c)
                <option value="{{ $c }}" @selected($classSection === $c)>{{ $c }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-3 py-1.5 text-sm font-semibold bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">Lọc</button>
    </form>

    <div class="divide-y divide-slate-50">
        @forelse($students as $student)
            @php
                $classNames = $student->studentClasses->pluck('class_name')->unique()->implode(', ');
                $facultyNames = $student->studentClasses->pluck('faculty')->unique()->filter()->implode(', ');
                $maxAbsence = $student->studentClasses->max('absence_rate');
            @endphp
            <a href="{{ route('dashboard', ['status' => 'all', 'student' => $student->id]) }}"
               class="flex items-center justify-between px-5 py-3 hover:bg-slate-50/50 transition">
                <div class="min-w-0">
                    <p class="font-semibold text-slate-800 text-sm truncate">
                        {{ $student->full_name }}
                        <span class="font-mono font-medium text-slate-400">· {{ $student->student_code }}</span>
                    </p>
                    <p class="text-xs text-slate-400 truncate">
                        🏫 {{ $classNames ?: 'N/A' }}@if($facultyNames) · 📚 {{ $facultyNames }}@endif
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    @if($maxAbsence !== null && $maxAbsence > 0)
                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded {{ \App\Support\AbsenceRateUi::badgeClass($maxAbsence) }}">
                            Vắng {{ number_format($maxAbsence, 1) }}%
                        </span>
                    @endif
                    <span class="text-[11px] text-slate-400">💬 {{ $student->feedbacks_count }}</span>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ \App\Support\CareStatusUi::badge($student->care_status) }}">
                        {{ \App\Support\CareStatusUi::emoji($student->care_status) }} {{ \App\Support\CareStatusUi::label($student->care_status) }}
                    </span>
                </div>
            </a>
        @empty
            <div class="p-8 text-center text-slate-400 text-sm">Không có sinh viên phù hợp bộ lọc</div>
        @endforelse
    </div>

    @if($students->hasPages())
        <div class="px-5 py-3 border-t border-slate-100">{{ $students->links() }}</div>
    @endif
</div>

<div id="importFapModal" class="modal-overlay backdrop-blur-sm" onclick="if(event.target===this) this.classList.remove('active')">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl mx-4 overflow-hidden border border-slate-100">
        <div class="px-6 py-4 border-b border-slate-100 flex items-start justify-between bg-gradient-to-r from-indigo-50 to-white">
            <div>
                <h3 class="text-xl font-bold text-slate-800">Import FAP CSV</h3>
                <p class="text-sm text-slate-500 mt-1">Tải file CSV từ extension để cập nhật dữ liệu sinh viên.</p>
            </div>
            <button type="button" id="closeImportModalBtn" class="text-slate-400 hover:text-slate-600 text-2xl leading-none">×</button>
        </div>
        <form method="POST" action="{{ route('admin.roster.import') }}" enctype="multipart/form-data" class="p-5 space-y-4" id="fapImportForm">
            @csrf
            <div class="flex items-center justify-between gap-3 rounded-xl border border-indigo-100 bg-indigo-50/60 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-700">Chưa có file CSV?</p>
                    <p class="text-xs text-slate-500">Tải file mẫu để kiểm tra đúng định dạng.</p>
                </div>
                <a href="{{ asset('downloads/fap-export-demo.csv') }}" download class="shrink-0 inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white border border-indigo-200 text-sm font-semibold text-indigo-600 hover:bg-indigo-100">
                    Tải file mẫu
                </a>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5">
                <p class="text-xs text-amber-800">
                    Không cần làm thủ công nếu dùng tool export.
                    <a href="{{ route('admin.fap-sync-guide') }}" class="font-semibold underline hover:no-underline">
                        Xem hướng dẫn tải extension
                    </a>.
                </p>
            </div>
            <div id="fapDropzone" class="group border-2 border-dashed border-slate-300 rounded-2xl p-5 text-center transition-all bg-slate-50 hover:border-indigo-300 hover:bg-indigo-50/40 cursor-pointer">
                <div class="w-12 h-12 mx-auto rounded-full bg-white border border-slate-200 flex items-center justify-center text-xl shadow-sm">📄</div>
                <p class="text-base text-slate-700 mt-3 font-semibold">Kéo thả file CSV vào đây</p>
                <p class="text-sm text-slate-500 mt-1">hoặc bấm để chọn file từ máy tính</p>
                <p class="text-xs text-slate-400 mt-2 font-medium" id="fapSelectedFile">Chưa có file nào được chọn</p>
                <input type="file" id="fapFileInput" name="file" accept=".csv,.txt" class="hidden" required>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" id="cancelImportBtn" class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
                    Hủy
                </button>
                <button type="submit" id="confirmImportBtn" class="px-5 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition shadow-sm">
                    Xác nhận import
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const importPollSince = @json(session('import_poll_since'));
        const importStatusUrl = @json(url('/admin/roster/import/status'));
        const modal = document.getElementById('importFapModal');
        const openBtn = document.getElementById('openImportModalBtn');
        const closeBtn = document.getElementById('closeImportModalBtn');
        const cancelBtn = document.getElementById('cancelImportBtn');
        const dropzone = document.getElementById('fapDropzone');
        const fileInput = document.getElementById('fapFileInput');
        const selectedFileText = document.getElementById('fapSelectedFile');
        const confirmImportBtn = document.getElementById('confirmImportBtn');
        const form = document.getElementById('fapImportForm');

        if (!modal || !openBtn || !closeBtn || !cancelBtn || !dropzone || !fileInput || !selectedFileText || !confirmImportBtn || !form) {
            return;
        }

        function showModal() {
            modal.classList.add('active');
        }

        function hideModal() {
            modal.classList.remove('active');
        }

        function updateSelectedFileText(file) {
            selectedFileText.textContent = file ? 'Đã chọn: ' + file.name : 'Chưa có file nào được chọn';
            confirmImportBtn.disabled = !file;
            if (file) {
                confirmImportBtn.classList.remove('opacity-60', 'cursor-not-allowed');
                dropzone.classList.add('border-emerald-400', 'bg-emerald-50');
            } else {
                confirmImportBtn.classList.add('opacity-60', 'cursor-not-allowed');
                dropzone.classList.remove('border-emerald-400', 'bg-emerald-50');
            }
        }

        openBtn.addEventListener('click', showModal);
        closeBtn.addEventListener('click', hideModal);
        cancelBtn.addEventListener('click', hideModal);

        dropzone.addEventListener('click', function () {
            fileInput.click();
        });

        dropzone.addEventListener('dragover', function (event) {
            event.preventDefault();
            dropzone.classList.add('border-indigo-400', 'bg-indigo-50');
        });

        dropzone.addEventListener('dragleave', function () {
            dropzone.classList.remove('border-indigo-400', 'bg-indigo-50');
        });

        dropzone.addEventListener('drop', function (event) {
            event.preventDefault();
            dropzone.classList.remove('border-indigo-400', 'bg-indigo-50');
            const files = event.dataTransfer.files;
            if (!files || !files.length) {
                return;
            }

            fileInput.files = files;
            updateSelectedFileText(files[0]);
        });

        fileInput.addEventListener('change', function () {
            updateSelectedFileText(fileInput.files[0] || null);
        });

        form.addEventListener('submit', function () {
            hideModal();
        });

        updateSelectedFileText(fileInput.files[0] || null);

        if (importPollSince && importStatusUrl) {
            const startedAt = new Date(importPollSince);
            const timer = setInterval(function () {
                fetch(importStatusUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (data) {
                        if (!data || !data.last_imported_at) {
                            return;
                        }

                        const lastImportedAt = new Date(data.last_imported_at);
                        if (lastImportedAt >= startedAt) {
                            clearInterval(timer);
                            Swal.fire({
                                icon: 'success',
                                title: 'Hoàn tất',
                                text: 'Import FAP CSV đã chạy xong.',
                                confirmButtonText: 'Đóng'
                            });
                        }
                    })
                    .catch(function () {});
            }, 3000);

            setTimeout(function () {
                clearInterval(timer);
            }, 300000);
        }
    })();
</script>
@endpush
