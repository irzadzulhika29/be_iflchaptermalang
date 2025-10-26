<?php

namespace Database\Factories;
use App\Models\Donation;
use App\Models\User;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'snap_token' => $this->faker->uuid,
            'midtrans_transaction_id' => $this->faker->uuid,
            'payment_method' => $this->faker->randomElement(['credit_card', 'bank_transfer', 'e-wallet']),
            'payment_provider' => $this->faker->randomElement(['ovo', 'dana', 'shopeepay', 'gopay', 'mandiri', 'bca', 'bni']),
            'va_number' => $this->faker->bankAccountNumber,
            'transaction_success_time' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'transaction_expiry_time' => $this->faker->dateTimeBetween('now', '+1 day'),
            'donation_id' => Donation::inRandomOrder()->first()->id,
            'user_id' => User::inRandomOrder()->first()->id,
        ];
    }
}
