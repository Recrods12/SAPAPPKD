<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceLocation;
use App\Models\LeaveRequest;
use App\Models\TrainingBatch;
use App\Models\TrainingClass;
use App\Models\TrainingProgram;
use App\Models\TrainingSchedule;
use App\Models\User;
use App\Services\ParticipantAttendanceSummaryService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ParticipantAttendanceSummaryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_summary_derives_present_incomplete_leave_and_absent_from_schedules(): void
    {
        Carbon::setTestNow('2026-09-10 17:00:00');
        $program = TrainingProgram::factory()->create();
        $batch = TrainingBatch::query()->create(['training_program_id' => $program->id, 'name' => 'A1', 'start_date' => '2026-09-01', 'end_date' => '2026-12-01', 'quota' => 20, 'status' => 'open']);
        $class = TrainingClass::query()->create(['training_batch_id' => $batch->id, 'name' => 'Kelas A', 'capacity' => 20, 'is_active' => true]);
        $location = AttendanceLocation::query()->create(['name' => 'PPKD', 'address' => 'Jakarta', 'latitude' => -6.1, 'longitude' => 106.1, 'radius_meters' => 20, 'max_accuracy_meters' => 35, 'is_active' => true]);
        $user = User::factory()->create(['account_status' => 'active']);
        $user->enrollments()->create(['training_batch_id' => $batch->id, 'training_class_id' => $class->id, 'status' => 'active', 'enrolled_at' => '2026-09-01']);
        $schedules = collect(['2026-09-07', '2026-09-08', '2026-09-09', '2026-09-10'])->map(fn (string $date) => TrainingSchedule::query()->create(['training_class_id' => $class->id, 'attendance_location_id' => $location->id, 'schedule_date' => $date, 'start_time' => '08:00', 'end_time' => '16:00', 'morning_open' => '06:30', 'morning_on_time_limit' => '08:00', 'morning_close' => '09:00', 'afternoon_open' => '15:30', 'afternoon_early_limit' => '16:00', 'afternoon_close' => '18:00', 'status' => 'active']));
        foreach (['morning', 'afternoon'] as $type) {
            Attendance::query()->create(['user_id' => $user->id, 'training_schedule_id' => $schedules[0]->id, 'attendance_location_id' => $location->id, 'attendance_date' => '2026-09-07', 'type' => $type, 'status' => $type === 'morning' ? 'on_time' : 'present', 'latitude' => -6.1, 'longitude' => 106.1, 'accuracy_meters' => 5, 'distance_meters' => 0, 'recorded_at' => '2026-09-07 08:00:00']);
        }
        Attendance::query()->create(['user_id' => $user->id, 'training_schedule_id' => $schedules[1]->id, 'attendance_location_id' => $location->id, 'attendance_date' => '2026-09-08', 'type' => 'morning', 'status' => 'on_time', 'latitude' => -6.1, 'longitude' => 106.1, 'accuracy_meters' => 5, 'distance_meters' => 0, 'recorded_at' => '2026-09-08 08:00:00']);
        LeaveRequest::query()->create(['user_id' => $user->id, 'type' => 'sick', 'start_date' => '2026-09-09', 'end_date' => '2026-09-09', 'reason' => 'Sakit dan membutuhkan istirahat.', 'status' => 'approved']);

        $summary = app(ParticipantAttendanceSummaryService::class)->summarize($user);
        $this->assertSame(1, $summary['present']);
        $this->assertSame(1, $summary['incomplete']);
        $this->assertSame(1, $summary['sick']);
        $this->assertSame(1, $summary['absent']);
        $this->assertSame(25.0, $summary['attendanceRate']);
    }
}
