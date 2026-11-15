<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Claim;
use App\Models\Report;
use App\Models\ReportMatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClaimFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $finder;

    private User $owner;

    private User $admin;

    private Report $found;

    protected function setUp(): void
    {
        parent::setUp();
        $category = Category::create(['name' => 'Kunci', 'slug' => 'kunci', 'icon' => 'key']);
        $this->finder = User::factory()->create();
        $this->owner = User::factory()->create();
        $this->admin = User::factory()->admin()->create();
        $this->found = Report::factory()->found()->create(['user_id' => $this->finder->id, 'category_id' => $category->id]);
    }

    private function claimPayload(array $override = []): array
    {
        return array_merge([
            'secret_answer' => 'Ada kunci loker nomor 17',
            'proof' => 'Saya parkir motor di KU3 dan kunci ini milik saya, bisa tunjukkan STNK.',
        ], $override);
    }

    public function test_pemilik_bisa_mengajukan_klaim(): void
    {
        $this->actingAs($this->owner)->post("/reports/{$this->found->id}/claims", $this->claimPayload())->assertRedirect();

        $this->assertDatabaseHas('claims', ['report_id' => $this->found->id, 'user_id' => $this->owner->id, 'status' => 'pending']);
        $this->assertSame('in_claim', $this->found->fresh()->status);
    }

    public function test_tidak_bisa_mengklaim_laporan_sendiri(): void
    {
        $this->actingAs($this->finder)->post("/reports/{$this->found->id}/claims", $this->claimPayload());

        $this->assertDatabaseCount('claims', 0);
    }

    public function test_jawaban_terlalu_pendek_ditolak(): void
    {
        $this->actingAs($this->owner)
            ->from("/reports/{$this->found->id}/claims/create")
            ->post("/reports/{$this->found->id}/claims", $this->claimPayload(['proof' => 'punya saya']))
            ->assertSessionHasErrors('proof');
    }

    public function test_admin_menyetujui_dan_klaim_lain_otomatis_ditolak(): void
    {
        $other = User::factory()->create();
        $c1 = Claim::create($this->claimPayload(['report_id' => $this->found->id, 'user_id' => $this->owner->id]));
        $c2 = Claim::create($this->claimPayload(['report_id' => $this->found->id, 'user_id' => $other->id]));

        $this->actingAs($this->admin)->post("/admin/claims/{$c1->id}/approve")->assertRedirect();

        $this->assertSame('approved', $c1->fresh()->status);
        $this->assertSame('rejected', $c2->fresh()->status);
    }

    public function test_penolakan_wajib_alasan(): void
    {
        $claim = Claim::create($this->claimPayload(['report_id' => $this->found->id, 'user_id' => $this->owner->id]));

        $this->actingAs($this->admin)->from("/admin/claims/{$claim->id}")
            ->post("/admin/claims/{$claim->id}/reject", ['admin_note' => 'tidak'])
            ->assertSessionHasErrors('admin_note');

        $this->assertSame('pending', $claim->fresh()->status);
    }

    public function test_chat_hanya_terbuka_setelah_disetujui(): void
    {
        $claim = Claim::create($this->claimPayload(['report_id' => $this->found->id, 'user_id' => $this->owner->id]));

        $this->actingAs($this->owner)->postJson("/ajax/claims/{$claim->id}/messages", ['body' => 'Halo'])->assertForbidden();

        $claim->update(['status' => 'approved']);
        $this->actingAs($this->owner)->postJson("/ajax/claims/{$claim->id}/messages", ['body' => 'Halo'])->assertCreated();
        $this->actingAs(User::factory()->create())->getJson("/ajax/claims/{$claim->id}/messages")->assertForbidden();
    }

    public function test_tidak_ada_klaim_baru_saat_barang_sedang_diserahkan(): void
    {
        Claim::create($this->claimPayload(['report_id' => $this->found->id, 'user_id' => $this->owner->id, 'status' => 'approved']));
        $this->found->update(['status' => 'in_claim']);

        $this->actingAs(User::factory()->create())->post("/reports/{$this->found->id}/claims", $this->claimPayload());

        $this->assertSame(1, Claim::where('report_id', $this->found->id)->count());
    }

    public function test_klaim_yang_ditolak_tidak_bisa_diajukan_ulang(): void
    {
        Claim::create($this->claimPayload(['report_id' => $this->found->id, 'user_id' => $this->owner->id, 'status' => 'rejected']));

        $this->actingAs($this->owner)->post("/reports/{$this->found->id}/claims", $this->claimPayload());

        $this->assertSame(0, Claim::where('report_id', $this->found->id)->where('status', 'pending')->count());
    }

    public function test_pelapor_tidak_bisa_menutup_laporan_saat_serah_terima(): void
    {
        $claim = Claim::create($this->claimPayload(['report_id' => $this->found->id, 'user_id' => $this->owner->id, 'status' => 'approved']));
        $this->found->update(['status' => 'in_claim']);

        $this->actingAs($this->finder)->post("/reports/{$this->found->id}/close")->assertSessionHas('error');

        $this->assertSame('in_claim', $this->found->fresh()->status);
        $this->assertSame('approved', $claim->fresh()->status);
    }

    public function test_admin_menutup_laporan_membatalkan_semua_klaim_berjalan(): void
    {
        $approved = Claim::create($this->claimPayload(['report_id' => $this->found->id, 'user_id' => $this->owner->id, 'status' => 'approved']));
        $pending = Claim::create($this->claimPayload(['report_id' => $this->found->id, 'user_id' => User::factory()->create()->id]));

        $this->actingAs($this->admin)->post("/admin/reports/{$this->found->id}/close")->assertRedirect();

        $this->assertSame('closed', $this->found->fresh()->status);
        $this->assertSame('cancelled', $approved->fresh()->status);
        $this->assertSame('cancelled', $pending->fresh()->status);
    }

    public function test_chat_hanya_bisa_dibaca_setelah_barang_diserahkan(): void
    {
        $claim = Claim::create($this->claimPayload(['report_id' => $this->found->id, 'user_id' => $this->owner->id, 'status' => 'returned']));

        $this->actingAs($this->owner)->getJson("/ajax/claims/{$claim->id}/messages")->assertOk();
        $this->actingAs($this->owner)->postJson("/ajax/claims/{$claim->id}/messages", ['body' => 'Halo'])->assertForbidden();
    }

    public function test_admin_tidak_bisa_memutuskan_klaim_miliknya_sendiri(): void
    {
        $claim = Claim::create($this->claimPayload(['report_id' => $this->found->id, 'user_id' => $this->admin->id]));

        $this->actingAs($this->admin)->post("/admin/claims/{$claim->id}/approve")->assertForbidden();

        $this->assertSame('pending', $claim->fresh()->status);
    }

    public function test_hanya_pemilik_laporan_hilang_yang_bisa_menyembunyikan_kandidat(): void
    {
        $lost = Report::factory()->create(['user_id' => $this->owner->id, 'category_id' => $this->found->category_id]);
        $match = ReportMatch::create([
            'lost_report_id' => $lost->id, 'found_report_id' => $this->found->id,
            'rule_score' => 80, 'score' => 80, 'distance_m' => 50,
        ]);

        $this->actingAs($this->finder)->post("/matches/{$match->id}/dismiss")->assertForbidden();
        $this->assertSame('suggested', $match->fresh()->status);

        $this->actingAs($this->owner)->post("/matches/{$match->id}/dismiss")->assertRedirect();
        $this->assertSame('dismissed', $match->fresh()->status);
    }

    public function test_kandidat_kembali_saat_klaim_dibatalkan_dan_hilang_saat_ditolak(): void
    {
        $lost = Report::factory()->create(['user_id' => $this->owner->id, 'category_id' => $this->found->category_id]);
        $match = ReportMatch::create([
            'lost_report_id' => $lost->id, 'found_report_id' => $this->found->id,
            'rule_score' => 80, 'score' => 80, 'distance_m' => 50,
        ]);

        $this->actingAs($this->owner)->post("/reports/{$this->found->id}/claims", $this->claimPayload(['lost_report_id' => $lost->id]));
        $this->assertSame('claimed', $match->fresh()->status);

        $claim = Claim::where('user_id', $this->owner->id)->first();
        $this->actingAs($this->owner)->post("/claims/{$claim->id}/cancel");
        $this->assertSame('suggested', $match->fresh()->status);

        $this->actingAs($this->owner)->post("/reports/{$this->found->id}/claims", $this->claimPayload(['lost_report_id' => $lost->id]));
        $second = Claim::where('user_id', $this->owner->id)->where('status', 'pending')->first();
        $this->actingAs($this->admin)->post("/admin/claims/{$second->id}/reject", ['admin_note' => 'Ciri yang disebutkan tidak sesuai.']);
        $this->assertSame('dismissed', $match->fresh()->status);
    }

    public function test_persetujuan_kedua_untuk_barang_yang_sama_ditolak(): void
    {
        $c1 = Claim::create($this->claimPayload(['report_id' => $this->found->id, 'user_id' => $this->owner->id]));
        $c2 = Claim::create($this->claimPayload(['report_id' => $this->found->id, 'user_id' => User::factory()->create()->id]));
        $this->found->update(['status' => 'in_claim']);
        // Simulasi dua tab admin: klaim kedua masih terlihat "pending" saat klaim pertama disetujui
        $c1->update(['status' => 'approved']);

        $this->actingAs($this->admin)->post("/admin/claims/{$c2->id}/approve")->assertStatus(409);

        $this->assertSame('pending', $c2->fresh()->status);
    }

    public function test_serah_terima_menyelesaikan_laporan_temuan_dan_hilang(): void
    {
        $lost = Report::factory()->create(['user_id' => $this->owner->id, 'category_id' => $this->found->category_id]);
        $claim = Claim::create($this->claimPayload([
            'report_id' => $this->found->id,
            'user_id' => $this->owner->id,
            'lost_report_id' => $lost->id,
            'status' => 'approved',
        ]));

        $this->actingAs($this->finder)->post("/claims/{$claim->id}/returned")->assertRedirect();

        $this->assertSame('returned', $claim->fresh()->status);
        $this->assertSame('resolved', $this->found->fresh()->status);
        $this->assertSame('resolved', $lost->fresh()->status);
    }
}
