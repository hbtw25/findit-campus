<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\ReportMatch;
use Illuminate\Support\Facades\Auth;

class AlertController extends Controller
{
    public function index()
    {
        $alerts = Auth::user()->alerts()->latest()->paginate(20);

        // Kandidat cocok untuk laporan hilang milik saya yang masih aktif
        $candidates = ReportMatch::with('lostReport', 'foundReport.campusLocation')
            ->shown()
            ->whereHas('lostReport', fn ($q) => $q->where('user_id', Auth::id())->whereIn('status', ['open', 'in_claim']))
            ->whereHas('foundReport', fn ($q) => $q->visible())
            ->orderByDesc('score')
            ->get()
            ->groupBy('lost_report_id');

        return view('alerts.index', compact('alerts', 'candidates'));
    }

    // Tandai sudah dibaca lalu buka halaman tujuannya
    public function open(Alert $alert)
    {
        abort_unless($alert->user_id === Auth::id(), 403);

        $alert->update(['read_at' => $alert->read_at ?? now()]);

        return redirect($alert->url ?: route('alerts.index'));
    }

    public function readAll()
    {
        Auth::user()->alerts()->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    // Dipakai ikon lonceng di navbar (jQuery AJAX)
    public function feed()
    {
        $user = Auth::user();

        return response()->json([
            'unread' => $user->alerts()->whereNull('read_at')->count(),
            'data' => $user->alerts()->latest()->take(6)->get()->map(fn (Alert $a) => [
                'title' => $a->title,
                'body' => $a->body,
                'icon' => $a->icon,
                'read' => $a->read_at !== null,
                'time' => $a->created_at->diffForHumans(),
                'url' => route('alerts.open', $a),
            ]),
        ], 200);
    }
}
