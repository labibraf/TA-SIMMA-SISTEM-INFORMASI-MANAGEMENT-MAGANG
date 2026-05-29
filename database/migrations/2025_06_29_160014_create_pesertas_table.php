<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesertas', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lengkap');
            $table->string('email');
            $table->string('no_telepon');
            $table->string('alamat');
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
            $table->string('asal_instansi');
            $table->string('jurusan');
            $table->integer('nomor_identitas')->unique(); // e.g., NIK, KTP, etc.
            $table->enum('tipe_magang', ['Kerja Praktik', 'Magang Nasional', 'Penelitian']);// e.g., mandiri, MBKM, dll etc.
            $table->date('tanggal_mulai_magang')->nullable();
            $table->date('tanggal_selesai_magang')->nullable();
            // $table->string('foto')->nullable(); // Path to the photo
            // $table->string('status_peserta')->default('aktif'); // aktif, tidak aktif
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesertas');
    }
};
