@extends('layouts.mantis')

@section('content')
<style>
    .card-grad-success { background: linear-gradient(135deg, #2dce89 0%, #2dcecc 100%); }
    .card-grad-primary { background: linear-gradient(135deg, #5c92fe 0%, #825ee4 100%); }
    .card-grad-warning { background: linear-gradient(135deg, #ffc107 0%, #ff8b67 100%); }
    .card-grad-info    { background: linear-gradient(135deg, #20a6e7 0%, #4facfe 100%); }
    .card-template1  { background:#DAA588 ; }
    .card-template2  { background:#C46D5E ; }
    .card-template3  { background:#F56960 ; }
    .card-template4  { background:#87A878 ; }
</style>

<div class="">
    {{-- Header Section --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-blue-500 text-white">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-2 text-white">
                                <i class="ti ti-dashboard me-2"></i>Dashboard Peserta
                            </h2>
                            <p class="mb-0 mt-2 opacity-80">Monitoring & Statistik Kegiatan Magang - <strong>{{ $peserta->bagian->nama_bagian ?? '-' }}</strong></p>
                        </div>
                        <div>
                            <span class="badge rounded-pill bg-white text-primary fs-5 px-4 py-2">
                                <i class="ti ti-calendar me-2"></i>{{ now()->format('d M Y') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- PRIORITAS 1: Kartu Statistik Utama (At-a-Glance Cards) -->
    <div class="row">
        <!-- Kartu 1: Progres Magang -->
        <div class="col-md-3 col-sm-6">
            <div class="card card-template1 text-white">
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
                        <i class="ti ti-calendar me-1"></i> Tersisah {{ $sisaWaktu }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Kartu 2: Total Jam Tercapai -->
        <div class="col-md-3 col-sm-6">
            <div class="card card-template2 text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h3 class="text-white m-0 mb-1">{{ number_format($totalJamTercapai, 1) }}</h3>
                            <p class="mb-0 text-white-50">Total Jam Tercapai</p>
                        </div>
                        <div class="avtar avtar-xl bg-white bg-opacity-25">
                            <i class="ti ti-clock fs-1"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="mb-0 text-white">
                            <span class="fw-semibold">Target Minimal: {{ number_format($targetJam, 1) }} jam</span>
                        </p>
                        @php
                            $selisihJam = $targetJam - $totalJamTercapai;
                        @endphp
                        <p class="mb-0 text-white-50 mt-1">
                            @if($selisihJam > 0)
                                <i class="ti ti-arrow-up-right me-1"></i>Kurang {{ number_format($selisihJam, 1) }} jam lagi
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
            <div class="card card-template3 text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h3 class="text-white m-0 mb-1">{{ $tugasAktif }}</h3>
                            <p class="mb-0 text-white-50">Tugas Tersedia</p>
                        </div>
                        <div class="avtar avtar-xl bg-white bg-opacity-25">
                            <i class="ti ti-clipboard-check fs-1"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        @if($tugasAktif > 0)
                            <p class="mb-0 text-white">
                                <span class="fw-semibold">{{ $tugasAktif }} tugas perlu dikerjakan</span>
                            </p>
                            <p class="mb-0 text-white-50 mt-1">
                                <i class="ti ti-alert-circle me-1"></i>Belum dimulai / sedang dikerjakan
                            </p>
                        @else
                            <p class="mb-0 text-white">
                                <span class="fw-semibold">Semua tugas selesai!</span>
                            </p>
                            <p class="mb-0 text-white-50 mt-1">
                                <i class="ti ti-check me-1"></i>Tidak ada tugas yang tersisa
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Kartu 4: Status Laporan Akhir -->
        <div class="col-md-3 col-sm-6">
            <div class="card card-template4 text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-white m-0 mb-3">Laporan Akhir</h6>
                            <span class="badge {{ $badgeClass }} fs-5 px-3 py-1.5 rounded-pill">
                                {{ $statusLaporanAkhir }}
                            </span>
                        </div>
                        <div class="avtar avtar-xl bg-white bg-opacity-25">
                            <i class="ti ti-file-text fs-1"></i>
                        </div>
                    </div>
                    <div class="mt-4 rounded-pill">
                        @if($statusLaporanAkhir === 'Belum Mengajukan')
                            <a href="{{ route('laporan-akhir.create') }}" class="btn btn-outline-light d-inline-flex ">
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
                    <a href="{{ route('penugasans.index') }}" class="btn btn-primary">
                        Lihat Semua Tugas
                    </a>
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
                                            // Hitung hari tersisa untuk badge "Segera!"
                                            $deadline = \Carbon\Carbon::parse($tugas->deadline);
                                        @endphp
                                        @if($tugas->isGugur)
                                            <br><span class="badge bg-danger badge-sm">
                                                <i class="ti ti-alarm me-1"></i>Terlambat
                                            </span>
                                        @elseif($tugas->is_approved != 1)
                                            <br><span class="badge bg-warning badge-sm">
                                                <i class="ti ti-clock me-1"></i>Segera!
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($tugas->isGugur)
                                            <span class="badge bg-danger">
                                                <i class="ti ti-alarm me-1"></i>Telat/Gugur
                                            </span>
                                        @elseif($tugas->is_approved == 1)
                                            <span class="badge bg-success">
                                                <i class="ti ti-check me-1"></i>Selesai
                                            </span>
                                        @elseif($tugas->status_tugas === 'Selesai')
                                            <span class="badge bg-warning">
                                                <i class="ti ti-clock me-1"></i>Review
                                            </span>
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
        <div class="col-xl-4 col-md-12">
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
                        <div class="col-3">
                            <div class="p-2">
                                <i class="ti ti-circle-filled text-success"></i>
                                <h6 class="mb-0 mt-1">{{ $tugasSelesai }}</h6>
                                <small class="text-muted">Selesai</small>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2">
                                <i class="ti ti-circle-filled text-warning"></i>
                                <h6 class="mb-0 mt-1">{{ $tugasDikerjakan }}</h6>
                                <small class="text-muted">Dikerjakan</small>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2">
                                <i class="ti ti-circle-filled text-danger"></i>
                                <h6 class="mb-0 mt-1">{{ $tugasTerlambat }}</h6>
                                <small class="text-muted">Terlambat</small>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2">
                                <i class="ti ti-circle-filled text-primary"></i>
                                <h6 class="mb-0 mt-1">{{ $tugasBelumDimulai }}</h6>
                                <small class="text-muted">Belum Dimulai</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Riwayat Tugas Selesai -->
        <div class="col-xl-8 col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="ti ti-checkbox me-2"></i>Riwayat Tugas Selesai
                            </h5>
                            <p class="text-muted small mb-0">Tugas yang telah Anda selesaikan</p>
                        </div>
                        <a href="{{ route('penugasans.index') }}" class="btn btn-sm btn-outline-primary">
                            <i class="ti ti-list me-1"></i>Lihat Semua
                        </a>
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
        </div>
    </div>

    <!-- Tabel Log Laporan Harian Terbaru -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <i class="ti ti-file-text me-2"></i>Catatan Harian Terbaru
                        </h5>
                        <p class="text-muted small mb-0">7 data terakhir dari aktivitas Anda</p>
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
                                                <i class="ti ti-circle-check me-1"></i>Selesai
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
        series: [{{ $tugasSelesai }}, {{ $tugasDikerjakan }}, {{ $tugasTerlambat }}, {{ $tugasBelumDimulai }}],
        chart: {
            type: 'donut',
            height: 300,
            fontFamily: 'Inter, sans-serif'
        },
        colors: ['#2dce89', '#ffc107', '#dc3545', '#5c92fe'],
        labels: ['Selesai', 'Dikerjakan', 'Terlambat', 'Belum Dimulai'],
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

});
</script>
@endpush

@endsection
