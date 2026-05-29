<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\BranchDebt;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchDebtFactory extends Factory
{
    protected $model = BranchDebt::class;

    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'debtor_branch_id' => Branch::factory(),
            'creditor_branch_id' => Branch::factory(),
            'amount' => $this->faker->numberBetween(100000, 1000000),
            'status' => 'pending',
        ];
    }
}
