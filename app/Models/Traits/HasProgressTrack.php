<?php

namespace App\Models\Traits;

use Carbon\Carbon;

/**
 * Trait HasProgressTrack
 *
 * Trait untuk mengelola progress tracking peserta magang
 * Menangani kalkulasi waktu, hari kerja, dan progress bar
 */
trait HasProgressTrack
{
    /**
     * MENGUBAH LOGIKA: Progress Bar Utama sekarang berdasarkan HARI KERJA (tanpa weekend)
     * Bukan berdasarkan jam tugas yang dikerjakan.
     * Progress menunjukkan: "Hari kerja ke-X dari Y hari kerja total"
     *
     * @return float
     */
    public function getProgressPct()
    {
        // Pastikan tanggal ada
        if (!$this->tanggal_mulai_magang || !$this->tanggal_selesai_magang) {
            return 0;
        }

        $start = Carbon::parse($this->tanggal_mulai_magang);
        $end = Carbon::parse($this->tanggal_selesai_magang);
        $now = Carbon::now();

        // Jika belum mulai magang
        if ($now->lt($start)) {
            return 0;
        }

        // Jika sudah lewat tanggal selesai, mentok 100%
        if ($now->gt($end)) {
            return 100;
        }

        // Hitung total hari kerja (tanpa weekend)
        $totalDays = $start->diffInDays($end) + 1;
        $weekendDays = $start->diffInWeekendDays($end);
        $totalWorkingDays = $totalDays - $weekendDays;

        // Hitung hari kerja yang sudah berjalan
        $daysPassedTotal = $start->diffInDays($now) + 1;
        $weekendDaysPassed = $start->diffInWeekendDays($now);
        $workingDaysPassed = $daysPassedTotal - $weekendDaysPassed;

        // Hitung persentase berdasarkan hari kerja
        if ($totalWorkingDays == 0) {
            return 0;
        }

        $percentage = ($workingDaysPassed / $totalWorkingDays) * 100;

        return round(min($percentage, 100), 2);
    }

    /**
     * BARU: Mendapatkan jumlah hari kerja yang sudah berjalan sampai hari ini
     * Untuk ditampilkan di UI sebagai "Hari kerja ke-X"
     *
     * @return int
     */
    public function getHariKerjaTercapai()
    {
        if (!$this->tanggal_mulai_magang) {
            return 0;
        }

        $start = Carbon::parse($this->tanggal_mulai_magang);
        $now = Carbon::now();

        // Jika belum mulai
        if ($now->lt($start)) {
            return 0;
        }

        // Hitung hari yang sudah lewat
        $daysPassedTotal = $start->diffInDays($now) + 1;

        // Kurangi weekend
        $weekendDaysPassed = $start->diffInWeekendDays($now);

        return $daysPassedTotal - $weekendDaysPassed;
    }

    /**
     * Mendapatkan sisa hari kerja dari hari ini sampai tanggal selesai magang
     * Tidak termasuk weekend (Sabtu & Minggu)
     *
     * @return int
     */
    public function getSisaHari()
    {
        if (!$this->tanggal_selesai_magang) {
            return 0;
        }

        $now = Carbon::now();
        $end = Carbon::parse($this->tanggal_selesai_magang);

        // Jika sudah lewat tanggal selesai
        if ($now->gt($end)) {
            return 0;
        }

        // Hitung total hari dari sekarang sampai selesai (inklusif)
        $totalDays = $now->diffInDays($end) + 1;

        // Kurangi hari weekend
        $weekendDays = $now->diffInWeekendDays($end);

        return $totalDays - $weekendDays;
    }

    /**
     * Mendapatkan sisa jam kerja berdasarkan kondisi:
     * - Jika masih dalam masa magang: Sisa Hari Kerja × 8 jam
     * - Jika sudah melewati masa magang: Sisa target yang belum tercapai
     *
     * @return float
     */
    public function getSisaJam()
    {
        // Jika masih dalam masa magang, gunakan sisa hari kerja
        $sisaHari = $this->getSisaHari();

        if ($sisaHari > 0) {
            return $sisaHari * 8;
        }

        // Jika sudah melewati masa magang, hitung sisa dari target
        if ($this->target_waktu_tugas && $this->waktu_tugas_tercapai < $this->target_waktu_tugas) {
            $sisaTarget = $this->target_waktu_tugas - $this->waktu_tugas_tercapai;
            return max(0, $sisaTarget); // Minimal 0
        }

        return 0;
    }

    /**
     * Menghitung waktu maksimum berdasarkan durasi magang
     *
     * @return int
     */
    public function getWaktuMaks()
    {
        if ($this->tanggal_mulai_magang && $this->tanggal_selesai_magang) {
            $start = Carbon::parse($this->tanggal_mulai_magang);
            $end = Carbon::parse($this->tanggal_selesai_magang);

            // 1. Hitung total hari kalender (inklusif)
            $totalDays = $start->diffInDays($end) + 1;

            // 2. Hitung jumlah hari Sabtu & Minggu dalam rentang itu
            $weekendDays = $start->diffInWeekendDays($end);

            // 3. Dapatkan hari kerja (Total - Weekend)
            $workingDays = $totalDays - $weekendDays;

            // 4. Kembalikan jam kerja (Hari Kerja * 8 jam)
            return $workingDays * 8;
        }

        return 0;
    }

    /**
     * Mendapatkan durasi hari kerja total (tanpa weekend)
     *
     * @return int
     */
    public function getDurasiHariKerja()
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
}
