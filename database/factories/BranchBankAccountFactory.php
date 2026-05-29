<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\BranchBankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BranchBankAccount>
 */
class BranchBankAccountFactory extends Factory
{
    protected $model = BranchBankAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $banks = ['BCA', 'MANDIRI', 'BNI', 'BRI', 'CIMB NIAGA', 'PERMATA', 'DANAMON'];
        
        return [
            'branch_id' => Branch::factory(),
            'bank_name' => fake()->randomElement($banks),
            'account_name' => fake()->company(),
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
