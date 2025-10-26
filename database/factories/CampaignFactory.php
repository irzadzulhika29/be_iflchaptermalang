<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Campaign>
 */
class CampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $user = User::where('username', 'bismar')->first();
        $title = $this->faker->unique()->sentence(4);
        $currentDonation = $this->faker->randomFloat(2, 0, 100000);
        $targetDonation = $this->faker->randomFloat(2, $currentDonation + 1, 100000);
        $startDate = now()->format('Y-m-d');
        $endDate = $this->faker->dateTimeBetween($startDate, '+1 year')->format('Y-m-d');
        $shortDescription = implode(' ', $this->faker->words(rand(3, 5)));

        return [
          'title' => $title,
          'slug' => Str::slug($title),
          'short_description' => $shortDescription,
          'body' => $this->faker->paragraph,
          'status' => $this->faker->randomElement(['active', 'closed', 'pending']),
          'current_donation' => $currentDonation,
          'target_donation' => $targetDonation,
          'publish_date' => $startDate,
          'end_date' => $endDate,
          'note' => $this->faker->optional()->sentence,
          'receiver' => $this->faker->name,
          'image' => $this->faker->imageUrl(),
          'user_id' => $user->id ?? null,
        ];
    }
}
