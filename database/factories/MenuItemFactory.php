<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MenuItemFactory extends Factory
{
    public function definition(): array
    {
        return ['menu_id' => \App\Models\Menu::factory(), 'label' => fake()->word(), 'type' => 'url', 'url' => '/'];
    }
}
