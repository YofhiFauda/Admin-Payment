<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bank_name',
        'account_number',
        'account_name',
    ];

    /**
     * Set the bank_name and account_name to uppercase automatically.
     */
    public function setBankNameAttribute($value)
    {
        $this->attributes['bank_name'] = strtoupper($value);
    }

    public function setAccountNameAttribute($value)
    {
        $this->attributes['account_name'] = strtoupper($value);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
