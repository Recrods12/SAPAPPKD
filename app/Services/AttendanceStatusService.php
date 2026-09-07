<?php

namespace App\Services;

use App\Models\TrainingSchedule;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class AttendanceStatusService
{
    public function determine(TrainingSchedule $schedule, string $type, CarbonInterface $serverTime): string
    {
        $time = $serverTime->format('H:i:s');

        return match ($type) {
            'morning' => $time <= $schedule->morning_on_time_limit ? 'on_time' : 'late',
            'afternoon' => $time < $schedule->afternoon_early_limit ? 'early_leave' : 'present',
            default => throw new InvalidArgumentException('Jenis absensi tidak didukung.'),
        };
    }
}
