<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\TrainingSchedule;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['date' => ['nullable', 'date'], 'class_id' => ['nullable', 'integer'], 'search' => ['nullable', 'string', 'max:100']]);
        $date = $filters['date'] ?? today()->toDateString();
        $classes = $request->user()->instructedClasses()->with('trainingBatch.trainingProgram')->orderBy('name')->get();
        $selectedClass = $classes->firstWhere('id', (int) ($filters['class_id'] ?? $classes->first()?->id));
        abort_if(($filters['class_id'] ?? null) && ! $selectedClass, 403);
        $schedule = $selectedClass ? TrainingSchedule::query()->where('training_class_id', $selectedClass->id)->whereDate('schedule_date', $date)->first() : null;
        $participants = collect();

        if ($selectedClass) {
            $attendances = $schedule ? Attendance::query()->where('training_schedule_id', $schedule->id)->whereDate('attendance_date', $date)->whereNull('voided_at')->with(['attendanceLocation:id,name', 'photo:id,attendance_id'])->get()->groupBy('user_id') : collect();
            $participantIds = $selectedClass->enrollments()->where('status', 'active')->pluck('user_id');
            $leaves = LeaveRequest::query()->whereIn('user_id', $participantIds)->where('status', 'approved')->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->get()->keyBy('user_id');
            $participants = $selectedClass->enrollments()->where('status', 'active')->with(['user:id,name,email', 'user.participantProfile:id,user_id,participant_number'])->when($filters['search'] ?? null, fn ($query, $search) => $query->whereHas('user', fn ($user) => $user->where('name', 'like', '%'.$search.'%')))->get()->map(function ($enrollment) use ($attendances, $leaves): array {
                $records = $attendances->get($enrollment->user_id, collect())->keyBy('type');

                return ['id' => $enrollment->user->id, 'name' => $enrollment->user->name, 'participant_number' => $enrollment->user->participantProfile?->participant_number, 'morning' => $records->get('morning'), 'afternoon' => $records->get('afternoon'), 'leave' => $leaves->get($enrollment->user_id)?->only(['type', 'reason'])];
            });
        }

        return Inertia::render('instructor/attendances/index', ['classes' => $classes, 'selectedClass' => $selectedClass, 'schedule' => $schedule, 'participants' => $participants, 'filters' => ['date' => $date, 'class_id' => $selectedClass?->id, 'search' => $filters['search'] ?? '']]);
    }

    public function show(Request $request, Attendance $attendance): Response
    {
        $attendance->loadMissing('trainingSchedule');
        abort_unless($request->user()->instructedClasses()->whereKey($attendance->trainingSchedule->training_class_id)->exists(), 403);

        return Inertia::render('instructor/attendances/show', ['attendance' => $attendance->load(['user.participantProfile', 'trainingSchedule.trainingClass.trainingBatch.trainingProgram', 'attendanceLocation', 'photo', 'fraudFlags', 'instructorCorrectionRequests.instructor:id,name'])]);
    }
}
