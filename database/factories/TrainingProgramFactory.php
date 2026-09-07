<?php

namespace Database\Factories;

use App\Models\TrainingProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingProgram>
 */
class TrainingProgramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('PRG-###')),
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'duration_days' => fake()->numberBetween(20, 90),
            'is_active' => true,
        ];
    }
}
