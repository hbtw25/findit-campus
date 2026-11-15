<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\MatchService;

class ReportController extends Controller
{
    public function index()
    {
        return view('admin.reports.index');
    }

    // Sumber data JSON untuk DataTables
    public function data()
    {
        $reports = Report::with('user', 'category', 'campusLocation')->latest()->get();

        return response()->json([
            'data' => $reports->map(fn (Report $r) => [
                'id' => $r->id,
                'thumb' => $r->thumb_url,
                'title' => $r->title,
                'type' => $r->type,
                'type_label' => $r->type_label,
                'category' => $r->category->name,
                'place' => $r->campusLocation?->short_name ?? '-',
                'reporter' => $r->user->name,
                'status' => $r->status,
                'status_label' => $r->status_label,
                'is_hidden' => $r->is_hidden,
                'created_at' => $r->created_at->format('Y-m-d H:i'),
                'url' => route('reports.show', $r),
                'toggle_url' => route('admin.reports.toggleHidden', $r),
                'close_url' => route('admin.reports.close', $r),
            ]),
        ], 200);
    }

    public function toggleHidden(Report $report, MatchService $matcher)
    {
        $report->update(['is_hidden' => ! $report->is_hidden]);

        if ($report->is_hidden) {
            $matcher->removeSuggestions($report);
        }

        return back()->with('success', $report->is_hidden
            ? 'Laporan disembunyikan dari peta dan pencarian.'
            : 'Laporan ditampilkan kembali.');
    }

    public function close(Report $report, MatchService $matcher)
    {
        $report->update(['status' => 'closed']);
        $report->claims()->where('status', 'pending')->update([
            'status' => 'cancelled',
            'admin_note' => 'Laporan ditutup admin.',
        ]);
        $matcher->removeSuggestions($report);

        return back()->with('success', 'Laporan ditutup.');
    }
}
