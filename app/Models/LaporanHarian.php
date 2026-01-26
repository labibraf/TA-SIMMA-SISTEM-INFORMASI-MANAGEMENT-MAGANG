<?php

namespace App\Models;

use App\Models\Penugasan;
use App\Models\Peserta;
use Illuminate\Database\Eloquent\Model;

class LaporanHarian extends Model
{
    protected $table = 'laporan_harians';
    protected $fillable = [
        'peserta_id',
        'penugasan_id',
        'tanggal_laporan',
        'deskripsi_kegiatan',
        'status_tugas',
        'progres_tugas',
        'file',
    ];

    protected static function booted()
    {
        static::saved(function ($laporan) {
            if ($laporan->progres_tugas == 100 && $laporan->penugasan) {
                $laporan->penugasan->update(['status_tugas' => 'Selesai']);
            }

            // Update waktu tugas tercapai peserta setiap kali laporan disimpan
            if ($laporan->peserta && method_exists($laporan->peserta, 'updateWaktuTugasTercapai')) {
                $laporan->peserta->updateWaktuTugasTercapai();
            }

            // Untuk tugas divisi, update semua peserta di bagian yang sama
            if ($laporan->penugasan && $laporan->penugasan->kategori === 'Divisi' && $laporan->penugasan->bagian_id) {
                $pesertasInBagian = \App\Models\Peserta::where('bagian_id', $laporan->penugasan->bagian_id)->get();
                foreach ($pesertasInBagian as $peserta) {
                    if (method_exists($peserta, 'updateWaktuTugasTercapai')) {
                        $peserta->updateWaktuTugasTercapai();
                    }
                }
            }
        });

        static::deleted(function ($laporan) {
            // Update waktu tugas tercapai peserta ketika laporan dihapus
            if ($laporan->peserta && method_exists($laporan->peserta, 'updateWaktuTugasTercapai')) {
                $laporan->peserta->updateWaktuTugasTercapai();
            }

            // Untuk tugas divisi, update semua peserta di bagian yang sama
            if ($laporan->penugasan && $laporan->penugasan->kategori === 'Divisi' && $laporan->penugasan->bagian_id) {
                $pesertasInBagian = \App\Models\Peserta::where('bagian_id', $laporan->penugasan->bagian_id)->get();
                foreach ($pesertasInBagian as $peserta) {
                    if (method_exists($peserta, 'updateWaktuTugasTercapai')) {
                        $peserta->updateWaktuTugasTercapai();
                    }
                }
            }
        });
    }

    /**
     * Relasi ke Peserta (Many-to-One)
     */
    public function peserta()
    {
        return $this->belongsTo(Peserta::class);
    }

    /**
     * Relasi ke Penugasan (Many-to-One)
     */
    public function penugasan()
    {
        return $this->belongsTo(Penugasan::class);
    }
}
