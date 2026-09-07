<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_ppkd_without_required_permission_receives_403(): void
    {
        Permission::findOrCreate('manage training master');
        $role = Role::findOrCreate('admin-ppkd');
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole($role);

        $this->actingAs($admin)->get(route('admin.programs.index'))->assertForbidden();
    }

    public function test_permission_granted_to_admin_ppkd_unlocks_the_endpoint(): void
    {
        $permission = Permission::findOrCreate('manage training master');
        $role = Role::findOrCreate('admin-ppkd');
        $role->givePermissionTo($permission);
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole($role);

        $this->actingAs($admin)->get(route('admin.programs.index'))->assertOk();
    }

    public function test_super_admin_keeps_full_access_without_direct_permission(): void
    {
        Permission::findOrCreate('manage training master');
        $superAdmin = User::factory()->create(['account_status' => 'active']);
        $superAdmin->assignRole(Role::findOrCreate('super-admin'));

        $this->actingAs($superAdmin)->get(route('admin.programs.index'))->assertOk();
    }
}
