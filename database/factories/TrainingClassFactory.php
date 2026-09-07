<?php

namespace Database\Factories;

use App\Models\TrainingBatch;
use App\Models\TrainingClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingClass>
 */
class TrainingClassFactory extends Factory
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
            'name' => 'Kelas '.fake()->unique()->bothify('?#'),
            'room' => fake()->bothify('Ruang ?-#'),
            'capacity' => 20,
            'is_active' => true,
        ];
    }
}
