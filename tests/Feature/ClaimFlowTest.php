<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Claim;
use App\Models\Report;
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
