<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use App\Services\MatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_jarak_haversine_mendekati_nilai_sebenarnya(): void
    {
        // KU3 ke Learning Center di peta whereis: sekitar 150 m
        $d = MatchService::distanceMeters(-6.97295, 107.62951, -6.97399, 107.63040);

        $this->assertEqualsWithDelta(152, $d, 10);
    }

    public function test_barang_mirip_dan_dekat_mendapat_skor_lebih_tinggi(): void
    {
        $svc = new MatchService;
        $lost = Report::factory()->make(['title' => 'Kunci motor Honda boneka pink', 'event_at' => now()->subHours(10)]);
        $similar = Report::factory()->found()->make(['title' => 'Kunci motor Honda dengan boneka', 'event_at' => now()->subHours(8)]);
        $different = Report::factory()->found()->make([
            'title' => 'Kunci rumah perak',
            'description' => 'Kunci rumah warna perak tanpa gantungan.',
            'color' => 'Perak',
            'brand' => null,
            'event_at' => now()->subDays(12),
        ]);

        $near = $svc->ruleScore($lost, $similar, 80);
        $far = $svc->ruleScore($lost, $different, 900);

        $this->assertGreaterThan($far, $near);
        $this->assertGreaterThanOrEqual(80, $near);
        $this->assertLessThan(40, $far);
    }

    public function test_tidak_mencocokkan_laporan_milik_sendiri_atau_kategori_lain(): void
    {
        $user = User::factory()->create();
        $keys = Category::create(['name' => 'Kunci', 'slug' => 'kunci', 'icon' => 'key']);
        $bags = Category::create(['name' => 'Tas', 'slug' => 'tas', 'icon' => 'backpack2']);

        $lost = Report::factory()->create(['user_id' => $user->id, 'category_id' => $keys->id]);
        Report::factory()->found()->create(['user_id' => $user->id, 'category_id' => $keys->id]); // milik sendiri
        Report::factory()->found()->create(['category_id' => $bags->id]); // kategori beda

        $this->assertCount(0, app(MatchService::class)->refreshFor($lost));
    }

    public function test_kata_umum_tidak_dihitung(): void
    {
        $report = Report::factory()->make(['title' => 'Dompet yang hilang di gedung', 'description' => 'warna coklat', 'color' => null, 'brand' => null]);

        $this->assertEqualsCanonicalizing(['dompet', 'coklat'], (new MatchService)->tokens($report));
    }
}
