<?php

namespace Database\Factories;

use App\Models\Holiday;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Holiday>
 */
class HolidayFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'holiday_date' => fake()->unique()->dateTimeBetween('now', '+2 years')->format('Y-m-d'),
            'name' => fake()->randomElement(['Hari Libur Nasional', 'Libur Pelatihan', 'Cuti Bersama']).' '.fake()->unique()->numberBetween(1, 9999),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
