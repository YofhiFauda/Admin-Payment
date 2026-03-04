<?php

namespace Database\Factories;

use App\Models\Transaction;
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
    public function definition(): array
    {
        return [
            'type' => Transaction::TYPE_REMBUSH,
            'invoice_number' => $this->faker->unique()->randomNumber(5),
            'customer' => $this->faker->company(),
            'category' => 'beban_lain_lain',
            'description' => $this->faker->sentence(),
            'payment_method' => 'cash',
            'amount' => $this->faker->numberBetween(10000, 500000),
            'date' => $this->faker->date(),
            'status' => 'pending',
            'submitted_by' => User::factory(),
        ];
    }

    public function pengajuan(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Transaction::TYPE_PENGAJUAN,
            'vendor' => $this->faker->company(),
            'estimated_price' => $this->faker->numberBetween(50000, 2000000),
            'quantity' => $this->faker->numberBetween(1, 10),
            'purchase_reason' => 'beban_lain_lain',
            'amount' => null,
            'category' => null,
        ]);
    }

    public function rembush(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Transaction::TYPE_REMBUSH,
            // Defaults are already rembush
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => User::factory()->admin(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'reviewed_at' => now(),
            'reviewed_by' => User::factory()->admin(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => User::factory()->admin(),
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }
}
