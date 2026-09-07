<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminHolidayTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_create_holiday_and_duplicate_date_is_rejected(): void
    {
        $admin = $this->admin();
        $data = ['holiday_date' => '2026-12-25', 'name' => 'Hari Libur', 'description' => 'Libur pelatihan', 'is_active' => true];
        $this->actingAs($admin)->post(route('admin.holidays.store'), $data)->assertRedirect(route('admin.holidays.index'));
        $this->actingAs($admin)->post(route('admin.holidays.store'), [...$data, 'name' => 'Duplikat'])->assertSessionHasErrors('holiday_date');
        $this->assertDatabaseCount('holidays', 1);
    }

    private function admin(): User
    {
        Role::findOrCreate('admin-ppkd');
        $user = User::factory()->create(['account_status' => 'active']);
        $user->assignRole('admin-ppkd');

        return $user;
    }
}
