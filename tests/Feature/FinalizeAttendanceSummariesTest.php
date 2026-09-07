<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceLocation;
use App\Models\AttendanceSummary;
use App\Models\LeaveRequest;
use App\Models\TrainingBatch;
use App\Models\TrainingClass;
use App\Models\TrainingProgram;
use App\Models\TrainingSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FinalizeAttendanceSummariesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_command_finalizes_absent_incomplete_and_leave_idempotently(): void
    {
        Carbon::setTestNow('2026-09-10 19:00:00');
        $program = TrainingProgram::factory()->create();
        $batch = TrainingBatch::query()->create(['training_program_id' => $program->id, 'name' => 'A1', 'start_date' => '2026-09-01', 'end_date' => '2026-12-01', 'quota' => 20, 'status' => 'open']);
        $class = TrainingClass::query()->create(['training_batch_id' => $batch->id, 'name' => 'Kelas A', 'capacity' => 20, 'is_active' => true]);
        $location = AttendanceLocation::query()->create(['name' => 'PPKD', 'address' => 'Jakarta', 'latitude' => -6.1, 'longitude' => 106.1, 'radius_meters' => 20, 'max_accuracy_meters' => 35, 'is_active' => true]);
        $schedule = TrainingSchedule::query()->create(['training_class_id' => $class->id, 'attendance_location_id' => $location->id, 'schedule_date' => '2026-09-10', 'start_time' => '08:00', 'end_time' => '16:00', 'morning_open' => '06:30', 'morning_on_time_limit' => '08:00', 'morning_close' => '09:00', 'afternoon_open' => '15:30', 'afternoon_early_limit' => '16:00', 'afternoon_close' => '18:00', 'status' => 'active']);
        $absent = $this->enroll($batch, $class);
        $incomplete = $this->enroll($batch, $class);
        $onLeave = $this->enroll($batch, $class);
        Attendance::query()->create(['user_id' => $incomplete->id, 'training_schedule_id' => $schedule->id, 'attendance_location_id' => $location->id, 'attendance_date' => '2026-09-10', 'type' => 'morning', 'status' => 'on_time', 'latitude' => -6.1, 'longitude' => 106.1, 'accuracy_meters' => 5, 'distance_meters' => 0, 'recorded_at' => '2026-09-10 07:50:00']);
        LeaveRequest::query()->create(['user_id' => $onLeave->id, 'type' => 'leave', 'start_date' => '2026-09-10', 'end_date' => '2026-09-10', 'reason' => 'Keperluan keluarga yang tidak dapat ditinggalkan.', 'status' => 'approved']);

        $this->artisan('attendance:finalize')->assertSuccessful();
        $this->artisan('attendance:finalize')->assertSuccessful();

        $this->assertDatabaseHas('attendance_summaries', ['user_id' => $absent->id, 'overall_status' => 'absent']);
        $this->assertDatabaseHas('attendance_summaries', ['user_id' => $incomplete->id, 'overall_status' => 'incomplete']);
        $this->assertDatabaseHas('attendance_summaries', ['user_id' => $onLeave->id, 'overall_status' => 'leave']);
        $this->assertSame(3, AttendanceSummary::query()->count());
    }

    private function enroll(TrainingBatch $batch, TrainingClass $class): User
    {
        $user = User::factory()->create(['account_status' => 'active']);
        $user->enrollments()->create(['training_batch_id' => $batch->id, 'training_class_id' => $class->id, 'status' => 'active', 'enrolled_at' => '2026-09-01']);

        return $user;
    }
}
