<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchDebt extends Model
{
    protected $fillable = [
        'transaction_id',
        'debtor_branch_id',
        'creditor_branch_id',
        'amount',
        'status',
        'payment_proof',
        'paid_at',
        'notes',
        'payment_method',
        'paid_by_id',
        'bank_account_id',
        'sender_bank_account_id',
    ];

    protected $casts = [
        'amount'  => 'integer',
        'paid_at' => 'datetime',
    ];

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  RELATIONSHIPS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function debtorBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'debtor_branch_id');
    }

    public function creditorBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'creditor_branch_id');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BranchBankAccount::class, 'bank_account_id');
    }

    public function senderBankAccount(): BelongsTo
    {
        return $this->belongsTo(BranchBankAccount::class, 'sender_bank_account_id');
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  SCOPES
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  HELPERS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function markAsPaid(
        ?string $notes = null,
        ?string $payment_proof = null,
        ?int $paidBy = null,
        ?int $bankAccountId = null,
        ?int $senderBankAccountId = null,
        string $paymentMethod = 'transfer'
    ): void {
        $this->update([
            'status'                 => 'paid',
            'paid_at'                => now(),
            'notes'                  => $notes ?? $this->notes,
            'payment_proof'          => $payment_proof ?? $this->payment_proof,
            'paid_by_id'             => $paidBy ?? auth()->id(),
            'payment_method'         => $paymentMethod,
            'bank_account_id'        => $paymentMethod === 'cash' ? null : $bankAccountId,
            'sender_bank_account_id' => $paymentMethod === 'cash' ? null : $senderBankAccountId,
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isCash(): bool
    {
        return $this->payment_method === 'cash';
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }
}
