<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $attributes = [
        'role' => 'user',
        'is_active' => true,
    ];

    protected $fillable = ['name', 'nim', 'email', 'phone', 'password', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function claims()
    {
        return $this->hasMany(Claim::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // Inisial nama untuk avatar, contoh "Rani Putri" -> "RP"
    public function getInitialsAttribute(): string
    {
        $words = preg_split('/\s+/', trim($this->name));
        $initials = mb_substr($words[0] ?? '', 0, 1).mb_substr($words[1] ?? '', 0, 1);

        return mb_strtoupper($initials ?: '?');
    }

    // Nama pendek untuk ditampilkan publik, contoh "Rani Putri Lestari" -> "Rani P."
    public function getShortNameAttribute(): string
    {
        $words = preg_split('/\s+/', trim($this->name));
        $short = $words[0] ?? '';
        if (isset($words[1])) {
            $short .= ' '.mb_substr($words[1], 0, 1).'.';
        }

        return $short;
    }
}
