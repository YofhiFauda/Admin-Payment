<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_rembush_under_one_million_is_auto_completed()
    {
        $admin = User::factory()->admin()->create();

        $transaction = Transaction::factory()->rembush()->create([
            'amount' => 500000,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($admin)
                         ->patch("/transactions/{$transaction->id}/status", [
                             'status' => 'approved'
                         ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Reload from DB
        $transaction->refresh();

        // Expectation: Because it's < 1,000,000, logic should bump it directly to 'completed'
        $this->assertEquals('completed', $transaction->status);
    }

    public function test_transaction_over_one_million_needs_owner_approval()
    {
        $admin = User::factory()->admin()->create();

        $transaction = Transaction::factory()->rembush()->create([
            'amount' => 1500000,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($admin)
                         ->patch("/transactions/{$transaction->id}/status", [
                             'status' => 'approved'
                         ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Reload from DB
        $transaction->refresh();

        // Expectation: Because it's >= 1,000,000, logic should keep it 'approved' waiting for Owner
        $this->assertEquals('approved', $transaction->status);
    }

    public function test_teknisi_cannot_update_transaction_status()
    {
        $teknisi = User::factory()->create(['role' => 'teknisi']);

        $transaction = Transaction::factory()->rembush()->create([
            'amount' => 500000,
            'status' => 'pending'
        ]);

        // Teknisi tries to hit the status update endpoint
        $response = $this->actingAs($teknisi)
                         ->patch("/transactions/{$transaction->id}/status", [
                             'status' => 'approved'
                         ]);

        // Expectation: Middleware 'role:admin,atasan,owner' should block them
        $response->assertStatus(403);
        
        // Ensure status in DB hasn't changed
        $transaction->refresh();
        $this->assertEquals('pending', $transaction->status);
    }
}
