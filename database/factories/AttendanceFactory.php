<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\AttendanceLocation;
use App\Models\TrainingSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
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
            'attendance_location_id' => AttendanceLocation::factory(),
            'attendance_date' => today(),
            'type' => 'morning',
            'status' => 'on_time',
            'latitude' => -6.1549,
            'longitude' => 106.7351,
            'accuracy_meters' => 8.5,
            'distance_meters' => 4.2,
            'recorded_at' => now(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'device_token_id' => null,
            'needs_review' => false,
            'voided_at' => null,
        ];
    }
}
