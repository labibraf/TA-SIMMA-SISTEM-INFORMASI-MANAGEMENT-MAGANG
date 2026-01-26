<?php

namespace App\Models\Traits;

/**
 * Trait HasStatusMagang
 *
 * Trait untuk mengelola status magang peserta
 * Menangani validasi status, scope query, dan helper methods
 */
trait HasStatusMagang
{
    /**
     * Mendapatkan status magang peserta
     * Status ditentukan berdasarkan kombinasi progress waktu dan pencapaian tugas
     *
     * @return string
     */
    public function getStatusMag()
    {
        if ($this->bisa_laporan_akhir) {
            return 'Siap Laporan Akhir';
        } elseif ($this->progress_percentage >= 100) {
            // Jika waktu kalender habis tapi belum bisa laporan akhir (belum mencapai target tugas)
            return 'Waktu Habis';
        } elseif ($this->progress_percentage > 0) {
            return 'Berjalan';
        } else {
            return 'Awal';
        }
    }

    /**
     * Mendapatkan status peserta untuk ditampilkan di form (text detail)
     *
     * @return string
     */
    public function getStatusText()
    {
        $laporanDiterima = $this->laporanAkhir()->where('status', 'terima')->exists();

        if ($laporanDiterima) {
            return 'Sudah Lulus';
        }

        if ($this->sisa_hari_kerja > 0) {
            return 'Aktif Magang';
        }

        if ($this->waktu_tugas_tercapai < $this->target_waktu_tugas) {
            $kurang = $this->target_waktu_tugas - $this->waktu_tugas_tercapai;
            return 'Masa berakhir - Kurang ' . round($kurang, 1) . ' jam';
        }

        return 'Menunggu Kelulusan';
    }

    /**
     * Scope untuk mendapatkan peserta yang masih aktif untuk form assignment
     *
     * Kriteria peserta yang BISA menerima tugas baru:
     * 1. Laporan akhir belum diterima (belum lulus), ATAU
     * 2. Sudah melewati masa magang TAPI target tugas belum tercapai
     *
     * Kriteria peserta yang TIDAK BISA menerima tugas:
     * - Laporan akhir sudah diterima (sudah lulus) DAN target sudah tercapai
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAktif($query)
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

    /**
     * Mengecek apakah peserta aktif (bisa menerima tugas)
     * Alias untuk bisaMenerimaTugasBaru() dengan nama lebih pendek
     *
     * @return bool
     */
    public function getIsAktif()
    {
        return $this->bisaMenerimaTugasBaru();
    }
}
