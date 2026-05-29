<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Integration Test: Autentikasi (Login & Logout)
 *
 * Menguji alur autentikasi mulai dari halaman login,
 * proses login dengan kredensial valid/invalid, hingga logout.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Setup: buat role dasar yang dibutuhkan sistem
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Buat role yang dibutuhkan sistem
        Role::insert([
            ['id' => 1, 'role_name' => 'Admin',  'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'role_name' => 'Mentor',  'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'role_name' => 'Intern',  'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    // =========================================================================
    // TEST: Halaman Login
    // =========================================================================

    #[Test]
    public function halaman_login_dapat_diakses_tanpa_autentikasi(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('login', false); // pastikan ada elemen login di view
    }

    #[Test]
    public function halaman_login_laravel_auth_dapat_diakses(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    // =========================================================================
    // TEST: Proses Login
    // =========================================================================

    #[Test]
    public function user_dapat_login_dengan_kredensial_valid(): void
    {
        $user = User::factory()->create([
            'email'    => 'admin@test.com',
            'password' => bcrypt('password123'),
            'role_id'  => 1,
        ]);

        $response = $this->post('/login', [
            'email'    => 'admin@test.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function user_tidak_dapat_login_dengan_password_salah(): void
    {
        User::factory()->create([
            'email'    => 'user@test.com',
            'password' => bcrypt('benar123'),
            'role_id'  => 1,
        ]);

        $response = $this->post('/login', [
            'email'    => 'user@test.com',
            'password' => 'salah123',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    #[Test]
    public function user_tidak_dapat_login_dengan_email_tidak_terdaftar(): void
    {
        $response = $this->post('/login', [
            'email'    => 'tidakada@test.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    #[Test]
    public function login_dengan_email_kosong_mengembalikan_error_validasi(): void
    {
        $response = $this->post('/login', [
            'email'    => '',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    // =========================================================================
    // TEST: Proteksi Route (redirect jika belum login)
    // =========================================================================

    #[Test]
    public function dashboard_redirect_ke_login_jika_belum_autentikasi(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    #[Test]
    public function halaman_peserta_redirect_ke_login_jika_belum_autentikasi(): void
    {
        $response = $this->get('/peserta');
        $response->assertRedirect('/login');
    }

    #[Test]
    public function halaman_penugasan_redirect_ke_login_jika_belum_autentikasi(): void
    {
        $response = $this->get('/penugasans');
        $response->assertRedirect('/login');
    }

    // =========================================================================
    // TEST: Logout
    // =========================================================================

    #[Test]
    public function user_yang_login_dapat_logout(): void
    {
        $user = User::factory()->create(['role_id' => 1]);

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }
}
