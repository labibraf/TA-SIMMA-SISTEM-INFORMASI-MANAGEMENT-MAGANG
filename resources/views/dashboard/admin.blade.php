@extends('layouts.mantis')

@section('content')
<style>
    .card-grad-indigo { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .card-grad-pink   { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    .card-grad-blue   { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
    .card-grad-rose   { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }

    .card-palete1 { background-color: #E6F7FF ; }
    .card-palete2 { background-color: #DAA588 ; }
    .card-palete3 { background-color: #C46D5E ; }
    .card-palete4 { background-color: #F56960 ; }
    .card-palete5 { background-color: #87A878 ; }

    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .input-group-text {
        border-right: 0;
    }

    .input-group .form-control {
        border-left: 0;
    }

    .input-group-text,
    .input-group .form-control {
        background-color: #fff;
    }

    .input-group:focus-within .input-group-text,
    .input-group:focus-within .form-control {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
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
                                <i class="ti ti-dashboard me-2"></i>Dashboard Admin
                            </h2>
                            <p class="mb-0 mt-2 opacity-80">Monitoring & Statistik Peserta Magang (Berdasarkan Periode Magang)</p>
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

    {{-- Filter & Search Section --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="{{ route('home') }}" id="filterForm">
                        <i class="fas fa-info-circle mb-3"></i> Filter bedasarkan Periode Magang
                        <div class="row g-3">
                            {{-- Filter Tahun --}}
                            <div class="col-md-3">
                                <select name="tahun" class="form-select" id="tahunFilter">
                                    <option value="">Semua Tahun Magang</option>
                                    @foreach($tahunList as $year)
                                        <option value="{{ $year }}" {{ request('tahun') == $year ? 'selected' : '' }}>
                                            {{ $year }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Filter Bulan --}}
                            <div class="col-md-3">
                                <select name="bulan" class="form-select" id="bulanFilter">
                                    <option value="">Semua Bulan Magang</option>
                                    <option value="1" {{ request('bulan') == '1' ? 'selected' : '' }}>Januari</option>
                                    <option value="2" {{ request('bulan') == '2' ? 'selected' : '' }}>Februari</option>
                                    <option value="3" {{ request('bulan') == '3' ? 'selected' : '' }}>Maret</option>
                                    <option value="4" {{ request('bulan') == '4' ? 'selected' : '' }}>April</option>
                                    <option value="5" {{ request('bulan') == '5' ? 'selected' : '' }}>Mei</option>
                                    <option value="6" {{ request('bulan') == '6' ? 'selected' : '' }}>Juni</option>
                                    <option value="7" {{ request('bulan') == '7' ? 'selected' : '' }}>Juli</option>
                                    <option value="8" {{ request('bulan') == '8' ? 'selected' : '' }}>Agustus</option>
                                    <option value="9" {{ request('bulan') == '9' ? 'selected' : '' }}>September</option>
                                    <option value="10" {{ request('bulan') == '10' ? 'selected' : '' }}>Oktober</option>
                                    <option value="11" {{ request('bulan') == '11' ? 'selected' : '' }}>November</option>
                                    <option value="12" {{ request('bulan') == '12' ? 'selected' : '' }}>Desember</option>
                                </select>
                            </div>

                            {{-- Filter Departemen/Bagian --}}
                            <div class="col-md-4">
                                <select name="bagian" class="form-select">
                                    <option value="">Semua Departemen</option>
                                    @foreach($bagianDistribution as $bagianItem)
                                        <option value="{{ $bagianItem->nama_bagian }}" {{ request('bagian') == $bagianItem->nama_bagian ? 'selected' : '' }}>
                                            {{ $bagianItem->nama_bagian }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Button Search & Reset --}}
                            <div class="col-md-2">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary flex-fill">
                                        <i class="ti ti-filter me-1"></i>Filter
                                    </button>
                                    <a href="{{ route('home') }}" class="btn btn-secondary" title="Reset Filter">
                                        <i class="ti ti-refresh"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Info Badge --}}
    @if(request('tahun') || request('bulan') || request('bagian'))
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-info alert-dismissible fade show mb-0" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="ti ti-info-circle me-2"></i>
                        <div class="flex-grow-1">
                            <strong>Filter Aktif:</strong>
                            @if(request('tahun'))
                                <span class="bg-primary ms-2 rounded-pill px-2 text-white">
                                    <i class="ti ti-calendar me-1"></i>{{ request('tahun') }}
                                </span>
                            @endif
                            @if(request('bulan'))
                                @php
                                    $bulanNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                                    $bulanName = $bulanNames[request('bulan')] ?? request('bulan');
                                @endphp
                                <span class="bg-primary ms-2 rounded-pill px-2 text-white">
                                    <i class="ti ti-calendar-event me-1"></i>{{ $bulanName }}
                                </span>
                            @endif
                            @if(request('bagian'))
                                <span class="bg-primary ms-2 rounded-pill px-2 text-white">
                                    <i class="ti ti-building me-1"></i>Bagian: {{ request('bagian') }}
                                </span>
                            @endif
                            <span class="bg-success ms-2 rounded-pill px-2 text-white">
                                <i class="ti ti-users me-1"></i>{{ $totalPeserta }} Peserta
                            </span>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-4 col-xl-4">
            <div class="card social-widget-card card-palete2">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="ti ti-users d-block f-46 text-white opacity-50"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-white mb-1">Total Peserta Aktif</p>
                            <h3 class="text-white mb-0">{{ number_format($pesertaAktif) }} / <span class="text-white opacity-75 small">{{ $totalPeserta }} Peserta</span></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-xl-4">
            <div class="card social-widget-card card-palete3">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="ti ti-accessible d-block f-46 text-white opacity-50"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-white mb-1">Total Mentor Aktif</p>
                            <h3 class="text-white mb-0">{{ number_format($totalMentor) }} Mentor</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-xl-4">
            <div class="card social-widget-card card-palete4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="ti ti-building d-block f-46 text-white opacity-50"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-white mb-1">Total Departemen</p>
                            <h3 class="text-white mb-0">{{ number_format($totalBagian) }} Divisi</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div}
    {{-- <!-- Status Overview --!> --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-8 col-md-12">
            <div class="card h-100 mb-3 d-flex flex-column">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">Tren Pendaftaran Magang</h4>
                        <p class="mb-0 mt-2 opacity-50"><i class="fas fa-info-circle"></i> Berdasarkan Tanggal Mulai Magang</p>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="trendPeriodDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ $trendPeriod }} Bulan Terakhir
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="trendPeriodDropdown">
                            <li><a class="dropdown-item {{ $trendPeriod == 3 ? 'active' : '' }}" href="#" onclick="changeTrendPeriod(3)">3 Bulan Terakhir</a></li>
                            <li><a class="dropdown-item {{ $trendPeriod == 6 ? 'active' : '' }}" href="#" onclick="changeTrendPeriod(6)">6 Bulan Terakhir</a></li>
                            <li><a class="dropdown-item {{ $trendPeriod == 9 ? 'active' : '' }}" href="#" onclick="changeTrendPeriod(9)">9 Bulan Terakhir</a></li>
                            <li><a class="dropdown-item {{ $trendPeriod == 12 ? 'active' : '' }}" href="#" onclick="changeTrendPeriod(12)">12 Bulan Terakhir</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body flex-grow-1">
                    <div id="monthly-trend-chart" class="h-100"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-12">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Status Peserta Magang</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-12 mb-3">
                            <div class="py-3 rounded bg-light-primary">
                                <h3 class="mb-1 text-primary">{{ $pesertaAktif }}</h3>
                                <p class="mb-0">Aktif Magang</p>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="py-3 rounded bg-light-warning">
                                <h4 class="mb-1 text-warning">{{ $pesertaHampirSelesai }}</h4>
                                <p class="mb-0">Dibawah 50%</p>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="py-3 rounded bg-light-success">
                                <h4 class="mb-1 text-success">{{ $pesertaSelesai }}</h4>
                                <p class="mb-0">Diatas 50%</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Quick Actions -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Aksi Cepat</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('peserta.create') }}" class="btn btn-primary">
                            <i class="ti ti-user-plus me-2"></i> Tambah Peserta
                        </a>
                        <a href="{{ route('mentor.create') ?? '#' }}" class="btn btn-info">
                            <i class="ti ti-user-shield me-2"></i> Tambah Mentor
                        </a>
                        @if(Route::has('bagian.create'))
                        <a href="{{ route('bagian.create') }}" class="btn btn-success">
                            <i class="ti ti-building me-2"></i> Tambah Bagian
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Peserta & Quick Actions -->
    <div class="row mt-4">
        <div class="col-xl-12 col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ti ti-check-circle text-success me-2"></i>Peserta yang Sudah Menyelesaikan Magang
                    </h5>
                    <a href="{{ route('peserta.index') }}" class="btn btn-sm btn-primary rounded">Lihat Semua</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-borderless mb-0">
                            <thead>
                                <tr>
                                    <th>Peserta</th>
                                    <th>Asal Instansi</th>
                                    <th>Bagian</th>
                                    <th>Progress</th>
                                    <th>Tanggal Selesai</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentPeserta as $peserta)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($peserta->foto)
                                            <img src="{{ asset('storage/foto_peserta/'.$peserta->foto) }}"
                                                 class="rounded-circle me-2" width="40" height="40" alt="">
                                            @else
                                            <div class="avtar avtar-s bg-light-secondary me-2">
                                                <span>{{ substr($peserta->nama_lengkap, 0, 1) }}</span>
                                            </div>
                                            @endif
                                            <div>
                                                <h6 class="mb-0">{{ $peserta->nama_lengkap }}</h6>
                                                <small class="text-muted">{{ $peserta->email ?? '-' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $peserta->asal_instansi ?? '-' }}</td>
                                    <td>
                                        <span class="badge bg-light-primary">
                                            {{ $peserta->bagian?->nama_bagian ?? 'Belum Ditentukan' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1 me-2">
                                                <div class="progress" style="height: 6px;">
                                                    <div class="progress-bar bg-success"
                                                         role="progressbar"
                                                         style="width: {{ $peserta->progress_percentage }}%"
                                                         aria-valuenow="{{ $peserta->progress_percentage }}"
                                                         aria-valuemin="0"
                                                         aria-valuemax="100">
                                                    </div>
                                                </div>
                                            </div>
                                            <small class="text-success fw-semibold">{{ $peserta->progress_percentage }}%</small>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="ti ti-calendar-check me-1"></i>
                                            {{ $peserta->tanggal_selesai }}
                                        </small>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('peserta.show', $peserta->id) }}"
                                           class="btn btn-sm btn-icon btn-light"
                                           data-bs-toggle="tooltip"
                                           title="Detail Peserta">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="ti ti-users-off fs-3 d-block mb-2"></i>
                                            Belum ada peserta yang menyelesaikan magang
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-12">
        </div>
    </div>

    <!-- Department Distribution -->
    <div class="row">
        <div class="col-xl-4 col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Distribusi per Departemen</h5>
                    @if(Route::has('bagian.index'))
                    <a href="{{ route('bagian.index') }}" class="btn btn-sm btn-light">
                        <i class="ti ti-external-link"></i>
                    </a>
                    @endif
                </div>
                <div class="card-body">
                    <div id="department-chart" style="height: 300px;"></div>
                </div>
            </div>
        </div>

         <!-- Laporan Akhir Status -->
        <div class="col-xl-4 col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Status Laporan Akhir</h5>
                </div>
                <div class="card-body">
                    <div id="laporan-akhir-chart" style="height: 250px;"></div>
                    <div class="mt-4 text-center">
                        <div class="row">
                            <div class="col-4">
                                <h6 class="mb-1 text-success">{{ $laporanAkhirSelesai }}</h6>
                                <small class="text-muted">Selesai</small>
                            </div>
                            <div class="col-4">
                                <h6 class="mb-1 text-warning">{{ $laporanAkhirBelum }}</h6>
                                <small class="text-muted">Belum</small>
                            </div>
                            <div class="col-4">
                                <h6 class="mb-1 text-warning">{{ $laporanAkhirTolak }}</h6>
                                <small class="text-muted">Ditolak</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card col-xl-4 col-md-12">
            <div class="card-header">
                <h5 class="mb-0">Jenis Magang</h5>
            </div>
            <div class="card-body">
                <div id="internship-type-chart" style="height: 280px;"></div>
                    <div class="mt-3">
                        <div class="row text-center">
                            <div class="col-4">
                                <h6 class="mb-1 text-primary">{{ $magangKP }}</h6>
                                <small class="text-muted">Kerja Praktik</small>
                            </div>
                            <div class="col-4">
                                <h6 class="mb-1 text-success">{{ $magangNasional }}</h6>
                                <small class="text-muted">Magang Nasional</small>
                            </div>
                            <div class="col-4">
                                <h6 class="mb-1 text-warning">{{ $magangPenelitian }}</h6>
                                <small class="text-muted">Penelitian</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Analytics -->
    <div class="row">
        <!-- Internship Type Distribution -->
        <div class="col-xl-6 col-md-6">
            <!-- Top Institutions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Top 5 Asal Institusi</h5>
                </div>
                <div class="card-body">
                    <div id="institutions-chart" style="height: 280px;"></div>
                    <div class="mt-3">
                        @foreach($topInstitutions->take(3) as $index => $institution)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center">
                                <div class="avtar avtar-s me-2" style="background-color: {{ ['#5c92fe', '#2dce89', '#ffc107'][$index] }}20;">
                                    <span style="color: {{ ['#5c92fe', '#2dce89', '#ffc107'][$index] }};">{{ $index + 1 }}</span>
                                </div>
                                <span class="text-truncate" style="max-width: 250px;">{{ $institution->asal_instansi }}</span>
                            </div>
                            <span class="badge bg-light-primary">{{ $institution->count }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Metrics Summary -->
        <div class="col-xl-6 col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Ringkasan Metrik</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <div class="d-flex align-items-center justify-content-between p-3 bg-light-success rounded">
                                <div>
                                    <h6 class="mb-1">Rata-rata Peserta per Mentor</h6>
                                    <h4 class="mb-0 text-success">{{ $rataRataPesertaPerMentor }}</h4>
                                </div>
                                <div class="avtar avtar-xl bg-success">
                                    <i class="ti ti-users-group fs-1 text-white"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <div class="d-flex align-items-center justify-content-between p-3 bg-light-primary rounded">
                                <div>
                                    <h6 class="mb-1">Total Tugas Tersedia</h6>
                                    <h4 class="mb-0 text-primary">{{ number_format($totalTugas) }}</h4>
                                </div>
                                <div class="avtar avtar-xl bg-primary">
                                    <i class="ti ti-clipboard-list fs-1 text-white"></i>
                                </div>
                            </div>
                        </div>
                        @if($mentorTertinggi)
                        <div class="col-12">
                            <div class="d-flex align-items-center justify-content-between p-3 bg-light-warning rounded">
                                <div>
                                    <h6 class="mb-1">Mentor Terbanyak Peserta</h6>
                                    <h5 class="mb-0 text-warning">{{ $mentorTertinggi->nama_mentor }}</h5>
                                    <small class="text-muted">{{ $mentorTertinggi->pesertas_count }} peserta</small>
                                </div>
                                <div class="avtar avtar-xl bg-warning">
                                    <i class="ti ti-award fs-1 text-white"></i>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Analytics Section -->
    <div class="row">

    </div>

    <!-- ==================== NEW SECTION A: LINE/AREA CHARTS ==================== -->
    <div class="row">
        <!-- Daily Activity Trend -->
        <div class="col-xl-12 col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ti ti-chart-line text-primary"></i> Tren Aktivitas Harian (30 Hari Terakhir)</h5>
                </div>
                <div class="card-body">
                    <div id="daily-activity-chart"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== NEW SECTION B: BAR CHARTS ==================== -->
    <div class="row">
        <!-- Mentor Performance -->
        <div class="col-xl-4 col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ti ti-award text-warning"></i> Performa Mentor</h5>
                </div>
                <div class="card-body">
                    <div id="mentor-performance-chart"></div>
                </div>
            </div>
        </div>

        <!-- Task Categories Distribution -->
        <div class="col-xl-4 col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ti ti-category text-info"></i> Distribusi Kategori Tugas</h5>
                </div>
                <div class="card-body">
                    <div id="task-categories-chart"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ti ti-target-arrow"></i> Metode Penetapan Target Peserta</h5>
                </div>
                <div class="card-body">
                    <div id="target-method-chart"></div>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span><i class="ti ti-circle-filled text-primary"></i> Berbasis SKS</span>
                            <strong>{{ $targetMethodSKS }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span><i class="ti ti-circle-filled text-info"></i> Manual</span>
                            <strong>{{ $targetMethodManual }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== NEW SECTION C: ADDITIONAL PIE CHARTS ==================== -->
    <div class="row">
        <!-- Target Method Distribution -->

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

.bg-light-primary { background-color: rgba(92, 146, 254, 0.1) !important; }
.bg-light-info { background-color: rgba(32, 166, 231, 0.1) !important; }
.bg-light-success { background-color: rgba(45, 206, 137, 0.1) !important; }
.bg-light-warning { background-color: rgba(255, 193, 7, 0.1) !important; }
.bg-light-secondary { background-color: rgba(108, 117, 125, 0.1) !important; }

.progress {
    border-radius: 10px;
    background-color: #f8f9fa;
}

.progress-bar {
    border-radius: 10px;
}

.card {
    border: 1px solid #e9ecef;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.table th {
    font-weight: 600;
    font-size: 0.875rem;
    color: #6c757d;
    border-bottom: 1px solid #dee2e6;
}
</style>
@endpush

@push('scripts')
<!-- ApexCharts -->
<script src="{{ asset('template/dist/assets/js/plugins/apexcharts.min.js') }}"></script>

<script>
// Function to change trend period
function changeTrendPeriod(period) {
    event.preventDefault();

    // Get current URL parameters
    const urlParams = new URLSearchParams(window.location.search);

    // Set or update trend_period parameter
    urlParams.set('trend_period', period);

    // Reload page with new parameter
    window.location.search = urlParams.toString();
}

document.addEventListener('DOMContentLoaded', function() {
    // Monthly Trend Chart
    const monthlyTrendOptions = {
        series: [{
            name: 'Pendaftaran',
            data: @json(array_column($monthlyTrend, 'count'))
        }],
        chart: {
            type: 'area',
            height: 400,
            toolbar: { show: false }
        },
        colors: ['#5c92fe'],
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.3,
            }
        },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 2 },
        xaxis: {
            categories: @json(array_column($monthlyTrend, 'month')),
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            title: { text: 'Jumlah Peserta' }
        },
        grid: {
            borderColor: '#f1f1f1',
            strokeDashArray: 4
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + " peserta"
                }
            }
        }
    };
    new ApexCharts(document.querySelector("#monthly-trend-chart"), monthlyTrendOptions).render();

    // Department Chart
    const departmentOptions = {
        series: [{
            name: 'Peserta',
            data: @json($bagianDistribution->pluck('pesertas_count')->toArray())
        }],
        chart: {
            type: 'bar',
            height: 300,
            toolbar: { show: false }
        },
        colors: ['#2dce89'],
        plotOptions: {
            bar: {
                horizontal: true,
                borderRadius: 4,
                dataLabels: {
                    position: 'top'
                }
            }
        },
        dataLabels: {
            enabled: true,
            offsetX: -6,
            style: {
                fontSize: '12px',
                colors: ['#fff']
            }
        },
        xaxis: {
            categories: @json($bagianDistribution->pluck('nama_bagian')->toArray()),
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            title: { text: 'Bagian' }
        },
        grid: {
            borderColor: '#f1f1f1'
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + " peserta"
                }
            }
        }
    };
    new ApexCharts(document.querySelector("#department-chart"), departmentOptions).render();

    // Laporan Akhir Chart
    const laporanAkhirOptions = {
        series: [{{ $laporanAkhirSelesai }}, {{ $laporanAkhirBelum }}, {{ $laporanAkhirTolak }}],
        chart: {
            type: 'pie',
            height: 250
        },
        colors: ['#2dce89', '#ffc107', '#dc3545'],
        labels: ['Selesai', 'Belum Selesai', 'Ditolak'],
        legend: {
            position: 'bottom',
            fontSize: '12px'
        },
        dataLabels: {
            enabled: true,
            formatter: function(val) {
                return Math.round(val) + '%';
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + " peserta"
                }
            }
        }
    };
    new ApexCharts(document.querySelector("#laporan-akhir-chart"), laporanAkhirOptions).render();

    // Task Status Chart
    const taskStatusOptions = {
        series: [{{ $tugasSelesai }}, {{ $tugasBerjalan }}, {{ $tugasTerlambat }}],
        chart: {
            type: 'pie',
            height: 250
        },
        colors: ['#2dce89', '#ffc107', '#dc3545'],
        labels: ['Selesai', 'Berlangsung', 'Terlambat'],
        legend: {
            position: 'bottom',
            fontSize: '11px'
        },
        dataLabels: {
            enabled: true,
            formatter: function(val) {
                return Math.round(val) + '%';
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + " tugas"
                }
            }
        }
    };
    new ApexCharts(document.querySelector("#task-status-chart"), taskStatusOptions).render();

    // Gender Distribution Chart
    const genderOptions = {
        series: [{{ $pesertaLakiLaki }}, {{ $pesertaPerempuan }}],
        chart: {
            type: 'donut',
            height: 250
        },
        colors: ['#5c92fe', '#e83e8c'],
        labels: ['Laki-laki', 'Perempuan'],
        legend: {
            position: 'bottom',
            fontSize: '11px'
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '60%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total',
                            formatter: function () {
                                return {{ $totalPeserta }}
                            }
                        }
                    }
                }
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function(val) {
                return Math.round(val) + '%';
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + " peserta"
                }
            }
        }
    };
    new ApexCharts(document.querySelector("#gender-chart"), genderOptions).render();

    // Target Achievement Chart
    const targetAchievementOptions = {
        series: [{{ $pesertaTargetTercapai }}, {{ $pesertaTargetBelum }}],
        chart: {
            type: 'donut',
            height: 250
        },
        colors: ['#2dce89', '#ffc107'],
        labels: ['Target Tercapai', 'Belum Tercapai'],
        legend: {
            position: 'bottom',
            fontSize: '11px'
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '60%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Progress',
                            formatter: function () {
                                const percentage = {{ $totalPeserta > 0 ? round(($pesertaTargetTercapai / $totalPeserta) * 100, 1) : 0 }};
                                return percentage + '%'
                            }
                        }
                    }
                }
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function(val) {
                return Math.round(val) + '%';
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + " peserta"
                }
            }
        }
    };
    new ApexCharts(document.querySelector("#target-achievement-chart"), targetAchievementOptions).render();

    // Internship Type Chart
    const internshipTypeOptions = {
        series: [{{ $magangKP }}, {{ $magangNasional }}, {{ $magangPenelitian }}],
        chart: {
            type: 'pie',
            height: 280
        },
        colors: ['#5c92fe', '#2dce89', '#ffc107'],
        labels: ['Kerja Praktik', 'Magang Nasional', 'Penelitian'],
        legend: {
            position: 'bottom',
            fontSize: '11px'
        },
        dataLabels: {
            enabled: true,
            formatter: function(val, opts) {
                return Math.round(val) + '%';
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + " peserta"
                }
            }
        }
    };
    new ApexCharts(document.querySelector("#internship-type-chart"), internshipTypeOptions).render();

    // Task Approval Chart
    const taskApprovalOptions = {
        series: [{{ $tugasApproved }}, {{ $tugasPendingApproval }}],
        chart: {
            type: 'donut',
            height: 280
        },
        colors: ['#2dce89', '#ffc107'],
        labels: ['Disetujui', 'Menunggu Persetujuan'],
        legend: {
            position: 'bottom',
            fontSize: '11px'
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total Tugas',
                            formatter: function () {
                                return {{ $tugasApproved + $tugasPendingApproval }}
                            }
                        }
                    }
                }
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function(val) {
                return Math.round(val) + '%';
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + " tugas"
                }
            }
        }
    };
    new ApexCharts(document.querySelector("#task-approval-chart"), taskApprovalOptions).render();

    // Top Institutions Chart
    const institutionsOptions = {
        series: [{
            name: 'Jumlah Peserta',
            data: @json($topInstitutions->pluck('count')->toArray())
        }],
        chart: {
            type: 'bar',
            height: 280,
            toolbar: { show: false }
        },
        colors: ['#5c92fe'],
        plotOptions: {
            bar: {
                borderRadius: 6,
                dataLabels: {
                    position: 'top'
                },
                columnWidth: '60%'
            }
        },
        dataLabels: {
            enabled: true,
            offsetY: -20,
            style: {
                fontSize: '12px',
                colors: ['#5c92fe']
            }
        },
        xaxis: {
            categories: @json($topInstitutions->pluck('asal_instansi')->map(function($name) { return Str::limit($name, 15); })->toArray()),
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: {
                rotate: -45,
                style: {
                    fontSize: '10px'
                }
            }
        },
        yaxis: {
            title: { text: 'Jumlah Peserta' }
        },
        grid: {
            borderColor: '#f1f1f1'
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + " peserta"
                }
            }
        }
    };
    new ApexCharts(document.querySelector("#institutions-chart"), institutionsOptions).render();

    // ==================== NEW CHARTS SECTION A: LINE/AREA CHARTS ====================

    // Daily Activity Trend Chart
    const dailyActivityOptions = {
        series: [{
            name: 'Laporan Harian',
            data: @json(array_column($dailyActivityTrend, 'count'))
        }],
        chart: {
            type: 'area',
            height: 300,
            toolbar: { show: false },
            zoom: { enabled: false }
        },
        colors: ['#5c92fe'],
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.3,
                stops: [0, 90, 100]
            }
        },
        dataLabels: { enabled: false },
        stroke: {
            curve: 'smooth',
            width: 2
        },
        xaxis: {
            categories: @json(array_column($dailyActivityTrend, 'date')),
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: {
                rotate: -45,
                style: { fontSize: '11px' }
            }
        },
        yaxis: {
            title: { text: 'Jumlah laporan Harian' }
        },
        grid: {
            borderColor: '#f1f1f1',
            strokeDashArray: 4
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + " laporan"
                }
            }
        }
    };
    new ApexCharts(document.querySelector("#daily-activity-chart"), dailyActivityOptions).render();

    // Mentor Performance Chart (Grouped Bar)
    const mentorPerformanceOptions = {
        series: [
            {
                name: 'Total Peserta',
                data: @json($mentorPerformance->pluck('total_peserta')->toArray())
            },
            {
                name: 'Selesai',
                data: @json($mentorPerformance->pluck('completed')->toArray())
            }
        ],
        chart: {
            type: 'bar',
            height: 300,
            toolbar: { show: false }
        },
        colors: ['#5c92fe', '#2dce89'],
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '55%',
                borderRadius: 4,
                dataLabels: {
                    position: 'top'
                }
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            show: true,
            width: 2,
            colors: ['transparent']
        },
        xaxis: {
            categories: @json($mentorPerformance->pluck('nama')->toArray()),
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: {
                rotate: -45,
                style: { fontSize: '10px' }
            }
        },
        yaxis: {
            title: { text: 'Jumlah Peserta' }
        },
        grid: {
            borderColor: '#f1f1f1'
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right'
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + " peserta"
                }
            }
        }
    };
    new ApexCharts(document.querySelector("#mentor-performance-chart"), mentorPerformanceOptions).render();

    // Task Categories Distribution Chart
    const taskCategoriesOptions = {
        series: [
            {
                name: 'Total Tugas',
                data: [{{ $taskIndividu }}, {{ $taskDivisi }}]
            },
            {
                name: 'Selesai',
                data: [{{ $taskIndividuSelesai }}, {{ $taskDivisiSelesai }}]
            }
        ],
        chart: {
            type: 'bar',
            height: 300,
            toolbar: { show: false }
        },
        colors: ['#ffc107', '#2dce89'],
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '50%',
                borderRadius: 4
            }
        },
        dataLabels: {
            enabled: true,
            offsetY: -20,
            style: {
                fontSize: '12px',
                colors: ['#304758']
            }
        },
        stroke: {
            show: true,
            width: 2,
            colors: ['transparent']
        },
        xaxis: {
            categories: ['Individu', 'Divisi'],
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            title: { text: 'Jumlah Tugas' }
        },
        grid: {
            borderColor: '#f1f1f1'
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right'
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + " tugas"
                }
            }
        }
    };
    new ApexCharts(document.querySelector("#task-categories-chart"), taskCategoriesOptions).render();

    // Target Method Distribution Chart
    const targetMethodOptions = {
        series: [{{ $targetMethodSKS }}, {{ $targetMethodManual }}],
        chart: {
            type: 'pie',
            height: 250
        },
        colors: ['#5c92fe', '#20a7e7'],
        labels: ['Berbasis SKS', 'Manual'],
        legend: {
            position: 'bottom',
            fontSize: '11px'
        },
        dataLabels: {
            enabled: true,
            formatter: function(val, opts) {
                return opts.w.config.series[opts.seriesIndex] + ' (' + Math.round(val) + '%)'
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + " peserta"
                }
            }
        }
    };
    new ApexCharts(document.querySelector("#target-method-chart"), targetMethodOptions).render();

    // Filter Enhancement
    const tahunSelect = document.querySelector('select[name="tahun"]');
    const bulanSelect = document.getElementById('bulanFilter');

    if (tahunSelect && bulanSelect) {
        // Disable bulan if tahun not selected
        function toggleBulan() {
            if (!tahunSelect.value) {
                bulanSelect.disabled = true;
                bulanSelect.value = '';
            } else {
                bulanSelect.disabled = false;
            }
        }
        toggleBulan();
        tahunSelect.addEventListener('change', toggleBulan);
    }
});
</script>
@endpush
@endsection
