<?php

namespace App\Http\Controllers;

use App\Models\ReportMatch;
use Illuminate\Support\Facades\Auth;

class MatchController extends Controller
{
    // "Bukan barang saya": hanya pemilik laporan hilang yang boleh menyembunyikan kandidat.
    // Penemu tidak diberi tombol ini, supaya kandidat tidak hilang dari pandangan pemilik barang.
    public function dismiss(ReportMatch $match)
    {
        $match->load('lostReport');
        abort_unless(Auth::id() === $match->lostReport->user_id, 403);
        abort_unless($match->status === 'suggested', 403, 'Kandidat ini sudah tidak aktif.');

        $match->update(['status' => 'dismissed']);

        return back()->with('success', 'Kandidat disembunyikan. Sistem tetap mencarikan barang temuan lain yang cocok.');
    }
}
