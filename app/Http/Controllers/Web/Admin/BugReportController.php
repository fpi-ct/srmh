<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\BugReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BugReportController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->get('status');
        $search = trim((string) $request->get('search'));

        $query = BugReport::query()->orderByDesc('created_at');

        if (in_array($status, ['open', 'resolved'], true)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('content', 'like', "%{$search}%")
                    ->orWhere('author_name', 'like', "%{$search}%")
                    ->orWhere('author_access_code', 'like', "%{$search}%");
            });
        }

        return view('admin.bug-reports.index', [
            'activeTab' => 'admin',
            'adminSection' => 'bug-reports',
            'reports' => $query->paginate(20)->withQueryString(),
            'status' => $status,
            'search' => $search,
            'openCount' => BugReport::where('status', 'open')->count(),
        ]);
    }

    public function resolve(BugReport $bugReport): RedirectResponse
    {
        $bugReport->update(['status' => 'resolved']);

        return back()->with('success', 'Đã đánh dấu báo cáo là đã xử lý.');
    }
}
