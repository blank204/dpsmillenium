<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Account extends Authenticatable
{
    use HasFactory;

    public const EDITOR = 'editor';
    public const SUPER_ADMIN = 'super_admin';

    protected $fillable = ['role', 'username', 'password'];

    protected $hidden = ['password', 'remember_token'];

    // Mirrors the migration's DB-level default so a freshly-created,
    // in-memory model reflects session_version = 1 without a round trip.
    protected $attributes = [
        'session_version' => 1,
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'session_version' => 'integer',
            'password_changed_at' => 'datetime',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::SUPER_ADMIN;
    }

    public function bumpSessionVersion(): void
    {
        $this->increment('session_version');
    }

    public function getRememberTokenName(): string
    {
        return '';
    }
}
