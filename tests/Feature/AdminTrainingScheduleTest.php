<?php

namespace Tests\Feature;

use App\Models\AttendanceLocation;
use App\Models\TrainingBatch;
use App\Models\TrainingClass;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminTrainingScheduleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_create_schedule_with_attendance_windows(): void
    {
        [$class,$location] = $this->masterData();
        $data = ['training_class_id' => $class->id, 'attendance_location_id' => $location->id, 'schedule_date' => '2026-09-10', 'start_time' => '08:00', 'end_time' => '16:00', 'morning_open' => '06:30', 'morning_on_time_limit' => '08:00', 'morning_close' => '09:00', 'afternoon_open' => '15:30', 'afternoon_early_limit' => '16:00', 'afternoon_close' => '18:00', 'subject' => 'Orientasi', 'notes' => null, 'status' => 'active'];
        $this->actingAs($this->admin())->post(route('admin.schedules.store'), $data)->assertRedirect(route('admin.schedules.index'));
        $this->assertDatabaseHas('training_schedules', ['training_class_id' => $class->id, 'schedule_date' => '2026-09-10']);
    }

    public function test_invalid_attendance_window_order_is_rejected(): void
    {
        [$class,$location] = $this->masterData();
        $data = ['training_class_id' => $class->id, 'attendance_location_id' => $location->id, 'schedule_date' => '2026-09-10', 'start_time' => '08:00', 'end_time' => '16:00', 'morning_open' => '09:00', 'morning_on_time_limit' => '08:00', 'morning_close' => '07:00', 'afternoon_open' => '15:30', 'afternoon_early_limit' => '16:00', 'afternoon_close' => '18:00', 'status' => 'active'];
        $this->actingAs($this->admin())->post(route('admin.schedules.store'), $data)->assertSessionHasErrors(['morning_on_time_limit', 'morning_close']);
    }

    private function admin(): User
    {
        Role::findOrCreate('admin-ppkd');
        $user = User::factory()->create(['account_status' => 'active']);
        $user->assignRole('admin-ppkd');

        return $user;
    }

    private function masterData(): array
    {
        $program = TrainingProgram::factory()->create();
        $batch = TrainingBatch::query()->create(['training_program_id' => $program->id, 'name' => 'Angkatan 1', 'start_date' => '2026-09-01', 'end_date' => '2026-12-01', 'quota' => 20, 'status' => 'open']);
        $class = TrainingClass::query()->create(['training_batch_id' => $batch->id, 'name' => 'Kelas A', 'capacity' => 20, 'is_active' => true]);
        $location = AttendanceLocation::query()->create(['name' => 'PPKD', 'address' => 'Jakarta', 'latitude' => -6.1, 'longitude' => 106.1, 'radius_meters' => 20, 'max_accuracy_meters' => 35, 'is_active' => true]);

        return [$class, $location];
    }
}
