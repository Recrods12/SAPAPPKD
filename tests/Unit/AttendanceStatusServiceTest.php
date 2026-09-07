<?php

namespace Tests\Unit;

use App\Models\TrainingSchedule;
use App\Services\AttendanceStatusService;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Tests\TestCase;

class AttendanceStatusServiceTest extends TestCase
{
    public function test_morning_status_uses_server_time_and_on_time_limit(): void
    {
        $schedule = $this->schedule();
        $service = app(AttendanceStatusService::class);

        $this->assertSame('on_time', $service->determine($schedule, 'morning', CarbonImmutable::parse('2026-09-10 08:00:00')));
        $this->assertSame('late', $service->determine($schedule, 'morning', CarbonImmutable::parse('2026-09-10 08:00:01')));
    }

    public function test_afternoon_status_uses_early_leave_limit(): void
    {
        $schedule = $this->schedule();
        $service = app(AttendanceStatusService::class);

        $this->assertSame('early_leave', $service->determine($schedule, 'afternoon', CarbonImmutable::parse('2026-09-10 15:59:59')));
        $this->assertSame('present', $service->determine($schedule, 'afternoon', CarbonImmutable::parse('2026-09-10 16:00:00')));
    }

    public function test_unsupported_attendance_type_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(AttendanceStatusService::class)->determine($this->schedule(), 'unknown', CarbonImmutable::now());
    }

    private function schedule(): TrainingSchedule
    {
        return new TrainingSchedule([
            'morning_on_time_limit' => '08:00:00',
            'afternoon_early_limit' => '16:00:00',
        ]);
    }
}
