<?php

namespace Database\Factories;

use App\Models\FraudFlag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FraudFlag>
 */
class FraudFlagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_id' => null,
            'user_id' => User::factory(),
            'reason' => fake()->randomElement(['poor_gps_accuracy', 'shared_device', 'identical_photo', 'impossible_movement']),
            'severity' => 'warning',
            'metadata' => ['generated_by' => 'factory'],
            'status' => 'open',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_notes' => null,
        ];
    }
}
