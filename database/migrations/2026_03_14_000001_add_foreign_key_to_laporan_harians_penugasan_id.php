<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('laporan_harians') && Schema::hasTable('penugasans') && Schema::hasColumn('laporan_harians', 'penugasan_id')) {
            $this->addLaporanHarianPenugasanForeignKey();
        }

        if (Schema::hasTable('pesertas') && Schema::hasTable('mentors') && Schema::hasColumn('pesertas', 'mentor_id')) {
            $this->addPesertaMentorForeignKey();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('laporan_harians') && $this->foreignKeyExists('laporan_harians', 'laporan_harians_penugasan_id_foreign')) {
            Schema::table('laporan_harians', function (Blueprint $table) {
                $table->dropForeign('laporan_harians_penugasan_id_foreign');
            });
        }

        if (Schema::hasTable('pesertas') && $this->foreignKeyExists('pesertas', 'pesertas_mentor_id_foreign')) {
            Schema::table('pesertas', function (Blueprint $table) {
                $table->dropForeign('pesertas_mentor_id_foreign');
            });
        }
    }

    private function addLaporanHarianPenugasanForeignKey(): void
    {
        if ($this->foreignKeyExists('laporan_harians', 'laporan_harians_penugasan_id_foreign')) {
            return;
        }

        Schema::table('laporan_harians', function (Blueprint $table) {
            $table->foreign('penugasan_id')
                ->references('id')
                ->on('penugasans')
                ->cascadeOnDelete();
        });
    }

    private function addPesertaMentorForeignKey(): void
    {
        if ($this->foreignKeyExists('pesertas', 'pesertas_mentor_id_foreign')) {
            return;
        }

        Schema::table('pesertas', function (Blueprint $table) {
            $table->foreign('mentor_id')
                ->references('id')
                ->on('mentors')
                ->nullOnDelete();
        });
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            return DB::table('information_schema.TABLE_CONSTRAINTS')
                ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', $table)
                ->where('CONSTRAINT_NAME', $constraintName)
                ->exists();
        }

        return false;
    }
};
