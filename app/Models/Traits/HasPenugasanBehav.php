<?php

namespace App\Models\Traits;

use App\Models\Penugasan;
use App\Models\LaporanHarian;

/**
 * Trait HasPenugasanBehav
 *
 * Trait untuk mengelola behavior terkait penugasan peserta magang
 * Menangani tugas individu, tugas divisi, validasi, dan kalkulasi
 */
trait HasPenugasanBehav
{
    /**
     * Mendapatkan semua tugas yang menyangkut peserta ini
     * Termasuk tugas individu dan tugas divisi
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTugasPeserta()
    {
        // Tugas individu: yang peserta_id-nya sama dengan peserta ini
        $tugasIndividu = Penugasan::where('peserta_id', $this->id)
            ->where('kategori', 'Individu')
            ->get();

        // Tugas divisi: yang peserta ini sudah di-assign (melalui pivot table)
        $tugasDivisi = Penugasan::where('kategori', 'Divisi')
            ->whereHas('pesertas', function($query) {
                $query->where('peserta_id', $this->id);
            })
            ->get();

        // Gabungkan kedua collection
        return $tugasIndividu->merge($tugasDivisi);
    }

    /**
     * Mendapatkan tugas yang sudah selesai (selesai dan di-approve)
     *
     * @return int
     */
    public function getTugasSelesai()
    {
        // Hitung tugas individu yang selesai dan di-approve
        $selesaiIndividu = $this->penugasan()
            ->where('status_tugas', 'Selesai')
            ->where('is_approved', 1)
            ->count();

        // Hitung tugas divisi yang selesai dan di-approve
        $selesaiDivisi = 0;
        if ($this->bagian_id) {
            $tugasDivisi = Penugasan::where('kategori', 'Divisi')
                ->where('bagian_id', $this->bagian_id)
                ->where('status_tugas', 'Selesai')
                ->where('is_approved', 1)
                ->whereNull('peserta_id')
                ->get();

            foreach ($tugasDivisi as $tugas) {
                // Periksa apakah peserta ini pernah melaporkan tugas divisi tersebut
                $adaLaporan = LaporanHarian::where('peserta_id', $this->id)
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

    /**
     * Mendapatkan daftar tugas yang belum selesai untuk peserta ini
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTugasBelumSelesai()
    {
        return $this->getTugasPeserta()->filter(function ($tugas) {
            // Cek apakah tugas gugur/terlambat
            $isOverdue = $tugas->deadline && now()->greaterThan(\Carbon\Carbon::parse($tugas->deadline)->endOfDay());

            // Cek apakah benar-benar selesai
            if ($tugas->kategori === 'Divisi') {
                $isSelesaiBetulan = ($tugas->is_approved == 1);
            } else {
                $latestLaporan = $tugas->laporanHarian->last();
                $progress = $latestLaporan ? $latestLaporan->progres_tugas : 0;
                $isSelesaiBetulan = ($tugas->is_approved == 1 || $progress == 100);
            }

            $isGugur = $isOverdue && !$isSelesaiBetulan;

            // Tugas dianggap "belum selesai" jika: belum selesai DAN tidak gugur
            // Tugas gugur tidak masuk hitungan karena sudah tidak relevan
            $belumSelesai = ($tugas->status_tugas !== 'Selesai' || $tugas->is_approved != 1);

            return $belumSelesai && !$isGugur;
        });
    }

    /**
     * Mengecek apakah semua tugas yang menyangkut peserta ini sudah selesai dan di-approve
     *
     * @return bool
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
        // ATAU tugas sudah gugur/terlambat (maka tidak perlu diselesaikan)
        $semuaSelesai = $tugasPeserta->every(function ($tugas) {
            // Cek apakah tugas gugur/terlambat
            $isOverdue = $tugas->deadline && now()->greaterThan(\Carbon\Carbon::parse($tugas->deadline)->endOfDay());

            // Cek apakah benar-benar selesai
            if ($tugas->kategori === 'Divisi') {
                $isSelesaiBetulan = ($tugas->is_approved == 1);
            } else {
                $latestLaporan = $tugas->laporanHarian->last();
                $progress = $latestLaporan ? $latestLaporan->progres_tugas : 0;
                $isSelesaiBetulan = ($tugas->is_approved == 1 || $progress == 100);
            }

            $isGugur = $isOverdue && !$isSelesaiBetulan;

            // Tugas dianggap SELESAI jika:
            // 1. Sudah selesai dan di-approve, ATAU
            // 2. Tugas sudah gugur/terlambat (tidak relevan lagi)
            return ($tugas->status_tugas === 'Selesai' && $tugas->is_approved == 1) || $isGugur;
        });

        return $semuaSelesai;
    }

    /**
     * Helper untuk validasi input tugas
     * Mengecek apakah penambahan jam akan melebihi Batas Maksimal
     *
     * @param float $jamTambahan Jumlah jam yang akan ditambahkan
     * @return bool True jika masih boleh ditambah, False jika akan melebihi batas
     */
    public function canAddJam($jamTambahan)
    {
        $totalNanti = $this->waktu_tugas_tercapai + $jamTambahan;
        // Pastikan tidak melebihi waktu maksimum
        return $totalNanti <= $this->waktu_maksimum;
    }

    /**
     * Mendapatkan semua penugasan yang relevan dengan peserta (individu + divisi)
     */
    public function getAllPenugasan()
    {
        // Gabungkan dalam satu query menggunakan union
        return Penugasan::where('peserta_id', $this->id)
            ->orWhere(function($query) {
                $query->where('kategori', 'Divisi')
                      ->where('bagian_id', $this->bagian_id)
                      ->whereNull('peserta_id');
            });
    }

    /**
     * Menghitung total semua tugas yang relevan dengan peserta (individu + divisi)
     * Termasuk tugas yang telat/gugur
     *
     * @return int
     */
    public function getTotalTugas()
    {
        // Hitung tugas individu (semua tugas yang di-assign)
        $totalIndividu = $this->penugasan()->count();

        // Hitung tugas divisi yang peserta ini DI-ASSIGN (ada di pivot table)
        $totalDivisi = 0;
        if ($this->bagian_id) {
            // Hitung semua tugas divisi yang peserta ini di-assign
            // Tidak peduli apakah sudah dilaporkan atau belum
            $totalDivisi = Penugasan::where('kategori', 'Divisi')
                ->where('bagian_id', $this->bagian_id)
                ->whereHas('pesertas', function($query) {
                    $query->where('peserta_id', $this->id);
                })
                ->count();
        }

        return $totalIndividu + $totalDivisi;
    }

    /**
     * Mengecek apakah peserta masih bisa menerima tugas baru
     *
     * @return bool
     */
    public function bisaMenerimaTugasBaru()
    {
        // Jika laporan akhir sudah diterima, tidak bisa menerima tugas lagi
        $laporanDiterima = $this->laporanAkhir()->where('status', 'terima')->exists();
        if ($laporanDiterima) {
            return false;
        }

        // Jika target sudah tercapai dan masa magang sudah berakhir, tidak bisa menerima tugas
        if ($this->sisa_hari_kerja <= 0 && $this->waktu_tugas_tercapai >= $this->target_waktu_tugas) {
            return false;
        }

        return true;
    }
}
