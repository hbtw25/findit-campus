<?php

namespace App\Services;

use App\Models\Report;
use App\Models\ReportMatch;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

// Auto-match tahap 2 (opsional): AI menilai ulang kandidat dengan membaca deskripsi dan foto.
// Penyedia: Gemini (Google AI Studio, gratis) lalu OpenCode sebagai cadangan.
// Ciri rahasia (secret_detail) dan nama pelapor sengaja tidak pernah dikirim.
class AiMatcher
{
    private const SYSTEM = 'Kamu membantu layanan barang hilang di kampus. Nilai kecocokan secara hati-hati dan jujur; '
        .'skor tinggi hanya bila ciri-cirinya benar-benar sesuai.';

    private const TASK = 'Beri skor 0-100 untuk setiap kandidat: seberapa yakin kandidat itu adalah barang yang hilang. '
        .'Bandingkan jenis barang, warna, merek, ciri fisik yang terlihat di foto, waktu, dan jarak. '
        .'Tulis alasan singkat dalam bahasa Indonesia, maksimal 140 karakter, tanpa menyebut nama orang. '
        .'Jawab hanya dengan JSON {"matches":[{"id":angka,"score":angka,"reason":"teks"}]}.';

    // Nama penyedia yang punya API key, sesuai urutan AI_PROVIDERS
    public static function providers(): array
    {
        return array_values(array_filter(
            config('findit.ai.providers', []),
            fn ($name) => in_array($name, ['gemini', 'opencode']) && filled(config("findit.ai.$name.api_key"))
        ));
    }

    public static function enabled(): bool
    {
        return count(self::providers()) > 0;
    }

    /**
     * Nilai semua kandidat aktif milik satu laporan hilang.
     * Mengembalikan jumlah kandidat yang berhasil dinilai.
     */
    public function scoreLostReport(Report $lost): int
    {
        if (! self::enabled()) {
            return 0;
        }

        $matches = ReportMatch::with('foundReport.category', 'foundReport.campusLocation')
            ->where('lost_report_id', $lost->id)
            ->where('status', 'suggested')
            ->get();

        if ($matches->isEmpty()) {
            return 0;
        }

        $lost->loadMissing('category', 'campusLocation');

        // Isi pesan disusun sekali dalam bentuk netral, lalu diubah ke format tiap penyedia
        $parts = [['text' => "LAPORAN BARANG HILANG\n".$this->describe($lost)]];
        if ($photo = $this->photo($lost)) {
            $parts[] = ['image' => $photo];
        }
        foreach ($matches as $match) {
            $found = $match->foundReport;
            $parts[] = ['text' => "KANDIDAT id={$found->id} (jarak {$match->distance_m} m dari lokasi hilang)\n".$this->describe($found)];
            if ($photo = $this->photo($found)) {
                $parts[] = ['image' => $photo];
            }
        }
        $parts[] = ['text' => self::TASK];

        $result = null;
        foreach (self::providers() as $provider) {
            try {
                $result = $provider === 'gemini' ? $this->askGemini($parts) : $this->askOpenCode($parts);
            } catch (Throwable $e) {
                // Kuota habis, jaringan putus, atau jawaban rusak: coba penyedia berikutnya
                Log::warning("Penilaian AI ($provider) gagal untuk laporan #{$lost->id}: ".$e->getMessage());
                $result = null;
            }

            if (is_array($result) && isset($result['matches']) && is_array($result['matches'])) {
                break;
            }
            $result = null;
        }

        $scored = 0;
        foreach ($result['matches'] ?? [] as $row) {
            $match = $matches->firstWhere('found_report_id', (int) ($row['id'] ?? 0));
            if (! $match || ! is_numeric($row['score'] ?? null)) {
                continue;
            }

            $match->ai_score = max(0, min(100, (int) $row['score']));
            $match->ai_reason = mb_substr((string) ($row['reason'] ?? ''), 0, 255);
            $match->score = $match->ai_score;
            $match->save();
            $scored++;
        }

        // Notifikasi yang ditahan tahap aturan dikirim sekarang. Kandidat yang tidak dinilai
        // (semua penyedia gagal atau AI melewatkannya) tetap memakai skor aturan.
        foreach ($matches as $match) {
            app(MatchService::class)->notifyIfStrong($match);
        }

        return $scored;
    }

