<?php

namespace Database\Factories;

use App\Models\AttendanceLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceLocation>
 */
class AttendanceLocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company().' Training Center',
            'address' => fake()->address(),
            'latitude' => fake()->latitude(-6.3, -6.0),
            'longitude' => fake()->longitude(106.6, 106.9),
            'radius_meters' => 20,
            'max_accuracy_meters' => 35,
            'is_active' => true,
        ];
    }
}
