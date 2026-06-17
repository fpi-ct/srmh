@extends('admin.layout')

@section('title', 'Hướng dẫn đồng bộ data FAP — Admin')

@section('admin')
<div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
        <div>
            <h3 class="font-bold text-slate-700">📘 Hướng dẫn đồng bộ data FAP</h3>
            <p class="text-sm text-slate-500">Cài extension, xuất CSV từ FAP, import vào hệ thống.</p>
        </div>
    </div>

    <div class="p-5 space-y-6">
        <section class="space-y-3" id="fapSyncSteps">
            <h4 class="font-semibold text-slate-700">Step by step</h4>

            <div data-step="1" class="p-3 rounded-lg border border-slate-200 bg-slate-50">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-sm font-semibold text-slate-700">Bước 1</p>
                    <button type="button" data-step-done class="px-3.5 py-1.5 text-sm font-semibold text-emerald-700 border border-emerald-300 bg-white hover:bg-emerald-50 rounded-md transition">✓ Done</button>
                    
                </div>
                <p class="text-sm text-slate-600">
                    Tải file
                    <a href="{{ $extensionDownloadUrl }}" class="text-indigo-600 hover:text-indigo-700 underline font-mono">student-care-extension.zip</a>.
                </p>
                <p class="text-sm text-slate-500">Làm xong bấm nút "Done".</p>
            </div>

            <div data-step="2" class="p-3 rounded-lg border border-slate-200 hidden">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-sm font-semibold text-slate-700">Bước 2</p>
                    <button type="button" data-step-done class="px-3.5 py-1.5 text-sm font-semibold text-emerald-700 border border-emerald-300 bg-white hover:bg-emerald-50 rounded-md transition">✓ Done</button>
                </div>
                <p class="text-sm text-slate-600">Giải nén file <span class="font-mono">student-care-extension.zip</span>.</p>
                <div class="mt-2 p-3 rounded-lg border border-amber-300 bg-amber-50 text-sm text-amber-800">
                    <p class="font-semibold">Lưu ý quan trọng: tránh giải nén lồng thư mục</p>
                    <p>Sau khi giải nén, thư mục bạn chọn ở bước 5 phải chứa trực tiếp các file như <span class="font-mono">manifest.json</span>, <span class="font-mono">popup.html</span>, <span class="font-mono">background.js</span>.</p>
                    <p>Nếu bên trong còn thêm 1 lớp thư mục <span class="font-mono">student-care-extension</span> nữa thì vào lớp trong cùng và chọn thư mục đó.</p>
                </div>
            </div>

            <div data-step="3" class="p-3 rounded-lg border border-slate-200 hidden">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-sm font-semibold text-slate-700">Bước 3</p>
                    <button type="button" data-step-done class="px-3.5 py-1.5 text-sm font-semibold text-emerald-700 border border-emerald-300 bg-white hover:bg-emerald-50 rounded-md transition">✓ Done</button>
                </div>
                <p class="text-sm text-slate-600">Mở <span class="font-mono">chrome://extensions</span> bằng cách copy và dán vào thanh địa chỉ Chrome.</p>
                <div class="mt-2 inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-200 bg-slate-50">
                    <span id="chromeExtensionsUrl" class="font-mono text-slate-700">chrome://extensions</span>
                    <button type="button" id="copyChromeExtensionsBtn" class="px-2.5 py-1 text-xs font-semibold text-indigo-700 bg-indigo-100 hover:bg-indigo-200 rounded-md transition">
                        Copy
                    </button>
                </div>
            </div>

            <div data-step="4" class="p-3 rounded-lg border border-slate-200 hidden">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-sm font-semibold text-slate-700">Bước 4</p>
                    <button type="button" data-step-done class="px-3.5 py-1.5 text-sm font-semibold text-emerald-700 border border-emerald-300 bg-white hover:bg-emerald-50 rounded-md transition">✓ Done</button>
                </div>
                <p class="text-sm text-slate-600">Bật <span class="font-semibold">Chế độ dành cho nhà phát triển</span>, sau đó bấm <span class="font-semibold">Tải tiện ích đã giải nén</span>.</p>
                <img src="{{ asset('images/guides/fap-sync/step2-install-real.png') }}" alt="Cài extension" class="mt-2 w-full rounded-lg border border-slate-200">
            </div>

            <div data-step="5" class="p-3 rounded-lg border border-slate-200 hidden">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-sm font-semibold text-slate-700">Bước 5</p>
                    <button type="button" data-step-done class="px-3.5 py-1.5 text-sm font-semibold text-emerald-700 border border-emerald-300 bg-white hover:bg-emerald-50 rounded-md transition">✓ Done</button>
                </div>
                <p class="text-sm text-slate-600">Chọn thư mục <span class="font-mono">extension</span> trong source code.</p>
                <div class="mt-2 p-3 rounded-lg border border-amber-300 bg-amber-50 text-sm text-amber-800">
                    <p class="font-semibold">Nhắc lại</p>
                    <p>Không chọn thư mục ngoài nếu còn lồng thêm 1 lớp <span class="font-mono">student-care-extension</span>. Chỉ chọn thư mục trong cùng, nơi chứa trực tiếp <span class="font-mono">manifest.json</span>.</p>
                </div>
                <img src="{{ asset('images/guides/fap-sync/step2-select-extension-folder.png') }}" alt="Chọn thư mục extension" class="mt-2 w-full rounded-lg border border-slate-200">
            </div>

            <div data-step="6" class="p-3 rounded-lg border border-slate-200 hidden">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-sm font-semibold text-slate-700">Bước 6</p>
                    <button type="button" data-step-done class="px-3.5 py-1.5 text-sm font-semibold text-emerald-700 border border-emerald-300 bg-white hover:bg-emerald-50 rounded-md transition">✓ Done</button>
                </div>
                <p class="text-sm text-slate-600">Mở trang FAP, bấm icon Extension trên thanh công cụ Chrome, chọn <span class="font-mono">FAP Fetch Data</span>.</p>
                <img src="{{ asset('images/guides/fap-sync/step3-open-extension.png') }}" alt="Mở extension" class="mt-2 w-full rounded-lg border border-slate-200">
            </div>

            <div data-step="7" class="p-3 rounded-lg border border-slate-200 hidden">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-sm font-semibold text-slate-700">Bước 7</p>
                    <button type="button" data-step-done class="px-3.5 py-1.5 text-sm font-semibold text-emerald-700 border border-emerald-300 bg-white hover:bg-emerald-50 rounded-md transition">✓ Done</button>
                </div>
                <p class="text-sm text-slate-600">Ở popup <span class="font-mono">FAP Export</span>, chọn học kỳ và bấm <span class="font-mono">Xuất CSV</span>.</p>
                <img src="{{ asset('images/guides/fap-sync/step3-export-real.png') }}" alt="Xuất CSV" class="mt-2 w-full rounded-lg border border-slate-200">
            </div>

            <div data-step="8" class="p-3 rounded-lg border border-slate-200 hidden">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-sm font-semibold text-slate-700">Bước 8</p>
                    <button type="button" data-step-done class="px-3.5 py-1.5 text-sm font-semibold text-emerald-700 border border-emerald-300 bg-white hover:bg-emerald-50 rounded-md transition">✓ Done</button>
                </div>
                <p class="text-sm text-slate-600">Vào tab <span class="font-mono">Sinh viên</span>, bấm <span class="font-mono">📄 Import FAP CSV</span> và chọn file CSV vừa xuất.</p>
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const stepsRoot = document.getElementById('fapSyncSteps');
        if (stepsRoot) {
            const steps = Array.from(stepsRoot.querySelectorAll('[data-step]'));

            function revealStep(stepNumber) {
                const node = stepsRoot.querySelector('[data-step="' + stepNumber + '"]');
                if (node) {
                    node.classList.remove('hidden');
                }
            }

            steps.forEach(function (stepNode) {
                const doneBtn = stepNode.querySelector('[data-step-done]');
                if (!doneBtn) return;
                doneBtn.addEventListener('click', function () {
                    stepNode.classList.add('bg-emerald-50', 'border-emerald-200');
                    doneBtn.textContent = 'Done';
                    doneBtn.disabled = true;
                    doneBtn.classList.remove('hover:bg-emerald-200');
                    doneBtn.classList.add('opacity-70', 'cursor-not-allowed');
                    const current = Number(stepNode.getAttribute('data-step') || 0);
                    revealStep(current + 1);
                });
            });
        }

        const btn = document.getElementById('copyChromeExtensionsBtn');
        const textNode = document.getElementById('chromeExtensionsUrl');
        if (!btn || !textNode) return;

        btn.addEventListener('click', function () {
            const value = (textNode.textContent || '').trim();
            const temp = document.createElement('input');
            temp.type = 'text';
            temp.value = value;
            document.body.appendChild(temp);
            temp.select();
            temp.setSelectionRange(0, value.length);
            document.execCommand('copy');
            document.body.removeChild(temp);

            const original = btn.textContent;
            btn.textContent = 'Copied';
            setTimeout(function () {
                btn.textContent = original;
            }, 1200);
        });
    })();
</script>
@endpush
