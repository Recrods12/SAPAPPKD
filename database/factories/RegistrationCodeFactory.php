<?php

namespace Database\Factories;

use App\Models\RegistrationCode;
use App\Models\TrainingBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistrationCode>
 */
class RegistrationCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'training_batch_id' => TrainingBatch::factory(),
            'code' => strtoupper(fake()->unique()->bothify('PPKD-####-????')),
            'expires_at' => now()->addMonth(),
            'usage_limit' => 50,
            'usage_count' => 0,
            'is_active' => true,
        ];
    }
}
