<?php

namespace Tests\Feature;

use App\Models\Bagian;
use App\Models\Mentor;
use App\Models\Peserta;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Integration Test: Manajemen Peserta (CRUD)
 *
 * Menguji akses halaman peserta, pembuatan, pengeditan, dan penghapusan
 * berdasarkan role pengguna (Admin, Mentor, dan Peserta/Intern).
 */
class PesertaTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $mentor;
    protected User $internUser;
    protected Bagian $bagian;
    protected Mentor $mentorModel;

    protected function setUp(): void
    {
        parent::setUp();

        // Buat role
        Role::insert([
            ['id' => 1, 'role_name' => 'Admin',  'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'role_name' => 'Mentor',  'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'role_name' => 'Intern',  'created_at' => now(), 'updated_at' => now()],
        ]);

        // Buat bagian
        $this->bagian = Bagian::create(['nama_bagian' => 'IT Development']);

        // Buat admin user
        $this->admin = User::factory()->create([
            'role_id' => 1,
            'email'   => 'admin@test.com',
        ]);

        // Buat mentor model dan user-nya
        $this->mentorModel = Mentor::create([
            'nama_mentor'     => 'Mentor Test',
            'email'           => 'mentor@test.com',
            'no_telepon'      => '08123456789',
            'nomor_identitas' => '123456789',
            'jenis_kelamin'   => 'Laki-laki',
            'keahlian'        => 'Web Development',
            'alamat'          => 'Jl. Peserta No. 1',
            'bagian_id'       => $this->bagian->id,
        ]);

        $this->mentor = User::factory()->create([
            'role_id'   => 2,
            'email'     => 'mentoruser@test.com',
            'mentor_id' => $this->mentorModel->id,
        ]);

        $this->mentorModel->update(['user_id' => $this->mentor->id]);

        // Buat peserta dan intern user
        $peserta = Peserta::factory()->create([
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
    // TEST: Akses halaman index peserta
    // =========================================================================

    #[Test]
    public function admin_dapat_mengakses_halaman_daftar_peserta(): void
    {
        $response = $this->actingAs($this->admin)->get('/peserta');
        $response->assertStatus(200);
    }

    #[Test]
    public function mentor_dapat_mengakses_halaman_daftar_peserta(): void
    {
        $response = $this->actingAs($this->mentor)->get('/peserta');
        $response->assertStatus(200);
    }

    #[Test]
    public function intern_tidak_dapat_mengakses_halaman_daftar_peserta(): void
    {
        // Role Intern tidak termasuk dalam middleware role:Admin,Mentor
        $response = $this->actingAs($this->internUser)->get('/peserta');
        // Harusnya redirect (403/302) bukan 200
        $this->assertNotEquals(200, $response->status());
    }

    // =========================================================================
    // TEST: Halaman create peserta
    // =========================================================================

    #[Test]
    public function admin_dapat_mengakses_halaman_tambah_peserta(): void
    {
        $response = $this->actingAs($this->admin)->get('/peserta/create');
        $response->assertStatus(200);
    }

    // =========================================================================
    // TEST: Store (simpan) peserta baru
    // =========================================================================

    #[Test]
    public function admin_dapat_menyimpan_peserta_baru(): void
    {
        $payload = [
            'nama_lengkap'           => 'Peserta Baru',
            'email'                  => 'pesertabaru@test.com',
            'nomor_identitas'        => '987654321',
            'no_telepon'             => '081234567890',
            'alamat'                 => 'Jl. Test No. 1',
            'jenis_kelamin'          => 'Laki-laki',
            'asal_instansi'          => 'Universitas Test',
            'jurusan'                => 'Teknik Informatika',
            'tipe_magang'            => 'Kerja Praktik',
            'tanggal_mulai_magang'   => '2026-01-01',
            'tanggal_selesai_magang' => '2026-06-30',
            'bagian_id'              => $this->bagian->id,
            'mentor_id'              => $this->mentorModel->id,
            'target_method'          => 'manual',
            'sks'                    => 3,
            'target_waktu_manual'    => 120,
        ];

        $response = $this->actingAs($this->admin)->post('/peserta', $payload);

        $response->assertRedirect('/peserta');
        $this->assertDatabaseHas('pesertas', [
            'nama_lengkap' => 'Peserta Baru',
            'email'        => 'pesertabaru@test.com',
        ]);
        // Pastikan akun user juga terbuat
        $this->assertDatabaseHas('users', [
            'email' => 'pesertabaru@test.com',
        ]);
    }

    #[Test]
    public function validasi_gagal_jika_email_peserta_sudah_ada(): void
    {
        // Email ini sudah dipakai oleh peserta yang dibuat di setUp
        $pesertaAda = Peserta::first();

        $payload = [
            'nama_lengkap'           => 'Peserta Duplikat',
            'email'                  => $pesertaAda->email, // email duplikat
            'nomor_identitas'        => '111111111',
            'no_telepon'             => '08111111111',
            'alamat'                 => 'Jl. Duplikat',
            'jenis_kelamin'          => 'Perempuan',
            'asal_instansi'          => 'Universitas Duplikat',
            'jurusan'                => 'Sistem Informasi',
            'tipe_magang'            => 'Kerja Praktik',
            'tanggal_mulai_magang'   => '2026-01-01',
            'tanggal_selesai_magang' => '2026-06-30',
            'bagian_id'              => $this->bagian->id,
            'mentor_id'              => $this->mentorModel->id,
            'target_method'          => 'sks',
            'sks'                    => 3,
        ];

        $response = $this->actingAs($this->admin)->post('/peserta', $payload);
        $response->assertSessionHasErrors(['email']);
    }

    #[Test]
    public function validasi_gagal_jika_tanggal_selesai_sebelum_tanggal_mulai(): void
    {
        $payload = [
            'nama_lengkap'           => 'Test Tanggal',
            'email'                  => 'testtanggal@test.com',
            'nomor_identitas'        => '555555555',
            'no_telepon'             => '085555555555',
            'alamat'                 => 'Jl. Test',
            'jenis_kelamin'          => 'Laki-laki',
            'asal_instansi'          => 'Universitas Test',
            'jurusan'                => 'Teknik Informatika',
            'tipe_magang'            => 'Kerja Praktik',
            'tanggal_mulai_magang'   => '2026-06-01',
            'tanggal_selesai_magang' => '2026-01-01', // sebelum tanggal mulai!
            'bagian_id'              => $this->bagian->id,
            'mentor_id'              => $this->mentorModel->id,
            'target_method'          => 'sks',
            'sks'                    => 3,
        ];

        $response = $this->actingAs($this->admin)->post('/peserta', $payload);
        $response->assertSessionHasErrors(['tanggal_selesai_magang']);
    }

    // =========================================================================
    // TEST: Show (detail) peserta
    // =========================================================================

    #[Test]
    public function admin_dapat_melihat_detail_peserta(): void
    {
        $peserta = Peserta::first();

        $response = $this->actingAs($this->admin)->get("/peserta/{$peserta->id}");
        $response->assertStatus(200);
    }

    // =========================================================================
    // TEST: Destroy (hapus) peserta
    // =========================================================================

    #[Test]
    public function admin_dapat_menghapus_peserta(): void
    {
        // Buat peserta baru agar tidak bentrok dengan internUser di setUp
        $peserta = Peserta::factory()->create([
            'bagian_id'   => $this->bagian->id,
            'mentor_id'   => $this->mentorModel->id,
            'tipe_magang' => 'Kerja Praktik',
        ]);
        $userPeserta = User::factory()->create([
            'role_id'    => 3,
            'peserta_id' => $peserta->id,
        ]);

        $response = $this->actingAs($this->admin)->delete("/peserta/{$peserta->id}");

        $response->assertRedirect('/peserta');
        $this->assertDatabaseMissing('pesertas', ['id' => $peserta->id]);
        $this->assertDatabaseMissing('users', ['id' => $userPeserta->id]);
    }
}
