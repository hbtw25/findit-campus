<?php

namespace App\Services;

use Anthropic\Client;
use App\Models\Report;
use App\Models\ReportMatch;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

// Auto-match tahap 2 (opsional): Claude menilai ulang kandidat dengan membaca deskripsi DAN foto.
// Ciri rahasia (secret_detail) sengaja tidak pernah dikirim agar tidak bocor lewat teks alasan.
class AiMatcher
{
    public static function enabled(): bool
    {
        return filled(config('findit.ai.api_key'));
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

        $content = [
            ['type' => 'text', 'text' => "LAPORAN BARANG HILANG\n".$this->describe($lost)],
        ];
        if ($image = $this->imageBlock($lost)) {
            $content[] = $image;
        }

        foreach ($matches as $match) {
            $found = $match->foundReport;
            $content[] = [
                'type' => 'text',
                'text' => "KANDIDAT id={$found->id} (jarak {$match->distance_m} m dari lokasi hilang)\n".$this->describe($found),
            ];
            if ($image = $this->imageBlock($found)) {
                $content[] = $image;
            }
        }

        $content[] = ['type' => 'text', 'text' => 'Beri skor 0-100 untuk setiap kandidat: seberapa yakin kandidat itu adalah barang yang hilang. '
            .'Bandingkan jenis barang, warna, merek, ciri fisik yang terlihat di foto, waktu, dan jarak. '
            .'Tulis alasan singkat dalam bahasa Indonesia, maksimal 140 karakter, tanpa menyebut nama orang.'];

        try {
            $client = new Client(apiKey: config('findit.ai.api_key'));

            $message = $client->messages->create(
                model: config('findit.ai.model'),
                maxTokens: 4096,
                system: 'Kamu membantu layanan barang hilang di kampus. Nilai kecocokan secara hati-hati dan jujur; '
                    .'skor tinggi hanya bila ciri-cirinya benar-benar sesuai.',
                messages: [['role' => 'user', 'content' => $content]],
                outputConfig: [
                    'effort' => 'low',
                    'format' => ['type' => 'json_schema', 'schema' => $this->schema()],
                ],
            );

            if ($message->stopReason === 'refusal') {
                Log::warning('AI menolak menilai laporan #'.$lost->id);

                return 0;
            }

            $text = '';
            foreach ($message->content as $block) {
                if ($block->type === 'text') {
                    $text .= $block->text;
                }
            }

            $result = json_decode($text, true);
        } catch (Throwable $e) {
            // Bila AI gagal, sistem tetap memakai skor aturan
            Log::warning('Penilaian AI gagal untuk laporan #'.$lost->id.': '.$e->getMessage());

            return 0;
        }

        $scored = 0;
        foreach ($result['matches'] ?? [] as $row) {
            $match = $matches->firstWhere('found_report_id', (int) ($row['id'] ?? 0));
            if (! $match) {
                continue;
            }

            $match->ai_score = max(0, min(100, (int) $row['score']));
            $match->ai_reason = mb_substr((string) ($row['reason'] ?? ''), 0, 255);
            $match->score = $match->ai_score;
            $match->save();
            $scored++;

            app(MatchService::class)->notifyIfStrong($match);
        }

        return $scored;
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

    private function imageBlock(Report $report): ?array
    {
        if (! $report->photo_thumb || ! Storage::disk('public')->exists($report->photo_thumb)) {
            return null;
        }

        return [
            'type' => 'image',
            'source' => [
                'type' => 'base64',
                'mediaType' => 'image/jpeg',
                'data' => base64_encode(Storage::disk('public')->get($report->photo_thumb)),
            ],
        ];
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
                            'score' => ['type' => 'integer'],
                            'reason' => ['type' => 'string'],
                        ],
                        'required' => ['id', 'score', 'reason'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['matches'],
            'additionalProperties' => false,
        ];
    }
}
