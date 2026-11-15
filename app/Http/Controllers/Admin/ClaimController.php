<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Services\ClaimFlow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClaimController extends Controller
{
    public function __construct(private ClaimFlow $flow) {}

    public function index()
    {
        return view('admin.claims.index');
    }

    // Sumber data JSON untuk DataTables
    public function data()
    {
        $claims = Claim::with('report', 'user')->latest()->get();

        return response()->json([
            'data' => $claims->map(fn (Claim $c) => [
                'id' => $c->id,
                'thumb' => $c->report->thumb_url,
                'item' => $c->report->title,
                'claimant' => $c->user->name,
                'nim' => $c->user->nim,
                'status' => $c->status,
                'status_label' => $c->status_label,
                'created_at' => $c->created_at->format('Y-m-d H:i'),
                'url' => route('admin.claims.show', $c),
            ]),
        ], 200);
    }

    // Halaman tinjau: jawaban pengklaim dibandingkan dengan ciri rahasia dari penemu
    public function show(Claim $claim)
    {
        $claim->load('report.user', 'report.category', 'report.campusLocation', 'user', 'lostReport', 'reviewer');

        $history = Claim::where('user_id', $claim->user_id)
            ->where('id', '!=', $claim->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.claims.show', compact('claim', 'history'));
    }

    public function approve(Request $request, Claim $claim)
    {
        abort_unless($claim->status === 'pending', 403, 'Klaim ini sudah diputuskan.');

        $validated = $request->validate([
            'admin_note' => 'nullable|string|max:255',
        ]);

        $this->flow->approve($claim, Auth::user(), $validated['admin_note'] ?? null);

        return redirect()->route('admin.claims.show', $claim)->with('success', 'Klaim disetujui. Chat serah terima sudah terbuka.');
    }

    public function reject(Request $request, Claim $claim)
    {
        abort_unless($claim->status === 'pending', 403, 'Klaim ini sudah diputuskan.');

        $validated = $request->validate([
            'admin_note' => 'required|string|min:10|max:255',
        ], [
            'admin_note.required' => 'Alasan penolakan wajib diisi.',
            'admin_note.min' => 'Alasan penolakan minimal 10 karakter.',
        ]);

        $this->flow->reject($claim, Auth::user(), $validated['admin_note']);

        return redirect()->route('admin.claims.show', $claim)->with('success', 'Klaim ditolak dan pengklaim sudah diberi tahu.');
    }
}
