<?php

namespace Tests\Feature;

use App\Models\Bagian;
use App\Models\Mentor;
use App\Models\Penugasan;
use App\Models\Peserta;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Integration Test: Manajemen Penugasan (CRUD + Approve + Feedback)
 *
 * Menguji pembuatan tugas individu/divisi, pembaruan status,
 * approve tugas oleh mentor/admin, dan pengiriman feedback.
 */
class PenugasanTest extends TestCase
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
            'nama_mentor'     => 'Mentor Test',
            'email'           => 'mentor@test.com',
            'no_telepon'      => '08123456789',
            'nomor_identitas' => '123456789',
            'jenis_kelamin'   => 'Laki-laki',
            'keahlian'        => 'Web Development',
            'alamat'          => 'Jl. Penugasan No. 1',
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
     * Helper: buat penugasan individu sample di DB
     */
    private function buatPenugasanIndividu(array $override = []): Penugasan
    {
        return Penugasan::create(array_merge([
            'judul_tugas'     => 'Tugas Test Individu',
            'deskripsi_tugas' => 'Deskripsi tugas test',
            'deadline'        => now()->addDays(7)->format('Y-m-d'),
            'status_tugas'    => 'Belum',
            'bobot_tugas'     => 8,
            'kategori'        => 'Individu',
            'mentor_id'       => $this->mentorModel->id,
            'peserta_id'      => $this->peserta->id,
            'is_approved'     => 0,
        ], $override));
    }

    // =========================================================================
    // TEST: Akses halaman daftar penugasan
    // =========================================================================

    #[Test]
    public function semua_role_yang_login_dapat_mengakses_halaman_penugasan(): void
    {
        foreach ([$this->admin, $this->mentorUser, $this->internUser] as $user) {
            $response = $this->actingAs($user)->get('/penugasans');
            $response->assertStatus(200);
        }
    }

    #[Test]
    public function guest_tidak_dapat_mengakses_halaman_penugasan(): void
    {
        $response = $this->get('/penugasans');
        $response->assertRedirect('/login');
    }

    // =========================================================================
    // TEST: Buat penugasan baru
    // =========================================================================

    #[Test]
    public function mentor_dapat_membuat_penugasan_individu_baru(): void
    {
        $payload = [
            'judul_tugas'     => 'Buat Fitur Login',
            'deskripsi_tugas' => 'Implementasi fitur login menggunakan Laravel',
            'deadline'        => now()->addDays(14)->format('Y-m-d'),
            'beban_waktu'     => 10,  // Nama field di form HTML, bukan kolom DB
            'kategori'        => 'Individu',
            'peserta_id'      => $this->peserta->id,
        ];

        $response = $this->actingAs($this->mentorUser)->post('/penugasans', $payload);

        // Pastikan tidak 404/500, dan redirect atau 200
        $this->assertNotEquals(500, $response->status());
        $this->assertDatabaseHas('penugasans', [
            'judul_tugas' => 'Buat Fitur Login',
            'kategori'    => 'Individu',
        ]);
    }

    #[Test]
    public function validasi_gagal_jika_judul_tugas_kosong(): void
    {
        $payload = [
            'judul_tugas'     => '',
            'deskripsi_tugas' => 'Deskripsi ada',
            'deadline'        => now()->addDays(7)->format('Y-m-d'),
            'beban_waktu'     => 5,
            'kategori'        => 'Individu',
            'peserta_id'      => $this->peserta->id,
        ];

        $response = $this->actingAs($this->mentorUser)->post('/penugasans', $payload);
        $response->assertSessionHasErrors(['judul_tugas']);
    }

    // =========================================================================
    // TEST: Update status penugasan
    // =========================================================================

    #[Test]
    public function peserta_dapat_update_status_penugasannya(): void
    {
        $penugasan = $this->buatPenugasanIndividu();

        $response = $this->actingAs($this->internUser)->put("/penugasan/{$penugasan->id}/status", [
            'status_tugas' => 'Dikerjakan',
        ]);

        // Tidak boleh error server
        $this->assertNotEquals(500, $response->status());
    }

    // =========================================================================
    // TEST: Approve penugasan (hanya Admin/Mentor)
    // =========================================================================

    #[Test]
    public function mentor_dapat_approve_penugasan(): void
    {
        // updateApprove() butuh progress 100% di laporan harian terlebih dahulu
        $penugasan = $this->buatPenugasanIndividu(['status_tugas' => 'Selesai']);

        // Buat laporan harian dengan progress 100% agar approve bisa dilakukan
        \App\Models\LaporanHarian::create([
            'penugasan_id'       => $penugasan->id,
            'peserta_id'         => $this->peserta->id,
            'tanggal_laporan'    => now()->format('Y-m-d'),
            'deskripsi_kegiatan' => 'Selesai',
            'status_tugas'       => 'Selesai',
            'progres_tugas'      => 100,
        ]);

        $response = $this->actingAs($this->mentorUser)->put("/penugasan/{$penugasan->id}/approve", [
            'is_approved' => 1,
            'feedback'    => 'Bagus sekali!',
        ]);

        $this->assertNotEquals(500, $response->status());
        $this->assertDatabaseHas('penugasans', [
            'id'          => $penugasan->id,
            'is_approved' => 1,
        ]);
    }

    #[Test]
    public function intern_tidak_dapat_approve_penugasan(): void
    {
        $penugasan = $this->buatPenugasanIndividu();

        $response = $this->actingAs($this->internUser)->put("/penugasan/{$penugasan->id}/approve", [
            'is_approved' => 1,
        ]);

        // Harus di-redirect (akses ditolak), bukan 200
        $this->assertNotEquals(200, $response->status());
        // is_approved tidak boleh berubah menjadi 1
        $this->assertDatabaseHas('penugasans', [
            'id'          => $penugasan->id,
            'is_approved' => 0,
        ]);
    }

    // =========================================================================
    // TEST: Feedback penugasan (hanya Admin/Mentor)
    // =========================================================================

    #[Test]
    public function mentor_dapat_memberikan_feedback_penugasan(): void
    {
        $penugasan = $this->buatPenugasanIndividu();

        $response = $this->actingAs($this->mentorUser)->put("/penugasan/{$penugasan->id}/feedback", [
            'feedback' => 'Pekerjaannya sudah bagus, tingkatkan kualitas dokumentasi.',
        ]);

        $this->assertNotEquals(500, $response->status());
        $this->assertDatabaseHas('penugasans', [
            'id'       => $penugasan->id,
            'feedback' => 'Pekerjaannya sudah bagus, tingkatkan kualitas dokumentasi.',
        ]);
    }

    // =========================================================================
    // TEST: Hapus penugasan
    // =========================================================================

    #[Test]
    public function admin_dapat_menghapus_penugasan(): void
    {
        $penugasan = $this->buatPenugasanIndividu();

        $response = $this->actingAs($this->admin)->delete("/penugasans/{$penugasan->id}");

        $this->assertNotEquals(500, $response->status());
        $this->assertDatabaseMissing('penugasans', ['id' => $penugasan->id]);
    }
}
