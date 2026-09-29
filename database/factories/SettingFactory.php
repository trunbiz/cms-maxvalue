<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SettingFactory extends Factory
{
    public function definition(): array
    {
        return ['key' => fake()->unique()->slug(), 'value' => fake()->word()];
    }
}
