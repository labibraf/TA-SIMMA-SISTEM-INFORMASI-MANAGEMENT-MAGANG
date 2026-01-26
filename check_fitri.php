<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$peserta = App\Models\Peserta::where('nama_lengkap', 'LIKE', '%Fitri%')->first();

if ($peserta) {
    echo "=== DATA PESERTA ===" . PHP_EOL;
    echo "Nama: " . $peserta->nama_lengkap . PHP_EOL;
    echo "Tanggal Mulai: " . $peserta->tanggal_mulai_magang . PHP_EOL;
    echo "Tanggal Selesai: " . $peserta->tanggal_selesai_magang . PHP_EOL;
    echo PHP_EOL;

    echo "=== PERHITUNGAN SISTEM ===" . PHP_EOL;
    echo "Durasi Hari Kerja: " . $peserta->durasi_hari_kerja . " hari" . PHP_EOL;
    echo "Waktu Maksimum: " . $peserta->waktu_maksimum . " jam" . PHP_EOL;
    echo "Target Bobot Tugas (min): " . $peserta->target_bobot_tugas . " jam" . PHP_EOL;
    echo PHP_EOL;

    // Perhitungan manual untuk verifikasi
    $start = \Carbon\Carbon::parse($peserta->tanggal_mulai_magang);
    $end = \Carbon\Carbon::parse($peserta->tanggal_selesai_magang);

    $totalDays = $start->diffInDays($end) + 1;
    $weekendDays = $start->diffInWeekendDays($end);
    $workingDays = $totalDays - $weekendDays;

    echo "=== VERIFIKASI MANUAL ===" . PHP_EOL;
    echo "Total hari kalender: " . $totalDays . " hari" . PHP_EOL;
    echo "Hari weekend (Sabtu+Minggu): " . $weekendDays . " hari" . PHP_EOL;
    echo "Hari kerja: " . $workingDays . " hari" . PHP_EOL;
    echo "Jam kerja maksimal (hari kerja × 8): " . ($workingDays * 8) . " jam" . PHP_EOL;
    echo PHP_EOL;

    echo "=== STATUS ===" . PHP_EOL;
    echo ($peserta->durasi_hari_kerja == $workingDays ? "✓" : "✗") . " Perhitungan hari kerja: " . ($peserta->durasi_hari_kerja == $workingDays ? "SESUAI" : "TIDAK SESUAI") . PHP_EOL;
    echo ($peserta->waktu_maksimum == ($workingDays * 8) ? "✓" : "✗") . " Perhitungan jam kerja: " . ($peserta->waktu_maksimum == ($workingDays * 8) ? "SESUAI" : "TIDAK SESUAI") . PHP_EOL;
} else {
    echo "Peserta dengan nama Fitri tidak ditemukan" . PHP_EOL;
}
