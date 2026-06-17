<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportRosterRequest;
use App\Jobs\ImportFapRosterJob;
use Illuminate\Http\RedirectResponse;

class RosterImportController extends Controller
{
    public function store(ImportRosterRequest $request): RedirectResponse
    {
        $file = $request->file('file');
        $storedPath = $file->store('imports/fap');

        ImportFapRosterJob::dispatch(
            storage_path('app/'.$storedPath),
            $file->getClientOriginalName()
        );

        return back()->with('success', 'Đã tải lên file, chờ trong giây lát để import.');
    }
}
