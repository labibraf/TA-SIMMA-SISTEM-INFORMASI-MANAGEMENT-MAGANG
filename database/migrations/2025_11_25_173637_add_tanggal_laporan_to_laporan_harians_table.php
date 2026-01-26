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
        Schema::table('laporan_harians', function (Blueprint $table) {
            $table->date('tanggal_laporan')->nullable()->after('penugasan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan_harians', function (Blueprint $table) {
            $table->dropColumn('tanggal_laporan');
        });
    }
};
