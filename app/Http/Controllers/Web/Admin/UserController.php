<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $roleFilter = $request->get('role');
        $facultyFilter = $request->get('faculty');
        $search = trim((string) $request->get('search'));
        $statusFilter = $request->get('active');

        $query = User::query()->orderBy('full_name');

        if ($roleFilter) {
            $query->where('role', $roleFilter);
        }

        if ($facultyFilter) {
            $query->where('faculties', 'like', '%'.$facultyFilter.'%');
        }

        if ($statusFilter !== null && $statusFilter !== '') {
            $query->where('is_active', $statusFilter === '1');
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('access_code', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%");
            });
        }

        $faculties = User::query()
            ->whereNotNull('faculties')
            ->where('faculties', '!=', '')
            ->pluck('faculties')
            ->flatMap(fn ($f) => array_map('trim', explode(',', $f)))
            ->unique()
            ->sort()
            ->values();

        return view('admin.users.index', [
            'activeTab' => 'admin',
            'adminSection' => 'users',
            'users' => $query->paginate(20)->withQueryString(),
            'faculties' => $faculties,
            'roleFilter' => $roleFilter,
            'facultyFilter' => $facultyFilter,
            'statusFilter' => $statusFilter,
            'search' => $search,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::create([
            'access_code' => $request->validated('access_code'),
            'full_name' => $request->validated('full_name'),
            'role' => $request->validated('role'),
            'role_label' => $request->validated('role_label'),
            'faculties' => $request->validated('faculties'),
            'is_active' => true,
        ]);

        return back()->with('success', 'Đã tạo tài khoản mới.');
    }

    public function toggle(Request $request, string $accessCode): RedirectResponse
    {
        $user = User::where('access_code', $accessCode)->firstOrFail();

        if ($user->role === UserRole::Admin) {
            return back()->with('error', 'Không thể vô hiệu hóa tài khoản Admin.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->is_active ? 'Đã kích hoạt tài khoản.' : 'Đã vô hiệu hóa tài khoản.');
    }
}
