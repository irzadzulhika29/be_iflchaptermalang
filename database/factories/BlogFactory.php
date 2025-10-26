<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Blog>
 */
class BlogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $title = $this->faker->sentence;
        return [
          'title' => $title,
          'slug' => Str::slug($title),
          'content' => $this->faker->paragraphs(3, true),
          'like' => $this->faker->numberBetween(0, 100),
          'author_id' => User::inRandomOrder()->first()->id ?? $this->faker->uuid,
          'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
          'updated_at' => now(),
        ];
    }
}
