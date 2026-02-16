<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Role checkers
    public function isTeknisi(): bool { return $this->role === 'teknisi'; }
    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isAtasan(): bool { return $this->role === 'atasan'; }
    public function isOwner(): bool { return $this->role === 'owner'; }

    // Permission checkers (match React ROLES definition)
    public function canInput(): bool
    {
        return in_array($this->role, ['teknisi', 'admin', 'owner']);
    }

    public function canManageStatus(): bool
    {
        return in_array($this->role, ['admin', 'atasan', 'owner']);
    }

    public function canViewHistory(): bool
    {
        return true; // all roles
    }

    // Relationships
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'submitted_by');
    }
}
