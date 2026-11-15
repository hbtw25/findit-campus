<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Claim;
use App\Models\Report;
use App\Models\ReportMatch;
use App\Models\User;

// Aturan perpindahan status klaim (PRD bagian 5.3) dipakai oleh controller pengguna dan admin
class ClaimFlow
{
    public function __construct(private MatchService $matcher) {}

    public function approve(Claim $claim, User $admin, ?string $note = null): void
    {
        $claim->update([
            'status' => 'approved',
            'admin_note' => $note,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        // Klaim lain yang masih menunggu untuk barang yang sama otomatis ditolak (aturan B10)
        $others = Claim::where('report_id', $claim->report_id)
            ->where('id', '!=', $claim->id)
            ->where('status', 'pending')
            ->get();

        foreach ($others as $other) {
            $other->update([
                'status' => 'rejected',
                'admin_note' => 'Barang ini sudah diverifikasi milik pengklaim lain.',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);
            Alert::send($other->user_id, 'claim_update', 'Klaim kamu ditolak', $claim->report->title, route('claims.show', $other));
        }

        $claim->report->update(['status' => 'in_claim']);

        Alert::send($claim->user_id, 'claim_update', 'Klaim kamu disetujui', 'Atur serah terima "'.$claim->report->title.'" lewat chat.', route('claims.show', $claim));
        Alert::send($claim->report->user_id, 'claim_update', 'Klaim atas barang temuanmu disetujui admin', 'Silakan atur serah terima lewat chat.', route('claims.show', $claim));
    }

    public function reject(Claim $claim, User $admin, string $note): void
    {
        $claim->update([
            'status' => 'rejected',
            'admin_note' => $note,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $this->syncReportStatus($claim->report);

        Alert::send($claim->user_id, 'claim_update', 'Klaim kamu ditolak', $note, route('claims.show', $claim));
    }

    public function cancel(Claim $claim): void
    {
        $claim->update(['status' => 'cancelled']);
        $this->syncReportStatus($claim->report);
    }

    // Serah terima selesai: laporan temuan dan laporan hilang terkait menjadi Selesai
    public function markReturned(Claim $claim): void
    {
        $claim->update(['status' => 'returned', 'returned_at' => now()]);

        $claim->report->update(['status' => 'resolved']);
        $this->matcher->removeSuggestions($claim->report);

        if ($claim->lostReport) {
            $claim->lostReport->update(['status' => 'resolved']);
            $this->matcher->removeSuggestions($claim->lostReport);

            ReportMatch::where('lost_report_id', $claim->lost_report_id)
                ->where('found_report_id', $claim->report_id)
                ->update(['status' => 'claimed']);
        }

        Alert::send($claim->user_id, 'claim_update', 'Barang sudah diserahkan', 'Terima kasih sudah memakai FindIt Campus.', route('claims.show', $claim));
        Alert::send($claim->report->user_id, 'claim_update', 'Serah terima tercatat selesai', 'Terima kasih sudah membantu mengembalikan barang.', route('claims.show', $claim));
    }

    // Laporan kembali Aktif bila tidak ada lagi klaim yang menunggu atau disetujui
    public function syncReportStatus(Report $report): void
    {
        if (! in_array($report->status, ['open', 'in_claim'])) {
            return;
        }

        $hasActive = $report->claims()->whereIn('status', ['pending', 'approved'])->exists();
        $report->update(['status' => $hasActive ? 'in_claim' : 'open']);
    }
}
