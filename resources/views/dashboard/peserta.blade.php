@extends('layouts.mantis')

@section('content')
<style>
    .card-grad-success { background: linear-gradient(135deg, #2dce89 0%, #2dcecc 100%); }
    .card-grad-primary { background: linear-gradient(135deg, #5c92fe 0%, #825ee4 100%); }
    .card-grad-warning { background: linear-gradient(135deg, #ffc107 0%, #ff8b67 100%); }
    .card-grad-info    { background: linear-gradient(135deg, #20a6e7 0%, #4facfe 100%); }
</style>

<div class="">
    <!-- PRIORITAS 1: Kartu Statistik Utama (At-a-Glance Cards) -->
    <div class="row">
        <!-- Kartu 1: Progres Magang -->
        <div class="col-md-3 col-sm-6">
            <div class="card card-grad-success text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h3 class="text-white m-0 mb-1">{{ number_format($progressPercentage, 1) }}%</h3>
                            <p class="mb-0 text-white-50">Progres Magang Anda</p>
                        </div>
                        <div class="avtar avtar-xl bg-white bg-opacity-25">
                            <i class="ti ti-trending-up fs-1"></i>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 6px; background: rgba(255,255,255,0.3);">
                        <div class="progress-bar bg-white" role="progressbar"
                             style="width: {{ $progressPercentage }}%"
                             aria-valuenow="{{ $progressPercentage }}"
                             aria-valuemin="0"
                             aria-valuemax="100">
                        </div>
                    </div>
                    <p class="mb-0 mt-2 text-white-50">
                        <i class="ti ti-calendar me-1"></i>{{ $sisaWaktu }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Kartu 2: Total Jam Tercapai -->
        <div class="col-md-3 col-sm-6">
            <div class="card card-grad-primary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h3 class="text-white m-0 mb-1">{{ number_format($totalJamTercapai, 1) }}</h3>
                            <p class="mb-0 text-white-50">Jam Minimal Tercapai</p>
                        </div>
                        <div class="avtar avtar-xl bg-white bg-opacity-25">
                            <i class="ti ti-clock-hour-4 fs-3"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="mb-0 text-white">
                            <span class="fw-semibold">Target: {{ number_format($targetJam, 1) }} jam</span>
                        </p>
                        @php
                            $selisihJam = $targetJam - $totalJamTercapai;
                        @endphp
                        <p class="mb-0 text-white-50 mt-1">
                            @if($selisihJam > 0)
                                <i class="ti ti-arrow-up-right me-1"></i>Sisa {{ number_format($selisihJam, 1) }} jam lagi
                            @else
                                <i class="ti ti-check me-1"></i>Target tercapai !
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kartu 3: Tugas Aktif -->
        <div class="col-md-3 col-sm-6">
            <div class="card card-grad-warning text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h3 class="text-white m-0 mb-1">{{ $tugasAktif }}</h3>
                            <p class="mb-0 text-white-50">Tugas Aktif</p>
                        </div>
                        <div class="avtar avtar-xl bg-white bg-opacity-25">
                            <i class="ti ti-clipboard-check fs-3"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="mb-0 text-white">
                            <span class="fw-semibold">Perlu perhatian Anda</span>
                        </p>
                        <p class="mb-0 text-white-50 mt-1">
                            <i class="ti ti-alert-circle me-1"></i>Segera selesaikan tugas Anda
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kartu 4: Status Laporan Akhir -->
        <div class="col-md-3 col-sm-6">
            <div class="card card-grad-info text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-white m-0 mb-3">Laporan Akhir</h6>
                            <span class="badge {{ $badgeClass }} fs-5 px-3 py-1.5">
                                {{ $statusLaporanAkhir }}
                            </span>
                        </div>
                        <div class="avtar avtar-xl bg-white bg-opacity-25">
                            <i class="ti ti-file-text fs-3"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        @if($statusLaporanAkhir === 'Belum Mengajukan')
                            <a href="{{ route('laporan-akhir.create') }}" class="btn btn-outline-light d-inline-flex">
                                <i class="ti ti-plus me-1"></i>Ajukan Sekarang
                            </a>
                        @elseif($statusLaporanAkhir === 'Perlu Revisi')
                            <a href="{{ route('laporan-akhir.index') }}" class="btn btn-outline-primary d-inline-flex">
                                <i class="ti ti-edit me-1"></i>Lihat Feedback
                            </a>
                        @else
                            <a href="{{ route('laporan-akhir.index') }}" class="btn btn-outline-primary d-inline-flex">
                                <i class="ti ti-eye me-1"></i>Lihat Detail
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PRIORITAS 1: Info Mentor & Magang + Tabel Tugas -->
    <div class="row mt-4">
        <!-- Kartu Info Mentor & Magang -->
        <div class="col-xl-4 col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti ti-info-circle me-2"></i>Informasi Magang
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Info Mentor -->
                    <div class="mb-4">
                        <h6 class="text-muted mb-3">Pembimbing Anda</h6>
                        <div class="d-flex align-items-center mb-3">
                            <div class="avtar avtar-xl bg-light-primary me-3">
                                <i class="ti ti-user fs-3"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">{{ $mentor->user->name ?? 'Belum ditentukan' }}</h6>
                                <p class="mb-0 text-muted small">Mentor Pembimbing</p>
                            </div>
                        </div>
                        @if($mentor)
                        <div class="list-group list-group-flush">
                            <div class="list-group-item px-0 py-2">
                                <small class="text-muted">
                                    <i class="ti ti-mail me-2"></i>{{ $mentor->email ?? 'Email tidak tersedia' }}
                                </small>
                            </div>
                            <div class="list-group-item px-0 py-2">
                                <small class="text-muted">
                                    <i class="ti ti-phone me-2"></i>{{ $mentor->no_telepon ?? 'No. telepon tidak tersedia' }}
                                </small>
                            </div>
                        </div>
                        @endif
                    </div>

                    <hr class="my-3">

                    <!-- Info Magang -->
                    <div>
                        <h6 class="text-muted mb-3">Detail Magang</h6>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                                <span class="text-muted small">
                                    <i class="ti ti-building me-2"></i>Divisi/Bagian
                                </span>
                                <span class="fw-semibold small">{{ $bagian->nama_bagian ?? 'Belum ditentukan' }}</span>
                            </div>
                            <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                                <span class="text-muted small">
                                    <i class="ti ti-calendar-event me-2"></i>Tanggal Mulai
                                </span>
                                <span class="fw-semibold small">{{ $tanggalMulaiFormatted }}</span>
                            </div>
                            <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                                <span class="text-muted small">
                                    <i class="ti ti-calendar-check me-2"></i>Tanggal Selesai
                                </span>
                                <span class="fw-semibold small">{{ $tanggalSelesaiFormatted }}</span>
                            </div>
                            <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                                <span class="text-muted small">
                                    <i class="ti ti-briefcase me-2"></i>Tipe Magang
                                </span>
                                <span class="fw-semibold small">{{ ucwords($peserta->tipe_magang) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Tugas Anda -->
        <div class="col-xl-8 col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ti ti-list-check me-2"></i>Tugas Anda
                    </h5>
                    <span class="badge bg-primary">{{ $tugasSaya->count() }} Total Tugas</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Judul Tugas</th>
                                    <th>Kategori</th>
                                    <th>Deadline</th>
                                    <th>Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tugasSaya as $tugas)
                                <tr>
                                    <td>
                                        <div>
                                            <div class="fw-semibold">{{ Str::limit($tugas->judul_tugas, 40) }}</div>
                                            <small class="text-muted">
                                                <i class="ti ti-clock-hour-4 me-1"></i>
                                                {{ $tugas->beban_waktu }} jam
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        @if($tugas->kategori === 'Individu')
                                            <span class="badge bg-light-primary">
                                                <i class="ti ti-user me-1"></i>Individu
                                            </span>
                                        @else
                                            <span class="badge bg-light-info">
                                                <i class="ti ti-users me-1"></i>Divisi
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($tugas->deadline)->format('d M Y') }}
                                        </small>
                                        @php
                                            $deadline = \Carbon\Carbon::parse($tugas->deadline);
                                            $hariTersisa = now()->diffInDays($deadline, false);
                                        @endphp
                                        @if($hariTersisa < 0 && $tugas->status_tugas !== 'Selesai')
                                            <br><span class="badge bg-danger badge-sm">Terlambat</span>
                                        @elseif($hariTersisa <= 3 && $tugas->status_tugas !== 'Selesai')
                                            <br><span class="badge bg-warning badge-sm">Segera!</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($tugas->status_tugas === 'Selesai')
                                            @if($tugas->is_approved)
                                                <span class="badge bg-success">
                                                    <i class="ti ti-check me-1"></i>Selesai
                                                </span>
                                            @else
                                                <span class="badge bg-warning">
                                                    <i class="ti ti-clock me-1"></i>Review
                                                </span>
                                            @endif
                                        @elseif($tugas->status_tugas === 'Dikerjakan')
                                            <span class="badge bg-info">
                                                <i class="ti ti-progress me-1"></i>Dikerjakan
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                <i class="ti ti-circle-dotted me-1"></i>Belum
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('penugasans.show', $tugas->id) }}"
                                           class="btn btn-sm btn-primary">
                                            <i class="ti ti-eye me-1"></i>Detail
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <i class="ti ti-clipboard-off fs-1 text-muted d-block mb-2"></i>
                                        <p class="text-muted mb-0">Belum ada tugas yang diberikan</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PRIORITAS 2: Visualisasi Data (Charts) -->
    <div class="row mt-4">
        <!-- Chart 1: Distribusi Beban Kerja -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti ti-chart-donut me-2"></i>Distribusi Tugas
                    </h5>
                    <p class="text-muted small mb-0">Status tugas yang dikerjakan</p>
                </div>
                <div class="card-body">
                    <div id="chart-distribusi-beban-kerja"></div>
                    <div class="row mt-3 text-center">
                        <div class="col-4">
                            <div class="p-2">
                                <i class="ti ti-circle-filled text-success"></i>
                                <h6 class="mb-0 mt-1">{{ $tugasSelesai }}</h6>
                                <small class="text-muted">Selesai</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2">
                                <i class="ti ti-circle-filled text-warning"></i>
                                <h6 class="mb-0 mt-1">{{ $tugasDikerjakan }}</h6>
                                <small class="text-muted">Dikerjakan</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2">
                                <i class="ti ti-circle-filled text-danger"></i>
                                <h6 class="mb-0 mt-1">{{ $tugasBelumDimulai }}</h6>
                                <small class="text-muted">Belum Dimulai</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart 2: Tren Aktivitas Harian -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti ti-chart-line me-2"></i>Tren Aktivitas Harian
                    </h5>
                    <p class="text-muted small mb-0">Laporan harian Anda dalam 14 hari terakhir</p>
                </div>
                <div class="card-body">
                    <div id="chart-tren-aktivitas"></div>
                    @php
                        $totalLaporan14Hari = array_sum($trendAktivitas);
                        $rataRataLaporan = $totalLaporan14Hari > 0 ? round($totalLaporan14Hari / 14, 1) : 0;
                    @endphp
                    <div class="row mt-3 text-center">
                        <div class="col-6">
                            <div class="p-2">
                                <h6 class="mb-0 text-primary">{{ $totalLaporan14Hari }}</h6>
                                <small class="text-muted">Total Laporan (14 Hari)</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2">
                                <h6 class="mb-0 text-info">{{ $rataRataLaporan }}</h6>
                                <small class="text-muted">Rata-rata per Hari</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PRIORITAS 2: Tabel Log Laporan Harian Terbaru -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <i class="ti ti-file-text me-2"></i>Log Laporan Harian Terbaru
                        </h5>
                        <p class="text-muted small mb-0">7 entri terakhir dari aktivitas Anda</p>
                    </div>
                    <a href="{{ route('laporan_harian.index') }}" class="btn btn-sm btn-outline-primary">
                        <i class="ti ti-list me-1"></i>Lihat Semua
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Tugas Terkait</th>
                                    <th>Deskripsi Kegiatan</th>
                                    <th>Progres</th>
                                    <th>Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($laporanHarianTerbaru as $laporan)
                                <tr>
                                    <td>
                                        <div>
                                            <div class="fw-semibold">
                                                {{ \Carbon\Carbon::parse($laporan->created_at)->format('d M Y') }}
                                            </div>
                                            <small class="text-muted">
                                                {{ \Carbon\Carbon::parse($laporan->created_at)->diffForHumans() }}
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        @if($laporan->penugasan)
                                            <div class="small">
                                                {{ Str::limit($laporan->penugasan->judul_tugas, 30) }}
                                            </div>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="small">
                                            {{ Str::limit($laporan->deskripsi_kegiatan, 50) }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1 me-2">
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar bg-primary"
                                                         role="progressbar"
                                                         style="width: {{ $laporan->progres_tugas }}%"
                                                         aria-valuenow="{{ $laporan->progres_tugas }}"
                                                         aria-valuemin="0"
                                                         aria-valuemax="100">
                                                    </div>
                                                </div>
                                            </div>
                                            <small class="text-muted fw-semibold" style="min-width: 40px;">
                                                {{ $laporan->progres_tugas }}%
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            // Cek apakah status_tugas adalah 'Selesai' sebagai indikator validasi
                                            $isValidated = $laporan->status_tugas === 'Selesai';
                                        @endphp
                                        @if($isValidated)
                                            <span class="badge bg-success">
                                                <i class="ti ti-circle-check me-1"></i>Tervalidasi
                                            </span>
                                        @else
                                            <span class="badge bg-warning">
                                                <i class="ti ti-clock me-1"></i>Belum
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('laporan_harian.show', $laporan->id) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="ti ti-file-off fs-1 text-muted d-block mb-2"></i>
                                        <p class="text-muted mb-0">Belum ada laporan harian</p>
                                        <a href="{{ route('laporan_harian.create') }}" class="btn btn-sm btn-primary mt-2">
                                            <i class="ti ti-plus me-1"></i>Buat Laporan Pertama
                                        </a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PRIORITAS 3: Riwayat & Informasi Tambahan -->
    <div class="row mt-4">
        <!-- Riwayat Tugas Selesai -->
        <div class="col-xl-8 col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <i class="ti ti-checkbox me-2"></i>Riwayat Tugas Selesai
                        </h5>
                        <p class="text-muted small mb-0">Portofolio tugas yang telah Anda selesaikan</p>
                    </div>
                    <span class="badge bg-success">{{ $riwayatTugasSelesai->count() }} Tugas</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Judul Tugas</th>
                                    <th>Kategori</th>
                                    <th>Beban Waktu</th>
                                    <th>Tanggal Selesai</th>
                                    <th>Feedback</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($riwayatTugasSelesai as $tugas)
                                <tr>
                                    <td>
                                        <div>
                                            <div class="fw-semibold">{{ Str::limit($tugas->judul_tugas, 35) }}</div>
                                            <small class="text-muted">
                                                <i class="ti ti-user me-1"></i>{{ $tugas->mentor->nama_mentor ?? 'N/A' }}
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        @if($tugas->kategori === 'Individu')
                                            <span class="badge bg-light-primary">
                                                <i class="ti ti-user me-1"></i>Individu
                                            </span>
                                        @else
                                            <span class="badge bg-light-info">
                                                <i class="ti ti-users me-1"></i>Divisi
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light-success">
                                            <i class="ti ti-clock-hour-4 me-1"></i>{{ $tugas->beban_waktu }} jam
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($tugas->updated_at)->format('d M Y') }}
                                        </small>
                                    </td>
                                    <td>
                                        @if($tugas->feedback)
                                            <span class="badge bg-info">
                                                <i class="ti ti-message-circle me-1"></i>Ada Feedback
                                            </span>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('penugasans.show', $tugas->id) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="ti ti-eye me-1"></i>Detail
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="ti ti-clipboard-off fs-1 text-muted d-block mb-2"></i>
                                        <p class="text-muted mb-0">Belum ada tugas yang diselesaikan</p>
                                        <small class="text-muted">Selesaikan tugas pertama Anda untuk memulai portofolio</small>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Area Notifikasi/Pengumuman -->
        <div class="col-xl-4 col-md-12">
            <!-- Motivasi Card -->
            <div class="card">
                <div class="card-body text-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <i class="ti ti-rocket fs-1 mb-3 d-block"></i>
                    <h5 class="text-white mb-2">Terus Semangat!</h5>
                    <p class="mb-3 text-white-50 small">
                        @php
                            $motivasiTexts = [
                                'Setiap progres kecil adalah langkah menuju kesuksesan besar.',
                                'Kerja keras Anda hari ini adalah investasi untuk masa depan.',
                                'Jangan pernah menyerah, kesuksesan ada di depan mata!',
                                'Kegagalan adalah kesempatan untuk memulai lagi dengan lebih cerdas.',
                                'Konsistensi adalah kunci dari pencapaian yang luar biasa.'
                            ];
                            $randomMotivasi = $motivasiTexts[array_rand($motivasiTexts)];
                        @endphp
                        "{{ $randomMotivasi }}"
                    </p>
                    <div class="row text-center">
                        <div class="col-6">
                            <h4 class="text-white mb-0">{{ number_format($progressPercentage, 0) }}%</h4>
                            <small class="text-white-50">Progress</small>
                        </div>
                        <div class="col-6">
                            <h4 class="text-white mb-0">{{ $sisaWaktu }}</h4>
                            <small class="text-white-50">Tersisa</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.avtar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    font-weight: 500;
}

.avtar-s {
    width: 2rem;
    height: 2rem;
    font-size: 0.875rem;
}

.avtar-xl {
    width: 4rem;
    height: 4rem;
    font-size: 1.5rem;
}

.bg-light-primary { background-color: rgba(92, 146, 254, 0.1) !important; color: #5c92fe; }
.bg-light-info { background-color: rgba(32, 166, 231, 0.1) !important; color: #20a6e7; }
.bg-light-success { background-color: rgba(45, 206, 137, 0.1) !important; color: #2dce89; }
.bg-light-warning { background-color: rgba(255, 193, 7, 0.1) !important; color: #ffc107; }

.card {
    border: 1px solid #e9ecef;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: transform 0.2s, box-shadow 0.2s;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.table th {
    font-weight: 600;
    font-size: 0.875rem;
    color: #6c757d;
    border-bottom: 1px solid #dee2e6;
}

.badge-sm {
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
}

.text-white-50 {
    color: rgba(255, 255, 255, 0.7) !important;
}

.list-group-item {
    border: none;
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('template/dist/assets/js/plugins/apexcharts.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {

    // ========== CHART 1: Distribusi Beban Kerja (Donut Chart) ==========
    const distribusiBebanKerjaOptions = {
        series: [{{ $tugasSelesai }}, {{ $tugasDikerjakan }}, {{ $tugasBelumDimulai }}],
        chart: {
            type: 'donut',
            height: 300,
            fontFamily: 'Inter, sans-serif'
        },
        colors: ['#2dce89', '#ffc107', '#dc3545'],
        labels: ['Selesai', 'Dikerjakan', 'Belum Dimulai'],
        legend: {
            show: false
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '70%',
                    labels: {
                        show: true,
                        name: {
                            show: true,
                            fontSize: '14px',
                            fontWeight: 600,
                            color: '#6c757d'
                        },
                        value: {
                            show: true,
                            fontSize: '24px',
                            fontWeight: 700,
                            color: '#2c3e50',
                            formatter: function(val) {
                                return val;
                            }
                        },
                        total: {
                            show: true,
                            label: 'Total Tugas',
                            fontSize: '14px',
                            fontWeight: 600,
                            color: '#6c757d',
                            formatter: function(w) {
                                return w.globals.seriesTotals.reduce((a, b) => {
                                    return a + b;
                                }, 0);
                            }
                        }
                    }
                }
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function(val, opts) {
                return opts.w.config.series[opts.seriesIndex];
            },
            style: {
                fontSize: '14px',
                fontWeight: 600,
                colors: ['#fff']
            },
            dropShadow: {
                enabled: false
            }
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return val + ' tugas';
                }
            }
        },
        states: {
            hover: {
                filter: {
                    type: 'lighten',
                    value: 0.15
                }
            }
        }
    };
    new ApexCharts(document.querySelector("#chart-distribusi-beban-kerja"), distribusiBebanKerjaOptions).render();

    // ========== CHART 2: Tren Aktivitas Harian (Area Chart) ==========
    const trendAktivitasOptions = {
        series: [{
            name: 'Jumlah Laporan',
            data: {!! json_encode($trendAktivitas) !!}
        }],
        chart: {
            type: 'area',
            height: 300,
            fontFamily: 'Inter, sans-serif',
            toolbar: {
                show: false
            },
            zoom: {
                enabled: false
            }
        },
        colors: ['#5c92fe'],
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.5,
                opacityTo: 0.1,
                stops: [0, 90, 100]
            }
        },
        xaxis: {
            categories: {!! json_encode($trendLabels) !!},
            labels: {
                style: {
                    fontSize: '11px',
                    colors: '#6c757d'
                }
            },
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false
            }
        },
        yaxis: {
            labels: {
                style: {
                    fontSize: '11px',
                    colors: '#6c757d'
                },
                formatter: function(val) {
                    return Math.floor(val);
                }
            },
            min: 0
        },
        grid: {
            borderColor: '#e9ecef',
            strokeDashArray: 4,
            yaxis: {
                lines: {
                    show: true
                }
            },
            xaxis: {
                lines: {
                    show: false
                }
            }
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return val + ' laporan';
                }
            },
            style: {
                fontSize: '12px'
            }
        },
        markers: {
            size: 4,
            colors: ['#fff'],
            strokeColors: '#5c92fe',
            strokeWidth: 2,
            hover: {
                size: 6
            }
        }
    };
    new ApexCharts(document.querySelector("#chart-tren-aktivitas"), trendAktivitasOptions).render();

});
</script>
@endpush

@endsection
