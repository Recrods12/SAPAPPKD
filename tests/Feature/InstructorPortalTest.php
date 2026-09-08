<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\InstructorAttendanceCorrection;
use App\Models\InstructorProfile;
use App\Models\LeaveRequest;
use App\Models\ParticipantEnrollment;
use App\Models\TrainingClass;
use App\Models\TrainingSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InstructorPortalTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_instructor_dashboard_monitoring_leave_and_reports_only_include_assigned_classes(): void
    {
        [$instructor, $assignedClass, $otherClass, $participant] = $this->portalRecords();
        $assignedSchedule = TrainingSchedule::factory()->create(['training_class_id' => $assignedClass->id, 'schedule_date' => today()]);
        $otherSchedule = TrainingSchedule::factory()->create(['training_class_id' => $otherClass->id, 'schedule_date' => today()]);
        Attendance::factory()->create(['user_id' => $participant->id, 'training_schedule_id' => $assignedSchedule->id]);
        $otherAttendance = Attendance::factory()->create(['training_schedule_id' => $otherSchedule->id]);
        LeaveRequest::factory()->create(['user_id' => $participant->id]);

        $this->actingAs($instructor)->get(route('instructor.dashboard'))->assertInertia(fn (Assert $page) => $page->component('instructor/dashboard')->has('todaySchedules', 1));
        $this->actingAs($instructor)->get(route('instructor.attendances.index', ['class_id' => $assignedClass->id]))->assertInertia(fn (Assert $page) => $page->component('instructor/attendances/index')->has('participants', 1)->where('participants.0.name', $participant->name));
        $this->actingAs($instructor)->get(route('instructor.leave-requests.index'))->assertInertia(fn (Assert $page) => $page->component('instructor/leave-requests/index')->has('leaveRequests.data', 1));
        $this->actingAs($instructor)->get(route('instructor.reports.index'))->assertInertia(fn (Assert $page) => $page->component('instructor/reports/index')->has('rows.data', 1));
        $this->actingAs($instructor)->get(route('instructor.attendances.index', ['class_id' => $otherClass->id]))->assertForbidden();
        $this->actingAs($instructor)->get(route('instructor.attendances.show', $otherAttendance))->assertForbidden();
        $this->actingAs($instructor)->get(route('instructor.reports.index', ['class_id' => $otherClass->id]))->assertForbidden();
    }

    public function test_instructor_can_request_correction_only_for_an_assigned_attendance(): void
    {
        [$instructor, $assignedClass, $otherClass, $participant] = $this->portalRecords();
        $assigned = Attendance::factory()->create(['user_id' => $participant->id, 'training_schedule_id' => TrainingSchedule::factory()->create(['training_class_id' => $assignedClass->id])->id]);
        $other = Attendance::factory()->create(['training_schedule_id' => TrainingSchedule::factory()->create(['training_class_id' => $otherClass->id])->id]);

        $this->actingAs($instructor)->post(route('instructor.attendance-corrections.store', $assigned), ['requested_status' => 'late', 'reason' => 'Waktu pada catatan perlu diperiksa ulang.'])->assertRedirect();

        $this->assertDatabaseHas(InstructorAttendanceCorrection::class, ['attendance_id' => $assigned->id, 'instructor_id' => $instructor->id, 'requested_status' => 'late', 'status' => 'pending']);
        $this->assertTrue($assigned->fresh()->needs_review);
        $this->actingAs($instructor)->post(route('instructor.attendance-corrections.store', $other), ['requested_status' => 'late', 'reason' => 'Tidak boleh mengubah kelas milik instruktur lain.'])->assertForbidden();
    }

    public function test_instructor_can_update_profile_and_download_assigned_reports(): void
    {
        [$instructor, $assignedClass, , $participant] = $this->portalRecords();
        InstructorProfile::factory()->create(['user_id' => $instructor->id]);
        Attendance::factory()->create(['user_id' => $participant->id, 'training_schedule_id' => TrainingSchedule::factory()->create(['training_class_id' => $assignedClass->id])->id]);

        $this->actingAs($instructor)->put(route('instructor.profile.update'), ['name' => 'Instruktur Baru', 'email' => 'instruktur-baru@example.test', 'phone' => '081234567890', 'address' => 'Jakarta Barat'])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $instructor->id, 'name' => 'Instruktur Baru']);
        $this->assertDatabaseHas('instructor_profiles', ['user_id' => $instructor->id, 'phone' => '081234567890']);
        $this->actingAs($instructor)->get(route('instructor.reports.excel'))->assertOk()->assertDownload();
        $this->actingAs($instructor)->get(route('instructor.reports.pdf'))->assertOk()->assertDownload();
    }

    private function portalRecords(): array
    {
        Role::findOrCreate('instructor');
        $instructor = User::factory()->create(['account_status' => 'active']);
        $instructor->assignRole('instructor');
        $assignedClass = TrainingClass::factory()->create();
        $otherClass = TrainingClass::factory()->create();
        $assignedClass->instructors()->attach($instructor);
        $participant = User::factory()->create(['account_status' => 'active']);
        ParticipantEnrollment::factory()->create(['user_id' => $participant->id, 'training_batch_id' => $assignedClass->training_batch_id, 'training_class_id' => $assignedClass->id]);

        return [$instructor, $assignedClass, $otherClass, $participant];
    }
}
