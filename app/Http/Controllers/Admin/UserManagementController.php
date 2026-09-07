<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdministrativeUserRequest;
use App\Http\Requests\Admin\UpdateAdministrativeUserRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function index(): Response
    {
        abort_unless(request()->user()->hasRole('super-admin'), 403);

        return Inertia::render('admin/users/index', ['users' => User::query()->with('roles:id,name')->latest()->paginate(20)]);
    }

    public function store(StoreAdministrativeUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = User::query()->create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'account_status' => 'active',
            'verified_at' => now(),
            'verified_by' => $request->user()->id,
        ]);
        $user->assignRole($data['role']);
        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'event' => 'user.created',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'properties' => ['role' => $data['role'], 'account_status' => 'active'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('status', 'Akun admin berhasil dibuat.');
    }

    public function update(UpdateAdministrativeUserRequest $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 422, 'Akun sendiri tidak dapat diubah dari halaman ini.');
        abort_if($user->hasRole('super-admin'), 422, 'Akun Super Admin lain tidak dapat diubah.');
        $data = $request->validated();
        $before = ['role' => $user->getRoleNames()->first(), 'account_status' => $user->account_status];
        $user->syncRoles([$data['role']]);
        $user->update(['account_status' => $data['account_status']]);
        ActivityLog::query()->create(['user_id' => $request->user()->id, 'event' => 'user.access_updated', 'subject_type' => User::class, 'subject_id' => $user->id, 'properties' => ['before' => $before, 'after' => $data], 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);

        return back()->with('status', 'Akses pengguna berhasil diperbarui.');
    }
}
