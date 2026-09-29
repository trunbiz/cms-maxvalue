<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PageFactory extends Factory
{
    public function definition(): array
    {
        return ['title' => fake()->sentence(), 'slug' => fake()->unique()->slug(), 'content' => '<p>'.fake()->paragraph().'</p>'];
    }
}
