<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    public function definition(): array
    {
        return ['title' => fake()->sentence(), 'slug' => fake()->unique()->slug(), 'type' => 'normal', 'status' => 'published', 'published_at' => now(), 'category_id' => \App\Models\Category::factory()];
    }
}
