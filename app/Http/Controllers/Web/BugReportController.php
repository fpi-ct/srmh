<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBugReportRequest;
use App\Models\BugReport;
use Illuminate\Http\RedirectResponse;

class BugReportController extends Controller
{
    public function store(StoreBugReportRequest $request): RedirectResponse
    {
        $user = $request->user();

        BugReport::create([
            'author_access_code' => $user->access_code,
            'author_name' => $user->full_name,
            'content' => $request->validated('content'),
            'status' => 'open',
        ]);

        return back()->with('success', 'Đã gửi báo cáo lỗi / góp ý. Cảm ơn bạn!');
    }
}
