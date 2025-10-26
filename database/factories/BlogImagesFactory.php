<?php

namespace Database\Factories;

use App\Models\Blog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BlogImages>
 */
class BlogImagesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
          'blog_id' => Blog::inRandomOrder()->first()->id ?? $this->faker->uuid,
          'image_url' => $this->faker->imageUrl(),
        ];
    }
}
