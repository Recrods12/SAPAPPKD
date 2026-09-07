<?php

namespace Database\Factories;

use App\Models\AttendanceSummary;
use App\Models\TrainingSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceSummary>
 */
class AttendanceSummaryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'training_schedule_id' => TrainingSchedule::factory(),
            'attendance_date' => today(),
            'morning_status' => 'on_time',
            'afternoon_status' => 'present',
            'overall_status' => 'present',
            'finalized_at' => now(),
        ];
    }
}
