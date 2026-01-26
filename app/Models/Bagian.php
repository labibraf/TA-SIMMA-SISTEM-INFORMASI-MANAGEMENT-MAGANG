<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bagian extends Model
{
    protected $fillable = [
        'nama_bagian'
    ];

    /**
     * Relasi ke Peserta (One-to-Many)
     */
    public function pesertas()
    {
        return $this->hasMany(Peserta::class);
    }

    /**
     * Relasi ke Mentor (One-to-Many)
     */
    public function mentors()
    {
        return $this->hasMany(Mentor::class);
    }
}
