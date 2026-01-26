<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mentor extends Model
{
    protected $fillable = [
        'user_id',
        'bagian_id',
        'nama_mentor',
        'email',
        'no_telepon',
        'nomor_identitas',
        'jenis_kelamin',
        'keahlian',
        'alamat',
        'foto',
    ];
    /**
     * Relasi ke User (One-to-One)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke Bagian (Many-to-One)
     */
    public function bagian()
    {
        return $this->belongsTo(Bagian::class);
    }

    /**
     * Relasi ke Peserta (One-to-Many)
     */
    public function pesertas()
    {
        return $this->hasMany(Peserta::class);
    }

    /**
     * Relasi ke LaporanAkhir (One-to-Many)
     */
    public function laporanAkhir()
    {
        return $this->hasMany(LaporanAkhir::class);
    }
}
