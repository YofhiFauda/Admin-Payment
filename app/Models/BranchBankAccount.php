<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchBankAccount extends Model
{
    use HasFactory;
    protected $fillable = [
        'branch_id',
        'bank_name',
        'account_number',
        'account_name',
    ];

    /**
     * Get the branch that owns the bank account.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
