<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            if ($request->user()?->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            } else {
                Auth::logout();
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Account is inactive.'], 403);
            }

            return redirect()->route('login')->withErrors(['access_code' => 'Tài khoản đã bị vô hiệu hóa.']);
        }

        return $next($request);
    }
}
