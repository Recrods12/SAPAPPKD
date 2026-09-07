<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendanceRequest;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\AttendancePhoto;
use App\Models\DeviceToken;
use App\Models\FraudFlag;
use App\Models\Holiday;
use App\Models\TrainingSchedule;
use App\Notifications\AppNotification;
use App\Services\AttendancePhotoService;
use App\Services\AttendanceStatusService;
use App\Services\GeolocationService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $attendances = Attendance::query()->whereBelongsTo($request->user())->with(['trainingSchedule:id,schedule_date,subject', 'attendanceLocation:id,name', 'photo:id,attendance_id'])->latest('recorded_at')->paginate(15);

        return Inertia::render('attendance/history', compact('attendances'));
    }

    public function create(Request $request): Response
    {
        $today = now()->toDateString();
        $enrollment = $request->user()->enrollments()->with(['trainingBatch.trainingProgram:id,name', 'trainingClass:id,name'])->where('status', 'active')->whereNotNull('training_class_id')->latest()->first();
        $schedule = $enrollment ? TrainingSchedule::query()->with('attendanceLocation')->where('training_class_id', $enrollment->training_class_id)->where('schedule_date', $today)->where('status', 'active')->first() : null;
        $attendances = $schedule ? Attendance::query()->whereBelongsTo($request->user())->where('training_schedule_id', $schedule->id)->whereNull('voided_at')->get(['id', 'type', 'status', 'recorded_at', 'distance_meters']) : collect();

        return Inertia::render('attendance/create', ['serverTime' => now()->toIso8601String(), 'participant' => ['name' => $request->user()->name, 'number' => $request->user()->participantProfile?->participant_number, 'program' => $enrollment?->trainingBatch?->trainingProgram?->name, 'batch' => $enrollment?->trainingBatch?->name, 'class' => $enrollment?->trainingClass?->name], 'schedule' => $schedule, 'attendances' => $attendances]);
    }

    public function store(StoreAttendanceRequest $request, GeolocationService $geolocation, AttendancePhotoService $photos, AttendanceStatusService $attendanceStatus): RedirectResponse
    {
        $now = now();
        abort_if(Holiday::query()->where('holiday_date', $now->toDateString())->where('is_active', true)->exists(), 422, 'Hari ini merupakan hari libur.');
        $schedule = TrainingSchedule::query()->with('attendanceLocation')->findOrFail($request->integer('training_schedule_id'));
        $enrolled = $request->user()->enrollments()->where('status', 'active')->where('training_class_id', $schedule->training_class_id)->exists();
        abort_unless($enrolled && $schedule->status === 'active' && $schedule->schedule_date === $now->toDateString(), 403);
        $type = $request->validated('type');
        [$open, $close] = $type === 'morning' ? [$schedule->morning_open, $schedule->morning_close] : [$schedule->afternoon_open, $schedule->afternoon_close];
        $time = $now->format('H:i:s');
        if ($time < $open || $time > $close) {
            throw ValidationException::withMessages(['type' => 'Waktu absensi belum dibuka atau sudah ditutup.']);
        }
        $location = $schedule->attendanceLocation;
        abort_if(! $location?->is_active || $location->latitude === null || $location->longitude === null, 422, 'Lokasi absensi belum siap.');
        $accuracy = (float) $request->validated('accuracy_meters');
        if ($accuracy > $location->max_accuracy_meters) {
            FraudFlag::query()->create(['user_id' => $request->user()->id, 'reason' => 'poor_gps_accuracy', 'severity' => 'warning', 'metadata' => ['accuracy_meters' => $accuracy, 'maximum_meters' => $location->max_accuracy_meters], 'status' => 'open']);
            throw ValidationException::withMessages(['accuracy_meters' => "Akurasi GPS harus maksimal {$location->max_accuracy_meters} meter."]);
        }
        $distance = $geolocation->distanceInMeters((float) $request->validated('latitude'), (float) $request->validated('longitude'), (float) $location->latitude, (float) $location->longitude);
        if ($distance > $location->radius_meters) {
            if ($distance > max(1000, $location->radius_meters * 10)) {
                FraudFlag::query()->create(['user_id' => $request->user()->id, 'reason' => 'abnormal_location', 'severity' => 'warning', 'metadata' => ['distance_meters' => $distance, 'allowed_radius_meters' => $location->radius_meters], 'status' => 'open']);
            }
            throw ValidationException::withMessages(['latitude' => "Anda berada {$distance} meter dari lokasi. Radius maksimal {$location->radius_meters} meter."]);
        }
        $status = $attendanceStatus->determine($schedule, $type, $now);
        $tokenHash = hash('sha256', $request->validated('device_token'));
        $deviceUsedByAnotherAccount = DeviceToken::query()->where('token_hash', $tokenHash)->where('user_id', '!=', $request->user()->id)->exists();
        $device = DeviceToken::query()->updateOrCreate(['user_id' => $request->user()->id, 'token_hash' => $tokenHash], ['label' => 'Browser absensi', 'last_used_at' => $now, 'is_active' => true]);
        $previousAttendance = Attendance::query()->whereBelongsTo($request->user())->whereNull('voided_at')->latest('recorded_at')->first();
        $movementDistance = $previousAttendance ? $geolocation->distanceInMeters((float) $previousAttendance->latitude, (float) $previousAttendance->longitude, (float) $request->validated('latitude'), (float) $request->validated('longitude')) : 0;
        $impossibleMovement = $previousAttendance && $previousAttendance->recorded_at->diffInMinutes($now) <= 30 && $movementDistance >= 20000;
        $photoData = null;
        try {
            $participantNumber = $request->user()->participantProfile?->participant_number ?? '-';
            $photoData = $photos->store($request->file('photo'), "SAPA PPKD | {$request->user()->name} ({$participantNumber}) | {$type} | {$location->name} | {$now->format('d-m-Y H:i:s')} WIB | {$distance} m");
            $identicalPhoto = AttendancePhoto::query()->where('source_sha256', $photoData['source_sha256'])->exists();
            DB::transaction(function () use ($request, $schedule, $location, $type, $status, $distance, $now, $photoData, $device, $deviceUsedByAnotherAccount, $identicalPhoto, $impossibleMovement, $movementDistance): void {
                $attendance = Attendance::query()->create(['user_id' => $request->user()->id, 'training_schedule_id' => $schedule->id, 'attendance_location_id' => $location->id, 'attendance_date' => $now->toDateString(), 'type' => $type, 'status' => $status, 'latitude' => $request->validated('latitude'), 'longitude' => $request->validated('longitude'), 'accuracy_meters' => $request->validated('accuracy_meters'), 'distance_meters' => $distance, 'recorded_at' => $now, 'ip_address' => $request->ip(), 'user_agent' => mb_strimwidth((string) $request->userAgent(), 0, 1000), 'device_token_id' => $device->id, 'needs_review' => $deviceUsedByAnotherAccount || $identicalPhoto || $impossibleMovement]);
                $attendance->photo()->create($photoData);
                if ($deviceUsedByAnotherAccount) {
                    FraudFlag::query()->create(['attendance_id' => $attendance->id, 'user_id' => $request->user()->id, 'reason' => 'shared_device', 'severity' => 'warning', 'metadata' => ['device_token_id' => $device->id], 'status' => 'open']);
                }
                if ($identicalPhoto) {
                    FraudFlag::query()->create(['attendance_id' => $attendance->id, 'user_id' => $request->user()->id, 'reason' => 'identical_photo', 'severity' => 'warning', 'metadata' => ['source_sha256' => $photoData['source_sha256']], 'status' => 'open']);
                }
                if ($impossibleMovement) {
                    FraudFlag::query()->create(['attendance_id' => $attendance->id, 'user_id' => $request->user()->id, 'reason' => 'impossible_movement', 'severity' => 'critical', 'metadata' => ['distance_meters' => $movementDistance, 'window_minutes' => 30], 'status' => 'open']);
                }
                ActivityLog::query()->create(['user_id' => $request->user()->id, 'event' => 'attendance.created', 'subject_type' => Attendance::class, 'subject_id' => $attendance->id, 'properties' => ['type' => $type, 'status' => $status, 'distance_meters' => $distance], 'ip_address' => $request->ip(), 'user_agent' => mb_strimwidth((string) $request->userAgent(), 0, 1000)]);
            });
        } catch (UniqueConstraintViolationException) {
            if ($photoData) {
                Storage::disk($photoData['disk'])->delete($photoData['path']);
            }
            $existingAttendance = Attendance::query()->whereBelongsTo($request->user())->where('training_schedule_id', $schedule->id)->where('attendance_date', $now->toDateString())->where('type', $type)->first();
            FraudFlag::query()->create(['attendance_id' => $existingAttendance?->id, 'user_id' => $request->user()->id, 'reason' => 'repeated_request', 'severity' => 'info', 'metadata' => ['schedule_id' => $schedule->id, 'type' => $type], 'status' => 'open']);
            throw ValidationException::withMessages(['type' => 'Absensi untuk sesi ini sudah tercatat.']);
        } catch (Throwable $exception) {
            if ($photoData) {
                Storage::disk($photoData['disk'])->delete($photoData['path']);
            }
            throw $exception;
        }

        $request->user()->notify(new AppNotification($deviceUsedByAnotherAccount || $identicalPhoto || $impossibleMovement ? 'Absensi perlu ditinjau' : 'Absensi berhasil', "Absensi {$type} tercatat pukul {$now->format('H:i:s')} WIB dengan jarak {$distance} meter.", route('attendance.history')));

        return redirect()->route('attendance.create')->with('status', 'Absensi berhasil dicatat menggunakan waktu server.');
    }
}
