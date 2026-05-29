<?php

namespace Tests\Feature;

use App\Models\Bagian;
use App\Models\Mentor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Integration Test: Role-Based Access Control (RBAC)
 *
 * Menguji bahwa setiap role hanya dapat mengakses resource sesuai haknya.
 * Ini adalah cross-cutting test yang memverifikasi middleware CheckRole dan isAdmin.
 */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $mentorUser;
    protected User $internUser;
    protected Bagian $bagian;
    protected Mentor $mentorModel;

    protected function setUp(): void
    {
        parent::setUp();

        Role::insert([
            ['id' => 1, 'role_name' => 'Admin',  'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'role_name' => 'Mentor',  'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'role_name' => 'Intern',  'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->bagian = Bagian::create(['nama_bagian' => 'IT Development']);

        $this->admin = User::factory()->create(['role_id' => 1]);

        $this->mentorModel = Mentor::create([
            'nama_mentor'     => 'Mentor RBAC',
            'email'           => 'mentor.rbac@test.com',
            'no_telepon'      => '08188888888',
            'nomor_identitas' => '777888999',
            'jenis_kelamin'   => 'Laki-laki',
            'keahlian'        => 'Web Development',
            'alamat'          => 'Jl. Test RBAC No. 1',
            'bagian_id'       => $this->bagian->id,
        ]);

        $this->mentorUser = User::factory()->create([
            'role_id'   => 2,
            'mentor_id' => $this->mentorModel->id,
        ]);
        $this->mentorModel->update(['user_id' => $this->mentorUser->id]);

        $peserta = \App\Models\Peserta::factory()->create([
            'bagian_id'   => $this->bagian->id,
            'mentor_id'   => $this->mentorModel->id,
            'tipe_magang' => 'Kerja Praktik',
        ]);

        $this->internUser = User::factory()->create([
            'role_id'    => 3,
            'peserta_id' => $peserta->id,
        ]);
    }

    // =========================================================================
    // TEST: Route users (hanya Admin)
    // =========================================================================

    #[Test]
    public function admin_dapat_mengakses_manajemen_users(): void
    {
        $response = $this->actingAs($this->admin)->get('/users');
        $response->assertStatus(200);
    }

    #[Test]
    public function mentor_tidak_dapat_mengakses_manajemen_users(): void
    {
        $response = $this->actingAs($this->mentorUser)->get('/users');
        $this->assertNotEquals(200, $response->status());
    }

    #[Test]
    public function intern_tidak_dapat_mengakses_manajemen_users(): void
    {
        $response = $this->actingAs($this->internUser)->get('/users');
        $this->assertNotEquals(200, $response->status());
    }

    // =========================================================================
    // TEST: Route bagian (Admin dan Mentor)
    // =========================================================================

    #[Test]
    public function admin_dapat_mengakses_manajemen_bagian(): void
    {
        $response = $this->actingAs($this->admin)->get('/bagian');
        $response->assertStatus(200);
    }

    #[Test]
    public function mentor_dapat_mengakses_manajemen_bagian(): void
    {
        $response = $this->actingAs($this->mentorUser)->get('/bagian');
        $response->assertStatus(200);
    }

    #[Test]
    public function intern_tidak_dapat_mengakses_manajemen_bagian(): void
    {
        $response = $this->actingAs($this->internUser)->get('/bagian');
        $this->assertNotEquals(200, $response->status());
    }

    // =========================================================================
    // TEST: Route mentor (Admin dan Mentor)
    // =========================================================================

    #[Test]
    public function admin_dapat_mengakses_halaman_mentor(): void
    {
        $response = $this->actingAs($this->admin)->get('/mentor');
        $response->assertStatus(200);
    }

    #[Test]
    public function mentor_dapat_mengakses_halaman_mentor(): void
    {
        $response = $this->actingAs($this->mentorUser)->get('/mentor');
        $response->assertStatus(200);
    }

    #[Test]
    public function intern_tidak_dapat_mengakses_halaman_mentor(): void
    {
        $response = $this->actingAs($this->internUser)->get('/mentor');
        $this->assertNotEquals(200, $response->status());
    }

    // =========================================================================
    // TEST: Dashboard (semua role yang login)
    // =========================================================================

    #[Test]
    public function semua_role_yang_login_dapat_mengakses_dashboard(): void
    {
        foreach ([$this->admin, $this->mentorUser, $this->internUser] as $user) {
            $response = $this->actingAs($user)->get('/dashboard');
            $response->assertStatus(200);
        }
    }

    #[Test]
    public function guest_tidak_dapat_mengakses_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    // =========================================================================
    // TEST: API endpoint mentor by bagian
    // =========================================================================

    #[Test]
    public function endpoint_api_mentor_by_bagian_dapat_diakses_admin(): void
    {
        $response = $this->actingAs($this->admin)
            ->get("/api/mentors/by-bagian/{$this->bagian->id}");

        $this->assertNotEquals(500, $response->status());
    }
}
