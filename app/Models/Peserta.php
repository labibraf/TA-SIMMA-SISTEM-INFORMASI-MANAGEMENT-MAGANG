<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use App\Models\Penugasan;

class Peserta extends Model
{
    use HasFactory;

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
        // Cek jika tanggal ada untuk menghindari error
        if (!$this->tanggal_mulai_magang || !$this->tanggal_selesai_magang) {
            return 0;
        }

        $start = Carbon::parse($this->tanggal_mulai_magang);
        $end = Carbon::parse($this->tanggal_selesai_magang);

        // +1 untuk membuatnya inklusif (Senin ke Jumat = 5 hari, bukan 4)
        $totalDays = $start->diffInDays($end) + 1;

        // Hitung jumlah hari Sabtu dan Minggu
        $weekendDays = $start->diffInWeekendDays($end);

        return $totalDays - $weekendDays;
    }

    /**
     * Menghitung waktu maksimum berdasarkan durasi magang
     *
     * @return int
     */
    public function getWaktuMaksimumAttribute()
    {
        if ($this->tanggal_mulai_magang && $this->tanggal_selesai_magang) {
            $start = \Carbon\Carbon::parse($this->tanggal_mulai_magang);
            $end = \Carbon\Carbon::parse($this->tanggal_selesai_magang);

            // 1. Hitung total hari kalender (inklusif)
            $totalDays = $start->diffInDays($end) + 1;

            // 2. Hitung jumlah hari Sabtu & Minggu dalam rentang itu
            $weekendDays = $start->diffInWeekendDays($end);

            // 3. Dapatkan hari kerja (Total - Weekend)
            $workingDays = $totalDays - $weekendDays; // Ini akan menghasilkan 44 hari kerja

            // 4. Kembalikan jam kerja (Hari Kerja * 8 jam)
            return $workingDays * 8; // Ini akan menghasilkan 44 * 8 = 352 jam
        }

        return 0;
    }

    public function getBisaLaporanAkhirAttribute()
    {
        // Validasi 1: Cek target waktu sudah tercapai
        $targetWaktu = $this->target_method === 'sks' ? $this->target_bobot_tugas : $this->target_waktu_tugas;
        $targetTercapai = $this->waktu_tugas_tercapai >= $targetWaktu;

        // Validasi 2: Cek semua tugas yang menyangkut peserta ini sudah selesai
        $semuaTugasSelesai = $this->isSemuaTugasSelesai();

        // Kedua syarat harus terpenuhi
        return $targetTercapai && $semuaTugasSelesai;
    }

    /**
     * Mengecek apakah semua tugas yang menyangkut peserta ini sudah selesai dan di-approve
     */
    public function isSemuaTugasSelesai()
    {
        // Ambil semua tugas yang menyangkut peserta ini
        $tugasPeserta = $this->getTugasPeserta();

        // Jika tidak ada tugas sama sekali, return true
        if ($tugasPeserta->isEmpty()) {
            return true;
        }

        // Cek apakah semua tugas sudah selesai (status_tugas = 'Selesai') dan di-approve (is_approved = 1)
        $semuaSelesai = $tugasPeserta->every(function ($tugas) {
            return $tugas->status_tugas === 'Selesai' && $tugas->is_approved == 1;
        });

        return $semuaSelesai;
    }

    /**
     * Mendapatkan semua tugas yang menyangkut peserta ini
     * Termasuk tugas individu dan tugas divisi
     */
    public function getTugasPeserta()
    {
        // Tugas individu: yang peserta_id-nya sama dengan peserta ini
        $tugasIndividu = Penugasan::where('peserta_id', $this->id)
            ->where('kategori', 'Individu')
            ->get();

        // Tugas divisi: yang bagian_id-nya sama dengan bagian peserta ini
        // DAN peserta ini sudah di-assign ke tugas tersebut (melalui pivot table)
        $tugasDivisi = Penugasan::where('kategori', 'Divisi')
            ->whereHas('pesertasRelation', function($query) {
                $query->where('peserta_id', $this->id);
            })
            ->get();

        // Gabungkan kedua collection
        return $tugasIndividu->merge($tugasDivisi);
    }

    /**
     * Mendapatkan daftar tugas yang belum selesai untuk peserta ini
     */
    public function getTugasBelumSelesai()
    {
        return $this->getTugasPeserta()->filter(function ($tugas) {
            return $tugas->status_tugas !== 'Selesai' || $tugas->is_approved != 1;
        });
    }

    /**
     * Accessor untuk mendapatkan jumlah tugas yang belum selesai
     */
    public function getJumlahTugasBelumSelesaiAttribute()
    {
        return $this->getTugasBelumSelesai()->count();
    }

    /**
     * Mengecek apakah peserta sudah menyelesaikan laporan akhir (status terima)
     */
    public function getIsLaporanAkhirSelesaiAttribute()
    {
        return $this->laporanAkhir()->where('status', 'terima')->exists();
    }

    /**
     * Mengecek apakah peserta masih memenuhi syarat untuk tampil di form
     * Syarat: Laporan akhir belum diterima DAN masih ada sisa waktu dari maksimal - minimum
     */
    public function getIsAktifUntukFormAttribute()
    {
        // Jika laporan akhir sudah diterima, tidak aktif
        if ($this->is_laporan_akhir_selesai) {
            return false;
        }

        // Cek sisa waktu: waktu_maksimum - target minimum
        $targetMinimum = $this->target_method === 'sks' ? $this->target_bobot_tugas : $this->target_waktu_tugas;
        $sisaWaktu = $this->waktu_maksimum - $targetMinimum;

        // Harus ada sisa waktu untuk bisa tampil di form
        return $sisaWaktu > 0;
    }

    /**
     * Mengecek apakah data akademis peserta dapat diedit
     * Data akademis tidak dapat diedit jika laporan akhir sudah diterima
     */
    public function getCanEditDataAkademisAttribute()
    {
        return !$this->is_laporan_akhir_selesai;
    }

    /**
     * Mendapatkan list field yang tidak dapat diedit jika laporan akhir sudah diterima
     */
    public function getProtectedFieldsAttribute()
    {
        if ($this->is_laporan_akhir_selesai) {
            return [
                'sks',
                'tanggal_mulai_magang',
                'tanggal_selesai_magang',
                'target_method',
                'target_waktu_tugas',
                'tipe_magang'
            ];
        }
        return [];
    }

    public function getProgressPercentageAttribute()
    {
        // Progress bar menggunakan waktu_maksimum sebagai basis 100%
        $waktuMaksimum = $this->waktu_maksimum;

        // Jika waktu maksimum 0, progres adalah 0 untuk menghindari pembagian dengan nol
        if ($waktuMaksimum == 0) {
            return 0;
        }

        // Hitung persentase progres: (Waktu Tercapai / Waktu Maksimum) × 100
        $percentage = ($this->waktu_tugas_tercapai / $waktuMaksimum) * 100;

        // Batasi progres agar tidak melebihi 100%
        return round(min($percentage, 100), 2);
    }

    // Di model Peserta
    public function getStatusMagangAttribute()
    {
        if ($this->bisa_laporan_akhir) {
            return 'Siap Laporan Akhir';
        } elseif ($this->progress_percentage >= 50) {
            return 'Berjalan';
        } else {
            return 'Awal';
        }
    }

    /**
     * Update waktu tugas tercapai berdasarkan penugasan yang selesai
     */
    public function updateWaktuTugasTercapai()
    {
        // Hitung total waktu dari tugas individu yang selesai dan di-approve
        $totalWaktuIndividu = $this->penugasan()
            ->where('status_tugas', 'Selesai')
            ->where('is_approved', 1)
            ->sum('beban_waktu');

        // Hitung total waktu dari tugas divisi yang selesai dan di-approve
        $totalWaktuDivisi = 0;
        if ($this->bagian_id) {
            // Ambil semua tugas divisi untuk bagian peserta ini
            $tugasDivisi = \App\Models\Penugasan::where('kategori', 'Divisi')
                ->where('bagian_id', $this->bagian_id)
                ->where('status_tugas', 'Selesai')
                ->where('is_approved', 1)
                ->get();

            foreach ($tugasDivisi as $tugas) {
                // Periksa apakah peserta ini pernah melaporkan tugas divisi tersebut
                $adaLaporan = \App\Models\LaporanHarian::where('peserta_id', $this->id)
                    ->where('penugasan_id', $tugas->id)
                    ->where('progres_tugas', '>', 0) // Peserta berkontribusi
                    ->exists();

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
        // Hitung tugas individu
        $totalIndividu = $this->penugasan()->count();

        // Hitung tugas divisi untuk bagian peserta ini
        $totalDivisi = 0;
        if ($this->bagian_id) {
            $tugasDivisi = \App\Models\Penugasan::where('kategori', 'Divisi')
                ->where('bagian_id', $this->bagian_id)
                ->whereNull('peserta_id')
                ->get();

            foreach ($tugasDivisi as $tugas) {
                // Periksa apakah peserta ini pernah melaporkan tugas divisi tersebut
                $adaLaporan = \App\Models\LaporanHarian::where('peserta_id', $this->id)
                    ->where('penugasan_id', $tugas->id)
                    ->exists();

                if ($adaLaporan) {
                    $totalDivisi++;
                }
            }
        }

        return $totalIndividu + $totalDivisi;
    }

    /**
     * Menghitung total tugas selesai (individu + divisi yang sudah di-approve)
     */
    public function getTugasSelesaiAttribute()
    {
        // Hitung tugas individu yang selesai dan di-approve
        $selesaiIndividu = $this->penugasan()
            ->where('status_tugas', 'Selesai')
            ->where('is_approved', 1)
            ->count();

        // Hitung tugas divisi yang selesai dan di-approve
        $selesaiDivisi = 0;
        if ($this->bagian_id) {
            $tugasDivisi = \App\Models\Penugasan::where('kategori', 'Divisi')
                ->where('bagian_id', $this->bagian_id)
                ->where('status_tugas', 'Selesai')
                ->where('is_approved', 1)
                ->whereNull('peserta_id')
                ->get();

            foreach ($tugasDivisi as $tugas) {
                // Periksa apakah peserta ini pernah melaporkan tugas divisi tersebut
                $adaLaporan = \App\Models\LaporanHarian::where('peserta_id', $this->id)
                    ->where('penugasan_id', $tugas->id)
                    ->where('progres_tugas', '>', 0) // Peserta berkontribusi
                    ->exists();

                if ($adaLaporan) {
                    $selesaiDivisi++;
                }
            }
        }

        return $selesaiIndividu + $selesaiDivisi;
    }

    // Relasi
    public function user()
    {
        return $this->hasOne(User::class, 'peserta_id');
    }

    public function bagian()
    {
        return $this->belongsTo(Bagian::class, 'bagian_id');
    }

    public function mentor()
    {
        return $this->belongsTo(Mentor::class);
    }

    public function penugasan()
    {
        return $this->hasMany(Penugasan::class, 'peserta_id', 'id');
    }

    /**
     * Mendapatkan semua penugasan yang relevan dengan peserta (individu + divisi)
     */
    public function getAllPenugasan()
    {
        // Ambil tugas individu
        $tugasIndividu = $this->penugasan();

        // Ambil tugas divisi untuk bagian yang sama
        $tugasDivisi = Penugasan::where('kategori', 'Divisi')
            ->where('bagian_id', $this->bagian_id)
            ->whereNull('peserta_id');

        // Gabungkan dalam satu query menggunakan union
        return Penugasan::where('peserta_id', $this->id)
            ->orWhere(function($query) {
                $query->where('kategori', 'Divisi')
                      ->where('bagian_id', $this->bagian_id)
                      ->whereNull('peserta_id');
            });
    }

    /**
     * Scope untuk mendapatkan peserta yang masih aktif untuk form
     * (laporan akhir belum diterima dan masih ada sisa waktu)
     */
    public function scopeAktifUntukForm($query)
    {
        return $query->whereDoesntHave('laporanAkhir', function($subquery) {
            $subquery->where('status', 'terima');
        })
        ->whereRaw('waktu_maksimum - COALESCE(CASE
            WHEN target_method = "sks" THEN sks * 45
            ELSE target_waktu_tugas
        END, 0) > 0');
    }

    public function laporanAkhir()
    {
        return $this->hasMany(LaporanAkhir::class, 'peserta_id', 'id');
    }

    /**
     * Relasi ke laporan harian
     */
    public function laporanHarian()
    {
        return $this->hasMany(LaporanHarian::class, 'peserta_id', 'id');
    }

    /**
     * Mendapatkan laporan akhir yang sudah diterima
     */
    public function laporanAkhirDiterima()
    {
        return $this->hasOne(LaporanAkhir::class, 'peserta_id', 'id')->where('status', 'terima');
    }
}
