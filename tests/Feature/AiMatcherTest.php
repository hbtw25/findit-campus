<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Category;
use App\Models\Report;
use App\Models\ReportMatch;
use App\Models\User;
use App\Services\AiMatcher;
use App\Services\MatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AiMatcherTest extends TestCase
{
    use RefreshDatabase;

    private Report $lost;

    private Report $found;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'findit.ai.providers' => ['gemini', 'opencode'],
            'findit.ai.gemini.api_key' => 'kunci-uji-gemini',
            'findit.ai.opencode.api_key' => 'kunci-uji-opencode',
        ]);

        $this->lost = Report::factory()->create();
        $this->found = Report::factory()->found()->create();
    }

    private function geminiReply(int $score, string $reason = 'Merek, warna, dan gantungan sama.'): array
    {
        $json = json_encode(['matches' => [['id' => $this->found->id, 'score' => $score, 'reason' => $reason]]]);

        return ['candidates' => [['content' => ['parts' => [['text' => $json]]]]]];
    }

    public function test_notifikasi_ditahan_sampai_ai_menilai(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->geminiReply(90))]);

        app(MatchService::class)->refreshFor($this->found);
        $this->assertGreaterThanOrEqual(60, ReportMatch::first()->rule_score);
        $this->assertSame(0, Alert::count());

        $this->assertSame(1, app(AiMatcher::class)->scoreLostReport($this->lost));

        $match = ReportMatch::first();
        $this->assertSame(90, $match->ai_score);
        $this->assertSame(90, $match->score);
        $this->assertSame('Merek, warna, dan gantungan sama.', $match->ai_reason);
        $this->assertSame(1, Alert::where('user_id', $this->lost->user_id)->where('type', 'match')->count());
    }

    public function test_ai_mencegah_notifikasi_palsu_dari_skor_aturan(): void
    {
        // Kasus nyata saat uji: skor aturan 68, AI memberi 10 karena warna dan merek berbeda
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->geminiReply(10, 'Warna dan merek berbeda.'))]);

        app(MatchService::class)->refreshFor($this->found);
        app(AiMatcher::class)->scoreLostReport($this->lost);

        $this->assertSame(10, ReportMatch::first()->score);
        $this->assertSame(0, Alert::count());

        // Kandidat yang dinilai AI di bawah ambang tidak ditampilkan ke pemilik
        $this->actingAs($this->lost->user)->get(route('reports.show', $this->lost))
            ->assertOk()
            ->assertViewHas('matches', fn ($matches) => $matches->isEmpty());
        $this->get('/alerts')->assertOk()->assertViewHas('candidates', fn ($candidates) => $candidates->isEmpty());
    }

    public function test_opencode_dipakai_bila_gemini_gagal(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'Kuota habis']], 429),
            'opencode.ai/*' => Http::response(['choices' => [['message' => [
                'content' => "```json\n".json_encode(['matches' => [['id' => $this->found->id, 'score' => 77, 'reason' => 'Cocok.']]])."\n```",
            ]]]]),
        ]);

        app(MatchService::class)->refreshFor($this->found);

        $this->assertSame(1, app(AiMatcher::class)->scoreLostReport($this->lost));
        $this->assertSame(77, ReportMatch::first()->ai_score);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'opencode.ai')
            && $request->hasHeader('Authorization', 'Bearer kunci-uji-opencode'));
    }

    public function test_semua_ai_gagal_tetap_memakai_skor_aturan(): void
    {
        Http::fake(['*' => Http::response('Server sibuk', 503)]);

        app(MatchService::class)->refreshFor($this->found);

        $this->assertSame(0, app(AiMatcher::class)->scoreLostReport($this->lost));

        $match = ReportMatch::first();
        $this->assertNull($match->ai_score);
        $this->assertSame($match->rule_score, $match->score);
        $this->assertSame(1, Alert::count());
    }

    public function test_ciri_rahasia_dan_nama_pelapor_tidak_dikirim_ke_ai(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->geminiReply(80))]);

        app(MatchService::class)->refreshFor($this->found);
        app(AiMatcher::class)->scoreLostReport($this->lost);

        Http::assertSent(fn (Request $request) => $request->hasHeader('x-goog-api-key', 'kunci-uji-gemini')
            && str_contains($request->body(), 'Kunci motor Honda')
            && ! str_contains($request->body(), 'loker nomor 17')
            && ! str_contains($request->body(), $this->found->user->name)
            && ! str_contains($request->body(), $this->lost->user->name));
    }

    public function test_laporan_temuan_baru_langsung_dinilai_ai_lewat_antrian(): void
    {
        // phpunit.xml memakai QUEUE_CONNECTION=sync, jadi job ScoreMatchesWithAi berjalan saat itu juga
        Http::fake(['generativelanguage.googleapis.com/*' => function (Request $request) {
            $found = Report::where('type', 'found')->latest('id')->first();

            return Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode([
                'matches' => [['id' => $found->id, 'score' => 88, 'reason' => 'Ciri fisik sama.']],
            ])]]]]]]);
        }]);

        $this->found->delete();
        Storage::fake('public');

        $this->actingAs(User::factory()->create())->post('/reports', [
            'type' => 'found',
            'title' => 'Kunci motor Honda gantungan boneka',
            'category_id' => Category::where('slug', 'kunci')->value('id'),
            'description' => 'Ditemukan kunci motor Honda dengan gantungan boneka pink di parkiran.',
            'color' => 'Hitam',
            'brand' => 'Honda',
            'secret_detail' => 'Ada kunci loker nomor 17',
            'photo' => UploadedFile::fake()->image('kunci.jpg', 900, 600),
            'event_at' => now()->subHour()->format('Y-m-d H:i'),
            'lat' => -6.97295,
            'lng' => 107.62951,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $match = ReportMatch::where('lost_report_id', $this->lost->id)->first();
        $this->assertSame(88, $match->ai_score);
        $this->assertSame(1, Alert::where('user_id', $this->lost->user_id)->count());

        // Foto ikut dikirim sebagai gambar supaya AI bisa membandingkan ciri fisik
        Http::assertSent(fn (Request $request) => str_contains($request->body(), '"inlineData"'));
    }
}
