<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Report extends Model
{
    use HasFactory;

    public const TYPES = [
        'lost' => 'Hilang',
        'found' => 'Ditemukan',
    ];

    public const STATUSES = [
        'open' => 'Aktif',
        'in_claim' => 'Proses Klaim',
        'resolved' => 'Selesai',
        'closed' => 'Ditutup',
    ];

    protected $fillable = [
        'user_id', 'category_id', 'campus_location_id', 'type', 'title', 'description',
        'color', 'brand', 'secret_detail', 'event_at', 'location_note', 'lat', 'lng',
        'photo', 'photo_thumb', 'status', 'is_hidden',
    ];

    // Nilai bawaan agar model yang baru dibuat langsung punya status yang sama dengan default database
    protected $attributes = [
        'status' => 'open',
        'is_hidden' => false,
    ];

    // Ciri rahasia tidak boleh ikut terkirim saat model diubah ke JSON
    protected $hidden = ['secret_detail'];

    protected function casts(): array
    {
        return [
            'event_at' => 'datetime',
            'lat' => 'float',
            'lng' => 'float',
            'is_hidden' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Report $report) {
            if (empty($report->share_code)) {
                do {
                    $code = Str::upper(Str::random(6));
                } while (static::where('share_code', $code)->exists());
                $report->share_code = $code;
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function campusLocation()
    {
        return $this->belongsTo(CampusLocation::class);
    }

    public function claims()
    {
        return $this->hasMany(Claim::class);
    }

    // Kandidat temuan untuk laporan hilang ini
    public function foundMatches()
    {
        return $this->hasMany(ReportMatch::class, 'lost_report_id');
    }

    // Kandidat laporan hilang untuk barang temuan ini
    public function lostMatches()
    {
        return $this->hasMany(ReportMatch::class, 'found_report_id');
    }

    // Laporan yang boleh tampil di peta dan halaman publik
    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false)->whereIn('status', ['open', 'in_claim']);
    }

    // Filter bersama untuk Peta Global dan halaman Jelajah
    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['category'] ?? null, fn ($q, $category) => $q->where('category_id', $category))
            ->when($filters['location'] ?? null, fn ($q, $location) => $q->where('campus_location_id', $location))
            ->when($filters['days'] ?? null, fn ($q, $days) => $q->where('event_at', '>=', now()->subDays((int) $days)))
            ->when($filters['q'] ?? null, function ($q, $keyword) {
                $q->where(function ($q) use ($keyword) {
                    $q->where('title', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhere('color', 'like', "%{$keyword}%")
                        ->orWhere('brand', 'like', "%{$keyword}%");
                });
            });

        return $query;
    }

    public function isLost(): bool
    {
        return $this->type === 'lost';
    }

    public function isFound(): bool
    {
        return $this->type === 'found';
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'in_claim']);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? asset('storage/'.$this->photo) : null;
    }

    public function getThumbUrlAttribute(): ?string
    {
        return $this->photo_thumb ? asset('storage/'.$this->photo_thumb) : null;
    }

    public function getPlaceNameAttribute(): string
    {
        if ($this->campusLocation) {
            return $this->campusLocation->name;
        }

        return $this->location_note ?: 'Area kampus';
    }

    public function getShareUrlAttribute(): string
    {
        return route('share.short', $this->share_code);
    }

    // Data ringkas untuk marker di Peta Global (dipakai di JSON AJAX)
    public function toMapArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'title' => $this->title,
            'category' => $this->category?->name,
            'icon' => $this->category?->icon ?? 'box-seam',
            'place' => $this->campusLocation?->short_name ?? $this->place_name,
            'place_full' => $this->place_name,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'thumb' => $this->thumb_url,
            'time_ago' => $this->event_at->diffForHumans(),
            'event_at' => $this->event_at->toIso8601String(),
            'url' => route('reports.show', $this),
        ];
    }
}
