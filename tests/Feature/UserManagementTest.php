<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_super_admin_can_create_an_admin_ppkd_account(): void
    {
        Role::findOrCreate('super-admin');
        Role::findOrCreate('admin-ppkd');
        $superAdmin = User::factory()->create(['account_status' => 'active']);
        $superAdmin->assignRole('super-admin');

        $this->actingAs($superAdmin)->post(route('admin.users.store'), [
            'name' => 'Admin Pelatihan',
            'username' => 'admin-pelatihan',
            'email' => 'admin.pelatihan@example.test',
            'password' => 'Rahasia123!',
            'password_confirmation' => 'Rahasia123!',
            'role' => 'admin-ppkd',
        ])->assertRedirect()->assertSessionHas('status');

        $admin = User::query()->where('username', 'admin-pelatihan')->firstOrFail();
        $this->assertTrue($admin->hasRole('admin-ppkd'));
        $this->assertSame('active', $admin->account_status);
        $this->assertTrue(ActivityLog::query()->where('event', 'user.created')->where('subject_id', $admin->id)->exists());
    }

    public function test_admin_ppkd_cannot_create_another_admin(): void
    {
        Role::findOrCreate('admin-ppkd');
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole('admin-ppkd');

        $this->actingAs($admin)->post(route('admin.users.store'))->assertForbidden();
        $this->assertSame(1, User::query()->count());
    }

    public function test_super_admin_role_cannot_be_created_through_the_form(): void
    {
        Role::findOrCreate('super-admin');
        $superAdmin = User::factory()->create(['account_status' => 'active']);
        $superAdmin->assignRole('super-admin');

        $this->actingAs($superAdmin)->post(route('admin.users.store'), [
            'name' => 'Unauthorized Super Admin',
            'username' => 'root2',
            'email' => 'root2@example.test',
            'password' => 'Rahasia123!',
            'password_confirmation' => 'Rahasia123!',
            'role' => 'super-admin',
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['username' => 'root2']);
    }
}
