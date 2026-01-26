<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use App\Models\Penugasan;
use App\Models\Traits\HasProgressTrack;
use App\Models\Traits\HasPenugasanBehav;
use App\Models\Traits\HasLaporanBehav;
use App\Models\Traits\HasStatusMagang;

class Peserta extends Model
{
    use HasFactory, HasProgressTrack, HasPenugasanBehav, HasLaporanBehav, HasStatusMagang;

    protected $fillable = [
        'mentor_id',
        'bagian_id',
        'nama_lengkap',
        'email',
        'no_telepon',
        'alamat',
        'jenis_kelamin',
        'asal_instansi',
        'jurusan',
        'nomor_identitas',
        'tipe_magang',
        'tanggal_mulai_magang',
        'tanggal_selesai_magang',
        'target_method',
        'target_waktu_tugas',
        'waktu_maksimum',
        'waktu_tugas_tercapai',
        'sks',
        'foto',
    ];

    /**
     * Menghitung target waktu berdasarkan metode yang dipilih
     *
     * @return float
     */
    public function getTargetBobotTugasAttribute()
    {
        // Jika menggunakan metode SKS
        if ($this->target_method === 'sks') {
            return round($this->sks * 45, 2);
        }

        // Jika menggunakan metode manual, return target_waktu_tugas yang sudah diinput
        return $this->target_waktu_tugas;
    }

    public function getDurasiHariKerjaAttribute()
    {
        return $this->getDurasiHariKerja();
    }

    /**
     * Menghitung waktu maksimum berdasarkan durasi magang
     *
     * @return int
     */
    public function getWaktuMaksimumAttribute()
    {
        return $this->getWaktuMaks();
    }

    /**
     * Accessor untuk mendapatkan jumlah tugas yang belum selesai
     */
    public function getJumlahTugasBelumSelesaiAttribute()
    {
        return $this->getTugasBelumSelesai()->count();
    }

    /**
     * Mengecek apakah semua tugas yang menyangkut peserta ini sudah selesai dan di-approve
     */
    public function getBisaLaporanAkhirAttribute()
    {
        return $this->getBisaLaporan();
    }

    /**
     * Mengecek apakah peserta sudah menyelesaikan laporan akhir (status terima)
     */
    public function getIsLaporanAkhirSelesaiAttribute()
    {
        return $this->getIsLapSelesai();
    }

    /**
     * Mengecek apakah peserta masih memenuhi syarat untuk tampil di form
     * Syarat: Laporan akhir belum diterima DAN masih ada sisa waktu dari maksimal - minimum
     */
    public function getIsAktifUntukFormAttribute()
    {
        return $this->getIsAktifForm();
    }

    /**
     * Mengecek apakah data akademis peserta dapat diedit
     * Data akademis tidak dapat diedit jika laporan akhir sudah diterima
     */
    public function getCanEditDataAkademisAttribute()
    {
        return $this->getCanEditAkademis();
    }

    /**
     * Mendapatkan list field yang tidak dapat diedit jika laporan akhir sudah diterima
     */
    public function getProtectedFieldsAttribute()
    {
        return $this->getProtectedFields();
    }

    /**
     * MENGUBAH LOGIKA: Progress Bar Utama sekarang berdasarkan HARI KERJA (tanpa weekend)
     * Bukan berdasarkan jam tugas yang dikerjakan.
     * Progress menunjukkan: "Hari kerja ke-X dari Y hari kerja total"
     */
    public function getProgressPercentageAttribute()
    {
        return $this->getProgressPct();
    }

    /**
     * BARU: Mendapatkan jumlah hari kerja yang sudah berjalan sampai hari ini
     * Untuk ditampilkan di UI sebagai "Hari kerja ke-X"
     */
    public function getHariKerjaBerjalanAttribute()
    {
        return $this->getHariKerjaTercapai();
    }

