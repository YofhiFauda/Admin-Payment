<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPartialUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_branches_without_items()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin'
        ]);
        $branch1 = Branch::create(['name' => 'Branch 1']);
        $branch2 = Branch::create(['name' => 'Branch 2']);

        $transaction = Transaction::create([
            'type' => 'pengajuan',
            'invoice_number' => 'INV-TEST-001',
            'status' => 'pending',
            'items' => [
                ['customer' => 'Item 1', 'quantity' => 1, 'estimated_price' => 1000, 'category' => 'office_supplies']
            ],
            'amount' => 1000,
            'submitted_by' => $admin->id,
        ]);

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->actingAs($admin)
            ->put(route('transactions.update', $transaction->id), [
                'type' => 'pengajuan',
                'branches' => [
                    ['branch_id' => $branch1->id, 'allocation_percent' => 50, 'allocation_amount' => 500],
                    ['branch_id' => $branch2->id, 'allocation_percent' => 50, 'allocation_amount' => 500],
                ]
            ])
            ->assertRedirect(route('transactions.index'))
            ->assertSessionHas('success');

        $this->assertEquals(2, $transaction->fresh()->branches()->count());
        $this->assertEquals('Item 1', $transaction->fresh()->items[0]['customer']); // Items should remain unchanged
    }
}
