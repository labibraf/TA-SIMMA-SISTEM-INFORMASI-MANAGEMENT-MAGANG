<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Peserta;

class LaporanAkhir extends Model
{
    protected $table = 'laporan_akhirs';
    protected $fillable = [
        'peserta_id',
        'mentor_id',
        'judul_laporan',
        'deskripsi_laporan',
        'file_path',
        'status',
        'catatan_mentor',
        'kategori_repository',
        'deskripsi_repository',
    ];

    public function peserta()
    {
        return $this->belongsTo(Peserta::class);
    }
    public function mentor()
    {
        return $this->belongsTo(Mentor::class);
    }

    public function repository()
    {
        return $this->hasOne(Repository::class);
    }
}
