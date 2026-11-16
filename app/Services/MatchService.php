<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Report;
use App\Models\ReportMatch;
use Illuminate\Support\Collection;

// Auto-match tahap 1: mencari kandidat laporan jenis lawan dan memberi skor aturan 0-100.
// Bobot: jarak 40, waktu 25, kemiripan teks 35 (PRD bagian 8.2).
class MatchService
{
    // Kata umum bahasa Indonesia yang tidak membantu membedakan barang
    private const STOPWORDS = [
        'yang', 'dan', 'di', 'ke', 'dari', 'ini', 'itu', 'ada', 'dengan', 'untuk', 'pada', 'saya',
        'aku', 'kami', 'kita', 'nya', 'warna', 'barang', 'hilang', 'ditemukan', 'menemukan', 'temuan',
        'tolong', 'mohon', 'bantu', 'sekitar', 'dekat', 'depan', 'belakang', 'dalam', 'luar', 'atas',
        'bawah', 'sudah', 'belum', 'masih', 'juga', 'atau', 'tapi', 'karena', 'jam', 'tadi', 'pagi',
        'siang', 'sore', 'malam', 'hari', 'kemarin', 'gedung', 'lantai', 'ruang', 'kelas', 'merk',
        'merek', 'kondisi', 'isi', 'berisi', 'ukuran', 'sebuah', 'satu', 'buah', 'the', 'and', 'with',
    ];

    /**
     * Hitung ulang kandidat untuk satu laporan.
     * Mengembalikan id laporan hilang yang kandidatnya berubah (untuk penilaian AI).
     *
     * @return Collection<int, int>
     */
    public function refreshFor(Report $report, bool $resetAi = false): Collection
    {
        $report->loadMissing('category', 'campusLocation');

        // Laporan yang sudah tidak aktif tidak dicocokkan lagi
        if (! $report->isOpen() || $report->is_hidden) {
            $this->removeSuggestions($report);

            return collect();
        }

        $isLost = $report->isLost();
        $max = config('findit.match');

        $from = $isLost ? $report->event_at->copy()->subDay() : $report->event_at->copy()->subDays($max['max_days']);
        $to = $isLost ? $report->event_at->copy()->addDays($max['max_days']) : $report->event_at->copy()->addDay();

        $candidates = Report::with('category', 'campusLocation')
            ->visible()
            ->where('type', $isLost ? 'found' : 'lost')
            ->where('category_id', $report->category_id)
            ->where('user_id', '!=', $report->user_id)
            ->whereBetween('event_at', [$from, $to])
            ->get();

        $scored = $candidates->map(function (Report $other) use ($report, $isLost) {
            $lost = $isLost ? $report : $other;
            $found = $isLost ? $other : $report;
            $distance = self::distanceMeters($lost->lat, $lost->lng, $found->lat, $found->lng);

            return [
                'lost' => $lost,
                'found' => $found,
                'distance' => (int) round($distance),
                'score' => $this->ruleScore($lost, $found, $distance),
            ];
        })
            ->filter(fn ($row) => $row['distance'] <= $max['max_distance_m'] && $row['score'] >= $max['min_score'])
            ->sortByDesc('score')
            ->take($max['max_candidates'])
            ->values();

        $keptIds = [];
        $affectedLost = collect();

        foreach ($scored as $row) {
            $match = ReportMatch::firstOrNew([
                'lost_report_id' => $row['lost']->id,
                'found_report_id' => $row['found']->id,
            ]);

            if ($resetAi) {
                $match->ai_score = null;
                $match->ai_reason = null;
            }

            $match->rule_score = $row['score'];
            $match->distance_m = $row['distance'];
            $match->score = $match->ai_score ?? $row['score'];
            $match->status = $match->status ?? 'suggested';
            $match->save();

            $keptIds[] = $match->id;
            $affectedLost->push($row['lost']->id);

            // Bila AI aktif, notifikasi menunggu penilaian AI agar skor aturan yang keliru
            // (misalnya tumbler beda warna dan merek) tidak sempat memicu alert palsu
            if (! AiMatcher::enabled() || $match->ai_score !== null) {
                $this->notifyIfStrong($match);
            }
        }

        // Hapus kandidat lama yang sudah tidak relevan (kecuali yang sudah ditolak/diklaim)
        ReportMatch::where($isLost ? 'lost_report_id' : 'found_report_id', $report->id)
            ->where('status', 'suggested')
            ->whereNotIn('id', $keptIds)
            ->delete();

        return $affectedLost->unique()->values();
    }

    // Kirim notifikasi ke pemilik barang hilang bila skor sudah cukup tinggi (sekali saja)
    public function notifyIfStrong(ReportMatch $match): void
    {
        if ($match->status !== 'suggested' || $match->notified_at || $match->score < config('findit.match.alert_score')) {
            return;
        }

        $match->loadMissing('lostReport', 'foundReport.campusLocation');

        Alert::send(
            $match->lostReport->user_id,
            'match',
            'Ada barang temuan yang diduga milikmu',
            $match->foundReport->title.' · skor '.$match->score.' · '.$match->foundReport->place_name,
            route('reports.show', $match->lostReport).'#kandidat'
        );

        $match->notified_at = now();
        $match->save();
    }

    public function removeSuggestions(Report $report): void
    {
        ReportMatch::where(function ($q) use ($report) {
            $q->where('lost_report_id', $report->id)->orWhere('found_report_id', $report->id);
        })->where('status', 'suggested')->delete();
    }

    public function ruleScore(Report $lost, Report $found, float $distance): int
    {
        // Jarak (maks 40)
        $distanceScore = match (true) {
            $distance <= 100 => 40,
            $distance <= 300 => 30,
            $distance <= 600 => 20,
            $distance <= 1000 => 10,
            default => 0,
        };

        // Selisih waktu (maks 25)
        $days = abs($found->event_at->diffInHours($lost->event_at)) / 24;
        $timeScore = match (true) {
            $days <= 1 => 25,
            $days <= 3 => 18,
            $days <= 7 => 10,
            $days <= 30 => 4,
            default => 0,
        };

        // Kemiripan teks (maks 35)
        $a = $this->tokens($lost);
        $b = $this->tokens($found);
        $overlap = 0;
        if (count($a) && count($b)) {
            $overlap = count(array_intersect($a, $b)) / min(count($a), count($b));
        }
        $textScore = (int) round($overlap * 25);
        $textScore += $this->sameValue($lost->color, $found->color) ? 5 : 0;
        $textScore += $this->sameValue($lost->brand, $found->brand) ? 5 : 0;

        return min(100, $distanceScore + $timeScore + min(35, $textScore));
    }

    /**
     * @return array<int, string>
     */
    public function tokens(Report $report): array
    {
        $text = mb_strtolower(implode(' ', [$report->title, $report->description, $report->color, $report->brand]));
        $words = preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        $words = array_filter($words, fn ($w) => mb_strlen($w) >= 3 && ! in_array($w, self::STOPWORDS, true));

        return array_values(array_unique($words));
    }

    private function sameValue(?string $a, ?string $b): bool
    {
        $a = mb_strtolower(trim((string) $a));
        $b = mb_strtolower(trim((string) $b));

        if ($a === '' || $b === '') {
            return false;
        }

        return str_contains($a, $b) || str_contains($b, $a);
    }

    // Jarak dua titik koordinat dalam meter (rumus haversine)
    public static function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $h = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earth * asin(min(1, sqrt($h)));
    }
}
