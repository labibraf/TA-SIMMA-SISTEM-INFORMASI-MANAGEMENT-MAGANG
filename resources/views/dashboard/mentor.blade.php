@extends('layouts.mantis')

@section('content')
<div class="">
    <!-- PRIORITAS 1: Kartu Statistik Utama (At-a-Glance Cards) -->
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="card social-widget-card bg-success">
                <div class="card-body">
                    <h3 class="text-white m-0">{{ $pesertaAktif }} Peserta Aktif</h3>
                    <span class="m-t-10">dari {{ $totalPesertaBimbingan }} peserta</span>
                    <i class="ti ti-users"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card social-widget-card bg-danger">
                <div class="card-body">
                    <h3 class="text-white m-0">{{ $pesertaLulus }} Peserta Lulus</h3>
                    <span class="m-t-10">dari {{ $totalPesertaBimbingan }} Peserta</span>
                    <i class="ti ti-user-check"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card social-widget-card bg-warning">
                <div class="card-body">
                    <h3 class="text-white m-0">{{ $tugasAktif }} Tugas Aktif</h3>
                    <span class="m-t-10">Dari {{ $totalTugas }} Tugas</span>
                    <i class="ti ti-clipboard-check"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card social-widget-card bg-danger">
                <div class="card-body">
                    <h3 class="text-white m-0">{{ $reviewLaporanAkhir }}</h3>
                    <span class="m-t-10">Laporan Akhir Perlu Review</span>
                    <i class="ti ti-alert-triangle"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- PRIORITAS 2: Visualisasi Data (Charts) -->
        <div class="row mt-4">
            <!-- Chart: Distribusi Progress Peserta -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ti ti-chart-pie me-2"></i>Distribusi Progress Peserta</h5>
                    </div>
                    <div class="card-body">
                        <div id="chart-progress-distribusi" style="height: 300px;"></div>
                    </div>
                </div>
            </div>

            <!-- Chart: Status Penugasan -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ti ti-chart-donut me-2"></i>Status Penugasan</h5>
                    </div>
                    <div class="card-body">
                        <div id="chart-status-penugasan" style="height: 300px;"></div>
                    </div>
                </div>
            </div>
        </div>

    <!-- PRIORITAS 1: Tabel Menunggu Persetujuan Anda (Pending Approvals) -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="ti ti-clipboard-check me-2"></i>Tugas Menunggu Persetujuan</h5>
                    <a href="{{ route('penugasans.index') }}" class="btn btn-sm btn-outline-primary">
                        <i class="ti ti-eye me-1"></i>Lihat Semua
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Ditugaskan Kepada</th>
                                    <th>Judul Tugas</th>
                                    <th>Tanggal Pengumpulan</th>
                                    <th>Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tugasMenungguApproval as $tugas)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avtar avtar-s {{ $tugas->kategori === 'Individu' ? 'bg-light-primary' : 'bg-light-success' }} me-2">
                                                <i class="{{ $tugas->kategori === 'Individu' ? 'ti ti-user' : 'ti ti-users' }}"></i>
                                            </div>
                                            <div>
                                                @if($tugas->kategori === 'Individu' && $tugas->peserta)
                                                    <h6 class="mb-0">{{ $tugas->peserta->nama_lengkap }}</h6>
                                                    <small class="text-muted">{{ $tugas->peserta->asal_instansi }}</small>
                                                @elseif($tugas->kategori === 'Divisi' && $tugas->bagian)
                                                    <h6 class="mb-0">Divisi {{ $tugas->bagian->nama_bagian }}</h6>
                                                    <small class="text-muted">Tugas Divisi</small>
                                                @else
                                                    <h6 class="mb-0">Tidak Diketahui</h6>
                                                    <small class="text-muted">-</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <h6 class="mb-0">{{ $tugas->judul_tugas }}</h6>
                                            <small class="text-muted">{{ Str::limit($tugas->deskripsi_tugas, 50) }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <medium class="text-muted">
                                            <i class="ti ti-calendar me-1"></i>
                                            {{ \Carbon\Carbon::parse($tugas->updated_at)->format('d M Y') }}
                                        </medium>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning">Menunggu Review</span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('penugasans.show', $tugas->id) }}" class="btn btn-sm btn-primary">
                                            <i class="ti ti-eye me-1"></i>Review
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="ti ti-clipboard-check fs-1 text-muted d-block mb-2"></i>
                                        <p class="text-muted mb-0">Tidak ada tugas yang menunggu persetujuan</p>
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

    <!-- PRIORITAS 1: Tabel Progres Peserta Bimbingan -->
    <div class="row mt-4">
        <div class="col-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="ti ti-users me-2"></i>Progres Peserta Bimbingan</h5>
                    <a href="{{ route('peserta.index') }}" class="btn btn-sm btn-outline-primary">
                        <i class="ti ti-eye me-1"></i>Lihat Semua
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nama Peserta</th>
                                    <th>Asal Instansi</th>
                                    <th>Progress Magang</th>
                                    <th>Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pesertaBimbingan as $peserta)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avtar avtar-s bg-light-success me-2">
                                                <i class="ti ti-user"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $peserta->nama_lengkap }}</h6>
                                                <small class="text-muted">{{ $peserta->tipe_magang }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $peserta->asal_instansi }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="badge
                                                @if($peserta->progress_percentage >= 75) bg-success #28a745
                                                @elseif($peserta->progress_percentage >= 25) bg-warning #ffc107
                                                @else bg-danger #dc3545
                                                @endif">
                                                {{ number_format($peserta->progress_percentage, 1) }}%
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($peserta->progress_percentage >= 75)
                                            <span class="badge bg-success">Segera Selesai</span>
                                        @elseif($peserta->progress_percentage >= 25)
                                            <span class="badge bg-warning">Sedang Berlangsung</span>
                                        @else
                                            <span class="badge bg-danger">Belum Mulai</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('peserta.show', $peserta->id) }}" class="btn btn-sm btn-primary">
                                            <i class="ti ti-eye me-1"></i>Detail
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="ti ti-users fs-1 text-muted d-block mb-2"></i>
                                        <p class="text-muted mb-0">Belum ada peserta bimbingan</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="ti ti-file-text me-2"></i>Log Laporan Harian Terbaru</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nama Peserta</th>
                                    <th>Aktivitas/Tugas</th>
                                    <th>Tanggal</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($laporanHarianTerbaru as $laporan)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avtar avtar-s bg-light-info me-2">
                                                <i class="ti ti-user"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $laporan->peserta->nama_lengkap }}</h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            @if($laporan->penugasan)
                                                <h6 class="mb-0">{{ Str::limit($laporan->penugasan->judul_tugas, 40) }}</h6>
                                                <small class="text-muted">{{ Str::limit($laporan->deskripsi_kegiatan, 50) }}</small>
                                            @else
                                                <p class="mb-0">{{ Str::limit($laporan->deskripsi_kegiatan, 60) }}</p>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="ti ti-calendar me-1"></i>
                                            {{ \Carbon\Carbon::parse($laporan->created_at)->format('d M Y') }}
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        @if($laporan->penugasan_id)
                                            <a href="{{ route('penugasans.show', $laporan->penugasan_id) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="ti ti-eye"></i>
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <i class="ti ti-file-text fs-1 text-muted d-block mb-2"></i>
                                        <p class="text-muted mb-0">Belum ada laporan harian</p>
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

    <!-- PRIORITAS 2: Tabel Log Laporan Harian Terbaru -->
    <!-- <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="ti ti-file-text me-2"></i>Log Laporan Harian Terbaru</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nama Peserta</th>
                                    <th>Aktivitas/Tugas</th>
                                    <th>Tanggal</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($laporanHarianTerbaru as $laporan)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avtar avtar-s bg-light-info me-2">
                                                <i class="ti ti-user"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $laporan->peserta->nama_lengkap }}</h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            @if($laporan->penugasan)
                                                <h6 class="mb-0">{{ Str::limit($laporan->penugasan->judul_tugas, 40) }}</h6>
                                                <small class="text-muted">{{ Str::limit($laporan->deskripsi_kegiatan, 50) }}</small>
                                            @else
                                                <p class="mb-0">{{ Str::limit($laporan->deskripsi_kegiatan, 60) }}</p>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="ti ti-calendar me-1"></i>
                                            {{ \Carbon\Carbon::parse($laporan->created_at)->format('d M Y') }}
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        @if($laporan->penugasan_id)
                                            <a href="{{ route('penugasans.show', $laporan->penugasan_id) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="ti ti-eye"></i>
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <i class="ti ti-file-text fs-1 text-muted d-block mb-2"></i>
                                        <p class="text-muted mb-0">Belum ada laporan harian</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div> -->
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

