<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = ['name'];

    public function transactions()
    {
        return $this->belongsToMany(Transaction::class, 'transaction_branches')
                    ->withPivot('allocation_percent', 'allocation_amount')
                    ->withTimestamps();
    }

    /**
     * Rekening bank yang dimiliki cabang ini
     */
    public function bankAccounts()
    {
        return $this->hasMany(BranchBankAccount::class, 'branch_id');
    }

    /**
     * Hutang yang dimiliki cabang ini (sebagai debtor/yang berhutang)
     */
    public function debtsAsDebtor()
    {
        return $this->hasMany(BranchDebt::class, 'debtor_branch_id');
    }

    /**
     * Piutang yang dimiliki cabang ini (sebagai creditor/yang membayarkan)
     */
    public function debtsAsCreditor()
    {
        return $this->hasMany(BranchDebt::class, 'creditor_branch_id');
    }

    /**
     * Total hutang pending cabang ini
     */
    public function getTotalPendingDebtAttribute(): int
    {
        return $this->debtsAsDebtor()->pending()->sum('amount');
    }

    /**
     * Total piutang pending cabang ini
     */
    public function getTotalPendingCreditAttribute(): int
    {
        return $this->debtsAsCreditor()->pending()->sum('amount');
    }
}
