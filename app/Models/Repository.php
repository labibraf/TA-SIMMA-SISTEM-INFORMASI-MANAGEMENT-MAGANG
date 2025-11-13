<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Repository extends Model
{
    protected $table = 'repositories';

    protected $fillable = [
        'judul',
        'deskripsi',
        'deskripsi_lengkap',
        'laporan_akhir_id',
        'peserta_id',
        'file_path',
        'nama_peserta',
        'tahun_magang',
        'bagian',
        'kategori',
        'views',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'views' => 'integer',
    ];

    /**
     * Accessor untuk mendapatkan nama peserta (dari sistem atau manual)
     */
    public function getNamaPesertaLengkapAttribute()
    {
        if ($this->peserta) {
            // Dari sistem: ambil dari relasi peserta
            return $this->peserta->nama_lengkap ?? ($this->peserta->user->name ?? 'N/A');
        }

        // Dari manual: ambil dari kolom nama_peserta
        return $this->nama_peserta ?? 'N/A';
    }

    /**
     * Accessor untuk mendapatkan file path laporan (dari sistem atau manual)
     */
    public function getFilePathLaporanAttribute()
    {
        if ($this->laporanAkhir && $this->laporanAkhir->file_path) {
            // Dari sistem: ambil dari laporan akhir
            return $this->laporanAkhir->file_path;
        }

        // Dari manual: ambil dari kolom file_path
        return $this->file_path;
    }

    /**
     * Cek apakah repository ini dari input manual atau sistem
     */
    public function getIsManualAttribute()
    {
        return is_null($this->laporan_akhir_id);
    }

    public function laporanAkhir(): BelongsTo
    {
        return $this->belongsTo(LaporanAkhir::class, 'laporan_akhir_id');
    }

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class, 'peserta_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('tahun_magang', $year);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('kategori', $category);
    }

    public function scopeByBagian($query, $bagian)
    {
        return $query->where('bagian', $bagian);
    }

    public function scopeManual($query)
    {
        return $query->whereNull('laporan_akhir_id');
    }

    public function scopeSistem($query)
    {
        return $query->whereNotNull('laporan_akhir_id');
    }

    public function incrementViews()
    {
        $this->increment('views');
    }

    public function publish()
    {
        $this->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    public function unpublish()
    {
        $this->update([
            'is_published' => false,
            'published_at' => null,
        ]);
    }
}
