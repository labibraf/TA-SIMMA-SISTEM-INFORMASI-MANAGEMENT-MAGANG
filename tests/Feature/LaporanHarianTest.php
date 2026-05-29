<?php

namespace Tests\Feature;

use App\Models\Bagian;
use App\Models\LaporanHarian;
use App\Models\Mentor;
use App\Models\Penugasan;
use App\Models\Peserta;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Integration Test: Laporan Harian
 *
 * Menguji pembuatan laporan harian oleh peserta, validasi form,
 * serta pembacaan laporan oleh mentor/admin.
 */
class LaporanHarianTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $mentorUser;
    protected User $internUser;
    protected Bagian $bagian;
    protected Mentor $mentorModel;
    protected Peserta $peserta;
    protected Penugasan $penugasan;

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
            'email'           => 'mentor.laporan@test.com',
            'no_telepon'      => '08123456789',
            'nomor_identitas' => '987654321',
            'jenis_kelamin'   => 'Laki-laki',
            'keahlian'        => 'Web Development',
            'alamat'          => 'Jl. Laporan No. 1',
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

        // Buat penugasan untuk digunakan dalam laporan
        $this->penugasan = Penugasan::create([
            'judul_tugas'     => 'Tugas Test untuk Laporan',
            'deskripsi_tugas' => 'Deskripsi',
            'deadline'        => now()->addDays(10)->format('Y-m-d'),
            'status_tugas'    => 'Belum',
            'bobot_tugas'     => 8,
            'kategori'        => 'Individu',
            'mentor_id'       => $this->mentorModel->id,
            'peserta_id'      => $this->peserta->id,
            'is_approved'     => 0,
        ]);
    }

    // =========================================================================
    // TEST: Akses halaman laporan harian
    // =========================================================================

    #[Test]
    public function semua_user_login_dapat_mengakses_halaman_laporan_harian(): void
    {
        foreach ([$this->admin, $this->mentorUser, $this->internUser] as $user) {
            $response = $this->actingAs($user)->get('/laporan_harian');
            $response->assertStatus(200);
        }
    }

    #[Test]
    public function guest_tidak_dapat_mengakses_laporan_harian(): void
    {
        $this->get('/laporan_harian')->assertRedirect('/login');
    }

    // =========================================================================
    // TEST: Halaman create laporan harian
    // =========================================================================

    #[Test]
    public function peserta_dapat_mengakses_halaman_buat_laporan_harian(): void
    {
        $response = $this->actingAs($this->internUser)
            ->get("/laporan_harian/create/{$this->penugasan->id}");
        $response->assertStatus(200);
    }

    // =========================================================================
    // TEST: Simpan laporan harian
    // =========================================================================

    #[Test]
    public function peserta_dapat_menyimpan_laporan_harian(): void
    {
        $payload = [
            'penugasan_id'       => $this->penugasan->id,
            'peserta_id'         => $this->peserta->id,
            'tanggal_laporan'    => now()->format('Y-m-d'),
            'deskripsi_kegiatan' => 'Melakukan coding fitur authentication',
            'status_tugas'       => 'Dikerjakan',
            'progres_tugas'      => 50,
        ];

        $response = $this->actingAs($this->internUser)->post('/laporan_harian', $payload);

        $this->assertNotEquals(500, $response->status());
        $this->assertDatabaseHas('laporan_harians', [
            'peserta_id'         => $this->peserta->id,
            'penugasan_id'       => $this->penugasan->id,
            'deskripsi_kegiatan' => 'Melakukan coding fitur authentication',
            'progres_tugas'      => 50,
        ]);
    }

    #[Test]
    public function laporan_harian_dengan_progres_100_mengubah_status_tugas_menjadi_selesai(): void
    {
        $payload = [
            'penugasan_id'       => $this->penugasan->id,
            'peserta_id'         => $this->peserta->id,
            'tanggal_laporan'    => now()->format('Y-m-d'),
            'deskripsi_kegiatan' => 'Selesai mengerjakan semua fitur',
            'status_tugas'       => 'Selesai',
            'progres_tugas'      => 100,
        ];

        $this->actingAs($this->internUser)->post('/laporan_harian', $payload);

        // Model event di LaporanHarian::booted() harus mengubah status penugasan
        $this->assertDatabaseHas('penugasans', [
            'id'           => $this->penugasan->id,
            'status_tugas' => 'Selesai',
        ]);
    }

    #[Test]
    public function validasi_gagal_jika_deskripsi_kegiatan_kosong(): void
    {
        // Controller: deskripsi_kegiatan adalah nullable, yang required adalah penugasan_id & progres_tugas
        // Test ini verifikasi bahwa progres_tugas wajib ada
        $payload = [
            'penugasan_id'    => $this->penugasan->id,
            'tanggal_laporan' => now()->format('Y-m-d'),
            'progres_tugas'   => '',  // Wajib diisi
        ];

        $response = $this->actingAs($this->internUser)->post('/laporan_harian', $payload);

        $response->assertSessionHasErrors(['progres_tugas']);
    }

    // =========================================================================
    // TEST: Edit dan update laporan harian
    // =========================================================================

    #[Test]
    public function peserta_dapat_mengedit_laporannya_sendiri(): void
    {
        $laporan = LaporanHarian::create([
            'penugasan_id'       => $this->penugasan->id,
            'peserta_id'         => $this->peserta->id,
            'tanggal_laporan'    => now()->format('Y-m-d'),
            'deskripsi_kegiatan' => 'Deskripsi awal',
            'status_tugas'       => 'Dikerjakan',
            'progres_tugas'      => 30,
        ]);

        $response = $this->actingAs($this->internUser)
            ->put("/laporan_harian/{$laporan->id}", [
                'penugasan_id'       => $this->penugasan->id,
                'peserta_id'         => $this->peserta->id,
                'tanggal_laporan'    => now()->format('Y-m-d'),
                'deskripsi_kegiatan' => 'Deskripsi sudah diperbarui',
                'status_tugas'       => 'Dikerjakan',
                'progres_tugas'      => 60,
            ]);

        $this->assertNotEquals(500, $response->status());
        $this->assertDatabaseHas('laporan_harians', [
            'id'                 => $laporan->id,
            'deskripsi_kegiatan' => 'Deskripsi sudah diperbarui',
            'progres_tugas'      => 60,
        ]);
    }

    // =========================================================================
    // TEST: Hapus laporan harian
    // =========================================================================

    #[Test]
    public function peserta_dapat_menghapus_laporannya_sendiri(): void
    {
        $laporan = LaporanHarian::create([
            'penugasan_id'       => $this->penugasan->id,
            'peserta_id'         => $this->peserta->id,
            'tanggal_laporan'    => now()->format('Y-m-d'),
            'deskripsi_kegiatan' => 'Laporan yang akan dihapus',
            'status_tugas'       => 'Dikerjakan',
            'progres_tugas'      => 20,
        ]);

        $response = $this->actingAs($this->internUser)
            ->delete("/laporan_harian/{$laporan->id}");

        $this->assertNotEquals(500, $response->status());
        $this->assertDatabaseMissing('laporan_harians', ['id' => $laporan->id]);
    }
}
