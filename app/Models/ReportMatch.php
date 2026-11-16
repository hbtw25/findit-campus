<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportMatch extends Model
{
    protected $attributes = [
        'status' => 'suggested',
    ];

    protected $fillable = [
        'lost_report_id', 'found_report_id', 'rule_score', 'ai_score', 'ai_reason',
        'score', 'distance_m', 'status', 'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'notified_at' => 'datetime',
        ];
    }

    // Kandidat yang ditampilkan ke pengguna: masih aktif dan skornya (aturan atau AI) di atas ambang.
    // Kandidat yang dinilai AI terlalu rendah tetap tersimpan agar tidak dinilai ulang terus.
    public function scopeShown($query)
    {
        return $query->where('status', 'suggested')->where('score', '>=', config('findit.match.min_score'));
    }

    public function lostReport()
    {
        return $this->belongsTo(Report::class, 'lost_report_id');
    }

    public function foundReport()
    {
        return $this->belongsTo(Report::class, 'found_report_id');
    }

    public function getSourceLabelAttribute(): string
    {
        return $this->ai_score !== null ? 'Dinilai AI' : 'Skor aturan';
    }

    public function getDistanceLabelAttribute(): string
    {
        return $this->distance_m < 1000
            ? $this->distance_m.' m'
            : number_format($this->distance_m / 1000, 1, ',', '.').' km';
    }
}
