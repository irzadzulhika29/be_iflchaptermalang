<?php

namespace Database\Factories;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Transaction;
use App\Models\User;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Donation>
 */
class DonationFactory extends Factory
{
    protected $model = Donation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
          'name' => $this->faker->name,
          'email' => $this->faker->email,
          'anonymous' => $this->faker->boolean,
          'donation_amount' => $this->faker->randomFloat(2, 10, 1000),
          'donation_message' => $this->faker->sentence,
          'status' => $this->faker->randomElement(['unpaid', 'pending', 'paid', 'denied', 'expired', 'canceled']),
          'campaign_id' => Campaign::inRandomOrder()->first()->id ?? $this->faker->uuid,
          'user_id' => User::inRandomOrder()->first()->id ?? $this->faker->uuid,
        ];
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterCreating(function (Donation $donation) {
            // Create a transaction for the donation
            Transaction::factory()->create([
              'donation_id' => $donation->id,
              'user_id' => $donation->user_id
            ]);
        });
    }
}
