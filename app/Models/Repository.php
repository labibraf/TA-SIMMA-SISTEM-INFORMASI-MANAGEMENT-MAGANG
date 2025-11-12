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
