<?php

namespace Database\Factories;

use App\Models\ParticipantProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParticipantProfile>
 */
class ParticipantProfileFactory extends Factory
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
            'nik' => fake()->unique()->numerify('################'),
            'participant_number' => fake()->unique()->bothify('PPKD-####'),
            'gender' => fake()->randomElement(['male', 'female']),
            'birth_place' => fake()->city(),
            'birth_date' => fake()->dateTimeBetween('-40 years', '-17 years')->format('Y-m-d'),
            'address' => fake()->address(),
            'phone' => '08'.fake()->numerify('##########'),
            'profile_photo_path' => null,
            'participant_status' => 'active',
            'privacy_accepted_at' => now(),
        ];
    }
}
