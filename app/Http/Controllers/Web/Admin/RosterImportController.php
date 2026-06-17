<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportRosterRequest;
use App\Jobs\ImportFapRosterJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class RosterImportController extends Controller
{
    public function store(ImportRosterRequest $request): RedirectResponse
    {
        $file = $request->file('file');
        $storedPath = $file->store('imports/fap');
        $queuedAt = now();

        ImportFapRosterJob::dispatch(
            storage_path('app/'.$storedPath),
            $file->getClientOriginalName()
        );

        return back()
            ->with('success', 'Đã tải lên file, chờ trong giây lát để import.')
            ->with('import_poll_since', $queuedAt->toIso8601String());
    }

    public function status(): JsonResponse
    {
        $lastImportedAt = Cache::get('fap_last_imported_at');
        $lastImportedAt = $lastImportedAt instanceof Carbon ? $lastImportedAt : null;

        return response()->json([
            'last_imported_at' => $lastImportedAt?->toIso8601String(),
        ]);
    }
}
