<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\TrainingSchedule;
use App\Services\ParticipantAttendanceSummaryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ParticipantAttendanceSummaryService $summaryService): Response
    {
        $user = $request->user()->load('participantProfile', 'enrollments.trainingBatch.trainingProgram', 'enrollments.trainingClass');
        $attendanceQuery = Attendance::query()->whereBelongsTo($user)->whereNull('voided_at');
        $enrollment = $user->enrollments->where('status', 'active')->sortByDesc('id')->first();
        $todaySchedule = $enrollment ? TrainingSchedule::query()->where('training_class_id', $enrollment->training_class_id)->whereDate('schedule_date', today())->where('status', 'active')->first() : null;
        $todayAttendances = $todaySchedule ? (clone $attendanceQuery)->where('training_schedule_id', $todaySchedule->id)->get(['type', 'status', 'recorded_at']) : collect();
        $summary = $summaryService->summarize($user);

        return Inertia::render('dashboard', [
            'participant' => ['name' => $user->name, 'profile' => $user->participantProfile?->only(['participant_number']), 'program' => $enrollment?->trainingBatch?->trainingProgram?->name, 'batch' => $enrollment?->trainingBatch?->name, 'class' => $enrollment?->trainingClass?->name],
            'todaySchedule' => $todaySchedule?->only(['id', 'subject', 'start_time', 'end_time']),
            'todayAttendances' => $todayAttendances,
            'stats' => collect($summary)->except('statuses'),
            'recentAttendances' => (clone $attendanceQuery)->with(['trainingSchedule:id,subject', 'attendanceLocation:id,name'])->latest('recorded_at')->limit(5)->get(),
        ]);
    }
}
