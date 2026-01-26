<?php

namespace App\Models\Traits;

use App\Models\LaporanAkhir;
use App\Models\LaporanHarian;

/**
 * Trait HasLaporanBehav
 *
 * Trait untuk mengelola behavior terkait laporan peserta magang
 * Menangani laporan akhir, laporan harian, dan validasi terkait
 */
trait HasLaporanBehav
{
    /**
     * Relasi ke LaporanAkhir (One-to-Many)
     */
    public function laporanAkhir()
    {
        return $this->hasMany(LaporanAkhir::class);
    }

    /**
     * Relasi ke LaporanHarian (One-to-Many)
     */
    public function laporanHarian()
    {
        return $this->hasMany(LaporanHarian::class);
    }

    /**
     * Mendapatkan laporan akhir yang sudah diterima
     */
    public function laporanAkhirDiterima()
    {
        return $this->hasOne(LaporanAkhir::class, 'peserta_id', 'id')->where('status', 'terima');
    }

    /**
     * Mengecek apakah peserta sudah menyelesaikan laporan akhir (status terima)
     *
     * @return bool
     */
    public function getIsLapSelesai()
    {
        return $this->laporanAkhir()->where('status', 'terima')->exists();
    }

    /**
     * Mengecek apakah peserta bisa submit laporan akhir
     * Syarat: Target waktu tercapai DAN semua tugas selesai
     *
     * @return bool
     */
    public function getBisaLaporan()
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
     * Mengecek apakah peserta masih memenuhi syarat untuk tampil di form
     * Syarat: Laporan akhir belum diterima DAN masih ada sisa waktu dari maksimal - minimum
     *
     * @return bool
     */
    public function getIsAktifForm()
    {
        // Jika laporan akhir sudah diterima, tidak aktif
        if ($this->getIsLapSelesai()) {
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
     *
     * @return bool
     */
    public function getCanEditAkademis()
    {
        return !$this->getIsLapSelesai();
    }

    /**
     * Mendapatkan list field yang tidak dapat diedit jika laporan akhir sudah diterima
     *
     * @return array
     */
    public function getProtectedFields()
    {
        if ($this->getIsLapSelesai()) {
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
}
