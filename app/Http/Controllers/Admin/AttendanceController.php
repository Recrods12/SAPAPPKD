<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CorrectAttendanceRequest;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\FraudFlag;
use App\Models\TrainingBatch;
use App\Models\TrainingClass;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Attendance::query()->with(['user:id,name', 'user.participantProfile:id,user_id,participant_number', 'trainingSchedule:id,training_class_id,subject', 'trainingSchedule.trainingClass:id,name', 'attendanceLocation:id,name', 'photo:id,attendance_id'])->withCount('corrections')->latest('recorded_at');
        $query->when($request->filled('start_date'), fn ($builder) => $builder->whereDate('attendance_date', '>=', $request->date('start_date')))->when($request->filled('end_date'), fn ($builder) => $builder->whereDate('attendance_date', '<=', $request->date('end_date')))->when($request->filled('time_from'), fn ($builder) => $builder->whereTime('recorded_at', '>=', $request->string('time_from')))->when($request->filled('time_to'), fn ($builder) => $builder->whereTime('recorded_at', '<=', $request->string('time_to')))->when($request->filled('class_id'), fn ($builder) => $builder->whereHas('trainingSchedule', fn ($schedule) => $schedule->where('training_class_id', $request->integer('class_id'))))->when($request->filled('batch_id'), fn ($builder) => $builder->whereHas('trainingSchedule.trainingClass', fn ($class) => $class->where('training_batch_id', $request->integer('batch_id'))))->when($request->filled('program_id'), fn ($builder) => $builder->whereHas('trainingSchedule.trainingClass.trainingBatch', fn ($batch) => $batch->where('training_program_id', $request->integer('program_id'))))->when($request->filled('instructor_id'), fn ($builder) => $builder->whereHas('trainingSchedule.trainingClass.instructors', fn ($instructor) => $instructor->whereKey($request->integer('instructor_id'))))->when($request->filled('status'), fn ($builder) => $builder->where('status', $request->string('status')))->when($request->filled('type'), fn ($builder) => $builder->where('type', $request->string('type')))->when($request->filled('search'), fn ($builder) => $builder->whereHas('user', fn ($user) => $user->where('name', 'like', '%'.$request->string('search').'%')));

        return Inertia::render('admin/attendances/index', ['attendances' => $query->paginate(20)->withQueryString(), 'programs' => TrainingProgram::query()->orderBy('name')->get(['id', 'name']), 'batches' => TrainingBatch::query()->orderBy('name')->get(['id', 'name']), 'classes' => TrainingClass::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']), 'instructors' => User::role('instructor')->orderBy('name')->get(['id', 'name']), 'filters' => $request->only(['start_date', 'end_date', 'time_from', 'time_to', 'program_id', 'batch_id', 'class_id', 'instructor_id', 'status', 'type', 'search'])]);
    }

    public function show(Attendance $attendance): Response
    {
        return Inertia::render('admin/attendances/show', ['attendance' => $attendance->load(['user.participantProfile', 'trainingSchedule.trainingClass.trainingBatch.trainingProgram', 'attendanceLocation', 'photo', 'corrections.admin:id,name', 'fraudFlags', 'instructorCorrectionRequests.instructor:id,name'])]);
    }

    public function update(CorrectAttendanceRequest $request, Attendance $attendance): RedirectResponse
    {
        DB::transaction(function () use ($request, $attendance): void {
            $attendance = Attendance::query()->lockForUpdate()->findOrFail($attendance->id);
            $before = $attendance->only(['status', 'voided_at', 'needs_review']);
            $attendance->update(['status' => $request->validated('status') === 'void' ? $attendance->status : $request->validated('status'), 'voided_at' => $request->validated('status') === 'void' ? now() : null, 'needs_review' => false]);
            $after = $attendance->fresh()->only(['status', 'voided_at', 'needs_review']);
            $attendance->instructorCorrectionRequests()->where('status', 'pending')->get()->each(function ($correctionRequest) use ($request): void {
                $correctionRequest->update(['status' => $correctionRequest->requested_status === $request->validated('status') ? 'approved' : 'rejected', 'admin_notes' => $request->validated('reason'), 'processed_by' => $request->user()->id, 'processed_at' => now()]);
            });
            AttendanceCorrection::query()->create(['attendance_id' => $attendance->id, 'admin_id' => $request->user()->id, 'before_data' => $before, 'after_data' => $after, 'reason' => $request->validated('reason')]);
            FraudFlag::query()->create(['attendance_id' => $attendance->id, 'user_id' => $attendance->user_id, 'reason' => 'admin_correction', 'severity' => 'info', 'metadata' => ['reason' => $request->validated('reason')], 'status' => 'reviewed', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_notes' => $request->validated('reason')]);
            ActivityLog::query()->create(['user_id' => $request->user()->id, 'event' => 'attendance.corrected', 'subject_type' => Attendance::class, 'subject_id' => $attendance->id, 'properties' => ['before' => $before, 'after' => $after, 'reason' => $request->validated('reason')], 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);
        });

        return back()->with('status', 'Koreksi absensi berhasil disimpan dan dicatat dalam audit log.');
    }
}