    /**
     * BARU: Atribut khusus untuk melihat pencapaian Target Tugas (Syarat Kelulusan)
     * Logika: (Waktu Tercapai / Target Minimum) × 100
     * Jika sudah mencapai target minimum, maka dianggap 100%
     */
    public function getPersentaseTargetTugasAttribute()
    {
        // Ambil target minimum (SKS atau Manual)
        $targetMinimum = $this->target_method === 'sks' ? $this->target_bobot_tugas : $this->target_waktu_tugas;

        if ($targetMinimum <= 0) {
            return 0;
        }

        // Hitung persentase pencapaian tugas
        $percentage = ($this->waktu_tugas_tercapai / $targetMinimum) * 100;

        // Cap di 100% sesuai permintaan: "jika progress sudah mencapai batas min -> maka progress sudah 100%"
        return round(min($percentage, 100), 2);
    }

    /**
     * BARU: Helper untuk validasi input tugas
     * Mengecek apakah penambahan jam akan melebihi Batas Maksimal
     *
     * @param float $jamTambahan Jumlah jam yang akan ditambahkan
     * @return bool True jika masih boleh ditambah, False jika akan melebihi batas
     */
    public function canAddJamTugas($jamTambahan)
    {
        return $this->canAddJam($jamTambahan);
    }

    /**
     * BARU: Helper untuk mendapatkan sisa jam sebelum mencapai batas maksimal
     * Berguna untuk ditampilkan di UI sebagai informasi
     *
     * @return float Sisa jam yang tersedia
     */
    public function getSisaJamSebelumMaksimalAttribute()
    {
        return max(0, $this->waktu_maksimum - $this->waktu_tugas_tercapai);
    }

    /**
     * BARU: Helper untuk pesan warning di UI jika mendekati atau melebihi batas maksimal
     *
     * @return string|null Pesan warning atau null jika tidak ada warning
     */
    public function getWarningBatasMaksimalAttribute()
    {
        $sisa = $this->sisa_jam_sebelum_maksimal;

        if ($sisa <= 0) {
            return "Batas waktu maksimum tercapai. Tidak dapat menambah tugas lagi.";
        }

        // Warning jika sisa kurang dari 10 jam
        if ($sisa < 10) {
            return "Perhatian: Sisa kuota waktu tinggal " . round($sisa, 2) . " jam sebelum mencapai batas maksimum.";
        }

        return null;
    }

    /**
     * Mendapatkan status magang peserta
     * Status ditentukan berdasarkan kombinasi progress waktu dan pencapaian tugas
     */
    public function getStatusMagangAttribute()
    {
        return $this->getStatusMag();
    }

    /**
     * Update waktu tugas tercapai berdasarkan penugasan yang selesai
     */
    public function updateWaktuTugasTercapai()
    {
        // Hitung total waktu dari tugas individu yang BENAR-BENAR SELESAI
        // Logika: is_approved = 1 ATAU (progress 100% DAN deadline belum lewat)
        $tugasIndividu = $this->penugasan()->get();

        $totalWaktuIndividu = 0;
        foreach ($tugasIndividu as $tugas) {
            $isOverdue = $tugas->deadline && now()->greaterThan(\Carbon\Carbon::parse($tugas->deadline)->endOfDay());

            // Cek progress dari laporan terakhir
            $latestLaporan = $tugas->laporanHarian()->where('peserta_id', $this->id)->latest('created_at')->first();
            $progress = $latestLaporan ? $latestLaporan->progres_tugas : 0;

            // Tugas individu terhitung jika:
            // - Sudah di-approve, ATAU
            // - Progress 100% DAN deadline belum lewat
            $isSelesaiBetulan = ($tugas->is_approved == 1) || ($progress == 100 && !$isOverdue);

            if ($isSelesaiBetulan) {
                $totalWaktuIndividu += $tugas->beban_waktu;
            }
        }

        // Hitung total waktu dari tugas divisi yang BENAR-BENAR SELESAI
        // Logika: HANYA jika is_approved = 1
        $totalWaktuDivisi = 0;
        if ($this->bagian_id) {
            // Ambil tugas divisi yang peserta ini DI-ASSIGN (ada di pivot table)
            $tugasDivisi = \App\Models\Penugasan::where('kategori', 'Divisi')
                ->where('bagian_id', $this->bagian_id)
                ->where('is_approved', 1) // HANYA yang sudah di-approve
                ->whereHas('pesertas', function($query) {
                    $query->where('peserta_id', $this->id);
                })
                ->get();

            foreach ($tugasDivisi as $tugas) {
                // Periksa apakah peserta ini pernah melaporkan tugas divisi tersebut
                $adaLaporan = \App\Models\LaporanHarian::where('peserta_id', $this->id)
                    ->where('penugasan_id', $tugas->id)
                    ->exists();

                // Jika peserta pernah melaporkan tugas ini, hitung beban waktu penuh
                if ($adaLaporan) {
                    $totalWaktuDivisi += $tugas->beban_waktu;
                }
            }
        }

        $totalWaktu = $totalWaktuIndividu + $totalWaktuDivisi;
        $this->update(['waktu_tugas_tercapai' => $totalWaktu]);

        return $totalWaktu;
    }

