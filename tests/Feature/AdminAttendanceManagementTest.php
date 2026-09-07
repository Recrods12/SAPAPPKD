<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceLocation;
use App\Models\TrainingBatch;
use App\Models\TrainingClass;
use App\Models\TrainingProgram;
use App\Models\TrainingSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAttendanceManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_correction_preserves_original_and_writes_audit_trail(): void
    {
        [$admin, $attendance] = $this->scenario();

        $this->actingAs($admin)->patch(route('admin.attendances.update', $attendance), ['status' => 'late', 'reason' => 'Waktu kedatangan diverifikasi ulang oleh petugas.'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('attendances', ['id' => $attendance->id, 'status' => 'late']);
        $this->assertDatabaseHas('attendance_corrections', ['attendance_id' => $attendance->id, 'admin_id' => $admin->id]);
        $this->assertDatabaseHas('activity_logs', ['event' => 'attendance.corrected', 'subject_id' => $attendance->id]);
    }

    public function test_reports_can_be_downloaded_as_excel_and_pdf(): void
    {
        [$admin] = $this->scenario();

        $this->actingAs($admin)->get(route('admin.reports.excel'))->assertOk()->assertHeader('content-disposition');
        $this->actingAs($admin)->get(route('admin.reports.pdf'))->assertOk()->assertHeader('content-disposition');
    }

    private function scenario(): array
    {
        Role::findOrCreate('admin-ppkd');
        Role::findOrCreate('participant');
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole('admin-ppkd');
        $participant = User::factory()->create(['account_status' => 'active']);
        $participant->assignRole('participant');
        $participant->participantProfile()->create(['nik' => '3174000000000001', 'participant_number' => 'P-001', 'gender' => 'male', 'birth_place' => 'Jakarta', 'birth_date' => '2000-01-01', 'address' => 'Jakarta', 'phone' => '081234567890', 'participant_status' => 'active', 'privacy_accepted_at' => now()]);
        $program = TrainingProgram::factory()->create();
        $batch = TrainingBatch::query()->create(['training_program_id' => $program->id, 'name' => 'Angkatan 1', 'start_date' => today(), 'end_date' => today()->addMonth(), 'quota' => 20, 'status' => 'open']);
        $class = TrainingClass::query()->create(['training_batch_id' => $batch->id, 'name' => 'Kelas A', 'capacity' => 20, 'is_active' => true]);
        $location = AttendanceLocation::query()->create(['name' => 'PPKD', 'address' => 'Jakarta', 'latitude' => -6.1, 'longitude' => 106.1, 'radius_meters' => 20, 'max_accuracy_meters' => 35, 'is_active' => true]);
        $schedule = TrainingSchedule::query()->create(['training_class_id' => $class->id, 'attendance_location_id' => $location->id, 'schedule_date' => today(), 'start_time' => '08:00', 'end_time' => '16:00', 'morning_open' => '06:30', 'morning_on_time_limit' => '08:00', 'morning_close' => '09:00', 'afternoon_open' => '15:30', 'afternoon_early_limit' => '16:00', 'afternoon_close' => '18:00', 'status' => 'active']);
        $attendance = Attendance::query()->create(['user_id' => $participant->id, 'training_schedule_id' => $schedule->id, 'attendance_location_id' => $location->id, 'attendance_date' => today(), 'type' => 'morning', 'status' => 'on_time', 'latitude' => -6.1, 'longitude' => 106.1, 'accuracy_meters' => 5, 'distance_meters' => 0, 'recorded_at' => now()]);

        return [$admin, $attendance];
    }
}
