<?php

namespace Tests\Feature;

use App\Exports\AttendanceWorkbookExport;
use App\Exports\FinalizedAttendanceExport;
use App\Models\AttendanceLocation;
use App\Models\AttendanceSummary;
use App\Models\TrainingBatch;
use App\Models\TrainingClass;
use App\Models\TrainingProgram;
use App\Models\TrainingSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendanceReportTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_complete_excel_report_contains_eight_filtered_sheets(): void
    {
        Carbon::setTestNow('2026-09-04 10:00:00');
        Role::findOrCreate('admin-ppkd');
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole('admin-ppkd');
        [$participant, $schedule] = $this->participantAndSchedule();
        AttendanceSummary::factory()->create(['user_id' => $participant->id, 'training_schedule_id' => $schedule->id, 'attendance_date' => '2026-09-03', 'overall_status' => 'absent']);
        $augustSchedule = $schedule->replicate();
        $augustSchedule->schedule_date = '2026-08-03';
        $augustSchedule->save();
        AttendanceSummary::factory()->create(['user_id' => $participant->id, 'training_schedule_id' => $augustSchedule->id, 'attendance_date' => '2026-08-03', 'overall_status' => 'present']);
        Excel::fake();

        $this->actingAs($admin)->get(route('admin.reports.excel', ['month' => 9, 'year' => 2026]))->assertOk();

        Excel::assertDownloaded('laporan-lengkap-sapa-ppkd-20260904-100000.xlsx', function (AttendanceWorkbookExport $export): bool {
            $sheets = $export->sheets();

            return count($sheets) === 8
                && $sheets[1] instanceof FinalizedAttendanceExport
                && $sheets[1]->query()->count() === 1;
        });
    }

    public function test_invalid_report_status_is_rejected(): void
    {
        Role::findOrCreate('admin-ppkd');
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole('admin-ppkd');

        $this->actingAs($admin)->get(route('admin.reports.index', ['final_status' => 'forged']))->assertSessionHasErrors('final_status');
    }

    public function test_pdf_report_is_generated_with_the_active_period(): void
    {
        Carbon::setTestNow('2026-09-04 10:00:00');
        Role::findOrCreate('admin-ppkd');
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole('admin-ppkd');

        $this->actingAs($admin)->get(route('admin.reports.pdf', ['start_date' => '2026-09-01', 'end_date' => '2026-09-30']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('laporan-lengkap-sapa-ppkd-20260904-100000.pdf');
    }

    /** @return array{User, TrainingSchedule} */
    private function participantAndSchedule(): array
    {
        $program = TrainingProgram::factory()->create();
        $batch = TrainingBatch::query()->create(['training_program_id' => $program->id, 'name' => 'A1', 'start_date' => '2026-08-01', 'end_date' => '2026-12-01', 'quota' => 20, 'status' => 'open']);
        $class = TrainingClass::query()->create(['training_batch_id' => $batch->id, 'name' => 'Kelas A', 'capacity' => 20, 'is_active' => true]);
        $location = AttendanceLocation::query()->create(['name' => 'PPKD', 'address' => 'Jakarta', 'latitude' => -6.1, 'longitude' => 106.1, 'radius_meters' => 20, 'max_accuracy_meters' => 35, 'is_active' => true]);
        $schedule = TrainingSchedule::query()->create(['training_class_id' => $class->id, 'attendance_location_id' => $location->id, 'schedule_date' => '2026-09-03', 'start_time' => '08:00', 'end_time' => '16:00', 'morning_open' => '06:30', 'morning_on_time_limit' => '08:00', 'morning_close' => '09:00', 'afternoon_open' => '15:30', 'afternoon_early_limit' => '16:00', 'afternoon_close' => '18:00', 'status' => 'active']);
        $participant = User::factory()->create(['account_status' => 'active']);

        return [$participant, $schedule];
    }
}
