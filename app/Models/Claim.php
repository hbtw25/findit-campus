<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Claim extends Model
{
    public const STATUSES = [
        'pending' => 'Menunggu',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'cancelled' => 'Dibatalkan',
        'returned' => 'Diserahkan',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected $fillable = [
        'report_id', 'user_id', 'lost_report_id', 'secret_answer', 'proof', 'status',
        'admin_note', 'reviewed_by', 'reviewed_at', 'returned_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    // Pengguna yang mengajukan klaim
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lostReport()
    {
        return $this->belongsTo(Report::class, 'lost_report_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['pending', 'approved']);
    }

    // Chat terbuka setelah klaim disetujui
    public function chatOpen(): bool
    {
        return in_array($this->status, ['approved', 'returned']);
    }

    // Penemu (pemilik laporan temuan), pengklaim, dan admin boleh melihat klaim
    public function canBeViewedBy(User $user): bool
    {
        return $user->isAdmin()
            || $this->user_id === $user->id
            || $this->report->user_id === $user->id;
    }
}
