<?php

namespace Tests\Feature;

use App\Models\AttendanceLocation;
use App\Models\Holiday;
use App\Models\TrainingBatch;
use App\Models\TrainingClass;
use App\Models\TrainingProgram;
use App\Models\TrainingSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendanceSubmissionTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-10 07:30:00');
        Storage::fake('local');
    }

    public function test_participant_can_submit_morning_attendance_inside_radius(): void
    {
        [$user,$schedule,$location] = $this->scenario();
        $this->actingAs($user)->post(route('attendance.store'), $this->payload($schedule, $location))->assertRedirect(route('attendance.create'));
        $attendance = $user->attendances()->first();
        $this->assertNotNull($attendance);
        $this->assertSame('on_time', $attendance->status);
        $this->assertLessThanOrEqual(20, (float) $attendance->distance_meters);
        Storage::disk('local')->assertExists($attendance->photo->path);
    }

    public function test_outside_radius_and_inaccurate_gps_are_rejected(): void
    {
        [$user,$schedule,$location] = $this->scenario();
        $this->actingAs($user)->post(route('attendance.store'), [...$this->payload($schedule, $location), 'latitude' => -6.2])->assertSessionHasErrors('latitude');
        $this->actingAs($user)->post(route('attendance.store'), [...$this->payload($schedule, $location), 'accuracy_meters' => 100])->assertSessionHasErrors('accuracy_meters');
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_duplicate_attendance_is_rejected_and_orphan_photo_is_removed(): void
    {
        [$user,$schedule,$location] = $this->scenario();
        $this->actingAs($user)->post(route('attendance.store'), $this->payload($schedule, $location));
        $this->actingAs($user)->post(route('attendance.store'), $this->payload($schedule, $location))->assertSessionHasErrors('type');
        $this->assertDatabaseCount('attendances', 1);
        $this->assertCount(1, Storage::disk('local')->allFiles('attendance'));
    }

    public function test_server_time_determines_recorded_time_and_late_status(): void
    {
        [$user, $schedule, $location] = $this->scenario();
        Carbon::setTestNow('2026-09-10 08:15:37');

        $this->actingAs($user)->post(route('attendance.store'), $this->payload($schedule, $location))->assertRedirect();

        $attendance = $user->attendances()->sole();
        $this->assertSame('late', $attendance->status);
        $this->assertSame('2026-09-10 08:15:37', $attendance->recorded_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-10', $attendance->attendance_date);
    }

    public function test_morning_and_afternoon_are_stored_as_separate_records(): void
    {
        [$user, $schedule, $location] = $this->scenario();
        $this->actingAs($user)->post(route('attendance.store'), $this->payload($schedule, $location))->assertRedirect();

        Carbon::setTestNow('2026-09-10 16:15:00');
        $this->actingAs($user)->post(route('attendance.store'), [...$this->payload($schedule, $location), 'type' => 'afternoon'])->assertRedirect();

        $this->assertDatabaseHas('attendances', ['user_id' => $user->id, 'type' => 'morning', 'status' => 'on_time']);
        $this->assertDatabaseHas('attendances', ['user_id' => $user->id, 'type' => 'afternoon', 'status' => 'present']);
        $this->assertSame(2, $user->attendances()->count());
    }

    public function test_closed_window_holiday_and_invalid_photo_are_rejected(): void
    {
        [$user, $schedule, $location] = $this->scenario();
        Carbon::setTestNow('2026-09-10 12:00:00');
        $this->actingAs($user)->post(route('attendance.store'), $this->payload($schedule, $location))->assertSessionHasErrors('type');

        Carbon::setTestNow('2026-09-10 07:30:00');
        Holiday::factory()->create(['holiday_date' => '2026-09-10', 'is_active' => true]);
        $this->actingAs($user)->post(route('attendance.store'), $this->payload($schedule, $location))->assertUnprocessable();
        Holiday::query()->delete();

        $this->actingAs($user)->post(route('attendance.store'), [...$this->payload($schedule, $location), 'photo' => UploadedFile::fake()->create('script.php', 10, 'application/x-php')])->assertSessionHasErrors('photo');
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_inactive_participant_and_participant_without_matching_schedule_cannot_submit(): void
    {
        [$user, $schedule, $location] = $this->scenario();
        $user->update(['account_status' => 'inactive']);
        $this->actingAs($user)->post(route('attendance.store'), $this->payload($schedule, $location))->assertRedirect(route('login'));

        [$otherUser] = $this->scenario();
        $this->actingAs($otherUser)->post(route('attendance.store'), $this->payload($schedule, $location))->assertForbidden();
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_database_unique_constraint_is_the_concurrency_boundary_for_duplicate_requests(): void
    {
        $index = collect(Schema::getIndexes('attendances'))->firstWhere('name', 'attendances_unique_submission');

        $this->assertNotNull($index);
        $this->assertTrue($index['unique']);
        $this->assertSame(['user_id', 'training_schedule_id', 'attendance_date', 'type'], $index['columns']);
    }

    private function scenario(): array
    {
        Role::findOrCreate('participant');
        $program = TrainingProgram::factory()->create();
        $batch = TrainingBatch::query()->create(['training_program_id' => $program->id, 'name' => 'A1', 'start_date' => '2026-09-01', 'end_date' => '2026-12-01', 'quota' => 20, 'status' => 'open']);
        $class = TrainingClass::query()->create(['training_batch_id' => $batch->id, 'name' => 'Kelas A', 'capacity' => 20, 'is_active' => true]);
        $location = AttendanceLocation::query()->create(['name' => 'PPKD', 'address' => 'Jakarta', 'latitude' => -6.1234567, 'longitude' => 106.1234567, 'radius_meters' => 20, 'max_accuracy_meters' => 35, 'is_active' => true]);
        $schedule = TrainingSchedule::query()->create(['training_class_id' => $class->id, 'attendance_location_id' => $location->id, 'schedule_date' => '2026-09-10', 'start_time' => '08:00', 'end_time' => '16:00', 'morning_open' => '06:30', 'morning_on_time_limit' => '08:00', 'morning_close' => '09:00', 'afternoon_open' => '15:30', 'afternoon_early_limit' => '16:00', 'afternoon_close' => '18:00', 'status' => 'active']);
        $user = User::factory()->create(['account_status' => 'active']);
        $user->assignRole('participant');
        $user->enrollments()->create(['training_batch_id' => $batch->id, 'training_class_id' => $class->id, 'status' => 'active', 'enrolled_at' => '2026-09-01']);

        return [$user, $schedule, $location];
    }

    private function payload(TrainingSchedule $schedule, AttendanceLocation $location): array
    {
        return ['training_schedule_id' => $schedule->id, 'type' => 'morning', 'latitude' => (float) $location->latitude, 'longitude' => (float) $location->longitude, 'accuracy_meters' => 10, 'photo' => UploadedFile::fake()->image('selfie.jpg', 640, 640), 'device_token' => 'test-device-token-with-enough-length'];
    }
}
