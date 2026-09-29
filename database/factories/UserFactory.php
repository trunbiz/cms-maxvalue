<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->name(), 'username' => 'user_'.fake()->unique()->numerify('########'), 'password' => bcrypt('password'), 'role_id' => \App\Models\Role::factory()];
    }
}
