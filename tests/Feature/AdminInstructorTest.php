<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminInstructorTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_create_instructor_account_and_profile(): void
    {
        Role::findOrCreate('instructor');
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.instructors.store'), ['name' => 'Instruktur Satu', 'email' => 'instruktur@example.test', 'password' => 'Password123!', 'password_confirmation' => 'Password123!', 'employee_number' => 'INS-01', 'phone' => '08123456789', 'address' => 'Jakarta', 'is_active' => true])->assertRedirect(route('admin.instructors.index'));
        $instructor = User::query()->where('email', 'instruktur@example.test')->firstOrFail();
        $this->assertTrue($instructor->hasRole('instructor'));
        $this->assertDatabaseHas('instructor_profiles', ['user_id' => $instructor->id, 'employee_number' => 'INS-01']);
    }

    public function test_participant_cannot_manage_instructors(): void
    {
        Role::findOrCreate('participant');
        $participant = User::factory()->create(['account_status' => 'active']);
        $participant->assignRole('participant');
        $this->actingAs($participant)->get(route('admin.instructors.index'))->assertForbidden();
    }

    private function admin(): User
    {
        Role::findOrCreate('admin-ppkd');
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole('admin-ppkd');

        return $admin;
    }
}