.bg-light-primary { background-color: rgba(92, 146, 254, 0.1) !important; color: #5c92fe; }
.bg-light-info { background-color: rgba(32, 166, 231, 0.1) !important; color: #20a6e7; }
.bg-light-success { background-color: rgba(45, 206, 137, 0.1) !important; color: #2dce89; }
.bg-light-warning { background-color: rgba(255, 193, 7, 0.1) !important; color: #ffc107; }
.bg-light-danger { background-color: rgba(220, 53, 69, 0.1) !important; color: #dc3545; }

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
<script src="{{ asset('template/dist/assets/js/plugins/apexcharts.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart: Distribusi Progress Peserta
    const progressDistribusiOptions = {
        series: [{{ $pesertaPemula }}, {{ $pesertaMenengah }}, {{ $pesertaMahir }}],
        chart: {
            type: 'donut',
            height: 300
        },
        colors: ['#dc3545', '#ffc107', '#2dce89'],
        labels: ['Progress (<25%)', 'Progress (25-75%)', 'Progress (>75%)'],
        legend: {
            position: 'bottom',
            fontSize: '12px'
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Peserta Aktif',
                            fontSize: '14px',
                            formatter: function (w) {
                                return {{ $pesertaAktif }};
                            }
                        }
                    }
                }
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function(val) {
                return val.toFixed(1) + '%';
            }
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return val + ' peserta';
                }
            }
        }
    };
    new ApexCharts(document.querySelector("#chart-progress-distribusi"), progressDistribusiOptions).render();

    // Chart: Status Penugasan
    const statusPenugasanOptions = {
        series: [{{ $tugasSelesai }}, {{ $tugasDikerjakan }}, {{ $tugasBelumDimulai }}],
        chart: {
            type: 'pie',
            height: 300
        },
        colors: ['#2dce89', '#ffc107', '#dc3545'],
        labels: ['Selesai', 'Dikerjakan', 'Belum Dimulai'],
        legend: {
            position: 'bottom',
            fontSize: '12px'
        },
        dataLabels: {
            enabled: true,
            formatter: function(val) {
                return val.toFixed(1) + '%';
            }
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return val + ' tugas';
                }
            }
        }
    };
    new ApexCharts(document.querySelector("#chart-status-penugasan"), statusPenugasanOptions).render();
});
</script>
@endpush
@endsection
