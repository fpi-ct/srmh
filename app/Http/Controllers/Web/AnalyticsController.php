<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __construct(
        private AnalyticsService $analyticsService
    ) {}

    public function index(Request $request): View
    {
        $data = $this->analyticsService->dashboard($request->user());

        return view('analytics.index', [
            'activeTab' => 'analytics',
            'data' => $data,
        ]);
    }
}
