<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_akhirs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_id')->constrained('pesertas')->cascadeOnDelete();
            $table->foreignId('mentor_id')->constrained('mentors')->cascadeOnDelete();
            $table->string('judul_laporan');
            $table->text('deskripsi_laporan');
            $table->string('file_path'); // Wajib diisi, tidak nullable
            $table->enum('status', ['draft', 'review', 'terima', 'tolak'])->default('draft');
            $table->text('catatan_mentor')->nullable();
            $table->string('kategori_repository')->nullable();
            $table->text('deskripsi_repository')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_akhirs');
    }
};
