<?php

namespace Database\Factories;

use App\Models\ParticipantEnrollment;
use App\Models\TrainingBatch;
use App\Models\TrainingClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParticipantEnrollment>
 */
class ParticipantEnrollmentFactory extends Factory
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
            'training_batch_id' => TrainingBatch::factory(),
            'training_class_id' => fn (array $attributes) => TrainingClass::factory()->create(['training_batch_id' => $attributes['training_batch_id']])->id,
            'status' => 'active',
            'enrolled_at' => today(),
            'completed_at' => null,
        ];
    }
}
