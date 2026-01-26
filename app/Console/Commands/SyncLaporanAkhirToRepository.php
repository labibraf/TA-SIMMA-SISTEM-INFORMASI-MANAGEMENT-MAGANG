<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LaporanAkhir;
use App\Models\Repository;
use Carbon\Carbon;

class SyncLaporanAkhirToRepository extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repository:sync-laporan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi laporan akhir yang diterima ke repository';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai sinkronisasi laporan akhir ke repository...');

        // Ambil semua laporan akhir yang sudah diterima (cek berbagai kemungkinan status)
        $laporanAkhirs = LaporanAkhir::whereIn('status', ['diterima', 'Diterima', 'terima', 'Terima'])
            ->with(['peserta.bagian'])
            ->get();

        if ($laporanAkhirs->isEmpty()) {
            $this->warn('Tidak ada laporan akhir yang diterima.');
            $this->info('Mencoba cek semua status yang ada...');

            // Debug: tampilkan semua status yang ada
            $allStatuses = LaporanAkhir::select('status')->distinct()->pluck('status');
            $this->info('Status yang ditemukan: ' . $allStatuses->implode(', '));

            return;
        }

        $this->info("Ditemukan {$laporanAkhirs->count()} laporan akhir yang diterima.");

        $created = 0;
        $skipped = 0;

        foreach ($laporanAkhirs as $laporan) {
            // Cek apakah sudah ada di repository
            $exists = Repository::where('laporan_akhir_id', $laporan->id)->exists();

            if ($exists) {
                $this->line("⏭️  Melewati: {$laporan->judul_laporan} (sudah ada di repository)");
                $skipped++;
                continue;
            }

            try {
                // Buat repository baru
                $tahunMagang = $laporan->peserta->tanggal_mulai_magang
                    ? Carbon::parse($laporan->peserta->tanggal_mulai_magang)->format('Y')
                    : date('Y');

                $repository = Repository::create([
                    'laporan_akhir_id' => $laporan->id,
                    'peserta_id' => $laporan->peserta_id,
                    'judul' => $laporan->judul_laporan,
                    'deskripsi' => $laporan->deskripsi_laporan ?? 'Laporan magang dari ' . ($laporan->peserta->nama_lengkap ?? 'peserta'),
                    'deskripsi_lengkap' => null,
                    'tahun_magang' => $tahunMagang,
                    'bagian' => $laporan->peserta->bagian->nama_bagian ?? null,
                    'kategori' => null, // Bisa diisi manual nanti
                    'is_published' => true,
                    'published_at' => now(),
                ]);

                $this->info("✅ Berhasil: {$laporan->judul_laporan}");
                $created++;

            } catch (\Exception $e) {
                $this->error("❌ Gagal: {$laporan->judul_laporan} - Error: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("=== Selesai ===");
        $this->info("✅ Berhasil dibuat: {$created}");
        $this->warn("⏭️  Dilewati: {$skipped}");
        $this->info("📊 Total repository sekarang: " . Repository::count());
    }
}
