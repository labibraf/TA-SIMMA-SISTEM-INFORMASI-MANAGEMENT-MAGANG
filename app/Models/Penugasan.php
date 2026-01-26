<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penugasan extends Model
{
    protected $table = 'penugasans';
    protected $fillable = [
        'judul_tugas',
        'deskripsi_tugas',
        'deadline',
        'status_tugas',
        'feedback',
        'catatan',
        'file',
        'beban_waktu',
        'kategori',
        'mentor_id',
        'bagian_id',
        'peserta_id',
        'is_approved',
    ];

    protected $casts = [
        'deadline' => 'datetime',
    ];
    public function peserta()
    {
        return $this->belongsTo(Peserta::class, 'peserta_id');
    }
    public function mentor()
    {
        return $this->belongsTo(Mentor::class, 'mentor_id');
    }

    /**
     * Relasi ke Bagian/Divisi
     * Foreign Key: bagian_id
     */
    public function bagian()
    {
        return $this->belongsTo(Bagian::class, 'bagian_id');
    }

    /**
     * Relasi ke LaporanHarian (One-to-Many)
     * Satu penugasan bisa memiliki banyak laporan harian
     */
    public function laporanHarian()
    {
        return $this->hasMany(LaporanHarian::class, 'penugasan_id');
    }

    /**
     * Relasi Many-to-Many dengan Peserta (untuk penugasan Divisi)
     * Pivot table: penugasan_peserta
     */
    public function pesertas()
    {
        return $this->belongsToMany(Peserta::class, 'penugasan_peserta', 'penugasan_id', 'peserta_id')
                    ->withTimestamps();
    }

    /**
     * Method helper untuk mendapatkan semua peserta yang ditugaskan
     * (Support kedua kategori: Individu & Divisi)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllPesertas()
    {
        // Untuk penugasan Divisi: ambil dari pivot table
        if ($this->kategori === 'Divisi') {
            return $this->pesertas()->get();
        }

        // Untuk penugasan Individu: wrap peserta dalam collection
        if ($this->kategori === 'Individu' && $this->peserta) {
            return collect([$this->peserta]);
        }

        // Return empty collection jika tidak ada
        return collect();
    }    /**
     * Accessor untuk mendapatkan nama yang ditugaskan
     * Digunakan untuk tampilan UI
     */
    public function getDitugaskanAttribute()
    {
        if ($this->kategori === 'Individu' && $this->peserta) {
            return $this->peserta->user->name;
        }

        if ($this->kategori === 'Divisi') {
            return 'Divisi ' . ($this->bagian->nama_bagian ?? 'bagian ini');
        }

        return 'Tidak ada peserta';
    }

    /**
     * Alias untuk backward compatibility
     */
    public function laporanHarians()
    {
        return $this->laporanHarian();
    }


    /**
     * Model events untuk update waktu tugas tercapai peserta
     */
    protected static function booted()
    {
        static::saved(function ($penugasan) {
            $penugasan->updatePesertaWaktuTugas();
        });

        static::deleted(function ($penugasan) {
            $penugasan->updatePesertaWaktuTugas();
        });
    }

    /**
     * Helper method untuk update waktu tugas tercapai semua peserta terkait
     */
    private function updatePesertaWaktuTugas()
    {
        $allPesertas = $this->getAllPesertas();

        foreach ($allPesertas as $peserta) {
            if (method_exists($peserta, 'updateWaktuTugasTercapai')) {
                $peserta->updateWaktuTugasTercapai();
            }
        }
    }
}
