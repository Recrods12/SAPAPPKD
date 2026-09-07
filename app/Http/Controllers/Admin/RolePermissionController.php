<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateRolePermissionsRequest;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function index(): Response
    {
        abort_unless(request()->user()->hasRole('super-admin'), 403);

        return Inertia::render('admin/role-permissions/index', ['roles' => Role::query()->with('permissions:id,name')->orderBy('name')->get(['id', 'name']), 'permissions' => Permission::query()->orderBy('name')->get(['id', 'name'])]);
    }

    public function update(UpdateRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        abort_if($role->name === 'super-admin', 422, 'Permission Super Admin bersifat penuh dan tidak dapat dikurangi.');
        $before = $role->permissions()->pluck('name')->all();
        $role->syncPermissions($request->validated('permissions'));
        ActivityLog::query()->create(['user_id' => $request->user()->id, 'event' => 'role.permissions_updated', 'subject_type' => Role::class, 'subject_id' => $role->id, 'properties' => ['before' => $before, 'after' => $request->validated('permissions')], 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);

        return back()->with('status', "Permission peran {$role->name} berhasil diperbarui.");
    }
}