    public function getSisaTargetJamAttribute()
    {
        $targetWaktu = $this->target_method === 'sks' ? $this->target_bobot_tugas : $this->target_waktu_tugas;
        return $targetWaktu - $this->waktu_tugas_tercapai;
    }

    /**
     * Menghitung sisa waktu dari batas maksimum (untuk penugasan)
     * Digunakan untuk validasi beban waktu penugasan
     *
     * @return int
     */
    public function getSisaWaktuMaksimalAttribute()
    {
        $waktuMaksimal = $this->waktu_maksimum; // Total jam kerja selama magang
        return $waktuMaksimal - $this->waktu_tugas_tercapai;
    }

    /**
     * Menghitung total semua tugas yang relevan dengan peserta (individu + divisi)
     */
    public function getTotalTugasAttribute()
    {
        return $this->getTotalTugas();
    }

    /**
     * Menghitung total tugas selesai (individu + divisi yang sudah di-approve)
     */
    public function getTugasSelesaiAttribute()
    {
        return $this->getTugasSelesai();
    }

    /**
     * Relasi ke User (One-to-One)
     */
    public function user()
    {
        return $this->hasOne(User::class, 'peserta_id');
    }

    /**
     * Relasi ke Bagian (Many-to-One)
     */
    public function bagian()
    {
        return $this->belongsTo(Bagian::class);
    }

    /**
     * Relasi ke Mentor (Many-to-One)
     */
    public function mentor()
    {
        return $this->belongsTo(Mentor::class);
    }

    /**
     * Relasi ke Penugasan Individu (One-to-Many)
     * Hanya untuk penugasan kategori "Individu"
     */
    public function penugasan()
    {
        return $this->hasMany(Penugasan::class);
    }

    /**
     * Mendapatkan sisa hari kerja dari hari ini sampai tanggal selesai magang
     * Tidak termasuk weekend (Sabtu & Minggu)
     */
    public function getSisaHariKerjaAttribute()
    {
        return $this->getSisaHari();
    }

    /**
     * Mendapatkan sisa jam kerja berdasarkan kondisi:
     * - Jika masih dalam masa magang: Sisa Hari Kerja × 8 jam
     * - Jika sudah melewati masa magang: Sisa target yang belum tercapai
     */
    public function getSisaJamKerjaAttribute()
    {
        return $this->getSisaJam();
    }

    /**
     * Scope untuk mendapatkan peserta yang masih aktif untuk form assignment
     * Kriteria: Laporan akhir belum diterima ATAU target tugas belum tercapai
     */
    public function scopeAktifUntukForm($query)
    {
        return $query->where(function($q) {
            // Kondisi 1: Laporan akhir belum diterima (masih aktif magang)
            $q->whereDoesntHave('laporanAkhir', function($subquery) {
                $subquery->where('status', 'terima');
            });

            // ATAU Kondisi 2: Sudah melewati masa magang TAPI target belum tercapai
            // (Peserta yang perlu menyelesaikan tugas untuk memenuhi syarat kelulusan)
            $q->orWhereHas('laporanAkhir', function($subquery) {
                $subquery->where('status', '!=', 'terima'); // Laporan belum diterima
            })
            ->whereNotNull('target_waktu_tugas')
            ->whereRaw('waktu_tugas_tercapai < target_waktu_tugas'); // Target belum tercapai
        });
    }

}
