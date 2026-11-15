<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Category;
use App\Models\Report;
use App\Models\ReportMatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->category = Category::create(['name' => 'Kunci', 'slug' => 'kunci', 'icon' => 'key']);
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'type' => 'lost',
            'title' => 'Kunci motor Honda',
            'category_id' => $this->category->id,
            'description' => 'Kunci motor Honda dengan gantungan boneka pink, hilang di parkiran KU3.',
            'color' => 'Hitam',
            'brand' => 'Honda',
            'event_at' => now()->subHours(3)->format('Y-m-d H:i'),
            'lat' => -6.97295,
            'lng' => 107.62951,
        ], $override);
    }

    public function test_peta_global_mengembalikan_json_tanpa_ciri_rahasia(): void
    {
        Report::factory()->found()->create(['category_id' => $this->category->id]);

        $response = $this->getJson('/ajax/map/reports')->assertOk()->assertJsonCount(1, 'data');

        $response->assertJsonStructure(['count', 'data' => [['id', 'type', 'title', 'lat', 'lng', 'thumb', 'url']]]);
        $this->assertStringNotContainsString('loker', $response->getContent());
    }

    public function test_tamu_harus_login_untuk_melapor(): void
    {
        $this->get('/reports/create')->assertRedirect('/login');
    }

    public function test_laporan_hilang_tersimpan(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/reports', $this->payload())->assertRedirect();

        $this->assertDatabaseHas('reports', ['title' => 'Kunci motor Honda', 'type' => 'lost', 'status' => 'open', 'user_id' => $user->id]);
    }

    public function test_laporan_temuan_wajib_foto_dan_ciri_rahasia(): void
    {
        $this->actingAs(User::factory()->create())
            ->from('/reports/create')
            ->post('/reports', $this->payload(['type' => 'found']))
            ->assertSessionHasErrors(['photo', 'secret_detail']);
    }

    public function test_laporan_temuan_dengan_foto_membuat_thumbnail(): void
    {
        $this->actingAs(User::factory()->create())->post('/reports', $this->payload([
            'type' => 'found',
            'photo' => UploadedFile::fake()->image('kunci.jpg', 900, 600),
            'secret_detail' => 'Ada kunci loker nomor 17',
        ]))->assertRedirect();

        $report = Report::first();
        $this->assertNotNull($report->photo_thumb);
        Storage::disk('public')->assertExists([$report->photo, $report->photo_thumb]);
        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($report->photo_thumb));
        $this->assertSame([320, 320], [$w, $h]);
    }

    public function test_titik_di_luar_kampus_ditolak(): void
    {
        $this->actingAs(User::factory()->create())
            ->from('/reports/create')
            ->post('/reports', $this->payload(['lat' => -6.9147, 'lng' => 107.6098])) // pusat kota Bandung
            ->assertSessionHasErrors('lat');

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_waktu_di_masa_depan_ditolak(): void
    {
        $this->actingAs(User::factory()->create())
            ->from('/reports/create')
            ->post('/reports', $this->payload(['event_at' => now()->addDay()->format('Y-m-d H:i')]))
            ->assertSessionHasErrors('event_at');
    }

    public function test_laporan_baru_langsung_dicocokkan_dan_pemilik_diberi_notifikasi(): void
    {
        $owner = User::factory()->create();
        $lost = Report::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $this->category->id,
            'event_at' => now()->subHours(6),
        ]);

        // Penemu melapor barang temuan yang mirip, 60 m dari lokasi hilang
        $this->actingAs(User::factory()->create())->post('/reports', $this->payload([
            'type' => 'found',
            'title' => 'Kunci motor Honda gantungan boneka',
            'lat' => -6.97240,
            'lng' => 107.62951,
            'photo' => UploadedFile::fake()->image('kunci.jpg'),
            'secret_detail' => 'Ada kunci loker nomor 17',
        ]));

        $match = ReportMatch::where('lost_report_id', $lost->id)->first();
        $this->assertNotNull($match);
        $this->assertGreaterThanOrEqual(60, $match->score);
        $alert = Alert::where('user_id', $owner->id)->where('type', 'match')->first();
        $this->assertNotNull($alert);
        // URL disimpan relatif agar tetap benar di host mana pun (localhost / 127.0.0.1 / IP LAN)
        $this->assertSame("/reports/{$lost->id}#kandidat", $alert->url);
    }

    public function test_jenis_laporan_tidak_bisa_diganti_saat_diubah(): void
    {
        $owner = User::factory()->create();
        $lost = Report::factory()->create(['user_id' => $owner->id, 'category_id' => $this->category->id]);

        // Permintaan mengganti jenis diabaikan; data lain tetap tersimpan
        $this->actingAs($owner)->put("/reports/{$lost->id}", $this->payload([
            'type' => 'found',
            'title' => 'Kunci motor Honda Beat',
        ]))->assertRedirect();

        $lost->refresh();
        $this->assertSame('lost', $lost->type);
        $this->assertSame('Kunci motor Honda Beat', $lost->title);
    }

    public function test_filter_jelajah_yang_tidak_valid_tidak_membuat_error(): void
    {
        $this->get('/reports?q[]=x')->assertRedirect();
        $this->get('/reports?type=semua')->assertRedirect();
        $this->get('/reports?q=dompet&type=found')->assertOk();
    }

    public function test_gambar_berdimensi_terlalu_besar_ditolak(): void
    {
        $this->actingAs(User::factory()->create())
            ->from('/reports/create')
            ->post('/reports', $this->payload([
                'type' => 'found',
                'photo' => UploadedFile::fake()->image('besar.jpg', 6500, 100),
                'secret_detail' => 'Ada kunci loker nomor 17',
            ]))
            ->assertSessionHasErrors('photo');
    }

    public function test_admin_menampilkan_lagi_laporan_menghitung_ulang_kandidat(): void
    {
        $lost = Report::factory()->create(['category_id' => $this->category->id]);
        $found = Report::factory()->found()->create(['category_id' => $this->category->id, 'is_hidden' => true]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post("/admin/reports/{$found->id}/toggle-hidden")->assertRedirect();

        $this->assertFalse($found->fresh()->is_hidden);
        $this->assertTrue(ReportMatch::where('lost_report_id', $lost->id)->where('found_report_id', $found->id)->exists());
    }

    public function test_orang_lain_tidak_bisa_mengubah_laporan(): void
    {
        $report = Report::factory()->create(['category_id' => $this->category->id]);

        $this->actingAs(User::factory()->create())
            ->put("/reports/{$report->id}", $this->payload())
            ->assertForbidden();
    }
}
