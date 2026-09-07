<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\AttendancePhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendancePhoto>
 */
class AttendancePhotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_id' => Attendance::factory(),
            'disk' => 'local',
            'path' => 'attendance-photos/'.fake()->uuid().'.jpg',
            'size_bytes' => fake()->numberBetween(50_000, 500_000),
            'mime_type' => 'image/jpeg',
            'width' => 720,
            'height' => 960,
            'sha256' => hash('sha256', fake()->uuid()),
            'source_sha256' => hash('sha256', fake()->uuid()),
            'uploaded_at' => now(),
        ];
    }
}
