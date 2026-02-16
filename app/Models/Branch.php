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
}
