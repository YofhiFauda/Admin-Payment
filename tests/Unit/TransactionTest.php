<?php

namespace Tests\Unit;

use App\Models\Transaction;
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    public function test_type_checkers_work_correctly(): void
    {
        $rembush = new Transaction(['type' => Transaction::TYPE_REMBUSH]);
        $pengajuan = new Transaction(['type' => Transaction::TYPE_PENGAJUAN]);

        $this->assertTrue($rembush->isRembush());
        $this->assertFalse($rembush->isPengajuan());

        $this->assertTrue($pengajuan->isPengajuan());
        $this->assertFalse($pengajuan->isRembush());
    }

    public function test_status_checkers_return_correct_booleans(): void
    {
        $pending = new Transaction(['status' => 'pending']);
        $approved = new Transaction(['status' => 'approved']);
        $completed = new Transaction(['status' => 'completed']);
        $rejected = new Transaction(['status' => 'rejected']);

        $this->assertTrue($pending->isPending());
        $this->assertFalse($pending->isApproved());

        $this->assertTrue($approved->isApproved());
        $this->assertTrue($completed->isCompleted());
        $this->assertTrue($rejected->isRejected());
    }

    public function test_effective_amount_is_correct_for_rembush(): void
    {
        // Rembush uses the 'amount' field
        $transaction = new Transaction([
            'type' => 'rembush',
            'amount' => 500000
        ]);

        $this->assertEquals(500000, $transaction->effective_amount);
        $this->assertEquals('Rp 500.000', $transaction->formatted_amount);
    }

    public function test_effective_amount_is_correct_for_pengajuan(): void
    {
        // Pengajuan also uses the 'amount' field (which represents total estimation: price * qty)
        $transaction = new Transaction([
            'type' => 'pengajuan',
            'amount' => 750000,
            'estimated_price' => 75000,
            'quantity' => 10
        ]);

        $this->assertEquals(750000, $transaction->effective_amount);
        $this->assertEquals('Rp 750.000', $transaction->formatted_amount);
    }

    public function test_format_short_rupiah_works_correctly(): void
    {
        // Test Thousands (below 1M)
        $this->assertEquals('Rp 500.000', Transaction::formatShortRupiah(500000));
        
        // Test Millions (Jt)
        $this->assertEquals('Rp 1,5 Jt', Transaction::formatShortRupiah(1500000));
        $this->assertEquals('Rp 10 Jt', Transaction::formatShortRupiah(10000000));
        
        // Test Billions (M)
        $this->assertEquals('Rp 1,25 M', Transaction::formatShortRupiah(1250000000));
        $this->assertEquals('Rp 500 M', Transaction::formatShortRupiah(500000000000));

        // Test Trillions (T)
        $this->assertEquals('Rp 1,5 T', Transaction::formatShortRupiah(1500000000000));
    }
}
