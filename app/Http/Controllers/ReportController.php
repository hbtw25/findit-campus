<?php

namespace App\Http\Controllers;

use App\Jobs\ScoreMatchesWithAi;
use App\Models\CampusLocation;
use App\Models\Category;
use App\Models\Claim;
use App\Models\Report;
use App\Services\AiMatcher;
use App\Services\ClaimFlow;
use App\Services\MatchService;
use App\Services\PhotoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class ReportController extends Controller
{
    public function __construct(
        private PhotoService $photos,
        private MatchService $matcher,
    ) {}

    // Halaman Jelajah: daftar laporan dengan filter + paginasi
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:60',
            'type' => 'nullable|in:lost,found',
            'category' => 'nullable|integer',
            'location' => 'nullable|integer',
            'days' => 'nullable|integer|in:7,30,90',
        ]);

        $reports = Report::with('category', 'campusLocation')
            ->visible()
            ->filter($filters)
            ->latest('event_at')
            ->paginate(12)
            ->withQueryString();

        return view('reports.index', [
            'reports' => $reports,
            'filters' => $filters,
            'categories' => Category::orderBy('name')->get(),
            'locations' => CampusLocation::orderBy('name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $report = new Report([
            'type' => $request->query('type') === 'found' ? 'found' : 'lost',
            'category_id' => $request->query('category'),
            'campus_location_id' => $request->query('location'),
            'event_at' => now()->startOfMinute(),
        ]);

        return view('reports.form', $this->formData($report, 'Buat Laporan', route('reports.store'), 'POST'));
    }

    public function store(Request $request)
    {
        $todayCount = Report::where('user_id', Auth::id())->where('created_at', '>=', now()->startOfDay())->count();
        if ($todayCount >= config('findit.reports.max_per_day')) {
            return back()->withInput()->with('error', 'Batas 10 laporan per hari sudah tercapai. Coba lagi besok.');
        }

        $validated = $this->validateReport($request);
        $validated['user_id'] = Auth::id();

        if ($request->hasFile('photo')) {
            $validated = array_merge($validated, $this->storePhoto($request));
        }

        $report = Report::create($validated);
        $this->runMatching($report);

        return redirect()->route('reports.show', $report)
            ->with('success', 'Laporan berhasil dibuat dan sudah tampil di Peta Global.');
    }

    public function show(Report $report)
    {
        $report->load('user', 'category', 'campusLocation');
        $user = Auth::user();
        $isOwner = $user && $user->id === $report->user_id;

        // Laporan yang disembunyikan admin hanya bisa dibuka pemiliknya dan admin.
        // Laporan Selesai/Ditutup tetap bisa dibuka lewat tautan sebagai riwayat.
        if ($report->is_hidden && ! ($isOwner || $user?->isAdmin())) {
            abort(404);
        }

        $matches = collect();
        if ($isOwner && $report->isLost()) {
            $matches = $report->foundMatches()->with('foundReport.campusLocation')
                ->shown()->orderByDesc('score')->get();
        } elseif ($isOwner && $report->isFound()) {
            $matches = $report->lostMatches()->with('lostReport.campusLocation')
                ->shown()->orderByDesc('score')->get();
        }

        $myClaim = $user
            ? Claim::where('report_id', $report->id)->where('user_id', $user->id)->latest()->first()
            : null;

        $incomingClaims = $isOwner && $report->isFound()
            ? $report->claims()->with('user')->latest()->get()
            : collect();

        // Tombol klaim disembunyikan bila barang sedang diserahkan ke pengklaim lain
        $hasApprovedClaim = $report->claims()->where('status', 'approved')->exists();

        return view('reports.show', compact('report', 'isOwner', 'matches', 'myClaim', 'incomingClaims', 'hasApprovedClaim'));
    }

    public function edit(Report $report)
    {
        $this->authorizeOwner($report);
        abort_unless($report->status === 'open', 403, 'Laporan hanya bisa diubah saat masih Aktif.');

        return view('reports.form', $this->formData($report, 'Ubah Laporan', route('reports.update', $report), 'PUT'));
    }

    public function update(Request $request, Report $report)
    {
        $this->authorizeOwner($report);
        abort_unless($report->status === 'open', 403, 'Laporan hanya bisa diubah saat masih Aktif.');

        // Jenis laporan (hilang/temuan) tidak bisa diganti setelah dibuat; buat laporan baru bila salah pilih
        $request->merge(['type' => $report->type]);
        $validated = $this->validateReport($request, $report);

        $oldPhoto = [$report->photo, $report->photo_thumb];
        if ($request->hasFile('photo')) {
            // Foto baru disimpan dulu; foto lama baru dihapus setelah penyimpanan berhasil
            $validated = array_merge($validated, $this->storePhoto($request));
        }

        $report->fill($validated);
        $keyChanged = $report->isDirty(['title', 'description', 'category_id', 'lat', 'lng', 'event_at', 'color', 'brand', 'photo']);
        $typeChanged = $report->isDirty('type');
        $report->save();

        if ($request->hasFile('photo')) {
            $this->photos->delete(...$oldPhoto);
        }

        // Jenis berubah (hilang <-> temuan): kandidat lama dari sisi sebelumnya tidak berlaku lagi
        if ($typeChanged) {
            $this->matcher->removeSuggestions($report);
        }

        $this->runMatching($report, $keyChanged);

        return redirect()->route('reports.show', $report)->with('success', 'Laporan berhasil diperbarui.');
    }

    public function destroy(Report $report)
    {
        $this->authorizeOwner($report);

        if ($report->claims()->exists()) {
            return back()->with('error', 'Laporan yang sudah pernah diklaim tidak bisa dihapus. Gunakan tombol Tutup.');
        }

        $this->photos->delete($report->photo, $report->photo_thumb);
        $report->delete();

        return redirect()->route('reports.mine')->with('success', 'Laporan berhasil dihapus.');
    }

    // Menutup laporan (misalnya barang sudah diambil di luar aplikasi)
    public function close(Report $report)
    {
        abort_unless(Auth::id() === $report->user_id || Auth::user()->isAdmin(), 403);
        abort_unless($report->isOpen(), 403, 'Laporan ini sudah tidak aktif.');

        // Saat serah terima sedang berjalan, pelapor harus mengonfirmasi serah terima atau meminta bantuan admin
        if (! Auth::user()->isAdmin() && $report->claims()->where('status', 'approved')->exists()) {
            return back()->with('error', 'Ada klaim yang sudah disetujui. Konfirmasi serah terima di halaman klaim, atau hubungi admin.');
        }

        $report->update(['status' => 'closed']);
        app(ClaimFlow::class)->cancelForClosedReport($report, 'Laporan ditutup oleh pelapor.');
        $this->matcher->removeSuggestions($report);

        return back()->with('success', 'Laporan sudah ditutup dan tidak tampil lagi di peta.');
    }

    // Pemilik laporan hilang menandai barangnya sudah ketemu
    public function resolve(Report $report)
    {
        $this->authorizeOwner($report);
        abort_unless($report->isLost() && $report->isOpen(), 403);

        $report->update(['status' => 'resolved']);
        $this->matcher->removeSuggestions($report);

        return back()->with('success', 'Ikut senang barangmu sudah ketemu! Laporan ditandai Selesai.');
    }

    public function mine()
    {
        $reports = Report::with('category', 'campusLocation')
            ->withCount([
                'foundMatches as lost_candidates' => fn ($q) => $q->shown(),
                'lostMatches as found_candidates' => fn ($q) => $q->shown(),
                'claims as pending_claims' => fn ($q) => $q->where('status', 'pending'),
            ])
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return view('reports.mine', [
            'active' => $reports->filter->isOpen(),
            'history' => $reports->reject->isOpen(),
        ]);
    }

    private function validateReport(Request $request, ?Report $report = null): array
    {
        $type = $request->input('type');
        $needsPhoto = $type === 'found' && ! ($report && $report->photo);

        $validated = $request->validate([
            'type' => 'required|in:lost,found',
            'title' => 'required|string|min:5|max:80',
            'category_id' => 'required|exists:categories,id',
            'campus_location_id' => 'nullable|exists:campus_locations,id',
            'description' => 'required|string|min:20|max:2000',
            'color' => 'nullable|string|max:30',
            'brand' => 'nullable|string|max:40',
            'event_at' => 'required|date|before_or_equal:now|after_or_equal:'.now()->subDays(config('findit.reports.max_age_days'))->toDateString(),
            'location_note' => 'nullable|string|max:120',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'photo' => ($needsPhoto ? 'required' : 'nullable').'|image|mimes:jpg,jpeg,png,webp|dimensions:max_width=6000,max_height=6000|max:'.config('findit.reports.photo_max_kb'),
            'secret_detail' => 'required_if:type,found|nullable|string|min:5|max:255',
        ], [
            'event_at.before_or_equal' => 'Waktu kejadian tidak boleh di masa depan.',
            'event_at.after_or_equal' => 'Waktu kejadian paling lama 60 hari yang lalu.',
            'photo.required' => 'Foto wajib untuk laporan barang temuan.',
            'secret_detail.required_if' => 'Ciri rahasia wajib diisi untuk barang temuan.',
            'lat.required' => 'Tentukan titik lokasi di peta.',
            'lng.required' => 'Tentukan titik lokasi di peta.',
        ]);

        // Titik harus berada di area kampus (PRD aturan B5)
        $campus = config('findit.campus');
        $distance = MatchService::distanceMeters($campus['lat'], $campus['lng'], (float) $validated['lat'], (float) $validated['lng']);
        if ($distance > $campus['radius_m']) {
            back()->withInput()->withErrors(['lat' => 'Titik lokasi harus berada di area kampus Telkom University.'])->throwResponse();
        }

        if ($validated['type'] === 'lost') {
            $validated['secret_detail'] = null;
        }
        unset($validated['photo']);

        return $validated;
    }

    // Foto yang lolos validasi tapi rusak saat diproses GD dikembalikan sebagai pesan validasi, bukan error 500
    private function storePhoto(Request $request): array
    {
        try {
            return $this->photos->store($request->file('photo'));
        } catch (RuntimeException $e) {
            back()->withInput()->withErrors(['photo' => 'Foto tidak bisa dibaca. Coba foto lain dalam format JPG atau PNG.'])->throwResponse();
        }
    }

    // Auto-match tahap 1 langsung; tahap 2 (AI) lewat antrian bila API key tersedia
    private function runMatching(Report $report, bool $resetAi = false): void
    {
        $lostIds = $this->matcher->refreshFor($report, $resetAi);

        if (AiMatcher::enabled()) {
            $lostIds->each(fn ($id) => ScoreMatchesWithAi::dispatch($id));
        }
    }

    private function formData(Report $report, string $title, string $route, string $method): array
    {
        return [
            'report' => $report,
            'title' => $title,
            'route' => $route,
            'method' => $method,
            'categories' => Category::orderBy('name')->get(),
            'locations' => CampusLocation::orderBy('name')->get(),
            'campus' => config('findit.campus'),
        ];
    }

    private function authorizeOwner(Report $report): void
    {
        abort_unless(Auth::id() === $report->user_id, 403, 'Ini bukan laporan milikmu.');
    }
}
