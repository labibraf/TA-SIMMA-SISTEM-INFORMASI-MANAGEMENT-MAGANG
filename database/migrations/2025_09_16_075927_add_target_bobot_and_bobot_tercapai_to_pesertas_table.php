<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pesertas', function (Blueprint $table) {
            $table->integer('target_waktu_tugas')->nullable()->default(0)->after('tipe_magang');
            $table->integer('waktu_tugas_tercapai')->nullable()->default(0)->after('target_waktu_tugas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pesertas', function (Blueprint $table) {
            $table->dropColumn('target_waktu_tugas');
            $table->dropColumn('waktu_tugas_tercapai');
        });
    }
};
