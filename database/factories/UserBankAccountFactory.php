<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserBankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserBankAccount>
 */
class UserBankAccountFactory extends Factory
{
    protected $model = UserBankAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $banks = ['BCA', 'MANDIRI', 'BNI', 'BRI', 'CIMB NIAGA', 'PERMATA', 'DANAMON'];
        
        return [
            'user_id' => User::factory(),
            'bank_name' => fake()->randomElement($banks),
            'account_name' => fake()->name(),
            'account_number' => fake()->numerify('##########'),
            'is_primary' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that this is the primary account.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }
}
