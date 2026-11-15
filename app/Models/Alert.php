<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $fillable = ['user_id', 'type', 'title', 'body', 'url', 'read_at'];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Membuat notifikasi baru untuk seorang pengguna
    public static function send(int $userId, string $type, string $title, ?string $body = null, ?string $url = null): self
    {
        return static::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'url' => static::relativeUrl($url),
        ]);
    }

    // Simpan hanya path-nya (misal "/claims/3#chat") supaya link tetap benar
    // walau aplikasi dibuka lewat localhost, 127.0.0.1, atau IP laptop saat demo
    public static function relativeUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $parts = parse_url($url);
        $path = ($parts['path'] ?? '/')
            .(isset($parts['query']) ? '?'.$parts['query'] : '')
            .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');

        return $path;
    }

    // Kirim ke semua admin aktif
    public static function sendToAdmins(string $type, string $title, ?string $body = null, ?string $url = null): void
    {
        User::where('role', 'admin')->where('is_active', true)->pluck('id')
            ->each(fn ($id) => static::send($id, $type, $title, $body, $url));
    }

    public function getIconAttribute(): string
    {
        return match ($this->type) {
            'match' => 'binoculars',
            'claim_new' => 'inbox',
            'claim_update' => 'patch-check',
            'message' => 'chat-dots',
            default => 'bell',
        };
    }
}
