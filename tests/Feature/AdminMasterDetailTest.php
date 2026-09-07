<?php

namespace Tests\Feature;

use App\Models\AttendanceLocation;
use App\Models\Holiday;
use App\Models\InstructorProfile;
use App\Models\ParticipantProfile;
use App\Models\TrainingBatch;
use App\Models\TrainingClass;
use App\Models\TrainingProgram;
use App\Models\TrainingSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminMasterDetailTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_super_admin_can_open_every_required_master_detail_page(): void
    {
        Role::findOrCreate('super-admin');
        Role::findOrCreate('instructor');
        Role::findOrCreate('participant');

        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole('super-admin');

        $program = TrainingProgram::factory()->create();
        $batch = TrainingBatch::factory()->for($program)->create();
        $class = TrainingClass::factory()->for($batch)->create();
        $location = AttendanceLocation::factory()->create();
        $schedule = TrainingSchedule::factory()->for($class)->for($location)->create();
        $holiday = Holiday::factory()->create();

        $instructor = User::factory()->create(['account_status' => 'active']);
        $instructor->assignRole('instructor');
        InstructorProfile::factory()->for($instructor)->create();

        $participant = User::factory()->create(['account_status' => 'active']);
        $participant->assignRole('participant');
        ParticipantProfile::factory()->for($participant)->create();

        $routes = [
            ['admin.programs.show', $program],
            ['admin.batches.show', $batch],
            ['admin.classes.show', $class],
            ['admin.instructors.show', $instructor],
            ['admin.schedules.show', $schedule],
            ['admin.holidays.show', $holiday],
            ['admin.locations.show', $location],
            ['admin.participants.show', $participant],
        ];

        foreach ($routes as [$routeName, $model]) {
            $this->actingAs($admin)->get(route($routeName, $model))->assertInertia(
                fn (Assert $page) => $page
                    ->component('admin/master-detail')
                    ->has('title')
                    ->has('backUrl')
                    ->has('editUrl')
                    ->has('sections')
            );
        }
    }

    public function test_master_detail_pages_still_enforce_role_boundary(): void
    {
        Role::findOrCreate('participant');
        $participant = User::factory()->create(['account_status' => 'active']);
        $participant->assignRole('participant');

        $this->actingAs($participant)
            ->get(route('admin.programs.show', TrainingProgram::factory()->create()))
            ->assertForbidden();
    }

    public function test_master_list_filters_and_sorting_are_applied_server_side(): void
    {
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole('super-admin');

        TrainingProgram::factory()->create(['name' => 'Zulu', 'is_active' => true]);
        TrainingProgram::factory()->create(['name' => 'Alpha', 'is_active' => true]);
        TrainingProgram::factory()->create(['name' => 'Program Nonaktif', 'is_active' => false]);

        $this->actingAs($admin)
            ->get(route('admin.programs.index', ['status' => 'active', 'sort' => 'name', 'direction' => 'asc']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/programs/index')
                ->where('status', 'active')
                ->where('sort', 'name')
                ->where('direction', 'asc')
                ->has('programs.data', 2)
                ->where('programs.data.0.name', 'Alpha')
                ->where('programs.data.1.name', 'Zulu'));
    }

    public function test_unknown_sort_column_falls_back_to_a_safe_allowlisted_column(): void
    {
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->get(route('admin.locations.index', ['sort' => 'malicious_column', 'direction' => 'sideways']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('sort', 'created_at')
                ->where('direction', 'desc'));
    }
}
