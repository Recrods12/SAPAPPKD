<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\InstructorAttendanceCorrection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstructorAttendanceCorrection>
 */
class InstructorAttendanceCorrectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['attendance_id' => Attendance::factory(), 'instructor_id' => User::factory(), 'requested_status' => 'present', 'reason' => fake()->sentence(), 'status' => 'pending', 'admin_notes' => null, 'processed_by' => null, 'processed_at' => null];
    }
}
