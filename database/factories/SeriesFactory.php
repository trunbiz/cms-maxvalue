<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SeriesFactory extends Factory
{
    public function definition(): array
    {
        return ['title' => fake()->sentence(), 'slug' => fake()->unique()->slug(), 'category_id' => \App\Models\Category::factory(), 'status' => 'published'];
    }
}