    // Google AI Studio: endpoint generateContent dengan keluaran JSON sesuai skema
    private function askGemini(array $parts): ?array
    {
        $contents = [];
        foreach ($parts as $part) {
            $contents[] = isset($part['image'])
                ? ['inlineData' => ['mimeType' => 'image/jpeg', 'data' => $part['image']]]
                : ['text' => $part['text']];
        }

        $model = config('findit.ai.gemini.model');
        $response = Http::withHeaders(['x-goog-api-key' => config('findit.ai.gemini.api_key')])
            ->timeout(config('findit.ai.timeout'))
            ->post(config('findit.ai.gemini.url')."/$model:generateContent", [
                'systemInstruction' => ['parts' => [['text' => self::SYSTEM]]],
                'contents' => [['role' => 'user', 'parts' => $contents]],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'responseMimeType' => 'application/json',
                    'responseJsonSchema' => $this->schema(),
                ],
            ])
            ->throw()
            ->json();

        $text = '';
        foreach ($response['candidates'][0]['content']['parts'] ?? [] as $part) {
            // Bagian "thought" adalah proses berpikir model, bukan jawaban
            if (isset($part['text']) && empty($part['thought'])) {
                $text .= $part['text'];
            }
        }

        return $this->decode($text);
    }

    // OpenCode Console: format OpenAI chat completions, hanya teks (tidak semua model bisa membaca foto)
    private function askOpenCode(array $parts): ?array
    {
        $text = collect($parts)->pluck('text')->filter()->implode("\n\n");

        $response = Http::withToken(config('findit.ai.opencode.api_key'))
            ->withUserAgent('FindItCampus/1.0')
            ->timeout(config('findit.ai.timeout'))
            ->post(config('findit.ai.opencode.url'), [
                'model' => config('findit.ai.opencode.model'),
                'temperature' => 0.2,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => self::SYSTEM],
                    ['role' => 'user', 'content' => $text],
                ],
            ])
            ->throw()
            ->json();

        return $this->decode($response['choices'][0]['message']['content'] ?? '');
    }

    // Ambil objek JSON dari jawaban, termasuk bila model membungkusnya dengan ```json
    private function decode(string $text): ?array
    {
        $text = trim($text);
        if (preg_match('/\{.*\}/s', $text, $found)) {
            $text = $found[0];
        }

        $data = json_decode($text, true);

        return is_array($data) ? $data : null;
    }

    private function describe(Report $report): string
    {
        return implode("\n", array_filter([
            'Judul: '.$report->title,
            'Kategori: '.$report->category?->name,
            $report->color ? 'Warna: '.$report->color : null,
            $report->brand ? 'Merek: '.$report->brand : null,
            'Deskripsi: '.$report->description,
            'Waktu: '.$report->event_at->translatedFormat('d F Y H:i'),
            'Lokasi: '.$report->place_name,
        ]));
    }

    // Foto thumbnail 320 px dalam base64 (EXIF sudah dibuang saat unggah)
    private function photo(Report $report): ?string
    {
        if (! $report->photo_thumb || ! Storage::disk('public')->exists($report->photo_thumb)) {
            return null;
        }

        return base64_encode(Storage::disk('public')->get($report->photo_thumb));
    }

    private function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'matches' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'score' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                            'reason' => ['type' => 'string'],
                        ],
                        'required' => ['id', 'score', 'reason'],
                    ],
                ],
            ],
            'required' => ['matches'],
        ];
    }
}
