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
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendancePhotoAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_photo_is_private_to_owner_and_authorized_admin(): void
    {
        Storage::fake('local');
        Role::findOrCreate('participant');
        Role::findOrCreate('super-admin');
        [$owner, $photo] = $this->attendanceWithPhoto();
        $stranger = User::factory()->create(['account_status' => 'active']);
        $stranger->assignRole('participant');
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole('super-admin');

        $this->actingAs($owner)->get(route('attendance-photos.show', $photo->id))->assertOk();
        $this->actingAs($stranger)->get(route('attendance-photos.show', $photo->id))->assertForbidden();
        $this->actingAs($admin)->get(route('attendance-photos.show', $photo->id))->assertOk();
    }

    private function attendanceWithPhoto(): array
    {
        $program = TrainingProgram::factory()->create();
        $batch = TrainingBatch::query()->create(['training_program_id' => $program->id, 'name' => 'A1', 'start_date' => '2026-09-01', 'end_date' => '2026-12-01', 'quota' => 20, 'status' => 'open']);
        $class = TrainingClass::query()->create(['training_batch_id' => $batch->id, 'name' => 'Kelas A', 'capacity' => 20, 'is_active' => true]);
        $location = AttendanceLocation::query()->create(['name' => 'PPKD', 'address' => 'Jakarta', 'latitude' => -6.1, 'longitude' => 106.1, 'radius_meters' => 20, 'max_accuracy_meters' => 35, 'is_active' => true]);
        $schedule = TrainingSchedule::query()->create(['training_class_id' => $class->id, 'attendance_location_id' => $location->id, 'schedule_date' => '2026-09-10', 'start_time' => '08:00', 'end_time' => '16:00', 'morning_open' => '06:30', 'morning_on_time_limit' => '08:00', 'morning_close' => '09:00', 'afternoon_open' => '15:30', 'afternoon_early_limit' => '16:00', 'afternoon_close' => '18:00', 'status' => 'active']);
        $owner = User::factory()->create(['account_status' => 'active']);
        $owner->assignRole('participant');
        $attendance = Attendance::query()->create(['user_id' => $owner->id, 'training_schedule_id' => $schedule->id, 'attendance_location_id' => $location->id, 'attendance_date' => '2026-09-10', 'type' => 'morning', 'status' => 'on_time', 'latitude' => -6.1, 'longitude' => 106.1, 'accuracy_meters' => 5, 'distance_meters' => 0, 'recorded_at' => '2026-09-10 07:30:00']);
        Storage::disk('local')->put('attendance/private.jpg', 'private-photo');
        $photo = $attendance->photo()->create(['disk' => 'local', 'path' => 'attendance/private.jpg', 'size_bytes' => 13, 'mime_type' => 'image/jpeg', 'width' => 1, 'height' => 1, 'sha256' => hash('sha256', 'private-photo'), 'uploaded_at' => now()]);

        return [$owner, $photo];
    }
}
