<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Support\Facades\Auth;

class ShareController extends Controller
{
    // Tautan pendek /r/{kode} untuk dibagikan ke media sosial
    public function short(string $code)
    {
        $report = Report::where('share_code', strtoupper($code))->firstOrFail();

        return redirect()->route('reports.show', $report);
    }

    // Poster A5 siap cetak + gambar story 1080x1920
    public function poster(Report $report)
    {
        $user = Auth::user();
        abort_if($report->is_hidden && ! ($user && ($user->id === $report->user_id || $user->isAdmin())), 404);

        $report->load('category', 'campusLocation');

        return view('share.poster', compact('report'));
    }
}
