<?php

namespace Database\Factories;

use App\Models\TrainingBatch;
use App\Models\TrainingProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingBatch>
 */
class TrainingBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'training_program_id' => TrainingProgram::factory(),
            'name' => 'Angkatan '.fake()->unique()->numberBetween(1, 9999),
            'start_date' => today(),
            'end_date' => today()->addMonths(3),
            'quota' => 40,
            'status' => 'open',
        ];
    }
}
