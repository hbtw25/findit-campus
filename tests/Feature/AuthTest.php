<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_mahasiswa_bisa_daftar_dengan_email_kampus(): void
    {
        $response = $this->post('/register', [
            'name' => 'Rani Putri',
            'nim' => '103032400999',
            'email' => 'rani.putri@student.telkomuniversity.ac.id',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $response->assertRedirect(route('map'));
        $this->assertAuthenticated();
        $this->assertTrue(Hash::check('rahasia123', User::first()->password));
    }

    public function test_email_selain_kampus_ditolak(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Orang Luar',
            'nim' => '123456789',
            'email' => 'orang@gmail.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_dengan_auth_attempt(): void
    {
        $user = User::factory()->create(['password' => Hash::make('findit123')]);

        $this->post('/login', ['email' => $user->email, 'password' => 'findit123'])
            ->assertRedirect(route('map'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_sandi_salah_ditolak(): void
    {
        $user = User::factory()->create(['password' => Hash::make('findit123')]);

        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'salah-sandi'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_akun_nonaktif_tidak_bisa_masuk(): void
    {
        $user = User::factory()->create(['password' => Hash::make('findit123'), 'is_active' => false]);

        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'findit123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_halaman_admin_khusus_admin(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk();
    }
}
