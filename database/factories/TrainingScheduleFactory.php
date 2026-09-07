<?php

namespace Database\Factories;

use App\Models\AttendanceLocation;
use App\Models\TrainingClass;
use App\Models\TrainingSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingSchedule>
 */
class TrainingScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'training_class_id' => TrainingClass::factory(),
            'attendance_location_id' => AttendanceLocation::factory(),
            'schedule_date' => fake()->unique()->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'morning_open' => '06:30:00',
            'morning_on_time_limit' => '08:00:00',
            'morning_close' => '09:00:00',
            'afternoon_open' => '15:30:00',
            'afternoon_early_limit' => '16:00:00',
            'afternoon_close' => '18:00:00',
            'subject' => fake()->words(3, true),
            'notes' => null,
            'status' => 'active',
        ];
    }
}
