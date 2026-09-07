<?php

namespace Database\Factories;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
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
            'type' => fake()->randomElement(['leave', 'sick']),
            'start_date' => today(),
            'end_date' => today(),
            'reason' => fake()->sentence(12),
            'document_path' => null,
            'status' => 'pending',
            'processed_by' => null,
            'processed_at' => null,
            'admin_notes' => null,
        ];
    }
}
