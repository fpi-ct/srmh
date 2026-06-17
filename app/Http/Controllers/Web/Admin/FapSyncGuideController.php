<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class FapSyncGuideController extends Controller
{
    public function index(): View
    {
        return view('admin.guides.fap-sync', [
            'activeTab' => 'admin',
            'adminSection' => 'fap-sync-guide',
            'extensionDownloadUrl' => asset('downloads/extensions/student-care-extension.zip'),
        ]);
    }
}
