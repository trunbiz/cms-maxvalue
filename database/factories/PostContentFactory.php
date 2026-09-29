<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PostContentFactory extends Factory
{
    public function definition(): array
    {
        return ['post_id' => \App\Models\Post::factory(), 'content' => '<p>'.fake()->paragraph().'</p>'];
    }
}
