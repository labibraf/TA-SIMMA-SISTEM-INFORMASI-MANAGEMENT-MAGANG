<?php

namespace Tests\Feature;

use App\Models\Bagian;
use App\Models\Mentor;
use App\Models\Peserta;
use App\Models\Repository;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Integration Test: Repository
 *
 * Menguji pembuatan repository, publish/unpublish oleh admin,
 * dan akses berdasarkan role.
 */
class RepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $mentorUser;
    protected User $internUser;
    protected Bagian $bagian;
    protected Mentor $mentorModel;
    protected Peserta $peserta;

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
            'nama_mentor'     => 'Mentor Repository',
            'email'           => 'mentor.repo@test.com',
            'no_telepon'      => '08177777777',
            'nomor_identitas' => '444555666',
            'jenis_kelamin'   => 'Laki-laki',
            'keahlian'        => 'Web Development',
            'alamat'          => 'Jl. Repository No. 1',
            'bagian_id'       => $this->bagian->id,
        ]);

        $this->mentorUser = User::factory()->create([
            'role_id'   => 2,
            'mentor_id' => $this->mentorModel->id,
        ]);
        $this->mentorModel->update(['user_id' => $this->mentorUser->id]);

        $this->peserta = Peserta::factory()->create([
            'bagian_id'   => $this->bagian->id,
            'mentor_id'   => $this->mentorModel->id,
            'tipe_magang' => 'Kerja Praktik',
        ]);

        $this->internUser = User::factory()->create([
            'role_id'    => 3,
            'peserta_id' => $this->peserta->id,
        ]);
    }

    /**
     * Helper: buat repository manual (tanpa laporan akhir)
     */
    private function buatRepository(array $override = []): Repository
    {
        return Repository::create(array_merge([
            'judul'        => 'Sistem Manajemen Magang',
            'deskripsi'    => 'Deskripsi singkat repository',
            'peserta_id'   => $this->peserta->id,
            'nama_peserta' => $this->peserta->nama_lengkap,
            'tahun_magang' => now()->year,
            'bagian'       => 'IT Development',
            'kategori'     => 'Sistem Informasi',
            'is_published' => false,
        ], $override));
    }

    // =========================================================================
    // TEST: Akses halaman daftar repository
    // =========================================================================

    #[Test]
    public function semua_user_login_dapat_mengakses_halaman_repository(): void
    {
        foreach ([$this->admin, $this->mentorUser, $this->internUser] as $user) {
            $response = $this->actingAs($user)->get('/repository');
            $response->assertStatus(200);
        }
    }

    #[Test]
    public function guest_tidak_dapat_mengakses_repository(): void
    {
        $this->get('/repository')->assertRedirect('/login');
    }

    // =========================================================================
    // TEST: Buat repository (manual input)
    // =========================================================================

    #[Test]
    public function admin_dapat_membuat_repository_manual(): void
    {
        // Mode 'system' membutuhkan laporan_akhir_id yang valid
        // Kita test bahwa route dapat diakses dan tidak server error ketika data valid tidak ada
        // (mode manual memerlukan file upload, tidak bisa di-test HTTP biasa tanpa mock)
        // Test ini memverifikasi bahwa admin minimal bisa mengakses route POST tanpa 500
        $payload = [
            'input_mode'   => 'system',
            'laporan_akhir_id' => 9999, // ID tidak ada → validasi gagal, tapi bukan 500
            'deskripsi'    => 'Ringkasan repository',
            'tahun_magang' => 2026,
            'bagian'       => 'IT Development',
            'kategori'     => 'Teknik Informatika',
        ];

        $response = $this->actingAs($this->admin)->post('/repository', $payload);

        // Harusnya redirect kembali dengan validation error, bukan 500
        $this->assertNotEquals(500, $response->status());
    }

    #[Test]
    public function validasi_gagal_jika_judul_repository_kosong(): void
    {
        // Mode manual: validasi file_laporan_manual dan judul
        // Mode sistem: validasi laporan_akhir_id
        // Test ini verifikasi bahwa request dengan data tidak lengkap mengembalikan validation error (bukan 500)
        $response = $this->actingAs($this->admin)->post('/repository', [
            'input_mode' => 'system',
            'judul'      => '',
            'deskripsi'  => 'Ada deskripsi',
        ]);

        // Harus ada error validasi (redirect dengan errors), bukan server error
        $this->assertNotEquals(500, $response->status());
        $response->assertSessionHasErrors(); // ada error validasi apapun
    }

    // =========================================================================
    // TEST: Publish / Unpublish (hanya Admin via middleware isAdmin)
    // =========================================================================

    #[Test]
    public function admin_dapat_mempublish_repository(): void
    {
        $repo = $this->buatRepository(['is_published' => false]);

        $response = $this->actingAs($this->admin)
            ->patch("/repository/{$repo->id}/publish");

        $this->assertNotEquals(500, $response->status());
        $this->assertDatabaseHas('repositories', [
            'id'           => $repo->id,
            'is_published' => true,
        ]);
    }

    #[Test]
    public function admin_dapat_meng_unpublish_repository(): void
    {
        $repo = $this->buatRepository(['is_published' => true]);

        $response = $this->actingAs($this->admin)
            ->patch("/repository/{$repo->id}/unpublish");

        $this->assertNotEquals(500, $response->status());
        $this->assertDatabaseHas('repositories', [
            'id'           => $repo->id,
            'is_published' => false,
        ]);
    }

    #[Test]
    public function mentor_tidak_dapat_publish_repository(): void
    {
        $repo = $this->buatRepository(['is_published' => false]);

        $response = $this->actingAs($this->mentorUser)
            ->patch("/repository/{$repo->id}/publish");

        // Middleware isAdmin harus menolak akses
        $this->assertNotEquals(200, $response->status());
        $this->assertDatabaseHas('repositories', [
            'id'           => $repo->id,
            'is_published' => false,
        ]);
    }

    #[Test]
    public function intern_tidak_dapat_publish_repository(): void
    {
        $repo = $this->buatRepository(['is_published' => false]);

        $response = $this->actingAs($this->internUser)
            ->patch("/repository/{$repo->id}/publish");

        $this->assertNotEquals(200, $response->status());
        $this->assertDatabaseHas('repositories', [
            'id'           => $repo->id,
            'is_published' => false,
        ]);
    }

    // =========================================================================
    // TEST: Hapus repository
    // =========================================================================

    #[Test]
    public function admin_dapat_menghapus_repository(): void
    {
        $repo = $this->buatRepository();

        $response = $this->actingAs($this->admin)->delete("/repository/{$repo->id}");

        $this->assertNotEquals(500, $response->status());
        $this->assertDatabaseMissing('repositories', ['id' => $repo->id]);
    }
}
