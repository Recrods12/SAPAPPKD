<?php

namespace App\Console\Commands;

use App\Models\AttendanceSummary;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\ParticipantEnrollment;
use App\Models\TrainingSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('attendance:finalize {--date= : Finalisasi sampai tanggal YYYY-MM-DD}')]
#[Description('Finalisasi status alpa dan absensi tidak lengkap setelah jadwal selesai')]
class FinalizeAttendanceSummaries extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cutoff = $this->option('date')
            ? CarbonImmutable::createFromFormat('Y-m-d', (string) $this->option('date'), config('app.timezone'))->endOfDay()
            : CarbonImmutable::now(config('app.timezone'));
        $processed = 0;

        TrainingSchedule::query()
            ->where('status', 'active')
            ->whereDate('schedule_date', '<=', $cutoff->toDateString())
            ->with('attendances:id,user_id,training_schedule_id,type,status,voided_at')
            ->chunkById(100, function ($schedules) use ($cutoff, &$processed): void {
                foreach ($schedules as $schedule) {
                    $closedAt = CarbonImmutable::parse($schedule->schedule_date.' '.$schedule->afternoon_close, config('app.timezone'));
                    if ($closedAt->isAfter($cutoff)) {
                        continue;
                    }
                    $date = CarbonImmutable::parse($schedule->schedule_date)->toDateString();
                    $isHoliday = Holiday::query()->where('is_active', true)->whereDate('holiday_date', $date)->exists();
                    ParticipantEnrollment::query()
                        ->where('training_class_id', $schedule->training_class_id)
                        ->whereIn('status', ['active', 'completed'])
                        ->whereDate('enrolled_at', '<=', $date)
                        ->where(fn ($query) => $query->whereNull('completed_at')->orWhereDate('completed_at', '>=', $date))
                        ->select(['id', 'user_id'])
                        ->chunkById(200, function ($enrollments) use ($schedule, $date, $isHoliday, &$processed): void {
                            foreach ($enrollments as $enrollment) {
                                [$morning, $afternoon, $overall] = $this->statuses($schedule, $enrollment->user_id, $date, $isHoliday);
                                AttendanceSummary::query()->updateOrCreate(
                                    ['user_id' => $enrollment->user_id, 'training_schedule_id' => $schedule->id],
                                    ['attendance_date' => $date, 'morning_status' => $morning, 'afternoon_status' => $afternoon, 'overall_status' => $overall, 'finalized_at' => now()],
                                );
                                $processed++;
                            }
                        });
                }
            });

        Log::info('Finalisasi rekap absensi selesai.', ['processed' => $processed, 'cutoff' => $cutoff->toIso8601String()]);
        $this->info("{$processed} rekap absensi difinalisasi.");

        return self::SUCCESS;
    }

    /** @return array{string, string, string} */
    private function statuses(TrainingSchedule $schedule, int $userId, string $date, bool $isHoliday): array
    {
        if ($isHoliday) {
            return ['holiday', 'holiday', 'holiday'];
        }
        $leaveType = LeaveRequest::query()->where('user_id', $userId)->where('status', 'approved')->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->value('type');
        if (in_array($leaveType, ['leave', 'sick'], true)) {
            return [$leaveType, $leaveType, $leaveType];
        }
        $records = $schedule->attendances->where('user_id', $userId)->whereNull('voided_at');
        $morning = $records->firstWhere('type', 'morning');
        $afternoon = $records->firstWhere('type', 'afternoon');
        $morningStatus = $morning?->status ?? 'absent';
        $afternoonStatus = $afternoon?->status ?? 'absent';
        $overall = ! $morning ? 'absent' : (! $afternoon ? 'incomplete' : ($morning->status === 'late' ? 'late' : 'present'));

        return [$morningStatus, $afternoonStatus, $overall];
    }
}
