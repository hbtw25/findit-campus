<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Claim;
use App\Models\Report;
use App\Models\ReportMatch;
use App\Services\ClaimFlow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClaimController extends Controller
{
    public function __construct(private ClaimFlow $flow) {}

    // Klaim yang saya kirim + klaim masuk untuk barang temuan saya
    public function index()
    {
        $sent = Claim::with('report.campusLocation')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        $incoming = Claim::with('report', 'user')
            ->whereHas('report', fn ($q) => $q->where('user_id', Auth::id()))
            ->latest()
            ->get();

        return view('claims.index', compact('sent', 'incoming'));
    }

    public function create(Request $request, Report $report)
    {
        if ($response = $this->guardCanClaim($report)) {
            return $response;
        }

        $report->load('category', 'campusLocation');

        return view('claims.create', [
            'report' => $report,
            'claim' => new Claim(['lost_report_id' => $request->query('lost')]),
            'myLostReports' => $this->myLostReports(),
        ]);
    }

    public function store(Request $request, Report $report)
    {
        if ($response = $this->guardCanClaim($report)) {
            return $response;
        }

        $validated = $request->validate([
            'secret_answer' => 'required|string|min:5|max:500',
            'proof' => 'required|string|min:20|max:1000',
            'lost_report_id' => 'nullable|integer|in:'.$this->myLostReports()->pluck('id')->implode(','),
        ]);

        $validated['report_id'] = $report->id;
        $validated['user_id'] = Auth::id();
        $claim = Claim::create($validated);

        if ($report->status === 'open') {
            $report->update(['status' => 'in_claim']);
        }

        if ($claim->lost_report_id) {
            ReportMatch::where('lost_report_id', $claim->lost_report_id)
                ->where('found_report_id', $report->id)
                ->update(['status' => 'claimed']);
        }

        Alert::send($report->user_id, 'claim_new', 'Ada klaim untuk barang temuanmu', $report->title, route('claims.show', $claim));
        Alert::sendToAdmins('claim_new', 'Klaim baru menunggu verifikasi', $report->title, route('admin.claims.show', $claim));

        return redirect()->route('claims.show', $claim)
            ->with('success', 'Klaim terkirim. Admin akan mencocokkan jawabanmu dengan ciri rahasia dari penemu.');
    }

    public function show(Claim $claim)
    {
        $claim->load('report.campusLocation', 'report.user', 'user', 'lostReport', 'reviewer');
        abort_unless($claim->canBeViewedBy(Auth::user()), 403);

        return view('claims.show', [
            'claim' => $claim,
            'isClaimant' => $claim->user_id === Auth::id(),
            'isFinder' => $claim->report->user_id === Auth::id(),
        ]);
    }

    public function cancel(Claim $claim)
    {
        abort_unless($claim->user_id === Auth::id() && $claim->status === 'pending', 403);

        $this->flow->cancel($claim);

        return back()->with('success', 'Klaim dibatalkan.');
    }

    // Penemu atau admin mengonfirmasi barang sudah diserahkan
    public function returned(Claim $claim)
    {
        $user = Auth::user();
        abort_unless($claim->status === 'approved' && ($claim->report->user_id === $user->id || $user->isAdmin()), 403);

        $this->flow->markReturned($claim);

        return back()->with('success', 'Serah terima tercatat. Laporan ditandai Selesai.');
    }

    // Aturan B9: hanya barang temuan aktif milik orang lain, satu klaim aktif per barang, maksimal 3 klaim menunggu
    private function guardCanClaim(Report $report)
    {
        $user = Auth::user();

        if (! $report->isFound() || ! $report->isOpen() || $report->is_hidden) {
            return redirect()->route('reports.show', $report)->with('error', 'Laporan ini tidak bisa diklaim.');
        }

        if ($report->user_id === $user->id) {
            return redirect()->route('reports.show', $report)->with('error', 'Kamu tidak bisa mengklaim laporanmu sendiri.');
        }

        $existing = Claim::where('report_id', $report->id)->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])->first();
        if ($existing) {
            return redirect()->route('claims.show', $existing)->with('error', 'Kamu sudah punya klaim aktif untuk barang ini.');
        }

        // Barang yang klaimnya sudah disetujui sedang menunggu serah terima, jadi tidak menerima klaim baru
        if ($report->claims()->where('status', 'approved')->exists()) {
            return redirect()->route('reports.show', $report)->with('error', 'Barang ini sedang dalam proses serah terima dengan pengklaim lain.');
        }

        // Klaim yang pernah ditolak tidak bisa diulang, supaya ciri rahasia tidak bisa ditebak berkali-kali
        if (Claim::where('report_id', $report->id)->where('user_id', $user->id)->where('status', 'rejected')->exists()) {
            return redirect()->route('reports.show', $report)->with('error', 'Klaim kamu untuk barang ini sudah pernah ditolak. Hubungi admin bila ada kekeliruan.');
        }

        $pending = Claim::where('user_id', $user->id)->where('status', 'pending')->count();
        if ($pending >= config('findit.claims.max_pending_per_user')) {
            return redirect()->route('claims.index')->with('error', 'Kamu sudah punya 3 klaim yang menunggu verifikasi. Tunggu hasilnya dulu.');
        }

        return null;
    }

    private function myLostReports()
    {
        return Report::where('user_id', Auth::id())
            ->where('type', 'lost')
            ->whereIn('status', ['open', 'in_claim'])
            ->latest()
            ->get();
    }
}
