<?php

namespace Tests\Unit;

use App\Models\Transaction;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransactionStatusLabelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_correct_status_label_for_waiting_payment_without_proof()
    {
        $transaction = new Transaction();
        $transaction->status = 'waiting_payment';
        $transaction->type = 'rembush';

        $this->assertEquals('Menunggu Pembayaran', $transaction->status_label);
    }

    /** @test */
    public function it_returns_correct_status_label_for_waiting_payment_with_invoice()
    {
        $transaction = new Transaction();
        $transaction->status = 'waiting_payment';
        $transaction->type = 'rembush';
        $transaction->invoice_file_path = 'invoices/test.pdf';

        $this->assertEquals('Menunggu Pelunasan Hutang', $transaction->status_label);
    }

    /** @test */
    public function it_returns_correct_status_label_for_waiting_payment_with_bukti_transfer()
    {
        $transaction = new Transaction();
        $transaction->status = 'waiting_payment';
        $transaction->type = 'rembush';
        $transaction->bukti_transfer = 'proofs/test.jpg';

        $this->assertEquals('Menunggu Pelunasan Hutang', $transaction->status_label);
    }

    /** @test */
    public function it_returns_correct_status_label_for_gudang_waiting_payment()
    {
        $transaction = new Transaction();
        $transaction->status = 'waiting_payment';
        $transaction->type = 'gudang';

        $this->assertEquals('Pembelanjaan Belum di bayar', $transaction->status_label);
    }

    /** @test */
    public function it_returns_correct_status_label_for_waiting_payment_with_branch_debts()
    {
        $branch = \App\Models\Branch::create(['name' => 'Test Branch']);
        
        // Setup transaction
        $transaction = \App\Models\Transaction::create([
            'status' => 'waiting_payment',
            'type' => 'rembush',
            'amount' => 100000,
            'invoice_number' => 'TEST-001',
            'date' => now(),
            'category' => 'other',
        ]);
        $transaction->branches()->attach($branch->id, ['allocation_percent' => 100, 'allocation_amount' => 100000]);

        // Create a debt for this branch from a DIFFERENT transaction
        \App\Models\BranchDebt::create([
            'transaction_id' => 999, // dummy
            'debtor_branch_id' => $branch->id,
            'creditor_branch_id' => 888, // dummy
            'amount' => 50000,
            'status' => 'pending'
        ]);

        $this->assertEquals('Menunggu Pelunasan Hutang', $transaction->status_label);
    }

    /** @test */
    public function it_includes_status_label_in_to_search_array()
    {
        $transaction = new Transaction();
        $transaction->status = 'waiting_payment';
        $transaction->invoice_file_path = 'invoices/test.pdf';
        
        $array = $transaction->toSearchArray();
        
        $this->assertEquals('Menunggu Pelunasan Hutang', $array['status_label']);
    }

    /** @test */
    public function it_includes_payment_proof_fields_in_to_search_array()
    {
        $transaction = new Transaction();
        $transaction->invoice_file_path = 'invoices/test.pdf';
        $transaction->bukti_transfer = 'proofs/test.jpg';
        $transaction->foto_penyerahan = 'photos/test.jpg';
        
        $array = $transaction->toSearchArray();
        
        $this->assertEquals('invoices/test.pdf', $array['invoice_file_path']);
        $this->assertEquals('proofs/test.jpg', $array['bukti_transfer']);
        $this->assertEquals('photos/test.jpg', $array['foto_penyerahan']);
    }
}
