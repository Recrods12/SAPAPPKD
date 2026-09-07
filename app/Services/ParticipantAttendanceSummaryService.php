<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\TrainingSchedule;
use App\Models\User;
use Illuminate\Support\Collection;

class ParticipantAttendanceSummaryService
{
    /** @return array{present:int,late:int,leave:int,sick:int,absent:int,incomplete:int,holiday:int,attendanceRate:float,statuses:Collection<int, array<string, mixed>>} */
    public function summarize(User $user, ?string $startDate = null, ?string $endDate = null): array
    {
        $enrollment = $user->enrollments()->where('status', 'active')->latest()->first();
        if (! $enrollment?->training_class_id) {
            return ['present' => 0, 'late' => 0, 'leave' => 0, 'sick' => 0, 'absent' => 0, 'incomplete' => 0, 'holiday' => 0, 'attendanceRate' => 0, 'statuses' => collect()];
        }
        $rangeStart = $startDate && $startDate > $enrollment->enrolled_at->toDateString() ? $startDate : $enrollment->enrolled_at->toDateString();
        $rangeEnd = $endDate && $endDate < today()->toDateString() ? $endDate : today()->toDateString();
        $schedules = TrainingSchedule::query()->where('training_class_id', $enrollment->training_class_id)->where('status', 'active')->whereDate('schedule_date', '>=', $rangeStart)->whereDate('schedule_date', '<=', $rangeEnd)->orderBy('schedule_date')->get();
        $scheduleIds = $schedules->pluck('id');
        $attendances = Attendance::query()->whereBelongsTo($user)->whereIn('training_schedule_id', $scheduleIds)->whereNull('voided_at')->get()->groupBy('training_schedule_id');
        $holidays = Holiday::query()->where('is_active', true)->whereBetween('holiday_date', [$rangeStart, $rangeEnd])->pluck('holiday_date')->map(fn ($date) => (string) $date)->all();
        $leaves = LeaveRequest::query()->whereBelongsTo($user)->where('status', 'approved')->whereDate('start_date', '<=', $rangeEnd)->whereDate('end_date', '>=', $rangeStart)->get();
        $statuses = $schedules->map(function (TrainingSchedule $schedule) use ($attendances, $holidays, $leaves): array {
            $date = (string) $schedule->schedule_date;
            $records = $attendances->get($schedule->id, collect());
            $leave = $leaves->first(fn (LeaveRequest $request) => $request->start_date->toDateString() <= $date && $request->end_date->toDateString() >= $date);
            $morning = $records->firstWhere('type', 'morning');
            $afternoon = $records->firstWhere('type', 'afternoon');
            $status = in_array($date, $holidays, true) ? 'holiday' : ($leave?->type ?? ($morning ? ($afternoon ? ($morning->status === 'late' ? 'late' : 'present') : 'incomplete') : 'absent'));

            return ['schedule_id' => $schedule->id, 'date' => $date, 'subject' => $schedule->subject, 'status' => $status];
        });
        $effectiveSchedules = max(0, $statuses->where('status', '!=', 'holiday')->count());
        $attended = $statuses->whereIn('status', ['present', 'late'])->count();

        return ['present' => $attended, 'late' => $statuses->where('status', 'late')->count(), 'leave' => $statuses->where('status', 'leave')->count(), 'sick' => $statuses->where('status', 'sick')->count(), 'absent' => $statuses->where('status', 'absent')->count(), 'incomplete' => $statuses->where('status', 'incomplete')->count(), 'holiday' => $statuses->where('status', 'holiday')->count(), 'attendanceRate' => $effectiveSchedules > 0 ? round(($attended / $effectiveSchedules) * 100, 1) : 0, 'statuses' => $statuses];
    }
}
