<?php

namespace Tests\Feature;

use App\Models\AttendanceLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAttendanceLocationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_create_location_with_configurable_radius(): void
    {
        $payload = ['name' => 'PPKD Barat', 'address' => 'Jakarta Barat', 'latitude' => -6.1234567, 'longitude' => 106.1234567, 'radius_meters' => 20, 'max_accuracy_meters' => 35, 'is_active' => true];
        $this->actingAs($this->admin())->post(route('admin.locations.store'), $payload)->assertRedirect(route('admin.locations.index'));
        $this->assertDatabaseHas('attendance_locations', ['name' => 'PPKD Barat', 'radius_meters' => 20]);
    }

    public function test_invalid_coordinates_and_radius_are_rejected(): void
    {
        $this->actingAs($this->admin())->post(route('admin.locations.store'), ['name' => 'Salah', 'address' => 'Jakarta', 'latitude' => 100, 'longitude' => 200, 'radius_meters' => 1, 'max_accuracy_meters' => 1, 'is_active' => true])->assertSessionHasErrors(['latitude', 'longitude', 'radius_meters', 'max_accuracy_meters']);
    }

    public function test_active_location_cannot_be_archived(): void
    {
        $location = AttendanceLocation::query()->forceCreate(['name' => 'Aktif', 'address' => 'Jakarta', 'latitude' => -6.1, 'longitude' => 106.1, 'radius_meters' => 20, 'max_accuracy_meters' => 35, 'is_active' => true]);
        $this->actingAs($this->admin())->delete(route('admin.locations.destroy', $location))->assertUnprocessable();
        $this->assertDatabaseHas('attendance_locations', ['id' => $location->id, 'deleted_at' => null]);
    }

    private function admin(): User
    {
        Role::findOrCreate('admin-ppkd');
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole('admin-ppkd');

        return $admin;
    }
}
