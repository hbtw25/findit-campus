<?php

namespace App\Http\Controllers;

use App\Models\ReportMatch;
use Illuminate\Support\Facades\Auth;

class MatchController extends Controller
{
    // "Bukan barang saya": kandidat tidak ditampilkan lagi
    public function dismiss(ReportMatch $match)
    {
        $match->load('lostReport', 'foundReport');
        $owners = [$match->lostReport->user_id, $match->foundReport->user_id];
        abort_unless(in_array(Auth::id(), $owners), 403);

        $match->update(['status' => 'dismissed']);

        return back()->with('success', 'Kandidat disembunyikan. Kami akan terus mencarikan yang lain.');
    }
}
